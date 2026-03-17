<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 badges plugin for GLPI
 Copyright (C) 2009-2022 by the badges Development Team.

 https://github.com/InfotelGLPI/badges
 -------------------------------------------------------------------------

 LICENSE

 This file is part of badges.

 badges is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 badges is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with badges. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use Glpi\Search\SearchOption;
use GlpiPlugin\Badges\Badge;
use GlpiPlugin\Badges\BadgeInjection;
use GlpiPlugin\Badges\BadgeType;
use GlpiPlugin\Badges\Config;
use GlpiPlugin\Badges\NotificationState;
use GlpiPlugin\Badges\NotificationTargetBadge;
use GlpiPlugin\Badges\Profile;
use GlpiPlugin\Badges\Request;

function plugin_badges_install()
{
    global $DB;

    $migration = new Migration(PLUGIN_BADGES_VERSION);
    Badge::install($migration);
    BadgeType::install($migration);
    Config::install($migration);
    NotificationState::install($migration);
    Request::install($migration);
    NotificationTargetBadge::install($migration);

    $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-3.1.5.sql");

    CronTask::Register('GlpiPlugin\Badges\Badge', 'BadgesAlert', DAY_TIMESTAMP);
    CronTask::Register('GlpiPlugin\Badges\BadgeReturn', 'BadgesReturnAlert', DAY_TIMESTAMP);

    Profile::initProfile();
    Profile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

    return true;
}

/**
 * @return bool
 */
function plugin_badges_uninstall()
{
    global $DB;

    //Delete rights associated with the plugin
    $profileRight = new ProfileRight();
    foreach (Profile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }

    Profile::removeRightsFromSession();

    $tables = [
        "glpi_plugin_badges_badges",
        "glpi_plugin_badges_badgetypes",
        "glpi_plugin_badges_configs",
        "glpi_plugin_badges_notificationstates",
        "glpi_plugin_badges_requests"
    ];

    foreach ($tables as $table) {
        $DB->dropTable($table, true);
    }

    //old versions
    $tables = [
        "glpi_plugin_badges",
        "glpi_dropdown_plugin_badges_type",
        "glpi_plugin_badges_users",
        "glpi_plugin_badges_profiles",
        "glpi_plugin_badges_config",
        "glpi_plugin_badges_mailing",
        "glpi_plugin_badges_default"
    ];

    foreach ($tables as $table) {
        $DB->dropTable($table, true);
    }

    $notif = new Notification();
    $options = ['itemtype' => Badge::class];
    foreach (
        $DB->request([
            'FROM' => 'glpi_notifications',
            'WHERE' => $options
        ]) as $data
    ) {
        $notif->delete($data);
    }

    //templates
    $template = new NotificationTemplate();
    $translation = new NotificationTemplateTranslation();
    $notif_template = new Notification_NotificationTemplate();
    $options = ['itemtype' => Badge::class];
    foreach (
        $DB->request([
            'FROM' => 'glpi_notificationtemplates',
            'WHERE' => $options
        ]) as $data
    ) {
        $options_template = [
            'notificationtemplates_id' => $data['id'],
        ];

        foreach (
            $DB->request([
                'FROM' => 'glpi_notificationtemplatetranslations',
                'WHERE' => $options_template
            ]) as $data_template
        ) {
            $translation->delete($data_template);
        }
        $template->delete($data);

        foreach (
            $DB->request([
                'FROM' => 'glpi_notifications_notificationtemplates',
                'WHERE' => $options_template
            ]) as $data_template
        ) {
            $notif_template->delete($data_template);
        }
    }

    $itemtypes = [
        'Alert',
        'DisplayPreference',
        'Document_Item',
        'ImpactItem',
        'Item_Ticket',
        'Link_Itemtype',
        'Notepad',
        'SavedSearch',
        'DropdownTranslation',
        'NotificationTemplate',
        'Notification'
    ];
    foreach ($itemtypes as $itemtype) {
        $item = new $itemtype();
        $item->deleteByCriteria(['itemtype' => Badge::class]);
    }

    if (class_exists('PluginDatainjectionModel')) {
        PluginDatainjectionModel::clean(['itemtype' => Badge::class]);
    }

    CronTask::Unregister('Badges');

    return true;
}

/**
 * @param $types
 *
 * @return mixed
 */
function plugin_badges_AssignToTicket($types)
{
    if (Session::haveRight("plugin_badges_open_ticket", "1")) {
        $types[Badge::class] = Badge::getTypeName(2);
    }

    return $types;
}

// Define dropdown relations
/**
 * @return array
 */
function plugin_badges_getDatabaseRelations()
{
    if (Plugin::isPluginActive("badges")) {
        return [
            "glpi_plugin_badges_badgetypes" => ["glpi_plugin_badges_badges" => "plugin_badges_badgetypes_id"],
            "glpi_entities" => [
                "glpi_plugin_badges_badges" => "entities_id",
                "glpi_plugin_badges_badgetypes" => "entities_id"
            ],
            "glpi_locations" => ["glpi_plugin_badges_badges" => "locations_id"],
            "glpi_states" => [
                "glpi_plugin_badges_badges" => "states_id",
                "glpi_plugin_badges_notificationstates" => "states_id"
            ],
            "glpi_users" => ["glpi_plugin_badges_badges" => "users_id"]
        ];
    } else {
        return [];
    }
}

// Define Dropdown tables to be manage in GLPI :
/**
 * @return array
 */
function plugin_badges_getDropdown()
{
    if (Plugin::isPluginActive("badges")) {
        return [BadgeType::class => BadgeType::getTypeName(2)];
    } else {
        return [];
    }
}

/**
 * @param $type
 * @param $ID
 * @param $data
 * @param $num
 *
 * @return string
 */
function plugin_badges_displayConfigItem($type, $ID, $data, $num)
{
    $searchopt = SearchOption::getOptionsForItemtype($type);
    $table = $searchopt[$ID]["table"];
    $field = $searchopt[$ID]["field"];

    switch ($table . '.' . $field) {
        case "glpi_plugin_badges_badges.date_expiration":
            if ($data[$num][0]['name'] <= date('Y-m-d') && !empty($data[$num][0]['name'])) {
                return " class=\"deleted\" ";
            }
            break;
    }
    return "";
}

function plugin_datainjection_populate_badges()
{
    global $INJECTABLE_TYPES;
    $INJECTABLE_TYPES[BadgeInjection::class] = 'badges';
}

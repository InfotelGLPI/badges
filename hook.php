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
use GlpiPlugin\Badges\Profile;

function plugin_badges_install()
{
    global $DB;

    $install   = false;
    $update78  = false;
    $update85  = false;
    $update201 = false;

    if (!$DB->tableExists("glpi_plugin_badges")
       && !$DB->tableExists("glpi_plugin_badges_badgetypes")) {
        $install = true;
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/empty-3.1.3.sql");
    } elseif ($DB->tableExists("glpi_plugin_badges_users")
              && !$DB->tableExists("glpi_plugin_badges_default")) {
        $update78 = true;
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.4.sql");
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.5.0.sql");
        plugin_badges_configure15();
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.5.1.sql");
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.6.0.sql");
    } elseif ($DB->tableExists("glpi_plugin_badges_profiles")
              && $DB->fieldExists("glpi_plugin_badges_profiles", "interface")) {
        $update78 = true;
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.5.0.sql");
        plugin_badges_configure15();
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.5.1.sql");
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.6.0.sql");
    } elseif ($DB->tableExists("glpi_plugin_badges")
              && !$DB->fieldExists("glpi_plugin_badges", "date_mod")) {
        $update78 = true;
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.5.1.sql");
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.6.0.sql");
    } elseif (!$DB->tableExists("glpi_plugin_badges_badgetypes")) {
        $update78 = true;
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.6.0.sql");
    } elseif ($DB->tableExists("glpi_plugin_badges_profiles")) {
        $update85 = true;
    }

    //version 2.0.1
    if (!$DB->tableExists("glpi_plugin_badges_requests")) {
        $update201 = true;
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-2.0.1.sql");
    }

    //version 2.4.1
    if ($DB->tableExists("glpi_plugin_badges_badges")
       && !$DB->fieldExists("glpi_plugin_badges_badges", "is_recursive")) {
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-2.4.1.sql");
    }

    //version 2.5.1
    if ($DB->tableExists("glpi_plugin_badges_badgetypes")
       && !$DB->fieldExists("glpi_plugin_badges_badgetypes", "is_recursive")) {
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-2.5.1.sql");
    }

    $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-3.1.0.sql");

    //version 3.1.3
    if (!$DB->fieldExists("glpi_plugin_badges_badges", "users_id_tech")) {
        $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-3.1.3.sql");
    }

    $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-3.1.4.sql");

    //DisplayPreferences Migration
    $classes = ['PluginBadgesBadge' => Badge::class];

    foreach ($classes as $old => $new) {
        $displayusers = $DB->request([
            'SELECT' => [
                'users_id'
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_displaypreferences',
            'WHERE' => [
                'itemtype' => $old,
            ],
        ]);

        if (count($displayusers) > 0) {
            foreach ($displayusers as $displayuser) {
                $iterator = $DB->request([
                    'SELECT' => [
                        'num',
                        'id'
                    ],
                    'FROM' => 'glpi_displaypreferences',
                    'WHERE' => [
                        'itemtype' => $old,
                        'users_id' => $displayuser['users_id'],
                        'interface' => 'central'
                    ],
                ]);

                if (count($iterator) > 0) {
                    foreach ($iterator as $data) {
                        $iterator2 = $DB->request([
                            'SELECT' => [
                                'id'
                            ],
                            'FROM' => 'glpi_displaypreferences',
                            'WHERE' => [
                                'itemtype' => $new,
                                'users_id' => $displayuser['users_id'],
                                'num' => $data['num'],
                                'interface' => 'central'
                            ],
                        ]);
                        if (count($iterator2) > 0) {
                            foreach ($iterator2 as $dataid) {
                                $query = $DB->buildDelete(
                                    'glpi_displaypreferences',
                                    [
                                        'id' => $dataid['id'],
                                    ]
                                );
                                $DB->doQuery($query);
                            }
                        } else {
                            $query = $DB->buildUpdate(
                                'glpi_displaypreferences',
                                [
                                    'itemtype' => $new,
                                ],
                                [
                                    'id' => $data['id'],
                                ]
                            );
                            $DB->doQuery($query);
                        }
                    }
                }
            }
        }
    }

    if ($install || $update201) {
        // Badge request notification
        install_notifications_badges();
    }

    if ($update78) {
        $iterator = $DB->request([
            'SELECT' => [
                'id',
            ],
            'FROM' => 'glpi_plugin_badges_profiles',
        ]);
        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                $query = "UPDATE `glpi_plugin_badges_profiles`
                  SET `profiles_id` = '" . $data["id"] . "'
                  WHERE `id` = '" . $data["id"] . "';";
                $DB->doQuery($query);
            }
        }

        $query = "ALTER TABLE `glpi_plugin_badges_profiles`
               DROP `name` ;";
        $DB->doQuery($query);

//        Plugin::migrateItemType(
//            [1600 => Badge::class],
//            ["glpi_savedsearches", "glpi_savedsearches_users", "glpi_displaypreferences",
//                "glpi_documents_items",
//                "glpi_infocoms",
//                "glpi_logs",
//                "glpi_items_tickets"]
//        );
    }

    if ($update85) {
        $notepad_tables = ['glpi_plugin_badges_badges'];

        foreach ($notepad_tables as $t) {
            // Migrate data
            if ($DB->fieldExists($t, 'notepad')) {
                $iterator = $DB->request([
                    'SELECT' => [
                        'notepad',
                        'id',
                    ],
                    'FROM' => $t,
                    'WHERE' => [
                        'NOT' => ['notepad' => null],
                        'notepad' => ['<>', ''],
                    ],
                ]);
                if (count($iterator) > 0) {
                    foreach ($iterator as $data) {
                        $iq = "INSERT INTO `glpi_notepads`
                             (`itemtype`, `items_id`, `content`, `date`, `date_mod`)
                      VALUES ('GlpiPlugin\\Badges\\Badge', '" . $data['id'] . "',
                              '" . addslashes($data['notepad']) . "', NOW(), NOW())";
                        $DB->doQuery($iq);
                    }
                }
                $query = "ALTER TABLE `glpi_plugin_badges_badges` DROP COLUMN `notepad`;";
                $DB->doQuery($query);
            }
        }
    }

    CronTask::Register('GlpiPlugin\Badges\Badge', 'BadgesAlert', DAY_TIMESTAMP);
    CronTask::Register('GlpiPlugin\Badges\BadgeReturn', 'BadgesReturnAlert', DAY_TIMESTAMP);

    Profile::initProfile();
    Profile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
    $migration = new Migration("2.2.0");
    $migration->dropTable('glpi_plugin_badges_profiles');

    return true;
}

function plugin_badges_configure15()
{
    global $DB;

    // ADD FK_users
    $query_old_items  = "SELECT `glpi_plugin_badges_users`.`FK_users`,`glpi_plugin_badges`.`ID`
               FROM `glpi_plugin_badges_users`,`glpi_plugin_badges` WHERE `glpi_plugin_badges_users`.`FK_badges` = `glpi_plugin_badges`.`ID` ";
    $result_old_items = $DB->doQuery($query_old_items);
    if ($DB->numrows($result_old_items) > 0) {
        while ($data_old_items = $DB->fetchArray($result_old_items)) {
            if ($data_old_items["ID"]) {
                $query = "UPDATE `glpi_plugin_badges` SET `FK_users` = '" . $data_old_items["FK_users"] . "' WHERE `ID` = '" . $data_old_items["ID"] . "' ";
                $DB->doQuery($query);
            }
        }
    }

    $query = "DROP TABLE IF EXISTS `glpi_plugin_badges_users` ";
    $DB->doQuery($query);
}



function install_notifications_badges()
{

    global $DB;

    $migration = new Migration(1.0);

    // Notification
    // Request
    $options_notif        = ['itemtype' => Badge::class,
        'name' => 'Access Badges Request'];
    $DB->insert(
        "glpi_notificationtemplates",
        $options_notif
    );

    foreach ($DB->request([
        'FROM' => 'glpi_notificationtemplates',
        'WHERE' => $options_notif]) as $data) {
        $templates_id = $data['id'];

        if ($templates_id) {
            $DB->insert(
                "glpi_notificationtemplatetranslations",
                [
                    'notificationtemplates_id' => $templates_id,
                    'subject' => '##badge.action## : ##badge.entity##',
                    'content_text' => '##lang.badge.entity## :##badge.entity##
                        ##FOREACHbadgerequest##
                        ##lang.badgerequest.arrivaldate## : ##badgerequest.arrivaldate##
                        ##lang.badgerequest.requester## : ##badgerequest.requester##
                        ##lang.badgerequest.visitorfirstname## : ##badgerequest.visitorfirstname##
                        ##lang.badgerequest.visitorrealname## : ##badgerequest.visitorrealname##
                        ##lang.badgerequest.visitorsociety## : ##badgerequest.visitorsociety##
                        ##ENDFOREACHbadgerequest##',
                    'content_html' => '&lt;p&gt;##lang.badge.entity## :##badge.entity##&lt;br /&gt; &lt;br /&gt;
                        ##FOREACHbadgerequest##&lt;br /&gt;
                        ##lang.badgerequest.arrivaldate## : ##badgerequest.arrivaldate##&lt;br /&gt;
                        ##lang.badgerequest.requester## : ##badgerequest.requester##&lt;br /&gt;
                        ##lang.badgerequest.visitorfirstname## : ##badgerequest.visitorfirstname##&lt;br /&gt;
                        ##lang.badgerequest.visitorrealname## : ##badgerequest.visitorrealname##&lt;br /&gt;
                        ##lang.badgerequest.visitorsociety## : ##badgerequest.visitorsociety##&lt;br /&gt;
                        ##ENDFOREACHbadgerequest##&lt;/p&gt;',
                ]
            );

            $DB->insert(
                "glpi_notifications",
                [
                    'name' => 'Access badge request',
                    'entities_id' => 0,
                    'itemtype' => Badge::class,
                    'event' => 'AccessBadgeRequest',
                    'is_recursive' => 1,
                ]
            );

            $options_notif        = ['itemtype' => Badge::class,
                'name' => 'Access badge request',
                'event' => 'AccessBadgeRequest'];

            foreach ($DB->request([
                'FROM' => 'glpi_notifications',
                'WHERE' => $options_notif]) as $data_notif) {
                $notification = $data_notif['id'];
                if ($notification) {
                    $DB->insert(
                        "glpi_notifications_notificationtemplates",
                        [
                            'notifications_id' => $notification,
                            'mode' => 'mailing',
                            'notificationtemplates_id' => $templates_id,
                        ]
                    );
                }
            }
        }
    }

    // Return
    $options_notif        = ['itemtype' => Badge::class,
        'name' => 'Access Badges Return'];
    // Request
    $DB->insert(
        "glpi_notificationtemplates",
        $options_notif
    );

    foreach ($DB->request([
        'FROM' => 'glpi_notificationtemplates',
        'WHERE' => $options_notif]) as $data) {
        $templates_id = $data['id'];

        if ($templates_id) {
            $DB->insert(
                "glpi_notificationtemplatetranslations",
                [
                    'notificationtemplates_id' => $templates_id,
                    'subject' => '##badge.action## : ##badge.entity##',
                    'content_text' => '##lang.badge.entity## :##badge.entity##
                     ##FOREACHbadgerequest##
                     ##lang.badgerequest.arrivaldate## : ##badgerequest.arrivaldate##
                     ##lang.badgerequest.requester## : ##badgerequest.requester##
                     ##lang.badgerequest.visitorfirstname## : ##badgerequest.visitorfirstname##
                     ##lang.badgerequest.visitorrealname## : ##badgerequest.visitorrealname##
                     ##lang.badgerequest.visitorsociety## : ##badgerequest.visitorsociety##
                     ##ENDFOREACHbadgerequest##',
                    'content_html' => '&lt;p&gt;##lang.badge.entity## :##badge.entity##&lt;br /&gt; &lt;br /&gt;
                     ##FOREACHbadgerequest##&lt;br /&gt;
                     ##lang.badgerequest.arrivaldate## : ##badgerequest.arrivaldate##&lt;br /&gt;
                     ##lang.badgerequest.requester## : ##badgerequest.requester##&lt;br /&gt;
                     ##lang.badgerequest.visitorfirstname## : ##badgerequest.visitorfirstname##&lt;br /&gt;
                     ##lang.badgerequest.visitorrealname## : ##badgerequest.visitorrealname##&lt;br /&gt;
                     ##lang.badgerequest.visitorsociety## : ##badgerequest.visitorsociety##&lt;br /&gt;
                     ##ENDFOREACHbadgerequest##&lt;/p&gt;',
                ]
            );

            $DB->insert(
                "glpi_notifications",
                [
                    'name' => 'Access Badges Return',
                    'entities_id' => 0,
                    'itemtype' => Badge::class,
                    'event' => 'BadgesReturn',
                    'is_recursive' => 1,
                ]
            );

            $options_notif        = ['itemtype' => Badge::class,
                'name' => 'Access Badges Return',
                'event' => 'BadgesReturn'];

            foreach ($DB->request([
                'FROM' => 'glpi_notifications',
                'WHERE' => $options_notif]) as $data_notif) {
                $notification = $data_notif['id'];
                if ($notification) {
                    $DB->insert(
                        "glpi_notifications_notificationtemplates",
                        [
                            'notifications_id' => $notification,
                            'mode' => 'mailing',
                            'notificationtemplates_id' => $templates_id,
                        ]
                    );
                }
            }
        }
    }

    $migration->executeMigration();
    return true;
}

/**
 * @return bool
 */
function plugin_badges_uninstall()
{
    global $DB;

    $tables = ["glpi_plugin_badges_badges",
        "glpi_plugin_badges_badgetypes",
        "glpi_plugin_badges_configs",
        "glpi_plugin_badges_notificationstates",
        "glpi_plugin_badges_requests"];

    foreach ($tables as $table) {
        $DB->dropTable($table, true);
    }

    //old versions
    $tables = ["glpi_plugin_badges",
        "glpi_dropdown_plugin_badges_type",
        "glpi_plugin_badges_users",
        "glpi_plugin_badges_profiles",
        "glpi_plugin_badges_config",
        "glpi_plugin_badges_mailing",
        "glpi_plugin_badges_default"];

    foreach ($tables as $table) {
        $DB->dropTable($table, true);
    }

    $notif   = new Notification();
    $options = ['itemtype' => Badge::class];
    foreach ($DB->request([
        'FROM' => 'glpi_notifications',
        'WHERE' => $options]) as $data) {
        $notif->delete($data);
    }

    //templates
    $template       = new NotificationTemplate();
    $translation    = new NotificationTemplateTranslation();
    $notif_template = new Notification_NotificationTemplate();
    $options        = ['itemtype' => Badge::class];
    foreach ($DB->request([
        'FROM' => 'glpi_notificationtemplates',
        'WHERE' => $options]) as $data) {
        $options_template = [
            'notificationtemplates_id' => $data['id']
        ];

        foreach ($DB->request([
            'FROM' => 'glpi_notificationtemplatetranslations',
            'WHERE' => $options_template]) as $data_template) {
            $translation->delete($data_template);
        }
        $template->delete($data);

        foreach ($DB->request([
            'FROM' => 'glpi_notifications_notificationtemplates',
            'WHERE' => $options_template]) as $data_template) {
            $notif_template->delete($data_template);
        }
    }

    $itemtypes = ['Alert',
        'DisplayPreference',
        'Document_Item',
        'ImpactItem',
        'Item_Ticket',
        'Link_Itemtype',
        'Notepad',
        'SavedSearch',
        'DropdownTranslation',
        'NotificationTemplate',
        'Notification'];
    foreach ($itemtypes as $itemtype) {
        $item = new $itemtype;
        $item->deleteByCriteria(['itemtype' => Badge::class]);
    }

    if (class_exists('PluginDatainjectionModel')) {
        PluginDatainjectionModel::clean(['itemtype' => Badge::class]);
    }

    CronTask::Unregister('badges');

    //Delete rights associated with the plugin
    $profileRight = new ProfileRight();
    foreach (Profile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }
    Badge::removeRightsFromSession();

    Profile::removeRightsFromSession();

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
        return ["glpi_plugin_badges_badgetypes" => ["glpi_plugin_badges_badges" => "plugin_badges_badgetypes_id"],
            "glpi_entities"                 => ["glpi_plugin_badges_badges"     => "entities_id",
                "glpi_plugin_badges_badgetypes" => "entities_id"],
            "glpi_locations"                => ["glpi_plugin_badges_badges" => "locations_id"],
            "glpi_states"                   => ["glpi_plugin_badges_badges"             => "states_id",
                "glpi_plugin_badges_notificationstates" => "states_id"],
            "glpi_users"                    => ["glpi_plugin_badges_badges" => "users_id"]];
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

    $searchopt  = SearchOption::getOptionsForItemtype($type);
    $table     = $searchopt[$ID]["table"];
    $field     = $searchopt[$ID]["field"];

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

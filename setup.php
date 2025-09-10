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

use Glpi\Plugin\Hooks;

global $CFG_GLPI;

define('PLUGIN_BADGES_VERSION', '3.0.0');

if (!defined("PLUGIN_BADGES_DIR")) {
    define("PLUGIN_BADGES_DIR", Plugin::getPhpDir("badges"));
//    define("PLUGIN_BADGES_NOTFULL_DIR", Plugin::getPhpDir("badges", false));
    $root = $CFG_GLPI['root_doc'] . '/plugins/badges';
    define("PLUGIN_BADGES_WEBDIR", $root);
}

// Init the hooks of the plugins -Needed
function plugin_init_badges()
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $PLUGIN_HOOKS['csrf_compliant']['badges'] = true;
    $PLUGIN_HOOKS['assign_to_ticket']['badges'] = true;
    $PLUGIN_HOOKS['change_profile']['badges'] = ['PluginBadgesProfile', 'initProfile'];


    if (Session::getLoginUserID()) {

        $PLUGIN_HOOKS[Hooks::ADD_CSS]['badges'] = ['css/badges.css'];
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['badges'] = ['badges.js'];

        Plugin::registerClass('PluginBadgesBadge', [
            'assignable_types' => true,
            'document_types' => true,
            'helpdesk_visible_types' => true,
            'ticket_types' => true,
            'notificationtemplates_types' => true,
        ]);

        Plugin::registerClass('PluginBadgesProfile', ['addtabon' => 'Profile']);
        Plugin::registerClass('PluginBadgesConfig', ['addtabon' => 'CronTask']);
        Plugin::registerClass('PluginBadgesReturn', ['addtabon' => 'CronTask']);
        Plugin::registerClass('PluginBadgesRequest', ['addtabon' => 'User']);

        if (class_exists('PluginResourcesResource')) {
            PluginResourcesResource::registerType('PluginBadgesBadge');
        }

        if (!Plugin::isPluginActive('environment')
            && Session::haveRight("plugin_badges", READ)) {
            $PLUGIN_HOOKS['menu_toadd']['badges'] = ['assets' => 'PluginBadgesBadge'];
            if (!in_array('PluginBadgesBadge', $CFG_GLPI['globalsearch_types'])) {
                array_push($CFG_GLPI['globalsearch_types'], 'PluginBadgesBadge');
            }
        }

        if (Session::haveRight("plugin_badges", READ)
            && !Plugin::isPluginActive('servicecatalog')) {
            $PLUGIN_HOOKS['helpdesk_menu_entry']['badges'] =  PLUGIN_BADGES_WEBDIR . '/front/wizard.php';
            $PLUGIN_HOOKS['helpdesk_menu_entry_icon']['badges'] = PluginBadgesBadge::getIcon();
        }

        if (Plugin::isPluginActive('servicecatalog')) {
            $PLUGIN_HOOKS['servicecatalog']['badges'] = ['PluginBadgesServicecatalog'];
        }

        if (Session::haveRight("plugin_badges", UPDATE)) {
            $PLUGIN_HOOKS['use_massive_action']['badges'] = 1;
        }

        if (Plugin::isPluginActive('badges')) { // only if plugin activated
            $PLUGIN_HOOKS['plugin_datainjection_populate']['badges'] = 'plugin_datainjection_populate_badges';
        }

        // Import from Data_Injection plugin
        $PLUGIN_HOOKS['migratetypes']['badges'] = 'plugin_datainjection_migratetypes_badges';
        $PLUGIN_HOOKS['redirect_page']['badges'] = PLUGIN_BADGES_WEBDIR . '/front/wizard.php';
    }
}

// Get the name and the version of the plugin - Needed

/**
 * @return array
 */
function plugin_version_badges()
{
    return [
        'name' => _n('Badge', 'Badges', 2, 'badges'),
        'version' => PLUGIN_BADGES_VERSION,
        'author' => "<a href='http://blogglpi.infotel.com'>Infotel</a>",
        'license' => 'GPLv2+',
        'homepage' => 'https://github.com/InfotelGLPI/badges',
        'requirements' => [
            'glpi' => [
                'min' => '11.0',
                'max' => '12.0',
                'dev' => false,
            ],
        ],
    ];
}

/**
 * @param $types
 *
 * @return mixed
 */
function plugin_datainjection_migratetypes_badges($types)
{
    $types[1600] = 'PluginBadgesBadge';
    return $types;
}

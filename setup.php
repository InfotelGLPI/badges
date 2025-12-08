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
use GlpiPlugin\Badges\Badge;
use GlpiPlugin\Badges\BadgeReturn;
use GlpiPlugin\Badges\Profile;
use GlpiPlugin\Badges\Request;
use GlpiPlugin\Badges\Servicecatalog;
use GlpiPlugin\Resources\Resource;

global $CFG_GLPI;

define('PLUGIN_BADGES_VERSION', '3.1.5');

if (!defined("PLUGIN_BADGES_DIR")) {
    define("PLUGIN_BADGES_DIR", Plugin::getPhpDir("badges"));
    $root = $CFG_GLPI['root_doc'] . '/plugins/badges';
    define("PLUGIN_BADGES_WEBDIR", $root);
}

// Init the hooks of the plugins -Needed
function plugin_init_badges()
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $PLUGIN_HOOKS['csrf_compliant']['badges'] = true;
    $PLUGIN_HOOKS['assign_to_ticket']['badges'] = true;
    $PLUGIN_HOOKS['change_profile']['badges'] = [Profile::class, 'initProfile'];


    if (Session::getLoginUserID()) {
        $PLUGIN_HOOKS[Hooks::ADD_CSS]['badges'] = ['css/badges.css'];
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['badges'] = ['badges.js'];

        Plugin::registerClass(Badge::class, [
            'assignable_types' => true,
            'document_types' => true,
            'helpdesk_visible_types' => true,
            'ticket_types' => true,
            'notificationtemplates_types' => true,
        ]);

        Plugin::registerClass(Profile::class, ['addtabon' => 'Profile']);
        Plugin::registerClass(Config::class, ['addtabon' => 'CronTask']);
        Plugin::registerClass(BadgeReturn::class, ['addtabon' => 'CronTask']);
        Plugin::registerClass(Request::class, ['addtabon' => 'User']);

        if (class_exists(Resource::class)) {
            Resource::registerType(Badge::class);
        }

        if (!Plugin::isPluginActive('environment')
            && Session::haveRight("plugin_badges", READ)) {
            $PLUGIN_HOOKS['menu_toadd']['badges'] = ['assets' => Badge::class];
            if (!in_array(Badge::class, $CFG_GLPI['globalsearch_types'])) {
                array_push($CFG_GLPI['globalsearch_types'], Badge::class);
            }
        }

        if (Session::haveRight("plugin_badges", READ)
            && !Plugin::isPluginActive('servicecatalog')) {
            $PLUGIN_HOOKS['helpdesk_menu_entry']['badges'] =  PLUGIN_BADGES_WEBDIR . '/front/wizard.php';
            $PLUGIN_HOOKS['helpdesk_menu_entry_icon']['badges'] = Badge::getIcon();
        }

        if (Plugin::isPluginActive('servicecatalog')) {
            $PLUGIN_HOOKS['servicecatalog']['badges'] = [Servicecatalog::class];
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
        'author' => "<a href='https//blogglpi.infotel.com'>Infotel</a>, Xavier CAILLAUD",
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
    $types[1600] = Badge::class;
    return $types;
}

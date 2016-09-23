<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 badges plugin for GLPI
 Copyright (C) 2009-2016 by the badges Development Team.

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

// Init the hooks of the plugins -Needed
function plugin_init_badges() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['badges']   = true;
   $PLUGIN_HOOKS['assign_to_ticket']['badges'] = true;
   $PLUGIN_HOOKS['change_profile']['badges']   = array('PluginBadgesProfile', 'initProfile');
   $PLUGIN_HOOKS['add_css']['badges']          = array('badges.css');
   $PLUGIN_HOOKS['add_javascript']['badges'][] = 'badges.js';
   
   if (Session::getLoginUserID()) {

      Plugin::registerClass('PluginBadgesBadge', array(
         'linkuser_types'              => true,
         'document_types'              => true,
         'helpdesk_visible_types'      => true,
         'ticket_types'                => true,
         'notificationtemplates_types' => true
      ));

      Plugin::registerClass('PluginBadgesProfile', array('addtabon' => 'Profile'));
      Plugin::registerClass('PluginBadgesConfig', array('addtabon' => 'CronTask'));
      Plugin::registerClass('PluginBadgesReturn', array('addtabon' => 'CronTask'));
      Plugin::registerClass('PluginBadgesRequest', array('addtabon' => 'User'));

      if (class_exists('PluginResourcesResource')) {
         PluginResourcesResource::registerType('PluginBadgesBadge');
      }

      $plugin = new Plugin();
      if (!$plugin->isActivated('environment') && Session::haveRight("plugin_badges", READ)) {
         $PLUGIN_HOOKS['menu_toadd']['badges'] = array('assets' => 'PluginBadgesMenu');
         $PLUGIN_HOOKS['helpdesk_menu_entry']['badges'] = '/front/wizard.php';
      }

      if (Session::haveRight("plugin_badges", UPDATE)) {
         $PLUGIN_HOOKS['use_massive_action']['badges'] = 1;
      }

      if (class_exists('PluginBadgesBadge')) { // only if plugin activated
         $PLUGIN_HOOKS['plugin_datainjection_populate']['badges'] = 'plugin_datainjection_populate_badges';
      }

      // Import from Data_Injection plugin
      $PLUGIN_HOOKS['migratetypes']['badges'] = 'plugin_datainjection_migratetypes_badges';
      $PLUGIN_HOOKS['redirect_page']['badges'] = 'front/wizard.php';
   }
}

// Get the name and the version of the plugin - Needed

function plugin_version_badges() {

   return array (
      'name' => _n('Badge', 'Badges', 2, 'badges'),
      'version' => '2.2.0',
      'author'  => "<a href='http://infotel.com/services/expertise-technique/glpi/'>Infotel</a>",
      'license' => 'GPLv2+',
      'homepage'=>'https://github.com/InfotelGLPI/badges',
      'minGlpiVersion' => '9.1',
   );
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_badges_check_prerequisites() {
   if (version_compare(GLPI_VERSION,'9.1','lt') || version_compare(GLPI_VERSION,'9.2','ge')) {
      _e('This plugin requires GLPI >= 9.1', 'badges');
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded
//may display messages or add to message after redirect
function plugin_badges_check_config() {
   return true;
}

function plugin_datainjection_migratetypes_badges($types) {
   $types[1600] = 'PluginBadgesBadge';
   return $types;
}

?>
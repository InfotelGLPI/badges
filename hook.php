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

/**
 * @return bool
 */
 
use Glpi\Search\SearchOption;

function plugin_badges_install() {
   global $DB;

   include_once(PLUGIN_BADGES_DIR . "/inc/profile.class.php");

   $install   = false;
   $update78  = false;
   $update85  = false;
   $update201 = false;

   if (!$DB->tableExists("glpi_plugin_badges")
       && !$DB->tableExists("glpi_plugin_badges_badgetypes")) {
      $install = true;
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/empty-3.0.0.sql");

   } else if ($DB->tableExists("glpi_plugin_badges_users")
              && !$DB->tableExists("glpi_plugin_badges_default")) {

      $update78 = true;
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.4.sql");
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.5.0.sql");
      plugin_badges_configure15();
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.5.1.sql");
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.6.0.sql");

   } else if ($DB->tableExists("glpi_plugin_badges_profiles")
              && $DB->fieldExists("glpi_plugin_badges_profiles", "interface")) {

      $update78 = true;
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.5.0.sql");
      plugin_badges_configure15();
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.5.1.sql");
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.6.0.sql");

   } else if ($DB->tableExists("glpi_plugin_badges")
              && !$DB->fieldExists("glpi_plugin_badges", "date_mod")) {

      $update78 = true;
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.5.1.sql");
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.6.0.sql");

   } else if (!$DB->tableExists("glpi_plugin_badges_badgetypes")) {

      $update78 = true;
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-1.6.0.sql");

   } else if ($DB->tableExists("glpi_plugin_badges_profiles")) {

      $update85 = true;

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

   if (!$DB->tableExists("glpi_plugin_badges_requests")) {
      $update201 = true;
      $DB->runFile(PLUGIN_BADGES_DIR . "/sql/update-2.0.1.sql");
   }

   if ($install || $update201) {
      // Badge request notification
      $query_id = "SELECT `id` FROM `glpi_notificationtemplates` WHERE `itemtype`='PluginBadgesBadge' AND `name` = 'Access Badges Request'";
      $result = $DB->doQuery($query_id) or die($DB->error());
      $itemtype = $DB->result($result, 0, 'id');
      if (empty($itemtype)) {
         $query_id = "INSERT INTO `glpi_notificationtemplates`(`name`, `itemtype`, `date_mod`, `comment`, `css`) VALUES ('Access Badges Request','PluginBadgesBadge', NOW(),'','');";
         $result = $DB->doQuery($query_id) or die($DB->error());
         $query_id = "SELECT `id` FROM `glpi_notificationtemplates` WHERE `itemtype`='PluginBadgesBadge' AND `name` = 'Access Badges Request'";
         $result = $DB->doQuery($query_id) or die($DB->error());
         $itemtype = $DB->result($result, 0, 'id');
      }

      $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                                 VALUES(NULL, '" . $itemtype . "', '','##badge.action## : ##badge.entity##',
                        '##lang.badge.entity## :##badge.entity##
                        ##FOREACHbadgerequest## 
                        ##lang.badgerequest.arrivaldate## : ##badgerequest.arrivaldate##	
                        ##lang.badgerequest.requester## : ##badgerequest.requester##	
                        ##lang.badgerequest.visitorfirstname## : ##badgerequest.visitorfirstname##	
                        ##lang.badgerequest.visitorrealname## : ##badgerequest.visitorrealname##
                        ##lang.badgerequest.visitorsociety## : ##badgerequest.visitorsociety##
                        ##ENDFOREACHbadgerequest##',
                        '&lt;p&gt;##lang.badge.entity## :##badge.entity##&lt;br /&gt; &lt;br /&gt;
                        ##FOREACHbadgerequest##&lt;br /&gt;
                        ##lang.badgerequest.arrivaldate## : ##badgerequest.arrivaldate##&lt;br /&gt;	
                        ##lang.badgerequest.requester## : ##badgerequest.requester##&lt;br /&gt;
                        ##lang.badgerequest.visitorfirstname## : ##badgerequest.visitorfirstname##&lt;br /&gt;
                        ##lang.badgerequest.visitorrealname## : ##badgerequest.visitorrealname##&lt;br /&gt;
                        ##lang.badgerequest.visitorsociety## : ##badgerequest.visitorsociety##&lt;br /&gt;
                        ##ENDFOREACHbadgerequest##&lt;/p&gt;');";
      $DB->doQuery($query);

      $query = "INSERT INTO `glpi_notifications` (name, entities_id, itemtype, event, is_recursive, is_active)
                VALUES ('Access badge request', 0, 'PluginBadgesBadge', 'AccessBadgeRequest', 1, 1);";
      $DB->doQuery($query);

      //retrieve notification id
      $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'Access badge request' AND `itemtype` = 'PluginBadgesBadge' AND `event` = 'AccessBadgeRequest'";
      $result = $DB->doQuery($query_id) or die ($DB->error());
      $notification = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $itemtype . ");";
      $DB->doQuery($query);

      // Badge expiration alert notification
      $query_id = "SELECT `id` FROM `glpi_notificationtemplates` WHERE `itemtype`='PluginBadgesBadge' AND `name` = 'Access Badges Return'";
      $result = $DB->doQuery($query_id) or die($DB->error());
      $itemtype = $DB->result($result, 0, 'id');
      if (empty($itemtype)) {
         $query_id = "INSERT INTO `glpi_notificationtemplates`(`name`, `itemtype`, `date_mod`, `comment`, `css`) VALUES ('Access Badges Return','PluginBadgesBadge', NOW(),'','');";
         $result = $DB->doQuery($query_id) or die($DB->error());
         $query_id = "SELECT `id` FROM `glpi_notificationtemplates` WHERE `itemtype`='PluginBadgesBadge' AND `name` = 'Access Badges Return'";
         $result = $DB->doQuery($query_id) or die($DB->error());
         $itemtype = $DB->result($result, 0, 'id');
      }

      $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                              VALUES(NULL, '" . $itemtype . "', '','##badge.action## : ##badge.entity##',
                     '##lang.badge.entity## :##badge.entity##
                     ##FOREACHbadgerequest## 
                     ##lang.badgerequest.arrivaldate## : ##badgerequest.arrivaldate##	
                     ##lang.badgerequest.requester## : ##badgerequest.requester##	
                     ##lang.badgerequest.visitorfirstname## : ##badgerequest.visitorfirstname##	
                     ##lang.badgerequest.visitorrealname## : ##badgerequest.visitorrealname##
                     ##lang.badgerequest.visitorsociety## : ##badgerequest.visitorsociety##
                     ##ENDFOREACHbadgerequest##',
                     '&lt;p&gt;##lang.badge.entity## :##badge.entity##&lt;br /&gt; &lt;br /&gt;
                     ##FOREACHbadgerequest##&lt;br /&gt;
                     ##lang.badgerequest.arrivaldate## : ##badgerequest.arrivaldate##&lt;br /&gt;	
                     ##lang.badgerequest.requester## : ##badgerequest.requester##&lt;br /&gt;
                     ##lang.badgerequest.visitorfirstname## : ##badgerequest.visitorfirstname##&lt;br /&gt;
                     ##lang.badgerequest.visitorrealname## : ##badgerequest.visitorrealname##&lt;br /&gt;
                     ##lang.badgerequest.visitorsociety## : ##badgerequest.visitorsociety##&lt;br /&gt;
                     ##ENDFOREACHbadgerequest##&lt;/p&gt;');";
      $DB->doQuery($query);

      $query = "INSERT INTO `glpi_notifications` (name, entities_id, itemtype, event, is_recursive, is_active)
                VALUES ('Access badge return', 0, 'PluginBadgesBadge', 'BadgesReturn', 1, 1);";
      $DB->doQuery($query);

      //retrieve notification id
      $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'Access badge return' AND `itemtype` = 'PluginBadgesBadge' AND `event` = 'BadgesReturn'";
      $result = $DB->doQuery($query_id) or die ($DB->error());
      $notification = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $itemtype . ");";
      $DB->doQuery($query);
   }

   if ($update78) {
       $iterator = $DB->request([
           'SELECT' => [
               'id'
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

      Plugin::migrateItemType(
         [1600 => 'PluginBadgesBadge'],
         ["glpi_savedsearches", "glpi_savedsearches_users", "glpi_displaypreferences",
          "glpi_documents_items", "glpi_infocoms", "glpi_logs", "glpi_items_tickets"]);
   }

   if ($update85) {
      $notepad_tables = ['glpi_plugin_badges_badges'];

      foreach ($notepad_tables as $t) {
         // Migrate data
         if ($DB->fieldExists($t, 'notepad')) {
             $iterator = $DB->request([
                 'SELECT' => [
                     'notepad',
                     'id'
                 ],
                 'FROM' => $t,
                 'WHERE' => [
                     'NOT' => ['notepad' => null],
                     'notepad' => ['<>', '']
                 ],
             ]);
             if (count($iterator) > 0) {
                 foreach ($iterator as $data) {
                     $iq = "INSERT INTO `glpi_notepads`
                             (`itemtype`, `items_id`, `content`, `date`, `date_mod`)
                      VALUES ('PluginBadgesBadge', '" . $data['id'] . "',
                              '" . addslashes($data['notepad']) . "', NOW(), NOW())";
                     $DB->doQuery($iq);
                 }
             }
             $query = "ALTER TABLE `glpi_plugin_badges_badges` DROP COLUMN `notepad`;";
             $DB->doQuery($query);
         }
      }
   }

   CronTask::Register('PluginBadgesBadge', 'BadgesAlert', DAY_TIMESTAMP);
   CronTask::Register('PluginBadgesReturn', 'BadgesReturnAlert', DAY_TIMESTAMP);

   PluginBadgesProfile::initProfile();
   PluginBadgesProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
   $migration = new Migration("2.2.0");
   $migration->dropTable('glpi_plugin_badges_profiles');

   return true;
}

function plugin_badges_configure15() {
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

/**
 * @return bool
 */
function plugin_badges_uninstall() {
   global $DB;

   include_once(PLUGIN_BADGES_DIR . "/inc/profile.class.php");

   $tables = ["glpi_plugin_badges_badges",
              "glpi_plugin_badges_badgetypes",
              "glpi_plugin_badges_configs",
              "glpi_plugin_badges_notificationstates",
              "glpi_plugin_badges_requests"];

   foreach ($tables as $table) {
      $DB->dropTable($table);
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
      $DB->dropTable($table);
   }

   $notif   = new Notification();
   $options = ['itemtype' => 'PluginBadgesBadge',
               'event'    => 'ExpiredBadges',
               'FIELDS'   => 'id'];
    foreach ($DB->request([
        'FROM' => 'glpi_notifications',
        'WHERE' => $options]) as $data) {
        $notif->delete($data);
    }

   $options = ['itemtype' => 'PluginBadgesBadge',
               'event'    => 'BadgesWhichExpire',
               'FIELDS'   => 'id'];
    foreach ($DB->request([
        'FROM' => 'glpi_notifications',
        'WHERE' => $options]) as $data) {
        $notif->delete($data);
    }

   $options = ['itemtype' => 'PluginBadgesBadge',
               'event'    => 'BadgesReturn',
               'FIELDS'   => 'id'];
    foreach ($DB->request([
        'FROM' => 'glpi_notifications',
        'WHERE' => $options]) as $data) {
        $notif->delete($data);
    }

   $options = ['itemtype' => 'PluginBadgesBadge',
               'event'    => 'AccessBadgeRequest',
               'FIELDS'   => 'id'];
    foreach ($DB->request([
        'FROM' => 'glpi_notifications',
        'WHERE' => $options]) as $data) {
        $notif->delete($data);
    }

   //templates
   $template       = new NotificationTemplate();
   $translation    = new NotificationTemplateTranslation();
   $notif_template = new Notification_NotificationTemplate();
   $options        = ['itemtype' => 'PluginBadgesBadge',
                      'FIELDS'   => 'id'];
    foreach ($DB->request([
        'FROM' => 'glpi_notificationtemplates',
        'WHERE' => $options]) as $data) {
        $options_template = [
            'notificationtemplates_id' => $data['id'],
            'FIELDS' => 'id'
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
   $tables_glpi = ["glpi_displaypreferences",
                   "glpi_documents_items",
                   "glpi_savedsearches",
                   "glpi_logs",
                   "glpi_items_tickets",
                   "glpi_notepads",
                   "glpi_dropdowntranslations"];

   foreach ($tables_glpi as $table_glpi) {
       $DB->delete($table_glpi, ['itemtype' => ['LIKE' => 'PluginBadges%']]);
   }

   if (class_exists('PluginDatainjectionModel')) {
      PluginDatainjectionModel::clean(['itemtype' => 'PluginBadgesBadge']);
   }

   CronTask::Unregister('badges');

   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();
   foreach (PluginBadgesProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(['name' => $right['field']]);
   }
   PluginBadgesBadge::removeRightsFromSession();

   PluginBadgesProfile::removeRightsFromSession();

   return true;
}

/**
 * @param $types
 *
 * @return mixed
 */
function plugin_badges_AssignToTicket($types) {

   if (Session::haveRight("plugin_badges_open_ticket", "1")) {
      $types['PluginBadgesBadge'] = PluginBadgesBadge::getTypeName(2);
   }

   return $types;
}

// Define dropdown relations
/**
 * @return array
 */
function plugin_badges_getDatabaseRelations() {

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
function plugin_badges_getDropdown() {

   if (Plugin::isPluginActive("badges")) {
      return ["PluginBadgesBadgeType" => PluginBadgesBadgeType::getTypeName(2)];
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
function plugin_badges_displayConfigItem($type, $ID, $data, $num) {

    $searchopt  = SearchOption::getOptionsForItemtype($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   switch ($table . '.' . $field) {
      case "glpi_plugin_badges_badges.date_expiration" :
         if ($data[$num][0]['name'] <= date('Y-m-d') && !empty($data[$num][0]['name'])) {
            return " class=\"deleted\" ";
         }
         break;
   }
   return "";
}

function plugin_datainjection_populate_badges() {
   global $INJECTABLE_TYPES;
   $INJECTABLE_TYPES['PluginBadgesBadgeInjection'] = 'badges';
}

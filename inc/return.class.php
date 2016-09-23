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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginBadgesMenu
 * 
 * This class shows the plugin main page
 * 
 * @package    Badges
 * @author     Ludovic Dupont
 */
class PluginBadgesReturn extends CommonDBTM {
   
   private $request;
   
   static $rightname = "plugin_badges";
   
   function __construct(){
      parent::__construct();
      
      $this->forceTable("glpi_plugin_badges_requests");
      $this->request = new PluginBadgesRequest();
   }
   
   static function getTypeName($nb=0) {
      return __('Badge return', 'badges');
   }

   /**
    * Display tab for each users
    *
    * @param CommonGLPI $item
    * @param int $withtemplate
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         if ($item->getType() == 'PluginBadgesBadge') {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(PluginBadgesRequest::getTypeName(), countElementsInTable($this->getTable(), "`badges_id` = '".$item->getID()."'"));
            }
            return PluginBadgesRequest::getTypeName();
         }
      }
      return '';
   }
   
   /**
    * Display content for each users
    *
    * @static
    * @param CommonGLPI $item
    * @param int $tabnum
    * @param int $withtemplate
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $field = new self();
      
      if ($item->getType() == 'PluginBadgesBadge') {
         $field->showForBadge($item);
      }
      return true;
   }
   
   /**
    * Show 
    * 
    * @param type $item
    */
   function showForBadge($item) {

      if (!$this->canCreate() || !$this->canView()){
         return false;
      }

      $data = $this->find('`badges_id` = '.$item->fields['id'], "`affectation_date` DESC");

      $badge   = new PluginBadgesBadge();
      $canedit = $badge->can($item->fields['id'], 'w');

      if ($canedit) {
         echo "<form name='form' method='post' action='".Toolbox::getItemTypeFormURL($this->getType())."'>";
         echo "<div align='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<th colspan='6'>".__('Badge return', 'badges')."</th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>";
         $return = new PluginBadgesReturn();
         $return->loadBadgeInformation(0, $item->fields['id']);
         echo "</td>";
         echo "</tr>";
         
         echo "<tr class='tab_bg_1'>";
         echo "<td class='tab_bg_2 center' colspan='6'>";
         echo "<input type='submit' name='force_return' class='submit' value='".__('Force badge restitution', 'badges')."' >";
         echo "<input type='hidden' name='return_badges_id' value='".$item->fields['id']."' >";
         echo "<input type='hidden' name='requesters_id' value='0'>";
         echo "</td>";
         echo "</tr>";
         echo "</table></div>";
         Html::closeForm();
      }
      
      $this->listItems($data);
   }
   
    
   /**
    * Show list of items
    * 
    * @param type $fields
    */
   function listItems($fields) {
      
      if (!empty($fields)) {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<th colspan='6'>".__('Badge requests history', 'badges')."</th>";
         echo "</tr>";
         
         echo "<tr>";
         echo "<th>".__('Requester')."</th>";
         echo "<th>".__('Visitor realname', 'badges')."</th>";
         echo "<th>".__('Visitor firstname', 'badges')."</th>";
         echo "<th>".__('Visitor society', 'badges')."</th>";
         echo "<th>".__('Arrival date', 'badges')."</th>";
         echo "<th>".__('Return date', 'badges')."</th>";
         echo "</tr>";
         
         foreach ($fields as $field) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>".getUserName($field['requesters_id'])."</td>";
            echo "<td>".stripslashes($field['visitor_realname'])."</td>";
            echo "<td>".stripslashes($field['visitor_firstname'])."</td>";
            echo "<td>".stripslashes($field['visitor_society'])."</td>";
            echo "<td>".Html::convDateTime($field['affectation_date'])."</td>";
            echo "<td>".Html::convDateTime($field['return_date'])."</td>";
            echo "</tr>";
         }

         echo "</table>";
         echo "</div>";
         
      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<th colspan='6'>".__('Badge requests history', 'badges')."</th>";
         echo "</tr>";
         echo "<tr><td class='center'>".__('No item found')."</td></tr>";
         echo "</table>";
         echo "</div>";
      }
   }

   
  /** 
   * Check mandatory fields 
   * 
   * @param type $input
   * @return boolean
   */
   function checkMandatoryFields($input){
      $msg     = array();
      $checkKo = false;

      $mandatory_fields = array('return_badges_id' => __("Badges in your possession", "badges"));

      foreach ($input as $key => $value) {
         if (isset($mandatory_fields[$key])) {
            if (empty($value)) {
               $msg[]   = $mandatory_fields[$key];
               $checkKo = true;
            }
         }
      }

      if ($checkKo) {
         return array(false, sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)));
      }
      
      return array(true, null);
   }
   
   /**
    * Show badge return
    */
   function showBadgeReturn(){
      global $CFG_GLPI;
      
       // Init badge cart javascript
      echo "<script type='text/javascript'>";
      echo "var badges = $(document).badges(".json_encode(array('root_doc' => $CFG_GLPI['root_doc'])).");";
      echo "</script>";
      
      // Wizard title
      echo "<div class='badges_wizard_title'><p>";
      echo "<img class='badges_wizard_img' src='".$CFG_GLPI['root_doc']."/plugins/badges/pics/badgereturn.png' alt='badgereturn'/>&nbsp;";
      _e("Access badge return", "badges");
      echo "</p></div>";
      
      
      // Add badges return
      echo "<table class='tab_cadre_fixe badges_wizard_rank'>";
      echo "<tr>";
      echo "<th colspan='4'>".__("Access badge request", "badges")."</th>";
      echo "</tr>";
      
      echo "<tr>";
      echo "<td>".__("Badges in your possession", "badges")." <span class='red'>*</span></td>";
      echo "<td>";
      $elements = array(Dropdown::EMPTY_VALUE);
      foreach ($this->request->getUserBadges(Session::getLoginUserID()) as $val) {
         $elements[$val['badges_id']] = Dropdown::getDropdownName("glpi_plugin_badges_badges", $val['badges_id']);
      }
      $rand = Dropdown::showFromArray("return_badges_id", $elements, array('on_change' => 'badges_loadBadgeInformation();'));
      echo "<script type='text/javascript'>";
      echo "function badges_loadBadgeInformation(){";
      $params = array('action'    => 'loadBadgeInformation', 
                      'badges_id' => '__VALUE__');
      Ajax::updateItemJsCode("badges_informations", $CFG_GLPI['root_doc']."/plugins/badges/ajax/request.php", $params, "dropdown_return_badges_id$rand");
      echo "}";
      echo "</script>";
      echo "</td>";
      echo "</tr>";
      
      echo "<tr>";
      echo "<td colspan ='2' id='badges_informations'></td>";
      echo "</tr>";
      
      echo "<tr>";
      echo "<td>".__("Restitution date", "badges")."</td>";
      echo "<td>";
      echo Html::convDateTime(Date('Y-m-d H:i:s'));
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      
      // Footer
      echo "<br/><table width='100%'>";
      echo "<tr>";
      echo "<td class='badges_wizard_button'>";
      echo "<div id='dialog-confirm'></div>";
      echo "<input type=\"button\" class=\"submit badge_next_button\" name=\"returnBadges\" value=\"".__('Return', 'badges')."\" onclick=\"badges.badges_returnBadges('returnBadges','badges_wizardForm');\">";
      echo "<input type=\"button\" class=\"badge_previous_button submit\" name=\"previous\" value=\""._sx('button', 'Cancel')."\" onclick=\"badges.badges_cancel('".$CFG_GLPI['root_doc']."/plugins/badges/front/wizard.php');\">";
      echo "<input type='hidden' name='requesters_id' value='".Session::getLoginUserID()."'>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
   }
   
   /**
    * Load badge information
    * 
    * @param type $users_id
    * @param type $badges_id
    */
   function loadBadgeInformation($users_id, $badges_id){
      $datas = $this->request->getUserBadges($users_id, "`badges_id` = $badges_id");
      
      if (!empty($datas)) {
         echo "<table class='tab_cadre_fixe badges_wizard_info'>";
         foreach ($datas as $data) {
            echo "<tr>";
            echo "<td><b>".__("Visitor firstname", "badges")."</b></td>";
            echo "<td>".stripslashes($data['visitor_firstname'])."</td>";
            echo "<td><b>".__("Visitor realname", "badges")."</b></td>";
            echo "<td>".stripslashes($data['visitor_realname'])."</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td><b>".__("Visitor society", "badges")."</b></td>";
            echo "<td>".stripslashes($data['visitor_society'])."</td>";
//            echo "<td><b>".__s("Available badge", "Availabe badges", "badges")."</b></td>";
//            echo "<td>";
//            $this->request->loadAvailableBadges();
//            echo "</td>";
            echo "<td><b>".__("Arrival date", "badges")."</b></td>";
            echo "<td>".Html::convDateTime($data['affectation_date'])."</td>";
            echo "<td colspan='2'></td>";
            echo "</tr>";
         }
         echo "</table>";
      }
   }

   /**
    * Return badge
    * 
    * @param type $params
    */
   function returnBadge($params) {

      list($success, $message) = $this->checkMandatoryFields($params);
      if ($success) {
         $datas = $this->request->getUserBadges($params['requesters_id'], "`badges_id` = ".$params['return_badges_id']);
         foreach ($datas as $data) {
            $this->update(array('id'          => $data['id'],
                                'is_affected' => 0,
                                'return_date' => date('Y-m-d H:i:s')));
         }
         $message = __('Badge returned', 'badges');
      }
      
      return array('success' => $success,
                   'message' => $message);
   }
   
   // Cron action
   static function cronInfo($name) {

      switch ($name) {
         case 'BadgesReturnAlert':
            return array(
               'description' => __('Badges return', 'badges'));   // Optional
            break;
      }
      return array();
   }

   static function queryBadgesReturnExpire() {

      $config = new PluginBadgesConfig();
      $notif  = new PluginBadgesNotificationState();

      $config->getFromDB('1');
      $delay = $config->fields["delay_returnexpire"];
      
      $query = null;
      if (!empty($delay)) {
         $query = "SELECT *
            FROM `glpi_plugin_badges_requests`
            LEFT JOIN `glpi_plugin_badges_badges`
               ON (`glpi_plugin_badges_requests`.`badges_id` = `glpi_plugin_badges_badges`.`id`)
            WHERE `glpi_plugin_badges_requests`.`affectation_date` IS NOT NULL
            AND `glpi_plugin_badges_requests`.`is_affected` = '1'
            AND TIME_TO_SEC(TIMEDIFF(NOW(),`glpi_plugin_badges_requests`.`affectation_date`)) > $delay ";
      }
      
      return $query;
   }

   /**
    * Cron action on badges : ExpiredBadges or BadgesWhichExpire
    *
    * @param $task for log, if NULL display
    *
    * */
   static function cronBadgesReturnAlert($task = NULL) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }

      $message     = array();
      $cron_status = 0;

      $query_returnexpire = self::queryBadgesReturnExpire();

      $querys = array(PluginBadgesNotificationTargetBadge::BadgesReturn => $query_returnexpire);

      $badge_infos    = array();
      $badge_messages = array();

      foreach ($querys as $type => $query) {
         $badge_infos[$type] = array();
         if (!empty($query)) {
            foreach ($DB->request($query) as $data) {
               $entity                        = $data['entities_id'];
               $message                       = $data["name"]."<br>".__("Arrival date", "badges")." : ".Html::convdate($data["affectation_date"])."<br>\n";
               $badge_infos[$type][$entity][] = $data;

               if (!isset($badges_infos[$type][$entity])) {
                  $badge_messages[$type][$entity] = __('Badges at the end of the validity', 'badges')."<br />";
               }
               $badge_messages[$type][$entity] .= $message;
            }
         }
      }

      foreach ($querys as $type => $query) {
         foreach ($badge_infos[$type] as $entity => $badges) {
            Plugin::loadLang('badges');
            // Set badge request fields
            foreach($badges as $badge){
               $badgerequest[] = array('visitor_realname'  => $badge['visitor_realname'],
                                       'visitor_firstname' => $badge['visitor_firstname'],
                                       'visitor_society'   => $badge['visitor_society'],
                                       'affectation_date'  => $badge['affectation_date'], 
                                       'requesters_id'     => $badge['requesters_id']);
            }
            if (NotificationEvent::raiseEvent($type, new PluginBadgesBadge(), array('entities_id'  => $entity,
                                                                                    'badges'       => $badges, 
                                                                                    'badgerequest' => $badgerequest))) {
               $message     = $badge_messages[$type][$entity];
               $cron_status = 1;
               if ($task) {
                  $task->log(Dropdown::getDropdownName("glpi_entities", $entity).":  $message\n");
                  $task->addVolume(1);
               } else {
                  Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity).":  $message");
               }
            } else {
               if ($task) {
                  $task->log(Dropdown::getDropdownName("glpi_entities", $entity).
                        ":  Send badges alert failed\n");
               } else {
                  Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity).
                        ":  Send badges alert failed", false, ERROR);
               }
            }
         }
      }

      return $cron_status;
   }

   static function configCron($target) {
      $config = new PluginBadgesConfig();
      $config->showFormBadgeReturn($target, 1);
   }
}
?>
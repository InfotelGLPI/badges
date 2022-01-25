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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginBadgesRequest
 *
 * This class shows the plugin main page
 *
 * @package    Badges
 * @author     Ludovic Dupont
 */
class PluginBadgesRequest extends CommonDBTM {

   static $rightname = "plugin_badges";

   /**
    * @param int $nb
    *
    * @return string|translated
    */
   static function getTypeName($nb = 0) {
      return __('Badges request', 'badges');
   }

   /**
    * Display tab for each users
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         if ($item->getType() == 'User'
             && Session::haveRight(self::$rightname, READ)) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(self::getTypeName());
            }
            return self::getTypeName();
         }
      }
      return '';
   }

   /**
    * Display content for each users
    *
    * @static
    *
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $field = new self();

      if ($item->getType() == 'User') {
         $field->showForUser($item);
      }
      return true;
   }

   /**
    * @param       $item
    * @param array $options
    *
    * @return bool|void
    */
   function showForUser($item, $options = []) {
      global $CFG_GLPI;

      if (!$this->canCreate() || !$this->canView()) {
         return false;
      }

      $canedit = $item->can($item->fields['id'], 'r');

      $begin_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . "-1 MONTH"));
      $end_date   = date('Y-m-d H:i:s');

      if ($canedit) {
         Html::requireJs('badges');

         echo "<form name='form' method='post' action='" . Toolbox::getItemTypeFormURL($this->getType()) . "' id='badges_formSearchBadges'>";
         echo "<div align='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<th colspan='6'>" . __('Badges usage search', 'badges') . "</th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>";
         echo __('Arrival date', 'badges');
         echo "</td>";
         echo "<td>";
         Html::showDateTimeField("begin_date", ['value' => $begin_date]);
         echo "</td>";
         echo "<td>";
         echo __('Return date', 'badges');
         echo "</td>";
         echo "<td>";
         Html::showDateTimeField("end_date", ['value' => $end_date]);
         echo "</td>";
         echo "<td>";
         echo "<input type='button' class='submit btn btn-primary' name='addToCart' value='" . __('Search') . "'
         onclick=\"badges_searchBadges('searchBadges','badges_formSearchBadges', 'badges_searchBadges');\" >";
         echo Html::hidden('requesters_id', ['value' => $item->fields['id']]);
         echo "</td>";
         echo "</tr>";
         echo "</table></div>";

         // Init javascript
         echo Html::scriptBlock('$(document).ready(function() {badges_initJs("' . PLUGIN_BADGES_WEBDIR . '");});');

         Html::closeForm();
      }

      echo "<div class='center' id='badges_searchBadges'>";
      $result = $this->listItems($item->fields['id'], ['begin_date' => $begin_date, 'end_date' => $end_date]);
      echo $result['message'];
      echo "</div>";
      Html::requireJs('glpi_dialog');
      echo "<div id='dialog-confirm'></div>";
      return;
   }

   /**
    * Show list of items
    *
    * @param       $requesters_id
    * @param array $options
    *
    * @return array
    * @internal param type $fields
    */
   function listItems($requesters_id, $options = []) {

      $params['begin_date'] = "NULL";
      $params['end_date']   = "NULL";

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      $data    = $this->find(['requesters_id'    => $requesters_id,
                              'affectation_date' => ['>=', $params['begin_date']],
                              [
                                 "OR" => [
                                    ['return_date' => ['<=', $params['end_date']]],
                                    ['return_date' => NULL]
                                 ]
                              ]],
                             ["affectation_date DESC"]);
      $message = null;
      if (!empty($data)) {
         $message .= "<table class='tab_cadre_fixe'>";
         $message .= "<tr>";
         $message .= "<th colspan='6'>" . __('Badge usage report', 'badges') . "</th>";
         $message .= "</tr>";
         $message .= "<tr>";
         $message .= "<th>" . _n('Badge', 'Badges', 1, 'badge') . "</th>";
         $message .= "<th>" . __('Visitor realname', 'badges') . "</th>";
         $message .= "<th>" . __('Visitor firstname', 'badges') . "</th>";
         $message .= "<th>" . __('Visitor society', 'badges') . "</th>";
         $message .= "<th>" . __('Arrival date', 'badges') . "</th>";
         $message .= "<th>" . __('Return date', 'badges') . "</th>";
         $message .= "</tr>";
         $badge   = new PluginBadgesBadge();
         foreach ($data as $field) {
            $message .= "<tr class='tab_bg_1'>";
            $badge->getFromDB($field['badges_id']);
            $message .= "<td>" . $badge->getLink() . "</td>";
            $message .= "<td>" . stripslashes($field['visitor_realname']) . "</td>";
            $message .= "<td>" . stripslashes($field['visitor_firstname']) . "</td>";
            $message .= "<td>" . stripslashes($field['visitor_society']) . "</td>";
            $message .= "<td>" . Html::convDateTime($field['affectation_date']) . "</td>";
            $message .= "<td>" . Html::convDateTime($field['return_date']) . "</td>";
            $message .= "</tr>";
         }

         $message .= "</table>";
         $message .= "</div>";

      } else {
         $message .= "<div class='center'>";
         $message .= "<table class='tab_cadre_fixe'>";
         $message .= "<tr>";
         $message .= "<th colspan='6'>" . __('Badge usage report', 'badges') . "</th>";
         $message .= "</tr>";
         $message .= "<tr><td class='center'>" . __('No item found') . "</td></tr>";
         $message .= "</table>";
      }

      return ['success' => true, 'message' => $message];
   }

   /**
    * Show badge request
    */
   function showBadgeRequest() {
      global $CFG_GLPI;

      $request = new PluginBadgesRequest();
      $request->getEmpty();

      Html::requireJs('badges');

      // Init javascript
      echo Html::scriptBlock('$(document).ready(function() {badges_initJs("' . PLUGIN_BADGES_WEBDIR . '");});');


      echo "<h3><div class='alert alert-secondary' role='alert'>";
      echo "<i class='".PluginBadgesBadge::getIcon()."'></i>&nbsp;";
      echo __("Access badge request", "badges");
      echo "</div></h3>";

      echo "<form name='wizard_form' id='badges_wizardForm'
                  method='post'>";

      echo "<div style='overflow-x:auto;'>";
      // Add badges request
      echo "<table class='tab_cadre_fixe badges_wizard_rank' style='width: 400px;'>";

      echo "<tr>";

      echo "<td>" . __("Visitor realname", "badges") . " <span style='color:red;'>*</span></td>";
      echo "<td>";
      echo Html::input('visitor_realname',  ['size' => 40]);
      echo "</td>";

      echo "<td>" . __("Visitor firstname", "badges") . " <span style='color:red;'>*</span></td>";
      echo "<td>";
      echo Html::input('visitor_firstname',  ['size' => 40]);
      echo "</td>";

      echo "</tr>";

      echo "<tr>";
      echo "<td>" . __("Visitor society", "badges") . " <span style='color:red;'>*</span></td>";
      echo "<td>";
      echo Html::input('visitor_society',  ['size' => 40]);
      echo "</td>";
      echo "<td>" . _n("Available badge", "Available badges", 2, "badges") . " <span style='color:red;'>*</span></td>";
      echo "<td id='badges_available'>";
      $this->loadAvailableBadges();
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>" . __("Arrival date", "badges") . " <span style='color:red;'>*</span></td>";
      echo "<td>";
      Html::showDateTimeField("affectation_date", ['value' => date('Y-m-d H:i:s')]);
      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td class='center' colspan='4'>";
      echo "<button form='' onclick=\"badges_addToCart('addToCart','badges_wizardForm', 'badges_cart');\" 
      class='submit btn btn-success' />
      <i class='ti ti-plus'></i>&nbsp;
      " .__('Add to cart', 'badges') . "</button>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";


      // Cart
      echo "<br><div class='center'>";
      echo "<table class='tab_cadre_fixe badges_wizard_rank' id='badges_cart' style='display:none'>";
      echo "<tr><th colspan='6'>" . __("Cart", "badges") . "</th></tr>";
      echo "<tr>";
      echo "<th>" . __("Visitor firstname", "badges") . "</th>";
      echo "<th>" . __("Visitor realname", "badges") . "</th>";
      echo "<th>" . __("Visitor society", "badges") . "</th>";
      echo "<th>" . _n("Badge", "Badges", 1, "badges") . "</th>";
      echo "<th>" . __("Arrival date", "badges") . "</th>";
      echo "<th></th>";
      echo "</tr>";
      echo "</table>";
      echo "</div>";
      // Footer
      echo "<br/><table width='100%'>";
      echo "<tr>";
      echo "<td>";
      Html::requireJs('glpi_dialog');
      echo "<div id='dialog-confirm'></div>";

      echo "<button form='' onclick=\"badges_cancel('" . PLUGIN_BADGES_WEBDIR . "/front/wizard.php');\" 
        class='submit btn btn-info badge_previous_button' />
      " ._sx('button', 'Cancel') . "</button>";

      echo "<button form='' onclick=\"badges_addBadges('addBadges','badges_wizardForm');\" 
        class='submit btn btn-success badge_next_button' />
      " ._sx('button', 'Post') . "</button>";

      echo "</td>";
      echo "</tr>";
      echo "</table>";

      Html::closeForm();
      echo "</div>";

   }

   /**
    * Reload badges list
    *
    * @param array|type $used
    *
    * @return array
    */
   function loadAvailableBadges($used = []) {

      $datas = $this->getUsedBadges();
      if (!empty($datas)) {
         foreach ($datas as $val) {
            $used[] = $val;
         }
      }

      Dropdown::show("PluginBadgesBadge", ['name'      => 'badges_id',
                                           'used'      => $used,
                                           'condition' => ['is_bookable' => 1],
                                           'entity'    => $_SESSION['glpiactive_entity']]);
      return;
   }

   /**
    * Add badge to cart
    *
    * @param type $params
    *
    * @return array
    */
   function addToCart($params) {

      list($success, $message) = $this->checkMandatoryFields($params);

      return ['success' => $success,
              'message' => $message,
              'rowId'   => mt_rand(),
              'fields'  => [
                 'visitor_firstname' => ['label' => $params['visitor_firstname'],
                                         'value' => $params['visitor_firstname']],
                 'visitor_realname'  => ['label' => $params['visitor_realname'],
                                         'value' => $params['visitor_realname']],
                 'visitor_society'   => ['label' => $params['visitor_society'],
                                         'value' => $params['visitor_society']],
                 'badges_id'         => ['label' => Dropdown::getDropdownName("glpi_plugin_badges_badges",
                                                                              $params['badges_id']),
                                         'value' => $params['badges_id']],
                 'affectation_date'  => ['label' => Html::convDateTime($params['affectation_date']),
                                         'value' => $params['affectation_date']]
              ]];

   }

   /**
    * Save badges in database
    *
    * @param $params
    *
    * @return array
    */
   function addBadges($params) {

      if (isset($params['badges_cart'])) {
         foreach ($params['badges_cart'] as $row) {
            list($success, $message) = $this->checkMandatoryFields($row);
            if ($success) {
               $badgeExist = $this->find(["badges_id"   => $row['badges_id'],
                                          "is_affected" => 1]);
               if (empty($badgeExist)) {
                  $this->add(['visitor_realname'  => $row['visitor_realname'],
                              'visitor_firstname' => $row['visitor_firstname'],
                              'visitor_society'   => $row['visitor_society'],
                              'affectation_date'  => $row['affectation_date'],
                              'badges_id'         => $row['badges_id'],
                              'is_affected'       => 1,
                              'requesters_id'     => Session::getLoginUserID()]);
               } else {
                  $badgeExist = reset($badgeExist);
                  $this->update(['id'                => $badgeExist['id'],
                                 'visitor_realname'  => $row['visitor_realname'],
                                 'visitor_firstname' => $row['visitor_firstname'],
                                 'visitor_society'   => $row['visitor_society'],
                                 'affectation_date'  => $row['affectation_date'],
                                 'badges_id'         => $row['badges_id'],
                                 'is_affected'       => 1,
                                 'requesters_id'     => Session::getLoginUserID()]);
               }
            }

            $message = "<div class='alert alert-important alert-success d-flex'>"._n('Badge affected', 'Badges affected', count($params['badges_cart']), 'badges')."</div>";
            NotificationEvent::raiseEvent("AccessBadgeRequest", new PluginBadgesBadge(),
                                          ['entities_id'  => $_SESSION['glpiactive_entity'],
                                           'badgerequest' => $params['badges_cart']]);
         }
      } else {
         $success = false;
         $message = __('Please add badges in cart', 'badges');
      }

      return ['success' => $success,
              'message' => $message];
   }

   /**
    * Get used badges
    */
   function getUsedBadges() {

      $used  = [];
      $datas = $this->find(["is_affected" => 1]);
      if (!empty($datas)) {
         foreach ($datas as $data) {
            $used[] = $data['badges_id'];
         }
      }

      return $used;
   }

   /**
    * Get badges of a given user
    *
    * @param type   $users_id
    * @param string $condition
    *
    * @return type
    */
   function getUserBadges($users_id, $condition = []) {

      $query = ["is_affected" => 1];
      if (!empty($users_id)) {
         $query += ["requesters_id" => $users_id];
      }
      $datas = $this->find($query + $condition);

      return $datas;
   }

   /**
    * Check mandatory fields
    *
    * @param type $input
    *
    * @return array
    */
   function checkMandatoryFields($input) {
      $msg     = [];
      $checkKo = false;

      $mandatory_fields = ['visitor_realname'  => __('Visitor realname', 'badges'),
                           'visitor_firstname' => __('Visitor firstname', 'badges'),
                           'visitor_society'   => __('Visitor society', 'badges'),
                           'affectation_date'  => __('Affectation date', 'badges'),
                           'badges_id'         => _n("Available badge", "Available badges", 2, "badges")];

      foreach ($input as $key => $value) {
         if (isset($mandatory_fields[$key])) {
            if (empty($value) || $value == 'NULL') {
               $msg[]   = $mandatory_fields[$key];
               $checkKo = true;
            }
         }
      }

      if ($checkKo) {
         return [false, "<div class='alert alert-important alert-warning d-flex'>".sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg))."</div>"];
      }

      return [true, null];
   }

}

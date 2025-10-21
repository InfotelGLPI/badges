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

namespace GlpiPlugin\Badges;

use Ajax;
use CommonDBTM;
use CommonGLPI;
use DbUtils;
use Dropdown;
use Glpi\DBAL\QueryExpression;
use Html;
use NotificationEvent;
use Session;
use Toolbox;

/**
 * Class BadgeReturn
 *
 * This class shows the plugin main page
 *
 * @package    Badges
 * @author     Ludovic Dupont
 */
class BadgeReturn extends CommonDBTM
{
   private $request;

   static $rightname = "plugin_badges";

   function __construct() {
      parent::__construct();

      $this->forceTable("glpi_plugin_badges_requests");
      $this->request = new Request();
   }

   /**
    * @param int $nb
    *
    * @return string|translated
    */
   static function getTypeName($nb = 0) {
      return __('Badge return', 'badges');
   }

    /**
     * @return string
     */
    static function getIcon()
    {
        return "ti ti-receipt-refund";
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
         if ($item->getType() == Badge::class) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               $dbu = new DbUtils();
               return self::createTabEntry(Request::getTypeName(),
                                           $dbu->countElementsInTable($this->getTable(),
                                                                      ["badges_id" => $item->getID()]));
            }
            return Request::getTypeName();
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

      if ($item->getType() == Badge::class) {
         $field->showForBadge($item);
      }
      return true;
   }

   /**
    * Show
    *
    * @param type $item
    *
    * @return bool
    */
   function showForBadge($item) {

      if (!$this->canCreate() || !$this->canView()) {
         return false;
      }

      $data = $this->find(['badges_id' => $item->fields['id']], ["affectation_date DESC"]);

      $badge   = new Badge();
      $canedit = $badge->can($item->fields['id'], UPDATE);

      if ($canedit) {
         echo "<form name='form' method='post' action='" . Toolbox::getItemTypeFormURL($this->getType()) . "'>";
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<th colspan='6'>" . __('Badge return', 'badges') . "</th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>";
         $return = new BadgeReturn();
         $return->loadBadgeInformation(0, $item->fields['id']);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td class='tab_bg_2 center' colspan='6'>";
         echo Html::submit( __('Force badge restitution', 'badges'), ['name' => 'force_return', 'class' => 'btn btn-primary']);
         echo Html::hidden('return_badges_id', ['value' => $item->fields['id']]);
         echo Html::hidden('requesters_id', ['value' => 0]);
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
         echo "<th colspan='6'>" . __('Badge requests history', 'badges') . "</th>";
         echo "</tr>";

         echo "<tr>";
         echo "<th>" . __('Requester') . "</th>";
         echo "<th>" . __('Visitor realname', 'badges') . "</th>";
         echo "<th>" . __('Visitor firstname', 'badges') . "</th>";
         echo "<th>" . __('Visitor society', 'badges') . "</th>";
         echo "<th>" . __('Arrival date', 'badges') . "</th>";
         echo "<th>" . __('Return date', 'badges') . "</th>";
         echo "</tr>";

         $dbu = new DbUtils();

         foreach ($fields as $field) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . $dbu->getUserName($field['requesters_id']) . "</td>";
            echo "<td>" . stripslashes($field['visitor_realname']) . "</td>";
            echo "<td>" . stripslashes($field['visitor_firstname']) . "</td>";
            echo "<td>" . stripslashes($field['visitor_society']) . "</td>";
            echo "<td>" . Html::convDateTime($field['affectation_date']) . "</td>";
            echo "<td>" . Html::convDateTime($field['return_date']) . "</td>";
            echo "</tr>";
         }

         echo "</table>";
         echo "</div>";

      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<th colspan='6'>" . __('Badge requests history', 'badges') . "</th>";
         echo "</tr>";
         echo "<tr><td class='center'>" . __('No results found') . "</td></tr>";
         echo "</table>";
         echo "</div>";
      }
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

      $mandatory_fields = ['return_badges_id' => __("Badges in your possession", "badges")];

      foreach ($input as $key => $value) {
         if (isset($mandatory_fields[$key])) {
            if (empty($value)) {
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

   /**
    * Show badge return
    */
   function showBadgeReturn() {
      global $CFG_GLPI;

      // Wizard title
      echo "<h3><div class='alert alert-secondary' role='alert'>";
      echo "<i class='ti ti-receipt-refund'></i>&nbsp;";
      echo __("Access badge return", "badges");
      echo "</div></h3>";

      echo "<form name='wizard_form' id='badges_wizardForm'
                  method='post'>";

      echo "<div style='overflow-x:auto;'>";
      // Add badges return
      echo "<table class='badges_wizard_rank'>";

      echo "<tr>";
      echo "<td>" . __("Badges in your possession", "badges") . " <span style='color:red;'>*</span></td>";
      echo "<td>";
      $elements = [Dropdown::EMPTY_VALUE];
      foreach ($this->request->getUserBadges(Session::getLoginUserID()) as $val) {
         $elements[$val['badges_id']] = Dropdown::getDropdownName("glpi_plugin_badges_badges", $val['badges_id']);
      }
      $rand = Dropdown::showFromArray("return_badges_id", $elements, ['on_change' => 'badges_loadBadgeInformation();']);
      echo "<script type='text/javascript'>";
      echo "function badges_loadBadgeInformation(){";
      $params = ['action'    => 'loadBadgeInformation',
                      'badges_id' => '__VALUE__'];
       $root = $CFG_GLPI['root_doc'] . '/plugins/badges';
      Ajax::updateItemJsCode("badges_informations", $root . "/ajax/request.php",
                             $params, "dropdown_return_badges_id$rand");
      echo "}";
      echo "</script>";
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td colspan ='2' id='badges_informations'></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>" . __("Restitution date", "badges") . "</td>";
      echo "<td>";
      echo Html::convDateTime(date('Y-m-d H:i:s'));
      echo "</td>";
      echo "</tr>";
      echo "</table>";

      // Footer
      echo "<br/><table width='100%'>";
      echo "<tr>";
      echo "<td>";
      Html::requireJs('glpi_dialog');
      echo "<div id='dialog-confirm'></div>";

       $root = PLUGIN_BADGES_WEBDIR;
      echo "<button form='' onclick=\"badges_cancel('" . $root . "/front/wizard.php');\"
        class='submit btn btn-info  badge_previous_button' />
      " ._sx('button', 'Cancel') . "</button>";

      echo "<button form='' onclick=\"badges_returnBadges('returnBadges','badges_wizardForm');\"
        class='submit btn btn-success badge_next_button' />
      " .__('Badges return', 'badges') . "</button>";
      echo Html::hidden('requesters_id', ['value' => Session::getLoginUserID()]);
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      echo "</div>";
      // Init javascript
       $root = $CFG_GLPI['root_doc'] . '/plugins/badges';
      echo Html::scriptBlock('$(document).ready(function() {badges_initJs("' . $root . '");});');

      Html::closeForm();

   }

   /**
    * Load badge information
    *
    * @param type $users_id
    * @param type $badges_id
    */
   function loadBadgeInformation($users_id, $badges_id) {
      $datas = $this->request->getUserBadges($users_id, ["badges_id" => $badges_id]);

      if (!empty($datas)) {
         echo "<table class='tab_cadre_fixe badges_wizard_info'>";
         foreach ($datas as $data) {
            echo "<tr>";
            echo "<td><b>" . __("Visitor firstname", "badges") . "</b></td>";
            echo "<td>" . stripslashes($data['visitor_firstname']) . "</td>";
            echo "<td><b>" . __("Visitor realname", "badges") . "</b></td>";
            echo "<td>" . stripslashes($data['visitor_realname']) . "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td><b>" . __("Visitor society", "badges") . "</b></td>";
            echo "<td>" . stripslashes($data['visitor_society']) . "</td>";
            //            echo "<td><b>".__s("Available badge", "Availabe badges", "badges")."</b></td>";
            //            echo "<td>";
            //            $this->request->loadAvailableBadges();
            //            echo "</td>";
            echo "<td><b>" . __("Arrival date", "badges") . "</b></td>";
            echo "<td>" . Html::convDateTime($data['affectation_date']) . "</td>";
            echo "</tr>";
         }
         echo "</table>";
      }
   }

   /**
    * Return badge
    *
    * @param type $params
    *
    * @return array
    */
   function returnBadge($params) {

      list($success, $message) = $this->checkMandatoryFields($params);
      if ($success) {
         $datas = $this->request->getUserBadges($params['requesters_id'],
                                                ["badges_id" => $params['return_badges_id']]);
         foreach ($datas as $data) {
            $this->update(['id'          => $data['id'],
                                'is_affected' => 0,
                                'return_date' => date('Y-m-d H:i:s')]);
         }
         $message = "<div class='alert alert-important alert-success d-flex'>".__('Badge returned', 'badges')."</div>";
      }

      return ['success' => $success,
                   'message' => $message];
   }

   // Cron action
   /**
    * @param $name
    *
    * @return array
    */
   static function cronInfo($name) {

      switch ($name) {
         case 'BadgesReturnAlert':
            return [
               'description' => __('Badges return', 'badges')];   // Optional
            break;
      }
      return [];
   }

   /**
    * @return array
    */
    static function queryBadgesReturnExpire()
    {
        global $DB;

        $config = new Config();

        $config->getFromDB('1');
        $delay = $config->fields["delay_returnexpire"] ?? "";

        $query = null;
        if (!empty($delay)) {
            $requesttable = Request::getTable();
            $badgetable = Badge::getTable();
            $query = [
                'FROM' => $requesttable,
                'LEFT JOIN' => [
                    $badgetable => [
                        'ON' => [
                            $requesttable => 'badges_id',
                            $badgetable => 'id'
                        ]
                    ]
                ],
                'WHERE' => [
                    $requesttable . 'is_affected' => '1',
                    'NOT' => [
                        $requesttable . 'affectation_date' => 'NULL'
                    ],
                    [
                        new QueryExpression("TIME_TO_SEC(TIMEDIFF(NOW(), " . $DB->quoteName($requesttable . 'affectation_date') . ")) > $delay"),
                    ]
                ]
            ];
        }


        return $query;
    }

   /**
    * Cron action on badges : ExpiredBadges or BadgesWhichExpire
    *
    * @param $task for log, if NULL display
    *
    *
    * @return int
    */
   static function cronBadgesReturnAlert($task = null) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["notifications_mailing"]) {
         return 0;
      }

      $cron_status = 0;

      $query_returnexpire = self::queryBadgesReturnExpire();

      $querys = [NotificationTargetBadge::BadgesReturn => $query_returnexpire];

      $badge_infos    = [];
      $badge_messages = [];

      foreach ($querys as $type => $query) {
         $badge_infos[$type] = [];
         if (!empty($query)) {
            foreach ($DB->request($query) as $data) {
               $entity                        = $data['entities_id'];
               $message                       = $data["name"] . "<br>" . __("Arrival date", "badges") . " : " .
                                                Html::convDate($data["affectation_date"]) . "<br>\n";
               $badge_infos[$type][$entity][] = $data;

               if (!isset($badges_infos[$type][$entity])) {
                  $badge_messages[$type][$entity] = __('Badges at the end of the validity', 'badges') . "<br />";
               }
               $badge_messages[$type][$entity] .= $message;
            }
         }
      }

      foreach ($querys as $type => $query) {
         foreach ($badge_infos[$type] as $entity => $badges) {
            Plugin::loadLang('badges');
            // Set badge request fields
            foreach ($badges as $badge) {
               $badgerequest[] = ['visitor_realname'  => $badge['visitor_realname'],
                                       'visitor_firstname' => $badge['visitor_firstname'],
                                       'visitor_society'   => $badge['visitor_society'],
                                       'affectation_date'  => $badge['affectation_date'],
                                       'requesters_id'     => $badge['requesters_id']];
            }
            if (NotificationEvent::raiseEvent($type, new Badge(), ['entities_id'  => $entity,
                                                                                    'badges'       => $badges,
                                                                                    'badgerequest' => $badgerequest])
            ) {
               $message     = $badge_messages[$type][$entity];
               $cron_status = 1;
               if ($task) {
                  $task->log(Dropdown::getDropdownName("glpi_entities", $entity) . ":  $message\n");
                  $task->addVolume(1);
               } else {
                  Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity) . ":  $message");
               }
            } else {
               if ($task) {
                  $task->log(Dropdown::getDropdownName("glpi_entities", $entity) .
                             ":  Send badges alert failed\n");
               } else {
                  Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity) .
                                                   ":  Send badges alert failed", false, ERROR);
               }
            }
         }
      }

      return $cron_status;
   }

   /**
    * @param $target
    */
   static function configCron($target) {
      $config = new Config();
      $config->showFormBadgeReturn($target, 1);
   }
}

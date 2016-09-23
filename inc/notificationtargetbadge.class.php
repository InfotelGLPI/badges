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

// Class NotificationTarget
class PluginBadgesNotificationTargetBadge extends NotificationTarget {

   const REQUESTER = 30;
   
   const BadgesWhichExpire  = "BadgesWhichExpire";
   const ExpiredBadges      = "ExpiredBadges";
   const BadgesReturn       = "BadgesReturn";
   const AccessBadgeRequest = "AccessBadgeRequest";
   
   
   function getEvents() {
      return array(self::ExpiredBadges      => __('Badges at the end of the validity', 'badges'),
                   self::BadgesWhichExpire  => __('Badges which expires', 'badges'),
                   self::AccessBadgeRequest => __('Access badge request', 'badges'),
                   self::BadgesReturn       => __('Badge return delay', 'badges'));
   }

   function getDatasForTemplate($event, $options = array()) {

      $this->datas['##badge.entity##']              = Dropdown::getDropdownName('glpi_entities', $options['entities_id']);
      $this->datas['##lang.badge.entity##']         = __('Entity');
      switch ($event) {
         case self::ExpiredBadges:
            $this->datas['##badge.action##'] = __('Badges at the end of the validity', 'badges');
            break;
         case self::BadgesWhichExpire:
            $this->datas['##badge.action##'] = __('Badges which expires', 'badges');
            break;
         case self::AccessBadgeRequest:
            $this->datas['##badge.action##'] = __('Access badge request', 'badges');
            break;
         case self::BadgesReturn:
            $this->datas['##badge.action##'] = __('Badge return delay', 'badges');
            break;
      }
      $this->datas['##lang.badge.name##']           = __('Name');
      $this->datas['##lang.badge.dateexpiration##'] = __('Date of end of validity', 'badges');
      $this->datas['##lang.badge.serial##']         = __('Serial number');
      $this->datas['##lang.badge.users##']          = __('Allotted to', 'badges');

      if (isset($options['badges'])) {
         foreach ($options['badges'] as $id => $badge) {
            $tmp = array();

            $tmp['##badge.name##']           = $badge['name'];
            $tmp['##badge.serial##']         = $badge['serial'];
            $tmp['##badge.users##']          = Html::clean(getUserName($badge["users_id"]));
            $tmp['##badge.dateexpiration##'] = Html::convDate($badge['date_expiration']);

            $this->datas['badges'][] = $tmp;
         }
      }

      // Badge request
      $this->datas['##lang.badgerequest.visitorrealname##']  = __('Visitor realname', 'badges');
      $this->datas['##lang.badgerequest.visitorfirstname##'] = __('Visitor firstname', 'badges');
      $this->datas['##lang.badgerequest.visitorsociety##']   = __('Visitor society', 'badges');
      $this->datas['##lang.badgerequest.arrivaldate##']      = __('Arrival date', 'badges');
      $this->datas['##lang.badgerequest.requester##']        = __('Requester');

      if (isset($options['badgerequest'])) {
         foreach ($options['badgerequest'] as $id => $badge) {
            $tmp = array();

            $tmp['##badgerequest.visitorrealname##']  = $badge['visitor_realname'];
            $tmp['##badgerequest.visitorfirstname##'] = $badge['visitor_firstname'];
            $tmp['##badgerequest.visitorsociety##']   = $badge['visitor_society'];
            $tmp['##badgerequest.arrivaldate##']      = Html::convDate($badge['affectation_date']);
            $tmp['##badgerequest.requester##']        = Html::clean(getUserName(Session::getLoginUserID()));

            $this->datas['badgerequest'][] = $tmp;
         }
      }
   }

   function getTags() {

      $tags = array('badge.name'                    => __('Name'),
                    'badge.serial'                  => __('Serial number'),
                    'badge.dateexpiration'          => __('Date of end of validity', 'badges'),
                    'badge.users'                   => __('Allotted to', 'badges'),
                    'badgerequest.visitorrealname'  => __('Visitor realname', 'badges'),
                    'badgerequest.visitorfirstname' => __('Visitor firstname', 'badges'),
                    'badgerequest.visitorsociety'   => __('Visitor society', 'badges'),
                    'badgerequest.arrivaldate'      => __('Arrival date', 'badges'),
                    'badgerequest.requester'        => __('Requester'));
      
      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag, 
                                   'label' => $label,
                                   'value' => true));
      }

      $this->addTagToList(array('tag'     => 'badgerequest',
                                'label'   => __('Badges request', 'badges'),
                                'value'   => false,
                                'foreach' => true,
                                'events'  => array(self::BadgesWhichExpire)));
      
      $this->addTagToList(array('tag'     => 'badges',
                                'label'   => __('Badges expired or badges which expires', 'badges'),
                                'value'   => false,
                                'foreach' => true,
                                'events'  => array(self::BadgesWhichExpire, self::ExpiredBadges)));

      asort($this->tag_descriptions);
   }
   
      /**
    * Get additionnals targets for Tickets
   **/
   function getAdditionalTargets($event='') {
      if ($event == self::BadgesReturn || $event == self::AccessBadgeRequest) {
         $this->addTarget(self::REQUESTER, __("Requester"));
      }
   }

   function getSpecificTargets($data, $options) {
      switch ($data['items_id']) {
         case self::REQUESTER:
            if (isset($this->options['badgerequest'])) {
               foreach ($this->options['badgerequest'] as $badgeRequest) {
                  $this->target_object->fields['requesters_id'] = $badgeRequest['requesters_id'];
                  $this->getUserByField("requesters_id");
               }
            }
            break;
      }
   }

}

?>
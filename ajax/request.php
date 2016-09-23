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

include ("../../../inc/includes.php");

Session::checkLoginUser();
//Html::header_nocache();

switch($_POST['action']){
   case 'addToCart':
      header('Content-Type: application/json; charset=UTF-8"');
      $request = new PluginBadgesRequest();
      echo json_encode($request->addToCart($_POST));
      break;
   
   case 'addBadges':
      header('Content-Type: application/json; charset=UTF-8"');
      $request = new PluginBadgesRequest();
      echo json_encode($request->addBadges($_POST));
      break;
   
   case 'reloadAvailableBadges':
      header("Content-Type: text/html; charset=UTF-8");
      $request = new PluginBadgesRequest();
      if (!isset($_POST['used'])) {
         $_POST['used'] = array();
      }
      $request->loadAvailableBadges($_POST['used']);
      break;
   
   case 'loadBadgeInformation':
      header("Content-Type: text/html; charset=UTF-8");
      $return = new PluginBadgesReturn();
      $return->loadBadgeInformation(Session::getLoginUserID(), $_POST['badges_id']);
      break;
   
   case 'returnBadges':
      header('Content-Type: application/json; charset=UTF-8"');
      $return = new PluginBadgesReturn();
      echo json_encode($return->returnBadge($_POST));
      break;
   
   case 'searchBadges':
      header('Content-Type: application/json; charset=UTF-8"');
      $request = new PluginBadgesRequest();
      echo json_encode($request->listItems($_POST['requesters_id'], $_POST));
      break;
}

?>
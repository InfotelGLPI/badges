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

include('../../../inc/includes.php');

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$badge = new PluginBadgesBadge();

if (isset($_POST["add"])) {

   $badge->check(-1, CREATE, $_POST);
   $newID = $badge->add($_POST);
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($badge->getFormURL() . "?id=" . $newID);
   }
   Html::back();

} else if (isset($_POST["delete"])) {

   $badge->check($_POST['id'], DELETE);
   $badge->delete($_POST);
   $badge->redirectToList();

} else if (isset($_POST["restore"])) {

   $badge->check($_POST['id'], PURGE);
   $badge->restore($_POST);
   $badge->redirectToList();

} else if (isset($_POST["purge"])) {

   $badge->check($_POST['id'], PURGE);
   $badge->delete($_POST, 1);
   $badge->redirectToList();

} else if (isset($_POST["update"])) {

   $badge->check($_POST['id'], UPDATE);
   $badge->update($_POST);
   Html::back();

} else {

   $badge->checkGlobal(READ);

   if (Session::getCurrentInterface() == 'central') {
   if (Plugin::isPluginActive("environment")) {
       Html::header(PluginBadgesBadge::getTypeName(2), '', "assets", "pluginenvironmentdisplay", "badges");
    } else {
       Html::header(PluginBadgesBadge::getTypeName(2), '', "assets", "pluginbadgesbadge");
    }
  } else {
     if (('servicecatalog')) {
        PluginServicecatalogMain::showDefaultHeaderHelpdesk(PluginBadgesBadge::getTypeName(2), true);
     } else {
        Html::helpHeader(PluginBadgesBadge::getTypeName(2));
     }
  }
   $badge->display($_GET);

   if (Session::getCurrentInterface() != 'central'
      && Plugin::isPluginActive('servicecatalog')) {

     PluginServicecatalogMain::showNavBarFooter('badges');
  }

  if (Session::getCurrentInterface() == 'central') {
     Html::footer();
  } else {
     Html::helpFooter();
  }
}

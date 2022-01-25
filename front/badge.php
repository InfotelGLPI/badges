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

$plugin = new Plugin();


if (Session::getCurrentInterface() == 'central') {
   if ($plugin->isActivated("environment")) {
     Html::header(PluginBadgesBadge::getTypeName(2), '', "assets", "pluginenvironmentdisplay", "badges");
  } else {
     Html::header(PluginBadgesBadge::getTypeName(2), '', "assets", "pluginbadgesbadge");
  }
} else {
   if ($plugin->isActivated('servicecatalog')) {
      PluginServicecatalogMain::showDefaultHeaderHelpdesk(PluginBadgesBadge::getTypeName(2));
   } else {
      Html::helpHeader(PluginBadgesBadge::getTypeName(2));
   }
}


$badge = new PluginBadgesBadge();
$badge->checkGlobal(READ);

if ($badge->canView()) {

   Search::show("PluginBadgesBadge");

} else {
   Html::displayRightError();
}

if (Session::getCurrentInterface() != 'central'
    && $plugin->isActivated('servicecatalog')) {

   PluginServicecatalogMain::showNavBarFooter('badges');
}

if (Session::getCurrentInterface() == 'central') {
   Html::footer();
} else {
   Html::helpFooter();
}

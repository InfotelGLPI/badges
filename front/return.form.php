<?php

/*
  -------------------------------------------------------------------------
  Badges plugin for GLPI
  Copyright (C) 2014 by the Badges Development Team.
  -------------------------------------------------------------------------

  LICENSE

  This file is part of Badges.

  Badges is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  Badges is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Badges. If not, see <http://www.gnu.org/licenses/>.
  -------------------------------------------------------------------------- */
include ('../../../inc/includes.php');

$return = new PluginBadgesReturn();

if (isset($_POST["force_return"])) {
   $return->check(-1, UPDATE, $_POST);
   $result = $return->returnBadge($_POST);
   Session::addMessageAfterRedirect($result['message']);
   
   Html::back();
}

?>
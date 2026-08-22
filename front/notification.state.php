<?php

/**
 * -------------------------------------------------------------------------
 * badges plugin for GLPI
 * Copyright (C) 2015-2026 by the badges Development Team.
 *
 * https://github.com/InfotelGLPI/badges
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of badges.
 *
 * badges is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * badges is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with badges. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

use GlpiPlugin\Badges\Config;
use GlpiPlugin\Badges\NotificationState;

Session::checkRight("config", UPDATE);

$config = new Config();
$notif = new NotificationState();

if (isset($_POST["add"])) {
    $notif->addNotificationState($_POST['states_id']);
    Html::back();
} elseif (isset($_POST["delete"])) {
    // Guard against a "delete" POST that carries no (or a non-array) "item":
    // iterating a missing/null value would raise a PHP warning. Cast each key to
    // int before deletion as a defensive measure.
    if (isset($_POST["item"]) && is_array($_POST["item"])) {
        foreach ($_POST["item"] as $key => $val) {
            if ($val == 1) {
                $notif->delete(['id' => (int) $key]);
            }
        }
    }
    Html::back();
} elseif (isset($_POST["update"])) {
    $config->update($_POST);
    Html::back();
}

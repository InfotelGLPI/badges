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

use GlpiPlugin\Badges\Request;
use GlpiPlugin\Badges\BadgeReturn;

Session::checkRight('plugin_badges', READ);

switch ($_POST['action']) {
    case 'addToCart':
        header('Content-Type: application/json; charset=UTF-8');
        $request = new Request();
        echo json_encode($request->addToCart($_POST));
        break;

    case 'addBadges':
        header('Content-Type: application/json; charset=UTF-8');
        $request = new Request();
        echo json_encode($request->addBadges($_POST));
        break;

    case 'reloadAvailableBadges':
        header("Content-Type: text/html; charset=UTF-8");
        $request = new Request();
        if (!isset($_POST['used'])) {
            $_POST['used'] = [];
        }
        $request->loadAvailableBadges($_POST['used']);
        break;

    case 'loadBadgeInformation':
        header("Content-Type: text/html; charset=UTF-8");
        $return = new BadgeReturn();
        $return->loadBadgeInformation(Session::getLoginUserID(), $_POST['badges_id']);
        break;

    case 'returnBadges':
        header('Content-Type: application/json; charset=UTF-8');
        $return = new BadgeReturn();
        $_POST['requesters_id'] = Session::getLoginUserID();
        echo json_encode($return->returnBadge($_POST));
        break;

    case 'searchBadges':
        header('Content-Type: application/json; charset=UTF-8');
        // Use Request::listItems($requesters_id, $options): it has the two-argument
        // signature and pins the search to the current requester. BadgeReturn::listItems()
        // expects a single argument, so the previous call passed the user id as $fields.
        $request = new Request();
        echo json_encode($request->listItems(Session::getLoginUserID(), $_POST));
        break;
}

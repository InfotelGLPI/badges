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

use GlpiPlugin\Badges\BadgeReturn;

$return = new BadgeReturn();

if (isset($_POST["force_return"])) {
    $return->check(-1, UPDATE, $_POST);
    // Security: never trust a client-supplied requesters_id. returnBadge()
    // forwards it to getUserBadges() and would otherwise let a user force the
    // return of another requester's badges. Pin it to the current user, exactly
    // like the AJAX route (ajax/request.php, action=returnBadges).
    $_POST['requesters_id'] = Session::getLoginUserID();
    $result = $return->returnBadge($_POST);
    Session::addMessageAfterRedirect($result['message']);

    Html::back();
}

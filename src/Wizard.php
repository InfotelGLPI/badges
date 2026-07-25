<?php

/*
 -------------------------------------------------------------------------
 badges plugin for GLPI
 Copyright (C) 2015-2026 by the badges Development Team.

 https://github.com/InfotelGLPI/badges
 -------------------------------------------------------------------------

 LICENSE

 This file is part of badges.

 badges is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
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

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;

/**
 * Class Wizard
 *
 * This class shows the plugin main page
 *
 * @package    Badges
 * @author     Ludovic Dupont
 */
class Wizard extends CommonDBTM
{

    static $rightname = "plugin_badges";

   /**
    * @param int $nb
    *
    * @return string|translated
    */
    static function getTypeName($nb = 0)
    {
        return __('Badges wizard', 'badges');
    }

   /**
    * Show config menu
    */
    function showMenu()
    {

        if (!$this->canView()) {
            return false;
        }

        TemplateRenderer::getInstance()->display('@badges/wizard_menu.html.twig', [
            'badge_icon' => Badge::getIcon(),
            'webdir'     => PLUGIN_BADGES_WEBDIR,
        ]);
    }

   /**
    * Show wizard form of the current step
    *
    * @param $step
    */
    function showWizard($step)
    {

        switch ($step) {
            case 'badgerequest':
                $badgerequest = new Request();
                $badgerequest->showBadgeRequest();
                break;
            case 'badgereturn':
                $badgereturn = new BadgeReturn();
                $badgereturn->showBadgeReturn();
                break;
        }
    }
}

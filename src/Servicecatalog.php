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

use CommonGLPI;
use Session;

class Servicecatalog extends CommonGLPI
{
    public static $rightname = 'plugin_badges';

    public $dohistory = false;

    public static function canUse()
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * @return string
     */
    public static function getMenuLink()
    {
        $root = PLUGIN_BADGES_WEBDIR;
        return $root . "/front/wizard.php";
    }

    /**
     * @return string
     */
    public static function getNavBarLink()
    {
        return PLUGIN_BADGES_WEBDIR . "/front/wizard.php";
    }

    public static function getMenuLogo()
    {

        return Badge::getIcon();
    }

    /**
     * @return string
     * @throws \GlpitestSQLError
     */
    public static function getMenuLogoCss()
    {

        $addstyle = "font-size: 4.5em;";
        return $addstyle;
    }

    public static function getMenuTitle()
    {

        return __('Manage temporary badges', 'badges');
    }


    public static function getMenuComment()
    {

        return __('Manage temporary badges', 'badges');
    }

    public static function getLinkList()
    {
        return "";
    }

    public static function getList()
    {
        return "";
    }
}

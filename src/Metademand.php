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

/**
 * Class Metademand
 */
class Metademand extends CommonGLPI
{

    static $rightname = 'plugin_metademands';

    var $dohistory = false;

    /**
     * @return array
     */
    static function addFieldItems()
    {
        return [
            Badge::class,
        ];
    }

    /**
     * @return array
     */
    static function addDropdownFieldItems()
    {
        $name = Badge::getTypeName(2);
        return [$name => [Badge::class => Badge::getTypeName()]];
        //       return ['PluginBadgesBadge',
        //       ];
    }

    /**
     * @return array
     */
    static function getFieldItemsName()
    {
        $prefix = _n('Badge', 'Badges', 2, 'badges') . " - ";
        return [
            Badge::class => $prefix . Badge::getTypeName(1),
        ];
    }

    /**
     * @return array
     */
    static function getFieldItemsType()
    {
        return [
            Badge::class => 'dropdown',
        ];
    }
}

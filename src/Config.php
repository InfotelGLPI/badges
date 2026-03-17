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

use CommonDBTM;
use CommonGLPI;
use DBConnection;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Migration;

/**
 * Class Config
 */
class Config extends CommonDBTM
{
    public static $rightname = "config";
   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string
    */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == 'CronTask'
            && $item->getField('name') == "BadgesAlert") {
            return self::createTabEntry(__s('Plugin Setup', 'badges'));
        }
        return '';
    }

    public static function getIcon()
    {
        return "ti ti-id";
    }

   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'CronTask') {
            $target = PLUGIN_BADGES_WEBDIR . "/front/notification.state.php";
            Badge::configCron($target);
        }
        return true;
    }


    /**
     * @param $target
     * @param $ID
     */
    public function showConfigForm($target)
    {

        if (!$this->canCreate()) {
            return false;
        }

        $canedit = true;

        if ($canedit) {
            $ID = 1;
            $this->getFromDB($ID);
            $delay_expired = $this->fields["delay_expired"];
            $delay_whichexpire = $this->fields["delay_whichexpire"];
            $delay_stamp_first = mktime(0, 0, 0, date("m"), date("d") - $delay_expired, date("y"));
            $delay_stamp_next = mktime(0, 0, 0, date("m"), date("d") + $delay_whichexpire, date("y"));
            $date_first = date("Y-m-d", $delay_stamp_first);
            $date_next = date("Y-m-d", $delay_stamp_next);

            TemplateRenderer::getInstance()->display(
                '@badges/config.html.twig',
                [
                    'id'                => 1,
                    'item'              => $this,
                    'config'            => $this->fields,
                    'action'            => $target,
                    'date_first'            => Html::convDate($date_first),
                    'date_next'            => Html::convDate($date_next),
                ],
            );
        }
        return true;
    }


   /**
    * @param $target
    * @param $ID
    */
    public function showFormBadgeReturn($target, $ID)
    {

        $this->getFromDB($ID);

        echo "<div class='center'>";
        echo "<form method='post' action=\"$target\">";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='4'>";
        echo __('Time of checking of validity of the badges', 'badges');
        echo "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Badge return delay', 'badges') . "&nbsp;";
        echo "</td>";
        echo "<td>";
        Dropdown::showTimeStamp("delay_returnexpire", ['min'             => DAY_TIMESTAMP,
                                                          'max'             => 52 * WEEK_TIMESTAMP,
                                                          'step'            => DAY_TIMESTAMP,
                                                          'value'           => $this->fields["delay_returnexpire"],
                                                          'addfirstminutes' => true,
                                                          'inhours'         => false]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center' colspan='4'>";
        echo Html::hidden('id', ['value' => $ID]);
        echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `delay_expired` varchar(50) collate utf8mb4_unicode_ci NOT NULL DEFAULT '30',
                        `delay_whichexpire` varchar(50) collate utf8mb4_unicode_ci NOT NULL DEFAULT '30',
                        `delay_returnexpire` int {$default_key_sign} NOT NULL DEFAULT '0',
                        PRIMARY KEY  (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

            $DB->insert(
                $table,
                ['id' => 1,
                    'delay_expired' => 30,
                    'delay_whichexpire' => 30,
                    'delay_returnexpire' => 30]
            );
        }
    }
}

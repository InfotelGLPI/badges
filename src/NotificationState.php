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
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Html;
use MassiveAction;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class NotificationState
 */
class NotificationState extends CommonDBTM
{
    public static $rightname = "config";
    /**
     * @return array
     */
    public function findStates()
    {
        $state = new self();
        $states = $state->find();
        $data = [];
        foreach ($states as $dataChilds) {
            $data[] = $dataChilds["states_id"];
        }

        return $data;
    }

    /**
     * @param $states_id
     */
    public function addNotificationState($states_id)
    {
        if ($this->getFromDBbyCrit(['states_id' => $states_id])) {
            $this->update([
                'id' => $this->fields['id'],
                'states_id' => $states_id
            ]);
        } else {
            $this->add(['states_id' => $states_id]);
        }
    }


    /**
     * @param $target
     */
    public function showNotificationForm($target)
    {

        $states = $this->find([], ["states_id ASC"]);

        $used = $entries = [];

        $canedit = $this->canEdit($this->getID());

        foreach ($states as $value) {
            $used[] = $value['states_id'];


            $entries[] = [
                'itemtype' => self::class,
                'id' => $value['id'],
                'name' => Dropdown::getDropdownName(
                    "glpi_states",
                    $value["states_id"]
                ),
            ];
        }


        $columns = [
            'name' => __('Name'),
        ];
        $formatters = [
            'name' => 'raw_html',
        ];
        $footers = [];

        $rand = mt_rand();

        TemplateRenderer::getInstance()->display(
            '@badges/status_cron.html.twig',
            [
                'id'                => 1,
                'item'              => $this,
                'config'            => $this->fields,
                'action'            => $target,
                'used'            => $used,
                'can_edit' => $canedit,
                'datatable_params' => [
                    'is_tab' => true,
                    'nofilter' => true,
                    'nosort' => true,
                    'columns' => $columns,
                    'formatters' => $formatters,
                    'entries' => $entries,
                    'footers' => $footers,
                    'total_number' => count($entries),
                    'filtered_number' => count($entries),
                    'showmassiveactions' => $canedit,
                    'massiveactionparams' => [
                        'container' => 'massiveactioncontainer' . $rand,
                        'itemtype'  => self::class,
                    ],
                ],
            ],
        );
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        $forbidden[] = 'delete';
        $forbidden[] = 'restore';
        return $forbidden;
    }
}

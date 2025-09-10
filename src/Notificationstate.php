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
use Html;
use MassiveAction;

/**
 * Class NotificationState
 */
class NotificationState extends CommonDBTM
{

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
    public function showAddForm($target)
    {
        $state = new self();
        $states = $state->find();
        $used = [];
        foreach ($states as $data) {
            $used[] = $data['states_id'];
        }

        echo "<div align='center'><form method='post'  action=\"$target\">";
        echo "<table class='tab_cadre_fixe' cellpadding='5'><tr ><th colspan='2'>";
        echo __('Unused status for expiration mailing', 'badges');
        echo "</th></tr>";
        echo "<tr class='tab_bg_1'><td>";
        Dropdown::show('State', [
            'name' => "states_id",
            'used' => $used
        ]);
        echo "</td>";
        echo "<td>";
        echo "<div align='center'>";
        echo Html::submit(_sx('button', 'Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
        echo "</div></td></tr>";
        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }

    /**
     * @param $target
     */
    public function showNotificationForm($target)
    {
        $rand = mt_rand();

        $data = $this->find([], ["states_id ASC"]);

        if (count($data) != 0) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = [
                'item' => __CLASS__,
                'container' => 'mass' . __CLASS__ . $rand
            ];
            Html::showMassiveActions($massiveactionparams);

            echo "<div align='center'>";
            echo "<form method='post' name='massiveaction_form$rand' id='massiveaction_form$rand'  action=\"$target\">";
            echo "<table class='tab_cadre_fixe' cellpadding='5'>";
            echo "<tr>";
            echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
            echo "<th>" . __('Unused status for expiration mailing', 'badges') . "</th>";
            echo "</tr>";
            foreach ($data as $ligne) {
                echo "<tr class='tab_bg_1'>";
                echo "<td width='10'>";
                Html::showMassiveActionCheckBox(__CLASS__, $ligne["id"]);
                echo "</td>";
                echo "<td>" . Dropdown::getDropdownName("glpi_states", $ligne["states_id"]) . "</td>";
                echo "</tr>";
            }

            $paramsma['ontop'] = false;

            echo "</table>";
            Html::closeForm();
            echo "</div>";

            Html::showMassiveActions($paramsma);
        }
    }

    /**
     * Get the specific massive actions
     *
     * @param $checkitem link item to check right   (default NULL)
     *
     * @return array $array of massive actions
     * @since version 0.84
     *
     */
    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions['GlpiPlugin\Badges\NotificationState;' . MassiveAction::CLASS_ACTION_SEPARATOR . 'purge'] = __('Delete');

        return $actions;
    }

    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     */
    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     */
    static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'purge':
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    /**
     * @param MassiveAction $ma
     * @param CommonDBTM $item
     * @param array $ids
     *
     * @return nothing|void
     * @since version 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     *
     */
    static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        $state = new self();

        switch ($ma->getAction()) {
            case "purge":

                foreach ($ids as $key) {
                    if ($state->delete(['id' => $key])) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                    }
                }
                break;
        }
    }
}

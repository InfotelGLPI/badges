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
use DBConnection;
use Dropdown;
use DropdownVisibility;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\Features\State;
use Html;
use Location;
use MassiveAction;
use Migration;
use NotificationEvent;
use Plugin;
use Session;
use Glpi\Features\StateInterface;
/**
 * Class Badge
 */
class Badge extends CommonDBTM implements StateInterface
{

    public $dohistory = true;
    static $rightname = "plugin_badges";
    protected $usenotepad = true;
    use State;
    /**
     * @param int $nb
     *
     * @return string
     */
    static function getTypeName($nb = 0)
    {
        return _n('Badge', 'Badges', $nb, 'badges');
    }

    /**
     * @return string
     */
    static function getIcon()
    {
        return "ti ti-id";
    }

    /**
     * @return array
     */
    function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(2)
        ];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'datatype' => 'itemlink',
            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id' => '2',
            'table' => 'glpi_plugin_badges_badgetypes',
            'field' => 'name',
            'name' => __('Type'),
            'datatype' => 'dropdown',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id' => '4',
            'table' => $this->getTable(),
            'field' => 'date_affectation',
            'name' => __('Affectation date', 'badges'),
            'datatype' => 'date',
        ];

        $tab[] = [
            'id' => '5',
            'table' => $this->getTable(),
            'field' => 'date_expiration',
            'name' => __('Date of end of validity', 'badges'),
            'datatype' => 'date',
        ];

        $tab[] = [
            'id' => '6',
            'table' => $this->getTable(),
            'field' => 'serial',
            'name' => __('Serial number'),
        ];


        $tab[] = [
            'id' => '7',
            'table' => 'glpi_states',
            'field' => 'completename',
            'name' => __('Status'),
            'datatype' => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $tab[] = [
            'id' => '8',
            'table' => $this->getTable(),
            'field' => 'comment',
            'name' => __('Comments'),
            'datatype' => 'text',
        ];

        $tab[] = [
            'id' => '9',
            'table' => $this->getTable(),
            'field' => 'is_helpdesk_visible',
            'name' => __('Associable to a ticket'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '10',
            'table' => 'glpi_users',
            'field' => 'name',
            'name' => __('User'),
            'datatype' => 'dropdown',
            'right' => 'all',
        ];

        $tab[] = [
            'id' => '11',
            'table' => $this->getTable(),
            'field' => 'date_mod',
            'name' => __('Last update'),
            'datatype' => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id' => '30',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'datatype' => 'number',
        ];

        $tab[] = [
            'id' => '80',
            'table' => 'glpi_entities',
            'field' => 'completename',
            'name' => __('Entity'),
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id' => '81',
            'table' => 'glpi_entities',
            'field' => 'entities_id',
            'name' => __('Entity') . "-" . __('ID'),
        ];

        $tab[] = [
            'id' => '82',
            'table' => $this->getTable(),
            'field' => 'is_bookable',
            'name' => __('Bookable', 'badges'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '86',
            'table' => $this->getTable(),
            'field' => 'is_recursive',
            'name' => __('Child entities'),
            'datatype' => 'bool'
        ];

        return $tab;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(BadgeReturn::class, $ong, $options);
        $this->addStandardTab('Item_Ticket', $ong, $options);
        $this->addStandardTab('Item_Problem', $ong, $options);
        $this->addStandardTab('Document_Item', $ong, $options);
        $this->addStandardTab('Notepad', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    /**
     * @param datas $input
     *
     * @return datas
     */
    function prepareInputForAdd($input)
    {
        if (isset($input['date_affectation']) && empty($input['date_affectation'])) {
            $input['date_affectation'] = 'NULL';
        }
        if (isset($input['date_expiration']) && empty($input['date_expiration'])) {
            $input['date_expiration'] = 'NULL';
        }
        return $input;
    }

    /**
     * @param datas $input
     *
     * @return datas
     */
    function prepareInputForUpdate($input)
    {
        if (isset($input['date_affectation']) && empty($input['date_affectation'])) {
            $input['date_affectation'] = 'NULL';
        }
        if (isset($input['date_expiration']) && empty($input['date_expiration'])) {
            $input['date_expiration'] = 'NULL';
        }

        return $input;
    }


    /**
     * Print the badge form
     *
     * @param $ID        integer  ID of the item
     * @param $options   array
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     *
     * @return boolean item found
     **/
    function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('@badges/badge_form.html.twig', [
            'item' => $this,
            'params' => $options,
        ]);

        return true;
    }


    //for search engine
    /**
     * @param String $field
     * @param String $values
     * @param array $options
     *
     * @return date|return|string|translated
     */
    static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'date_expiration' :

                if (empty($values[$field])) {
                    return __('infinite');
                } else {
                    return Html::convDate($values[$field]);
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    //Massive Action

    /**
     * @param null $checkitem
     *
     * @return array
     */
    function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if (Session::haveRight('transfer', READ)
            && Session::isMultiEntitiesMode()
            && $isadmin
        ) {
            $actions['GlpiPlugin\Badges\Badge' . MassiveAction::CLASS_ACTION_SEPARATOR . 'transfer'] = __('Transfer');
        }
        return $actions;
    }


    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     */
    static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case "transfer" :
                Dropdown::show('Entity');
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
                return true;
                break;
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
        switch ($ma->getAction()) {
            case "transfer" :
                $input = $ma->getInput();

                if ($item->getType() == Badge::class) {
                    foreach ($ids as $key) {
                        $item->getFromDB($key);
                        $type = BadgeType::transfer(
                            $item->fields["plugin_badges_badgetypes_id"],
                            $input['entities_id']
                        );
                        if ($type > 0) {
                            $values["id"] = $key;
                            $values["plugin_badges_badgetypes_id"] = $type;
                            $item->update($values);
                        }

                        unset($values);
                        $values["id"] = $key;
                        $values["entities_id"] = $input['entities_id'];

                        if ($item->update($values)) {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        }
                    }
                }
                break;
        }
        return;
    }


    // Cron action

    /**
     * @param $name
     *
     * @return array
     */
    static function cronInfo($name)
    {
        switch ($name) {
            case 'BadgesAlert':
                return [
                    'description' => __('Badges which expires', 'badges')
                ];   // Optional
                break;
        }
        return [];
    }

    /**
     * @return array
     */
    static function queryExpiredBadges()
    {
        global $DB;

        $config = new Config();
        $notif = new NotificationState();

        $config->getFromDB('1');
        $delay = $config->fields["delay_expired"];

        $criteria = [
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'NOT' => ['date_expiration' => null
                ],
                'is_deleted'   => 0,
                new QueryExpression("DATEDIFF(CURDATE(), " . $DB->quoteName('date_expiration') . ") > $delay"),
                new QueryExpression("DATEDIFF(CURDATE(), " . $DB->quoteName('date_expiration') . ") > 0")
            ]
        ];

        if (count($notif->findStates()) > 0) {
            $criteria['WHERE'] = $criteria['WHERE'] + ['states_id' => $notif->findStates()];
        }
        return $criteria;
    }

    /**
     * @return array
     */
    static function queryBadgesWhichExpire()
    {
        global $DB;

        $config = new Config();
        $notif = new NotificationState();

        $config->getFromDB('1');
        $delay = $config->fields["delay_whichexpire"];

        $criteria = [
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'NOT' => ['date_expiration' => null],
                'is_deleted'   => 0,
                new QueryExpression("DATEDIFF(CURDATE(), " . $DB->quoteName('date_expiration') . ") > -$delay"),
                new QueryExpression("DATEDIFF(CURDATE(), " . $DB->quoteName('date_expiration') . ") < 0")
            ]
        ];

        if (count($notif->findStates()) > 0) {
            $criteria['WHERE'] = $criteria['WHERE'] + ['states_id' => $notif->findStates()];
        }
        return $criteria;

    }


    /**
     * Cron action on badges : ExpiredBadges or BadgesWhichExpire
     *
     * @param $task for log, if NULL display
     *
     *
     * @return int
     */
    static function cronBadgesAlert($task = null)
    {
        global $DB, $CFG_GLPI;

        if (!$CFG_GLPI["notifications_mailing"]) {
            return 0;
        }

        $query_expired = self::queryExpiredBadges();
        $query_whichexpire = self::queryBadgesWhichExpire();

        $querys = [
            NotificationTargetBadge::BadgesWhichExpire => $query_whichexpire,
            NotificationTargetBadge::ExpiredBadges => $query_expired
        ];

        $badge_infos = [];
        $badge_messages = [];
        $cron_status = 0;

        foreach ($querys as $type => $query) {
            $badge_infos[$type] = [];
            if (!empty($query)) {
                foreach ($DB->request($query) as $data) {
                    $entity = $data['entities_id'];
                    $message = $data["name"] . ": " .
                        Html::convDate($data["date_expiration"]) . "<br>\n";
                    $badge_infos[$type][$entity][] = $data;

                    if (!isset($badge_messages[$type][$entity])) {
                        $badge_messages[$type][$entity] = __('Badges at the end of the validity', 'badges') . "<br />";
                    }
                    $badge_messages[$type][$entity] .= $message;
                }
            }
        }

        foreach ($querys as $type => $query) {
            foreach ($badge_infos[$type] as $entity => $badges) {
                Plugin::loadLang('badges');

                if (NotificationEvent::raiseEvent($type, new Badge(), [
                    'entities_id' => $entity,
                    'badges' => $badges
                ])
                ) {
                    $message = $badge_messages[$type][$entity];
                    $cron_status = 1;
                    if ($task) {
                        $task->log(
                            Dropdown::getDropdownName(
                                "glpi_entities",
                                $entity
                            ) . ":  $message\n"
                        );
                        $task->addVolume(1);
                    } else {
                        Session::addMessageAfterRedirect(
                            Dropdown::getDropdownName(
                                "glpi_entities",
                                $entity
                            ) . ":  $message"
                        );
                    }
                } else {
                    if ($task) {
                        $task->log(
                            Dropdown::getDropdownName("glpi_entities", $entity) .
                            ":  Send badges alert failed\n"
                        );
                    } else {
                        Session::addMessageAfterRedirect(
                            Dropdown::getDropdownName("glpi_entities", $entity) .
                            ":  Send badges alert failed",
                            false,
                            ERROR
                        );
                    }
                }
            }
        }

        return $cron_status;
    }

    /**
     * @param $target
     */
    static function configCron($target)
    {
        $notif = new NotificationState();
        $config = new Config();

        $config->showConfigForm($target, 1);
        $notif->showNotificationForm($target);
    }

    static function getMenuContent()
    {
        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page'] = self::getSearchURL(false);
        $menu['links']['search'] = self::getSearchURL(false);
        $menu['links']['lists'] = "";
        if (self::canCreate()) {
            $menu['links']['add'] = self::getFormURL(false);
        }
        $menu['icon'] = self::getIcon();

        return $menu;
    }

    static function removeRightsFromSession()
    {
        if (isset($_SESSION['glpimenu']['assets']['types'][Badge::class])) {
            unset($_SESSION['glpimenu']['assets']['types'][Badge::class]);
        }
        if (isset($_SESSION['glpimenu']['assets']['content'][Badge::class])) {
            unset($_SESSION['glpimenu']['assets']['content'][Badge::class]);
        }
    }

    public function getStateVisibilityCriteria(): array
    {
        return  [
            'LEFT JOIN' => [
                DropdownVisibility::getTable() => [
                    'ON' => [
                        DropdownVisibility::getTable() => 'items_id',
                        \State::getTable() => 'id', [
                            'AND' => [
                                DropdownVisibility::getTable() . '.itemtype' => \State::getType(),
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE' => [
                DropdownVisibility::getTable() . '.itemtype' => \State::getType(),
                DropdownVisibility::getTable() . '.visible_itemtype' => static::class,
                DropdownVisibility::getTable() . '.is_visible' => 1,
            ],
        ];
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
                        `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `is_recursive` tinyint NOT NULL default '0',
                        `name` varchar(255) collate utf8mb4_unicode_ci DEFAULT NULL,
                        `serial` varchar(255) collate utf8mb4_unicode_ci DEFAULT NULL,
                        `plugin_badges_badgetypes_id` int {$default_key_sign} NOT NULL default '0' COMMENT 'RELATION to glpi_plugin_badges_badgetypes (id)',
                        `locations_id` int {$default_key_sign} NOT NULL default '0' COMMENT 'RELATION to glpi_locations (id)',
                        `date_affectation` timestamp NULL DEFAULT NULL,
                        `date_expiration` timestamp NULL DEFAULT NULL,
                        `states_id` int {$default_key_sign} NOT NULL DEFAULT '0' COMMENT 'RELATION to glpi_states (id)',
                        `users_id` int {$default_key_sign} NOT NULL DEFAULT '0' COMMENT 'RELATION to glpi_users (id)',
                        `users_id_tech` int {$default_key_sign} NOT NULL DEFAULT '0' COMMENT 'RELATION to glpi_users (id)',
                        `is_helpdesk_visible` int {$default_key_sign} NOT NULL DEFAULT '1',
                        `date_mod` timestamp NULL DEFAULT NULL,
                        `comment` text collate utf8mb4_unicode_ci,
                        `notepad` longtext collate utf8mb4_unicode_ci,
                        `is_deleted` tinyint NOT NULL DEFAULT '0',
                        `is_bookable` tinyint NOT NULL DEFAULT '1',
                        PRIMARY KEY  (`id`),
                        KEY `name` (`name`),
                        KEY `entities_id` (`entities_id`),
                        KEY `is_recursive` (`is_recursive`),
                        KEY `plugin_badges_badgetypes_id` (`plugin_badges_badgetypes_id`),
                        KEY `locations_id` (`locations_id`),
                        KEY `date_expiration` (`date_expiration`),
                        KEY `states_id` (`states_id`),
                        KEY `users_id` (`users_id`),
                        KEY `is_helpdesk_visible` (`is_helpdesk_visible`),
                        KEY `is_deleted` (`is_deleted`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

            $DB->insert(
                'glpi_displaypreferences',
                ['itemtype' => self::class,
                    'num' => 3,
                    'rank' => 2,
                    'users_id' => 0,
                    'interface' => 'central']
            );

            $DB->insert(
                'glpi_displaypreferences',
                ['itemtype' => self::class,
                    'num' => 4,
                    'rank' => 3,
                    'users_id' => 0,
                    'interface' => 'central']
            );

            $DB->insert(
                'glpi_displaypreferences',
                ['itemtype' => self::class,
                    'num' => 5,
                    'rank' => 4,
                    'users_id' => 0,
                    'interface' => 'central']
            );
        }

        if (!$DB->fieldExists($table, "is_bookable")) {
            $migration->addField($table, "is_bookable", "tinyint(1) NOT NULL DEFAULT '1'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "is_recursive")) {
            $migration->addField($table, "is_recursive", "tinyint(1) NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "users_id_tech")) {
            $migration->addField($table, "users_id_tech", "int {$default_key_sign} NOT NULL DEFAULT '0' COMMENT 'RELATION to glpi_users (id)'");
            $migration->migrationOneTable($table);
        }

        //DisplayPreferences Migration
        $classes = ['PluginBadgesBadge' => Badge::class];

        foreach ($classes as $old => $new) {
            $displayusers = $DB->request([
                'SELECT' => [
                    'users_id'
                ],
                'DISTINCT' => true,
                'FROM' => 'glpi_displaypreferences',
                'WHERE' => [
                    'itemtype' => $old,
                ],
            ]);

            if (count($displayusers) > 0) {
                foreach ($displayusers as $displayuser) {
                    $iterator = $DB->request([
                        'SELECT' => [
                            'num',
                            'id'
                        ],
                        'FROM' => 'glpi_displaypreferences',
                        'WHERE' => [
                            'itemtype' => $old,
                            'users_id' => $displayuser['users_id'],
                            'interface' => 'central'
                        ],
                    ]);

                    if (count($iterator) > 0) {
                        foreach ($iterator as $data) {
                            $iterator2 = $DB->request([
                                'SELECT' => [
                                    'id'
                                ],
                                'FROM' => 'glpi_displaypreferences',
                                'WHERE' => [
                                    'itemtype' => $new,
                                    'users_id' => $displayuser['users_id'],
                                    'num' => $data['num'],
                                    'interface' => 'central'
                                ],
                            ]);
                            if (count($iterator2) > 0) {
                                foreach ($iterator2 as $dataid) {
                                    $query = $DB->buildDelete(
                                        'glpi_displaypreferences',
                                        [
                                            'id' => $dataid['id'],
                                        ]
                                    );
                                    $DB->doQuery($query);
                                }
                            } else {
                                $query = $DB->buildUpdate(
                                    'glpi_displaypreferences',
                                    [
                                        'itemtype' => $new,
                                    ],
                                    [
                                        'id' => $data['id'],
                                    ]
                                );
                                $DB->doQuery($query);
                            }
                        }
                    }
                }
            }
        }
    }
}

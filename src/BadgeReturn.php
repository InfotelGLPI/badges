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

use Ajax;
use CommonDBTM;
use CommonGLPI;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Html;
use NotificationEvent;
use Session;
use Toolbox;

/**
 * Class BadgeReturn
 *
 * This class shows the plugin main page
 *
 * @package    Badges
 * @author     Ludovic Dupont
 */
class BadgeReturn extends CommonDBTM
{
    private $request;

    public static $rightname = "plugin_badges";

    public function __construct()
    {
        parent::__construct();

        $this->forceTable("glpi_plugin_badges_requests");
        $this->request = new Request();
    }

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Badge return', 'badges');
    }

    /**
     * @return string
     */
    public static function getIcon()
    {
        return "ti ti-receipt-refund";
    }


    /**
    * Display tab for each users
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            if ($item->getType() == Badge::class) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $dbu = new DbUtils();
                    return self::createTabEntry(
                        Request::getTypeName(),
                        $dbu->countElementsInTable(
                            $this->getTable(),
                            ["badges_id" => $item->getID()]
                        )
                    );
                }
                return Request::getTypeName();
            }
        }
        return '';
    }

    /**
     * Display content for each users
     *
     * @static
     *
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $field = new self();

        if ($item->getType() == Badge::class) {
            $field->showForBadge($item);
        }
        return true;
    }

    /**
     * Show
     *
     * @param type $item
     *
     * @return bool
     */
    public function showForBadge($item)
    {

        if (!$this->canCreate() || !$this->canView()) {
            return false;
        }

        $data = $this->find(['badges_id' => $item->fields['id']], ["affectation_date DESC"]);

        $badge   = new Badge();
        $canedit = $badge->can($item->fields['id'], UPDATE);

        ob_start();
        $this->loadBadgeInformation(0, $item->fields['id']);
        $badge_info_html = ob_get_clean();

        $dbu  = new DbUtils();
        $rows = [];
        foreach ($data as $field) {
            $rows[] = [
                'requester_name'    => $dbu->getUserName($field['requesters_id']),
                'visitor_realname'  => stripslashes($field['visitor_realname']),
                'visitor_firstname' => stripslashes($field['visitor_firstname']),
                'visitor_society'   => stripslashes($field['visitor_society']),
                'affectation_date'  => Html::convDateTime($field['affectation_date']),
                'return_date'       => Html::convDateTime($field['return_date']),
            ];
        }

        TemplateRenderer::getInstance()->display('@badges/badge_return_for_badge.html.twig', [
            'canedit'        => $canedit,
            'form_url'       => Toolbox::getItemTypeFormURL($this->getType()),
            'badge_info_html' => $badge_info_html,
            'badges_id'      => $item->fields['id'],
            'rows'           => $rows,
        ]);
    }


    /**
     * Show list of items
     *
     * @param type $fields
     */
    public function listItems($fields)
    {
        $dbu  = new DbUtils();
        $rows = [];
        foreach ($fields as $field) {
            $rows[] = [
                'requester_name'    => $dbu->getUserName($field['requesters_id']),
                'visitor_realname'  => stripslashes($field['visitor_realname']),
                'visitor_firstname' => stripslashes($field['visitor_firstname']),
                'visitor_society'   => stripslashes($field['visitor_society']),
                'affectation_date'  => Html::convDateTime($field['affectation_date']),
                'return_date'       => Html::convDateTime($field['return_date']),
            ];
        }

        TemplateRenderer::getInstance()->display('@badges/badge_return_list.html.twig', [
            'rows' => $rows,
        ]);
    }


    /**
     * Check mandatory fields
     *
     * @param type $input
     *
     * @return array
     */
    public function checkMandatoryFields($input)
    {
        $msg     = [];
        $checkKo = false;

        $mandatory_fields = ['return_badges_id' => __("Badges in your possession", "badges")];

        foreach ($input as $key => $value) {
            if (isset($mandatory_fields[$key])) {
                if (empty($value)) {
                    $msg[]   = $mandatory_fields[$key];
                    $checkKo = true;
                }
            }
        }

        if ($checkKo) {
            return [false, "<div class='alert alert-important alert-warning d-flex'>" . sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)) . "</div>"];
        }

        return [true, null];
    }

    /**
     * Show badge return
     */
    public function showBadgeReturn()
    {
        $elements = [Dropdown::EMPTY_VALUE];
        foreach ($this->request->getUserBadges(Session::getLoginUserID()) as $val) {
            $elements[$val['badges_id']] = Dropdown::getDropdownName("glpi_plugin_badges_badges", $val['badges_id']);
        }

        ob_start();
        $rand = Dropdown::showFromArray("return_badges_id", $elements, ['on_change' => 'badges_loadBadgeInformation();']);
        $dropdown_html = ob_get_clean();

        $ajax_url = PLUGIN_BADGES_WEBDIR . '/ajax/request.php';
        ob_start();
        echo "function badges_loadBadgeInformation(){";
        Ajax::updateItemJsCode(
            "badges_informations",
            $ajax_url,
            ['action' => 'loadBadgeInformation', 'badges_id' => '__VALUE__'],
            "dropdown_return_badges_id{$rand}"
        );
        echo "}";
        $load_badge_info_js = ob_get_clean();

        Html::requireJs('glpi_dialog');

        TemplateRenderer::getInstance()->display('@badges/badge_return_wizard.html.twig', [
            'dropdown_html'      => $dropdown_html,
            'load_badge_info_js' => $load_badge_info_js,
            'return_date_now'    => Html::convDateTime(date('Y-m-d H:i:s')),
            'cancel_url'         => PLUGIN_BADGES_WEBDIR . '/front/wizard.php',
            'web_dir'            => PLUGIN_BADGES_WEBDIR,
            'requesters_id'      => Session::getLoginUserID(),
        ]);
    }

    /**
     * Load badge information
     *
     * @param type $users_id
     * @param type $badges_id
     */
    public function loadBadgeInformation($users_id, $badges_id)
    {
        $datas = $this->request->getUserBadges($users_id, ["badges_id" => $badges_id]);

        $rows = [];
        foreach ($datas as $data) {
            $rows[] = [
                'visitor_firstname' => stripslashes($data['visitor_firstname']),
                'visitor_realname'  => stripslashes($data['visitor_realname']),
                'visitor_society'   => stripslashes($data['visitor_society']),
                'affectation_date'  => Html::convDateTime($data['affectation_date']),
            ];
        }

        TemplateRenderer::getInstance()->display('@badges/badge_return_info.html.twig', [
            'rows' => $rows,
        ]);
    }

    /**
     * Return badge
     *
     * @param type $params
     *
     * @return array
     */
    public function returnBadge($params)
    {

        [$success, $message] = $this->checkMandatoryFields($params);
        if ($success) {
            $datas = $this->request->getUserBadges(
                $params['requesters_id'],
                ["badges_id" => $params['return_badges_id']]
            );
            foreach ($datas as $data) {
                $this->update(['id'          => $data['id'],
                    'is_affected' => 0,
                    'return_date' => date('Y-m-d H:i:s')]);
            }
            $message = "<div class='alert alert-important alert-success d-flex'>" . __('Badge returned', 'badges') . "</div>";
        }

        return ['success' => $success,
            'message' => $message];
    }

    // Cron action
    /**
     * @param $name
     *
     * @return array
     */
    public static function cronInfo($name)
    {

        switch ($name) {
            case 'BadgesReturnAlert':
                return [
                    'description' => __('Badges return', 'badges')];   // Optional
                break;
        }
        return [];
    }

    /**
     * @return array
     */
    public static function queryBadgesReturnExpire()
    {
        global $DB;

        $config = new Config();

        $config->getFromDB('1');
        $delay = $config->fields["delay_returnexpire"] ?? "";

        $query = null;
        if (!empty($delay)) {
            $requesttable = Request::getTable();
            $badgetable = Badge::getTable();
            $query = [
                'FROM' => $requesttable,
                'LEFT JOIN' => [
                    $badgetable => [
                        'ON' => [
                            $requesttable => 'badges_id',
                            $badgetable => 'id',
                        ],
                    ],
                ],
                'WHERE' => [
                    $requesttable . '.is_affected' => '1',
                    'NOT' => [
                        $requesttable . '.affectation_date' => 'NULL',
                    ],
                    [
                        new QueryExpression("TIME_TO_SEC(TIMEDIFF(NOW(), " . $DB->quoteName($requesttable . '.affectation_date') . ")) > $delay"),
                    ],
                ],
            ];
        }


        return $query;
    }

    /**
     * Cron action on badges : ExpiredBadges or BadgesWhichExpire
     *
     * @param $task for log, if NULL display
     *
     *
     * @return int
     */
    public static function cronBadgesReturnAlert($task = null)
    {
        global $DB, $CFG_GLPI;

        if (!$CFG_GLPI["notifications_mailing"]) {
            return 0;
        }

        $cron_status = 0;

        $query_returnexpire = self::queryBadgesReturnExpire();

        $querys = [NotificationTargetBadge::BadgesReturn => $query_returnexpire];

        $badge_infos    = [];
        $badge_messages = [];

        foreach ($querys as $type => $query) {
            $badge_infos[$type] = [];
            if (!empty($query)) {
                foreach ($DB->request($query) as $data) {
                    $entity                        = $data['entities_id'];
                    $message                       = $data["name"] . "<br>" . __("Arrival date", "badges") . " : "
                                                     . Html::convDate($data["affectation_date"]) . "<br>\n";
                    $badge_infos[$type][$entity][] = $data;

                    if (!isset($badges_infos[$type][$entity])) {
                        $badge_messages[$type][$entity] = __('Badges at the end of the validity', 'badges') . "<br />";
                    }
                    $badge_messages[$type][$entity] .= $message;
                }
            }
        }

        foreach ($querys as $type => $query) {
            foreach ($badge_infos[$type] as $entity => $badges) {
                Plugin::loadLang('badges');
                // Set badge request fields
                foreach ($badges as $badge) {
                    $badgerequest[] = ['visitor_realname'  => $badge['visitor_realname'],
                        'visitor_firstname' => $badge['visitor_firstname'],
                        'visitor_society'   => $badge['visitor_society'],
                        'affectation_date'  => $badge['affectation_date'],
                        'requesters_id'     => $badge['requesters_id']];
                }
                if (NotificationEvent::raiseEvent($type, new Badge(), ['entities_id'  => $entity,
                    'badges'       => $badges,
                    'badgerequest' => $badgerequest])
                ) {
                    $message     = $badge_messages[$type][$entity];
                    $cron_status = 1;
                    if ($task) {
                        $task->log(Dropdown::getDropdownName("glpi_entities", $entity) . ":  $message\n");
                        $task->addVolume(1);
                    } else {
                        Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity) . ":  $message");
                    }
                } else {
                    if ($task) {
                        $task->log(Dropdown::getDropdownName("glpi_entities", $entity)
                                   . ":  Send badges alert failed\n");
                    } else {
                        Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity)
                                                         . ":  Send badges alert failed", false, ERROR);
                    }
                }
            }
        }

        return $cron_status;
    }

    /**
     * @param $target
     */
    public static function configCron($target)
    {
        $config = new Config();
        $config->showFormBadgeReturn($target, 1);
    }
}

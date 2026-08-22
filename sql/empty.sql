--
-- -------------------------------------------------------------------------
-- badges plugin for GLPI
-- Copyright (C) 2015-2026 by the badges Development Team.
--
-- https://github.com/InfotelGLPI/badges
-- -------------------------------------------------------------------------
--
-- LICENSE
--
-- This file is part of badges.
--
-- badges is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- badges is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with badges. If not, see <http://www.gnu.org/licenses/>.
-- --------------------------------------------------------------------------
--

DROP TABLE IF EXISTS `glpi_plugin_badges_badges`;
CREATE TABLE `glpi_plugin_badges_badges` (
   `id` int unsigned NOT NULL auto_increment,
   `entities_id` int unsigned NOT NULL default '0',
   `is_recursive` tinyint NOT NULL default '0',
   `name` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `serial` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `plugin_badges_badgetypes_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_plugin_badges_badgetypes (id)',
   `locations_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_locations (id)',
   `date_affectation` timestamp NULL DEFAULT NULL,
   `date_expiration` timestamp NULL DEFAULT NULL,
   `states_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_states (id)',
   `users_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_users (id)',
   `users_id_tech` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_users (id)',
   `is_helpdesk_visible` int unsigned NOT NULL default '1',
   `date_mod` timestamp NULL DEFAULT NULL,
   `comment` text collate utf8mb4_unicode_ci,
   `notepad` longtext collate utf8mb4_unicode_ci,
   `is_deleted` tinyint NOT NULL default '0',
   `is_bookable` tinyint NOT NULL default '1',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_badges_badgetypes`;
   CREATE TABLE `glpi_plugin_badges_badgetypes` (
   `id` int unsigned NOT NULL auto_increment,
   `entities_id` int unsigned NOT NULL default '0',
   `name` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `comment` text collate utf8mb4_unicode_ci,
   `is_recursive` tinyint NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `name` (`name`),
   KEY `entities_id` (`entities_id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_badges_notificationstates`;
CREATE TABLE `glpi_plugin_badges_notificationstates` (
   `id` int unsigned NOT NULL auto_increment,
   `states_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_states (id)',
   PRIMARY KEY  (`id`),
   KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_badges_configs`;
CREATE TABLE `glpi_plugin_badges_configs` (
   `id` int unsigned NOT NULL auto_increment,
   `delay_expired` varchar(50) collate utf8mb4_unicode_ci NOT NULL default '30',
   `delay_whichexpire` varchar(50) collate utf8mb4_unicode_ci NOT NULL default '30',
   `delay_returnexpire` int unsigned NOT NULL default '0',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_badges_requests`;
CREATE TABLE `glpi_plugin_badges_requests` (
   `id` int unsigned NOT NULL auto_increment,
   `badges_id`  int unsigned NOT NULL default '0',
   `requesters_id`  int unsigned NOT NULL default '0',
   `visitor_firstname` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `visitor_realname` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `visitor_society` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `affectation_date` timestamp NULL DEFAULT NULL,
   `return_date` timestamp NULL DEFAULT NULL,
   `is_affected`  tinyint NOT NULL default '0',
   PRIMARY KEY  (`id`),
        KEY `badges_id` (`badges_id`),
        KEY `requesters_id` (`requesters_id`),
        KEY `is_affected` (`is_affected`),
        KEY `affectation_date` (`affectation_date`),
        KEY `return_date` (`return_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_plugin_badges_configs` VALUES (1, '30', '30', '30');

INSERT INTO `glpi_notificationtemplates` (name, itemtype)
VALUES('Alert Badges', 'GlpiPlugin\\Badges\\Badge');

INSERT INTO `glpi_displaypreferences` VALUES (NULL,'GlpiPlugin\\Badges\\Badge','3','2','0', 'central');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'GlpiPlugin\\Badges\\Badge','4','3','0', 'central');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'GlpiPlugin\\Badges\\Badge','5','4','0', 'central');

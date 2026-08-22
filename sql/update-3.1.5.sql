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

UPDATE `glpi_displaypreferences` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';
UPDATE `glpi_notificationtemplates` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';
UPDATE `glpi_notifications` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';
UPDATE `glpi_impactrelations` SET `itemtype_source` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype_source` = 'PluginBadgesBadge';
UPDATE `glpi_impactrelations` SET `itemtype_impacted` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype_impacted` = 'PluginBadgesBadge';

UPDATE `glpi_documents_items` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';
UPDATE `glpi_savedsearches` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';
UPDATE `glpi_items_tickets` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';
UPDATE `glpi_dropdowntranslations` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';
UPDATE `glpi_savedsearches_users` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';
UPDATE `glpi_notepads` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';

UPDATE `glpi_crontasks` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';
UPDATE `glpi_crontasks` SET `itemtype` = 'GlpiPlugin\\Badges\\BadgeReturn' WHERE `itemtype` = 'PluginBadgesBadgeReturn';

DELETE FROM `glpi_crontasks` WHERE `itemtype` LIKE 'PluginBadges%';

UPDATE `glpi_items_tickets` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';
UPDATE `glpi_items_problems` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';
UPDATE `glpi_documents_items` SET `itemtype` = 'GlpiPlugin\\Badges\\Badge' WHERE `itemtype` = 'PluginBadgesBadge';

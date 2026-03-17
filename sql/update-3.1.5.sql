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

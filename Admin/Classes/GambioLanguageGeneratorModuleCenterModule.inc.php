<?php
/* --------------------------------------------------------------
   GambioLanguageGeneratorModuleCenterModule.inc.php 2024-11-09
   REDOzone
   http://www.redozone.com
   Copyright (c) 2024 REDOzone
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Gambio Language Generator Module für Gambio GX 4.8
 * Generiert automatisch Sprachdateien basierend auf language_phrases_cache
 */
class GambioLanguageGeneratorModuleCenterModule extends AbstractModuleCenterModule
{
    /**
     * Modul-Initialisierung
     */
    protected function _init()
    {
        $this->title = 'Gambio Language Generator';
        $this->description = 'Generiert automatisch Sprachdateien für neue und vorhandene Shop-Sprachen basierend auf der Original-Shopsprache';
        $this->sortOrder = 1000;
    }
    
    /**
     * Installation des Moduls
     */
    public function install()
    {
        parent::install();
        
        // Lizenzschlüssel-Konfiguration erstellen
        $this->_createConfigEntry('GLG_LICENSE_KEY', '', 6, 1);
        
        // Datenbanktabellen erstellen
        $this->_createTables();
    }
    
    /**
     * Deinstallation des Moduls
     */
    public function uninstall()
    {
        // Konfigurationswerte entfernen
        $db = StaticGXCoreLoader::getDatabaseQueryBuilder();
        $db->where('configuration_key', 'GLG_LICENSE_KEY')
           ->delete('configuration');
        
        parent::uninstall();
    }
    
    /**
     * Gibt die URL für den Admin-Menü-Link zurück
     */
    public function getMenuLinkUrl()
    {
        return 'admin.php?do=GambioLanguageGeneratorModuleCenterModule';
    }
    
    /**
     * Modul hat eine Konfigurationsseite
     */
    public function hasConfigurationPage()
    {
        return true;
    }
    
    /**
     * URL zur Konfigurationsseite
     */
    public function getConfigurationPageUrl()
    {
        return 'admin.php?do=GambioLanguageGeneratorModuleCenterModule';
    }
    
    /**
     * Erstellt einen Konfigurationseintrag
     */
    private function _createConfigEntry($key, $defaultValue, $groupId, $sortOrder)
    {
        $db = StaticGXCoreLoader::getDatabaseQueryBuilder();
        
        // Lösche existierende Einträge erst
        $db->where('configuration_key', $key)->delete('configuration');
        
        // Dann neu einfügen
        $db->insert('configuration', array(
            'configuration_key' => $key,
            'configuration_value' => $defaultValue,
            'configuration_group_id' => $groupId,
            'sort_order' => $sortOrder,
            'date_added' => date('Y-m-d H:i:s')
        ));
    }
    
    /**
     * Erstellt die benötigten Datenbanktabellen
     */
    private function _createTables()
    {
        // Settings Tabelle
        $query = "CREATE TABLE IF NOT EXISTS `rz_glg_settings` (
            `setting_key` varchar(100) NOT NULL,
            `setting_value` text NOT NULL,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        xtc_db_query($query);
        
        // Log Tabelle
        $query = "CREATE TABLE IF NOT EXISTS `rz_glg_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `date` datetime DEFAULT CURRENT_TIMESTAMP,
            `action` varchar(50) NOT NULL,
            `source_language` varchar(50) DEFAULT NULL,
            `target_language` varchar(50) DEFAULT NULL,
            `status` enum('success','error','running') DEFAULT 'running',
            `details` text,
            PRIMARY KEY (`id`),
            KEY `date` (`date`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        xtc_db_query($query);
        
        // Update Tracking Tabelle
        $query = "CREATE TABLE IF NOT EXISTS `rz_glg_update_tracking` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `last_update` datetime DEFAULT CURRENT_TIMESTAMP,
            `source_language` varchar(50) NOT NULL,
            `target_language` varchar(50) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `languages` (`source_language`, `target_language`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        xtc_db_query($query);
    }
}

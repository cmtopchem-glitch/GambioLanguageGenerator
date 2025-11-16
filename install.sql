-- Gambio Language Generator - Manuelle Registrierung
-- Dieses Script registriert das Modul in Gambio

-- 1. Prüfe ob Modul bereits existiert
SELECT * FROM gx_modules WHERE name = 'GambioLanguageGenerator';

-- Falls noch nicht vorhanden, füge es hinzu:
INSERT INTO gx_modules (
    name,
    version,
    type,
    source,
    installed,
    priority
) VALUES (
    'GambioLanguageGenerator',
    '1.0.0',
    'module',
    'GXModules/GambioLanguageGenerator',
    NOW(),
    100
);

-- 2. Lizenzschlüssel hinzufügen (falls noch nicht vorhanden)
INSERT INTO configuration (
    configuration_key,
    configuration_value,
    configuration_group_id,
    sort_order,
    date_added
) VALUES (
    'GLG_LICENSE_KEY',
    'DEIN-LIZENZSCHLUESSEL-HIER',
    6,
    1,
    NOW()
) ON DUPLICATE KEY UPDATE 
    configuration_value = 'DEIN-LIZENZSCHLUESSEL-HIER';

-- 3. Erstelle die benötigten Tabellen
CREATE TABLE IF NOT EXISTS `rz_glg_settings` (
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text NOT NULL,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rz_glg_log` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rz_glg_update_tracking` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `last_update` datetime DEFAULT CURRENT_TIMESTAMP,
    `source_language` varchar(50) NOT NULL,
    `target_language` varchar(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `languages` (`source_language`, `target_language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Prüfe Ergebnis
SELECT * FROM gx_modules WHERE name = 'GambioLanguageGenerator';

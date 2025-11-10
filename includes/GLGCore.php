<?php
/**
 * Gambio Language Generator - Core Class
 * 
 * @author Christian Mittenzwei
 * @version 1.0.0
 */

class GLGCore {
    
    private $db;
    private $processFile = DIR_FS_CATALOG . 'cache/glg_process_%s.json';
    private $settingsTable = 'rz_glg_settings';
    private $logTable = 'rz_glg_log';
    
    public function __construct() {
        $this->db = $GLOBALS['db_link'];
        $this->ensureTablesExist();
    }
    
    /**
     * Stellt sicher dass die benötigten Tabellen existieren
     */
    private function ensureTablesExist() {
        // Settings Tabelle
        $query = "CREATE TABLE IF NOT EXISTS `{$this->settingsTable}` (
            `setting_key` varchar(100) NOT NULL,
            `setting_value` text NOT NULL,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        xtc_db_query($query);
        
        // Log Tabelle
        $query = "CREATE TABLE IF NOT EXISTS `{$this->logTable}` (
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
    
    /**
     * Startet den Generierungsprozess
     */
    public function startGeneration($params) {
        $processId = $params['processId'];
        
        // Prozess-Status initialisieren
        $this->updateProcessStatus($processId, [
            'status' => 'running',
            'percent' => 0,
            'statusText' => 'Starte Generierung...',
            'details' => '',
            'params' => $params
        ]);
        
        // Log-Eintrag erstellen
        $this->addLog('generate', $params['sourceLanguage'], implode(',', $params['targetLanguages']), 'running', 'Generierung gestartet');
        
        // Starte eigentlichen Generierungsprozess
        // In Produktivumgebung würde dies asynchron laufen
        try {
            $this->executeGeneration($processId, $params);
        } catch (Exception $e) {
            $this->updateProcessStatus($processId, [
                'status' => 'error',
                'percent' => 0,
                'statusText' => 'Fehler',
                'details' => $e->getMessage()
            ]);
            
            $this->addLog('generate', $params['sourceLanguage'], implode(',', $params['targetLanguages']), 'error', $e->getMessage());
        }
        
        return [
            'success' => true,
            'processId' => $processId,
            'message' => 'Generierung gestartet'
        ];
    }
    
    /**
     * Führt die Generierung aus
     */
    private function executeGeneration($processId, $params) {
        require_once(DIR_FS_CATALOG . 'GXModules/GambioLanguageGenerator/includes/GLGReader.php');
        require_once(DIR_FS_CATALOG . 'GXModules/GambioLanguageGenerator/includes/GLGTranslator.php');
        require_once(DIR_FS_CATALOG . 'GXModules/GambioLanguageGenerator/includes/GLGFileWriter.php');
        
        $sourceLanguage = $params['sourceLanguage'];
        $targetLanguages = $params['targetLanguages'];
        $options = [
            'includeCoreFiles' => $params['includeCoreFiles'],
            'includeGXModules' => $params['includeGXModules'],
            'selectedModules' => $params['selectedModules'] ?? []
        ];
        
        // Initialisiere Klassen
        $reader = new GLGReader();
        $settings = $this->getSettings();
        $translator = new GLGTranslator($settings);
        $writer = new GLGFileWriter($settings['backupEnabled'] ?? true);
        
        // Schritt 1: Quelldaten lesen
        $this->updateProcessStatus($processId, [
            'status' => 'running',
            'percent' => 10,
            'statusText' => 'Lese Quelldaten...',
            'details' => 'Sprache: ' . $sourceLanguage
        ]);
        
        $sourceData = $reader->readLanguageData($sourceLanguage, $options);
        $totalFiles = count($sourceData);
        
        if ($totalFiles === 0) {
            throw new Exception('Keine Quelldaten gefunden für ' . $sourceLanguage);
        }
        
        // Schritt 2: Für jede Zielsprache übersetzen und schreiben
        $fileIndex = 0;
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($targetLanguages as $targetLanguage) {
            foreach ($sourceData as $source => $fileData) {
                $fileIndex++;
                $percent = 10 + (($fileIndex / ($totalFiles * count($targetLanguages))) * 85);
                
                $this->updateProcessStatus($processId, [
                    'status' => 'running',
                    'percent' => round($percent),
                    'statusText' => 'Übersetze nach ' . $targetLanguage . '...',
                    'details' => basename($source) . ' (' . $fileIndex . ' von ' . ($totalFiles * count($targetLanguages)) . ')'
                ]);
                
                try {
                    // Übersetze jede Sektion
                    $translatedSections = [];
                    
                    foreach ($fileData['sections'] as $sectionName => $entries) {
                        // Erstelle optimale Batches für API
                        $batches = $translator->createOptimalBatches($entries);
                        $translatedEntries = [];
                        
                        foreach ($batches as $batch) {
                            $translated = $translator->translateBatch(
                                $batch,
                                $sourceLanguage,
                                $targetLanguage,
                                $source . ' - ' . $sectionName
                            );
                            $translatedEntries = array_merge($translatedEntries, $translated);
                        }
                        
                        $translatedSections[$sectionName] = $translatedEntries;
                    }
                    
                    // Schreibe Datei
                    $result = $writer->writeSourceFile([
                        'source' => $source,
                        'sections' => $translatedSections
                    ], $targetLanguage);
                    
                    $successCount++;
                    
                } catch (Exception $e) {
                    $errorCount++;
                    $this->addLog('generate', $sourceLanguage, $targetLanguage, 'error', 
                                  'Fehler bei ' . $source . ': ' . $e->getMessage());
                }
                
                // Kurze Pause zwischen Dateien
                usleep(100000); // 100ms
            }
        }
        
        // Schritt 3: Update-Tracking aktualisieren
        $this->updateProcessStatus($processId, [
            'status' => 'running',
            'percent' => 95,
            'statusText' => 'Finalisiere...',
            'details' => 'Aktualisiere Tracking'
        ]);
        
        foreach ($targetLanguages as $targetLanguage) {
            $this->updateTrackingDate($sourceLanguage, $targetLanguage);
        }
        
        // Fertig
        $this->updateProcessStatus($processId, [
            'status' => 'complete',
            'percent' => 100,
            'statusText' => 'Abgeschlossen',
            'details' => '',
            'message' => "Erfolgreich: $successCount Dateien, Fehler: $errorCount"
        ]);
        
        $this->addLog('generate', $sourceLanguage, implode(',', $targetLanguages), 'success', 
                      "Generierung abgeschlossen. Erfolgreich: $successCount, Fehler: $errorCount");
    }
    
    /**
     * Aktualisiert das Tracking-Datum
     */
    private function updateTrackingDate($sourceLanguage, $targetLanguage) {
        $source = xtc_db_input($sourceLanguage);
        $target = xtc_db_input($targetLanguage);
        
        $query = "INSERT INTO rz_glg_update_tracking 
                  (source_language, target_language, last_update)
                  VALUES ('$source', '$target', NOW())
                  ON DUPLICATE KEY UPDATE last_update = NOW()";
        
        xtc_db_query($query);
    }
    
    /**
     * Startet den Update-Prozess
     */
    public function startUpdate($params) {
        $processId = $params['processId'];
        
        // Prozess-Status initialisieren
        $this->updateProcessStatus($processId, [
            'status' => 'running',
            'percent' => 0,
            'statusText' => 'Starte Aktualisierung...',
            'details' => ''
        ]);
        
        $this->addLog('update', null, null, 'running', 'Aktualisierung gestartet');
        
        return [
            'success' => true,
            'processId' => $processId,
            'message' => 'Aktualisierung gestartet'
        ];
    }
    
    /**
     * Gibt den Fortschritt eines Prozesses zurück
     */
    public function getProgress($processId) {
        $filename = sprintf($this->processFile, $processId);
        
        if (!file_exists($filename)) {
            return [
                'status' => 'unknown',
                'percent' => 0,
                'statusText' => 'Prozess nicht gefunden',
                'details' => ''
            ];
        }
        
        $content = file_get_contents($filename);
        return json_decode($content, true);
    }
    
    /**
     * Bricht einen Prozess ab
     */
    public function cancelProcess($processId) {
        $filename = sprintf($this->processFile, $processId);
        
        if (file_exists($filename)) {
            unlink($filename);
        }
        
        if (isset($_SESSION['glg_process_' . $processId])) {
            unset($_SESSION['glg_process_' . $processId]);
        }
        
        return [
            'success' => true,
            'message' => 'Prozess abgebrochen'
        ];
    }
    
    /**
     * Aktualisiert den Prozess-Status
     */
    private function updateProcessStatus($processId, $data) {
        $filename = sprintf($this->processFile, $processId);
        file_put_contents($filename, json_encode($data));
    }
    
    /**
     * Gibt alle verfügbaren GXModules zurück
     */
    public function getAvailableModules() {
        $modules = [];
        $modulesPath = DIR_FS_CATALOG . 'GXModules/';
        
        if (!is_dir($modulesPath)) {
            return $modules;
        }
        
        $dirs = scandir($modulesPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $modulePath = $modulesPath . $dir;
            if (!is_dir($modulePath)) {
                continue;
            }
            
            // Prüfe ob Sprachdateien existieren
            $langPath = $modulePath . '/lang/';
            if (!is_dir($langPath)) {
                continue;
            }
            
            $modules[] = [
                'name' => $dir,
                'title' => $this->getModuleTitle($modulePath),
                'path' => $modulePath
            ];
        }
        
        return $modules;
    }
    
    /**
     * Liest den Modul-Titel aus der module.info
     */
    private function getModuleTitle($modulePath) {
        $infoFile = $modulePath . '/module.info';
        if (!file_exists($infoFile)) {
            return '';
        }
        
        $xml = simplexml_load_file($infoFile);
        if ($xml && isset($xml->title->de)) {
            return (string)$xml->title->de;
        }
        
        return '';
    }
    
    /**
     * Speichert Einstellungen
     */
    public function saveSettings($settings) {
        foreach ($settings as $key => $value) {
            $key = xtc_db_input($key);
            $value = xtc_db_input($value);
            
            $query = "INSERT INTO `{$this->settingsTable}` 
                      (`setting_key`, `setting_value`) 
                      VALUES ('$key', '$value')
                      ON DUPLICATE KEY UPDATE `setting_value` = '$value'";
            
            xtc_db_query($query);
        }
        
        return [
            'success' => true,
            'message' => 'Einstellungen gespeichert'
        ];
    }
    
    /**
     * Lädt Einstellungen
     */
    public function getSettings() {
        $settings = [];
        
        $query = "SELECT * FROM `{$this->settingsTable}`";
        $result = xtc_db_query($query);
        
        while ($row = xtc_db_fetch_array($result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Defaults setzen wenn nicht vorhanden
        $defaults = [
            'apiProvider' => 'openai',
            'model' => 'gpt-4o',
            'temperature' => '0.3',
            'maxTokens' => '4000',
            'backupEnabled' => '1'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }
        
        return $settings;
    }
    
    /**
     * Testet die API-Verbindung
     */
    public function testApi($provider, $apiKey) {
        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'API-Schlüssel fehlt'
            ];
        }
        
        switch ($provider) {
            case 'openai':
                return $this->testOpenAI($apiKey);
            case 'deepl':
                return $this->testDeepL($apiKey);
            case 'google':
                return $this->testGoogleTranslate($apiKey);
            default:
                return [
                    'success' => false,
                    'message' => 'Unbekannter Provider'
                ];
        }
    }
    
    /**
     * Testet OpenAI API
     */
    private function testOpenAI($apiKey) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'user', 'content' => 'Say "API Test successful"']
            ],
            'max_tokens' => 20
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return [
                'success' => true,
                'message' => 'OpenAI API funktioniert'
            ];
        } else {
            $error = json_decode($response, true);
            return [
                'success' => false,
                'message' => 'API-Fehler: ' . ($error['error']['message'] ?? 'Unbekannter Fehler')
            ];
        }
    }
    
    /**
     * Testet DeepL API
     */
    private function testDeepL($apiKey) {
        // TODO: DeepL API Test implementieren
        return [
            'success' => true,
            'message' => 'DeepL Test (TODO)'
        ];
    }
    
    /**
     * Testet Google Translate API
     */
    private function testGoogleTranslate($apiKey) {
        // TODO: Google Translate API Test implementieren
        return [
            'success' => true,
            'message' => 'Google Translate Test (TODO)'
        ];
    }
    
    /**
     * Gibt das Datum der letzten Aktualisierung zurück
     */
    public function getLastUpdateDate() {
        $query = "SELECT MAX(last_update) as last_update FROM rz_glg_update_tracking";
        $result = xtc_db_query($query);
        
        if ($row = xtc_db_fetch_array($result)) {
            return $row['last_update'] ?: 'Noch nie';
        }
        
        return 'Noch nie';
    }
    
    /**
     * Gibt Änderungen seit einem Datum zurück
     */
    public function getChanges($since = null) {
        if (!$since) {
            return [];
        }
        
        $since = xtc_db_input($since);
        
        $query = "SELECT DISTINCT source, section_name, phrase_name
                  FROM language_phrases_cache
                  WHERE date_modified > '$since'
                  ORDER BY date_modified DESC
                  LIMIT 100";

        $result = xtc_db_query($query);
        $changes = [];

        while ($row = xtc_db_fetch_array($result)) {
            $changes[] = [
                'source' => $row['source'],
                'section' => $row['section_name'],
                'key' => $row['phrase_name']
            ];
        }
        
        return $changes;
    }
    
    /**
     * Fügt einen Log-Eintrag hinzu
     */
    public function addLog($action, $source, $target, $status, $details = '') {
        $action = xtc_db_input($action);
        $source = xtc_db_input($source);
        $target = xtc_db_input($target);
        $status = xtc_db_input($status);
        $details = xtc_db_input($details);
        
        $query = "INSERT INTO `{$this->logTable}` 
                  (`action`, `source_language`, `target_language`, `status`, `details`)
                  VALUES ('$action', '$source', '$target', '$status', '$details')";
        
        xtc_db_query($query);
    }
    
    /**
     * Gibt das Protokoll zurück
     */
    public function getLog($limit = 50) {
        $limit = intval($limit);
        
        $query = "SELECT * FROM `{$this->logTable}` 
                  ORDER BY date DESC 
                  LIMIT $limit";
        
        $result = xtc_db_query($query);
        $log = [];
        
        while ($row = xtc_db_fetch_array($result)) {
            $log[] = [
                'date' => date('d.m.Y H:i:s', strtotime($row['date'])),
                'action' => $row['action'],
                'source' => $row['source_language'] ?: '-',
                'target' => $row['target_language'] ?: '-',
                'status' => $row['status'],
                'details' => $row['details']
            ];
        }
        
        return $log;
    }
}

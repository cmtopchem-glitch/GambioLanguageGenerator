<?php
if (!class_exists('GambioLanguageGeneratorModuleCenterModuleController')) {
class GambioLanguageGeneratorModuleCenterModuleController extends AbstractModuleCenterModuleController
{
    protected function _init()
    {
    }

    public function actionDefault()
    {
        // Debug: Zeige welche Action aufgerufen wird
        $action = $this->_getQueryParameter('action');
        error_log('GLG: actionDefault() called, action parameter: ' . ($action ? $action : 'none'));

        // Handle sub-actions manually
        if ($action === 'save') {
            error_log('GLG: Routing to actionSave()');
            return $this->actionSave();
        }
        if ($action === 'generate') {
            error_log('GLG: Routing to actionGenerate()');
            return $this->actionGenerate();
        }
        if ($action === 'compare') {
            error_log('GLG: Routing to actionCompare()');
            return $this->actionCompare();
        }

        $this->pageTitle = 'Gambio Language Generator';

        // Stelle sicher, dass die Settings-Tabelle existiert
        $this->_ensureTablesExist();

        // Hole verfügbare Sprachen
        $languages = array();
        $query = "SELECT * FROM languages ORDER BY sort_order";
        $result = xtc_db_query($query);
        while ($lang = xtc_db_fetch_array($result)) {
            $languages[] = $lang;
        }

        // Lade gespeicherte Einstellungen
        $apiProvider = 'openai';
        $apiKey = '';
        $model = 'gpt-4o';

        // Prüfe ob Tabelle existiert und lade Settings
        $tableCheck = xtc_db_query("SHOW TABLES LIKE 'rz_glg_settings'");
        if (xtc_db_num_rows($tableCheck) > 0) {
            $query = "SELECT setting_key, setting_value FROM rz_glg_settings WHERE setting_key IN ('apiProvider', 'apiKey', 'model')";
            $result = xtc_db_query($query);

            while ($row = xtc_db_fetch_array($result)) {
                if ($row['setting_key'] == 'apiProvider') {
                    $apiProvider = $row['setting_value'];
                }
                if ($row['setting_key'] == 'apiKey') {
                    $apiKey = $row['setting_value'];
                }
                if ($row['setting_key'] == 'model') {
                    $model = $row['setting_value'];
                }
            }
        }

        $success = $this->_getQueryParameter('success') == '1';
        $error = $this->_getQueryParameter('error') == '1';

        // Template laden und Variablen zuweisen (wie AIProductOptimizer)
        $smarty = new Smarty();
        $smarty->template_dir = DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/Admin/Templates/';
        $smarty->compile_dir = DIR_FS_CATALOG . 'cache/smarty/';

        $smarty->assign('pageTitle', $this->pageTitle);
        $smarty->assign('languages', $languages);
        $smarty->assign('apiProvider', $apiProvider);
        $smarty->assign('apiKey', $apiKey);
        $smarty->assign('model', $model);
        $smarty->assign('success', $success);
        $smarty->assign('error', $error);

        $html = $smarty->fetch('module_content.html');

        // Return AdminPageHttpControllerResponse for proper ModuleCenter integration
        return new AdminPageHttpControllerResponse($this->pageTitle, $html);
    }


    public function actionSave()
    {
        error_log('GLG: actionSave() called');

        // Stelle sicher, dass die Tabelle existiert
        $this->_ensureTablesExist();

        $apiProvider = $this->_getPostData('apiProvider');
        $apiKey = $this->_getPostData('apiKey');
        $model = $this->_getPostData('model');

        error_log('GLG: Received data - Provider: ' . $apiProvider . ', Model: ' . $model . ', API Key length: ' . strlen($apiKey));

        if (empty($apiKey)) {
            error_log('GLG: API Key is empty, redirecting to error');
            header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule&error=1');
            exit;
        }

        try {
            error_log('GLG: Starting to save settings...');
            $this->_saveSetting('apiProvider', $apiProvider);
            $this->_saveSetting('apiKey', $apiKey);
            $this->_saveSetting('model', $model);

            error_log('GLG: All settings saved successfully, redirecting to success');
            header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule&success=1');
            exit;
        } catch (Exception $e) {
            error_log('GLG Save Error: ' . $e->getMessage());
            header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule&error=1');
            exit;
        }
    }

    private function _saveSetting($key, $value)
    {
        $key = xtc_db_input($key);
        $value = xtc_db_input($value);

        $query = "SELECT setting_key FROM rz_glg_settings WHERE setting_key = '$key'";
        $result = xtc_db_query($query);

        if (xtc_db_num_rows($result) > 0) {
            $query = "UPDATE rz_glg_settings SET setting_value = '$value', updated_at = NOW() WHERE setting_key = '$key'";
            error_log("GLG: Updating setting $key");
        } else {
            $query = "INSERT INTO rz_glg_settings (setting_key, setting_value) VALUES ('$key', '$value')";
            error_log("GLG: Inserting setting $key");
        }

        $success = xtc_db_query($query);
        if (!$success) {
            error_log("GLG: Failed to save setting $key");
            throw new Exception("Failed to save setting: $key");
        }

        error_log("GLG: Successfully saved setting $key = " . substr($value, 0, 10) . "...");
    }

    public function actionGenerate()
    {
        error_log('GLG: actionGenerate() called');

        // Initialisiere Session-Status
        $_SESSION['glg_progress'] = [
            'status' => 'starting',
            'current_file' => '',
            'current_language' => '',
            'files_processed' => 0,
            'total_files' => 0,
            'languages_completed' => 0,
            'total_languages' => 0,
            'message' => 'Starte Übersetzung...'
        ];

        $sourceLanguage = $this->_getPostData('sourceLanguage');
        $targetLanguages = $this->_getPostData('targetLanguages');

        error_log('GLG: Source: ' . $sourceLanguage . ', Targets: ' . print_r($targetLanguages, true));

        // Validierung
        if (empty($sourceLanguage)) {
            $this->_jsonResponse(['success' => false, 'error' => 'Keine Quellsprache ausgewählt']);
            return;
        }

        if (empty($targetLanguages) || !is_array($targetLanguages)) {
            $this->_jsonResponse(['success' => false, 'error' => 'Keine Zielsprachen ausgewählt']);
            return;
        }

        // Prüfe ob Quellsprache in Zielsprachen enthalten ist
        if (in_array($sourceLanguage, $targetLanguages)) {
            $this->_jsonResponse(['success' => false, 'error' => 'Quellsprache kann nicht als Zielsprache ausgewählt werden']);
            return;
        }

        // Lade Einstellungen
        $settings = $this->_loadSettings();

        if (empty($settings['apiKey'])) {
            $this->_jsonResponse(['success' => false, 'error' => 'API Key nicht konfiguriert. Bitte in den Einstellungen hinterlegen.']);
            return;
        }

        try {
            // Erhöhe Timeout für lange Übersetzungen
            set_time_limit(3600); // 1 Stunde
            ini_set('memory_limit', '512M');

            // Lade Helper-Klassen (relative Pfade vom Controller aus)
            require_once(__DIR__ . '/../../../includes/GLGReader.php');
            require_once(__DIR__ . '/../../../includes/GLGTranslator.php');
            require_once(__DIR__ . '/../../../includes/GLGFileWriter.php');

            $reader = new GLGReader();
            $translator = new GLGTranslator($settings);
            $writer = new GLGFileWriter();

            // Lese Quellsprache (gruppiert nach Dateien)
            error_log('GLG: Reading source language data...');
            $_SESSION['glg_progress']['message'] = 'Lese Quelldateien...';
            $_SESSION['glg_progress']['status'] = 'reading';

            $sourceFiles = $reader->readLanguageData($sourceLanguage);

            if (empty($sourceFiles)) {
                $_SESSION['glg_progress']['status'] = 'error';
                $_SESSION['glg_progress']['message'] = 'Keine Sprachdateien gefunden';
                $this->_jsonResponse(['success' => false, 'error' => 'Keine Sprachdateien in Quellsprache gefunden']);
                return;
            }

            error_log('GLG: Found ' . count($sourceFiles) . ' source files');

            $_SESSION['glg_progress']['total_files'] = count($sourceFiles);
            $_SESSION['glg_progress']['total_languages'] = count($targetLanguages);
            $_SESSION['glg_progress']['status'] = 'translating';

            $results = [];
            $totalEntriesProcessed = 0;
            $errors = [];

            // Übersetze in jede Zielsprache
            foreach ($targetLanguages as $langIndex => $targetLanguage) {
                $_SESSION['glg_progress']['current_language'] = $targetLanguage;
                $_SESSION['glg_progress']['languages_completed'] = $langIndex;
                $_SESSION['glg_progress']['files_processed'] = 0;
                error_log('GLG: Translating to ' . $targetLanguage);
                $filesWritten = 0;
                $totalEntries = 0;
                $fileErrors = 0;

                // Verarbeite jede Source-Datei einzeln
                $fileIndex = 0;
                foreach ($sourceFiles as $sourceFile => $sourceData) {
                    $fileIndex++;
                    try {
                        $_SESSION['glg_progress']['files_processed'] = $fileIndex;
                        $_SESSION['glg_progress']['current_file'] = $sourceFile;
                        $_SESSION['glg_progress']['message'] = "Übersetze $sourceFile nach $targetLanguage...";

                        error_log('GLG: Processing file: ' . $sourceFile);

                        // Flatten sections into single array for translation
                        $flatEntries = [];
                        foreach ($sourceData['sections'] as $sectionName => $entries) {
                            foreach ($entries as $key => $value) {
                                $flatKey = $sectionName . '::' . $key;
                                $flatEntries[$flatKey] = $value;
                            }
                        }

                        if (empty($flatEntries)) {
                            error_log('GLG: Skipping empty file: ' . $sourceFile);
                            continue;
                        }

                        // Translate in batches
                        $batchSize = 50;
                        $chunks = array_chunk($flatEntries, $batchSize, true);
                        $translatedFlat = [];

                        foreach ($chunks as $index => $chunk) {
                            try {
                                error_log('GLG: Translating batch ' . ($index + 1) . '/' . count($chunks) . ' of ' . $sourceFile);
                                $translated = $translator->translateBatch($chunk, $sourceLanguage, $targetLanguage, 'E-Commerce: ' . $sourceFile);
                                $translatedFlat = array_merge($translatedFlat, $translated);
                            } catch (Exception $e) {
                                error_log('GLG: Error translating batch ' . ($index + 1) . ' of ' . $sourceFile . ': ' . $e->getMessage());
                                $errors[] = 'Batch error in ' . $sourceFile . ': ' . $e->getMessage();
                                // Continue with next batch
                            }
                        }

                        if (empty($translatedFlat)) {
                            error_log('GLG: No translations received for: ' . $sourceFile);
                            continue;
                        }

                        // Reconstruct section structure
                        $translatedSections = [];
                        foreach ($translatedFlat as $flatKey => $translatedValue) {
                            $parts = explode('::', $flatKey, 2);
                            if (count($parts) === 2) {
                                list($sectionName, $key) = $parts;
                                if (!isset($translatedSections[$sectionName])) {
                                    $translatedSections[$sectionName] = [];
                                }
                                $translatedSections[$sectionName][$key] = $translatedValue;
                            }
                        }

                        // Write file
                        $writeData = [
                            'source' => $sourceFile,
                            'sections' => $translatedSections
                        ];

                        $writeResult = $writer->writeSourceFile($writeData, $targetLanguage);
                        if ($writeResult['success']) {
                            $filesWritten++;
                            error_log('GLG: Written: ' . $writeResult['file']);
                        } else {
                            error_log('GLG: Failed to write: ' . $sourceFile);
                            $errors[] = 'Failed to write: ' . $sourceFile;
                        }
                        $totalEntries += count($translatedFlat);

                    } catch (Exception $e) {
                        error_log('GLG: Error processing file ' . $sourceFile . ': ' . $e->getMessage());
                        $errors[] = 'File error: ' . $sourceFile . ' - ' . $e->getMessage();
                        $fileErrors++;
                        // Continue with next file
                    }
                }

                $results[] = [
                    'language' => $targetLanguage,
                    'files' => $filesWritten,
                    'entries' => $totalEntries,
                    'errors' => $fileErrors
                ];

                error_log('GLG: Completed ' . $targetLanguage . ': ' . $filesWritten . ' files, ' . $totalEntries . ' entries, ' . $fileErrors . ' errors');
            }

            // Log Erfolg oder teilweiser Erfolg
            $totalProcessed = array_sum(array_column($results, 'entries'));
            $totalErrors = count($errors);

            $_SESSION['glg_progress']['status'] = 'completed';
            $_SESSION['glg_progress']['message'] = 'Übersetzung abgeschlossen!';
            $_SESSION['glg_progress']['files_processed'] = $_SESSION['glg_progress']['total_files'];
            $_SESSION['glg_progress']['languages_completed'] = $_SESSION['glg_progress']['total_languages'];

            if ($totalErrors > 0) {
                $this->_logAction('generate', $sourceLanguage, implode(',', $targetLanguages), 'success',
                    $totalProcessed . ' Einträge übersetzt, ' . $totalErrors . ' Fehler');
                error_log('GLG: Generation completed with ' . $totalErrors . ' errors');
                $this->_jsonResponse([
                    'success' => true,
                    'message' => 'Übersetzung abgeschlossen mit ' . $totalErrors . ' Fehlern',
                    'results' => $results,
                    'errors' => array_slice($errors, 0, 10) // Nur erste 10 Fehler anzeigen
                ]);
            } else {
                $this->_logAction('generate', $sourceLanguage, implode(',', $targetLanguages), 'success',
                    $totalProcessed . ' Einträge in ' . count($sourceFiles) . ' Dateien übersetzt');
                error_log('GLG: Generation completed successfully');
                $this->_jsonResponse([
                    'success' => true,
                    'message' => 'Übersetzung erfolgreich abgeschlossen',
                    'results' => $results
                ]);
            }

        } catch (Exception $e) {
            error_log('GLG Generate Error: ' . $e->getMessage());
            $_SESSION['glg_progress']['status'] = 'error';
            $_SESSION['glg_progress']['message'] = 'Fehler: ' . $e->getMessage();
            $this->_logAction('generate', $sourceLanguage, implode(',', $targetLanguages), 'error', $e->getMessage());
            $this->_jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function actionGetProgress()
    {
        // Gibt den aktuellen Fortschritt zurück (für AJAX Polling)
        $progress = $_SESSION['glg_progress'] ?? [
            'status' => 'idle',
            'message' => 'Keine laufende Übersetzung',
            'files_processed' => 0,
            'total_files' => 0,
            'languages_completed' => 0,
            'total_languages' => 0,
            'current_file' => '',
            'current_language' => ''
        ];

        $this->_jsonResponse($progress);
    }

    public function actionCompare()
    {
        error_log('GLG: actionCompare() called');

        $sourceLanguage = $this->_getPostData('sourceLanguage');
        $targetLanguage = $this->_getPostData('targetLanguage');

        error_log('GLG: Source: ' . $sourceLanguage . ', Target: ' . $targetLanguage);

        // Check if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        // Validierung
        if (empty($sourceLanguage)) {
            if ($isAjax) {
                $this->_jsonResponse(['success' => false, 'error' => 'Keine Quellsprache ausgewählt']);
                return;
            } else {
                header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule&error=1');
                exit;
            }
        }

        if (empty($targetLanguage)) {
            if ($isAjax) {
                $this->_jsonResponse(['success' => false, 'error' => 'Keine Zielsprache ausgewählt']);
                return;
            } else {
                header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule&error=1');
                exit;
            }
        }

        // Prüfe ob Quellsprache == Zielsprache
        if ($sourceLanguage === $targetLanguage) {
            if ($isAjax) {
                $this->_jsonResponse(['success' => false, 'error' => 'Quell- und Zielsprache dürfen nicht identisch sein']);
                return;
            } else {
                header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule&error=1');
                exit;
            }
        }

        try {
            // Lade Helper-Klassen (relative Pfade vom Controller aus)
            require_once(__DIR__ . '/../../../includes/GLGCompare.php');

            $comparer = new GLGCompare();

            // Vergleiche beide Sprachen
            error_log('GLG: Comparing languages...');
            $comparison = $comparer->compareLanguages($sourceLanguage, $targetLanguage);

            $sourceCount = $comparison['total_source_entries'];
            $targetCount = $comparison['total_target_entries'];
            $missingCount = $comparison['missing_entries'];

            $completion = $sourceCount > 0
                ? round((($sourceCount - $missingCount) / $sourceCount) * 100, 1)
                : 100;

            error_log('GLG: Comparison completed. Missing: ' . $missingCount);

            // Extrahiere nur die Keys für die Anzeige
            $missingKeys = array_map(function($item) {
                return $item['file'] . ' > ' . $item['section'] . ' > ' . $item['key'];
            }, $comparison['missing_keys']);

            // Always return JSON (the form uses AJAX)
            $this->_jsonResponse([
                'success' => true,
                'sourceCount' => $sourceCount,
                'targetCount' => $targetCount,
                'missingCount' => $missingCount,
                'completion' => $completion,
                'missing' => array_slice($missingKeys, 0, 100) // Nur erste 100 zeigen
            ]);

        } catch (Exception $e) {
            error_log('GLG Compare Error: ' . $e->getMessage());
            if ($isAjax) {
                $this->_jsonResponse(['success' => false, 'error' => $e->getMessage()]);
            } else {
                header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule&error=1');
                exit;
            }
        }
    }

    private function _loadSettings()
    {
        $settings = [
            'apiProvider' => 'openai',
            'apiKey' => '',
            'model' => 'gpt-4o',
            'temperature' => 0.3,
            'maxTokens' => 4000
        ];

        $query = "SELECT setting_key, setting_value FROM rz_glg_settings";
        $result = xtc_db_query($query);

        while ($row = xtc_db_fetch_array($result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    private function _logAction($action, $sourceLanguage, $targetLanguage, $status, $details = '')
    {
        $query = "INSERT INTO rz_glg_log (action, source_language, target_language, status, details)
                  VALUES ('" . xtc_db_input($action) . "',
                          '" . xtc_db_input($sourceLanguage) . "',
                          '" . xtc_db_input($targetLanguage) . "',
                          '" . xtc_db_input($status) . "',
                          '" . xtc_db_input($details) . "')";
        xtc_db_query($query);
    }

    private function _jsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function _ensureTablesExist()
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
}
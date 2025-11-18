<?php
/**
 * Gambio Language Generator - AJAX Controller
 * 
 * @author Christian Mittenzwei
 * @version 1.0.0
 */

// Sicherheitscheck
define('_VALID_XTC', true);

require_once('../../includes/application_top.php');
require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGCore.php');
require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGLicense.php');

header('Content-Type: application/json');

// Lizenzprüfung (temporär deaktiviert für Entwicklung/Testing)
// $license = new GLGLicense();
// if (!$license->isValid()) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Lizenz ungültig oder abgelaufen'
//     ]);
//     exit;
// }

// Action auslesen
$action = isset($_POST['action']) ? $_POST['action'] : '';

$glgCore = new GLGCore();

switch ($action) {
    
    /**
     * Sprachen generieren
     */
    case 'generate':
        $sourceLanguage = $_POST['sourceLanguage'] ?? '';
        $targetLanguages = $_POST['targetLanguages'] ?? [];
        $includeCoreFiles = isset($_POST['includeCoreFiles']) && $_POST['includeCoreFiles'] === 'true';
        $includeGXModules = isset($_POST['includeGXModules']) && $_POST['includeGXModules'] === 'true';
        $selectedModules = $_POST['selectedModules'] ?? [];

        if (empty($sourceLanguage) || empty($targetLanguages)) {
            echo json_encode([
                'success' => false,
                'message' => 'Quell- und Zielsprache erforderlich'
            ]);
            exit;
        }

        $processId = uniqid('glg_', true);
        
        // Prozess im Hintergrund starten
        $result = $glgCore->startGeneration([
            'processId' => $processId,
            'sourceLanguage' => $sourceLanguage,
            'targetLanguages' => $targetLanguages,
            'includeCoreFiles' => $includeCoreFiles,
            'includeGXModules' => $includeGXModules,
            'selectedModules' => $selectedModules
        ]);

        echo json_encode($result);
        break;

    /**
     * Sprachen aktualisieren
     */
    case 'update':
        $processId = uniqid('glg_update_', true);
        
        $result = $glgCore->startUpdate([
            'processId' => $processId
        ]);

        echo json_encode($result);
        break;

    /**
     * Fortschritt abfragen
     */
    case 'getProgress':
        $processId = $_POST['processId'] ?? '';
        $progress = $glgCore->getProgress($processId);
        
        echo json_encode([
            'success' => true,
            'progress' => $progress
        ]);
        break;

    /**
     * Prozess abbrechen
     */
    case 'cancel':
        $processId = $_POST['processId'] ?? '';
        $result = $glgCore->cancelProcess($processId);
        
        echo json_encode($result);
        break;

    /**
     * Module-Liste abrufen
     */
    case 'getModules':
        $modules = $glgCore->getAvailableModules();
        
        echo json_encode([
            'success' => true,
            'modules' => $modules
        ]);
        break;

    /**
     * Einstellungen speichern
     */
    case 'saveSettings':
        $settings = [
            'apiProvider' => $_POST['apiProvider'] ?? 'openai',
            'apiKey' => $_POST['apiKey'] ?? '',
            'model' => $_POST['model'] ?? 'gpt-4o',
            'temperature' => floatval($_POST['temperature'] ?? 0.3),
            'maxTokens' => intval($_POST['maxTokens'] ?? 4000),
            'backupEnabled' => isset($_POST['backupEnabled']) ? 1 : 0
        ];

        $result = $glgCore->saveSettings($settings);
        
        echo json_encode($result);
        break;

    /**
     * Einstellungen laden
     */
    case 'getSettings':
        $settings = $glgCore->getSettings();
        
        echo json_encode([
            'success' => true,
            'settings' => $settings
        ]);
        break;

    /**
     * API testen
     */
    case 'testApi':
        $apiProvider = $_POST['apiProvider'] ?? 'openai';
        $apiKey = $_POST['apiKey'] ?? '';

        $result = $glgCore->testApi($apiProvider, $apiKey);
        
        echo json_encode($result);
        break;

    /**
     * Letztes Update-Datum abrufen
     */
    case 'getLastUpdate':
        $lastUpdate = $glgCore->getLastUpdateDate();
        
        echo json_encode([
            'success' => true,
            'lastUpdate' => $lastUpdate
        ]);
        break;

    /**
     * Änderungen seit letztem Update abrufen
     */
    case 'getChanges':
        $since = $_POST['since'] ?? null;
        $changes = $glgCore->getChanges($since);
        
        echo json_encode([
            'success' => true,
            'count' => count($changes),
            'changes' => $changes
        ]);
        break;

    /**
     * Protokoll abrufen
     */
    case 'getLog':
        $log = $glgCore->getLog();
        
        echo json_encode([
            'success' => true,
            'log' => $log
        ]);
        break;

    /**
     * Sprache anlegen
     */
    case 'createLanguage':
        require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGLanguageManager.php');
        
        $languageManager = new GLGLanguageManager();
        $languageData = [
            'name' => $_POST['name'] ?? '',
            'code' => $_POST['code'] ?? '',
            'directory' => $_POST['directory'] ?? '',
            'country_code' => $_POST['country_code'] ?? ''
        ];
        
        $result = $languageManager->createLanguage($languageData);
        echo json_encode($result);
        break;

    /**
     * Sprachvorschläge abrufen
     */
    case 'getLanguageSuggestions':
        require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGLanguageManager.php');
        
        $languageManager = new GLGLanguageManager();
        $suggestions = $languageManager->getLanguageSuggestions();
        
        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions
        ]);
        break;

    /**
     * Sprachen vergleichen (Testlauf)
     */
    case 'compareLanguages':
        require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGCompare.php');
        
        $sourceLanguage = $_POST['sourceLanguage'] ?? '';
        $targetLanguage = $_POST['targetLanguage'] ?? '';
        $options = [
            'includeCoreFiles' => isset($_POST['includeCoreFiles']) && $_POST['includeCoreFiles'] === 'true',
            'includeGXModules' => isset($_POST['includeGXModules']) && $_POST['includeGXModules'] === 'true',
            'selectedModules' => $_POST['selectedModules'] ?? []
        ];
        
        $compare = new GLGCompare();
        $comparison = $compare->compareLanguages($sourceLanguage, $targetLanguage, $options);
        
        echo json_encode([
            'success' => true,
            'comparison' => $comparison
        ]);
        break;

    /**
     * Vergleichs-Report als HTML
     */
    case 'getComparisonReport':
        require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGCompare.php');
        
        $sourceLanguage = $_POST['sourceLanguage'] ?? '';
        $targetLanguage = $_POST['targetLanguage'] ?? '';
        $options = [
            'includeCoreFiles' => isset($_POST['includeCoreFiles']) && $_POST['includeCoreFiles'] === 'true',
            'includeGXModules' => isset($_POST['includeGXModules']) && $_POST['includeGXModules'] === 'true',
            'selectedModules' => $_POST['selectedModules'] ?? []
        ];
        
        $compare = new GLGCompare();
        $comparison = $compare->compareLanguages($sourceLanguage, $targetLanguage, $options);
        $html = $compare->createHtmlReport($comparison);
        
        // Speichere Report
        $reportFile = DIR_FS_CATALOG . 'cache/glg_report_' . time() . '.html';
        file_put_contents($reportFile, $html);
        
        echo json_encode([
            'success' => true,
            'report_url' => str_replace(DIR_FS_CATALOG, '', $reportFile)
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Unbekannte Aktion: ' . $action
        ]);
        break;
}

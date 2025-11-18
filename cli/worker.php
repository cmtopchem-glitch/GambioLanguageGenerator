<?php
/**
 * Gambio Language Generator - Background Worker
 *
 * Verarbeitet Jobs aus der rz_glg_jobs Queue asynchron
 *
 * Nutzung: php /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator/cli/worker.php
 *
 * @author Christian Mittenzwei
 * @version 1.0.0
 */

// Konfiguration
define('SCRIPT_DIR', dirname(__FILE__));
define('PROJECT_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
define('DIR_FS_CATALOG', PROJECT_ROOT . '/');

// Setze HTTP_HOST für CLI-Ausführung (verhindert Warnings)
if (php_sapi_name() === 'cli' || php_sapi_name() === 'cli-server') {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_URI'] = '/admin/admin.php';
    $_SERVER['REQUEST_METHOD'] = 'POST';
}

// Ladedatei-Bootloader
echo "[DEBUG] Loading application_top.php...\n";
require_once(DIR_FS_CATALOG . 'includes/application_top.php');
echo "[DEBUG] application_top.php loaded\n";

// Lade GLGCore
echo "[DEBUG] Loading GLG classes...\n";
require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGCore.php');
require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGReader.php');
require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGTranslator.php');
require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGFileWriter.php');
echo "[DEBUG] GLG classes loaded\n";

error_log("[GLG Worker] Started at " . date('Y-m-d H:i:s'));
echo "[DEBUG] Worker started at " . date('Y-m-d H:i:s') . "\n";

echo "[DEBUG] Initializing GLGCore...\n";
$glgCore = new GLGCore();
echo "[DEBUG] GLGCore initialized\n";

echo "[DEBUG] Loading settings...\n";
$settings = $glgCore->getSettings();
echo "[DEBUG] Settings loaded\n";

// Worker Loop - Läuft bis kein Job mehr verfügbar ist
$jobCount = 0;
$maxJobsPerRun = isset($argv[1]) ? intval($argv[1]) : 5; // Max 5 Jobs pro Worker-Lauf

while ($jobCount < $maxJobsPerRun) {
    // Hole nächsten verfügbaren Job
    $job = $glgCore->getNextJob();

    if (!$job) {
        error_log("[GLG Worker] Keine mehr Jobs vorhanden. Beende Worker.");
        break;
    }

    $jobId = $job['job_id'];
    $status = $job['status'];
    $action = $job['action'];
    $sourceLanguage = $job['source_language'];
    $targetLanguage = $job['target_language'];
    $sourceFile = $job['source_file'];

    error_log("[GLG Worker] Processing Job: $jobId | $sourceLanguage → $targetLanguage | File: $sourceFile");

    try {
        if ($action === 'translate_file') {
            processTranslationJob($job, $glgCore, $settings);
            $glgCore->completeJob($jobId);
            error_log("[GLG Worker] ✓ Job completed: $jobId");
        } else {
            throw new Exception("Unbekannte Job-Action: $action");
        }

        $jobCount++;

    } catch (Exception $e) {
        error_log("[GLG Worker] ✗ Job failed: $jobId | Error: " . $e->getMessage());
        $glgCore->failJob($jobId, $e->getMessage());
    }

    // Kurze Pause zwischen Jobs
    usleep(100000); // 0.1 Sekunden
}

error_log("[GLG Worker] Worker finished. Processed $jobCount jobs.");
exit(0);

/**
 * Verarbeitet einen Translation-Job
 */
function processTranslationJob($job, $glgCore, $settings) {
    $jobId = $job['job_id'];
    $sourceLanguage = $job['source_language'];
    $targetLanguage = $job['target_language'];
    $sourceFile = $job['source_file'];
    $params = json_decode($job['params'], true);

    // Aktualisiere Progress
    $glgCore->updateJobProgress($jobId, 10, 'Initializing...');

    // Initialisiere Klassen
    $reader = new GLGReader();
    $translator = new GLGTranslator($settings);
    $writer = new GLGFileWriter($settings['backupEnabled'] ?? true);

    // Lese Source-Daten
    $glgCore->updateJobProgress($jobId, 20, 'Reading source language data...');

    $options = [
        'includeCoreFiles' => $params['includeCoreFiles'] ?? true,
        'includeGXModules' => $params['includeGXModules'] ?? true,
        'selectedModules' => $params['selectedModules'] ?? []
    ];

    $sourceData = $reader->readLanguageData($sourceLanguage, $options);

    if (!isset($sourceData[$sourceFile])) {
        throw new Exception("Source file '$sourceFile' not found in language data");
    }

    $fileData = $sourceData[$sourceFile];
    $totalEntries = 0;

    // Zähle Einträge
    foreach ($fileData['sections'] as $entries) {
        $totalEntries += count($entries);
    }

    error_log("[GLG Worker] Job $jobId: Found $totalEntries entries to translate");

    // Übersetze jede Sektion
    $glgCore->updateJobProgress($jobId, 30, 'Translating sections...');

    $translatedSections = [];
    $sectionIndex = 0;

    foreach ($fileData['sections'] as $sectionName => $entries) {
        $sectionIndex++;
        $percent = 30 + (($sectionIndex / count($fileData['sections'])) * 60);

        $glgCore->updateJobProgress($jobId, intval($percent), "Section: $sectionName");

        // Erstelle optimale Batches für API
        $batches = $translator->createOptimalBatches($entries);
        $translatedEntries = [];

        // Übersetze Batches
        foreach ($batches as $batchIndex => $batch) {
            try {
                $translated = $translator->translateBatch(
                    $batch,
                    $sourceLanguage,
                    $targetLanguage,
                    $sourceFile . ' - ' . $sectionName
                );

                $translatedEntries = array_merge($translatedEntries, $translated);

                // Rate limiting - pause between batches
                if ($batchIndex < count($batches) - 1) {
                    error_log("[GLG Worker] Rate limiting pause for job $jobId...");
                    sleep(2);
                }

            } catch (Exception $e) {
                error_log("[GLG Worker] Batch translation failed for job $jobId: " . $e->getMessage());
                throw new Exception("Batch translation error in section '$sectionName': " . $e->getMessage());
            }
        }

        $translatedSections[$sectionName] = $translatedEntries;
    }

    // Schreibe Zieldatei
    $glgCore->updateJobProgress($jobId, 95, 'Writing target language file...');

    $result = $writer->writeSourceFile([
        'source' => $sourceFile,
        'sections' => $translatedSections
    ], $targetLanguage);

    error_log("[GLG Worker] Job $jobId: Wrote file to " . $result['file']);

    // Fertig
    $glgCore->updateJobProgress($jobId, 100, 'Completed');
}

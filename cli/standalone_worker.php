<?php
/**
 * Gambio Language Generator - Standalone Background Worker
 *
 * Minimaler Worker der nur die DB-Verbindung braucht, kein application_top.php
 *
 * Nutzung: php standalone_worker.php [max_jobs]
 */

// Konfiguration
define('SCRIPT_DIR', dirname(__FILE__));
define('PROJECT_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
define('DIR_FS_CATALOG', PROJECT_ROOT . '/');

echo "[WORKER] Starting standalone worker at " . date('Y-m-d H:i:s') . "\n";

// Lade nur das Minimum: DB-Konfiguration
require_once(DIR_FS_CATALOG . 'includes/configure.php');

echo "[WORKER] Configuration loaded\n";

// Lade DB-Funktionen (minimale Variante)
if (!function_exists('xtc_db_connect')) {
    require_once(DIR_FS_CATALOG . 'includes/database_tables.php');
    require_once(DIR_FS_CATALOG . 'includes/functions/database.php');

    // DB-Verbindung herstellen
    $link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
    if (!$link) {
        die("DB Connection failed: " . mysqli_connect_error() . "\n");
    }
    mysqli_set_charset($link, DB_SERVER_CHARSET);

    // Wrapper-Funktionen für Gambio's DB-API
    function xtc_db_query($query, $link = null) {
        global $link;
        return mysqli_query($link, $query);
    }

    function xtc_db_fetch_array($result) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC);
    }

    function xtc_db_num_rows($result) {
        return mysqli_num_rows($result);
    }

    function xtc_db_free_result($result) {
        return mysqli_free_result($result);
    }

    function xtc_db_input($string) {
        global $link;
        return mysqli_real_escape_string($link, $string);
    }
}

echo "[WORKER] Database connected\n";

// Lade nur die GLG-Klassen (ohne Gambio dependencies)
require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGReader.php');
require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGTranslator.php');
require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGFileWriter.php');

echo "[WORKER] GLG classes loaded\n";

// Lade Settings direkt aus DB
function getSettings() {
    $result = xtc_db_query("SELECT * FROM rz_glg_settings LIMIT 1");
    if ($row = xtc_db_fetch_array($result)) {
        return $row;
    }
    return [
        'api_provider' => 'openai',
        'api_key' => '',
        'model' => 'gpt-4o',
        'temperature' => 0.3,
        'max_tokens' => 4000
    ];
}

$settings = getSettings();
echo "[WORKER] Settings loaded: Provider={$settings['api_provider']}, Model={$settings['model']}\n";

// Worker-Funktion: Hole nächsten Job mit Locking
function getNextJob() {
    global $link;

    // Start Transaction für atomare Lock-Operation
    mysqli_begin_transaction($link);

    try {
        // Hole ältesten pending Job und locke ihn
        $query = "SELECT * FROM rz_glg_jobs
                  WHERE status = 'pending'
                  AND (locked_until IS NULL OR locked_until < NOW())
                  ORDER BY id ASC
                  LIMIT 1
                  FOR UPDATE";

        $result = xtc_db_query($query);

        if (xtc_db_num_rows($result) == 0) {
            mysqli_commit($link);
            return null;
        }

        $job = xtc_db_fetch_array($result);

        // Markiere als processing und locke für 10 Minuten
        $jobId = xtc_db_input($job['job_id']);
        $pid = getmypid();
        $updateQuery = "UPDATE rz_glg_jobs
                       SET status = 'processing',
                           worker_pid = $pid,
                           locked_until = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
                       WHERE job_id = '$jobId'";
        xtc_db_query($updateQuery);

        mysqli_commit($link);

        return $job;

    } catch (Exception $e) {
        mysqli_rollback($link);
        echo "[WORKER] Error getting job: " . $e->getMessage() . "\n";
        return null;
    }
}

// Worker-Funktion: Update Job-Status
function updateJobStatus($jobId, $status, $progressPercent = null, $progressText = null, $errorMessage = null) {
    $jobId = xtc_db_input($jobId);
    $status = xtc_db_input($status);

    $sets = ["status = '$status'"];

    if ($progressPercent !== null) {
        $sets[] = "progress_percent = " . intval($progressPercent);
    }
    if ($progressText !== null) {
        $text = xtc_db_input($progressText);
        $sets[] = "progress_text = '$text'";
    }
    if ($errorMessage !== null) {
        $error = xtc_db_input($errorMessage);
        $sets[] = "error_message = '$error'";
    }
    if ($status === 'success' || $status === 'error') {
        $sets[] = "completed_at = NOW()";
    }

    $query = "UPDATE rz_glg_jobs SET " . implode(', ', $sets) . " WHERE job_id = '$jobId'";
    xtc_db_query($query);
}

// Worker Loop
$maxJobsPerRun = isset($argv[1]) ? intval($argv[1]) : 10;
$jobCount = 0;

echo "[WORKER] Starting job processing (max $maxJobsPerRun jobs)...\n\n";

while ($jobCount < $maxJobsPerRun) {
    $job = getNextJob();

    if (!$job) {
        echo "[WORKER] No more jobs available\n";
        break;
    }

    $jobCount++;
    $jobId = $job['job_id'];
    $sourceLanguage = $job['source_language'];
    $targetLanguage = $job['target_language'];
    $sourceFile = $job['source_file'];

    echo "[WORKER] Job $jobCount/$maxJobsPerRun: {$jobId}\n";
    echo "         Source: {$sourceLanguage}\n";
    echo "         Target: {$targetLanguage}\n";
    echo "         File: {$sourceFile}\n";

    try {
        // 1. Lese Source-File
        echo "         [1/4] Reading source file...\n";
        updateJobStatus($jobId, 'processing', 10, 'Reading source file');

        $reader = new GLGReader();
        $sections = $reader->readSourceFile($sourceFile, $sourceLanguage);

        if (empty($sections)) {
            throw new Exception("No sections found in source file or file not readable");
        }

        echo "         Found " . count($sections) . " sections\n";

        // 2. Übersetze jede Section
        echo "         [2/4] Translating sections...\n";
        updateJobStatus($jobId, 'processing', 30, 'Translating sections');

        $translator = new GLGTranslator($settings);
        $translatedSections = [];
        $sectionCount = count($sections);
        $currentSection = 0;

        foreach ($sections as $sectionName => $entries) {
            $currentSection++;
            $percent = 30 + (($currentSection / $sectionCount) * 40); // 30-70%

            echo "         Translating section '$sectionName' ($currentSection/$sectionCount)...\n";
            updateJobStatus($jobId, 'processing', $percent, "Translating section $currentSection/$sectionCount");

            // Batch-Übersetzung
            $batch = [];
            foreach ($entries as $key => $value) {
                $batch[$key] = $value;
            }

            if (!empty($batch)) {
                $translated = $translator->translateBatch(
                    $batch,
                    $sourceLanguage,
                    $targetLanguage,
                    $sourceFile . ' - ' . $sectionName
                );

                $translatedSections[$sectionName] = $translated;
            }
        }

        // 3. Schreibe Target-File
        echo "         [3/4] Writing target file...\n";
        updateJobStatus($jobId, 'processing', 75, 'Writing target file');

        $writer = new GLGFileWriter();
        $result = $writer->writeSourceFile([
            'source' => $sourceFile,
            'sections' => $translatedSections
        ], $targetLanguage);

        if (!$result['success']) {
            throw new Exception("Failed to write target file: " . ($result['message'] ?? 'Unknown error'));
        }

        // 4. Erfolg
        echo "         [4/4] Success! File written to: " . $result['path'] . "\n";
        updateJobStatus($jobId, 'success', 100, 'Translation completed');

        echo "         ✓ Job completed successfully\n\n";

    } catch (Exception $e) {
        echo "         ✗ Error: " . $e->getMessage() . "\n\n";
        updateJobStatus($jobId, 'error', null, null, $e->getMessage());
    }
}

echo "[WORKER] Processed $jobCount jobs\n";
echo "[WORKER] Finished at " . date('Y-m-d H:i:s') . "\n";

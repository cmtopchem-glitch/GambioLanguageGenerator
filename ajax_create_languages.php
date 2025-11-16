<?php
/**
 * Gambio Language Generator - AJAX Handler für Sprach-Erstellung
 *
 * Standalone JSON-Endpoint außerhalb des ModuleCenter Frameworks
 * um reines JSON ohne HTML-Wrapping zu liefern
 */

// Set response header to JSON
header('Content-Type: application/json; charset=utf-8');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Bestimme Root-Verzeichnis
    $moduleDir = dirname(__FILE__);
    // GXModules/REDOzone/GambioLanguageGenerator -> gehe 3 Ebenen hoch -> /srv/www/test.redozone
    $catalogRoot = dirname(dirname(dirname($moduleDir)));

    // Lade Gambio Funktionen
    if (!file_exists($catalogRoot . '/includes/application_top.php')) {
        throw new Exception('Gambio application_top.php nicht gefunden in: ' . $catalogRoot);
    }
    require_once($catalogRoot . '/includes/application_top.php');

    // Lese POST-Daten
    $languages = isset($_POST['languages']) ? $_POST['languages'] : [];

    if (empty($languages) || !is_array($languages)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Keine Sprachen angegeben'
        ]);
        exit;
    }

    // Lade GLGLanguageInitializer
    require_once($moduleDir . '/Admin/Classes/GLGLanguageInitializer.php');

    $created = [];
    $errors = [];

    foreach ($languages as $isoCode) {
        // Sanitize
        $isoCode = preg_replace('/[^a-z]/', '', strtolower($isoCode));

        if (empty($isoCode)) {
            continue;
        }

        // Erstelle Sprache
        $result = GLGLanguageInitializer::initializeLanguage($isoCode);

        if ($result['success']) {
            // Aktualisiere die Datumsformate mit direkter SQL
            $languageId = $result['languageId'] ?? null;
            $dateFormat = xtc_db_input($result['dateFormat'] ?? 'd.m.Y');
            $dateFormatLong = xtc_db_input($result['dateFormatLong'] ?? 'l, d. F Y');
            $dateFormatShort = xtc_db_input($result['dateFormatShort'] ?? 'd.m.Y');
            $dateTimeFormat = xtc_db_input($result['dateTimeFormat'] ?? 'd.m.Y H:i:s');
            $dobFormat = xtc_db_input($result['dobFormat'] ?? 'tt.mm.jjjj');
            $htmlParams = xtc_db_input($result['htmlParams'] ?? 'dir="ltr" lang="en"');
            $phpDateTimeFormat = xtc_db_input($result['phpDateTimeFormat'] ?? 'd.m.Y H:i:s');

            if ($languageId) {
                $updateQuery = "UPDATE languages SET date_format='$dateFormat', date_format_long='$dateFormatLong', date_format_short='$dateFormatShort', date_time_format='$dateTimeFormat', dob_format_string='$dobFormat', html_params='$htmlParams', language_currency='EUR', php_date_time_format='$phpDateTimeFormat' WHERE languages_id=$languageId";
                xtc_db_query($updateQuery);
            }

            $created[] = [
                'code' => $isoCode,
                'name' => $result['languageName'] ?? $isoCode,
                'directory' => $result['languageDir'] ?? '',
                'message' => $result['message']
            ];
        } else {
            $errors[] = [
                'code' => $isoCode,
                'error' => $result['message']
            ];
        }
    }

    // Response
    $response = [
        'success' => true,
        'created' => $created,
        'errors' => $errors,
        'message' => count($created) . ' Sprachen erstellt, ' . count($errors) . ' Fehler'
    ];

    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler: ' . $e->getMessage()
    ]);
}

exit;

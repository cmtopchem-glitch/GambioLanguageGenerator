<?php
/**
 * Gambio Language Generator - AJAX Handler für Abruf existierender Sprachen
 *
 * Gibt Liste der bereits erstellten Sprachen zurück
 */

// Set response header to JSON
header('Content-Type: application/json; charset=utf-8');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Bestimme Root-Verzeichnis
    $moduleDir = dirname(__FILE__);
    $catalogRoot = dirname(dirname(dirname($moduleDir)));

    // Lade Gambio Funktionen
    if (!file_exists($catalogRoot . '/includes/application_top.php')) {
        throw new Exception('Gambio application_top.php nicht gefunden');
    }
    require_once($catalogRoot . '/includes/application_top.php');

    // Hole alle existierenden Sprachen aus der Datenbank
    $query = "SELECT code FROM languages ORDER BY sort_order";
    $result = xtc_db_query($query);

    $existingLanguages = [];
    while ($row = xtc_db_fetch_array($result)) {
        if (!empty($row['code'])) {
            $existingLanguages[] = strtolower($row['code']);
        }
    }

    // Response mit Liste existierender Sprachen
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'existingLanguages' => $existingLanguages
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler: ' . $e->getMessage()
    ]);
}

exit;
?>

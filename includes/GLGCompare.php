<?php
/**
 * Gambio Language Generator - Compare & Preview
 * 
 * Vergleicht Sprachen und zeigt fehlende Übersetzungen
 * 
 * @author Christian Mittenzwei
 * @version 1.0.0
 */

class GLGCompare {

    private $reader;

    public function __construct() {
        // GLGReader wird vom Controller geladen, einfach eine neue Instanz erstellen
        if (!class_exists('GLGReader')) {
            require_once(__DIR__ . '/GLGReader.php');
        }
        $this->reader = new GLGReader();
    }
    
    /**
     * Vergleicht zwei Sprachen und findet fehlende Übersetzungen
     * 
     * @param string $sourceLanguage Quellsprache
     * @param string $targetLanguage Zielsprache
     * @param array $options Filter-Optionen
     * @return array Vergleichsergebnis
     */
    public function compareLanguages($sourceLanguage, $targetLanguage, $options = []) {
        // Lade beide Sprachen
        $sourceData = $this->reader->readLanguageData($sourceLanguage, $options);
        $targetData = $this->reader->readLanguageData($targetLanguage, $options);
        
        $comparison = [
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'total_source_entries' => 0,
            'total_target_entries' => 0,
            'missing_entries' => 0,
            'missing_files' => [],
            'missing_keys' => [],
            'statistics' => []
        ];
        
        // Vergleiche Dateien
        foreach ($sourceData as $sourceFile => $sourceFileData) {
            $sourceSections = $sourceFileData['sections'];
            
            // Zähle Einträge
            $sourceCount = $this->countEntries($sourceSections);
            $comparison['total_source_entries'] += $sourceCount;
            
            if (!isset($targetData[$sourceFile])) {
                // Komplette Datei fehlt
                $comparison['missing_files'][] = [
                    'file' => $sourceFile,
                    'missing_count' => $sourceCount
                ];
                $comparison['missing_entries'] += $sourceCount;
                continue;
            }
            
            $targetSections = $targetData[$sourceFile]['sections'];
            $targetCount = $this->countEntries($targetSections);
            $comparison['total_target_entries'] += $targetCount;
            
            // Vergleiche Sektionen
            foreach ($sourceSections as $sectionName => $sourceEntries) {
                if (!isset($targetSections[$sectionName])) {
                    // Komplette Sektion fehlt
                    foreach ($sourceEntries as $key => $value) {
                        $comparison['missing_keys'][] = [
                            'file' => $sourceFile,
                            'section' => $sectionName,
                            'key' => $key,
                            'source_value' => $value
                        ];
                        $comparison['missing_entries']++;
                    }
                    continue;
                }
                
                $targetEntries = $targetSections[$sectionName];
                
                // Vergleiche Keys
                foreach ($sourceEntries as $key => $value) {
                    if (!isset($targetEntries[$key])) {
                        // Key fehlt
                        $comparison['missing_keys'][] = [
                            'file' => $sourceFile,
                            'section' => $sectionName,
                            'key' => $key,
                            'source_value' => $value
                        ];
                        $comparison['missing_entries']++;
                    }
                }
            }
        }
        
        // Berechne Statistiken
        $comparison['statistics'] = $this->calculateStatistics($comparison);
        
        return $comparison;
    }
    
    /**
     * Zählt Einträge in Sektionen
     */
    private function countEntries($sections) {
        $count = 0;
        foreach ($sections as $entries) {
            $count += count($entries);
        }
        return $count;
    }
    
    /**
     * Berechnet Statistiken
     */
    private function calculateStatistics($comparison) {
        $stats = [
            'completion_percentage' => 0,
            'missing_percentage' => 0,
            'files_with_missing' => count($comparison['missing_files']),
            'keys_missing' => count($comparison['missing_keys'])
        ];
        
        if ($comparison['total_source_entries'] > 0) {
            $existing = $comparison['total_source_entries'] - $comparison['missing_entries'];
            $stats['completion_percentage'] = round(($existing / $comparison['total_source_entries']) * 100, 2);
            $stats['missing_percentage'] = round(($comparison['missing_entries'] / $comparison['total_source_entries']) * 100, 2);
        }
        
        return $stats;
    }
    
    /**
     * Gruppiert fehlende Einträge nach Datei
     */
    public function groupMissingByFile($missingKeys) {
        $grouped = [];
        
        foreach ($missingKeys as $entry) {
            $file = $entry['file'];
            
            if (!isset($grouped[$file])) {
                $grouped[$file] = [
                    'file' => $file,
                    'missing_count' => 0,
                    'sections' => []
                ];
            }
            
            $section = $entry['section'];
            if (!isset($grouped[$file]['sections'][$section])) {
                $grouped[$file]['sections'][$section] = [];
            }
            
            $grouped[$file]['sections'][$section][] = [
                'key' => $entry['key'],
                'source_value' => $entry['source_value']
            ];
            
            $grouped[$file]['missing_count']++;
        }
        
        return $grouped;
    }
    
    /**
     * Erstellt einen Vorschau-Report
     */
    public function createPreviewReport($comparison) {
        $report = [
            'summary' => $this->createSummary($comparison),
            'details' => $this->createDetails($comparison)
        ];
        
        return $report;
    }
    
    /**
     * Erstellt Zusammenfassung
     */
    private function createSummary($comparison) {
        return [
            'source_language' => $comparison['source_language'],
            'target_language' => $comparison['target_language'],
            'total_source_entries' => $comparison['total_source_entries'],
            'total_target_entries' => $comparison['total_target_entries'],
            'missing_entries' => $comparison['missing_entries'],
            'completion_percentage' => $comparison['statistics']['completion_percentage'],
            'missing_percentage' => $comparison['statistics']['missing_percentage']
        ];
    }
    
    /**
     * Erstellt Detail-Informationen
     */
    private function createDetails($comparison) {
        $details = [
            'missing_files' => $comparison['missing_files'],
            'missing_by_file' => $this->groupMissingByFile($comparison['missing_keys']),
            'top_missing_files' => $this->getTopMissingFiles($comparison)
        ];
        
        return $details;
    }
    
    /**
     * Gibt Dateien mit den meisten fehlenden Einträgen zurück
     */
    private function getTopMissingFiles($comparison, $limit = 10) {
        $grouped = $this->groupMissingByFile($comparison['missing_keys']);
        
        // Sortiere nach Anzahl fehlender Einträge
        usort($grouped, function($a, $b) {
            return $b['missing_count'] - $a['missing_count'];
        });
        
        return array_slice($grouped, 0, $limit);
    }
    
    /**
     * Exportiert Vergleich als CSV
     */
    public function exportToCsv($comparison, $filename) {
        $fp = fopen($filename, 'w');
        
        // Header
        fputcsv($fp, ['Datei', 'Sektion', 'Key', 'Quelltext']);
        
        // Daten
        foreach ($comparison['missing_keys'] as $entry) {
            fputcsv($fp, [
                $entry['file'],
                $entry['section'],
                $entry['key'],
                $entry['source_value']
            ]);
        }
        
        fclose($fp);
        
        return $filename;
    }
    
    /**
     * Erstellt einen HTML-Report
     */
    public function createHtmlReport($comparison) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sprachvergleich: ' . $comparison['source_language'] . ' → ' . $comparison['target_language'] . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .summary { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .summary h2 { margin-top: 0; }
        .stats { display: flex; gap: 20px; margin: 15px 0; }
        .stat-box { background: white; padding: 15px; border-radius: 5px; flex: 1; text-align: center; }
        .stat-value { font-size: 32px; font-weight: bold; color: #333; }
        .stat-label { color: #666; margin-top: 5px; }
        .progress-bar { background: #e0e0e0; height: 30px; border-radius: 5px; overflow: hidden; margin: 15px 0; }
        .progress-fill { background: #4CAF50; height: 100%; line-height: 30px; color: white; text-align: center; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .file-name { font-weight: bold; color: #333; }
        .section-name { color: #666; font-style: italic; }
        .key-name { font-family: monospace; background: #f5f5f5; padding: 2px 5px; }
    </style>
</head>
<body>
    <h1>Sprachvergleich: ' . $comparison['source_language'] . ' → ' . $comparison['target_language'] . '</h1>
    
    <div class="summary">
        <h2>Zusammenfassung</h2>
        <div class="stats">
            <div class="stat-box">
                <div class="stat-value">' . $comparison['total_source_entries'] . '</div>
                <div class="stat-label">Quell-Einträge</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">' . $comparison['total_target_entries'] . '</div>
                <div class="stat-label">Ziel-Einträge</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: #f44336;">' . $comparison['missing_entries'] . '</div>
                <div class="stat-label">Fehlende Einträge</div>
            </div>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: ' . $comparison['statistics']['completion_percentage'] . '%">
                ' . $comparison['statistics']['completion_percentage'] . '% vollständig
            </div>
        </div>
    </div>
    
    <h2>Fehlende Übersetzungen (' . count($comparison['missing_keys']) . ')</h2>
    <table>
        <thead>
            <tr>
                <th>Datei</th>
                <th>Sektion</th>
                <th>Key</th>
                <th>Quelltext</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($comparison['missing_keys'] as $entry) {
            $html .= '<tr>
                <td class="file-name">' . htmlspecialchars($entry['file']) . '</td>
                <td class="section-name">' . htmlspecialchars($entry['section']) . '</td>
                <td class="key-name">' . htmlspecialchars($entry['key']) . '</td>
                <td>' . htmlspecialchars(substr($entry['source_value'], 0, 100)) . (strlen($entry['source_value']) > 100 ? '...' : '') . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
    </table>
    
    <p style="margin-top: 30px; color: #666;">
        Erstellt am ' . date('d.m.Y H:i:s') . ' mit Gambio Language Generator
    </p>
</body>
</html>';
        
        return $html;
    }
}

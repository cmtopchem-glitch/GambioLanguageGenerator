<?php
/**
 * Gambio Language Generator - Database Reader
 * 
 * Liest Sprachdaten aus der language_phrases_cache Tabelle
 * 
 * @author Christian Mittenzwei
 * @version 1.0.0
 */

class GLGReader {
    
    private $db;
    
    public function __construct() {
        $this->db = $GLOBALS['db_link'];
    }
    
    /**
     * Liest alle Spracheinträge für eine Sprache
     * 
     * @param string $language Sprachverzeichnis (z.B. 'german')
     * @param array $options Optionen (includeCoreFiles, includeGXModules, selectedModules)
     * @return array Gruppierte Spracheinträge nach Source-Datei
     */
    public function readLanguageData($language, $options = []) {
        $includeCoreFiles = $options['includeCoreFiles'] ?? true;
        $includeGXModules = $options['includeGXModules'] ?? true;
        $selectedModules = $options['selectedModules'] ?? [];
        
        $data = [];
        
        // Core-Dateien laden
        if ($includeCoreFiles) {
            $coreData = $this->readCoreFiles($language);
            $data = array_merge($data, $coreData);
        }
        
        // GXModule-Dateien laden
        if ($includeGXModules) {
            $moduleData = $this->readGXModules($language, $selectedModules);
            $data = array_merge($data, $moduleData);
        }
        
        return $data;
    }
    
    /**
     * Liest Core-Sprachdateien (/lang)
     */
    private function readCoreFiles($language) {
        $language = xtc_db_input($language);
        
        $query = "SELECT
                    source,
                    section_name,
                    phrase_name,
                    phrase_text,
                    date_modified
                  FROM language_phrases_cache
                  WHERE language_id = (
                    SELECT languages_id FROM languages WHERE directory = '$language'
                  )
                  AND source NOT LIKE 'GXModules/%'
                  ORDER BY source, section_name, phrase_name";
        
        return $this->executeAndGroup($query);
    }
    
    /**
     * Liest GXModule-Sprachdateien
     */
    private function readGXModules($language, $selectedModules = []) {
        $language = xtc_db_input($language);
        
        $moduleFilter = '';
        if (!empty($selectedModules)) {
            $modules = array_map('xtc_db_input', $selectedModules);
            $modulesStr = "'" . implode("','", $modules) . "'";
            
            // Erstelle LIKE Filter für jedes Modul
            $likeConditions = array_map(function($module) {
                return "source LIKE 'GXModules/$module/%'";
            }, $modules);
            
            $moduleFilter = ' AND (' . implode(' OR ', $likeConditions) . ')';
        }
        
        $query = "SELECT
                    source,
                    section_name,
                    phrase_name,
                    phrase_text,
                    date_modified
                  FROM language_phrases_cache
                  WHERE language_id = (
                    SELECT languages_id FROM languages WHERE directory = '$language'
                  )
                  AND source LIKE 'GXModules/%'
                  $moduleFilter
                  ORDER BY source, section_name, phrase_name";
        
        return $this->executeAndGroup($query);
    }
    
    /**
     * Führt Query aus und gruppiert Ergebnisse
     */
    private function executeAndGroup($query) {
        $result = xtc_db_query($query);
        $data = [];
        
        while ($row = xtc_db_fetch_array($result)) {
            $source = $row['source'];
            $section = $row['section_name'];
            
            if (!isset($data[$source])) {
                $data[$source] = [
                    'source' => $source,
                    'sections' => [],
                    'latest_modification' => $row['date_modified']
                ];
            }
            
            if (!isset($data[$source]['sections'][$section])) {
                $data[$source]['sections'][$section] = [];
            }
            
            $data[$source]['sections'][$section][$row['phrase_name']] = $row['phrase_text'];
            
            // Aktualisiere latest_modification wenn neuer
            if (strtotime($row['date_modified']) > strtotime($data[$source]['latest_modification'])) {
                $data[$source]['latest_modification'] = $row['date_modified'];
            }
        }
        
        return $data;
    }
    
    /**
     * Liest geänderte Einträge seit einem Datum
     * 
     * @param string $since Datum im Format 'Y-m-d H:i:s'
     * @param string $language Sprachverzeichnis
     * @return array Geänderte Einträge
     */
    public function readChangedEntries($since, $language) {
        $since = xtc_db_input($since);
        $language = xtc_db_input($language);
        
        $query = "SELECT
                    source,
                    section_name,
                    phrase_name,
                    phrase_text,
                    date_modified
                  FROM language_phrases_cache
                  WHERE language_id = (
                    SELECT languages_id FROM languages WHERE directory = '$language'
                  )
                  AND date_modified > '$since'
                  ORDER BY source, section_name, phrase_name";
        
        return $this->executeAndGroup($query);
    }
    
    /**
     * Prüft welche Source-Dateien existieren
     * 
     * @param string $language Sprachverzeichnis
     * @return array Liste der Source-Dateien
     */
    public function getSourceFiles($language) {
        $language = xtc_db_input($language);
        
        $query = "SELECT DISTINCT source
                  FROM language_phrases_cache
                  WHERE language_id = (
                    SELECT languages_id FROM languages WHERE directory = '$language'
                  )
                  ORDER BY source";
        
        $result = xtc_db_query($query);
        $sources = [];
        
        while ($row = xtc_db_fetch_array($result)) {
            $sources[] = $row['source'];
        }
        
        return $sources;
    }
    
    /**
     * Zählt Einträge für eine Sprache
     * 
     * @param string $language Sprachverzeichnis
     * @param array $options Filter-Optionen
     * @return int Anzahl der Einträge
     */
    public function countEntries($language, $options = []) {
        $language = xtc_db_input($language);
        
        $whereConditions = [
            "language_id = (SELECT languages_id FROM languages WHERE directory = '$language')"
        ];
        
        if (isset($options['includeCoreFiles']) && !$options['includeCoreFiles']) {
            $whereConditions[] = "source LIKE 'GXModules/%'";
        }
        
        if (isset($options['includeGXModules']) && !$options['includeGXModules']) {
            $whereConditions[] = "source NOT LIKE 'GXModules/%'";
        }
        
        if (!empty($options['selectedModules'])) {
            $modules = array_map('xtc_db_input', $options['selectedModules']);
            $likeConditions = array_map(function($module) {
                return "source LIKE 'GXModules/$module/%'";
            }, $modules);
            $whereConditions[] = '(' . implode(' OR ', $likeConditions) . ')';
        }
        
        $where = implode(' AND ', $whereConditions);
        
        $query = "SELECT COUNT(*) as total
                  FROM language_phrases_cache
                  WHERE $where";
        
        $result = xtc_db_query($query);
        $row = xtc_db_fetch_array($result);
        
        return intval($row['total']);
    }
    
    /**
     * Gibt Statistiken über die Sprachdaten zurück
     * 
     * @param string $language Sprachverzeichnis
     * @return array Statistiken
     */
    public function getStatistics($language) {
        $language = xtc_db_input($language);
        
        $stats = [
            'total_entries' => 0,
            'core_entries' => 0,
            'module_entries' => 0,
            'source_files' => 0,
            'modules' => 0
        ];
        
        // Gesamt
        $query = "SELECT COUNT(*) as total
                  FROM language_phrases_cache
                  WHERE language_id = (
                    SELECT languages_id FROM languages WHERE directory = '$language'
                  )";
        $result = xtc_db_query($query);
        $row = xtc_db_fetch_array($result);
        $stats['total_entries'] = intval($row['total']);
        
        // Core
        $query = "SELECT COUNT(*) as total
                  FROM language_phrases_cache
                  WHERE language_id = (
                    SELECT languages_id FROM languages WHERE directory = '$language'
                  )
                  AND source NOT LIKE 'GXModules/%'";
        $result = xtc_db_query($query);
        $row = xtc_db_fetch_array($result);
        $stats['core_entries'] = intval($row['total']);
        
        // Module
        $stats['module_entries'] = $stats['total_entries'] - $stats['core_entries'];
        
        // Source-Dateien
        $query = "SELECT COUNT(DISTINCT source) as total
                  FROM language_phrases_cache
                  WHERE language_id = (
                    SELECT languages_id FROM languages WHERE directory = '$language'
                  )";
        $result = xtc_db_query($query);
        $row = xtc_db_fetch_array($result);
        $stats['source_files'] = intval($row['total']);
        
        // Module
        $query = "SELECT COUNT(DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(source, '/', 2), '/', -1)) as total
                  FROM language_phrases_cache
                  WHERE language_id = (
                    SELECT languages_id FROM languages WHERE directory = '$language'
                  )
                  AND source LIKE 'GXModules/%'";
        $result = xtc_db_query($query);
        $row = xtc_db_fetch_array($result);
        $stats['modules'] = intval($row['total']);
        
        return $stats;
    }
}

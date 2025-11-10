<?php
/**
 * Gambio Language Generator - Language Manager
 * 
 * Verwaltet Sprachen im Gambio-System
 * - Neue Sprachen anlegen
 * - Sprachen aktualisieren
 * - Sprachicons generieren
 * 
 * @author Christian Mittenzwei
 * @version 1.0.0
 */

class GLGLanguageManager {
    
    private $db;
    private $languagesTable = 'languages';
    
    public function __construct() {
        $this->db = $GLOBALS['db_link'];
    }
    
    /**
     * Prüft ob eine Sprache existiert
     * 
     * @param string $directory Sprachverzeichnis (z.B. 'spanish')
     * @return bool
     */
    public function languageExists($directory) {
        $directory = xtc_db_input($directory);
        
        $query = "SELECT languages_id FROM {$this->languagesTable} 
                  WHERE directory = '$directory'";
        $result = xtc_db_query($query);
        
        return xtc_db_num_rows($result) > 0;
    }
    
    /**
     * Legt eine neue Sprache an
     * 
     * @param array $languageData Sprachdaten
     * @return array Ergebnis
     */
    public function createLanguage($languageData) {
        $name = xtc_db_input($languageData['name']);
        $code = xtc_db_input($languageData['code']);
        $directory = xtc_db_input($languageData['directory']);
        $countryCode = xtc_db_input($languageData['country_code'] ?? strtoupper($code));
        
        // Prüfe ob Sprache bereits existiert
        if ($this->languageExists($directory)) {
            return [
                'success' => false,
                'message' => "Sprache '$directory' existiert bereits"
            ];
        }
        
        // Ermittle nächste sort_order
        $query = "SELECT MAX(sort_order) as max_order FROM {$this->languagesTable}";
        $result = xtc_db_query($query);
        $row = xtc_db_fetch_array($result);
        $sortOrder = intval($row['max_order']) + 1;
        
        // Erstelle Sprachverzeichnisse
        $this->createLanguageDirectories($directory);
        
        // Generiere Sprachicon
        $iconPath = $this->generateLanguageIcon($directory, $countryCode);
        
        // Füge Sprache in Datenbank ein
        $query = "INSERT INTO {$this->languagesTable} 
                  (name, code, image, directory, sort_order, language_charset) 
                  VALUES 
                  ('$name', '$code', '$iconPath', '$directory', $sortOrder, 'utf-8')";
        
        if (xtc_db_query($query)) {
            $languageId = xtc_db_insert_id();
            
            // Kopiere Standard-Konfiguration
            $this->copyLanguageConfiguration($languageId);
            
            return [
                'success' => true,
                'message' => "Sprache '$name' erfolgreich angelegt",
                'language_id' => $languageId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Fehler beim Einfügen in Datenbank'
            ];
        }
    }
    
    /**
     * Erstellt Sprachverzeichnisse
     */
    private function createLanguageDirectories($directory) {
        $basePath = DIR_FS_CATALOG;
        
        $directories = [
            "lang/$directory",
            "lang/$directory/admin",
            "lang/$directory/images",
            "lang/$directory/modules",
            "lang/$directory/original_sections",
            "lang/$directory/sections"
        ];
        
        foreach ($directories as $dir) {
            $fullPath = $basePath . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }
    }
    
    /**
     * Generiert Sprachicon aus Länderflagge
     * 
     * @param string $directory Sprachverzeichnis
     * @param string $countryCode ISO-Ländercode (z.B. 'ES', 'FR')
     * @return string Relativer Pfad zum Icon
     */
    private function generateLanguageIcon($directory, $countryCode) {
        $iconDir = DIR_FS_CATALOG . 'lang/' . $directory . '/images/';
        $iconFile = 'icon.gif';
        $iconPath = $iconDir . $iconFile;
        
        // Prüfe ob Länderflagge existiert
        $flagSources = [
            DIR_FS_CATALOG . "images/flags/$countryCode.gif",
            DIR_FS_CATALOG . "images/flags/" . strtolower($countryCode) . ".gif",
            DIR_FS_CATALOG . "admin/images/icons/flags/$countryCode.png",
            DIR_FS_CATALOG . "admin/images/icons/flags/" . strtolower($countryCode) . ".png"
        ];
        
        foreach ($flagSources as $flagSource) {
            if (file_exists($flagSource)) {
                // Kopiere oder konvertiere Flagge
                if (pathinfo($flagSource, PATHINFO_EXTENSION) === 'gif') {
                    copy($flagSource, $iconPath);
                } else {
                    // PNG zu GIF konvertieren
                    $this->convertImageToGif($flagSource, $iconPath);
                }
                
                return $directory . '/images/' . $iconFile;
            }
        }
        
        // Wenn keine Flagge gefunden, erstelle Standard-Icon
        $this->createDefaultIcon($iconPath, $countryCode);
        
        return $directory . '/images/' . $iconFile;
    }
    
    /**
     * Konvertiert Bild zu GIF
     */
    private function convertImageToGif($source, $destination) {
        if (!function_exists('imagecreatefromstring')) {
            // Wenn GD nicht verfügbar, einfach kopieren
            copy($source, $destination);
            return;
        }
        
        try {
            $sourceImage = imagecreatefromstring(file_get_contents($source));
            if ($sourceImage) {
                imagegif($sourceImage, $destination);
                imagedestroy($sourceImage);
            } else {
                copy($source, $destination);
            }
        } catch (Exception $e) {
            copy($source, $destination);
        }
    }
    
    /**
     * Erstellt Standard-Icon mit Ländercode
     */
    private function createDefaultIcon($path, $countryCode) {
        if (!function_exists('imagecreate')) {
            // GD nicht verfügbar
            return;
        }
        
        // Erstelle 16x11 Icon (Standard Flaggen-Größe)
        $width = 16;
        $height = 11;
        
        $image = imagecreate($width, $height);
        
        // Farben
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        $textColor = imagecolorallocate($image, 50, 50, 50);
        
        // Text
        $text = strtoupper(substr($countryCode, 0, 2));
        imagestring($image, 2, 2, 2, $text, $textColor);
        
        // Speichern
        imagegif($image, $path);
        imagedestroy($image);
    }
    
    /**
     * Kopiert Sprachkonfiguration von einer anderen Sprache
     */
    private function copyLanguageConfiguration($targetLanguageId) {
        // Hole Referenz-Sprache (meist die mit der niedrigsten ID)
        $query = "SELECT languages_id FROM {$this->languagesTable} 
                  WHERE languages_id != $targetLanguageId 
                  ORDER BY languages_id ASC 
                  LIMIT 1";
        $result = xtc_db_query($query);
        
        if ($row = xtc_db_fetch_array($result)) {
            $sourceLanguageId = $row['languages_id'];
            
            // Kopiere Kategorie-Namen
            $this->copyTableData('categories_description', $sourceLanguageId, $targetLanguageId);
            
            // Kopiere Produkt-Namen/Beschreibungen
            $this->copyTableData('products_description', $sourceLanguageId, $targetLanguageId);
            
            // Kopiere Content-Texte
            $this->copyTableData('content_manager', $sourceLanguageId, $targetLanguageId);
            
            // Weitere Tabellen können hier ergänzt werden
        }
    }
    
    /**
     * Kopiert Daten von einer Sprache zur anderen
     */
    private function copyTableData($table, $sourceLanguageId, $targetLanguageId) {
        // Prüfe ob Tabelle existiert
        $query = "SHOW TABLES LIKE '$table'";
        $result = xtc_db_query($query);
        
        if (xtc_db_num_rows($result) === 0) {
            return; // Tabelle existiert nicht
        }
        
        // Prüfe ob language_id Spalte existiert
        $query = "SHOW COLUMNS FROM $table LIKE 'language_id'";
        $result = xtc_db_query($query);
        
        if (xtc_db_num_rows($result) === 0) {
            return; // Keine language_id Spalte
        }
        
        // Kopiere Daten (mit neuem language_id)
        $query = "SELECT * FROM $table WHERE language_id = $sourceLanguageId";
        $result = xtc_db_query($query);
        
        while ($row = xtc_db_fetch_array($result)) {
            // Ändere language_id
            $row['language_id'] = $targetLanguageId;
            
            // Baue INSERT Query
            $columns = array_keys($row);
            $values = array_map(function($value) {
                return "'" . xtc_db_input($value) . "'";
            }, array_values($row));
            
            $insertQuery = "INSERT INTO $table 
                           (" . implode(', ', $columns) . ") 
                           VALUES 
                           (" . implode(', ', $values) . ")";
            
            xtc_db_query($insertQuery);
        }
    }
    
    /**
     * Gibt alle verfügbaren Sprachen zurück
     */
    public function getAvailableLanguages() {
        $query = "SELECT * FROM {$this->languagesTable} ORDER BY sort_order";
        $result = xtc_db_query($query);
        
        $languages = [];
        while ($row = xtc_db_fetch_array($result)) {
            $languages[] = [
                'id' => $row['languages_id'],
                'name' => $row['name'],
                'code' => $row['code'],
                'directory' => $row['directory'],
                'image' => $row['image'],
                'sort_order' => $row['sort_order']
            ];
        }
        
        return $languages;
    }
    
    /**
     * Gibt Sprachvorschläge zurück (häufige Sprachen)
     */
    public function getLanguageSuggestions() {
        return [
            [
                'name' => 'Español',
                'code' => 'es',
                'directory' => 'spanish',
                'country_code' => 'ES'
            ],
            [
                'name' => 'Français',
                'code' => 'fr',
                'directory' => 'french',
                'country_code' => 'FR'
            ],
            [
                'name' => 'Italiano',
                'code' => 'it',
                'directory' => 'italian',
                'country_code' => 'IT'
            ],
            [
                'name' => 'Nederlands',
                'code' => 'nl',
                'directory' => 'dutch',
                'country_code' => 'NL'
            ],
            [
                'name' => 'Polski',
                'code' => 'pl',
                'directory' => 'polish',
                'country_code' => 'PL'
            ],
            [
                'name' => 'Português',
                'code' => 'pt',
                'directory' => 'portuguese',
                'country_code' => 'PT'
            ],
            [
                'name' => 'Русский',
                'code' => 'ru',
                'directory' => 'russian',
                'country_code' => 'RU'
            ],
            [
                'name' => 'Türkçe',
                'code' => 'tr',
                'directory' => 'turkish',
                'country_code' => 'TR'
            ],
            [
                'name' => '中文',
                'code' => 'zh',
                'directory' => 'chinese',
                'country_code' => 'CN'
            ],
            [
                'name' => '日本語',
                'code' => 'ja',
                'directory' => 'japanese',
                'country_code' => 'JP'
            ],
            [
                'name' => 'Svenska',
                'code' => 'sv',
                'directory' => 'swedish',
                'country_code' => 'SE'
            ],
            [
                'name' => 'Norsk',
                'code' => 'no',
                'directory' => 'norwegian',
                'country_code' => 'NO'
            ],
            [
                'name' => 'Dansk',
                'code' => 'da',
                'directory' => 'danish',
                'country_code' => 'DK'
            ],
            [
                'name' => 'Suomi',
                'code' => 'fi',
                'directory' => 'finnish',
                'country_code' => 'FI'
            ],
            [
                'name' => 'Ελληνικά',
                'code' => 'el',
                'directory' => 'greek',
                'country_code' => 'GR'
            ],
            [
                'name' => 'Čeština',
                'code' => 'cs',
                'directory' => 'czech',
                'country_code' => 'CZ'
            ],
            [
                'name' => 'Magyar',
                'code' => 'hu',
                'directory' => 'hungarian',
                'country_code' => 'HU'
            ],
            [
                'name' => 'Română',
                'code' => 'ro',
                'directory' => 'romanian',
                'country_code' => 'RO'
            ]
        ];
    }
}

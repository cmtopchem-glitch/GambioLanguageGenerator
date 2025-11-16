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

    /**
     * Locale-Mapping für alle unterstützten Sprachen
     */
    private $localeMap = [
        'de' => 'de_DE.utf8, de_DE.UTF-8, de_DE@euro, de_DE, de-DE, de, ge, German',
        'en' => 'en_US.utf8, en_US.UTF-8, en_EN@euro, en_US, en-US, en, English',
        'fr' => 'fr_FR.utf8, fr_FR.UTF-8, fr_FR@euro, fr_FR, fr-FR, fr, Français',
        'it' => 'it_IT.utf8, it_IT.UTF-8, it_IT@euro, it_IT, it-IT, it, Italiano',
        'es' => 'es_ES.utf8, es_ES.UTF-8, es_ES@euro, es_ES, es-ES, es, Spanish',
        'pl' => 'pl_PL.utf8, pl_PL.UTF-8, pl_PL, pl-PL, pl, Polish',
        'nl' => 'nl_NL.utf8, nl_NL.UTF-8, nl_NL@euro, nl_NL, nl-NL, nl, Dutch',
        'sv' => 'sv_SE.utf8, sv_SE.UTF-8, sv_SE, sv-SE, sv, Swedish',
        'da' => 'da_DK.utf8, da_DK.UTF-8, da_DK, da-DK, da, Danish',
        'fi' => 'fi_FI.utf8, fi_FI.UTF-8, fi_FI, fi-FI, fi, Finnish',
        'no' => 'nb_NO.utf8, nb_NO.UTF-8, nb_NO, nb-NO, no, Norwegian',
        'pt' => 'pt_PT.utf8, pt_PT.UTF-8, pt_PT, pt-PT, pt, Portuguese',
        'el' => 'el_GR.utf8, el_GR.UTF-8, el_GR, el-GR, el, Greek',
        'cs' => 'cs_CZ.utf8, cs_CZ.UTF-8, cs_CZ, cs-CZ, cs, Czech',
        'hu' => 'hu_HU.utf8, hu_HU.UTF-8, hu_HU, hu-HU, hu, Hungarian',
        'ro' => 'ro_RO.utf8, ro_RO.UTF-8, ro_RO, ro-RO, ro, Romanian',
        'bg' => 'bg_BG.utf8, bg_BG.UTF-8, bg_BG, bg-BG, bg, Bulgarian',
        'hr' => 'hr_HR.utf8, hr_HR.UTF-8, hr_HR, hr-HR, hr, Croatian',
        'sl' => 'sl_SI.utf8, sl_SI.UTF-8, sl_SI, sl-SI, sl, Slovenian',
        'sk' => 'sk_SK.utf8, sk_SK.UTF-8, sk_SK, sk-SK, sk, Slovak',
        'lt' => 'lt_LT.utf8, lt_LT.UTF-8, lt_LT, lt-LT, lt, Lithuanian',
        'lv' => 'lv_LV.utf8, lv_LV.UTF-8, lv_LV, lv-LV, lv, Latvian',
        'et' => 'et_EE.utf8, et_EE.UTF-8, et_EE, et-EE, et, Estonian',
        'mt' => 'mt_MT.utf8, mt_MT.UTF-8, mt_MT, mt-MT, mt, Maltese',
        'lb' => 'lb_LU.utf8, lb_LU.UTF-8, lb_LU, lb-LU, lb, Luxembourgish',
        'ga' => 'ga_IE.utf8, ga_IE.UTF-8, ga_IE, ga-IE, ga, Irish',
        'tr' => 'tr_TR.utf8, tr_TR.UTF-8, tr_TR, tr-TR, tr, Turkish',
        'ru' => 'ru_RU.utf8, ru_RU.UTF-8, ru_RU, ru-RU, ru, Russian',
        'uk' => 'uk_UA.utf8, uk_UA.UTF-8, uk_UA, uk-UA, uk, Ukrainian',
        'ar' => 'ar_SA.utf8, ar_SA.UTF-8, ar_SA, ar-SA, ar, Arabic',
        'he' => 'he_IL.utf8, he_IL.UTF-8, he_IL, he-IL, he, Hebrew',
        'zh' => 'zh_CN.utf8, zh_CN.UTF-8, zh_CN, zh-CN, zh, Chinese',
        'ja' => 'ja_JP.utf8, ja_JP.UTF-8, ja_JP, ja-JP, ja, Japanese',
        'ko' => 'ko_KR.utf8, ko_KR.UTF-8, ko_KR, ko-KR, ko, Korean',
        'th' => 'th_TH.utf8, th_TH.UTF-8, th_TH, th-TH, th, Thai',
        'vi' => 'vi_VN.utf8, vi_VN.UTF-8, vi_VN, vi-VN, vi, Vietnamese',
        'id' => 'id_ID.utf8, id_ID.UTF-8, id_ID, id-ID, id, Indonesian',
        'ms' => 'ms_MY.utf8, ms_MY.UTF-8, ms_MY, ms-MY, ms, Malay',
        'hi' => 'hi_IN.utf8, hi_IN.UTF-8, hi_IN, hi-IN, hi, Hindi',
        'pa' => 'pa_IN.utf8, pa_IN.UTF-8, pa_IN, pa-IN, pa, Punjabi',
        'bn' => 'bn_BD.utf8, bn_BD.UTF-8, bn_BD, bn-BD, bn, Bengali',
    ];
    
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
        $existingLanguage = null;
        if ($this->languageExists($directory)) {
            // Hole die existing language ID
            $query = "SELECT languages_id FROM {$this->languagesTable} WHERE directory = '$directory'";
            $result = xtc_db_query($query);
            if ($row = xtc_db_fetch_array($result)) {
                $existingLanguage = $row['languages_id'];
            }
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
        
        // Wenn Sprache bereits existiert, nutze die existing ID und update die Files
        // Wenn nicht, insert neue Sprache
        $languageId = null;

        if ($existingLanguage) {
            // Sprache existiert bereits - nutze die existing ID
            $languageId = $existingLanguage;
            // Optionally: update icon image path
            xtc_db_query("UPDATE {$this->languagesTable} SET image='$iconPath' WHERE languages_id=$languageId");
        } else {
            // Neue Sprache - insert in Datenbank
            if ($this->db && is_object($this->db)) {
                $query = "INSERT INTO {$this->languagesTable} (name, code, image, directory, sort_order, language_charset, status, status_admin, date_format, date_format_long, date_format_short, date_time_format, dob_format_string, html_params, language_currency, php_date_time_format) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $this->db->prepare($query);
                if (!$stmt) {
                    return [
                        'success' => false,
                        'message' => 'Fehler beim Vorbereiten der Query: ' . $this->db->error
                    ];
                }

                $utf8 = 'utf-8';
                $status = 0;
                $statusAdmin = 1;
                $dateFormat = 'd.m.Y';
                $dateFormatLong = 'l, d. F Y';
                $dateFormatShort = 'd.m.Y';
                $dateTimeFormat = 'd.m.Y H:i:s';
                $dobFormatString = 'tt.mm.jjjj';
                $htmlParams = 'dir="ltr" lang="en"';
                $languageCurrency = 'EUR';
                $phpDateTimeFormat = 'd.m.Y H:i:s';

                $stmt->bind_param(
                    'ssssissiisssisss',
                    $name, $code, $iconPath, $directory, $sortOrder,
                    $utf8, $status, $statusAdmin,
                    $dateFormat, $dateFormatLong, $dateFormatShort, $dateTimeFormat,
                    $dobFormatString, $htmlParams, $languageCurrency, $phpDateTimeFormat
                );

                if ($stmt->execute()) {
                    $languageId = $this->db->insert_id;
                } else {
                    return [
                        'success' => false,
                        'message' => 'Fehler beim Einfügen in Datenbank: ' . $stmt->error
                    ];
                }
                $stmt->close();
            } else {
                // Fallback zu xtc_db_query wenn mysqli nicht verfügbar
                $query = "INSERT INTO {$this->languagesTable} (name, code, image, directory, sort_order, language_charset, status, status_admin) VALUES ('$name', '$code', '$iconPath', '$directory', $sortOrder, 'utf-8', 0, 1)";
                if (xtc_db_query($query)) {
                    $languageId = xtc_db_insert_id();
                } else {
                    return [
                        'success' => false,
                        'message' => 'Fehler beim Einfügen in Datenbank'
                    ];
                }
            }
        }

        if ($languageId) {

            // Erstelle init.inc.php Dateien
            $this->createInitFiles($directory, $languageId, $code);

            // Erstelle index.html Dateien
            $this->createIndexFiles($directory);

            // Kopiere Mail-Templates von Referenz-Sprache
            $this->copyMailTemplates(1, $languageId, $directory); // 1 = English als Standard

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
            "lang/$directory/admin/images",
            "lang/$directory/modules",
            "lang/$directory/original_sections",
            "lang/$directory/original_mail_templates",
            "lang/$directory/user_sections",
            "lang/$directory/user_mail_templates"
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
     * @return string Icon-Dateiname
     */
    private function generateLanguageIcon($directory, $countryCode) {
        $basePath = DIR_FS_CATALOG;
        $iconFile = 'icon.gif';

        // Prüfe ob Länderflagge existiert
        $flagSources = [
            $basePath . "images/icons/flags/" . strtolower($countryCode) . ".png",
            $basePath . "images/icons/flags/" . strtoupper($countryCode) . ".png",
            $basePath . "images/flags/" . strtolower($countryCode) . ".gif",
            $basePath . "images/flags/" . strtoupper($countryCode) . ".gif"
        ];

        $flagFound = false;
        foreach ($flagSources as $flagSource) {
            if (file_exists($flagSource)) {
                // Erstelle icon.gif in lang/{dir}/admin/images/
                $adminIconDir = $basePath . 'lang/' . $directory . '/admin/images/';
                if (!is_dir($adminIconDir)) {
                    mkdir($adminIconDir, 0755, true);
                }
                $adminIconPath = $adminIconDir . $iconFile;

                // Erstelle icon.gif in lang/{dir}/
                $rootIconPath = $basePath . 'lang/' . $directory . '/' . $iconFile;

                // Kopiere oder konvertiere Flagge zu beiden Orten
                if (pathinfo($flagSource, PATHINFO_EXTENSION) === 'gif') {
                    copy($flagSource, $adminIconPath);
                    copy($flagSource, $rootIconPath);
                } else {
                    // PNG zu GIF konvertieren
                    $this->convertImageToGif($flagSource, $adminIconPath);
                    $this->convertImageToGif($flagSource, $rootIconPath);
                }

                // Erstelle auch flag.png in root language directory
                $flagPath = $basePath . 'lang/' . $directory . '/flag.png';
                if (!file_exists($flagPath)) {
                    if (pathinfo($flagSource, PATHINFO_EXTENSION) === 'png') {
                        copy($flagSource, $flagPath);
                    } else {
                        // GIF zu PNG konvertieren (einfach kopieren, da keine GIFs existieren)
                        copy($flagSource, $flagPath);
                    }
                }

                $flagFound = true;
                break;
            }
        }

        // Wenn keine Flagge gefunden, erstelle Standard-Icons
        if (!$flagFound) {
            $adminIconDir = $basePath . 'lang/' . $directory . '/admin/images/';
            if (!is_dir($adminIconDir)) {
                mkdir($adminIconDir, 0755, true);
            }
            $adminIconPath = $adminIconDir . $iconFile;
            $rootIconPath = $basePath . 'lang/' . $directory . '/' . $iconFile;

            $this->createDefaultIcon($adminIconPath, $countryCode);
            $this->createDefaultIcon($rootIconPath, $countryCode);
        }

        return $iconFile;
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
     * Erstellt init.inc.php Dateien für die neue Sprache
     */
    private function createInitFiles($directory, $languageId, $languageCode = 'en') {
        $basePath = DIR_FS_CATALOG;

        // Create root init.inc.php
        $rootInitContent = $this->getInitFileTemplate('root', $languageCode);
        $rootInitPath = $basePath . 'lang/' . $directory . '/init.inc.php';
        // Überschreibe existierende Datei, um sicherzustellen, dass richtige Locale gesetzt ist
        file_put_contents($rootInitPath, $rootInitContent);
        chmod($rootInitPath, 0644);

        // Create admin init.inc.php
        $adminInitContent = $this->getInitFileTemplate('admin', $languageCode);
        $adminInitPath = $basePath . 'lang/' . $directory . '/admin/init.inc.php';
        // Überschreibe existierende Datei, um sicherzustellen, dass richtige Locale gesetzt ist
        file_put_contents($adminInitPath, $adminInitContent);
        chmod($adminInitPath, 0644);
    }

    /**
     * Gibt init.inc.php Template zurück mit korrektem Locale für die Sprache
     */
    private function getInitFileTemplate($type = 'root', $languageCode = 'en') {
        // Hole Locale für die Sprache, Fallback zu Englisch
        $locale = $this->localeMap[$languageCode] ?? $this->localeMap['en'];

        if ($type === 'admin') {
            return "<?php
/* --------------------------------------------------------------
   init.inc.php
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2018 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   -------------------------------------------------------------- */

@setlocale(LC_TIME, '{$locale}');

\$db               = StaticGXCoreLoader::getDatabaseQueryBuilder();
\$languageSettings = \$db->select()
                       ->from('languages')
                       ->where('languages_id', \$_SESSION['languages_id'])
                       ->get()
                       ->row_array();
if(\$languageSettings !== null)
{
	define('DATE_FORMAT', \$languageSettings['date_format']);
	define('DATE_FORMAT_LONG', \$languageSettings['date_format_long']);
	define('DATE_FORMAT_SHORT', \$languageSettings['date_format_short']);
	define('DATE_TIME_FORMAT', \$languageSettings['date_time_format']);
	define('DOB_FORMAT_STRING', \$languageSettings['dob_format_string']);
	define('HTML_PARAMS', \$languageSettings['html_params']);
	define('LANGUAGE_CURRENCY', \$languageSettings['language_currency']);
	define('PHP_DATE_TIME_FORMAT', \$languageSettings['php_date_time_format']);
}

\$coo_lang_file_master->init_from_lang_file('admin_general');
\$coo_lang_file_master->init_from_lang_file('gm_general');
";
        } else {
            return "<?php
/* --------------------------------------------------------------
   init.inc.php 2022-07-27
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2022 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   -------------------------------------------------------------- */

@setlocale(LC_TIME, '{$locale}');

\$db               = StaticGXCoreLoader::getDatabaseQueryBuilder();
\$languageSettings = \$db->select()
                       ->from('languages')
                       ->where('languages_id', \$_SESSION['languages_id'])
                       ->get()
                       ->row_array();
if(\$languageSettings !== null)
{

    defined('DATE_FORMAT') ?: define('DATE_FORMAT', \$languageSettings['date_format']);
	defined('DATE_FORMAT_LONG') ?: define('DATE_FORMAT_LONG', \$languageSettings['date_format_long']);
	defined('DATE_FORMAT_SHORT') ?: define('DATE_FORMAT_SHORT', \$languageSettings['date_format_short']);
	defined('DATE_TIME_FORMAT') ?: define('DATE_TIME_FORMAT', \$languageSettings['date_time_format']);
	defined('DOB_FORMAT_STRING') ?: define('DOB_FORMAT_STRING', \$languageSettings['dob_format_string']);
	defined('HTML_PARAMS') ?: define('HTML_PARAMS', \$languageSettings['html_params']);
	defined('LANGUAGE_CURRENCY') ?: define('LANGUAGE_CURRENCY', \$languageSettings['language_currency']);
    defined('PHP_DATE_TIME_FORMAT') ?: define('PHP_DATE_TIME_FORMAT', \$languageSettings['php_date_time_format']);
}

\$coo_lang_file_master->init_from_lang_file('general');
\$coo_lang_file_master->init_from_lang_file('gm_logger');
\$coo_lang_file_master->init_from_lang_file('gm_shopping_cart');
\$coo_lang_file_master->init_from_lang_file('gm_account_delete');
\$coo_lang_file_master->init_from_lang_file('gm_price_offer');
\$coo_lang_file_master->init_from_lang_file('gm_tell_a_friend');
\$coo_lang_file_master->init_from_lang_file('gm_callback_service');
";
        }
    }

    /**
     * Kopiert Mail-Templates von einer anderen Sprache
     */
    private function copyMailTemplates($sourceLanguageId, $targetLanguageId, $targetDirectory) {
        $basePath = DIR_FS_CATALOG;

        // Hole Verzeichnis der Quellsprache
        $query = "SELECT directory FROM {$this->languagesTable} WHERE languages_id = $sourceLanguageId";
        $result = xtc_db_query($query);

        if (!$row = xtc_db_fetch_array($result)) {
            return; // Quellsprache nicht gefunden
        }

        $sourceDirectory = $row['directory'];

        // Kopiere original_mail_templates
        $sourceMailPath = $basePath . 'lang/' . $sourceDirectory . '/original_mail_templates';
        $targetMailPath = $basePath . 'lang/' . $targetDirectory . '/original_mail_templates';

        if (is_dir($sourceMailPath)) {
            $this->copyDirectory($sourceMailPath, $targetMailPath);
        }

        // Kopiere user_mail_templates
        $sourceUserMailPath = $basePath . 'lang/' . $sourceDirectory . '/user_mail_templates';
        $targetUserMailPath = $basePath . 'lang/' . $targetDirectory . '/user_mail_templates';

        if (is_dir($sourceUserMailPath)) {
            $this->copyDirectory($sourceUserMailPath, $targetUserMailPath);
        }
    }

    /**
     * Kopiert ein komplettes Verzeichnis rekursiv
     */
    private function copyDirectory($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $dir = opendir($source);
        while (false !== ($file = readdir($dir))) {
            if ($file != '.' && $file != '..') {
                $sourceFile = $source . '/' . $file;
                $destFile = $destination . '/' . $file;

                if (is_dir($sourceFile)) {
                    $this->copyDirectory($sourceFile, $destFile);
                } else {
                    copy($sourceFile, $destFile);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Erstellt index.html Datei in Verzeichnissen
     */
    private function createIndexFiles($directory) {
        $basePath = DIR_FS_CATALOG;
        $indexContent = '';

        $directories = [
            "lang/$directory",
            "lang/$directory/admin",
            "lang/$directory/user_mail_templates",
            "lang/$directory/user_sections",
            "lang/$directory/original_sections",
            "lang/$directory/original_mail_templates"
        ];

        foreach ($directories as $dir) {
            $indexPath = $basePath . $dir . '/index.html';
            if (!file_exists($indexPath)) {
                file_put_contents($indexPath, $indexContent);
            }
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

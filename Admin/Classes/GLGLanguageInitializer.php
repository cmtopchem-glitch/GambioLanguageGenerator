<?php
/**
 * Gambio Language Generator - Language Initializer
 *
 * Verwaltet 41 Sprachen mit automatischer Flag-Erkennung
 * 26 EU-Sprachen + 15 globale Sprachen
 *
 * @author Christian Mittenzwei
 * @version 1.0.0
 */

// Lade GLGLanguageManager
$initializerDir = dirname(__FILE__);
$includesDir = dirname($initializerDir) . '/../includes';
if (file_exists($includesDir . '/GLGLanguageManager.php')) {
    require_once($includesDir . '/GLGLanguageManager.php');
}

class GLGLanguageInitializer {

    /**
     * 41 verfügbare Sprachen (26 EU + 15 global) mit Datumsformaten und deutschen Namen
     */
    const AVAILABLE_LANGUAGES = [
        // EU-Sprachen (26)
        'de' => ['name' => 'Deutsch', 'directory' => 'german', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'tt.mm.jjjj', 'html_params' => 'dir="ltr" lang="de"'],
        'en' => ['name' => 'Englisch', 'directory' => 'english', 'date_format' => 'm/d/Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'm/d/Y', 'date_time_format' => 'm/d/Y H:i:s', 'dob_format' => 'mm/dd/yyyy', 'html_params' => 'dir="ltr" lang="en"'],
        'fr' => ['name' => 'Französisch', 'directory' => 'french', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'jj.mm.aaaa', 'html_params' => 'dir="ltr" lang="fr"'],
        'it' => ['name' => 'Italienisch', 'directory' => 'italian', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'gg.mm.aaaa', 'html_params' => 'dir="ltr" lang="it"'],
        'es' => ['name' => 'Spanisch', 'directory' => 'spanish', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.aaaa', 'html_params' => 'dir="ltr" lang="es"'],
        'pl' => ['name' => 'Polnisch', 'directory' => 'polish', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.rrrr', 'html_params' => 'dir="ltr" lang="pl"'],
        'nl' => ['name' => 'Niederländisch', 'directory' => 'dutch', 'date_format' => 'd-m-Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd-m-Y', 'date_time_format' => 'd-m-Y H:i:s', 'dob_format' => 'dd.mm.jjjj', 'html_params' => 'dir="ltr" lang="nl"'],
        'sv' => ['name' => 'Schwedisch', 'directory' => 'swedish', 'date_format' => 'Y-m-d', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'Y-m-d', 'date_time_format' => 'Y-m-d H:i:s', 'dob_format' => 'yyyy-mm-dd', 'html_params' => 'dir="ltr" lang="sv"'],
        'da' => ['name' => 'Dänisch', 'directory' => 'danish', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="da"'],
        'fi' => ['name' => 'Finnisch', 'directory' => 'finnish', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'pp.kk.vvvv', 'html_params' => 'dir="ltr" lang="fi"'],
        'no' => ['name' => 'Norwegisch', 'directory' => 'norwegian', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="no"'],
        'pt' => ['name' => 'Portugiesisch', 'directory' => 'portuguese', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.aaaa', 'html_params' => 'dir="ltr" lang="pt"'],
        'el' => ['name' => 'Griechisch', 'directory' => 'greek', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.eeee', 'html_params' => 'dir="ltr" lang="el"'],
        'cs' => ['name' => 'Tschechisch', 'directory' => 'czech', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.rrrr', 'html_params' => 'dir="ltr" lang="cs"'],
        'hu' => ['name' => 'Ungarisch', 'directory' => 'hungarian', 'date_format' => 'Y.m.d', 'date_format_long' => 'Y. F d., l', 'date_format_short' => 'Y.m.d', 'date_time_format' => 'Y.m.d H:i:s', 'dob_format' => 'yyyy.mm.dd', 'html_params' => 'dir="ltr" lang="hu"'],
        'ro' => ['name' => 'Rumänisch', 'directory' => 'romanian', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.aaaa', 'html_params' => 'dir="ltr" lang="ro"'],
        'bg' => ['name' => 'Bulgarisch', 'directory' => 'bulgarian', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="bg"'],
        'hr' => ['name' => 'Kroatisch', 'directory' => 'croatian', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="hr"'],
        'sl' => ['name' => 'Slowenisch', 'directory' => 'slovenian', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="sl"'],
        'sk' => ['name' => 'Slowakisch', 'directory' => 'slovak', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="sk"'],
        'lt' => ['name' => 'Litauisch', 'directory' => 'lithuanian', 'date_format' => 'Y-m-d', 'date_format_long' => 'Y m d d.', 'date_format_short' => 'Y-m-d', 'date_time_format' => 'Y-m-d H:i:s', 'dob_format' => 'yyyy-mm-dd', 'html_params' => 'dir="ltr" lang="lt"'],
        'lv' => ['name' => 'Lettisch', 'directory' => 'latvian', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="lv"'],
        'et' => ['name' => 'Estnisch', 'directory' => 'estonian', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="et"'],
        'mt' => ['name' => 'Maltesisch', 'directory' => 'maltese', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="mt"'],
        'lb' => ['name' => 'Luxemburgisch', 'directory' => 'luxembourgish', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="lb"'],
        'ga' => ['name' => 'Irisch', 'directory' => 'irish', 'date_format' => 'd/m/Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd/m/Y', 'date_time_format' => 'd/m/Y H:i:s', 'dob_format' => 'dd/mm/yyyy', 'html_params' => 'dir="ltr" lang="ga"'],

        // Globale Sprachen (15)
        'tr' => ['name' => 'Türkisch', 'directory' => 'turkish', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="tr"'],
        'ru' => ['name' => 'Russisch', 'directory' => 'russian', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="ru"'],
        'uk' => ['name' => 'Ukrainisch', 'directory' => 'ukrainian', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="uk"'],
        'ar' => ['name' => 'Arabisch', 'directory' => 'arabic', 'date_format' => 'Y-m-d', 'date_format_long' => 'd F Y l', 'date_format_short' => 'Y-m-d', 'date_time_format' => 'Y-m-d H:i:s', 'dob_format' => 'yyyy-mm-dd', 'html_params' => 'dir="rtl" lang="ar"'],
        'he' => ['name' => 'Hebräisch', 'directory' => 'hebrew', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="rtl" lang="he"'],
        'zh' => ['name' => 'Chinesisch', 'directory' => 'chinese', 'date_format' => 'Y-m-d', 'date_format_long' => 'Y年m月d日 l', 'date_format_short' => 'Y-m-d', 'date_time_format' => 'Y-m-d H:i:s', 'dob_format' => 'yyyy-mm-dd', 'html_params' => 'dir="ltr" lang="zh"'],
        'ja' => ['name' => 'Japanisch', 'directory' => 'japanese', 'date_format' => 'Y/m/d', 'date_format_long' => 'Y年m月d日 l', 'date_format_short' => 'Y/m/d', 'date_time_format' => 'Y/m/d H:i:s', 'dob_format' => 'yyyy/mm/dd', 'html_params' => 'dir="ltr" lang="ja"'],
        'ko' => ['name' => 'Koreanisch', 'directory' => 'korean', 'date_format' => 'Y-m-d', 'date_format_long' => 'Y년 m월 d일 l', 'date_format_short' => 'Y-m-d', 'date_time_format' => 'Y-m-d H:i:s', 'dob_format' => 'yyyy-mm-dd', 'html_params' => 'dir="ltr" lang="ko"'],
        'th' => ['name' => 'Thai', 'directory' => 'thai', 'date_format' => 'd/m/Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd/m/Y', 'date_time_format' => 'd/m/Y H:i:s', 'dob_format' => 'dd/mm/yyyy', 'html_params' => 'dir="ltr" lang="th"'],
        'vi' => ['name' => 'Vietnamesisch', 'directory' => 'vietnamese', 'date_format' => 'd/m/Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd/m/Y', 'date_time_format' => 'd/m/Y H:i:s', 'dob_format' => 'dd/mm/yyyy', 'html_params' => 'dir="ltr" lang="vi"'],
        'id' => ['name' => 'Indonesisch', 'directory' => 'indonesian', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="id"'],
        'ms' => ['name' => 'Malaiisch', 'directory' => 'malay', 'date_format' => 'd.m.Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd.m.Y', 'date_time_format' => 'd.m.Y H:i:s', 'dob_format' => 'dd.mm.yyyy', 'html_params' => 'dir="ltr" lang="ms"'],
        'hi' => ['name' => 'Hindi', 'directory' => 'hindi', 'date_format' => 'd-m-Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd-m-Y', 'date_time_format' => 'd-m-Y H:i:s', 'dob_format' => 'dd-mm-yyyy', 'html_params' => 'dir="ltr" lang="hi"'],
        'pa' => ['name' => 'Punjabi', 'directory' => 'punjabi', 'date_format' => 'd-m-Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd-m-Y', 'date_time_format' => 'd-m-Y H:i:s', 'dob_format' => 'dd-mm-yyyy', 'html_params' => 'dir="ltr" lang="pa"'],
        'bn' => ['name' => 'Bengalisch', 'directory' => 'bengali', 'date_format' => 'd-m-Y', 'date_format_long' => 'l, d. F Y', 'date_format_short' => 'd-m-Y', 'date_time_format' => 'd-m-Y H:i:s', 'dob_format' => 'dd-mm-yyyy', 'html_params' => 'dir="ltr" lang="bn"'],
    ];

    /**
     * Mapping von ISO-Code zu Flag-Datei
     * Format: 'iso-code' => 'flag-filename.png'
     */
    const LANGUAGE_TO_FLAG = [
        'de' => 'de.png',
        'en' => 'gb.png',
        'fr' => 'fr.png',
        'it' => 'it.png',
        'es' => 'es.png',
        'pl' => 'pl.png',
        'nl' => 'nl.png',
        'sv' => 'se.png',
        'da' => 'dk.png',
        'fi' => 'fi.png',
        'no' => 'no.png',
        'pt' => 'pt.png',
        'el' => 'gr.png',
        'cs' => 'cz.png',
        'hu' => 'hu.png',
        'ro' => 'ro.png',
        'bg' => 'bg.png',
        'hr' => 'hr.png',
        'sl' => 'si.png',
        'sk' => 'sk.png',
        'lt' => 'lt.png',
        'lv' => 'lv.png',
        'et' => 'ee.png',
        'mt' => 'mt.png',
        'lb' => 'lu.png',
        'ga' => 'ie.png',
        'tr' => 'tr.png',
        'ru' => 'ru.png',
        'uk' => 'ua.png',
        'ar' => 'sa.png',
        'he' => 'il.png',
        'zh' => 'cn.png',
        'ja' => 'jp.png',
        'ko' => 'kr.png',
        'th' => 'th.png',
        'vi' => 'vn.png',
        'id' => 'id.png',
        'ms' => 'my.png',
        'hi' => 'in.png',
        'pa' => 'in.png',
        'bn' => 'bd.png',
    ];

    /**
     * Gibt die Flag-Datei für einen ISO-Code zurück
     */
    public static function getFlagForLanguage($isoCode) {
        $isoCode = strtolower($isoCode);
        return self::LANGUAGE_TO_FLAG[$isoCode] ?? null;
    }

    /**
     * Gibt alle verfügbaren Sprachen zurück
     */
    public static function getAvailableLanguages() {
        return self::AVAILABLE_LANGUAGES;
    }

    /**
     * Erstellt eine neue Sprache
     */
    public static function initializeLanguage($isoCode, $flagFile = null) {
        $isoCode = strtolower(preg_replace('/[^a-z]/', '', $isoCode));

        if (!isset(self::AVAILABLE_LANGUAGES[$isoCode])) {
            return [
                'success' => false,
                'message' => 'Sprache nicht in Konfiguration vorhanden: ' . $isoCode
            ];
        }

        $languageData = self::AVAILABLE_LANGUAGES[$isoCode];

        // Auto-detect flag if not provided
        if (empty($flagFile)) {
            $flagFile = self::getFlagForLanguage($isoCode);
        }

        try {
            $manager = new GLGLanguageManager();

            // Erstelle die Sprache (GLGLanguageManager kümmert sich um existierende Sprachen)
            $result = $manager->createLanguage([
                'name' => $languageData['name'],
                'code' => $isoCode,
                'directory' => $languageData['directory'],
                'country_code' => strtoupper($isoCode),
                'flag' => $flagFile
            ]);

            // Prüfe ob createLanguage erfolgreich war
            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message']
                ];
            }

            // Hole die languages_id
            $languageId = $result['language_id'] ?? null;

            return [
                'success' => true,
                'languageDir' => $languageData['directory'],
                'languageName' => $languageData['name'],
                'languageId' => $languageId,
                'dateFormat' => $languageData['date_format'],
                'dateFormatLong' => $languageData['date_format_long'],
                'dateFormatShort' => $languageData['date_format_short'],
                'dateTimeFormat' => $languageData['date_time_format'],
                'dobFormat' => $languageData['dob_format'],
                'htmlParams' => $languageData['html_params'],
                'phpDateTimeFormat' => $languageData['date_time_format'],
                'message' => 'Sprache erstellt: ' . $languageData['name']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Erstellen der Sprache: ' . $e->getMessage()
            ];
        }
    }
}
?>

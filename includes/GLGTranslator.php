<?php
/**
 * Gambio Language Generator - Translator
 * 
 * Übersetzt Sprachdaten via OpenAI, DeepL oder Google Translate
 * 
 * @author Christian Mittenzwei
 * @version 1.0.0
 */

class GLGTranslator {
    
    private $apiProvider;
    private $apiKey;
    private $model;
    private $temperature;
    private $maxTokens;
    
    public function __construct($settings) {
        $this->apiProvider = $settings['apiProvider'] ?? 'openai';
        $this->apiKey = $settings['apiKey'] ?? '';
        $this->model = $settings['model'] ?? 'gpt-4o';
        $this->temperature = floatval($settings['temperature'] ?? 0.3);
        $this->maxTokens = intval($settings['maxTokens'] ?? 4000);
    }
    
    /**
     * Übersetzt einen Batch von Spracheinträgen
     * 
     * @param array $entries Array von Key => Value Paaren
     * @param string $sourceLanguage Quellsprache (z.B. 'german')
     * @param string $targetLanguage Zielsprache (z.B. 'english')
     * @param string $context Kontext (Dateiname, Modul, etc.)
     * @return array Übersetzte Einträge
     */
    public function translateBatch($entries, $sourceLanguage, $targetLanguage, $context = '') {
        switch ($this->apiProvider) {
            case 'openai':
                return $this->translateWithOpenAI($entries, $sourceLanguage, $targetLanguage, $context);
            case 'deepl':
                return $this->translateWithDeepL($entries, $sourceLanguage, $targetLanguage);
            case 'google':
                return $this->translateWithGoogle($entries, $sourceLanguage, $targetLanguage);
            default:
                throw new Exception('Unbekannter API-Provider: ' . $this->apiProvider);
        }
    }
    
    /**
     * Übersetzt mit OpenAI
     */
    private function translateWithOpenAI($entries, $sourceLanguage, $targetLanguage, $context = '') {
        $sourceLanguageName = $this->getLanguageName($sourceLanguage);
        $targetLanguageName = $this->getLanguageName($targetLanguage);
        
        // Erstelle JSON für die Übersetzung
        $sourceJson = json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // System Prompt
        $systemPrompt = "Du bist ein professioneller Übersetzer für E-Commerce Software. 
Übersetze die folgenden Sprachvariablen von $sourceLanguageName nach $targetLanguageName.

WICHTIGE REGELN:
1. Behalte die JSON-Struktur EXAKT bei
2. Übersetze NUR die Werte, NICHT die Keys
3. Behalte Platzhalter wie %s, {name}, [value] etc. bei
4. Behalte HTML-Tags bei: <br>, <strong>, <span> etc.
5. Achte auf den E-Commerce Kontext
6. Sei konsistent bei Fachbegriffen
7. Antworte NUR mit dem übersetzten JSON, keine Erklärungen

Kontext: $context";

        $userPrompt = "Übersetze diese Sprachvariablen:\n\n$sourceJson";
        
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $error = json_decode($response, true);
            throw new Exception('OpenAI API Fehler: ' . ($error['error']['message'] ?? 'Unbekannter Fehler'));
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('Ungültige OpenAI Response');
        }
        
        $translatedText = $result['choices'][0]['message']['content'];
        
        // Entferne mögliche Markdown Code-Blöcke
        $translatedText = preg_replace('/```json\s*/', '', $translatedText);
        $translatedText = preg_replace('/```\s*$/', '', $translatedText);
        $translatedText = trim($translatedText);
        
        $translated = json_decode($translatedText, true);
        
        if (!is_array($translated)) {
            throw new Exception('Übersetzung konnte nicht als JSON geparst werden');
        }
        
        return $translated;
    }
    
    /**
     * Übersetzt mit DeepL (TODO)
     */
    private function translateWithDeepL($entries, $sourceLanguage, $targetLanguage) {
        // DeepL unterstützt nur einzelne Texte, nicht JSON
        // Daher müssen wir jeden Eintrag einzeln übersetzen
        
        $targetLang = $this->getDeepLLanguageCode($targetLanguage);
        $url = 'https://api-free.deepl.com/v2/translate';
        
        $translated = [];
        
        foreach ($entries as $key => $text) {
            $data = [
                'auth_key' => $this->apiKey,
                'text' => $text,
                'target_lang' => $targetLang,
                'preserve_formatting' => 1,
                'tag_handling' => 'html'
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('DeepL API Fehler');
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['translations'][0]['text'])) {
                $translated[$key] = $result['translations'][0]['text'];
            } else {
                $translated[$key] = $text; // Fallback
            }
            
            // Rate limiting
            usleep(100000); // 100ms zwischen Anfragen
        }
        
        return $translated;
    }
    
    /**
     * Übersetzt mit Google Translate (TODO)
     */
    private function translateWithGoogle($entries, $sourceLanguage, $targetLanguage) {
        $targetLang = $this->getGoogleLanguageCode($targetLanguage);
        $url = 'https://translation.googleapis.com/language/translate/v2';
        
        $translated = [];
        
        foreach ($entries as $key => $text) {
            $data = [
                'q' => $text,
                'target' => $targetLang,
                'format' => 'html',
                'key' => $this->apiKey
            ];
            
            $ch = curl_init($url . '?' . http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('Google Translate API Fehler');
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['data']['translations'][0]['translatedText'])) {
                $translated[$key] = $result['data']['translations'][0]['translatedText'];
            } else {
                $translated[$key] = $text; // Fallback
            }
            
            usleep(50000); // 50ms zwischen Anfragen
        }
        
        return $translated;
    }
    
    /**
     * Konvertiert Gambio-Sprachverzeichnis zu echtem Sprachnamen
     */
    private function getLanguageName($directory) {
        $mapping = [
            'german' => 'Deutsch',
            'english' => 'English',
            'spanish' => 'Español',
            'french' => 'Français',
            'italian' => 'Italiano',
            'dutch' => 'Nederlands',
            'polish' => 'Polski',
            'russian' => 'Русский',
            'turkish' => 'Türkçe',
            'chinese' => '中文',
            'japanese' => '日本語'
        ];
        
        return $mapping[$directory] ?? ucfirst($directory);
    }
    
    /**
     * Konvertiert Gambio-Sprachverzeichnis zu DeepL Code
     */
    private function getDeepLLanguageCode($directory) {
        $mapping = [
            'german' => 'DE',
            'english' => 'EN',
            'spanish' => 'ES',
            'french' => 'FR',
            'italian' => 'IT',
            'dutch' => 'NL',
            'polish' => 'PL',
            'russian' => 'RU',
            'turkish' => 'TR',
            'chinese' => 'ZH',
            'japanese' => 'JA'
        ];
        
        return $mapping[$directory] ?? strtoupper(substr($directory, 0, 2));
    }
    
    /**
     * Konvertiert Gambio-Sprachverzeichnis zu Google Code
     */
    private function getGoogleLanguageCode($directory) {
        $mapping = [
            'german' => 'de',
            'english' => 'en',
            'spanish' => 'es',
            'french' => 'fr',
            'italian' => 'it',
            'dutch' => 'nl',
            'polish' => 'pl',
            'russian' => 'ru',
            'turkish' => 'tr',
            'chinese' => 'zh',
            'japanese' => 'ja'
        ];
        
        return $mapping[$directory] ?? substr($directory, 0, 2);
    }
    
    /**
     * Optimiert die Batch-Größe basierend auf geschätzten Tokens
     * 
     * @param array $entries Einträge
     * @return array Batches
     */
    public function createOptimalBatches($entries) {
        $batches = [];
        $currentBatch = [];
        $estimatedTokens = 0;
        
        // Schätze ca. 1 Token pro 4 Zeichen (konservativ)
        $maxBatchTokens = $this->maxTokens * 0.7; // 70% für Input, Rest für Output
        
        foreach ($entries as $key => $value) {
            $entryTokens = strlen($key . $value) / 4;
            
            if ($estimatedTokens + $entryTokens > $maxBatchTokens && !empty($currentBatch)) {
                $batches[] = $currentBatch;
                $currentBatch = [];
                $estimatedTokens = 0;
            }
            
            $currentBatch[$key] = $value;
            $estimatedTokens += $entryTokens;
        }
        
        if (!empty($currentBatch)) {
            $batches[] = $currentBatch;
        }
        
        return $batches;
    }
}

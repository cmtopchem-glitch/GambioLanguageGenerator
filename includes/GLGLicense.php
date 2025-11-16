<?php
/**
 * Gambio Language Generator - License Handler
 * 
 * @author Christian Mittenzwei
 * @version 1.0.0
 */

class GLGLicense {
    
    private $licenseKey;
    private $licensedUrl;
    private $validationUrl = 'https://license.redozone.de/validate.php';
    private $cacheFile = DIR_FS_CATALOG . 'cache/glg_license.cache';
    private $cacheTime = 86400; // 24 Stunden
    
    public function __construct() {
        $this->loadLicenseKey();
        $this->licensedUrl = $this->getCurrentUrl();
    }
    
    /**
     * Prüft ob die Lizenz gültig ist
     */
    public function isValid() {
        // Cache prüfen
        if ($this->isCacheValid()) {
            return $this->getCachedValidation();
        }
        
        // Online-Validierung
        $isValid = $this->validateOnline();
        $this->cacheValidation($isValid);
        
        return $isValid;
    }
    
    /**
     * Lädt den Lizenzschlüssel aus der Datenbank
     */
    private function loadLicenseKey() {
        $query = xtc_db_query("
            SELECT configuration_value 
            FROM configuration 
            WHERE configuration_key = 'GLG_LICENSE_KEY'
        ");
        
        if ($row = xtc_db_fetch_array($query)) {
            $this->licenseKey = $row['configuration_value'];
        }
    }
    
    /**
     * Ermittelt die aktuelle URL
     */
    private function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host;
    }
    
    /**
     * Validiert die Lizenz online
     */
    private function validateOnline() {
        if (empty($this->licenseKey)) {
            return false;
        }
        
        $postData = [
            'license_key' => $this->licenseKey,
            'url' => $this->licensedUrl,
            'product' => 'gambio_language_generator',
            'version' => '1.0.0'
        ];
        
        $ch = curl_init($this->validationUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return false;
        }
        
        $data = json_decode($response, true);
        return isset($data['valid']) && $data['valid'] === true;
    }
    
    /**
     * Prüft ob der Cache gültig ist
     */
    private function isCacheValid() {
        if (!file_exists($this->cacheFile)) {
            return false;
        }
        
        $cacheAge = time() - filemtime($this->cacheFile);
        return $cacheAge < $this->cacheTime;
    }
    
    /**
     * Liest die gecachte Validierung
     */
    private function getCachedValidation() {
        $content = file_get_contents($this->cacheFile);
        $data = json_decode($content, true);
        return isset($data['valid']) && $data['valid'] === true;
    }
    
    /**
     * Speichert die Validierung im Cache
     */
    private function cacheValidation($isValid) {
        $data = [
            'valid' => $isValid,
            'timestamp' => time()
        ];
        
        file_put_contents($this->cacheFile, json_encode($data));
    }
    
    /**
     * Gibt den Lizenzschlüssel zurück
     */
    public function getLicenseKey() {
        return $this->licenseKey;
    }
    
    /**
     * Gibt die lizenzierte URL zurück
     */
    public function getLicensedUrl() {
        return $this->licensedUrl;
    }
    
    /**
     * Löscht den Lizenz-Cache
     */
    public function clearCache() {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
}

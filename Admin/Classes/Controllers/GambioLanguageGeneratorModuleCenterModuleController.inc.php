<?php

/**
 * Gambio Language Generator - Admin Controller
 * 
 * @category   GXModule
 * @package    GambioLanguageGenerator
 * @version    1.0.0
 */

class GambioLanguageGeneratorModuleCenterModuleController extends AdminHttpViewController
{
    /**
     * Default action - show module interface
     */
    public function actionDefault()
    {
        // Basis-Pfad zum Modul
        $modulePath = dirname(dirname(dirname(__FILE__)));

        // Sprachdatei laden
        $language = $_SESSION['language'] ?? 'german';
        $langFile = $modulePath . '/lang/' . $language . '/glg.php';

        if (file_exists($langFile)) {
            include($langFile);
        }

        // Lade vereinfachtes Admin-Interface (ohne doppelte Includes)
        ob_start();
        $adminFile = $modulePath . '/admin/glg_admin_simple.php';
        if (file_exists($adminFile)) {
            include($adminFile);
        } else {
            echo '<div class="alert alert-danger">Admin-Interface nicht gefunden: ' . $adminFile . '</div>';
            echo '<p>Modulepath: ' . $modulePath . '</p>';
        }
        $html = ob_get_clean();

        // Erstelle korrektes Response-Objekt für Gambio
        return MainFactory::create('AdminLayoutHttpControllerResponse', $html);
    }
}

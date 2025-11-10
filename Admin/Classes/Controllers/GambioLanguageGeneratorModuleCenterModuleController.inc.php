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
        // Sehr einfache Test-Version ohne Datenbank-Zugriffe
        $html = '<div style="padding: 20px;">';
        $html .= '<h1>Gambio Language Generator</h1>';
        $html .= '<p>Modul erfolgreich geladen!</p>';
        $html .= '<div class="alert alert-success">Der Controller funktioniert.</div>';
        $html .= '</div>';

        // Erstelle korrektes Response-Objekt für Gambio
        return MainFactory::create('AdminLayoutHttpControllerResponse', $html);
    }
}

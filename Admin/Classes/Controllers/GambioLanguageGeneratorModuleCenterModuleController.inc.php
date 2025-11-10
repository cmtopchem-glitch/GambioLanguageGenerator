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

        // HTML direkt hier erstellen, ohne externe Includes
        ob_start();
        $this->renderInterface($modulePath);
        $html = ob_get_clean();

        // Erstelle korrektes Response-Objekt für Gambio
        return MainFactory::create('AdminLayoutHttpControllerResponse', $html);
    }

    /**
     * Rendert das Admin-Interface
     */
    private function renderInterface($modulePath)
    {
        // Hole verfügbare Sprachen
        $db = StaticGXCoreLoader::getDatabaseQueryBuilder();
        $languages = $db->get('languages')->result_array();

        ?>
        <div class="glg-container" style="padding: 20px;">

            <div class="page-header">
                <h1>
                    <?php echo defined('GLG_TITLE') ? GLG_TITLE : 'Gambio Language Generator'; ?>
                    <small><?php echo defined('GLG_SUBTITLE') ? GLG_SUBTITLE : 'Automatische Generierung von Sprachdateien'; ?></small>
                </h1>
            </div>

            <!-- Bootstrap Tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#generate" role="tab" data-toggle="tab">
                        <?php echo defined('GLG_TAB_GENERATE') ? GLG_TAB_GENERATE : 'Sprachen generieren'; ?>
                    </a>
                </li>
                <li role="presentation">
                    <a href="#compare" role="tab" data-toggle="tab">
                        <?php echo defined('GLG_TAB_COMPARE') ? GLG_TAB_COMPARE : 'Vergleichen'; ?>
                    </a>
                </li>
                <li role="presentation">
                    <a href="#settings" role="tab" data-toggle="tab">
                        <?php echo defined('GLG_TAB_SETTINGS') ? GLG_TAB_SETTINGS : 'Einstellungen'; ?>
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" style="padding: 20px; background: #fff; border: 1px solid #ddd; border-top: none;">

                <!-- Generate Tab -->
                <div role="tabpanel" class="tab-pane active" id="generate">
                    <form id="glgGenerateForm" class="form-horizontal">

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Quellsprache</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="sourceLanguage" id="glg-source-language">
                                    <option value="">Bitte wählen...</option>
                                    <?php foreach ($languages as $lang): ?>
                                        <option value="<?php echo $lang['directory']; ?>"
                                                <?php echo ($lang['directory'] == 'german') ? 'selected' : ''; ?>>
                                            <?php echo $lang['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Zielsprachen</label>
                            <div class="col-sm-10">
                                <?php foreach ($languages as $lang): ?>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="targetLanguages[]"
                                                   value="<?php echo $lang['directory']; ?>">
                                            <?php echo $lang['name']; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa fa-play"></i> Sprachen generieren
                                </button>
                            </div>
                        </div>

                    </form>

                    <div id="glg-generate-messages"></div>
                </div>

                <!-- Compare Tab -->
                <div role="tabpanel" class="tab-pane" id="compare">
                    <h3>Sprachen vergleichen</h3>
                    <p class="help-block">Zeigt fehlende Übersetzungen in der Zielsprache an</p>
                    <div id="glg-compare-messages">
                        <div class="alert alert-info">
                            Diese Funktion wird in Kürze implementiert.
                        </div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div role="tabpanel" class="tab-pane" id="settings">
                    <form id="glgSettingsForm" class="form-horizontal">

                        <h3>API-Einstellungen</h3>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">API Provider</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="apiProvider">
                                    <option value="openai">OpenAI</option>
                                    <option value="deepl">DeepL</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">API Key</label>
                            <div class="col-sm-6">
                                <input type="password" class="form-control" name="apiKey" id="glg-api-key">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Modell</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="model">
                                    <option value="gpt-4o">GPT-4o</option>
                                    <option value="gpt-4o-mini">GPT-4o Mini</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fa fa-save"></i> Einstellungen speichern
                                </button>
                                <button type="button" class="btn btn-info" id="glg-test-api">
                                    <i class="fa fa-check"></i> API testen
                                </button>
                            </div>
                        </div>

                    </form>

                    <div id="glg-settings-messages"></div>
                </div>

            </div>
        </div>

        <script>
        $(document).ready(function() {

            // Generate Form
            $('#glgGenerateForm').on('submit', function(e) {
                e.preventDefault();

                var sourceLanguage = $('#glg-source-language').val();
                var targetLanguages = $('input[name="targetLanguages[]"]:checked').map(function() {
                    return $(this).val();
                }).get();

                if (!sourceLanguage) {
                    showMessage('glg-generate-messages', 'danger', 'Bitte Quellsprache auswählen');
                    return;
                }

                if (targetLanguages.length === 0) {
                    showMessage('glg-generate-messages', 'danger', 'Bitte mindestens eine Zielsprache auswählen');
                    return;
                }

                showMessage('glg-generate-messages', 'info', 'Generierung wird gestartet...');

                // AJAX Call wird hier implementiert
            });

            // Settings Form
            $('#glgSettingsForm').on('submit', function(e) {
                e.preventDefault();
                showMessage('glg-settings-messages', 'success', 'Einstellungen gespeichert');
            });

            // API Test
            $('#glg-test-api').on('click', function() {
                showMessage('glg-settings-messages', 'info', 'API wird getestet...');
            });

            function showMessage(containerId, type, message) {
                var html = '<div class="alert alert-' + type + ' alert-dismissible">';
                html += '<button type="button" class="close" data-dismiss="alert">&times;</button>';
                html += message;
                html += '</div>';

                $('#' + containerId).html(html);

                if (type === 'success') {
                    setTimeout(function() {
                        $('#' + containerId + ' .alert').fadeOut();
                    }, 5000);
                }
            }
        });
        </script>
        <?php
    }
}

<?php
if (!class_exists('GambioLanguageGeneratorModuleCenterModuleController')) {
class GambioLanguageGeneratorModuleCenterModuleController extends AbstractModuleCenterModuleController
{
    protected function _init()
    {
    }

    public function actionDefault()
    {
        // Debug: Zeige welche Action aufgerufen wird
        $action = $this->_getQueryParameter('action');
        error_log('GLG: actionDefault() called, action parameter: ' . ($action ? $action : 'none'));

        // Handle sub-actions manually
        if ($action === 'save') {
            error_log('GLG: Routing to actionSave()');
            return $this->actionSave();
        }
        if ($action === 'generate') {
            error_log('GLG: Routing to actionGenerate()');
            return $this->actionGenerate();
        }
        if ($action === 'compare') {
            error_log('GLG: Routing to actionCompare()');
            return $this->actionCompare();
        }

        $this->pageTitle = 'Gambio Language Generator';

        // Stelle sicher, dass die Settings-Tabelle existiert
        $this->_ensureTablesExist();

        // Hole verfügbare Sprachen
        $languages = array();
        $query = "SELECT * FROM languages ORDER BY sort_order";
        $result = xtc_db_query($query);
        while ($lang = xtc_db_fetch_array($result)) {
            $languages[] = $lang;
        }

        // Lade gespeicherte Einstellungen
        $apiProvider = 'openai';
        $apiKey = '';
        $model = 'gpt-4o';

        // Prüfe ob Tabelle existiert und lade Settings
        $tableCheck = xtc_db_query("SHOW TABLES LIKE 'rz_glg_settings'");
        if (xtc_db_num_rows($tableCheck) > 0) {
            $query = "SELECT setting_key, setting_value FROM rz_glg_settings WHERE setting_key IN ('apiProvider', 'apiKey', 'model')";
            $result = xtc_db_query($query);

            error_log('GLG: Loading settings, found ' . xtc_db_num_rows($result) . ' rows');

            while ($row = xtc_db_fetch_array($result)) {
                error_log('GLG: Loaded setting: ' . $row['setting_key'] . ' = ' . substr($row['setting_value'], 0, 10) . '...');

                if ($row['setting_key'] == 'apiProvider') {
                    $apiProvider = $row['setting_value'];
                }
                if ($row['setting_key'] == 'apiKey') {
                    $apiKey = $row['setting_value'];
                }
                if ($row['setting_key'] == 'model') {
                    $model = $row['setting_value'];
                }
            }
        } else {
            error_log('GLG: Settings table does not exist yet');
        }

        error_log('GLG: Settings loaded successfully, about to render interface');

        $success = $this->_getQueryParameter('success') == '1';
        $error = $this->_getQueryParameter('error') == '1';

        error_log('GLG: Query params - success: ' . ($success ? 'true' : 'false') . ', error: ' . ($error ? 'true' : 'false'));

        // Erstelle HTML direkt (ohne Smarty Template erstmal)
        error_log('GLG: Calling _renderInterface()');
        $html = $this->_renderInterface($languages, $apiProvider, $apiKey, $model, $success, $error);

        error_log('GLG: _renderInterface() returned, HTML length: ' . strlen($html));

        // Output HTML directly and exit - ModuleCenterModule pattern
        error_log('GLG: Outputting HTML and exiting');
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }

    private function _renderInterface($languages, $apiProvider, $apiKey, $model, $success, $error)
    {
        error_log('GLG: _renderInterface() START - Languages count: ' . count($languages));
        ob_start();
        ?>
        <div class="glg-container" style="padding: 20px;">

            <?php if ($success): ?>
                <div class="alert alert-success">Einstellungen erfolgreich gespeichert!</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">Fehler beim Speichern der Einstellungen!</div>
            <?php endif; ?>

            <!-- Bootstrap Tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#generate" role="tab" data-toggle="tab">Sprachen generieren</a>
                </li>
                <li role="presentation">
                    <a href="#compare" role="tab" data-toggle="tab">Vergleichen</a>
                </li>
                <li role="presentation">
                    <a href="#settings" role="tab" data-toggle="tab">Einstellungen</a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" style="padding: 20px; background: #fff; border: 1px solid #ddd; border-top: none; margin-bottom: 20px;">

                <!-- Generate Tab -->
                <div role="tabpanel" class="tab-pane active" id="generate">
                    <form id="glgGenerateForm" class="form-horizontal" method="post" action="admin.php?do=GambioLanguageGeneratorModuleCenterModule&action=generate">

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Quellsprache</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="sourceLanguage" id="glg-source-language">
                                    <option value="">Bitte wählen...</option>
                                    <?php foreach ($languages as $lang): ?>
                                        <option value="<?php echo htmlspecialchars($lang['directory']); ?>"
                                                <?php echo ($lang['directory'] == 'german') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($lang['name']); ?>
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
                                                   value="<?php echo htmlspecialchars($lang['directory']); ?>">
                                            <?php echo htmlspecialchars($lang['name']); ?>
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
                    <p class="text-muted">Vergleiche die Vollständigkeit der Übersetzungen zwischen zwei Sprachen.</p>

                    <form id="glgCompareForm" class="form-horizontal" method="post" action="admin.php?do=GambioLanguageGeneratorModuleCenterModule&action=compare">

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Quellsprache</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="sourceLanguage" id="glg-compare-source-language">
                                    <option value="">Bitte wählen...</option>
                                    <?php foreach ($languages as $lang): ?>
                                        <option value="<?php echo htmlspecialchars($lang['directory']); ?>"
                                                <?php echo ($lang['directory'] == 'german') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($lang['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Zielsprache</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="targetLanguage" id="glg-compare-target-language">
                                    <option value="">Bitte wählen...</option>
                                    <?php foreach ($languages as $lang): ?>
                                        <option value="<?php echo htmlspecialchars($lang['directory']); ?>">
                                            <?php echo htmlspecialchars($lang['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-info btn-lg">
                                    <i class="fa fa-search"></i> Vergleichen
                                </button>
                            </div>
                        </div>

                    </form>

                    <div id="glg-compare-messages"></div>
                    <div id="glg-compare-results" style="display: none; margin-top: 20px;">
                        <h4>Ergebnisse</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="panel panel-default">
                                    <div class="panel-body text-center">
                                        <h2 id="glg-source-count">-</h2>
                                        <p>Einträge in Quellsprache</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="panel panel-default">
                                    <div class="panel-body text-center">
                                        <h2 id="glg-target-count">-</h2>
                                        <p>Einträge in Zielsprache</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="panel panel-danger">
                                    <div class="panel-body text-center">
                                        <h2 id="glg-missing-count">-</h2>
                                        <p>Fehlende Übersetzungen</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="panel panel-success">
                                    <div class="panel-body text-center">
                                        <h2 id="glg-completion">-</h2>
                                        <p>Vollständigkeit</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="progress">
                            <div class="progress-bar progress-bar-success" id="glg-progress-bar" style="width: 0%">
                                <span id="glg-progress-text">0%</span>
                            </div>
                        </div>

                        <div id="glg-missing-details"></div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div role="tabpanel" class="tab-pane" id="settings">
                    <form id="glgSettingsForm" class="form-horizontal" method="post" action="admin.php?do=GambioLanguageGeneratorModuleCenterModule&action=save">

                        <h3>API-Einstellungen</h3>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">API Provider</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="apiProvider">
                                    <option value="openai" <?php echo ($apiProvider == 'openai') ? 'selected' : ''; ?>>OpenAI</option>
                                    <option value="deepl" <?php echo ($apiProvider == 'deepl') ? 'selected' : ''; ?>>DeepL</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">API Key</label>
                            <div class="col-sm-6">
                                <input type="password" class="form-control" name="apiKey" id="glg-api-key" value="<?php echo htmlspecialchars($apiKey); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Modell</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="model">
                                    <option value="gpt-4o" <?php echo ($model == 'gpt-4o') ? 'selected' : ''; ?>>GPT-4o</option>
                                    <option value="gpt-4o-mini" <?php echo ($model == 'gpt-4o-mini') ? 'selected' : ''; ?>>GPT-4o Mini</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fa fa-save"></i> Einstellungen speichern
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

            </div>
        </div>

        <!-- JavaScript für dynamische Interaktionen -->
        <script>
        jQuery(document).ready(function($) {
            // Funktion: Entferne Quellsprache aus Zielsprachen
            function updateTargetLanguages() {
                var sourceLanguage = $('#glg-source-language').val();

                // Alle Checkboxen wieder aktivieren
                $('input[name="targetLanguages[]"]').prop('disabled', false);
                $('input[name="targetLanguages[]"]').closest('label').removeClass('text-muted');

                // Quellsprache deaktivieren
                if (sourceLanguage) {
                    $('input[name="targetLanguages[]"][value="' + sourceLanguage + '"]').prop('disabled', true);
                    $('input[name="targetLanguages[]"][value="' + sourceLanguage + '"]').prop('checked', false);
                    $('input[name="targetLanguages[]"][value="' + sourceLanguage + '"]').closest('label').addClass('text-muted');
                }
            }

            // Event Listener für Quellsprache
            $('#glg-source-language').on('change', updateTargetLanguages);

            // Bei Seitenladung ausführen
            updateTargetLanguages();

            // Gleiches für Compare Tab
            function updateCompareTargetLanguage() {
                var sourceLanguage = $('#glg-compare-source-language').val();

                // Alle Optionen wieder aktivieren
                $('#glg-compare-target-language option').prop('disabled', false);

                // Quellsprache deaktivieren
                if (sourceLanguage) {
                    $('#glg-compare-target-language option[value="' + sourceLanguage + '"]').prop('disabled', true);

                    // Wenn die deaktivierte Option ausgewählt war, zurücksetzen
                    if ($('#glg-compare-target-language').val() === sourceLanguage) {
                        $('#glg-compare-target-language').val('');
                    }
                }
            }

            $('#glg-compare-source-language').on('change', updateCompareTargetLanguage);
            updateCompareTargetLanguage();

            // Generate Form Handler
            $('#glgGenerateForm').on('submit', function(e) {
                e.preventDefault();

                var sourceLanguage = $('#glg-source-language').val();
                var targetLanguages = [];
                $('input[name="targetLanguages[]"]:checked').each(function() {
                    targetLanguages.push($(this).val());
                });

                if (!sourceLanguage) {
                    alert('Bitte wählen Sie eine Quellsprache aus');
                    return;
                }

                if (targetLanguages.length === 0) {
                    alert('Bitte wählen Sie mindestens eine Zielsprache aus');
                    return;
                }

                $('#glg-generate-messages').html('<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Übersetzung läuft... Dies kann einige Minuten dauern.</div>');

                $.ajax({
                    url: 'admin.php?do=GambioLanguageGeneratorModuleCenterModule&action=generate',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        sourceLanguage: sourceLanguage,
                        targetLanguages: targetLanguages
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<div class="alert alert-success"><strong>Erfolg!</strong> ' + response.message + '</div>';
                            html += '<table class="table table-striped"><thead><tr><th>Sprache</th><th>Anzahl Einträge</th></tr></thead><tbody>';
                            $.each(response.results, function(i, result) {
                                html += '<tr><td>' + result.language + '</td><td>' + result.count + '</td></tr>';
                            });
                            html += '</tbody></table>';
                            $('#glg-generate-messages').html(html);
                        } else {
                            $('#glg-generate-messages').html('<div class="alert alert-danger"><strong>Fehler:</strong> ' + response.error + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#glg-generate-messages').html('<div class="alert alert-danger"><strong>Fehler:</strong> ' + error + '</div>');
                    }
                });
            });

            // Compare Form Handler
            $('#glgCompareForm').on('submit', function(e) {
                e.preventDefault();

                var sourceLanguage = $('#glg-compare-source-language').val();
                var targetLanguage = $('#glg-compare-target-language').val();
                if (!sourceLanguage) {
                    alert('Bitte wählen Sie eine Quellsprache aus');
                    return;
                }

                if (!targetLanguage) {
                    alert('Bitte wählen Sie eine Zielsprache aus');
                    return;
                }

                $('#glg-compare-messages').html('<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Vergleiche Sprachen...</div>');
                $('#glg-compare-results').hide();

                $.ajax({
                    url: 'admin.php?do=GambioLanguageGeneratorModuleCenterModule&action=compare',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        sourceLanguage: sourceLanguage,
                        targetLanguage: targetLanguage
                    },
                    success: function(response) {
                        $('#glg-compare-messages').html('');

                        if (response.success) {
                            $('#glg-source-count').text(response.sourceCount);
                            $('#glg-target-count').text(response.targetCount);
                            $('#glg-missing-count').text(response.missingCount);
                            $('#glg-completion').text(response.completion + '%');
                            $('#glg-progress-bar').css('width', response.completion + '%');
                            $('#glg-progress-text').text(response.completion + '%');

                            // Fehlende Einträge anzeigen
                            if (response.missing.length > 0) {
                                var html = '<h5>Fehlende Übersetzungen (erste 100):</h5><ul class="list-group">';
                                $.each(response.missing, function(i, key) {
                                    html += '<li class="list-group-item"><code>' + key + '</code></li>';
                                });
                                html += '</ul>';
                                $('#glg-missing-details').html(html);
                            } else {
                                $('#glg-missing-details').html('<div class="alert alert-success">Alle Übersetzungen vorhanden!</div>');
                            }

                            $('#glg-compare-results').show();
                        } else {
                            $('#glg-compare-messages').html('<div class="alert alert-danger"><strong>Fehler:</strong> ' + response.error + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#glg-compare-messages').html('<div class="alert alert-danger"><strong>Fehler:</strong> ' + error + '</div>');
                    }
                });
            });
        });
        </script>
        <?php
        $html = ob_get_clean();
        error_log('GLG: _renderInterface() END - HTML length: ' . strlen($html));
        return $html;
    }

    public function actionSave()
    {
        error_log('GLG: actionSave() called');

        // Stelle sicher, dass die Tabelle existiert
        $this->_ensureTablesExist();

        $apiProvider = $this->_getPostData('apiProvider');
        $apiKey = $this->_getPostData('apiKey');
        $model = $this->_getPostData('model');

        error_log('GLG: Received data - Provider: ' . $apiProvider . ', Model: ' . $model . ', API Key length: ' . strlen($apiKey));

        if (empty($apiKey)) {
            error_log('GLG: API Key is empty, redirecting to error');
            header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule&error=1');
            exit;
        }

        try {
            error_log('GLG: Starting to save settings...');
            $this->_saveSetting('apiProvider', $apiProvider);
            $this->_saveSetting('apiKey', $apiKey);
            $this->_saveSetting('model', $model);

            error_log('GLG: All settings saved successfully, redirecting to success');
            header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule&success=1');
            exit;
        } catch (Exception $e) {
            error_log('GLG Save Error: ' . $e->getMessage());
            header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule&error=1');
            exit;
        }
    }

    private function _saveSetting($key, $value)
    {
        $key = xtc_db_input($key);
        $value = xtc_db_input($value);

        $query = "SELECT setting_key FROM rz_glg_settings WHERE setting_key = '$key'";
        $result = xtc_db_query($query);

        if (xtc_db_num_rows($result) > 0) {
            $query = "UPDATE rz_glg_settings SET setting_value = '$value', updated_at = NOW() WHERE setting_key = '$key'";
            error_log("GLG: Updating setting $key");
        } else {
            $query = "INSERT INTO rz_glg_settings (setting_key, setting_value) VALUES ('$key', '$value')";
            error_log("GLG: Inserting setting $key");
        }

        $success = xtc_db_query($query);
        if (!$success) {
            error_log("GLG: Failed to save setting $key");
            throw new Exception("Failed to save setting: $key");
        }

        error_log("GLG: Successfully saved setting $key = " . substr($value, 0, 10) . "...");
    }

    public function actionGenerate()
    {
        error_log('GLG: actionGenerate() called');

        $sourceLanguage = $this->_getPostData('sourceLanguage');
        $targetLanguages = $this->_getPostData('targetLanguages');

        error_log('GLG: Source: ' . $sourceLanguage . ', Targets: ' . print_r($targetLanguages, true));

        // Validierung
        if (empty($sourceLanguage)) {
            $this->_jsonResponse(['success' => false, 'error' => 'Keine Quellsprache ausgewählt']);
            return;
        }

        if (empty($targetLanguages) || !is_array($targetLanguages)) {
            $this->_jsonResponse(['success' => false, 'error' => 'Keine Zielsprachen ausgewählt']);
            return;
        }

        // Prüfe ob Quellsprache in Zielsprachen enthalten ist
        if (in_array($sourceLanguage, $targetLanguages)) {
            $this->_jsonResponse(['success' => false, 'error' => 'Quellsprache kann nicht als Zielsprache ausgewählt werden']);
            return;
        }

        // Lade Einstellungen
        $settings = $this->_loadSettings();

        if (empty($settings['apiKey'])) {
            $this->_jsonResponse(['success' => false, 'error' => 'API Key nicht konfiguriert. Bitte in den Einstellungen hinterlegen.']);
            return;
        }

        try {
            // Erhöhe Timeout für lange Übersetzungen
            set_time_limit(3600); // 1 Stunde
            ini_set('memory_limit', '512M');

            // Lade Helper-Klassen (relative Pfade vom Controller aus)
            require_once(__DIR__ . '/../../../includes/GLGReader.php');
            require_once(__DIR__ . '/../../../includes/GLGTranslator.php');
            require_once(__DIR__ . '/../../../includes/GLGFileWriter.php');

            $reader = new GLGReader();
            $translator = new GLGTranslator($settings);
            $writer = new GLGFileWriter();

            // Lese Quellsprache (gruppiert nach Dateien)
            error_log('GLG: Reading source language data...');
            $sourceFiles = $reader->readLanguageData($sourceLanguage);

            if (empty($sourceFiles)) {
                $this->_jsonResponse(['success' => false, 'error' => 'Keine Sprachdateien in Quellsprache gefunden']);
                return;
            }

            error_log('GLG: Found ' . count($sourceFiles) . ' source files');

            $results = [];
            $totalEntriesProcessed = 0;

            // Übersetze in jede Zielsprache
            foreach ($targetLanguages as $targetLanguage) {
                error_log('GLG: Translating to ' . $targetLanguage);
                $filesWritten = 0;
                $totalEntries = 0;

                // Verarbeite jede Source-Datei einzeln
                foreach ($sourceFiles as $sourceFile => $sourceData) {
                    error_log('GLG: Processing file: ' . $sourceFile);

                    // Flatten sections into single array for translation
                    $flatEntries = [];
                    foreach ($sourceData['sections'] as $sectionName => $entries) {
                        foreach ($entries as $key => $value) {
                            $flatKey = $sectionName . '::' . $key;
                            $flatEntries[$flatKey] = $value;
                        }
                    }

                    // Translate in batches
                    $batchSize = 50;
                    $chunks = array_chunk($flatEntries, $batchSize, true);
                    $translatedFlat = [];

                    foreach ($chunks as $index => $chunk) {
                        error_log('GLG: Translating batch ' . ($index + 1) . '/' . count($chunks) . ' of ' . $sourceFile);
                        $translated = $translator->translateBatch($chunk, $sourceLanguage, $targetLanguage, 'E-Commerce: ' . $sourceFile);
                        $translatedFlat = array_merge($translatedFlat, $translated);
                    }

                    // Reconstruct section structure
                    $translatedSections = [];
                    foreach ($translatedFlat as $flatKey => $translatedValue) {
                        list($sectionName, $key) = explode('::', $flatKey, 2);
                        if (!isset($translatedSections[$sectionName])) {
                            $translatedSections[$sectionName] = [];
                        }
                        $translatedSections[$sectionName][$key] = $translatedValue;
                    }

                    // Write file
                    $writeData = [
                        'source' => $sourceFile,
                        'sections' => $translatedSections
                    ];

                    $writeResult = $writer->writeSourceFile($writeData, $targetLanguage);
                    if ($writeResult['success']) {
                        $filesWritten++;
                        error_log('GLG: Written: ' . $writeResult['file']);
                    }
                    $totalEntries += count($translatedFlat);
                }

                $results[] = [
                    'language' => $targetLanguage,
                    'files' => $filesWritten,
                    'entries' => $totalEntries
                ];

                error_log('GLG: Completed ' . $targetLanguage . ': ' . $filesWritten . ' files, ' . $totalEntries . ' entries');
            }

            // Log Erfolg
            $totalProcessed = array_sum(array_column($results, 'entries'));
            $this->_logAction('generate', $sourceLanguage, implode(',', $targetLanguages), 'success', $totalProcessed . ' Einträge in ' . count($sourceFiles) . ' Dateien übersetzt');

            error_log('GLG: Generation completed successfully');
            $this->_jsonResponse([
                'success' => true,
                'message' => 'Übersetzung erfolgreich abgeschlossen',
                'results' => $results
            ]);

        } catch (Exception $e) {
            error_log('GLG Generate Error: ' . $e->getMessage());
            $this->_logAction('generate', $sourceLanguage, implode(',', $targetLanguages), 'error', $e->getMessage());
            $this->_jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function actionCompare()
    {
        error_log('GLG: actionCompare() called');

        $sourceLanguage = $this->_getPostData('sourceLanguage');
        $targetLanguage = $this->_getPostData('targetLanguage');

        error_log('GLG: Source: ' . $sourceLanguage . ', Target: ' . $targetLanguage);

        // Validierung
        if (empty($sourceLanguage)) {
            $this->_jsonResponse(['success' => false, 'error' => 'Keine Quellsprache ausgewählt']);
            return;
        }

        if (empty($targetLanguage)) {
            $this->_jsonResponse(['success' => false, 'error' => 'Keine Zielsprache ausgewählt']);
            return;
        }

        // Prüfe ob Quellsprache == Zielsprache
        if ($sourceLanguage === $targetLanguage) {
            $this->_jsonResponse(['success' => false, 'error' => 'Quell- und Zielsprache dürfen nicht identisch sein']);
            return;
        }

        try {
            // Lade Helper-Klassen (relative Pfade vom Controller aus)
            require_once(__DIR__ . '/../../../includes/GLGCompare.php');

            $comparer = new GLGCompare();

            // Vergleiche beide Sprachen
            error_log('GLG: Comparing languages...');
            $comparison = $comparer->compareLanguages($sourceLanguage, $targetLanguage);

            $sourceCount = $comparison['total_source_entries'];
            $targetCount = $comparison['total_target_entries'];
            $missingCount = $comparison['missing_entries'];

            $completion = $sourceCount > 0
                ? round((($sourceCount - $missingCount) / $sourceCount) * 100, 1)
                : 100;

            error_log('GLG: Comparison completed. Missing: ' . $missingCount);

            // Extrahiere nur die Keys für die Anzeige
            $missingKeys = array_map(function($item) {
                return $item['file'] . ' > ' . $item['section'] . ' > ' . $item['key'];
            }, $comparison['missing_keys']);

            $this->_jsonResponse([
                'success' => true,
                'sourceCount' => $sourceCount,
                'targetCount' => $targetCount,
                'missingCount' => $missingCount,
                'completion' => $completion,
                'missing' => array_slice($missingKeys, 0, 100) // Nur erste 100 zeigen
            ]);

        } catch (Exception $e) {
            error_log('GLG Compare Error: ' . $e->getMessage());
            $this->_jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function _loadSettings()
    {
        $settings = [
            'apiProvider' => 'openai',
            'apiKey' => '',
            'model' => 'gpt-4o',
            'temperature' => 0.3,
            'maxTokens' => 4000
        ];

        $query = "SELECT setting_key, setting_value FROM rz_glg_settings";
        $result = xtc_db_query($query);

        while ($row = xtc_db_fetch_array($result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    private function _logAction($action, $sourceLanguage, $targetLanguage, $status, $details = '')
    {
        $query = "INSERT INTO rz_glg_log (action, source_language, target_language, status, details)
                  VALUES ('" . xtc_db_input($action) . "',
                          '" . xtc_db_input($sourceLanguage) . "',
                          '" . xtc_db_input($targetLanguage) . "',
                          '" . xtc_db_input($status) . "',
                          '" . xtc_db_input($details) . "')";
        xtc_db_query($query);
    }

    private function _jsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function _ensureTablesExist()
    {
        // Settings Tabelle
        $query = "CREATE TABLE IF NOT EXISTS `rz_glg_settings` (
            `setting_key` varchar(100) NOT NULL,
            `setting_value` text NOT NULL,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        xtc_db_query($query);

        // Log Tabelle
        $query = "CREATE TABLE IF NOT EXISTS `rz_glg_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `date` datetime DEFAULT CURRENT_TIMESTAMP,
            `action` varchar(50) NOT NULL,
            `source_language` varchar(50) DEFAULT NULL,
            `target_language` varchar(50) DEFAULT NULL,
            `status` enum('success','error','running') DEFAULT 'running',
            `details` text,
            PRIMARY KEY (`id`),
            KEY `date` (`date`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        xtc_db_query($query);

        // Update Tracking Tabelle
        $query = "CREATE TABLE IF NOT EXISTS `rz_glg_update_tracking` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `last_update` datetime DEFAULT CURRENT_TIMESTAMP,
            `source_language` varchar(50) NOT NULL,
            `target_language` varchar(50) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `languages` (`source_language`, `target_language`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        xtc_db_query($query);
    }
}
}
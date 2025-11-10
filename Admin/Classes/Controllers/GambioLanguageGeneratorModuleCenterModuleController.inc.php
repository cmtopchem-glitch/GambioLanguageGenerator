<?php
if (!class_exists('GambioLanguageGeneratorModuleCenterModuleController')) {
class GambioLanguageGeneratorModuleCenterModuleController extends AbstractModuleCenterModuleController
{
    protected function _init()
    {
    }

    public function actionDefault()
    {
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

        $success = $this->_getQueryParameter('success') == '1';
        $error = $this->_getQueryParameter('error') == '1';

        // Erstelle HTML direkt (ohne Smarty Template erstmal)
        $html = $this->_renderInterface($languages, $apiProvider, $apiKey, $model, $success, $error);

        // Return proper response object - wie beim AI Product Optimizer
        return new AdminPageHttpControllerResponse($this->pageTitle, $html);
    }

    private function _renderInterface($languages, $apiProvider, $apiKey, $model, $success, $error)
    {
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
        <?php
        return ob_get_clean();
    }

    public function actionSave()
    {
        // Stelle sicher, dass die Tabelle existiert
        $this->_ensureTablesExist();

        $apiProvider = $this->_getPostData('apiProvider');
        $apiKey = $this->_getPostData('apiKey');
        $model = $this->_getPostData('model');

        if (empty($apiKey)) {
            header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule&error=1');
            exit;
        }

        try {
            $this->_saveSetting('apiProvider', $apiProvider);
            $this->_saveSetting('apiKey', $apiKey);
            $this->_saveSetting('model', $model);

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
        // TODO: Implementieren
        $this->_jsonResponse(array('success' => false, 'error' => 'Noch nicht implementiert'));
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

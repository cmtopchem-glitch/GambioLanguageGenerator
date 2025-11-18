<?php
/**
 * Gambio Language Generator - Admin Interface
 * 
 * @author Christian Mittenzwei
 * @version 1.0.0
 */

// Sicherheitscheck
if (!defined('_VALID_XTC')) {
    die('Direct Access to this location is not allowed.');
}

// Fallback für Gambio-Funktionen falls not loaded
if (!function_exists('xtc_db_query')) {
    // Wenn über Admin-Proxy aufgerufen und DB nicht initialisiert
    error_log('WARNING: xtc_db_query not defined - using fallback');
    function xtc_db_query($query) {
        global $connection;
        if (!isset($connection)) {
            return false;
        }
        return mysqli_query($connection, $query);
    }
}

if (!function_exists('xtc_db_fetch_array')) {
    function xtc_db_fetch_array($result) {
        if (!$result) return false;
        return mysqli_fetch_array($result, MYSQLI_ASSOC);
    }
}

// Sprachdatei laden
$langDir = isset($_SESSION['language']) ? $_SESSION['language'] : 'german';
$langFile = DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/lang/' . $langDir . '/glg.php';
if (file_exists($langFile)) {
    require_once($langFile);
} else {
    require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/lang/german/glg.php');
}

// Definiere Konstanten aus dem Sprach-Array
if (isset($t_language_text_section_content_array) && is_array($t_language_text_section_content_array)) {
    foreach ($t_language_text_section_content_array as $key => $value) {
        if (!defined($key)) {
            define($key, $value);
        }
    }
}

// Lizenzprüfung (temporär deaktiviert für Entwicklung/Testing)
// require_once(DIR_FS_CATALOG . 'GXModules/REDOzone/GambioLanguageGenerator/includes/GLGLicense.php');
// $license = new GLGLicense();
// if (!$license->isValid()) {
//     echo '<div class="alert alert-danger">' . GLG_ERROR_LICENSE . '</div>';
//     return;
// }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo GLG_TITLE; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        .glg-container {
            padding: 20px;
        }
        .glg-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        .glg-tab-content {
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
        }
        .glg-progress {
            display: none;
            margin-top: 20px;
        }
        .glg-log-table {
            margin-top: 20px;
        }
        .language-checkbox {
            margin: 5px 0;
        }
        .module-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 10px;
        }
        .alert {
            margin-top: 15px;
        }
        .settings-group {
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="glg-container">
        <div class="glg-header">
            <h1><?php echo GLG_TITLE; ?></h1>
            <p class="text-muted"><?php echo GLG_SUBTITLE; ?></p>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#generate" aria-controls="generate" role="tab" data-toggle="tab">
                    <?php echo GLG_TAB_GENERATE; ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#compare" aria-controls="compare" role="tab" data-toggle="tab">
                    <?php echo GLG_TAB_COMPARE; ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#update" aria-controls="update" role="tab" data-toggle="tab">
                    <?php echo GLG_TAB_UPDATE; ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#languages" aria-controls="languages" role="tab" data-toggle="tab">
                    <?php echo GLG_TAB_LANGUAGES; ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">
                    <?php echo GLG_TAB_SETTINGS; ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#log" aria-controls="log" role="tab" data-toggle="tab">
                    <?php echo GLG_TAB_LOG; ?>
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content glg-tab-content">
            
            <!-- Generate Tab -->
            <div role="tabpanel" class="tab-pane active" id="generate">
                <form id="generateForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo GLG_SOURCE_LANGUAGE; ?></label>
                                <select class="form-control" id="sourceLanguage" name="sourceLanguage">
                                    <option value="">Bitte wählen...</option>
                                    <?php
                                    // Verfügbare Sprachen aus der Datenbank laden
                                    $languages = array();
                                    if (function_exists('xtc_db_query')) {
                                        $languages_query = xtc_db_query("SELECT * FROM languages ORDER BY sort_order");
                                        if ($languages_query) {
                                            while ($lang = xtc_db_fetch_array($languages_query)) {
                                                $languages[] = $lang;
                                            }
                                        }
                                    }

                                    // Fallback auf Standard-Sprachen wenn DB nicht erreichbar
                                    if (empty($languages)) {
                                        $languages = array(
                                            array('directory' => 'german', 'name' => 'Deutsch'),
                                            array('directory' => 'english', 'name' => 'English'),
                                            array('directory' => 'french', 'name' => 'Français'),
                                        );
                                    }

                                    foreach ($languages as $lang) {
                                        $selected = ($lang['directory'] == 'german') ? 'selected' : '';
                                        echo '<option value="' . $lang['directory'] . '" ' . $selected . '>' .
                                             $lang['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo GLG_TARGET_LANGUAGES; ?></label>
                                <div class="checkbox-group">
                                    <?php
                                    // Verwende die bereits geladene $languages variable
                                    foreach ($languages as $lang) {
                                        echo '<div class="checkbox language-checkbox">';
                                        echo '<label>';
                                        echo '<input type="checkbox" name="targetLanguages[]" value="' .
                                             $lang['directory'] . '"> ' . $lang['name'];
                                        echo '</label>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo GLG_SELECT_MODULES; ?></label>
                        
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="includeCoreFiles" name="includeCoreFiles" checked>
                                <strong><?php echo GLG_CORE_FILES; ?></strong>
                            </label>
                        </div>

                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="includeGXModules" name="includeGXModules" checked>
                                <strong><?php echo GLG_GXMODULES; ?></strong>
                            </label>
                        </div>

                        <div class="module-list" id="moduleList" style="display: none;">
                            <!-- Module werden via AJAX geladen -->
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <span class="glyphicon glyphicon-play"></span> <?php echo GLG_BTN_GENERATE; ?>
                        </button>
                        <button type="button" class="btn btn-default btn-lg" id="cancelBtn">
                            <?php echo GLG_BTN_CANCEL; ?>
                        </button>
                    </div>
                </form>

                <!-- Progress Bar -->
                <div class="glg-progress" id="progressContainer">
                    <h4 id="progressStatus"><?php echo GLG_PROGRESS_READING; ?></h4>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped active" role="progressbar" 
                             id="progressBar" style="width: 0%">
                            <span id="progressPercent">0%</span>
                        </div>
                    </div>
                    <div id="progressDetails"></div>
                </div>

                <!-- Messages -->
                <div id="generateMessages"></div>
            </div>

            <!-- Compare Tab -->
            <div role="tabpanel" class="tab-pane" id="compare">
                <h3><?php echo GLG_COMPARE_TITLE; ?></h3>
                <p class="text-muted"><?php echo GLG_COMPARE_DESC; ?></p>
                
                <form id="compareForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo GLG_SOURCE_LANGUAGE; ?></label>
                                <select class="form-control" id="compareSourceLanguage" name="sourceLanguage">
                                    <option value="">Bitte wählen...</option>
                                    <?php
                                    foreach ($languages as $lang) {
                                        $selected = ($lang['directory'] == 'german') ? 'selected' : '';
                                        echo '<option value="' . $lang['directory'] . '" ' . $selected . '>' .
                                             $lang['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo GLG_TARGET_LANGUAGES; ?> (nur eine)</label>
                                <select class="form-control" id="compareTargetLanguage" name="targetLanguage">
                                    <option value="">Bitte wählen...</option>
                                    <?php
                                    foreach ($languages as $lang) {
                                        echo '<option value="' . $lang['directory'] . '">' . $lang['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="compareIncludeCoreFiles" name="includeCoreFiles" checked>
                            <strong><?php echo GLG_CORE_FILES; ?></strong>
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="compareIncludeGXModules" name="includeGXModules" checked>
                            <strong><?php echo GLG_GXMODULES; ?></strong>
                        </label>
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <span class="glyphicon glyphicon-search"></span> <?php echo GLG_BTN_COMPARE; ?>
                        </button>
                    </div>
                </form>

                <div id="compareProgress" style="display: none;">
                    <h4><?php echo GLG_COMPARE_RUNNING; ?></h4>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>

                <!-- Ergebnisse -->
                <div id="compareResults" style="display: none; margin-top: 30px;">
                    <h3><?php echo GLG_COMPARISON_RESULTS; ?></h3>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="panel panel-default">
                                <div class="panel-body text-center">
                                    <h2 id="compareSourceCount">-</h2>
                                    <p><?php echo GLG_TOTAL_SOURCE; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-default">
                                <div class="panel-body text-center">
                                    <h2 id="compareTargetCount">-</h2>
                                    <p><?php echo GLG_TOTAL_TARGET; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-danger">
                                <div class="panel-body text-center">
                                    <h2 id="compareMissingCount">-</h2>
                                    <p><?php echo GLG_MISSING_ENTRIES; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-success">
                                <div class="panel-body text-center">
                                    <h2 id="compareCompletion">-</h2>
                                    <p><?php echo GLG_COMPLETION; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="progress">
                        <div class="progress-bar progress-bar-success" id="compareProgressBar" style="width: 0%">
                            <span id="compareProgressText">0%</span>
                        </div>
                    </div>

                    <div class="btn-group" style="margin-top: 20px;">
                        <button type="button" class="btn btn-info" id="viewReportBtn">
                            <span class="glyphicon glyphicon-file"></span> <?php echo GLG_BTN_VIEW_REPORT; ?>
                        </button>
                        <button type="button" class="btn btn-default" id="exportCsvBtn">
                            <span class="glyphicon glyphicon-download-alt"></span> <?php echo GLG_BTN_EXPORT_CSV; ?>
                        </button>
                        <button type="button" class="btn btn-success" id="updateMissingBtn">
                            <span class="glyphicon glyphicon-play"></span> <?php echo GLG_BTN_UPDATE_MISSING; ?>
                        </button>
                    </div>

                    <div id="compareDetails" style="margin-top: 20px;">
                        <!-- Details werden via JavaScript gefüllt -->
                    </div>
                </div>

                <div id="compareMessages"></div>
            </div>

            <!-- Update Tab -->
            <div role="tabpanel" class="tab-pane" id="update">
                <div class="alert alert-info">
                    <strong><?php echo GLG_LAST_UPDATE; ?>:</strong> 
                    <span id="lastUpdateDate">Noch nie</span>
                </div>

                <div id="changesContainer">
                    <h4><?php echo GLG_CHANGES_SINCE; ?> <span id="changesSinceDate"></span></h4>
                    <div id="changesList"></div>
                </div>

                <button type="button" class="btn btn-primary" id="updateBtn">
                    <span class="glyphicon glyphicon-refresh"></span> <?php echo GLG_BTN_UPDATE; ?>
                </button>

                <div id="updateMessages"></div>
            </div>

            <!-- Languages Tab -->
            <div role="tabpanel" class="tab-pane" id="languages">
                <h3><?php echo GLG_CREATE_LANGUAGE; ?></h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <h4><?php echo GLG_LANGUAGE_SUGGESTIONS; ?></h4>
                        <div id="languageSuggestions" class="list-group">
                            <!-- Wird via JavaScript gefüllt -->
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h4><?php echo GLG_LANGUAGE_CUSTOM; ?></h4>
                        <form id="createLanguageForm">
                            <div class="form-group">
                                <label><?php echo GLG_LANGUAGE_NAME; ?></label>
                                <input type="text" class="form-control" name="name" placeholder="z.B. Español" required>
                            </div>
                            
                            <div class="form-group">
                                <label><?php echo GLG_LANGUAGE_CODE; ?></label>
                                <input type="text" class="form-control" name="code" placeholder="z.B. es" maxlength="2" required>
                                <small class="text-muted">ISO 639-1 Code (2 Buchstaben)</small>
                            </div>
                            
                            <div class="form-group">
                                <label><?php echo GLG_LANGUAGE_DIRECTORY; ?></label>
                                <input type="text" class="form-control" name="directory" placeholder="z.B. spanish" required>
                                <small class="text-muted">Verzeichnisname (nur Kleinbuchstaben)</small>
                            </div>
                            
                            <div class="form-group">
                                <label><?php echo GLG_LANGUAGE_COUNTRY; ?></label>
                                <input type="text" class="form-control" name="country_code" placeholder="z.B. ES" maxlength="2">
                                <small class="text-muted">ISO 3166-1 Ländercode für Flagge (optional)</small>
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg btn-block">
                                <span class="glyphicon glyphicon-plus"></span> <?php echo GLG_BTN_CREATE_LANGUAGE; ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div id="languagesMessages"></div>
            </div>

            <!-- Settings Tab -->
            <div role="tabpanel" class="tab-pane" id="settings">
                <form id="settingsForm">
                    
                    <!-- API Settings -->
                    <div class="settings-group">
                        <h3><?php echo GLG_API_SETTINGS; ?></h3>
                        
                        <div class="form-group">
                            <label><?php echo GLG_API_PROVIDER; ?></label>
                            <select class="form-control" name="apiProvider" id="apiProvider">
                                <option value="openai">OpenAI</option>
                                <option value="deepl">DeepL</option>
                                <option value="google">Google Translate</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><?php echo GLG_API_KEY; ?></label>
                            <input type="password" class="form-control" name="apiKey" id="apiKey">
                        </div>

                        <div id="openaiSettings">
                            <div class="form-group">
                                <label><?php echo GLG_MODEL; ?></label>
                                <select class="form-control" name="model">
                                    <option value="gpt-4o">GPT-4o</option>
                                    <option value="gpt-4o-mini">GPT-4o Mini</option>
                                    <option value="gpt-4">GPT-4</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><?php echo GLG_TEMPERATURE; ?></label>
                                        <input type="number" class="form-control" name="temperature" 
                                               value="0.3" min="0" max="2" step="0.1">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><?php echo GLG_MAX_TOKENS; ?></label>
                                        <input type="number" class="form-control" name="maxTokens" 
                                               value="4000" min="100" max="16000" step="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Settings -->
                    <div class="settings-group">
                        <h3>Backup</h3>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="backupEnabled" checked>
                                <?php echo GLG_BACKUP_ENABLED; ?>
                            </label>
                        </div>
                    </div>

                    <!-- License Settings -->
                    <div class="settings-group">
                        <h3><?php echo GLG_LICENSE_KEY; ?></h3>
                        <div class="form-group">
                            <input type="text" class="form-control" name="licenseKey"
                                   value="<?php echo (isset($license) && $license) ? $license->getLicenseKey() : 'N/A'; ?>" readonly>
                        </div>
                        <div class="alert alert-success">
                            <strong><?php echo GLG_LICENSE_STATUS; ?>:</strong> <?php echo (isset($license) && $license) ? GLG_LICENSE_VALID : 'N/A'; ?><br>
                            <strong><?php echo GLG_LICENSE_URL; ?>:</strong> <?php echo (isset($license) && $license) ? $license->getLicensedUrl() : 'N/A'; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success btn-lg">
                            <span class="glyphicon glyphicon-save"></span> <?php echo GLG_BTN_SAVE_SETTINGS; ?>
                        </button>
                        <button type="button" class="btn btn-info" id="testApiBtn">
                            <span class="glyphicon glyphicon-check"></span> <?php echo GLG_BTN_TEST_API; ?>
                        </button>
                    </div>
                </form>

                <div id="settingsMessages"></div>
            </div>

            <!-- Log Tab -->
            <div role="tabpanel" class="tab-pane" id="log">
                <div class="table-responsive glg-log-table">
                    <table class="table table-striped table-hover" id="logTable">
                        <thead>
                            <tr>
                                <th><?php echo GLG_LOG_DATE; ?></th>
                                <th><?php echo GLG_LOG_ACTION; ?></th>
                                <th><?php echo GLG_LOG_SOURCE; ?></th>
                                <th><?php echo GLG_LOG_TARGET; ?></th>
                                <th><?php echo GLG_LOG_STATUS; ?></th>
                                <th><?php echo GLG_LOG_DETAILS; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Wird via AJAX gefüllt -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- jQuery und Bootstrap laden -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script>
        // Globale Konfiguration für AJAX-Requests
        window.GLG = {
            controllerUrl: '/GXModules/REDOzone/GambioLanguageGenerator/admin/glg_controller.php',
            baseUrl: '/'
        };

        // Debug-Info
        console.log('GLG Config loaded:', window.GLG);
        console.log('jQuery loaded:', typeof jQuery !== 'undefined');
        console.log('Bootstrap loaded:', typeof jQuery !== 'undefined' && typeof jQuery.fn.tab !== 'undefined');
    </script>
    <script src="/GXModules/REDOzone/GambioLanguageGenerator/admin/glg_admin.js"></script>
</body>
</html>

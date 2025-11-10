<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('GLG_TITLE') ? GLG_TITLE : 'Gambio Language Generator'; ?></title>
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
            <h1><?php echo defined('GLG_TITLE') ? GLG_TITLE : 'Gambio Language Generator'; ?></h1>
            <p class="text-muted"><?php echo defined('GLG_SUBTITLE') ? GLG_SUBTITLE : 'Automatische Generierung von Sprachdateien'; ?></p>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#generate" aria-controls="generate" role="tab" data-toggle="tab">
                    <?php echo defined('GLG_TAB_GENERATE') ? GLG_TAB_GENERATE : 'Sprachen generieren'; ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#compare" aria-controls="compare" role="tab" data-toggle="tab">
                    <?php echo defined('GLG_TAB_COMPARE') ? GLG_TAB_COMPARE : 'Vergleich / Testlauf'; ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">
                    <?php echo defined('GLG_TAB_SETTINGS') ? GLG_TAB_SETTINGS : 'Einstellungen'; ?>
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
                                <label>Quellsprache</label>
                                <select class="form-control" id="sourceLanguage" name="sourceLanguage">
                                    <option value="">Bitte wählen...</option>
                                    <?php
                                    $languages_query = xtc_db_query("SELECT * FROM languages ORDER BY sort_order");
                                    while ($lang = xtc_db_fetch_array($languages_query)) {
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
                                <label>Zielsprachen</label>
                                <div class="checkbox-group">
                                    <?php
                                    $languages_query = xtc_db_query("SELECT * FROM languages ORDER BY sort_order");
                                    while ($lang = xtc_db_fetch_array($languages_query)) {
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
                        <button type="submit" class="btn btn-primary btn-lg">
                            <span class="glyphicon glyphicon-play"></span> Sprachen generieren
                        </button>
                    </div>
                </form>

                <div id="generateMessages"></div>
            </div>

            <!-- Compare Tab -->
            <div role="tabpanel" class="tab-pane" id="compare">
                <h3>Sprachen vergleichen</h3>
                <p class="text-muted">Zeigt fehlende Übersetzungen in der Zielsprache an</p>
                <div id="compareMessages"></div>
            </div>

            <!-- Settings Tab -->
            <div role="tabpanel" class="tab-pane" id="settings">
                <form id="settingsForm">
                    <div class="settings-group">
                        <h3>API-Einstellungen</h3>
                        
                        <div class="form-group">
                            <label>API Provider</label>
                            <select class="form-control" name="apiProvider" id="apiProvider">
                                <option value="openai">OpenAI</option>
                                <option value="deepl">DeepL</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>API Key</label>
                            <input type="password" class="form-control" name="apiKey" id="apiKey">
                        </div>

                        <div class="form-group">
                            <label>Modell</label>
                            <select class="form-control" name="model">
                                <option value="gpt-4o">GPT-4o</option>
                                <option value="gpt-4o-mini">GPT-4o Mini</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success btn-lg">
                            <span class="glyphicon glyphicon-save"></span> Einstellungen speichern
                        </button>
                        <button type="button" class="btn btn-info" id="testApiBtn">
                            <span class="glyphicon glyphicon-check"></span> API testen
                        </button>
                    </div>
                </form>

                <div id="settingsMessages"></div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        
        // Generate Form Submit
        $('#generateForm').submit(function(e) {
            e.preventDefault();
            
            var sourceLanguage = $('#sourceLanguage').val();
            var targetLanguages = $('input[name="targetLanguages[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            if (!sourceLanguage) {
                showMessage('generateMessages', 'danger', 'Bitte Quellsprache auswählen');
                return;
            }

            if (targetLanguages.length === 0) {
                showMessage('generateMessages', 'danger', 'Bitte mindestens eine Zielsprache auswählen');
                return;
            }

            showMessage('generateMessages', 'info', 'Funktion wird implementiert...');
        });

        // Settings Form Submit
        $('#settingsForm').submit(function(e) {
            e.preventDefault();
            showMessage('settingsMessages', 'info', 'Einstellungen werden gespeichert...');
        });

        // API Test Button
        $('#testApiBtn').click(function() {
            showMessage('settingsMessages', 'info', 'API wird getestet...');
        });
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
    </script>
</body>
</html>

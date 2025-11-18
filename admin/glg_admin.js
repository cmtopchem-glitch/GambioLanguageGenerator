/**
 * Gambio Language Generator - Admin JavaScript
 *
 * @author Christian Mittenzwei
 * @version 1.0.0
 */

// Fallback falls window.GLG nicht gesetzt wurde
if (typeof window.GLG === 'undefined') {
    window.GLG = {
        controllerUrl: '../GXModules/REDOzone/GambioLanguageGenerator/admin/glg_controller.php',
        baseUrl: '/'
    };
}

$(document).ready(function() {
    console.log('GLG Admin JS loaded!');
    console.log('GLG Config:', window.GLG);

    // Test: Bootstrap Tabs manuell aktivieren
    $('a[data-toggle="tab"]').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });

    // Module-Liste ein-/ausblenden
    $('#includeGXModules').change(function() {
        if ($(this).is(':checked')) {
            loadModuleList();
            $('#moduleList').slideDown();
        } else {
            $('#moduleList').slideUp();
        }
    });

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

        var formData = {
            action: 'generate',
            sourceLanguage: sourceLanguage,
            targetLanguages: targetLanguages,
            includeCoreFiles: $('#includeCoreFiles').is(':checked'),
            includeGXModules: $('#includeGXModules').is(':checked'),
            selectedModules: $('input[name="modules[]"]:checked').map(function() {
                return $(this).val();
            }).get()
        };

        startGeneration(formData);
    });

    // Settings Form Submit
    $('#settingsForm').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serializeArray();
        formData.push({name: 'action', value: 'saveSettings'});

        $.ajax({
            url: window.GLG.controllerUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage('settingsMessages', 'success', response.message);
                } else {
                    showMessage('settingsMessages', 'danger', response.message);
                }
            },
            error: function() {
                showMessage('settingsMessages', 'danger', 'Fehler beim Speichern');
            }
        });
    });

    // API Test Button
    $('#testApiBtn').click(function() {
        var apiProvider = $('#apiProvider').val();
        var apiKey = $('#apiKey').val();

        if (!apiKey) {
            showMessage('settingsMessages', 'warning', 'Bitte API-Schlüssel eingeben');
            return;
        }

        $(this).prop('disabled', true).html('<span class="glyphicon glyphicon-refresh spinning"></span> Teste...');

        $.ajax({
            url: window.GLG.controllerUrl,
            type: 'POST',
            data: {
                action: 'testApi',
                apiProvider: apiProvider,
                apiKey: apiKey
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage('settingsMessages', 'success', 'API-Test erfolgreich: ' + response.message);
                } else {
                    showMessage('settingsMessages', 'danger', 'API-Test fehlgeschlagen: ' + response.message);
                }
            },
            error: function() {
                showMessage('settingsMessages', 'danger', 'Fehler beim API-Test');
            },
            complete: function() {
                $('#testApiBtn').prop('disabled', false).html('<span class="glyphicon glyphicon-check"></span> API testen');
            }
        });
    });

    // Update Button
    $('#updateBtn').click(function() {
        var formData = {
            action: 'update'
        };
        startGeneration(formData);
    });

    // Cancel Button
    $('#cancelBtn').click(function() {
        if (confirm('Generierung wirklich abbrechen?')) {
            $.ajax({
                url: window.GLG.controllerUrl,
                type: 'POST',
                data: {action: 'cancel'},
                success: function() {
                    location.reload();
                }
            });
        }
    });

    // API Provider Change
    $('#apiProvider').change(function() {
        if ($(this).val() === 'openai') {
            $('#openaiSettings').show();
        } else {
            $('#openaiSettings').hide();
        }
    });

    // Load initial data
    loadLastUpdate();
    loadLog();
    loadSettings();
    loadLanguageSuggestions();
});

/**
 * Lädt Sprachvorschläge
 */
function loadLanguageSuggestions() {
    $.ajax({
        url: window.GLG.controllerUrl,
        type: 'POST',
        data: {action: 'getLanguageSuggestions'},
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var html = '';
                $.each(response.suggestions, function(index, lang) {
                    html += '<a href="#" class="list-group-item language-suggestion" ' +
                           'data-name="' + lang.name + '" ' +
                           'data-code="' + lang.code + '" ' +
                           'data-directory="' + lang.directory + '" ' +
                           'data-country="' + lang.country_code + '">';
                    html += '<h4 class="list-group-item-heading">' + lang.name + '</h4>';
                    html += '<p class="list-group-item-text">' + lang.code + ' / ' + lang.directory + '</p>';
                    html += '</a>';
                });
                $('#languageSuggestions').html(html);
                
                // Click Handler
                $('.language-suggestion').click(function(e) {
                    e.preventDefault();
                    $('[name="name"]').val($(this).data('name'));
                    $('[name="code"]').val($(this).data('code'));
                    $('[name="directory"]').val($(this).data('directory'));
                    $('[name="country_code"]').val($(this).data('country'));
                });
            }
        }
    });
}

/**
 * Sprache erstellen
 */
$('#createLanguageForm').submit(function(e) {
    e.preventDefault();
    
    var formData = $(this).serializeArray();
    formData.push({name: 'action', value: 'createLanguage'});

    $.ajax({
        url: window.GLG.controllerUrl,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showMessage('languagesMessages', 'success', response.message);
                $('#createLanguageForm')[0].reset();
                
                // Reload Sprach-Selects
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showMessage('languagesMessages', 'danger', response.message);
            }
        },
        error: function() {
            showMessage('languagesMessages', 'danger', 'Fehler beim Anlegen der Sprache');
        }
    });
});

/**
 * Sprachen vergleichen
 */
$('#compareForm').submit(function(e) {
    e.preventDefault();
    
    var sourceLanguage = $('#compareSourceLanguage').val();
    var targetLanguage = $('#compareTargetLanguage').val();

    if (!sourceLanguage || !targetLanguage) {
        showMessage('compareMessages', 'warning', 'Bitte beide Sprachen auswählen');
        return;
    }

    if (sourceLanguage === targetLanguage) {
        showMessage('compareMessages', 'warning', 'Quell- und Zielsprache müssen unterschiedlich sein');
        return;
    }

    var formData = {
        action: 'compareLanguages',
        sourceLanguage: sourceLanguage,
        targetLanguage: targetLanguage,
        includeCoreFiles: $('#compareIncludeCoreFiles').is(':checked'),
        includeGXModules: $('#compareIncludeGXModules').is(':checked')
    };

    $('#compareProgress').show();
    $('#compareResults').hide();

    $.ajax({
        url: window.GLG.controllerUrl,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            $('#compareProgress').hide();
            
            if (response.success) {
                displayComparisonResults(response.comparison);
            } else {
                showMessage('compareMessages', 'danger', 'Fehler beim Vergleich');
            }
        },
        error: function() {
            $('#compareProgress').hide();
            showMessage('compareMessages', 'danger', 'Fehler beim Vergleich');
        }
    });
});

/**
 * Zeigt Vergleichsergebnisse
 */
function displayComparisonResults(comparison) {
    $('#compareSourceCount').text(comparison.total_source_entries);
    $('#compareTargetCount').text(comparison.total_target_entries);
    $('#compareMissingCount').text(comparison.missing_entries);
    $('#compareCompletion').text(comparison.statistics.completion_percentage + '%');
    
    $('#compareProgressBar').css('width', comparison.statistics.completion_percentage + '%');
    $('#compareProgressText').text(comparison.statistics.completion_percentage + '%');
    
    // Details
    var html = '<h4>Details</h4>';
    
    if (comparison.missing_files.length > 0) {
        html += '<h5>Komplett fehlende Dateien (' + comparison.missing_files.length + ')</h5>';
        html += '<ul>';
        $.each(comparison.missing_files, function(index, file) {
            html += '<li><strong>' + file.file + '</strong> (' + file.missing_count + ' Einträge)</li>';
        });
        html += '</ul>';
    }
    
    if (comparison.missing_keys.length > 0) {
        html += '<h5>Fehlende Keys (Top 20)</h5>';
        html += '<table class="table table-striped">';
        html += '<thead><tr><th>Datei</th><th>Sektion</th><th>Key</th></tr></thead>';
        html += '<tbody>';
        $.each(comparison.missing_keys.slice(0, 20), function(index, entry) {
            html += '<tr>';
            html += '<td>' + entry.file + '</td>';
            html += '<td>' + entry.section + '</td>';
            html += '<td><code>' + entry.key + '</code></td>';
            html += '</tr>';
        });
        html += '</tbody></table>';
        
        if (comparison.missing_keys.length > 20) {
            html += '<p class="text-muted">... und ' + (comparison.missing_keys.length - 20) + ' weitere</p>';
        }
    }
    
    $('#compareDetails').html(html);
    $('#compareResults').show();
    
    // Speichere Comparison für weitere Actions
    window.currentComparison = comparison;
}

/**
 * Report anzeigen
 */
$('#viewReportBtn').click(function() {
    var formData = {
        action: 'getComparisonReport',
        sourceLanguage: $('#compareSourceLanguage').val(),
        targetLanguage: $('#compareTargetLanguage').val(),
        includeCoreFiles: $('#compareIncludeCoreFiles').is(':checked'),
        includeGXModules: $('#compareIncludeGXModules').is(':checked')
    };

    $.ajax({
        url: window.GLG.controllerUrl,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.open(response.report_url, '_blank');
            }
        }
    });
});

/**
 * Nur fehlende übersetzen
 */
$('#updateMissingBtn').click(function() {
    if (confirm('Nur die fehlenden ' + window.currentComparison.missing_entries + ' Einträge übersetzen?')) {
        // TODO: Implementiere selective Update
        showMessage('compareMessages', 'info', 'Funktion wird implementiert...');
    }
});

/**
 * Lädt die Liste der verfügbaren Module
 */
function loadModuleList() {
    $.ajax({
        url: window.GLG.controllerUrl,
        type: 'POST',
        data: {action: 'getModules'},
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var html = '';
                $.each(response.modules, function(index, module) {
                    html += '<div class="checkbox">';
                    html += '<label>';
                    html += '<input type="checkbox" name="modules[]" value="' + module.name + '" checked> ';
                    html += module.name;
                    if (module.title) {
                        html += ' <small class="text-muted">(' + module.title + ')</small>';
                    }
                    html += '</label>';
                    html += '</div>';
                });
                $('#moduleList').html(html);
            }
        }
    });
}

/**
 * Startet den Generierungsprozess
 */
function startGeneration(formData) {
    $('#progressContainer').show();
    $('#progressBar').css('width', '0%');
    $('#progressPercent').text('0%');
    $('#progressDetails').html('');
    
    $.ajax({
        url: window.GLG.controllerUrl,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                pollProgress(response.processId);
            } else {
                showMessage('generateMessages', 'danger', response.message);
                $('#progressContainer').hide();
            }
        },
        error: function() {
            showMessage('generateMessages', 'danger', 'Fehler beim Starten');
            $('#progressContainer').hide();
        }
    });
}

/**
 * Fragt den Fortschritt ab
 */
function pollProgress(processId) {
    var pollInterval = setInterval(function() {
        $.ajax({
            url: window.GLG.controllerUrl,
            type: 'POST',
            data: {
                action: 'getProgress',
                processId: processId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateProgress(response.progress);
                    
                    if (response.progress.status === 'complete') {
                        clearInterval(pollInterval);
                        showMessage('generateMessages', 'success', response.progress.message);
                        loadLog();
                        loadLastUpdate();
                    } else if (response.progress.status === 'error') {
                        clearInterval(pollInterval);
                        showMessage('generateMessages', 'danger', response.progress.message);
                    }
                }
            }
        });
    }, 1000);
}

/**
 * Aktualisiert die Fortschrittsanzeige
 */
function updateProgress(progress) {
    $('#progressBar').css('width', progress.percent + '%');
    $('#progressPercent').text(progress.percent + '%');
    $('#progressStatus').text(progress.statusText);
    
    if (progress.details) {
        var html = '<small class="text-muted">' + progress.details + '</small>';
        $('#progressDetails').html(html);
    }
}

/**
 * Lädt das Datum der letzten Aktualisierung
 */
function loadLastUpdate() {
    $.ajax({
        url: window.GLG.controllerUrl,
        type: 'POST',
        data: {action: 'getLastUpdate'},
        dataType: 'json',
        success: function(response) {
            if (response.success && response.lastUpdate) {
                $('#lastUpdateDate').text(response.lastUpdate);
                loadChanges(response.lastUpdate);
            }
        }
    });
}

/**
 * Lädt die Änderungen seit dem letzten Update
 */
function loadChanges(sinceDate) {
    $.ajax({
        url: window.GLG.controllerUrl,
        type: 'POST',
        data: {
            action: 'getChanges',
            since: sinceDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var html = '<p>Geänderte Einträge: <strong>' + response.count + '</strong></p>';
                if (response.count > 0) {
                    html += '<ul>';
                    $.each(response.changes, function(index, change) {
                        html += '<li>' + change.section + ' - ' + change.key + '</li>';
                    });
                    html += '</ul>';
                } else {
                    html = '<div class="alert alert-info">Keine Änderungen</div>';
                }
                $('#changesList').html(html);
                $('#changesSinceDate').text(sinceDate);
            }
        }
    });
}

/**
 * Lädt das Protokoll
 */
function loadLog() {
    $.ajax({
        url: window.GLG.controllerUrl,
        type: 'POST',
        data: {action: 'getLog'},
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var html = '';
                $.each(response.log, function(index, entry) {
                    var statusClass = entry.status === 'success' ? 'success' : 'danger';
                    html += '<tr class="' + statusClass + '">';
                    html += '<td>' + entry.date + '</td>';
                    html += '<td>' + entry.action + '</td>';
                    html += '<td>' + entry.source + '</td>';
                    html += '<td>' + entry.target + '</td>';
                    html += '<td><span class="label label-' + statusClass + '">' + entry.status + '</span></td>';
                    html += '<td>' + entry.details + '</td>';
                    html += '</tr>';
                });
                $('#logTable tbody').html(html);
            }
        }
    });
}

/**
 * Lädt die Einstellungen
 */
function loadSettings() {
    $.ajax({
        url: window.GLG.controllerUrl,
        type: 'POST',
        data: {action: 'getSettings'},
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $.each(response.settings, function(key, value) {
                    var input = $('[name="' + key + '"]');
                    if (input.attr('type') === 'checkbox') {
                        input.prop('checked', value === '1' || value === true);
                    } else {
                        input.val(value);
                    }
                });
                $('#apiProvider').trigger('change');
            }
        }
    });
}

/**
 * Zeigt eine Nachricht an
 */
function showMessage(containerId, type, message) {
    var html = '<div class="alert alert-' + type + ' alert-dismissible">';
    html += '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    html += message;
    html += '</div>';
    
    $('#' + containerId).html(html);
    
    // Auto-hide nach 5 Sekunden bei Erfolg
    if (type === 'success') {
        setTimeout(function() {
            $('#' + containerId + ' .alert').fadeOut();
        }, 5000);
    }
}

// CSS für spinning icon
$('<style>')
    .prop('type', 'text/css')
    .html('.spinning { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }')
    .appendTo('head');

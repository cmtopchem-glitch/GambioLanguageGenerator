# Claude Code - Aktueller Arbeitsstand

**Datum:** 2025-11-12 19:55 Uhr
**Letzter Commit:** b0615f4 - FIX: Standard-Dateien kopieren & Progress-System Routing
**GitHub:** https://github.com/cmtopchem-glitch/GambioLanguageGenerator

---

## ⚠️ Aktueller Status - IN ENTWICKLUNG (NICHT PRODUKTIV)

### Was funktioniert
- ✅ ModuleCenter Integration mit Smarty-Templates
- ✅ UI mit Bootstrap-Tabs (Sprachen generieren, Vergleichen, Einstellungen)
- ✅ API-Settings speichern (OpenAI Key, Provider, Model)
- ✅ Automatische Verzeichnis-Erstellung mit korrekten Berechtigungen (0775)
- ✅ Standard-Dateien werden kopiert (flag.png, icon.gif, init.inc.php, admin/*)
- ✅ 23+ Sprachen unterstützt
- ✅ Detailliertes Logging via error_log()

### ❌ Was NICHT funktioniert
- ❌ **Progress-Anzeige:** AJAX Polling funktioniert nicht (Session-Lock Problem)
- ❌ **Übersetzung startet nicht:** Hängt beim Bootstrap (application_top.php)
- ❌ **PHP-FPM Worker hängen:** Bei langen Requests/Tests
- ❌ **Mail-Templates kopieren:** copyDirectoryRecursive() temporär deaktiviert (Timeout)
- ❌ **Stop-Button:** Erscheint nicht (weil Progress nicht funktioniert)

### Kritische Probleme (2025-11-12)

#### Problem 1: Session-Lock verhindert Progress-Polling
**Symptom:** Browser zeigt "Starte Übersetzung..." aber keine Progress-Updates

**Ursache:**
- `actionGenerate()` macht einen Long-Running AJAX Request
- Session ist während des gesamten Requests gelockt
- `actionGetProgress()` kann Session nicht lesen (blockiert)

**Versuchte Lösung:**
- `session_write_close()` nach Progress-Init → POST-Daten nicht mehr lesbar

**TODO:**
- Alle `$_SESSION['glg_progress']` Updates mit `session_start()` / `session_write_close()` wrappen
- ODER: Background-Job für Übersetzungen (beste Lösung)

#### Problem 2: Übersetzung startet nie
**Symptom:** Keine Dateien werden in `/lang/czech/` erstellt

**Ursache (vermutet):**
- `copyDirectoryRecursive()` hängt bei Mail-Templates
- Oder: Gambio Bootstrap (application_top.php) hat Probleme

**Aktueller Workaround:**
- Mail-Templates kopieren deaktiviert (Zeile 436-447 in GLGFileWriter.php)

#### Problem 3: PHP-FPM Worker hängen
**Symptom:** Server wird langsam, Admin nicht erreichbar

**Ursache:**
- Test-Scripts mit Gambio-Bootstrap hängen endlos
- PHP Worker gehen nicht in Timeout

**Lösung:**
```bash
sudo systemctl restart php8.2-fpm
```

### Wichtige Dateien
- **Controller:** `Admin/Classes/Controllers/GambioLanguageGeneratorModuleCenterModuleController.inc.php` (650 Zeilen)
  - Zeile 28-35: Action-Routing für getProgress/stop hinzugefügt
  - Zeile 157-398: actionGenerate() - Haupt-Übersetzungs-Logik
  - Zeile 400-427: actionGetProgress() & actionStop() - AJAX Endpoints
  - Zeile 602-611: _updateProgress() Helper (noch nicht verwendet)

- **Template:** `Admin/Templates/module_content.html` (Smarty mit Tabs, Progress, Stop-Button)
  - Zeile 345-374: Progress-Polling JavaScript (alle 500ms)
  - Zeile 438-499: Form Submit Handler mit AJAX

- **GLGFileWriter.php:** `includes/GLGFileWriter.php`
  - Zeile 361-454: copyLanguageDefaults() - Kopiert Standard-Dateien
  - Zeile 435-447: copyDirectoryRecursive() für Mail-Templates (DEAKTIVIERT)
  - Zeile 529-573: copyDirectoryRecursive() Methode

- **Includes:**
  - `GLGReader.php` - Liest Sprachdaten aus language_phrases_cache
  - `GLGTranslator.php` - OpenAI/DeepL Integration
  - `GLGFileWriter.php` - Schreibt Dateien mit korrekten Permissions
  - `GLGCompare.php` - Sprachvergleich
  - `GLGCore.php` - Core-Funktionalität

### Wichtige Befehle
```bash
# Cache löschen (IMMER nach Code-Änderungen!)
cd /srv/www/test.redozone && php clearcache.php

# Git Status
cd /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator && git status

# Syntax prüfen
php -l Admin/Classes/Controllers/GambioLanguageGeneratorModuleCenterModuleController.inc.php

# PHP-FPM neu starten (bei hängenden Workern)
sudo systemctl restart php8.2-fpm

# Czech-Verzeichnis prüfen
ls -la /srv/www/test.redozone/lang/czech/
```

### Gambio-Kontext
- **Version:** Gambio 4.x (kompatibel mit 3.0-4.9)
- **Framework:** GXModules System
- **Parent Class:** AbstractModuleCenterModuleController
- **Response Types:** AdminPageHttpControllerResponse, AdminLayoutHttpControllerResponse
- **Datenbank:** language_phrases_cache Tabelle
- **Session:** PHP Session für Progress-Tracking

### Modul-Funktionalität
Das Modul soll Gambio-Sprachdateien automatisch übersetzen:
1. Quellsprache wählen (z.B. german)
2. Zielsprachen auswählen (z.B. czech, italian)
3. KI-Übersetzung via OpenAI API (GPT-4o, GPT-4o-mini)
4. Sprachdateien schreiben nach `/lang/{sprache}/`
5. Standard-Dateien kopieren (flag.png, icon.gif, init.inc.php, etc.)

### Besonderheit: Gemischte Quellsprachen
Die Gambio-Datenbank kann für eine Sprache (z.B. deutsch, language_id=2) Einträge mit verschiedenen Source-Pfaden enthalten:
- `source = "german/buttons.php"` mit deutschem Text
- `source = "english/buttons.php"` mit englischem Text (!!)

**Lösung:** GLGTranslator.php erweitert OpenAI-Prompt um automatische Sprach-Erkennung:
- OpenAI erkennt tatsächliche Sprache jedes Textes
- Übersetzt ALLES zur Zielsprache
- Funktioniert mit beliebigen Sprachmischungen

---

## 📋 Für den nächsten Entwickler

### Sofort-Aufgaben (Critical)
1. **Session-Lock Problem lösen**
   - Option A: `_updateProgress()` Helper an allen 23 Stellen verwenden
   - Option B: Background-Job für Übersetzungen (empfohlen!)

2. **Übersetzung zum Laufen bringen**
   - Debug: Warum hängt Bootstrap?
   - Test: Minimales Script ohne application_top.php

3. **Mail-Templates kopieren fixen**
   - copyDirectoryRecursive() optimieren (Chunks, Timeout handling)
   - Oder: Asynchron mit AJAX Progress

### Mittelfristig (High Priority)
4. **Background-Job implementieren**
   - Cronjob oder Gearman/Redis Queue
   - Browser zeigt nur Progress, läuft nicht Request

5. **Error-Handling verbessern**
   - Try/Catch um API-Calls
   - Retry-Logik bei Timeouts
   - Partial Success (einige Dateien übersetzt)

### Nice-to-Have
6. **Testing**
   - Unit Tests für GLGReader, GLGTranslator, GLGFileWriter
   - Integration Tests ohne echten API-Call

7. **Performance**
   - Batch-Übersetzungen (mehrere Dateien pro API-Call)
   - Rate-Limiting für OpenAI

---

## 🐛 Debugging-Tipps

### Übersetzung hängt?
1. Prüfe ob Dateien erstellt werden: `ls -la /srv/www/test.redozone/lang/czech/`
2. Prüfe PHP-FPM Worker: `ps aux | grep php-fpm | grep -v grep`
3. Prüfe Error-Log: `tail -50 /srv/www/test.redozone/export/php_errors.log | grep GLG`
4. Bei Hang: `sudo systemctl restart php8.2-fpm`

### Progress funktioniert nicht?
1. Browser DevTools öffnen (F12)
2. Network Tab: AJAX Requests zu `action=getProgress` prüfen
3. Console Tab: JavaScript-Fehler suchen
4. Prüfe Session: `grep -r "glg_progress" /var/lib/php/sessions/` (mit sudo)

### AJAX kommt nicht an?
1. Eingeloggt im Admin? (sonst 302 Redirect)
2. Cache gelöscht? `php clearcache.php`
3. Syntax OK? `php -l Controller.php`

---

**Server:** test.redozone.de
**User:** cm
**Pfad:** /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator/
**Branch:** main

Viel Erfolg! 🚀

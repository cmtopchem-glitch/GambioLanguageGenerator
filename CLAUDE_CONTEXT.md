# Claude Code - Aktueller Arbeitsstand

**Datum:** 2025-11-13 (Session fortgesetzt)
**Letzter Commit:** 94c7afc - DEBUG: Erweitert OpenAI API Error-Handling & Logging
**GitHub:** https://github.com/cmtopchem-glitch/GambioLanguageGenerator
**Branch:** claude/gambio-language-generator-011CV4hTchAi6UmAhuQm88sk

---

## ⚠️ Aktueller Status - DEBUGGING PHASE

### Was funktioniert ✅
- ✅ ModuleCenter Integration mit Smarty-Templates
- ✅ UI mit Bootstrap-Tabs (Sprachen generieren, Vergleichen, Einstellungen)
- ✅ API-Settings speichern (OpenAI Key, Provider, Model)
- ✅ **System Prompt editierbar** in Einstellungen (NEU seit 460996f)
- ✅ Automatische Verzeichnis-Erstellung mit korrekten Berechtigungen (0775)
- ✅ Standard-Dateien werden kopiert (flag.png, icon.gif, init.inc.php, admin/*)
- ✅ 23+ Sprachen unterstützt
- ✅ **Progress-Anzeige funktioniert** (Session-Lock gelöst seit 8bca953)
- ✅ **Quellsprache-Filter funktioniert** (korrekte SQL-Filterung seit 34022e0)
- ✅ Detailliertes Logging via error_log()
- ✅ **Rate Limiting** zwischen API-Calls (1 Sekunde Pause seit 6c2b955)
- ✅ **Erweiterte Error-Handling** mit cURL Timeout-Detection (seit 94c7afc)

### ❌ Was noch NICHT funktioniert
- ❌ **PHP-FPM Worker hängt bei OpenAI API Call** - Erste Datei wird übersetzt, dann Stillstand
- ⚠️ **Ursache unbekannt** - Debugging läuft mit erweiterten Logs

### Gelöste Probleme ✅

#### Problem 1: Session-Lock verhindert Progress-Polling ✅ GELÖST
**Symptom:** Browser zeigt "Starte Übersetzung..." aber keine Progress-Updates

**Lösung (Commit 8bca953):**
- POST-Daten VOR `session_write_close()` auslesen und in Variablen speichern
- `session_write_close()` direkt nach Initialisierung aufrufen
- Alle Session-Updates mit `_updateProgress()` Helper-Methode
- Helper macht: `session_start()` → Update → `session_write_close()`

#### Problem 2: Falsche Quellsprache wird gelesen ✅ GELÖST
**Symptom:** Trotz Auswahl "german" wurden "english/..." und "french/..." Dateien übersetzt

**Ursache:**
- Datenbank `language_phrases_cache` kann für language_id=2 (deutsch) auch `source="english/..."` enthalten
- GLGReader filterte nur nach language_id, nicht nach source-Pfad

**Lösung (Commit 34022e0):**
- SQL-Queries erweitert mit Source-Filter:
  - Core Files: `AND source LIKE '$language/%'`
  - GXModules: `AND source LIKE '%/$language/%'`
- Siehe GLGReader.php Zeile 67 und 109

#### Problem 3: System Prompt nicht sichtbar ✅ GELÖST
**Symptom:** System Prompt konnte nicht angesehen oder editiert werden

**Lösung (Commit 460996f):**
- Textarea in Einstellungen-Tab hinzugefügt (module_content.html Zeile 269-283)
- System Prompt in Datenbank speichern (Controller Zeile 107, 112, 127)
- Variable-Replacement: {{sourceLanguageName}}, {{targetLanguageName}}, {{context}}
- GLGTranslator lädt Prompt aus Settings (Zeile 26, 65-68)

### Aktuelles Problem - IN DEBUGGING 🔍

#### Problem: PHP-FPM Worker hängt bei OpenAI API Call
**Symptom:**
- Erste Datei (honeygrid.lang.inc.php) wird erfolgreich übersetzt
- Danach stoppt Prozess komplett - keine weiteren Logs
- Worker antwortet nicht mehr, keine Timeouts
- Nach 6+ Stunden immer noch keine Reaktion

**Bisher versucht:**
1. ✅ Batch-Größe von 50 auf 20 reduziert (Commit 6c2b955)
2. ✅ Rate Limiting: 1 Sekunde Pause zwischen API-Calls (Commit 6c2b955)
3. ✅ Erweiterte Error-Logs + cURL Timeout Detection (Commit 94c7afc)

**Erwartete Debug-Ausgabe im Log:**
```
GLGTranslator: Translating from 'german' (Deutsch) to 'polish' (Polski)
GLGTranslator: Context: german/..., Entries count: X
GLGTranslator: Using system prompt (first 100 chars): Du bist ein...
GLGTranslator: Sending request to OpenAI API...
GLGTranslator: Received response from OpenAI (HTTP 200, 2.5s)  ← DIESES LOG FEHLT!
GLGTranslator: Successfully translated X entries
```

**Mögliche Ursachen:**
- cURL hängt ohne Timeout-Exception zu werfen
- OpenAI API antwortet nicht / sehr langsam
- PHP-FPM Worker crash nach erstem API-Call
- Netzwerk-Problem zwischen Server und OpenAI

**Nächster Schritt:**
- Code deployen und Test mit Live-Log-Monitoring: `tail -f /var/log/php8.2-fpm/error.log | grep GLG`
- Wenn "Sending request..." erscheint aber KEIN "Received response..." → cURL hängt
- Wenn cURL Error #28 → Connection Timeout
- Wenn HTTP 429 → Rate Limiting von OpenAI

### Wichtige Dateien & Änderungen

- **Controller:** `Admin/Classes/Controllers/GambioLanguageGeneratorModuleCenterModuleController.inc.php`
  - Zeile 180: `session_write_close()` nach Init (8bca953)
  - Zeile 328: Batch-Größe auf 20 reduziert (6c2b955)
  - Zeile 334-337: Rate Limiting mit sleep(1) (6c2b955)
  - Zeile 602-611: `_updateProgress()` Helper für Session-Updates
  - Zeile 54, 107, 112, 127: System Prompt laden/speichern (460996f)

- **Template:** `Admin/Templates/module_content.html`
  - Zeile 269-283: System Prompt Textarea mit Variablen-Hilfe (460996f)
  - Zeile 345-374: Progress-Polling JavaScript (alle 500ms)
  - Zeile 438-499: Form Submit Handler mit AJAX

- **GLGReader:** `includes/GLGReader.php`
  - Zeile 67: Core Files Filter: `AND source LIKE '$language/%'` (34022e0)
  - Zeile 109: GXModules Filter: `AND source LIKE '%/$language/%'` (34022e0)
  - Zeile 71, 113: Debug-Logs für Filterung

- **GLGTranslator:** `includes/GLGTranslator.php`
  - Zeile 18, 26: System Prompt aus Settings laden (460996f)
  - Zeile 65-68: Variable-Replacement für Prompt (460996f)
  - Zeile 87: Log vor API-Call: "Sending request..." (94c7afc)
  - Zeile 96-97: cURL Timeouts (CURLOPT_TIMEOUT=120s, CONNECTTIMEOUT=30s) (94c7afc)
  - Zeile 99-101: Dauer-Messung für API-Call (94c7afc)
  - Zeile 104-110: cURL Error Detection mit errno/message (94c7afc)
  - Zeile 115: Log nach API-Call: "Received response (HTTP X, Ys)" (94c7afc)
  - Zeile 354-374: Default System Prompt Fallback-Methode (460996f)

- **GLGFileWriter:** `includes/GLGFileWriter.php`
  - Zeile 361-454: copyLanguageDefaults() - Kopiert Standard-Dateien
  - Zeile 529-573: copyDirectoryRecursive() Methode

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
1. ✅ ~~**Session-Lock Problem lösen**~~ → GELÖST mit `_updateProgress()` Helper (8bca953)

2. ✅ ~~**Quellsprache-Filter implementieren**~~ → GELÖST mit source-Pfad Filterung (34022e0)

3. ✅ ~~**System Prompt editierbar machen**~~ → GELÖST mit UI + DB-Storage (460996f)

4. **🔍 AKTUELL: PHP-FPM Worker Hang debuggen**
   - Erweiterte Logs deployed (94c7afc, 6c2b955)
   - Nächster Schritt: Code deployen und Test mit `tail -f` log monitoring
   - Erwartung: Logs zeigen wo genau der Hang auftritt

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

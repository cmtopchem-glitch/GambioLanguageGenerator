# Deployment & Testing Guide
**Branch:** claude/gambio-language-generator-011CV4hTchAi6UmAhuQm88sk
**Letzte Updates:** 5 Commits (8bca953 bis 94c7afc)
**Status:** Bereit zum Deployment & Testing

---

## 📦 Was wurde implementiert?

### Commit-Historie (neueste zuerst):
1. **94c7afc** - DEBUG: Erweitert OpenAI API Error-Handling & Logging
2. **6c2b955** - IMPROVE: Batch-Größe reduziert & Rate Limiting eingebaut
3. **34022e0** - FIX: Quellsprache wird jetzt korrekt beachtet
4. **460996f** - FEATURE: System Prompt in Einstellungen editierbar
5. **8bca953** - FIX: Session-Lock Problem für Progress-Polling gelöst

### Wichtigste Änderungen:

#### ✅ Session-Lock Problem gelöst (8bca953)
- Progress-Anzeige funktioniert jetzt während der Übersetzung
- `session_write_close()` nach Initialisierung
- `_updateProgress()` Helper für Session-Updates

#### ✅ Quellsprache-Filter implementiert (34022e0)
- SQL-Queries filtern jetzt nach source-Pfad
- Keine falschen "english/..." oder "french/..." Einträge mehr
- Core Files: `AND source LIKE 'german/%'`
- GXModules: `AND source LIKE '%/german/%'`

#### ✅ System Prompt editierbar (460996f)
- Textarea in Einstellungen-Tab
- Variablen-Ersetzung: {{sourceLanguageName}}, {{targetLanguageName}}, {{context}}
- Speicherung in Datenbank

#### ✅ Batch-Größe & Rate Limiting (6c2b955)
- Batch-Größe von 50 auf 20 Einträge reduziert
- 1 Sekunde Pause zwischen API-Calls
- Reduziert Last auf OpenAI API und PHP-FPM

#### ✅ Erweiterte Error-Logs (94c7afc)
- Detailliertes Logging vor/nach jedem API-Call
- cURL Error Detection mit errno und message
- Dauer-Messung für jeden API-Call
- Connection Timeout (30s) zusätzlich zu Request Timeout (120s)

---

## 🚀 Deployment-Schritte

### 1. Server vorbereiten

```bash
# Zum Gambio-Root wechseln
cd /srv/www/test.redozone

# Optional: PHP-FPM neu starten für frischen Start
sudo systemctl restart php8.2-fpm

# Status prüfen
sudo systemctl status php8.2-fpm
```

### 2. Code deployen

```bash
# Zum Modul-Verzeichnis
cd GXModules/REDOzone/GambioLanguageGenerator

# Aktuellen Branch prüfen
git branch

# Sollte anzeigen: * claude/gambio-language-generator-011CV4hTchAi6UmAhuQm88sk

# Code pullen (alle 5 Commits)
git pull origin claude/gambio-language-generator-011CV4hTchAi6UmAhuQm88sk

# Sollte anzeigen:
# Already up to date. (wenn bereits gepullt)
# oder die Updates

# Commit-Historie prüfen
git log --oneline -5

# Sollte anzeigen:
# 7cc6d40 DOCS: Aktualisiert CLAUDE_CONTEXT.md - Fortschritt dokumentiert
# 94c7afc DEBUG: Erweitert OpenAI API Error-Handling & Logging
# 6c2b955 IMPROVE: Batch-Größe reduziert & Rate Limiting eingebaut
# 34022e0 FIX: Quellsprache wird jetzt korrekt beachtet
# 460996f FEATURE: System Prompt in Einstellungen editierbar
```

### 3. Cache löschen (WICHTIG!)

```bash
# Zurück zum Gambio-Root
cd /srv/www/test.redozone

# Cache löschen
php clearcache.php

# Sollte anzeigen: "Cache cleared successfully" oder ähnlich
```

### 4. Berechtigungen prüfen (optional, sollte passen)

```bash
# Prüfe ob www-data Schreibrechte hat
ls -la GXModules/REDOzone/GambioLanguageGenerator/

# Sollte www-data:www-data und 0775 anzeigen
```

---

## 🧪 Test-Durchführung

### Vorbereitung:

1. **Terminal 1 öffnen** für Log-Monitoring:
```bash
ssh cm@test.redozone.de
tail -f /var/log/php8.2-fpm/error.log | grep --line-buffered GLG
```

2. **Browser öffnen:**
   - Gambio Admin einloggen
   - ModuleCenter → GambioLanguageGenerator öffnen

### Test-Szenario 1: Settings prüfen

1. Gehe zu Tab "Einstellungen"
2. Prüfe ob **System Prompt** Textarea erscheint
3. Prüfe ob Default-Prompt angezeigt wird mit Variablen:
   - `{{sourceLanguageName}}`
   - `{{targetLanguageName}}`
   - `{{context}}`
4. Optional: Prompt leicht ändern und speichern (z.B. eine Zeile ändern)
5. Seite neu laden → Änderung sollte gespeichert sein

**Erwartetes Ergebnis:** ✅ System Prompt ist sichtbar und editierbar

### Test-Szenario 2: Kleine Übersetzung (EMPFOHLEN)

1. Tab "Sprachen generieren"
2. **Einstellungen:**
   - Quellsprache: **german**
   - Zielsprachen: **NUR polish** auswählen (1 Sprache für schnellen Test!)
   - Core-Dateien: **NEIN** (deaktivieren!)
   - GXModule Dateien: **JA** (aktivieren)
   - Module: **Nur 1-2 kleine Module** auswählen (z.B. HoneyGrid)
3. Speichern und Übersetzen

**Im Terminal-Log sollte erscheinen:**

```
GLG: Starting language generation...
GLG: Source language: german, Target languages: polish
GLG: Reading language data...
GLGReader: Reading GXModules with filter: language_id for 'german' AND source LIKE '%/german/%'
GLG: Found X source files
GLG: Processing batch 1/Y for polish
GLG: Translating source file: GXModules/.../german/...
GLGTranslator: Translating from 'german' (Deutsch) to 'polish' (Polski)
GLGTranslator: Context: GXModules/..., Entries count: 15
GLGTranslator: Using system prompt (first 100 chars): Du bist ein...
GLGTranslator: Sending request to OpenAI API...
GLGTranslator: Received response from OpenAI (HTTP 200, 2.5s)  ← WICHTIG!
GLGTranslator: Successfully translated 15 entries
... (weitere Dateien) ...
GLG: Language generation completed successfully!
```

**Kritische Log-Zeilen:**
- ✅ **"Sending request to OpenAI API..."** → API-Call startet
- ✅ **"Received response from OpenAI (HTTP 200, X.Xs)"** → API-Call erfolgreich!
- ❌ **Wenn "Received response" FEHLT** → cURL hängt!
- ❌ **"cURL Error #28"** → Connection Timeout
- ❌ **"HTTP 429"** → OpenAI Rate Limiting

**Im Browser sollte erscheinen:**
- Progress-Bar aktualisiert sich alle 500ms
- "Sprache: polish 1/1"
- "Aktuelle Datei: GXModules/.../german/..."
- "Fortschritt: 1/X Dateien (Y%)"

**Erwartetes Ergebnis:**
- ✅ Progress-Anzeige funktioniert
- ✅ Übersetzung läuft durch ohne Hängen
- ✅ Dateien werden erstellt in `/srv/www/test.redozone/lang/polish/`

### Test-Szenario 3: Quellsprache-Filter prüfen

1. Während der Übersetzung (Szenario 2) im Log prüfen:
   - Alle "Translating source file:" Zeilen sollten `german/...` enthalten
   - **KEINE** Zeilen mit `english/...` oder `french/...`!

2. Nach Übersetzung prüfen:
```bash
ls -la /srv/www/test.redozone/lang/polish/
```
- Sollte Dateien enthalten (nicht leer!)
- Stichprobe: Eine Datei öffnen und prüfen ob polnischer Text drin steht

**Erwartetes Ergebnis:** ✅ Nur deutsche Quellen werden übersetzt

---

## 🔍 Debugging bei Problemen

### Problem: "Received response..." Log fehlt

**Bedeutung:** cURL hängt beim API-Call, bekommt keine Antwort von OpenAI

**Mögliche Ursachen:**
1. OpenAI API antwortet sehr langsam (>120s)
2. Netzwerk-Problem zwischen Server und OpenAI
3. Firewall blockiert ausgehende Verbindungen

**Debug-Schritte:**
```bash
# Test: Kann Server OpenAI erreichen?
curl -I https://api.openai.com/v1/models

# Sollte HTTP/2 200 zurückgeben

# Prüfe ob Firewall aktiv ist
sudo iptables -L -n | grep REJECT
sudo ufw status

# Prüfe DNS-Auflösung
nslookup api.openai.com
```

### Problem: cURL Error #28 (Connection Timeout)

**Bedeutung:** Verbindung zu OpenAI kann nicht hergestellt werden

**Mögliche Ursachen:**
1. OpenAI API ist down
2. Firewall blockiert Port 443
3. DNS-Problem

**Debug-Schritte:**
- OpenAI Status prüfen: https://status.openai.com/
- Firewall-Regeln prüfen (siehe oben)
- Anderen API-Endpoint testen

### Problem: HTTP 429 (Rate Limit)

**Bedeutung:** OpenAI API blockiert wegen zu vieler Anfragen

**Lösung:**
- Rate Limiting ist bereits eingebaut (1s Pause)
- Ggf. auf 2s erhöhen in Controller Zeile 336: `sleep(2);`
- Oder kleinere Batches: Zeile 328: `$batchSize = 10;`

### Problem: Übersetzung hängt nach N Dateien

**Debug:**
1. Welche Datei war die letzte?
   - Im Log: Letzte "Translating source file:" Zeile
2. Wie groß ist die Datei?
   - Im Log: "Entries count: X"
3. Kam "Sending request..." aber kein "Received response..."?
   - Dann hängt cURL bei dieser Datei

**Mögliche Lösung:**
- Batch-Größe weiter reduzieren (aktuell 20)
- Timeout erhöhen: GLGTranslator.php Zeile 96: `curl_setopt($ch, CURLOPT_TIMEOUT, 300);`

### Problem: PHP-FPM Worker crashed

**Log prüfen:**
```bash
tail -50 /var/log/php8.2-fpm/error.log | grep -i segfault
tail -50 /var/log/apache2/error.log | grep -i fastcgi
```

**Symptome:**
- Apache Error: "Failed to read FastCGI header"
- Apache Error: "Connection reset by peer"
- Im PHP-FPM Log: "SIGSEGV" oder "segmentation fault"

**Lösung:**
- PHP-FPM neu starten: `sudo systemctl restart php8.2-fpm`
- PHP Extensions prüfen: `php -m`
- cURL Extension status: `php -i | grep cURL`

---

## 📊 Erwartete Performance

Mit den aktuellen Einstellungen (Batch-Größe 20, Rate Limiting 1s):

- **1 Datei mit 20 Einträgen:** ~3-5 Sekunden
- **100 Dateien:** ~15-25 Minuten (bei 5 Batches pro Datei = 500 API-Calls + 500s Pause)
- **500 Dateien:** ~75-125 Minuten

**API-Kosten (GPT-4o-mini):**
- Pro 1000 Einträge: ~$0.01-0.02
- Komplette Sprache (30.000 Einträge): ~$0.30-0.60

**API-Kosten (GPT-4o):**
- Pro 1000 Einträge: ~$0.10-0.20
- Komplette Sprache: ~$3-6

---

## ✅ Success Criteria

Der Test ist erfolgreich wenn:

1. ✅ **Progress-Anzeige funktioniert**
   - Browser zeigt aktuellen Fortschritt
   - Updates alle 500ms
   - Progress-Bar animiert

2. ✅ **Logs zeigen alle Schritte**
   - "Sending request..." für jeden API-Call
   - "Received response (HTTP 200, X.Xs)" für jeden API-Call
   - "Successfully translated X entries"

3. ✅ **Dateien werden erstellt**
   - `/srv/www/test.redozone/lang/polish/` existiert
   - Dateien enthalten polnischen Text
   - Keine leeren Dateien

4. ✅ **Nur deutsche Quellen verwendet**
   - Logs zeigen nur `german/...` als source
   - Keine `english/...` oder `french/...`

5. ✅ **Keine Worker-Crashes**
   - Apache Error Log bleibt sauber
   - PHP-FPM läuft stabil

6. ✅ **Übersetzung läuft durch**
   - Keine Hänger nach X Dateien
   - "Language generation completed successfully!"

---

## 📞 Support

Bei Fragen oder Problemen:
- Error-Logs teilen (siehe oben)
- Browser DevTools Console-Log teilen (F12 → Console)
- Screenshot vom UI (Progress-Anzeige)

**Wichtig:** Live-Logs während des Tests mit `tail -f` mitlaufen lassen, damit wir sehen wo es genau hängt!

---

**Version:** Testing Version 1.0
**Branch:** claude/gambio-language-generator-011CV4hTchAi6UmAhuQm88sk
**Datum:** 2025-11-13

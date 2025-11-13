# 🚀 Deployment Checklist

## Schritt 1: Code deployen

```bash
# SSH zum Server
ssh cm@test.redozone.de

# Zum Modul-Verzeichnis
cd /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator

# Aktuellen Branch prüfen
git branch
# Sollte zeigen: * claude/gambio-language-generator-011CV4hTchAi6UmAhuQm88sk

# Code pullen
git pull origin claude/gambio-language-generator-011CV4hTchAi6UmAhuQm88sk

# Commits prüfen - sollte zeigen:
# 03c758f DOCS: Ready-for-Testing Summary erstellt
# 623c262 DOCS: Deployment & Testing Guide erstellt
# 7cc6d40 DOCS: Aktualisiert CLAUDE_CONTEXT.md
# 94c7afc DEBUG: Erweitert OpenAI API Error-Handling & Logging  ← WICHTIG!
# 6c2b955 IMPROVE: Batch-Größe reduziert & Rate Limiting         ← WICHTIG!
git log --oneline -5
```

## Schritt 2: Cache löschen

```bash
cd /srv/www/test.redozone
php clearcache.php
```

## Schritt 3: PHP-FPM neu starten (optional aber empfohlen)

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl status php8.2-fpm
# Sollte "active (running)" zeigen
```

## Schritt 4: Logs vorbereiten

**Terminal 1 - PHP-FPM Logs (das ist wo die Debug-Infos sind!):**
```bash
tail -f /var/log/php8.2-fpm/error.log | grep --line-buffered GLG
```

**Optional - Terminal 2 - Apache Logs (für FastCGI Errors):**
```bash
tail -f /var/log/apache2/error.log | grep --line-buffered proxy_fcgi
```

## Schritt 5: Test durchführen

**Browser:**
1. https://test.redozone.de/admin/
2. ModuleCenter → GambioLanguageGenerator
3. Tab "Einstellungen" → Prüfe ob **System Prompt** Textarea sichtbar ist
4. Tab "Sprachen generieren":
   - Quellsprache: **german**
   - Zielsprache: **NUR polish** (1 Sprache!)
   - Core-Dateien: **NEIN** ❌
   - GXModule Dateien: **JA** ✅
   - Module: **Nur 1-2 kleine** auswählen (HoneyGrid, etc.)
5. Speichern und Übersetzen

## Schritt 6: Logs beobachten

**Im PHP-FPM Terminal sollte erscheinen:**
```
[13-Nov-2025 HH:MM:SS] GLG: Starting language generation...
[13-Nov-2025 HH:MM:SS] GLG: Source language: german, Target languages: polish
[13-Nov-2025 HH:MM:SS] GLGReader: Reading GXModules with filter...
[13-Nov-2025 HH:MM:SS] GLG: Found X source files
[13-Nov-2025 HH:MM:SS] GLGTranslator: Translating from 'german' (Deutsch) to 'polish' (Polski)
[13-Nov-2025 HH:MM:SS] GLGTranslator: Context: GXModules/..., Entries count: 15
[13-Nov-2025 HH:MM:SS] GLGTranslator: Using system prompt (first 100 chars): Du bist ein...
[13-Nov-2025 HH:MM:SS] GLGTranslator: Sending request to OpenAI API...
[13-Nov-2025 HH:MM:SS] GLGTranslator: Received response from OpenAI (HTTP 200, 2.5s)
[13-Nov-2025 HH:MM:SS] GLGTranslator: Successfully translated 15 entries
... (weitere Dateien) ...
```

**Kritische Zeilen:**
- ✅ "Sending request to OpenAI API..." → API-Call startet
- ✅ "Received response from OpenAI (HTTP 200, X.Xs)" → API-Call erfolgreich!
- ❌ Wenn "Received response..." FEHLT → cURL hängt (das ist unser Problem!)
- ⚠️ "cURL Error #28: ..." → Connection Timeout
- ⚠️ "HTTP 429" → Rate Limiting

## Schritt 7: Ergebnis prüfen

```bash
# Wurden Dateien erstellt?
ls -la /srv/www/test.redozone/lang/polish/

# Eine Datei öffnen und prüfen
cat /srv/www/test.redozone/lang/polish/sections/irgendeine.lang.inc.php
# Sollte polnischen Text enthalten
```

## ✅ Success Criteria

- [ ] Code gepullt (git log zeigt 94c7afc & 6c2b955)
- [ ] Cache gelöscht
- [ ] PHP-FPM neu gestartet
- [ ] System Prompt Textarea ist sichtbar in Einstellungen
- [ ] Test gestartet
- [ ] PHP-FPM Log zeigt "GLG: Starting..." Nachrichten
- [ ] Für jeden "Sending request..." gibt es ein "Received response..."
- [ ] Progress-Bar aktualisiert sich im Browser
- [ ] Dateien werden erstellt in /lang/polish/
- [ ] Übersetzung läuft durch bis "completed successfully!"

## ❌ Wenn was schief geht

**Keine GLG-Logs im PHP-FPM Log:**
→ Code nicht deployed oder Cache nicht gelöscht

**System Prompt Textarea nicht sichtbar:**
→ Code nicht deployed oder Cache nicht gelöscht

**"Sending request..." aber kein "Received response...":**
→ cURL hängt - das ist unser Problem! Logs teilen!

**"cURL Error #28":**
→ Connection Timeout - OpenAI nicht erreichbar

**Apache Log zeigt "FastCGI header" Fehler:**
→ Worker crashed - PHP-FPM Log teilen für Details

---

**Nach dem Test bitte folgendes teilen:**
1. PHP-FPM Log Output (mit GLG-Zeilen)
2. Apache Log Output (wenn FastCGI Errors auftreten)
3. Browser Screenshot (Progress-Anzeige)
4. `ls -la /srv/www/test.redozone/lang/polish/` Output

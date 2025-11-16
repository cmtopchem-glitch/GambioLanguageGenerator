# 🔧 Troubleshooting Guide - Gambio Language Generator

**Branch:** claude/gambio-language-generator-011CV4hTchAi6UmAhuQm88sk
**Datum:** 2025-11-13

---

## 🚨 Häufige Probleme & Lösungen

### Problem 1: Worker hängt bei "Sending request to OpenAI API..."

**Symptome:**
- Log zeigt: `GLGTranslator: Sending request to OpenAI API...`
- KEIN Log: `GLGTranslator: Received response...`
- Worker antwortet nicht mehr
- Nach 5-10 Minuten: Apache Error "Failed to read FastCGI header"

**Ursache:**
`CURLOPT_NOSIGNAL` fehlt → Timeouts funktionieren nicht in PHP-FPM

**Lösung:**
```bash
# 1. Prüfe ob neuester Code deployed ist
cd /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator
git log --oneline -1
# Sollte zeigen: 859c51c FIX: CURLOPT_NOSIGNAL für Timeouts...

# 2. Falls NICHT → Code pullen
git pull origin claude/gambio-language-generator-011CV4hTchAi6UmAhuQm88sk

# 3. Cache löschen
cd /srv/www/test.redozone
php clearcache.php

# 4. PHP-FPM neu starten
sudo systemctl restart php8.2-fpm
```

**Verifikation:**
```bash
# Prüfe ob CURLOPT_NOSIGNAL im Code ist
grep -n "CURLOPT_NOSIGNAL" /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator/includes/GLGTranslator.php
# Sollte Zeile 98 anzeigen
```

---

### Problem 2: Progress-Anzeige bleibt bei "Starte Übersetzung..."

**Symptome:**
- Browser zeigt nur "Starte Übersetzung..."
- Keine Updates, keine Progress-Bar
- Logs zeigen nur getProgress() Calls

**Ursache:**
Session-Lock oder Code nicht deployed

**Lösung:**
```bash
# 1. Prüfe ob Session-Lock-Fix deployed ist
cd /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator
git log --oneline --grep="Session-Lock"
# Sollte zeigen: 8bca953 FIX: Session-Lock Problem für Progress-Polling gelöst

# 2. Cache löschen
cd /srv/www/test.redozone
php clearcache.php

# 3. Browser-Cache leeren
# Strg+Shift+R (Chrome/Firefox)
# Oder Inkognito-Fenster öffnen

# 4. PHP-FPM neu starten
sudo systemctl restart php8.2-fpm
```

**Debug:**
```bash
# Prüfe PHP-FPM Logs
grep "GLG: Session closed" /var/log/php8.2-fpm/error.log | tail -1
# Sollte bei jedem actionGenerate() erscheinen
```

---

### Problem 3: Falsche Quellsprache (english/french statt german)

**Symptome:**
- Ausgewählte Quellsprache: german
- Logs zeigen: `Translating source file: english/buttons.php`
- Oder: `french/sections/...`

**Ursache:**
SQL-Filter fehlt oder nicht deployed

**Lösung:**
```bash
# 1. Prüfe ob Quellsprache-Fix deployed ist
cd /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator
git log --oneline --grep="Quellsprache"
# Sollte zeigen: 34022e0 FIX: Quellsprache wird jetzt korrekt beachtet

# 2. Cache löschen
cd /srv/www/test.redozone
php clearcache.php
```

**Verifikation:**
```bash
# Prüfe ob Source-Filter im Code ist
grep -A5 "AND source LIKE" /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator/includes/GLGReader.php
# Sollte zeigen:
# Zeile 67: AND source LIKE '$language/%'
# Zeile 109: AND source LIKE '%/$language/%'
```

---

### Problem 4: System Prompt nicht sichtbar in Einstellungen

**Symptome:**
- Tab "Einstellungen" zeigt keine Textarea für System Prompt
- Nur API-Settings sichtbar

**Ursache:**
Code nicht deployed oder Cache nicht gelöscht

**Lösung:**
```bash
# 1. Prüfe ob System-Prompt-Feature deployed ist
cd /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator
git log --oneline --grep="System Prompt"
# Sollte zeigen: 460996f FEATURE: System Prompt in Einstellungen editierbar

# 2. Cache löschen
cd /srv/www/test.redozone
php clearcache.php

# 3. Browser-Cache leeren
# Strg+Shift+R
```

---

### Problem 5: "Received response" kommt, aber keine Dateien erstellt

**Symptome:**
- Logs zeigen erfolgreiche Übersetzungen
- "Successfully translated X entries"
- ABER: Keine Dateien in `/lang/[sprache]/`

**Debug:**
```bash
# 1. Prüfe ob Ziel-Verzeichnis existiert
ls -la /srv/www/test.redozone/lang/polish/
# Sollte existieren mit 0775 Rechten

# 2. Prüfe Berechtigungen
ls -ld /srv/www/test.redozone/lang/
# Sollte www-data:www-data zeigen

# 3. Prüfe PHP-FPM Logs nach Write-Errors
grep -i "permission denied\|failed to open\|could not write" /var/log/php8.2-fpm/error.log
```

**Lösung:**
```bash
# Berechtigungen korrigieren
sudo chown -R www-data:www-data /srv/www/test.redozone/lang/
sudo chmod -R 0775 /srv/www/test.redozone/lang/
```

---

### Problem 6: Apache Error "Failed to read FastCGI header"

**Symptome:**
```
[proxy_fcgi:error] AH01067: Failed to read FastCGI header
[proxy_fcgi:error] (104)Connection reset by peer
```

**Ursache:**
PHP-FPM Worker crashed (meist wegen CURLOPT_NOSIGNAL Problem, siehe Problem 1)

**Sofort-Fix:**
```bash
# PHP-FPM neu starten
sudo systemctl restart php8.2-fpm

# Status prüfen
sudo systemctl status php8.2-fpm
```

**Logs prüfen:**
```bash
# PHP-FPM Error Log
tail -50 /var/log/php8.2-fpm/error.log

# Nach Segfaults suchen
grep -i "segfault\|sigsegv\|core dump" /var/log/php8.2-fpm/error.log
```

**Dauerhafte Lösung:**
Siehe Problem 1 - CURLOPT_NOSIGNAL deployen

---

### Problem 7: cURL Error #28 "Operation timed out"

**Symptome:**
```
GLGTranslator: cURL Error #28: Operation timed out after 60001 milliseconds
GLG: Error translating batch X: OpenAI API Connection Error
```

**Ursache:**
OpenAI API antwortet nicht innerhalb 60 Sekunden (normal nach vielen Requests)

**Ist das schlimm?**
❌ NEIN! Das ist **gewünschtes Verhalten** seit Commit 859c51c!
- Exception wird gefangen
- Fehler wird geloggt
- Worker crashed NICHT
- Nächster Batch wird versucht

**Wenn es zu oft passiert:**
```bash
# 1. OpenAI Status prüfen
curl -I https://api.openai.com/v1/models
# Sollte HTTP/2 200 zurückgeben

# 2. Timeout erhöhen (falls nötig)
# Edit: includes/GLGTranslator.php Zeile 96
curl_setopt($ch, CURLOPT_TIMEOUT, 120);  # War 60, jetzt 120

# 3. Rate Limiting erhöhen
# Edit: Admin/Classes/Controllers/...Controller.inc.php Zeile 336
sleep(3);  # War 2, jetzt 3 Sekunden
```

---

### Problem 8: Übersetzung läuft, aber sehr langsam

**Symptome:**
- Jeder Batch dauert >30 Sekunden
- Fortschritt kriecht nur langsam voran

**Debug:**
```bash
# Prüfe API-Response-Zeiten in Logs
grep "Received response" /var/log/php8.2-fpm/error.log | tail -20
# Sollte zeigen: "(HTTP 200, 8-15s)" normal
# Wenn ">20s" → OpenAI langsam
```

**Ursachen:**
1. OpenAI API überlastet
2. Netzwerk-Probleme
3. Rate Limiting greift (429 Errors)

**Lösungen:**
```bash
# 1. Prüfe auf 429 Errors
grep "HTTP 429" /var/log/php8.2-fpm/error.log
# Wenn viele 429s → Rate Limiting erhöhen (siehe Problem 7)

# 2. Netzwerk-Test
ping -c 5 api.openai.com
traceroute api.openai.com

# 3. Kleineren Batch-Size versuchen
# Edit: Admin/Classes/Controllers/...Controller.inc.php Zeile 328
$batchSize = 10;  # War 20, jetzt 10
```

---

### Problem 9: OPcache Warning in Logs

**Symptom:**
```
PHP Warning: Zend OPcache can't be temporary enabled (it may be only disabled till the end of request) in Unknown on line 0
```

**Ist das schlimm?**
❌ NEIN! Das ist **harmlos** und kann ignoriert werden.
- Kommt von Gambio-Core
- Hat keine Auswirkung auf Übersetzung
- Kann nicht einfach gefixt werden (Gambio-Core)

---

### Problem 10: Cache wird nicht gelöscht

**Symptome:**
```bash
php clearcache.php
# Keine Ausgabe oder Error
```

**Lösung:**
```bash
# 1. Prüfe ob Datei existiert
ls -la /srv/www/test.redozone/clearcache.php

# 2. Manuell Cache löschen
sudo rm -rf /srv/www/test.redozone/cache/*
sudo rm -rf /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator/Cache/*

# 3. Berechtigungen prüfen
ls -la /srv/www/test.redozone/cache/
# Sollte www-data:www-data zeigen
```

---

## 🔍 Debug-Kommandos

### Generelle Checks

```bash
# 1. Aktueller Git-Stand
cd /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator
git log --oneline -5
# Sollte a0baeb2 und 859c51c enthalten!

# 2. PHP-FPM Status
sudo systemctl status php8.2-fpm
# Sollte "active (running)" zeigen

# 3. Letzte 50 GLG-Logs
grep "GLG" /var/log/php8.2-fpm/error.log | tail -50

# 4. Letzte Apache Errors
tail -20 /var/log/apache2/error.log | grep -i "fastcgi\|proxy"

# 5. Disk Space prüfen
df -h /srv/www/test.redozone
# Sollte genug Platz haben für Übersetzungen
```

### Live-Monitoring während Test

```bash
# Terminal 1 - PHP-FPM Logs
tail -f /var/log/php8.2-fpm/error.log | grep --line-buffered "GLG"

# Terminal 2 - Apache Logs
tail -f /var/log/apache2/error.log | grep --line-buffered "fastcgi"

# Terminal 3 - System Load
watch -n 2 'uptime && free -h'
```

### Nach Test - Analyse

```bash
# 1. Wie viele Batches wurden übersetzt?
grep "Successfully translated" /var/log/php8.2-fpm/error.log | wc -l

# 2. Wie viele Errors gab es?
grep "Error translating batch" /var/log/php8.2-fpm/error.log | wc -l

# 3. Durchschnittliche API-Response-Zeit
grep "Received response" /var/log/php8.2-fpm/error.log | tail -50 | grep -oP '\d+\.\d+s' | awk '{sum+=$1; count++} END {print "Avg:", sum/count, "s"}'

# 4. Welche Dateien wurden erstellt?
find /srv/www/test.redozone/lang/polish/ -name "*.php" -mmin -30 -ls
# Zeigt alle PHP-Dateien die in letzten 30 Minuten erstellt wurden
```

---

## 📞 Wenn nichts hilft

### Complete Reset

```bash
# 1. Job stoppen im Browser (Stop-Button)

# 2. Alle PHP-FPM Worker killen
sudo systemctl restart php8.2-fpm

# 3. Code komplett neu pullen
cd /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator
git fetch origin
git reset --hard origin/claude/gambio-language-generator-011CV4hTchAi6UmAhuQm88sk

# 4. Cache komplett löschen
sudo rm -rf /srv/www/test.redozone/cache/*
php /srv/www/test.redozone/clearcache.php

# 5. Modul-Cache löschen
sudo rm -rf /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator/Cache/*

# 6. Apache & PHP-FPM neu starten
sudo systemctl restart php8.2-fpm
sudo systemctl restart apache2

# 7. Warten 10 Sekunden
sleep 10

# 8. Neuer Test
```

### Logs für Support sammeln

```bash
# Bundle erstellen
cd /tmp
mkdir glg-debug-$(date +%Y%m%d-%H%M%S)
cd glg-debug-*

# Git Info
cd /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator
git log --oneline -10 > /tmp/glg-debug-*/git-log.txt
git status > /tmp/glg-debug-*/git-status.txt

# Logs
grep "GLG" /var/log/php8.2-fpm/error.log | tail -200 > /tmp/glg-debug-*/php-fpm-glg.log
tail -100 /var/log/apache2/error.log > /tmp/glg-debug-*/apache-error.log

# Config
php -i | grep -E "memory_limit|max_execution_time|timeout" > /tmp/glg-debug-*/php-config.txt
grep -E "request_terminate_timeout|pm.max" /etc/php/8.2/fpm/pool.d/*.conf > /tmp/glg-debug-*/php-fpm-config.txt

# System Info
uname -a > /tmp/glg-debug-*/system.txt
df -h >> /tmp/glg-debug-*/system.txt
free -h >> /tmp/glg-debug-*/system.txt

# Archiv erstellen
cd /tmp
tar czf glg-debug-*.tar.gz glg-debug-*
ls -lh glg-debug-*.tar.gz
```

---

**Weitere Hilfe:** Siehe DEPLOYMENT_GUIDE.md, STATUS.md, CLAUDE_CONTEXT.md

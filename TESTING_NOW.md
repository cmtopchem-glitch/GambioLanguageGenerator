# 🧪 Modul aktiv - Jetzt testen!

**Datum:** 2025-11-18
**Status:** ✅ Alle Vorbereitungen abgeschlossen

---

## ✅ Abgeschlossene Setup-Schritte

- ✅ Worker-Script ausführbar gemacht (`parallel_worker.sh`)
- ✅ Job-Tabelle erstellt (`rz_glg_jobs`)
- ✅ PHP-Syntax gecheckt (alle Files OK)
- ✅ Datenbank-Struktur verifiziert

---

## 🚀 So testest du das Modul

### Option A: Im Browser (Admin-Panel)

**1. Admin öffnen:**
```
https://test.redozone.de/admin/admin.php?do=GambioLanguageGeneratorModuleCenterModule
```

**2. Settings auf der rechten Seite:**
- OpenAI API Key eintragen (falls nicht vorhanden)
- System Prompt konfigurieren

**3. Tab "Sprachen generieren" öffnen:**
- **Quellsprache:** german
- **Zielsprache:** dutch (oder andere)
- **Include Core Files:** ✓ ja
- **Include GXModules:** leer lassen (zu schnellerem Test)

**4. "Speichern und Übersetzen" klicken**

**5. Progress beobachten:**
- GUI sollte Updates zeigen
- Jobs sollten in der Datenbank auftauchen

---

### Option B: Command-Line Test (schneller)

**1. Job manuell erstellen:**
```bash
mysql testredozone << 'EOF'
INSERT INTO rz_glg_jobs (
  job_id, action, source_language, target_language,
  source_file, status, params
) VALUES (
  'test_' . UNIX_TIMESTAMP(),
  'translate_file',
  'german',
  'dutch',
  'german/admin.lang.inc.php',
  'pending',
  '{"test": true}'
);
EOF
```

**2. Worker manuell starten:**
```bash
cd /srv/www/test.redozone
php GXModules/REDOzone/GambioLanguageGenerator/cli/worker.php 1
```

**3. Job-Status prüfen:**
```bash
mysql testredozone -e "SELECT job_id, status, progress_percent FROM rz_glg_jobs ORDER BY id DESC LIMIT 5;"
```

---

## 📊 Was sollte passieren?

### Success-Kriterien ✅

**Im Browser:**
- [ ] Admin-Interface lädt ohne Fehler
- [ ] Tabs sind alle sichtbar (Generieren, Vergleich, Sprachen verwalten)
- [ ] Buttons funktionieren
- [ ] Progress wird angezeigt

**In der Datenbank:**
- [ ] Jobs werden erstellt (status = 'pending')
- [ ] Jobs werden zu 'processing' (status = 'processing')
- [ ] Jobs werden zu 'success' (status = 'success')
- [ ] progress_percent steigt von 0 → 100

**In den Logs:**
```bash
tail -f /var/log/php8.2-fpm/error.log | grep "GLG"
```

Sollte zeigen:
```
[GLG] Job created: glg_abc123_0
[GLG] Job count: 1, Starting 1 parallel workers
[GLG] Processing Job: glg_abc123_0
[GLG] Job completed: glg_abc123_0
```

---

## 🔧 Debugging bei Problemen

### Problem 1: "Module nicht sichtbar im Admin"

**Lösung:**
```bash
# Cache clearen
cd /srv/www/test.redozone
php clearcache.php

# PHP-FPM neustarten
sudo systemctl restart php8.2-fpm
```

### Problem 2: "Worker startet nicht"

**Check:**
```bash
# Log ansehen
tail -100 /var/log/php8.2-fpm/error.log | grep GLG

# Worker manuell testen
cd /srv/www/test.redozone
php GXModules/REDOzone/GambioLanguageGenerator/cli/worker.php

# Sollte ausgeben: "No pending jobs found" oder Jobs verarbeiten
```

### Problem 3: "Jobs bleiben auf 'processing'"

**Check:**
```bash
# Job-Locks prüfen
mysql testredozone -e "SELECT job_id, status, locked_until FROM rz_glg_jobs WHERE status='processing';"

# Unlock (falls nötig):
mysql testredozone -e "UPDATE rz_glg_jobs SET status='pending', locked_until=NULL WHERE status='processing' AND locked_until < NOW();"
```

### Problem 4: "PHP-Fehler im Admin"

**Check Browser-Console:**
```javascript
// Öffne Developer Tools (F12)
// Gehe zu Console
// Prüfe auf JavaScript-Fehler
```

**Check Server-Logs:**
```bash
tail -50 /var/log/php8.2-fpm/error.log
```

---

## 📈 Performance-Test

**Zum Überprüfen ob Parallele Worker funktionieren:**

**Mit 3 Dateien (German → Dutch):**
```bash
time php GXModules/REDOzone/GambioLanguageGenerator/cli/worker.php 10
```

- **Ohne OpenAI API:** sollte < 1 Sekunde sein
- **Mit OpenAI API:** sollte ~15-30 Sekunden sein pro Datei

---

## 🔗 Wichtige Dateien

| Datei | Zweck |
|-------|-------|
| `/admin/glg_admin.php` | Admin-Interface |
| `/admin/glg_controller.php` | Request-Handler |
| `/includes/GLGCore.php` | Haupt-Logic + Job-Management |
| `/cli/worker.php` | Background Worker |
| `/cli/parallel_worker.sh` | Parallele Worker-Orchestrierung |
| `rz_glg_jobs` (DB) | Job-Queue Tabelle |

---

## 📞 Support

**Falls etwas nicht funktioniert:**

1. **Logs sammeln:**
   ```bash
   tail -200 /var/log/php8.2-fpm/error.log > glg-errors.log
   mysql testredozone -e "SELECT * FROM rz_glg_jobs ORDER BY id DESC LIMIT 20;" > glg-jobs.log
   ```

2. **Git-Status checken:**
   ```bash
   cd /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator
   git log -3 --oneline
   git status
   ```

3. **Bekommene Logs zur Analyse verwenden**

---

## ✅ Checkliste vor Start

- [x] Worker-Script ausführbar
- [x] Job-Tabelle existiert
- [x] PHP-Syntax OK
- [x] Alle Dependencies vorhanden
- [ ] OpenAI API Key konfiguriert (musst du machen)
- [ ] Cache geleert (optional, aber empfohlen)

---

**Nächster Schritt:** Browser öffnen und testen! 🚀


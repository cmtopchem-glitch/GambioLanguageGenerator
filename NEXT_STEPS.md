# 📋 Nächste Schritte - To-Do List

**Status:** Async-Architektur funktioniert, aber noch nicht 100% produktionsreif

---

## 🔴 Critical (Muss vor Production)

### 1. Job-Tabelle in Installation einbauen
**Problem:** Tabelle wird manuell erstellt, sollte aber automatisch bei Installation entstehen
**Location:** Finde wo andere Module ihre Tabellen erstellen und folge dem Muster
**Effort:** ~20 Minuten
**Priority:** 🔴 HOCH

**Checklist:**
- [ ] Installation-Routine findet
- [ ] `CREATE TABLE rz_glg_jobs` in Installations-Flow einbauen
- [ ] Testen dass Tabelle nach Installation vorhanden ist

---

### 2. Worker-Auto-Start sicherer machen
**Problem:** Könnte zu viele Worker starten wenn viele Requests gleichzeitig kommen
**Current:** Jeder startGeneration() Request startet einen Worker
**Solution:** Vor Worker-Start checken ob bereits einer läuft

**Effort:** ~30 Minuten

**Code-Location:** `includes/GLGCore.php::startBackgroundWorker()`

**Checklist:**
- [ ] `isWorkerRunning()` Methode schreiben
  - Prüfe: Laufen noch alte Worker-Prozesse?
  - Prüfe: Gibt es noch `processing` Jobs in der DB?
- [ ] Nur starten wenn kein Worker aktiv
- [ ] Testen mit mehreren gleichzeitigen Requests

---

### 3. Better Error Messages
**Problem:** Bei API-Fehlern ist nicht klar was schiefging
**Current:** Generic error im `error_message` Feld
**Solution:** Strukturierte Error-Codes

**Effort:** ~40 Minuten

**Checklist:**
- [ ] Error-Codes definieren (z.B. `ERR_API_TIMEOUT`, `ERR_RATE_LIMIT`, etc.)
- [ ] In GLGTranslator::translateWithOpenAI() anpassen
- [ ] In worker.php::processTranslationJob() strukturiert speichern
- [ ] Admin-Panel zeigt sprechende Fehlermeldungen

---

## 🟡 High Priority (Sollte bald implementiert sein)

### 4. Retry-Logic für fehlgeschlagene Jobs
**Problem:** Wenn OpenAI API down ist, Job bleibt stuck in error
**Current:** `retry_count` Spalte existiert aber wird nicht genutzt
**Solution:** Auto-Retry mit exponentiellem Backoff

**Effort:** ~60 Minuten

**Checklist:**
- [ ] In `failJob()` Logik: War es ein Retry-würdiger Fehler?
- [ ] `retry_count` erhöhen statt Status auf error zu setzen
- [ ] Nach 3 Retries: Status auf `error` setzen
- [ ] Exponentieller Backoff: 1 Min, 5 Min, 30 Min
- [ ] Testen mit simuliertem API-Fehler

---

### 5. Job-Cleanup für alte Einträge
**Problem:** DB wächst unbegrenzt
**Current:** Jobs werden nie gelöscht
**Solution:** Auto-Cleanup von alten/abgeschlossenen Jobs

**Effort:** ~30 Minuten

**Checklist:**
- [ ] `cleanupOldJobs()` Methode in GLGCore
  - Lösche: Jobs älter als 7 Tage mit Status `success` oder `error`
  - Behalte: Letzte 100 Jobs zur Debugging
- [ ] Aufgerufen am Ende von startGeneration() oder per Cron
- [ ] Testen

---

## 🟢 Nice-to-Have (Später)

### 6. Job-Monitoring Dashboard
**Aufwand:** ~2-3 Stunden
- Admin-Panel zeigt Live-Job-Status
- Real-time Progress mit WebSocket/Server-Sent Events
- Job-Logs abrufbar

### 7. Parallele Worker
**Aufwand:** ~1 Stunde
- Mehrere Worker-Instanzen gleichzeitig
- Z.B. 3 Worker statt 1
- Einfach: `startBackgroundWorker()` 3x aufrufen

### 8. Cron-Job als Fallback
**Aufwand:** ~30 Minuten
- Falls Worker nicht startet, Cron-Job alle 5 Min aufräumen
- Script: `/etc/cron.d/glg-worker`

---

## 🧪 Testing Checklist

Bevor Critical-Items als "done" gelten:

- [ ] **Einzelner Job:** German → Dutch für 1 Datei
- [ ] **Mehrere Jobs:** German → Dutch für 15 Dateien (alle durchzählen)
- [ ] **Fehlerbehandlung:** OpenAI API ausschalten, Job sollte fehlschlagen
- [ ] **Worker-Crash:** Worker während Verarbeitung killen, Job sollte weitergehen
- [ ] **Mehrere Requests:** 3x startGeneration() gleichzeitig, nicht zu viele Worker
- [ ] **DB-Größe:** Nach 100 Übersetzungen sollten alte Jobs aufgeräumt sein

---

## 📊 Geschätzter Aufwand

| Task | Effort | Priority |
|------|--------|----------|
| Installation-Integration | 20 min | 🔴 Critical |
| Worker-Safety | 30 min | 🔴 Critical |
| Error-Handling | 40 min | 🔴 Critical |
| Retry-Logic | 60 min | 🟡 High |
| Job-Cleanup | 30 min | 🟡 High |
| **Gesamt Critical** | **90 min** | |
| **Gesamt High** | **90 min** | |

---

## 🚀 Quick-Start nach Priority

**Woche 1:** Critical Items (90 min)
1. Installation-Integration
2. Worker-Safety
3. Error-Handling

**Woche 2:** High-Priority Items (90 min)
1. Retry-Logic
2. Job-Cleanup

**Später:** Nice-to-Have

---

## 💡 Tipps für Implementierung

### Installation-Integration
Suche nach anderen Modules die Tabellen erstellen:
```bash
grep -r "CREATE TABLE" /srv/www/test.redozone/GXModules/ --include="*.php"
```

### Worker-Safety
Nutze DB-Query um laufende Worker zu checken:
```php
$query = "SELECT COUNT(*) FROM rz_glg_jobs WHERE status = 'processing' AND locked_until > NOW()";
```

### Error-Handling
Nutze Exceptions und catch spezifische Fehlertypen:
```php
try {
    $translator->translateWithOpenAI(...);
} catch (RateLimitException $e) {
    // Retry
} catch (APIException $e) {
    // Fail
}
```

---

**Dokumentation erstellt:** 2025-11-15
**Von:** Claude Code Session
**Token-Limit:** Approaching weekly limit - daher Dokumentation jetzt!

# 🎯 Session Templates - Token-effiziente AI-Nutzung

**Zweck:** Maximiere Requests, minimiere Token-Verbrauch, vermeide Rate Limits
**Für:** Claude Pro Account + OpenAI Fallback
**Projekt:** Gambio Language Generator

---

## 📋 Quick Reference

| Task-Typ | Model | Session-Länge | Geschätzter Verbrauch |
|----------|-------|---------------|----------------------|
| **Debugging** | Sonnet 4.5 | 5-10 Messages | ~50k Tokens |
| **Implementierung** | Haiku | 10-15 Messages | ~20k Tokens |
| **Testing** | Haiku | 5-10 Messages | ~10k Tokens |
| **Dokumentation** | Haiku | 10-15 Messages | ~15k Tokens |
| **Code Review** | Haiku | 5-10 Messages | ~12k Tokens |

---

## 🔴 Template 1: Debugging Session (Sonnet 4.5)

**Wann nutzen:**
- Komplexe Bugs (Worker-Hangs, unerklärliche Errors)
- Root-Cause-Analyse nötig
- Performance-Probleme
- Architektur-Entscheidungen

**Model:** Sonnet 4.5 (temporär wechseln!)

**Maximale Länge:** 5-10 Messages

### Prompt-Template:

```markdown
## Debugging Session

**Problem:** [1-2 Sätze]

**Relevante Dateien:**
- [Dateiname + Zeilennummer wenn möglich]

**Logs (letzte 20 Zeilen):**
```
[Nur relevante Logs einfügen, nicht alles!]
```

**Was ich schon versucht habe:**
- [Bullet point]

**Frage:** [Konkrete Frage, z.B. "Was ist die Root Cause?"]
```

### Beispiel (aus unserem Projekt):

```markdown
## Debugging Session

**Problem:** Worker hängt bei Batch 22/26 ohne Timeout-Exception

**Relevante Dateien:**
- includes/GLGTranslator.php:87-115 (translateWithOpenAI)

**Logs (letzte 20 Zeilen):**
```
[08:09:38] GLGTranslator: Sending request to OpenAI API...
[08:09:40] GLG: actionDefault() called, action parameter: getProgress
```

**Was ich schon versucht habe:**
- CURLOPT_TIMEOUT auf 120s gesetzt
- Rate Limiting mit sleep(1)

**Frage:** Warum greift der Timeout nicht und wie fixe ich das?
```

**Nach der Antwort:**
```markdown
Danke! Implementiere ich in neuer Session mit Haiku.
```

**→ Session BEENDEN! ✅**

---

## 🟢 Template 2: Implementierung (Haiku)

**Wann nutzen:**
- Bug-Fix implementieren (Lösung ist bekannt)
- Feature hinzufügen
- Code refactoren
- Logging/Error-Handling erweitern

**Model:** Haiku

**Maximale Länge:** 10-15 Messages

### Prompt-Template:

```markdown
## Implementierung: [Task-Name]

**Ziel:** [1 Satz]

**Änderungen:**
1. [Konkret, z.B. "Füge CURLOPT_NOSIGNAL in GLGTranslator.php:98 hinzu"]
2. [...]

**Betroffene Dateien:**
- [Dateiname]

**Kontext (falls nötig):**
[Nur wenn essentiell - max 5 Zeilen!]
```

### Beispiel:

```markdown
## Implementierung: CURLOPT_NOSIGNAL hinzufügen

**Ziel:** Timeouts in PHP-FPM zum Funktionieren bringen

**Änderungen:**
1. In GLGTranslator.php nach Zeile 97 hinzufügen: curl_setopt($ch, CURLOPT_NOSIGNAL, true);
2. Timeout reduzieren von 120s auf 60s
3. Kommentar hinzufügen warum wichtig

**Betroffene Dateien:**
- includes/GLGTranslator.php

Los geht's!
```

**Folge-Prompts (kurz & präzise):**

```markdown
# Statt:
"Könntest du bitte auch noch prüfen ob das richtig ist?"

# Besser:
"Sieht gut aus. Commit-Message generieren."
```

**Nach 10-15 Messages:**
```markdown
Session beenden, bei Bedarf neue öffnen.
```

**→ Session BEENDEN! ✅**

---

## 🔵 Template 3: Testing Session (Haiku)

**Wann nutzen:**
- Deployment testen
- Test-Ergebnisse analysieren (wenn nicht komplex)
- Error-Logs interpretieren (einfache)

**Model:** Haiku

**Maximale Länge:** 5-10 Messages

### Prompt-Template:

```markdown
## Test: [Was getestet wird]

**Setup:**
- Quellsprache: [z.B. german]
- Zielsprache: [z.B. polish]
- Module: [z.B. HoneyGrid]

**Deployment:**
- Code gepullt: [Commit-Hash]
- Cache gelöscht: [Ja/Nein]
- PHP-FPM restarted: [Ja/Nein]

**Logs (relevanter Teil):**
```
[Nur kritische Logs, max 30 Zeilen]
```

**Frage:** [z.B. "Hat es funktioniert? Welche Probleme gab es?"]
```

### Beispiel:

```markdown
## Test: CURLOPT_NOSIGNAL Fix

**Setup:**
- Quellsprache: german
- Zielsprache: polish
- Module: HoneyGrid

**Deployment:**
- Code gepullt: 859c51c
- Cache gelöscht: Ja
- PHP-FPM restarted: Ja

**Logs (relevanter Teil):**
```
[08:45:23] GLGTranslator: Sending request to OpenAI API...
[08:45:34] GLGTranslator: Received response from OpenAI (HTTP 200, 11.2s)
[08:45:36] GLG: Rate limiting pause (2s)
[08:45:38] GLGTranslator: Sending request to OpenAI API...
[08:45:49] GLGTranslator: Received response from OpenAI (HTTP 200, 10.8s)
... alle 26 Batches erfolgreich ...
[08:51:12] GLG: Language generation completed successfully!
```

**Frage:** Hat der Fix funktioniert? Zusammenfassung in 5 Bullet Points.
```

**Erwartete Antwort-Länge begrenzen:**
```markdown
"Zusammenfassung in max 5 Bullet Points, je 1 Zeile."
```

**→ Nach Antwort: Session BEENDEN! ✅**

---

## 🟡 Template 4: Dokumentation (Haiku)

**Wann nutzen:**
- README aktualisieren
- Changelog schreiben
- TROUBLESHOOTING erweitern
- Kommentare hinzufügen

**Model:** Haiku

**Maximale Länge:** 10-15 Messages

### Prompt-Template:

```markdown
## Dokumentation: [Was dokumentieren]

**Basis-Info:**
[Stichpunkte mit Fakten, keine langen Texte]

**Ziel:**
[z.B. "Aktualisiere TROUBLESHOOTING.md mit neuem Problem"]

**Format:**
[z.B. "Markdown, max 200 Zeilen"]

**Kontext (falls nötig):**
[Nur essentielles!]
```

### Beispiel:

```markdown
## Dokumentation: TROUBLESHOOTING.md erweitern

**Basis-Info:**
- Problem: Worker hängt bei Batch 22
- Root Cause: CURLOPT_NOSIGNAL fehlte
- Fix: Commit 859c51c
- Symptom: "Sending request..." ohne "Received response..."

**Ziel:**
Füge neuen Abschnitt "Problem 11: Worker hängt trotz Timeout-Setting" hinzu

**Format:**
Markdown, gleicher Stil wie bestehende Probleme in TROUBLESHOOTING.md

Los geht's!
```

**Nach 10-15 Messages:**
```markdown
Danke! Session beenden.
```

**→ Session BEENDEN! ✅**

---

## 🟣 Template 5: Code Review (Haiku)

**Wann nutzen:**
- Code-Qualität prüfen
- Sicherheitslücken finden
- Performance-Review
- Best Practices check

**Model:** Haiku

**Maximale Länge:** 5-10 Messages

### Prompt-Template:

```markdown
## Code Review: [Dateiname oder Feature]

**Code:**
```php
[Nur der relevante Code-Abschnitt, nicht ganze Datei!]
```

**Review-Fokus:**
- [ ] Sicherheit (SQL-Injection, XSS, etc.)
- [ ] Performance
- [ ] Error-Handling
- [ ] Code-Qualität

**Frage:** Findings in max 10 Bullet Points, priorisiert nach Schwere.
```

### Beispiel:

```markdown
## Code Review: GLGTranslator.php translateWithOpenAI()

**Code:**
```php
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_NOSIGNAL, true);

$response = curl_exec($ch);

if ($response === false) {
    $curlError = curl_error($ch);
    curl_close($ch);
    throw new Exception("OpenAI API Connection Error: {$curlError}");
}
```

**Review-Fokus:**
- [x] Sicherheit
- [x] Error-Handling
- [ ] Performance (irrelevant hier)

**Frage:** Findings in max 5 Bullet Points.
```

**→ Nach Antwort: Session BEENDEN! ✅**

---

## 🔄 Fallback-Strategie (wenn Claude blocked)

### OpenAI GPT-4o-mini (günstig & schnell)

**Wann nutzen:**
- Claude Rate Limit erreicht
- Einfache Tasks (Implementierung, Docs, Testing)

**Setup:**
```bash
Claude Code → Settings → Provider → OpenAI
API Key: [von https://platform.openai.com/api-keys]
```

**Nutze gleiche Templates:**
- Gleiche Prompts funktionieren
- Eventuell etwas weniger Qualität
- Aber: Keine Wartezeit!

**Kosten:**
- GPT-4o-mini: ~$0.15/1M Input, $0.60/1M Output
- Sehr günstig für Fallback

---

## 📊 Session-Planung (Beispiel-Tag)

### Morgen (09:00 - 12:00) - Frisches Limit

**Session 1 - Debugging (Sonnet):**
```
Problem: Performance-Issue bei großen Dateien
Template: Debugging
Dauer: 10 Messages
Token: ~50k
```

**Session 2 - Implementierung (Haiku):**
```
Task: Fix implementieren aus Session 1
Template: Implementierung
Dauer: 15 Messages
Token: ~20k
```

**Session 3 - Testing (Haiku):**
```
Task: Fix testen
Template: Testing
Dauer: 5 Messages
Token: ~10k
```

**Gesamt Morgen:** ~80k Tokens → Weit unter Limit! ✅

---

### Mittag (12:00 - 15:00)

**Session 4 - Code Review (Haiku):**
```
Task: Neuen Code reviewen
Template: Code Review
Dauer: 8 Messages
Token: ~12k
```

**Session 5 - Dokumentation (Haiku):**
```
Task: CHANGELOG aktualisieren
Template: Dokumentation
Dauer: 10 Messages
Token: ~15k
```

**Gesamt Mittag:** ~27k Tokens

---

### Falls Limit erreicht → OpenAI Fallback

**Session 6 - Implementierung (GPT-4o-mini):**
```
Task: Weitere Features implementieren
Template: Implementierung (gleich!)
Dauer: 15 Messages
Kosten: ~$0.05
```

---

## ✅ Best Practices Checkliste

Vor jeder Session:

- [ ] **Richtiges Model gewählt?**
  - Komplex → Sonnet 4.5
  - Einfach → Haiku

- [ ] **Template bereit?**
  - Kopiere relevantes Template
  - Fülle Platzhalter aus

- [ ] **Prompt optimiert?**
  - Kurz & präzise
  - Nur relevante Infos
  - Konkrete Frage

- [ ] **Output begrenzt?**
  - "Max 5 Bullet Points"
  - "Max 200 Zeilen"
  - "Nur kritische Findings"

Nach 10-15 Messages:

- [ ] **Session beenden!**
- [ ] **Neue Session starten wenn weiter nötig**
- [ ] **Context nicht weiterschleppen!**

---

## 💡 Token-Spar-Tricks

### 1. Code lokal vorbereiten
```bash
# Statt AI zu bitten Code zu suchen:
grep -A 20 "function translateBatch" includes/GLGTranslator.php > snippet.txt

# Dann:
"Hier ist translateBatch() [snippet], reviewe es"
```

### 2. Grep statt Full-File-Read
```bash
# Statt:
"Lies GLGTranslator.php und finde alle CURLOPT Settings"

# Besser:
grep "CURLOPT" includes/GLGTranslator.php > curlopts.txt
"Hier sind alle CURLOPT Settings [paste], welche fehlt?"
```

### 3. Diff statt komplette Dateien
```bash
# Statt:
"Vergleiche alte und neue Version von GLGTranslator.php"

# Besser:
git diff HEAD~1 includes/GLGTranslator.php > changes.diff
"Hier ist der Diff [paste], reviewe Änderungen"
```

### 4. Summaries zwischen Sessions
```bash
# Ende Session 1:
"Fasse Findings zusammen in 5 Bullet Points"

# Start Session 2:
"Basierend auf Findings [paste 5 Bullets], implementiere Fix"
# Statt ganzen Conversation-History!
```

---

## 📈 Erwartete Verbesserung

**Ohne Templates (bisheriges Verhalten):**
- 1 lange Session = ~350k Tokens
- Limit nach 2-3 Sessions
- Mehrere Stunden Wartezeit

**Mit Templates:**
- 10 kurze Sessions = ~150k Tokens
- Limit nach 15-20 Sessions
- **10x mehr Produktivität!** 🚀

**Mit OpenAI Fallback:**
- Praktisch unlimitiert
- ~$5-10/Tag extra Kosten
- Keine Wartezeiten

---

## 🎯 Quick-Start Anleitung

### Erste Nutzung:

1. **Öffne diese Datei** (SESSION_TEMPLATES.md)

2. **Identifiziere deinen Task-Typ:**
   - Debugging → Template 1 (Sonnet)
   - Implementierung → Template 2 (Haiku)
   - Testing → Template 3 (Haiku)
   - Dokumentation → Template 4 (Haiku)
   - Code Review → Template 5 (Haiku)

3. **Kopiere relevantes Template**

4. **Fülle Platzhalter aus**
   - [Problem], [Task-Name], etc.
   - Nur relevante Infos!

5. **Paste in Claude**

6. **Nach Antwort: Session beenden!**

7. **Neue Session für nächsten Task**

---

## 📞 Support

**Fragen zu Templates?**
- Siehe Beispiele in jedem Template
- Bei Unsicherheit: Lieber neue Session als lange weitermachen

**Template funktioniert nicht gut?**
- Prüfe ob richtiges Model gewählt
- Prüfe ob Prompt zu lang (kürzen!)
- Prüfe ob zu viel Context (neue Session!)

---

**Version:** 1.0
**Erstellt:** 2025-11-15
**Für:** Gambio Language Generator Development
**Projekt:** https://github.com/cmtopchem-glitch/GambioLanguageGenerator

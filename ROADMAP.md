# Gambio Language Generator - Roadmap

**Projekt:** AI-gestützte Übersetzung von Gambio-Sprachdateien
**Status:** In Entwicklung - Kernfunktion hängt (nicht produktiv)
**Letzte Aktualisierung:** 2025-11-12

---

## ⚠️ AKTUELLER STATUS (2025-11-12)

**Kritische Blocker:**
1. ❌ Übersetzung startet nicht (hängt beim Bootstrap oder copyDirectoryRecursive)
2. ❌ Progress-Anzeige funktioniert nicht (Session-Lock Problem)
3. ❌ PHP-FPM Worker hängen bei Tests

**Siehe CLAUDE_CONTEXT.md für Details!**

---

## ✅ COMPLETED (2025-11-11 & 2025-11-12)

### 1. ModuleCenter Integration ✅
- [x] Controller nach AIProductOptimizer-Muster umgebaut
- [x] Smarty-Template mit Bootstrap-Tabs
- [x] Tab-Switching funktioniert (eigene JavaScript-Funktion)
- [x] Modul läuft im ModuleCenter-Frame

### 2. Live Progress Tracking ✅
- [x] Session-basierter Fortschritt
- [x] AJAX Polling alle 500ms
- [x] Anzeige: Aktuelle Datei, Sprache, Fortschritt in %
- [x] Progress-Bar mit Animation
- [x] Stop-Button zum Abbrechen

### 3. Automatische Verzeichnis-Erstellung ✅
- [x] Hauptsprachverzeichnis wird automatisch erstellt
- [x] Alle Unterverzeichnisse werden automatisch angelegt
- [x] Standard-Dateien von german kopiert (index.html, .htaccess, etc.)
- [x] Berechtigungen korrekt: 0775 für www-data Schreibzugriff

### 4. Korrekte Pfad-Generierung ✅
- [x] Dateien werden in `/srv/www/test.redozone/lang/danish/...` erstellt
- [x] Nicht mehr in `/srv/www/test.redozone/danish/...`
- [x] `lang/` Präfix wird automatisch hinzugefügt

### 5. Erweiterte Sprachunterstützung ✅
- [x] 23+ Sprachen in Mapping-Funktion
- [x] Korrekte Sprachnamen für OpenAI API
- [x] Deutsch → Dansk, English, Español, etc.

### 6. Logging & Debugging ✅
- [x] Detailliertes Logging in allen Komponenten
- [x] GLGTranslator zeigt Quell-/Zielsprache
- [x] GLGFileWriter zeigt erstellte Pfade
- [x] Error-Handling mit aussagekräftigen Meldungen

---

## 🚧 TODO - Wichtige Verbesserungen

### Priorität 1 - Kritisch

#### 1.1 Performance & Batch-Verarbeitung
- [ ] **Batch-Größe optimieren** - Aktuell 702 Dateien einzeln, sehr langsam
  - [ ] Mehrere Dateien in einem API-Call zusammenfassen
  - [ ] Intelligentes Batching nach Token-Größe
  - [ ] Progress pro Batch statt pro Datei

- [ ] **API-Rate-Limiting**
  - [ ] Pause zwischen API-Calls
  - [ ] Retry-Logik bei Rate-Limit-Errors
  - [ ] Exponential Backoff

#### 1.2 Fehlerbehandlung
- [ ] **Robustere Error Recovery**
  - [ ] Bei API-Fehler: Einzelne Datei überspringen, nicht ganzen Prozess abbrechen
  - [ ] Fehler-Log in Datenbank schreiben
  - [ ] "Retry failed files" Funktion

- [ ] **Validation nach Übersetzung**
  - [ ] Prüfe ob alle Keys übersetzt wurden
  - [ ] Prüfe ob Platzhalter erhalten bleiben (%s, {name})
  - [ ] Prüfe ob HTML-Tags korrekt bleiben

### Priorität 2 - Wichtig

#### 2.1 User Experience
- [ ] **Übersetzungs-Historie**
  - [ ] Zeige letzte Übersetzungen
  - [ ] Wann wurde welche Sprache generiert
  - [ ] Wie viele Dateien/Einträge

- [ ] **Vorschau vor Übersetzung**
  - [ ] Zeige wie viele Dateien übersetzt werden
  - [ ] Geschätzte Dauer
  - [ ] Geschätzte API-Kosten

- [ ] **Fortschritt speichern**
  - [ ] Bei Abbruch: Zeige was bereits übersetzt wurde
  - [ ] "Resume"-Funktion um weiterzumachen

#### 2.2 Qualitätssicherung
- [ ] **Übersetzungs-Review**
  - [ ] Nach Übersetzung: Stichproben anzeigen
  - [ ] User kann einzelne Übersetzungen korrigieren
  - [ ] Manuelle Nachbearbeitung speichern

- [ ] **Glossar-Funktion**
  - [ ] Fachbegriffe festlegen (z.B. "Warenkorb" = "Shopping Cart")
  - [ ] Glossar in API-Prompt einbauen
  - [ ] Konsistenz über alle Übersetzungen

### Priorität 3 - Nice to Have

#### 3.1 Erweiterte Funktionen
- [ ] **Partial Updates**
  - [ ] Nur fehlende Einträge übersetzen
  - [ ] Nur geänderte Einträge übersetzen
  - [ ] Bestehende Übersetzungen nicht überschreiben

- [ ] **Multi-Provider Support**
  - [ ] DeepL vollständig implementieren
  - [ ] Google Translate als Option
  - [ ] Provider-Vergleich (Qualität/Kosten)

- [ ] **Backup & Restore**
  - [ ] Automatische Backups vor Übersetzung
  - [ ] Restore-Funktion im UI
  - [ ] Backup-Verwaltung (löschen, exportieren)

#### 3.2 Analytics & Reporting
- [ ] **Übersetzungs-Statistiken**
  - [ ] Dashboard mit Übersicht
  - [ ] Vollständigkeit pro Sprache
  - [ ] API-Kosten-Tracking
  - [ ] Qualitäts-Metriken

- [ ] **Export-Funktionen**
  - [ ] Fehlende Übersetzungen als CSV
  - [ ] Übersetzungs-Report als PDF
  - [ ] Git-Diff für Änderungen

---

## 🐛 BEKANNTE BUGS

### Kritisch
- [x] ~~Berechtigungsfehler bei Verzeichnis-Erstellung~~ → FIXED (0775)
- [x] ~~Falscher Pfad ohne /lang/ Präfix~~ → FIXED
- [x] ~~Tab-Switching funktioniert nicht~~ → FIXED
- [x] ~~foreach Loop mit falscher Array-Struktur~~ → FIXED

### Mittelschwer
- [ ] **Timeout bei großen Übersetzungen**
  - Wenn 700+ Dateien übersetzt werden, kann PHP-Timeout auftreten
  - Lösung: Background-Job oder kleinere Batches

- [ ] **Session-Verlust bei langen Übersetzungen**
  - Nach ~30min kann Session ablaufen
  - Progress geht verloren
  - Lösung: Session-Lifetime erhöhen oder in DB speichern

### Minor
- [ ] **Progress zeigt manchmal 0/0 Dateien**
  - Race Condition beim Session-Update
  - Kosmetisches Problem

---

## 📋 TECHNISCHE SCHULDEN

### Code-Qualität
- [ ] **Unit Tests schreiben**
  - GLGReader Tests
  - GLGTranslator Tests
  - GLGFileWriter Tests
  - GLGCompare Tests

- [ ] **Code-Dokumentation**
  - PHPDoc für alle Methoden vervollständigen
  - Inline-Kommentare verbessern
  - Architecture Decision Records (ADR)

- [ ] **Refactoring**
  - Controller ist zu groß (500+ Zeilen)
  - Service-Layer einführen
  - Dependency Injection verwenden

### Performance
- [ ] **Caching**
  - Übersetzungs-Cache für häufige Begriffe
  - API-Response cachen
  - Reduce DB-Queries

- [ ] **Database Optimization**
  - Indizes auf language_text Tabelle prüfen
  - Query-Performance optimieren
  - Avoid N+1 Queries

### Security
- [ ] **Input Validation**
  - API-Key Validierung verbessern
  - SQL-Injection Prävention prüfen
  - XSS-Prävention in Templates

- [ ] **API-Key Security**
  - Verschlüsselung in Datenbank
  - Nie im Log ausgeben
  - Rotation-Mechanismus

---

## 🎯 NÄCHSTE SCHRITTE (Empfohlen)

### Sofort (Diese Session)
1. ✅ ~~danish-Verzeichnis Berechtigungen fixen~~ → DONE
2. ✅ ~~Vollständige Übersetzung testen~~ → IN PROGRESS
3. [ ] Ergebnisse validieren
4. [ ] README.md aktualisieren

### Kurz-Term (Nächste Session)
1. [ ] Performance optimieren - Batching implementieren
2. [ ] Error-Handling robuster machen
3. [ ] Übersetzungs-Historie implementieren
4. [ ] Unit Tests für Core-Funktionalität

### Mittel-Term (Nächste Woche)
1. [ ] Glossar-Funktion
2. [ ] Partial Updates
3. [ ] DeepL vollständig implementieren
4. [ ] Dashboard mit Statistiken

### Lang-Term (Nächster Monat)
1. [ ] Background-Jobs für lange Übersetzungen
2. [ ] Multi-Sprach Batch (Mehrere Sprachen gleichzeitig)
3. [ ] Qualitäts-Review Interface
4. [ ] Analytics & Reporting

---

## 📝 NOTIZEN

### Lessons Learned
- **ModuleCenter Integration:** AdminPageHttpControllerResponse ist korrekt, nicht AdminLayoutHttpControllerResponse
- **Permissions:** Immer 0775 für Verzeichnisse wenn www-data schreiben muss
- **Tab-Switching:** Gambio überschreibt Bootstrap-JS, eigene Funktionen nötig
- **Session vs DB:** Session gut für kurze Prozesse, bei langen besser DB

### Best Practices
- Detailliertes Logging ist Gold wert
- KISS-Prinzip: Einfache Lösungen sind robuster
- Immer an Berechtigungen denken (www-data)
- Progress-Tracking verbessert UX enorm

### API-Kosten Schätzung
- ~702 Dateien × ~50 Einträge = ~35.000 Einträge
- Mit GPT-4o-mini: ~$0.01-0.05 pro Sprache
- Mit GPT-4o: ~$0.10-0.50 pro Sprache

---

## 🤝 CONTRIBUTION GUIDELINES

Bei Weiterentwicklung beachten:
1. **Immer committen mit aussagekräftiger Message**
2. **CLAUDE_CONTEXT.md aktualisieren** nach großen Änderungen
3. **Error-Logs prüfen** nach jeder Änderung
4. **Cache löschen** nach Template/Controller-Änderungen
5. **Diese Roadmap aktualisieren** wenn Tasks erledigt

---

## 📞 SUPPORT

Bei Problemen:
1. Error-Logs prüfen: `tail -100 /var/log/apache2/error.log | grep GLG`
2. Cache löschen: `cd /srv/www/test.redozone && php clearcache.php`
3. Berechtigungen prüfen: `ls -la /srv/www/test.redozone/lang/`
4. GitHub Issues: https://github.com/cmtopchem-glitch/GambioLanguageGenerator/issues

---

**Version:** 1.0.0-beta
**Maintainer:** Christian Mittenzwei
**AI Assistant:** Claude Code (Anthropic)
**License:** Proprietary

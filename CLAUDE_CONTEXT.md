# Claude Code - Aktueller Arbeitsstand

**Datum:** 2025-11-11 22:54 Uhr
**Letzter Commit:** 8dcca2b - FIX: Gemischte Quellsprachen richtig behandeln
**GitHub:** https://github.com/cmtopchem-glitch/GambioLanguageGenerator

---

## ✅ Aktueller Status - BETA FUNKTIONSFÄHIG

### Was funktioniert
- ✅ ModuleCenter Integration mit Smarty-Templates
- ✅ Live Progress Tracking (Session-basiert, AJAX Polling)
- ✅ Stop-Button zum Abbrechen von Übersetzungen
- ✅ Automatische Verzeichnis-Erstellung mit korrekten Berechtigungen
- ✅ Korrekte Pfad-Generierung (`/srv/www/test.redozone/lang/danish/...`)
- ✅ 23+ Sprachen unterstützt
- ✅ Bootstrap-Tabs funktionieren
- ✅ Detailliertes Logging

### Wichtige Dateien
- **Controller:** `Admin/Classes/Controllers/GambioLanguageGeneratorModuleCenterModuleController.inc.php` (420 Zeilen)
- **Template:** `Admin/Templates/module_content.html` (Smarty mit Tabs, Progress, Stop-Button)
- **Includes:**
  - `GLGReader.php` - Liest Sprachdaten aus DB
  - `GLGTranslator.php` - OpenAI/DeepL Integration
  - `GLGFileWriter.php` - Schreibt Dateien mit korrekten Permissions
  - `GLGCompare.php` - Sprachvergleich
  - `GLGCore.php` - Core-Funktionalität

### Kritische Fixes (Heute)
1. **ModuleCenter Integration** - AdminPageHttpControllerResponse + Smarty
2. **Tab-Switching** - Eigene glgSwitchTab() Funktion (kein Bootstrap-JS)
3. **Pfad-Generierung** - Automatisches `lang/` Präfix
4. **Berechtigungen** - 0775 statt 0755 für www-data Schreibzugriff
5. **foreach Loop** - Korrekte Array-Struktur für sourceFiles
6. **Live Progress** - Session-basiert mit AJAX Polling alle 500ms
7. **Rekursive Directory Creation** - Alle Parent-Verzeichnisse erhalten korrekte Permissions
8. **Gemischte Quellsprachen** - OpenAI erkennt automatisch Sprache jedes Textes

### Wichtige Befehle
Cache loeschen: cd /srv/www/test.redozone && php clearcache.php
Git Status: cd /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator && git status
Syntax pruefen: php -l /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator/Admin/Classes/Controllers/GambioLanguageGeneratorModuleCenterModuleController.inc.php

### Gambio-Kontext
- Version: Gambio 4.x (kompatibel mit 3.0-4.9)
- Framework: GXModules System
- Parent Class: AbstractModuleCenterModuleController
- Response Types: AdminPageHttpControllerResponse, AdminLayoutHttpControllerResponse

### Modul-Funktionalitaet
Das Modul soll Gambio-Sprachdateien automatisch uebersetzen:
- Quellsprache waehlen
- Zielsprachen auswaehlen
- KI-Uebersetzung via OpenAI API
- Sprachvergleich
- Einstellungen speichern

### Besonderheit: Gemischte Quellsprachen
Die Gambio-Datenbank kann für eine Sprache (z.B. deutsch, language_id=2) Einträge mit verschiedenen Source-Pfaden enthalten:
- `source = "german/buttons.php"` mit deutschem Text
- `source = "english/buttons.php"` mit englischem Text (!!)

**Lösung:** GLGTranslator.php erweitert OpenAI-Prompt um automatische Sprach-Erkennung:
- OpenAI erkennt tatsächliche Sprache jedes Textes
- Übersetzt ALLES zur Zielsprache
- Funktioniert mit beliebigen Sprachmischungen

---

## Fuer den neuen Client

Du kannst direkt weitermachen! Der Code ist committed und gepusht zu GitHub.

**Deine erste Aufgabe:** Das ModuleCenter-Integrationsproblem loesen - das Modul soll IM ModuleCenter-Frame erscheinen, nicht als eigenstaendige Seite.

**Tipp:** Schau dir an, wie ItRechtModuleCenterModuleController die Integration macht.

**Server:** test.redozone.de | **User:** cm | **Pfad:** /srv/www/test.redozone/GXModules/REDOzone/GambioLanguageGenerator/

Viel Erfolg!

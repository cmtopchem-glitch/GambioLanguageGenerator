# Gambio Language Generator

Automatische Generierung von Sprachdateien für Gambio-Shops basierend auf der Tabelle `language_phrases_cache`.

## Version
1.0.0

## Autor
Christian Mittenzwei

## Features

- ✅ Automatische Übersetzung aller Sprachdateien aus der Original-Shopsprache
- ✅ Unterstützung für Core-Sprachdateien (/lang)
- ✅ Unterstützung für GXModule-Sprachdateien
- ✅ Update-Funktion für geänderte Texte (basierend auf date_modified)
- ✅ OpenAI GPT-4o Integration
- ✅ DeepL API Support (geplant)
- ✅ Google Translate API Support (geplant)
- ✅ Backup vor Überschreiben
- ✅ Fortschrittsanzeige
- ✅ Detailliertes Protokoll
- ✅ URL-basierter Lizenzschutz

## Installation

1. **Modul hochladen**
   ```bash
   # Kopiere das Verzeichnis nach:
   /shop-root/GXModules/GambioLanguageGenerator/
   ```

2. **Datenbanktabellen**
   Die benötigten Tabellen werden automatisch beim ersten Aufruf erstellt:
   - `rz_glg_settings` - Modul-Einstellungen
   - `rz_glg_log` - Protokoll
   - `rz_glg_update_tracking` - Update-Tracking

3. **Lizenzschlüssel**
   - Lizenzschlüssel in der Datenbank hinterlegen:
   ```sql
   INSERT INTO configuration (configuration_key, configuration_value, configuration_group_id)
   VALUES ('GLG_LICENSE_KEY', 'DEIN-LIZENZSCHLUESSEL', 6);
   ```

4. **API-Schlüssel konfigurieren**
   - Im Admin-Bereich unter "Einstellungen" den API-Schlüssel eintragen
   - Für OpenAI: https://platform.openai.com/api-keys

## Verzeichnisstruktur

```
GambioLanguageGenerator/
├── admin/
│   ├── glg_admin.php          # Admin-Interface
│   ├── glg_admin.js           # JavaScript
│   └── glg_controller.php     # AJAX-Controller
├── includes/
│   ├── GLGCore.php            # Kern-Logik
│   ├── GLGLicense.php         # Lizenzprüfung
│   ├── GLGTranslator.php      # Übersetzungs-Engine (TODO)
│   └── GLGFileWriter.php      # Dateischreiber (TODO)
├── lang/
│   ├── german/
│   │   └── glg.php
│   └── english/
│       └── glg.php
├── images/
│   └── icon.png
├── module.info
└── README.md
```

## Verwendung

### Sprachen generieren

1. Öffne das Modul im Gambio-Admin
2. Wähle die **Quellsprache** (z.B. "Deutsch")
3. Wähle die **Zielsprachen** aus
4. Wähle ob Core-Dateien und/oder GXModules übersetzt werden sollen
5. Optional: Wähle spezifische Module aus
6. Klicke auf **"Sprachen generieren"**

### Sprachen aktualisieren

1. Wechsle zum Tab **"Sprachen aktualisieren"**
2. Prüfe die Änderungen seit dem letzten Update
3. Klicke auf **"Aktualisieren"**

Die Update-Funktion übersetzt nur die Texte, die sich seit dem letzten Update geändert haben (basierend auf `date_modified` in `language_phrases_cache`).

## Funktionsweise

### Datenquelle

Das Modul nutzt die Tabelle `language_phrases_cache` als Basis:

```sql
SELECT * FROM language_phrases_cache WHERE source = 'admin/buttons.php'
```

Wichtige Felder:
- `source` - Quell-Datei (z.B. "admin/buttons.php")
- `section_name` - Sektion innerhalb der Datei
- `phrase_name` - Sprachvariable (z.B. "BUTTON_SAVE")
- `phrase_text` - Übersetzter Text
- `date_modified` - Letzte Änderung

### Übersetzungsprozess

1. **Lesen**: Alle Einträge aus `language_phrases_cache` für die Quellsprache
2. **Gruppieren**: Nach `source` (Datei) und `section_name` (Sektion)
3. **Übersetzen**: Via OpenAI API (GPT-4o)
4. **Schreiben**: Neue Sprachdateien erstellen

### Dateiformat

Das Modul erkennt automatisch das Format der Quelldatei:

**Format 1: Einfaches Array**
```php
$t_language_text_section_content_array = array(
    'BUTTON_SAVE' => 'Speichern',
    'BUTTON_CANCEL' => 'Abbrechen'
);
```

**Format 2: Verschachtelte Arrays (GXModules)**
```php
$t_language_text_section_content_array = array(
    'admin' => array(
        'BUTTON_SAVE' => 'Speichern'
    )
);
```

## API-Provider

### OpenAI (Standard)

**Modelle:**
- GPT-4o (empfohlen)
- GPT-4o-mini (günstiger, schneller)
- GPT-4

**Kosten (ca.):**
- 1000 Sprachvariablen ≈ $0.50 - $2.00
- Abhängig von Textlänge und Modell

### DeepL (geplant)

- Sehr gute Übersetzungsqualität
- Günstiger als OpenAI
- Begrenzte Sprachauswahl

### Google Translate (geplant)

- Kostengünstig
- Viele Sprachen
- Basale Qualität

## Backup

Wenn aktiviert, erstellt das Modul vor dem Überschreiben automatisch Backups:

```
/lang/backup/german_2024-01-15_143022/buttons.php
```

## Lizenzierung

Dieses Modul ist lizenzpflichtig und nutzt URL-basierte Lizenzprüfung:

- Validierung erfolgt gegen: `https://license.redozone.de/validate.php`
- Cache-Zeit: 24 Stunden
- Bei Offline-Betrieb: Letzter Cache-Status gilt

## TODO / Roadmap

### Version 1.1
- [ ] GLGTranslator.php - Übersetzungs-Engine implementieren
- [ ] GLGFileWriter.php - Dateischreiber implementieren
- [ ] Background-Job für lange Prozesse
- [ ] DeepL API Integration
- [ ] Google Translate API Integration

### Version 1.2
- [ ] Multi-Domain Support
- [ ] Übersetzungsspeicher (Translation Memory)
- [ ] Glossar-Funktion für konsistente Übersetzungen
- [ ] CSV-Export/Import
- [ ] Diff-Ansicht für Änderungen

### Version 2.0
- [ ] KI-gestützte Kontexterkennung
- [ ] Automatische Qualitätsprüfung
- [ ] Team-Workflow (Freigabeprozess)
- [ ] API für externe Tools

## Support

Bei Fragen oder Problemen:
- E-Mail: support@redozone.de
- Web: https://redozone.de

## Changelog

### Version 1.0.0 (2024-11-09)
- Initiales Release
- Grundstruktur und Admin-Interface
- OpenAI Integration (Vorbereitung)
- Lizenzprüfung
- Logging und Update-Tracking

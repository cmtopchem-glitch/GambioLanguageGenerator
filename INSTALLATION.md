# Gambio Language Generator - Installationsanleitung

## Schritt-für-Schritt Installation

### 1. Dateien hochladen

**Via FTP/SFTP:**
```
Lade das Verzeichnis "GambioLanguageGenerator" hoch nach:
/dein-shop/GXModules/
```

**Resultierende Struktur:**
```
/dein-shop/
└── GXModules/
    └── GambioLanguageGenerator/
        ├── admin/
        ├── includes/
        ├── lang/
        ├── images/
        └── module.info
```

### 2. Verzeichnisse und Berechtigungen

**Cache-Verzeichnis erstellen:**
```bash
mkdir -p /dein-shop/cache
chmod 755 /dein-shop/cache
```

**Backup-Verzeichnis erstellen (optional):**
```bash
mkdir -p /dein-shop/backup/language_generator
chmod 755 /dein-shop/backup/language_generator
```

### 3. Datenbank-Konfiguration

Die Tabellen werden automatisch beim ersten Aufruf erstellt. Optional kannst du sie manuell anlegen:

```sql
-- Settings Tabelle
CREATE TABLE IF NOT EXISTS `rz_glg_settings` (
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text NOT NULL,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log Tabelle
CREATE TABLE IF NOT EXISTS `rz_glg_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` datetime DEFAULT CURRENT_TIMESTAMP,
    `action` varchar(50) NOT NULL,
    `source_language` varchar(50) DEFAULT NULL,
    `target_language` varchar(50) DEFAULT NULL,
    `status` enum('success','error','running') DEFAULT 'running',
    `details` text,
    PRIMARY KEY (`id`),
    KEY `date` (`date`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update Tracking Tabelle
CREATE TABLE IF NOT EXISTS `rz_glg_update_tracking` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `last_update` datetime DEFAULT CURRENT_TIMESTAMP,
    `source_language` varchar(50) NOT NULL,
    `target_language` varchar(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `languages` (`source_language`, `target_language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4. Lizenzschlüssel eintragen

**Option A: Via SQL**
```sql
INSERT INTO configuration 
    (configuration_key, configuration_value, configuration_group_id, sort_order, date_added)
VALUES 
    ('GLG_LICENSE_KEY', 'DEIN-LIZENZSCHLUESSEL-HIER', 6, 1, NOW())
ON DUPLICATE KEY UPDATE 
    configuration_value = 'DEIN-LIZENZSCHLUESSEL-HIER';
```

**Option B: Via Gambio Admin**
- Gehe zu: Konfiguration → Meine Shop-Einstellungen
- Füge neue Konfiguration hinzu:
  - Key: `GLG_LICENSE_KEY`
  - Value: Dein Lizenzschlüssel
  - Group: 6

### 5. OpenAI API-Schlüssel vorbereiten

1. Gehe zu: https://platform.openai.com/api-keys
2. Erstelle einen neuen API-Schlüssel
3. Kopiere den Schlüssel (wird nur einmal angezeigt!)

**Empfohlene Einstellungen:**
- Rate Limits: 500 requests/min
- Budget Limit: $50/Monat (anpassen nach Bedarf)

### 6. Modul aufrufen

**Im Gambio Admin:**
```
Toolbox → Module → GambioLanguageGenerator
```

Oder direkt via URL:
```
https://dein-shop.de/admin/admin.php?do=GambioLanguageGenerator
```

### 7. Einstellungen konfigurieren

Im Modul unter "Einstellungen":

1. **API-Provider:** OpenAI auswählen
2. **API-Schlüssel:** Deinen OpenAI API-Key eintragen
3. **Modell:** GPT-4o (beste Qualität) oder GPT-4o-mini (günstiger)
4. **Temperature:** 0.3 (Standard, für konsistente Übersetzungen)
5. **Max Tokens:** 4000 (Standard)
6. **Backup aktiviert:** ✓ (empfohlen)
7. **Speichern** klicken
8. **API testen** klicken zur Verifikation

### 8. Erste Generierung testen

1. Wechsle zum Tab "Sprachen generieren"
2. **Quellsprache:** Deine Hauptsprache (z.B. Deutsch)
3. **Zielsprachen:** Wähle EINE Sprache zum Testen (z.B. Englisch)
4. **Core-Dateien:** ✓ aktivieren
5. **GXModules:** ✗ erst mal deaktivieren
6. Klicke auf "Sprachen generieren"

**Erwartete Dauer:** 5-15 Minuten je nach Anzahl der Spracheinträge

## Wichtige Hinweise

### Kosten

Die Übersetzungskosten hängen vom gewählten Modell ab:

**GPT-4o:**
- Input: $2.50 / 1M tokens
- Output: $10.00 / 1M tokens
- ~1000 Sprachvariablen ≈ $0.50 - $2.00

**GPT-4o-mini:**
- Input: $0.15 / 1M tokens
- Output: $0.60 / 1M tokens
- ~1000 Sprachvariablen ≈ $0.05 - $0.20

### Backup

Bei aktiviertem Backup werden alle überschriebenen Dateien gesichert nach:
```
/backup/language_generator/YYYY-MM-DD_HHmmss/
```

Backups älter als 30 Tage werden automatisch gelöscht.

### Performance

**Erste Generierung:**
- Alle Sprachdateien: 30-60 Minuten
- Nur Core-Dateien: 10-20 Minuten
- Einzelnes Modul: 2-5 Minuten

**Updates (nur geänderte Texte):**
- Deutlich schneller, nur Delta wird übersetzt

### Empfohlene Reihenfolge

1. **Test:** Erst mit 1 Zielsprache und ohne Module testen
2. **Core:** Dann alle Core-Dateien für alle Sprachen generieren
3. **Module:** Zuletzt einzelne Module nach Bedarf

## Troubleshooting

### "Lizenz ungültig"
- Prüfe ob der Lizenzschlüssel korrekt in der Datenbank steht
- Prüfe ob die URL übereinstimmt
- Cache löschen: `rm /cache/glg_license.cache`

### "API-Fehler"
- API-Schlüssel prüfen
- OpenAI Account Guthaben prüfen
- Rate Limits prüfen (max. 500 req/min)

### "Keine Quelldaten gefunden"
- Prüfe ob `language_phrases_cache` gefüllt ist
- Prüfe ob die Quellsprache in der Datenbank existiert

### "Datei konnte nicht geschrieben werden"
- Prüfe Schreibrechte auf `/lang/` Verzeichnis
- Prüfe Schreibrechte auf `/GXModules/` Verzeichnis

### Generierung hängt
- Prüfe PHP max_execution_time (min. 300 Sekunden)
- Prüfe PHP memory_limit (min. 256MB)
- Prüfe Netzwerkverbindung zu OpenAI

## Support

Bei Problemen:
- E-Mail: support@redozone.de
- Dokumentation: https://redozone.de/docs/language-generator
- GitHub Issues: (wenn verfügbar)

## Deinstallation

Falls du das Modul entfernen möchtest:

```bash
# 1. Dateien löschen
rm -rf /dein-shop/GXModules/GambioLanguageGenerator/

# 2. Datenbank bereinigen
DROP TABLE IF EXISTS rz_glg_settings;
DROP TABLE IF EXISTS rz_glg_log;
DROP TABLE IF EXISTS rz_glg_update_tracking;
DELETE FROM configuration WHERE configuration_key = 'GLG_LICENSE_KEY';

# 3. Cache/Backup löschen (optional)
rm -rf /dein-shop/cache/glg_*
rm -rf /dein-shop/backup/language_generator/
```

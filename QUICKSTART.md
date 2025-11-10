# Gambio Language Generator - Quick Start

## Installation über Gambio Modulverwaltung

### Schritt 1: Modul hochladen

**Via FTP/SFTP:**
```
Lade das komplette Verzeichnis "GambioLanguageGenerator" hoch nach:
/dein-shop/GXModules/
```

**Resultierende Struktur:**
```
/GXModules/
└── GambioLanguageGenerator/
    ├── Classes/
    │   └── Controller/
    │       └── Admin/
    │           └── GambioLanguageGeneratorModuleCenterModuleController.inc.php
    ├── admin/
    ├── includes/
    ├── lang/
    ├── images/
    ├── module.info
    └── GambioLanguageGeneratorModuleCenterModuleController.inc.php
```

### Schritt 2: Cache löschen

**Im Gambio Admin:**
1. Gehe zu: `Toolbox → Cache`
2. Lösche: `Modul-Cache`
3. Lösche: `Seiten-Cache`

**Via SSH (alternative):**
```bash
rm -rf /dein-shop/cache/*
rm -rf /dein-shop/templates_c/*
```

### Schritt 3: Modul in Modulverwaltung finden

**Gambio 4.x:**
1. Gehe zu: `Module → Module Center`
2. Suche nach: "Language Generator" oder "Sprachgenerator"
3. Klicke auf: `Installieren`
4. Modul erscheint dann in der Navigation

**Gambio 3.x:**
1. Gehe zu: `Module → GX-Customizer`
2. Suche nach: "GambioLanguageGenerator"
3. Klicke auf: `Aktivieren`

### Schritt 4: Lizenzschlüssel eintragen

**Via SQL:**
```sql
INSERT INTO configuration 
    (configuration_key, configuration_value, configuration_group_id, sort_order, date_added)
VALUES 
    ('GLG_LICENSE_KEY', 'DEIN-LIZENZSCHLUESSEL-HIER', 6, 1, NOW())
ON DUPLICATE KEY UPDATE 
    configuration_value = 'DEIN-LIZENZSCHLUESSEL-HIER';
```

### Schritt 5: Modul aufrufen

Nach Installation über:
```
Module → Gambio Language Generator
```

Oder direkt via URL:
```
https://dein-shop.de/admin/admin.php?do=ModuleCenter&module=GambioLanguageGenerator
```

## Troubleshooting

### "Modul erscheint nicht in Modulverwaltung"

**Prüfe:**

1. **Verzeichnisstruktur korrekt?**
   ```bash
   ls -la /dein-shop/GXModules/GambioLanguageGenerator/
   ```
   → Muss `module.info` und `Classes/` enthalten

2. **Dateirechte korrekt?**
   ```bash
   chmod 755 /dein-shop/GXModules/GambioLanguageGenerator
   chmod 644 /dein-shop/GXModules/GambioLanguageGenerator/module.info
   chmod 644 /dein-shop/GXModules/GambioLanguageGenerator/Classes/Controller/Admin/*.inc.php
   ```

3. **Cache wirklich gelöscht?**
   - Browser-Cache (Strg+F5)
   - Shop-Cache (`/cache/`)
   - Template-Cache (`/templates_c/`)

4. **Gambio-Version kompatibel?**
   - Kompatibel: Gambio 3.0 - 4.9
   - Prüfe deine Version in: `Admin → Info → Version`

### "Lizenz ungültig"

**Lösung:**
1. Prüfe Lizenzschlüssel in Datenbank:
   ```sql
   SELECT * FROM configuration WHERE configuration_key = 'GLG_LICENSE_KEY';
   ```

2. Lösche Lizenz-Cache:
   ```bash
   rm /dein-shop/cache/glg_license.cache
   ```

3. Prüfe URL-Übereinstimmung:
   - Lizenzierte URL muss mit Shop-URL übereinstimmen
   - Inkl. http/https und mit/ohne www

### "Klasse nicht gefunden"

Falls Fehler wie `Class 'AdminHttpViewController' not found`:

**Lösung:**
Gambio 4.x braucht den MainFactory-Load. Prüfe ob vorhanden:
```bash
cat /dein-shop/GXModules/GambioLanguageGenerator/GambioLanguageGeneratorModuleCenterModuleController.inc.php
```

Sollte enthalten:
```php
MainFactory::load_origin_class('AdminHttpViewController');
```

## Manuelle Installation (falls Modulverwaltung nicht funktioniert)

Falls die Modulverwaltung nicht funktioniert, kannst du das Modul auch manuell installieren:

**1. SQL-Script ausführen:**
```bash
mysql -u DEIN_USER -p DEINE_DB < install.sql
```

**2. Direct-Access verwenden:**
Erstelle: `/admin/glg_access.php`
```php
<?php
define('_VALID_XTC', true);
require_once('includes/application_top.php');
require_once(DIR_FS_CATALOG . 'GXModules/GambioLanguageGenerator/admin/glg_admin.php');
```

**3. Aufrufen via:**
```
https://dein-shop.de/admin/glg_access.php
```

## Nächste Schritte nach Installation

1. **API-Schlüssel konfigurieren:**
   - Tab "Einstellungen" öffnen
   - OpenAI API-Key eintragen
   - API testen

2. **Erste Sprache testen:**
   - Tab "Sprachen verwalten" 
   - Neue Sprache anlegen (z.B. Español)
   
3. **Erste Generierung:**
   - Tab "Sprachen generieren"
   - Quellsprache: Deutsch
   - Zielsprache: Español
   - Nur Core-Dateien auswählen
   - Generieren starten

## Support

Bei Problemen:
- E-Mail: support@redozone.de
- GitHub: (falls verfügbar)
- Forum: (falls verfügbar)

## Struktur-Checkliste

Nach dem Upload sollte diese Struktur vorhanden sein:

```
✓ GXModules/GambioLanguageGenerator/module.info
✓ GXModules/GambioLanguageGenerator/GambioLanguageGeneratorModuleCenterModuleController.inc.php
✓ GXModules/GambioLanguageGenerator/Classes/Controller/Admin/GambioLanguageGeneratorModuleCenterModuleController.inc.php
✓ GXModules/GambioLanguageGenerator/admin/glg_admin.php
✓ GXModules/GambioLanguageGenerator/admin/glg_admin.js
✓ GXModules/GambioLanguageGenerator/admin/glg_controller.php
✓ GXModules/GambioLanguageGenerator/includes/*.php (7 Dateien)
✓ GXModules/GambioLanguageGenerator/lang/german/glg.php
✓ GXModules/GambioLanguageGenerator/lang/english/glg.php
✓ GXModules/GambioLanguageGenerator/images/icon.svg
```

Wenn alle Dateien vorhanden sind und der Cache gelöscht wurde, sollte das Modul in der Modulverwaltung erscheinen!

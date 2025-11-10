# Gambio Language Generator - Zugriff

## ✅ Modul ist installiert - So rufst du es auf:

### Option 1: Direktlink (EMPFOHLEN)

Öffne diese URL in deinem Browser:
```
https://test.redozone.de/admin/admin.php?do=GambioLanguageGeneratorModuleCenterModule
```

**Bookmark diese URL!** So kannst du das Modul immer direkt aufrufen.

### Option 2: Quick-Access Script erstellen

Auf deinem Server ausführen:

```bash
# Erstelle Shortcut-Datei
cat > /srv/www/test.redozone/admin/language_generator.php << 'EOF'
<?php
define('_VALID_XTC', true);
require_once('includes/application_top.php');
header('Location: admin.php?do=GambioLanguageGeneratorModuleCenterModule');
exit;
EOF

chmod 644 /srv/www/test.redozone/admin/language_generator.php
```

Dann aufrufen via:
```
https://test.redozone.de/admin/language_generator.php
```

### Option 3: Nach Update - Konfigurationsbutton

Mit dem neuesten Update (gerade hochgeladen) sollte jetzt auch ein **"Konfigurieren"** Button in der Modulverwaltung erscheinen!

**So testest du das:**
1. Lade das neue ZIP hoch (überschreibe die alte Version)
2. Gehe zu: Module → Module Center
3. Suche: "Language Generator"
4. Jetzt sollte neben "Bearbeiten" auch ein **"Konfigurieren"** Button sein

### Option 4: Admin-Menü erweitern (manuell)

Falls du das Modul ins Admin-Menü integrieren willst:

**Für Gambio 4.x mit Custom Admin-Menü:**

1. Finde deine Admin-Menü-Datei (meist: `/admin/includes/...`)
2. Füge hinzu:
```php
<li>
    <a href="admin.php?do=GambioLanguageGeneratorModuleCenterModule">
        <i class="fa fa-language"></i> 
        Language Generator
    </a>
</li>
```

## 🎯 Schnellstart nach Installation

1. **Lizenzschlüssel eintragen** (SQL):
```sql
UPDATE configuration 
SET configuration_value = 'DEIN-LIZENZSCHLUESSEL' 
WHERE configuration_key = 'GLG_LICENSE_KEY';
```

2. **Modul aufrufen**:
```
https://test.redozone.de/admin/admin.php?do=GambioLanguageGeneratorModuleCenterModule
```

3. **API-Key konfigurieren**:
- Tab "Einstellungen" öffnen
- OpenAI API-Key eintragen
- Speichern & Testen

4. **Loslegen!** 🚀

## 📌 Bookmark-Vorschlag

Erstelle ein Lesezeichen mit:
- **Name:** "🌍 Language Generator"
- **URL:** `https://test.redozone.de/admin/admin.php?do=GambioLanguageGeneratorModuleCenterModule`

Dann hast du immer schnellen Zugriff!

## ⚠️ Wichtig

Die Modulverwaltung zeigt nur Standard-Settings (Ein/Aus-Schalter).
Die **eigentliche Konfiguration** läuft über die direkte URL zum ModuleCenterModule!

Das ist bei allen Custom-Modulen in Gambio so (auch beim AI Product Optimizer).

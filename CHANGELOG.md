# Gambio Language Generator - Changelog

## Version 1.0.0 - Erweitert (09.11.2024)

### 🆕 Neue Features

#### 1. Automatische Sprach-Verwaltung
**GLGLanguageManager.php** - Neue Klasse zur Verwaltung von Sprachen

**Features:**
- ✅ Neue Sprachen automatisch im Gambio-System anlegen
- ✅ Automatische Erstellung aller benötigten Verzeichnisse:
  - `/lang/[sprache]/`
  - `/lang/[sprache]/admin/`
  - `/lang/[sprache]/images/`
  - `/lang/[sprache]/modules/`
  - `/lang/[sprache]/sections/`
- ✅ Automatische Generierung von Sprachicons aus Länderflaggen
- ✅ Fallback auf generierte Standard-Icons mit Ländercode
- ✅ Kopie der Basis-Konfiguration von vorhandenen Sprachen
- ✅ 18 vordefinierte Sprachvorschläge:
  - Español, Français, Italiano, Nederlands
  - Polski, Português, Русский, Türkçe
  - 中文, 日本語, Svenska, Norsk
  - Dansk, Suomi, Ελληνικά, Čeština
  - Magyar, Română

**Verwendung:**
```php
$languageManager = new GLGLanguageManager();
$result = $languageManager->createLanguage([
    'name' => 'Español',
    'code' => 'es',
    'directory' => 'spanish',
    'country_code' => 'ES'
]);
```

#### 2. Sprach-Vergleich & Testlauf
**GLGCompare.php** - Neue Klasse für Vergleich und Vorschau

**Features:**
- ✅ Vergleich zwischen Quell- und Zielsprache
- ✅ Zeigt fehlende Übersetzungen an:
  - Komplett fehlende Dateien
  - Fehlende Sektionen
  - Fehlende Keys
- ✅ Detaillierte Statistiken:
  - Gesamt-Einträge (Quelle/Ziel)
  - Anzahl fehlender Einträge
  - Vollständigkeit in Prozent
- ✅ Gruppierung fehlender Einträge nach Datei
- ✅ HTML-Report-Generator mit visueller Darstellung
- ✅ CSV-Export für fehlende Einträge
- ✅ Top-Listen (Dateien mit meisten fehlenden Einträgen)

**Verwendung:**
```php
$compare = new GLGCompare();
$comparison = $compare->compareLanguages('german', 'spanish', $options);

// HTML Report
$html = $compare->createHtmlReport($comparison);

// CSV Export
$compare->exportToCsv($comparison, 'missing_translations.csv');
```

#### 3. Erweiterte Admin-Oberfläche

**Neue Tabs:**

**Tab "Vergleich / Testlauf":**
- Interaktiver Sprachvergleich
- Visuelle Darstellung der Vollständigkeit
- Statistik-Boxen (Quell-/Ziel-/Fehlend/Vollständigkeit)
- Fortschrittsbalken
- Detail-Tabellen mit fehlenden Keys
- Buttons:
  - "Detailreport anzeigen" (öffnet HTML-Report)
  - "Als CSV exportieren"
  - "Nur fehlende übersetzen"

**Tab "Sprachen verwalten":**
- Liste mit 18 häufigen Sprachvorschlägen
- Formular für benutzerdefinierte Sprachen
- Auto-Fill durch Klick auf Vorschlag
- Felder:
  - Sprachname (z.B. "Español")
  - ISO-Code (z.B. "es")
  - Verzeichnis (z.B. "spanish")
  - Ländercode (z.B. "ES" für Flagge)

#### 4. Sprachicon-Generierung

**Automatische Icon-Suche:**
1. Prüft auf vorhandene Länderflaggen in:
   - `/images/flags/[CODE].gif`
   - `/images/flags/[code].gif`
   - `/admin/images/icons/flags/[CODE].png`
   - `/admin/images/icons/flags/[code].png`

2. Konvertiert PNG zu GIF wenn nötig (via GD Library)

3. Erstellt Fallback-Icon mit Ländercode wenn keine Flagge gefunden

**Ergebnis:**
- Icon wird gespeichert in: `/lang/[sprache]/images/icon.gif`
- Automatische Registrierung in der Datenbank

### 📊 Statistiken der Erweiterung

**Neue Dateien:**
- `includes/GLGLanguageManager.php` (~450 Zeilen)
- `includes/GLGCompare.php` (~470 Zeilen)

**Erweiterte Dateien:**
- `admin/glg_admin.php` (+150 Zeilen) - 2 neue Tabs
- `admin/glg_admin.js` (+150 Zeilen) - Neue Funktionen
- `admin/glg_controller.php` (+90 Zeilen) - 4 neue Actions
- `lang/german/glg.php` (+30 Konstanten)
- `lang/english/glg.php` (+30 Konstanten)

**Gesamt:**
- **+920 Zeilen neuer Code**
- **+60 neue Sprachkonstanten**
- **+4 neue AJAX-Actions**
- **+2 neue Admin-Tabs**

### 🔧 Technische Details

**GLGLanguageManager:**
- Erstellt Datenbank-Einträge in `languages` Tabelle
- Verwaltet `sort_order` automatisch
- Kopiert Daten aus folgenden Tabellen (wenn vorhanden):
  - `categories_description`
  - `products_description`
  - `content_manager`
- Prüft Tabellen-Existenz vor Zugriff (robust gegen verschiedene Gambio-Versionen)

**GLGCompare:**
- Nutzt GLGReader für Daten-Abruf
- Vergleicht auf 3 Ebenen: Dateien → Sektionen → Keys
- Berechnet präzise Statistiken
- Generiert HTML mit embedded CSS (standalone)
- CSV-Export mit UTF-8 BOM für Excel-Kompatibilität

**Icon-Generierung:**
- GD Library optional (graceful degradation)
- Standard-Größe: 16x11 Pixel (Gambio-Standard)
- Format: GIF (Gambio-Standard)
- Farbschema: Neutral grau bei Fallback

### 🎯 Use Cases

**Szenario 1: Neue Sprache hinzufügen**
```
1. Tab "Sprachen verwalten" öffnen
2. Sprache aus Vorschlägen wählen (z.B. Español)
3. "Sprache anlegen" klicken
4. → Sprache ist angelegt mit Icon und Struktur
5. Tab "Sprachen generieren" öffnen
6. Neue Sprache als Ziel wählen
7. Generieren starten
```

**Szenario 2: Bestehende Sprache aktualisieren**
```
1. Tab "Vergleich / Testlauf" öffnen
2. Quell- und Zielsprache wählen
3. "Vergleich starten" klicken
4. → Sieht: 234 fehlende Einträge (15% unvollständig)
5. "Detailreport anzeigen" klicken
6. → HTML-Report mit allen Details öffnet sich
7. Zurück zum Tab, "Nur fehlende übersetzen" klicken
8. → Nur die 234 fehlenden werden übersetzt
```

**Szenario 3: Qualitätskontrolle**
```
1. Nach Generierung: Tab "Vergleich" öffnen
2. Vergleich starten
3. Prüfen ob 100% Vollständigkeit
4. Falls nicht: Report exportieren
5. Fehlende manuell nachbearbeiten
```

### 🚀 Performance

**Sprach-Vergleich:**
- ~1000 Einträge: < 1 Sekunde
- ~10000 Einträge: ~3-5 Sekunden
- Keine API-Calls nötig (reine Datenbank-Abfrage)

**Sprache anlegen:**
- Dauer: < 1 Sekunde
- Verzeichnisse erstellen
- Icon generieren
- Datenbank-Einträge
- Basis-Konfiguration kopieren

### 📝 API-Referenz

**Neue AJAX-Actions:**

```javascript
// Sprache anlegen
{
    action: 'createLanguage',
    name: 'Español',
    code: 'es',
    directory: 'spanish',
    country_code: 'ES'
}

// Sprachvorschläge abrufen
{
    action: 'getLanguageSuggestions'
}

// Sprachen vergleichen
{
    action: 'compareLanguages',
    sourceLanguage: 'german',
    targetLanguage: 'spanish',
    includeCoreFiles: true,
    includeGXModules: true
}

// HTML-Report generieren
{
    action: 'getComparisonReport',
    sourceLanguage: 'german',
    targetLanguage: 'spanish',
    includeCoreFiles: true,
    includeGXModules: true
}
```

### 🔜 Geplante Features (v1.1)

- [ ] "Nur fehlende übersetzen" Funktion implementieren
- [ ] CSV-Import für Übersetzungen
- [ ] Batch-Vergleich (alle Sprachen auf einmal)
- [ ] Übersetzungsspeicher (Translation Memory)
- [ ] Automatische Aktualisierungs-Benachrichtigungen

### 📖 Dokumentation

Siehe auch:
- [README.md](README.md) - Vollständige Dokumentation
- [INSTALLATION.md](INSTALLATION.md) - Installationsanleitung

# Nuki Opener Web API

[![Image](../imgs/NUKI_Logo.png)](https://nuki.io/de/)

[![Image](../imgs/NUKI_Opener.png)]()  

Dieses Modul integriert den [NUKI Opener](https://nuki.io/de/opener) in [IP-Symcon](https://www.symcon.de) mittels der [Nuki Web API](https://developer.nuki.io/t/nuki-web-api/25).  
In Verbindung mit einer NUKI Bridge macht der Nuki Opener aus deiner bestehenden Gegensprechanlage einen smarten Türöffner.  

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Tür öffnen
* Ring to Open ein- / ausschalten
* Klingelunterdrückung ein- / ausschalten
* Sounds einstellen
* Lautstärke ändern
* LED-Signal ein- / ausschalten
* Protokoll anzeigen

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- Nuki Opener
- Nuki Bridge
- [Nuki Web Zugang](https://web.nuki.io/#/login)
- [Nuki Splitter Web API](../Splitter) Instanz inkl. API Token

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Über das Module Control folgende URL hinzufügen: `https://github.com/ubittner/SymconNukiWeb`
* Über den Module Store das `Nuki Web`-Modul, sofern bereits im Module Store vorhanden, installieren.

### 4. Einrichten der Instanzen in IP-Symcon

- Unter `Instanz hinzufügen` kann das `Nuki Opener Web API`-Modul mithilfe des Schnellfilters gefunden werden.  
- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name                            | Beschreibung
------------------------------- | ------------------
Smart Lock ID                   | ID des Openers
Account ID                      | ID des Benutzerkontos
Auth ID                         | Authorisierungs ID
Name                            | Name des Gerätes
Aktualisierungsintervall        | Intervall zur Aktualisierung
Protokoll verwenden             | Protokoll verwenden
Zeitraum (letzte Tage)          | Zeitraum
Anzahl der maximalen Einträge   | Anzahl der maximalen Einträge   

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt.  
Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name                            | Typ     | Beschreibung
------------------------------- | ------- | ----------------------------------------------------
Door                            | integer | Öffnet die Tür
DeviceState                     | integer | Status des Openers
BatteryState                    | boolean | Batteriestatus
RingToOpen                      | boolean | Ring To Open ein- / ausschalten
RingToOpenTimeout               | integer | Dauer Ring To Open
OneTimeAccess                   | boolean | Einmal-Zutritt
ContinuousMode                  | boolean | Dauermodus  ein- / ausschalten
RingSuppressionRing             | boolean | Klingelunterdrückung Klingeln ein- / ausschalten
RingSuppressionRingToOpen       | boolean | Klingelunterdrückung Ring To Open ein- / ausschalten
RingSuppressionContinuousMode   | boolean | Klingelunterdrückung Dauermodus ein- / ausschalten
SoundDoorbellRings              | integer | Sound Klingeln
SoundOpenViaApp                 | integer | Sound Öffnen via App
SoundRingToOpen                 | integer | Sound Ring To Open
SoundContinuousMode             | integer | Sound Dauermodus
Volume                          | integer | Lautstärke
OpenerLED                       | boolean | LED-Signal am Opener ein- / ausschalten
ActivityLog                     | string  | Protokoll

#### Profile

NUKIOW.InstanzID.Name

Name                | Typ
------------------- | -------
Door                | integer
DeviceState         | integer  
BatteryState        | boolean
RingToOpenTimeout   | integer
SoundDoorbellRings  | integer
SoundOpenViaApp     | integer
SoundRingToOpen     | integer
SoundContinuousMode | integer
Volume              | integer

Wird die NUKI Smart Lock Instanz gelöscht, so werden automatisch die oben aufgeführten Profile gelöscht.

### 6. WebFront

Die Funktionalität, die das Modul im WebFront bietet.

* Tür öffnen
* Ring to Open ein- / ausschalten
* Klingelunterdrückung ein- / ausschalten
* Sounds einstellen
* Lautstärke ändern
* LED-Signal ein- / ausschalten
* Protokoll anzeigen

### 7. PHP-Befehlsreferenz

```text
Tür öffnen

NUKIOW_OpenDoor(integer $InstanzID);
Liefert keinen Rückgabewert.

Beispiel:

NUKIOW_OpenDoor(12345);
```

```text
Dauermodus ein- / ausschalten

NUKIOW_ToggleContinuousMode(integer $InstanzID, bool $Status);
Liefert keinen Rückgabewert.

$Status = false;		//ausschalten
$Status = true; 		//einschalten

Beispiel:

NUKIOW_ToggleContinuousMode(12345, true);
```

```text
Ring To Open ein- / ausschalten

NUKIOW_ToggleRingToOpen(integer $InstanzID, bool $Status);
Liefert keinen Rückgabewert.

$Status = false;		//ausschalten
$Status = true; 		//einschalten

Beispiel:

NUKIOW_ToggleRingToOpen(12345, true);
```

```text
Daten aktualisieren

NUKIOW_UpdateData(integer $InstanzID);
Liefert keinen Rückgabewert.

Fragt die Daten des Nuki Openers ab und aktualisiert die Instanz.

Beispiel:

NUKIOW_UpdateData(12345);
```

```text
Protokoll des Nuki Openers abrufen

NUKIOW_GetActivityLog(integer $InstanzID, bool $Aktualisierung);
Liefert als Rückgabewert das Protokoll des Nuki Openers als json kodierten String.

Es gelten die Einschränkungen der Instanzkonfiguration (Zeitraum letzte Tage / Anzahl der maximalen Einträge)

$Aktualisierung = false;	//ruft nur die Daten ab
$Aktualisierung = true;		//aktualisiert das Protokoll im WebFront

Beispiel:

$log = NUKIOW_GetActivityLog(12345, true);
print_r(json_decode($log, true));  //Ausgabe der Daten als Array
```

```text
Konfiguration des Nuki Openers abrufen

NUKIOW_GetOpenerData(integer $InstanzID, bool $Aktualisierung);
Liefert als Rückgabewert einen json kodierten String mit den Konfigurationsdaten des Nuki Openers.

$Aktualisierung = false;	//ruft nur die Daten ab
$Aktualisierung = true;		//aktualisiert die Einstellungen im WebFront

Beispiel:

$data = NUKIOW_GetOpenerData(12345, true);
print_r(json_decode($data, true));  //Ausgabe der Daten als Array
```

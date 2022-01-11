# Nuki Smart Lock Web API

[![Image](../imgs/NUKI_Logo.png)](https://nuki.io/de/)

[![Image](../imgs/NUKI_SmartLock.png)]()  

Dieses Modul integriert das [NUKI Smart Lock](https://nuki.io/de/smart-lock/) Version 1.0, 2.0, 3.0 (Pro) in [IP-Symcon](https://www.symcon.de) mittels der [Nuki Web API](https://developer.nuki.io/t/nuki-web-api/25).  
In Verbindung mit einer NUKI Bridge macht das Nuki Smart Lock aus deinem Türschloss einen smarten Türöffner.

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

* Schloss zu- und aufsperren inkl. weiterer Funktionen 
* Gerätestatus anzeigen (diverse)
* LED-Signal ein- / ausschalten
* Helligkeit einstellen
* Informationen zum Türsensor anzeigen
* Informationen zum Keypad anzeigen
* Protokoll anzeigen

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- Nuki Smart Lock 1.0, 2.0, 3.0 (Pro)
- Nuki Bridge
- [Nuki Web Zugang](https://web.nuki.io/#/login)
- [Nuki Splitter Web API](../Splitter) Instanz inkl. API Token

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Über das Module Control folgende URL hinzufügen: `https://github.com/ubittner/SymconNukiWeb`
* Über den Module Store das `Nuki Web`-Modul, sofern bereits im Module Store vorhanden, installieren.

### 4. Einrichten der Instanzen in IP-Symcon

- Unter `Instanz hinzufügen` kann das `Nuki Smart Lock Web API`-Modul mithilfe des Schnellfilters gefunden werden.  
- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name                                    | Beschreibung
--------------------------------------- | ------------------
Smart Lock ID                           | ID des Smart Locks
Account ID                              | ID des Benutzerkontos
Auth ID                                 | Authorisierungs ID
Name                                    | Name des Gerätes
Aktualisierungsintervall                | Intervall zur Aktualisierung
Türsensor                               | Türsensor Informationen anzeigen
Keypad                                  | Keypad Informationen anzeigen
Protokoll                               | Protokoll anzeigen
Protokollzeitraum (letzte Tage)         | Protokollzeitraum in Tagen
Anzahl der maximalen Protokolleinträge  | Anzahl der maximalen Protokolleinträge

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt.  
Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name                            | Typ     | Beschreibung
------------------------------- | ------- | -------------------------------------------------------------
SmartLock                       | integer | Smart Lock Aktionen (auf- und zusperren + weitere Funktionen)
DeviceState                     | integer | Gerätestatus (diverse)
BatteryState                    | boolean | Batteriestatus (OK, Batterie schwach)
BatteryCharge                   | integer | Batterieladung (in %)
BatteryCharging                 | boolean | Batterieaufladung (in- / aktiv)
SmartLockLED                    | boolean | Led-Signal am Smart Lock (Aus / An)
Brightness                      | integer | LED Helligkeit (0 - 5) 
DoorState                       | integer | Türstatus (diverse)
DoorSensorBatteryState          | boolean | Türsensor Batteriestatus (OK, Batterie schwach)
KeypadBatteryState              | boolean | Keypad Batteriestatus (OK, Batterie schwach)
ActivityLog                     | string  | Protokoll

#### Profile

NUKISLW.InstanzID.Name

Name                    | Typ
----------------------- | -------
SmartLock               | integer
DeviceState             | integer
BatteryState            | boolean
BatteryCharge           | integer
BatteryCharging         | boolean
Brightness              | integer
DoorState               | integer
DoorSensorBatteryState  | boolean
KeypadBatteryState      | boolean

Wird die NUKI Smart Lock Instanz gelöscht, so werden automatisch die oben aufgeführten Profile gelöscht.

### 6. WebFront

Die Funktionalität, die das Modul im WebFront bietet.

* Smart Lock Aktionen (auf- und zusperren + weitere Funktionen)
* Gerätestatus anzeigen (diverse)
* LED-Signal ein- / ausschalten
* Helligkeit einstellen
* Informationen zum Türsensor anzeigen
* Informationen zum Keypad anzeigen
* Protokoll anzeigen


### 7. PHP-Befehlsreferenz

```text
Smart Lock Aktionen (Zu- und aufsperren + weitere Aktionen)

NUKISLW_SetSmartLockAction(integer $InstanzID, integer $Aktion);

$Aktion:
0   =   Zusperren
1   =   Aufsperren
2   =   Tür öffnen
3   =   Lock 'n' Go Tür
4   =   Lock 'n' Go Tür öffnen

Liefert keinen Rückgabewert.

Beispiel:

NUKISLW_SetSmartLockAction(12345, 0);   //Zusperren
```

```text
Daten aktualisieren

NUKISLW_UpdateData(integer $InstanzID);
Liefert keinen Rückgabewert.

Fragt die Daten des Nuki Smart Locks ab und aktualisiert die Instanz.

Beispiel:

NUKISLW_UpdateData(12345);
```

```text
Protokoll des Nuki Smart Locks abrufen

NUKISLW_GetActivityLog(integer $InstanzID, bool $Aktualisierung);
Liefert als Rückgabewert das Protokoll des Nuki Smart Locks als json kodierten String.

Es gelten die Einschränkungen der Instanzkonfiguration (Zeitraum letzte Tage / Anzahl der maximalen Einträge)

$Aktualisierung = false;	//ruft nur die Daten ab
$Aktualisierung = true;		//aktualisiert das Protokoll im WebFront

Beispiel:

$log = NUKISLW_GetActivityLog(12345, true);
print_r(json_decode($log, true));  //Ausgabe der Daten als Array
```

```text
Konfiguration des Nuki Smart Locks abrufen

NUKISLW_GetSmartLockData(integer $InstanzID, bool $Aktualisierung);
Liefert als Rückgabewert einen json kodierten String mit den Konfigurationsdaten des Nuki Smart Locks.

$Aktualisierung = false;	//ruft nur die Daten ab
$Aktualisierung = true;		//aktualisiert die Einstellungen im WebFront

Beispiel:

$data = NUKISLW_GetSmartLockData(12345, true);
print_r(json_decode($data, true));  //Ausgabe der Daten als Array
```
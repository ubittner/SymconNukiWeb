# Nuki Opener Web API

[![Image](../imgs/NUKI_Logo.png)](https://nuki.io/de/)

[![Image](../imgs/NUKI_Opener.png)]()  

Dieses Modul integriert den [NUKI Opener](https://nuki.io/de/opener) in [IP-Symcon](https://www.symcon.de) mittels Nuki Web API.  
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

* Klingelunterdrückung de- bzw. aktivieren
* Lautstärke verändern

### 2. Voraussetzungen

- IP-Symcon ab Version 5.5
- Nuki Splitter Web API
- Nuki Opener

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Über das Module Control folgende URL hinzufügen `https://github.com/ubittner/SymconNukiWeb`

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'Nuki Opener Web API'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
diverse  | 
         |

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name    | Typ     | Beschreibung
------- | ------- | ------------
diverse |         |
        |         |

#### Profile

Name    | Typ
------- | -------
diverse |
        |

### 6. WebFront

Die Funktionalität, die das Modul im WebFront bietet.

* Klingelunterdrückung de- bzw. aktivieren
* Lautstärke verändern

### 7. PHP-Befehlsreferenz

`boolean NUKIOW_BeispielFunktion(integer $InstanzID);`
Erklärung der Funktion.

Beispiel:
`NUKIOW_BeispielFunktion(12345);`
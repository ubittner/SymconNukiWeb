# Nuki Configurator Web API  

[![Image](../imgs/NUKI_Logo.png)](https://nuki.io/de/)  

Dieses Modul listet die vorhandenen Nuki Geräte mittels [Nuki Web API](https://developer.nuki.io/t/nuki-web-api/25) auf.  
Der Nutzer kann die ausgewählten Geräte automatisch anlegen lassen.  

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

* Listet die verfügbaren Nuki Geräte auf
* Automatisches Anlegen des ausgewählten Nuki Gerätes

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- Nuki Smart Lock 1.0, 2.0, 3.0 (Pro)
- Nuki Opener
- Nuki Bridge
- [Nuki Web Zugang](https://web.nuki.io/#/login)
- [Nuki Splitter Web API](../Splitter)

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Über den Module Store das `Nuki Web`-Modul installieren.

### 4. Einrichten der Instanzen in IP-Symcon

- In IP-Symcon an beliebiger Stelle `Instanz hinzufügen` auswählen und `Nuki Konfigurator Web API` auswählen, welches unter dem Hersteller `NUKI` aufgeführt ist.  
- Es wird eine Nuki Konfigurator Web API Instanz unter der Kategorie `Konfigurator Instanzen` angelegt.  

__Konfigurationsseite__:

| Name        | Beschreibung                              |
|-------------|-------------------------------------------|
| Kategorie   | Auswahl der Kategorie für die Nuki Geräte |
| Nuki Geräte | Liste der verfügbaren Nuki Geräte         |

__Schaltflächen__:

| Name           | Beschreibung                                                     |
|----------------|------------------------------------------------------------------|
| Alle erstellen | Erstellt für alle aufgelisteten Nuki Geräte jeweils eine Instanz |
| Erstellen      | Erstellt für das ausgewählte Nuki Gerät eine Instanz             |

__Vorgehensweise__:

Über die Schaltfläche `AKTUALISIEREN` können Sie im Nuki Konfigurator Web API die Liste der verfügbaren Nuki Geräte jederzeit aktualisieren.  
Wählen Sie `ALLE ERSTELLEN` oder wählen Sie ein Nuki Gerät aus der Liste aus und drücken dann die Schaltfläche `ERSTELLEN`, um das Nuki Gerät automatisch anzulegen.  
Sofern noch keine [Nuki Splitter Web API](../Splitter) Instanz angelegt wurde, muss einmalig beim Erstellen der Nuki Konfigurator Web API Instanz die Konfiguration der Nuki Splitter Web API Instanz vorgenommen werden.  
Geben Sie Ihren API Token und den Netzwerk-Timeout an.  
Wählen Sie anschließend `WEITER` aus.  

Sofern Sie mehrere Nuki Splitter Web API Instanzen verwenden, können Sie in der Instanzkonfiguration unter `GATEWAY ÄNDERN` die entsprechende Nuki Splitter Web API Instanz auswählen.  
Die Nuki Splitter Web API Instanz muss dafür bereits vorhanden sein.  

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt.  
Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Es werden keine Statusvariablen verwendet.

##### Profile:

Es werden keine Profile verwendet.

### 6. WebFront

Der Nuki Konfigurator Web API hat im WebFront keine Funktionalität.

### 7. PHP-Befehlsreferenz

Es ist keine Befehlsreferenz verfügbar.
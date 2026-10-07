# GeCoS-Modules

Symcon-Module für die GeCoS-Hardware von [GeDaD](https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/): I²C-Ein-/Ausgabemodule, 1-Wire-Sensoren am GeCoS-Server sowie die netzwerkbasierten Klimasensoren WTH, WTHQ und WSens.

## Inhaltsverzeichnis

1. [Voraussetzungen](#1-voraussetzungen)
2. [Installation](#2-installation)
3. [Einrichtung](#3-einrichtung)
4. [Module](#4-module)
   - [GeCoS IO V2](#gecos-io-v2)
   - [GeCoS Konfigurator (I²C)](#gecos-konfigurator-ic)
   - [GeCoS 1-Wire-Konfigurator](#gecos-1-wire-konfigurator)
   - [GeCoS 16In](#gecos-16in)
   - [GeCoS 16Out](#gecos-16out)
   - [GeCoS PWM16Out](#gecos-pwm16out)
   - [GeCoS RGBW](#gecos-rgbw)
   - [GeCoS 4AnalogIn](#gecos-4analogin)
   - [GeCoS DS18B20 / DS18S20](#gecos-ds18b20--ds18s20)
   - [GeCoS DS2413](#gecos-ds2413)
   - [GeCoS DS2438](#gecos-ds2438)
   - [GeCoS RPi](#gecos-rpi)
   - [GeCoS WTH / WTHQ / WSens](#gecos-wth--wthq--wsens)
5. [Versionshistorie](#5-versionshistorie)

## 1. Voraussetzungen

- Symcon ab Version 8.2
- Für die I²C- und 1-Wire-Module: ein Raspberry Pi mit GeCoS-Server-Software, erreichbar über das Netzwerk (TCP-Port 8000) und per SSH
- Für WTH, WTHQ und WSens: der jeweilige Sensor im Netzwerk (JSON-Schnittstelle unter `http://<IP>/json`)

## 2. Installation

- **Module Store:** Nach „GeCoS“ suchen und installieren.
- **Alternativ über Module Control:** `https://github.com/bgersmann/GeCoS-Modules` hinzufügen.

## 3. Einrichtung

Die I²C- und 1-Wire-Module kommunizieren über eine gemeinsame Instanz **GeCoS IO V2** mit dem GeCoS-Server.

1. Eine Instanz **GeCoS IO V2** anlegen. Symcon legt dabei automatisch einen Client Socket als übergeordnete Instanz an.
2. Im IO die IP-Adresse des Raspberry Pi sowie Benutzer und Passwort für SSH eintragen und „Aktiv“ setzen.
3. Eine Instanz **GeCoS Konfigurator** (I²C) und/oder **GeCoS 1-Wire-Konfigurator** anlegen und mit dem IO verbinden.
4. Im Konfigurator werden die gefundenen Geräte aufgelistet und können per Klick angelegt werden.

Bei mehreren GeCoS-Servern wird je Server ein eigener IO mit eigenen Konfiguratoren angelegt. Ein Konfigurator zeigt nur Instanzen an, die mit seinem IO verbunden sind – auch solche, deren Gerät aktuell nicht gefunden wird.

WTH, WTHQ und WSens benötigen keinen IO und werden direkt angelegt.

## 4. Module

### GeCoS IO V2

Splitter für die Kommunikation mit dem GeCoS-Server. Die Verbindung läuft über einen Client Socket (Port 8000); Host, Port und Aktiv-Status werden aus der Konfiguration des IO übernommen.

| Eigenschaft | Beschreibung |
|---|---|
| Aktiv | Kommunikation ein-/ausschalten |
| IP | IP-Adresse oder Hostname des Raspberry Pi |
| User / Password | SSH-Zugangsdaten des Raspberry Pi (für Konfigurationsprüfung, Update, Neustart) |
| Nutzung / Baud / Connection String | Funktion und Parameter der seriellen Schnittstelle (DMX, ModBus, RS232) |

Die Liste „Konfiguration“ zeigt, ob I²C, serielle Schnittstelle und Shell-Zugriff auf dem Raspberry Pi korrekt eingerichtet sind.

| Variable | Beschreibung |
|---|---|
| RTC Temperatur | Temperatur der Echtzeituhr |
| RTC Zeitstempel | Uhrzeit der Echtzeituhr |
| Server Status | Verbindungsstatus zum GeCoS-Server |
| Letztes Keep Alive | Zeitpunkt der letzten Nachricht des Servers |

| Funktion | Beschreibung |
|---|---|
| `GeCoSIOV2_SetRTC_Data(int $InstanzID)` | Echtzeituhr auf die Symcon-Zeit setzen |
| `GeCoSIOV2_GetRTC_Data(int $InstanzID)` | Echtzeituhr auslesen |
| `GeCoSIOV2_GetUpdate(int $InstanzID)` | Software-Update des GeCoS-Servers ausführen |
| `GeCoSIOV2_ServerRestart(int $InstanzID)` | GeCoS-Server-Software neu starten |
| `GeCoSIOV2_RPiReboot(int $InstanzID)` | Raspberry Pi neu starten |
| `GeCoSIOV2_RPiShutdown(int $InstanzID)` | Raspberry Pi herunterfahren |

### GeCoS Konfigurator (I²C)

Listet die am GeCoS-Server gefundenen I²C-Module (16In, 16Out, PWM16Out, RGBW, 4AnalogIn) mit Bus und Adresse und legt die passenden Instanzen an. Keine weiteren Einstellungen.

### GeCoS 1-Wire-Konfigurator

Listet die am GeCoS-Server gefundenen 1-Wire-Sensoren (DS18S20, DS18B20, DS2413, DS2438) mit ihrer Sensor-ID und legt die passenden Instanzen an. Keine weiteren Einstellungen.

### Gemeinsame Eigenschaften der I²C-Module

| Eigenschaft | Beschreibung |
|---|---|
| Aktiv | Modul ein-/ausschalten |
| Device Adresse | I²C-Adresse des Moduls |
| GeCoS I²C-Bus | Bus 0, 1 oder 2 |

### GeCoS 16In

16 digitale Eingänge. Die Variablen „Eingang X0“ bis „Eingang X15“ zeigen den Zustand an; Änderungen meldet der Server selbstständig.

| Funktion | Beschreibung |
|---|---|
| `GeCoS16In_GetInput(int $InstanzID): bool` | Eingänge neu einlesen |

### GeCoS 16Out

16 digitale Ausgänge, schaltbar über die Variablen „Ausgang X0“ bis „Ausgang X15“.

| Eigenschaft | Beschreibung |
|---|---|
| Start-Status | Zustand der Ausgänge nach der Initialisierung: Status erhalten, alle aus, alle ein oder bestimmter Status |
| Startwert | Bitmaske (0–65535) für „bestimmter Status“ |

| Funktion | Beschreibung |
|---|---|
| `GeCoS16Out_SetOutputPin(int $InstanzID, int $Ausgang, bool $Wert): bool` | Einzelnen Ausgang (0–15) schalten |
| `GeCoS16Out_SetOutput(int $InstanzID, int $Bitmaske): bool` | Alle Ausgänge per Bitmaske setzen |
| `GeCoS16Out_GetOutputPin(int $InstanzID, int $Ausgang): bool` | Zustand eines Ausgangs lesen |
| `GeCoS16Out_GetOutput(int $InstanzID): bool` | Zustände aller Ausgänge neu einlesen |

### GeCoS PWM16Out

16 PWM-Ausgänge mit je einer Schalt- und einer Helligkeitsvariable (0–4095, Anzeige in %).

| Funktion | Beschreibung |
|---|---|
| `GeCoSPWM16Out_SetOutputPinStatus(int $InstanzID, int $Kanal, bool $Status)` | Kanal (0–15) ein-/ausschalten |
| `GeCoSPWM16Out_SetOutputPinValue(int $InstanzID, int $Kanal, int $Wert)` | Helligkeit (0–4095) setzen |

### GeCoS RGBW

4 RGBW-Gruppen (1–4) sowie eine Gruppe 5 für alle Kanäle gemeinsam. Je Gruppe gibt es Status RGB, Farbe, Intensität Rot/Grün/Blau, Status Weiß und Intensität Weiß (0–4095).

| Funktion | Beschreibung |
|---|---|
| `GeCoSRGBW_SetOutputPinStateRGBW(int $InstanzID, int $Gruppe, bool $Status)` | RGB und Weiß gemeinsam schalten |
| `GeCoSRGBW_SetOutputPinStateRGB(int $InstanzID, int $Gruppe, bool $Status)` | RGB schalten |
| `GeCoSRGBW_SetOutputPinStateW(int $InstanzID, int $Gruppe, bool $Status)` | Weiß schalten |
| `GeCoSRGBW_SetOutputPinValueR/G/B/W(int $InstanzID, int $Gruppe, int $Wert)` | Intensität eines Kanals (0–4095) setzen |
| `GeCoSRGBW_SetOutputColor(int $InstanzID, int $Gruppe, int $Farbe)` | Farbe als Hex-Wert setzen |
| `GeCoSRGBW_SetOutput(int $InstanzID, int $Gruppe, bool $StatusRGB, bool $StatusW, int $R, int $G, int $B, int $W)` | Alle Werte einer Gruppe setzen |

### GeCoS 4AnalogIn

4 analoge Eingänge (Spannung in V, Variablen „Eingang X0“ bis „Eingang X3“).

| Eigenschaft | Beschreibung |
|---|---|
| Sekunden | Messzyklus |
| Kanal 0–3: Aktiv | Kanal messen |
| Kanal 0–3: Auflösung | 12, 14, 16 oder 18 Bit |
| Kanal 0–3: Verstärkung | 1x, 2x, 4x oder 8x |

| Funktion | Beschreibung |
|---|---|
| `GeCoS4AnalogIn_GetInput(int $InstanzID)` | Messung aller aktiven Kanäle anstoßen |

### GeCoS DS18B20 / DS18S20

1-Wire-Temperatursensoren. Messbereich laut Datenblatt −55 °C bis +125 °C; Werte außerhalb werden verworfen. Der Fehlerwert −85 setzt die Instanz in den Fehlerzustand.

| Eigenschaft | Beschreibung |
|---|---|
| Aktiv | Sensor ein-/ausschalten |
| Sensor ID | 1-Wire-ID des Sensors (wird vom 1-Wire-Konfigurator gesetzt) |
| Präzision (nur DS18B20) | 9 bis 12 Bit |
| Offset | Korrekturwert in °C |
| Messzyklus | Abfrageintervall in Sekunden |

| Funktion | Beschreibung |
|---|---|
| `GeCoSDS18B20_Measurement(int $InstanzID)` bzw. `GeCoSDS18S20_Measurement(...)` | Messung sofort ausführen |

### GeCoS DS2413

2-Kanal-1-Wire-Schalter. Jeder Port kann als Eingang oder Ausgang betrieben werden. Ausgänge sind über die Variablen „Status (0)“ und „Status (1)“ schaltbar, Eingänge werden im Messzyklus gelesen.

| Eigenschaft | Beschreibung |
|---|---|
| Sensor ID | 1-Wire-ID des Bausteins |
| Port (0) / Port (1) | Digital Input oder Digital Output |
| Invert (0) / Invert (1) | Logik des Ports umkehren |
| Sekunden | Messzyklus für Eingänge |

| Funktion | Beschreibung |
|---|---|
| `GeCoSDS2413_SetPortStatus(int $InstanzID, int $Port, bool $Wert): bool` | Ausgang schalten |
| `GeCoSDS2413_Measurement(int $InstanzID)` | Zustand sofort lesen |

### GeCoS DS2438

1-Wire-Batteriemonitor. Aktuell wird die Temperatur (−55 °C bis +125 °C) ausgewertet; die Variablen VAD, VDD und XSENS sind vorbereitet.

| Eigenschaft | Beschreibung |
|---|---|
| Sensor ID | 1-Wire-ID des Bausteins |
| Offset | Korrekturwert in °C |
| Messzyklus | Abfrageintervall in Sekunden |

### GeCoS RPi

Systeminformationen des Raspberry Pi über den GeCoS IO: Board, Hardware, Seriennummer, Betriebssystem, Hostname, Uptime, CPU-/GPU-Temperatur, Spannung, Taktfrequenz, CPU-Auslastung, Arbeitsspeicher und SD-Karten-Belegung.

| Eigenschaft | Beschreibung |
|---|---|
| Aktiv | Abfrage ein-/ausschalten |
| Sekunden | Messzyklus |

| Funktion | Beschreibung |
|---|---|
| `GeCoSRPi_Measurement(int $InstanzID)` | Alle Werte sofort abfragen |
| `GeCoSRPi_SetDisplayPower(int $InstanzID, bool $Status)` | Angeschlossenes Display ein-/ausschalten |
| `GeCoSRPi_PiReboot(int $InstanzID)` | Raspberry Pi neu starten |
| `GeCoSRPi_PiShutdown(int $InstanzID)` | Raspberry Pi herunterfahren |

### GeCoS WTH / WTHQ / WSens

Netzwerk-Klimasensoren, die ihre Messwerte per HTTP/JSON bereitstellen.

- **WTH:** Temperatur, Luftfeuchtigkeit, Luftdruck, optional 1-Wire-Temperatur
- **WTHQ:** wie WTH, zusätzlich CO2 und TVOC
- **WSens:** wie WTH, zusätzlich Luftqualität (IAQ), CO2 und Lichtintensität (Weiß, Rot, Grün, Blau)

Aus den Messwerten werden Taupunkt, absolute Luftfeuchtigkeit und der relative Luftdruck (auf Meereshöhe) berechnet.

| Eigenschaft | Beschreibung |
|---|---|
| Aktiv | Abfrage ein-/ausschalten |
| IP | IP-Adresse oder Hostname des Sensors |
| Sekunden | Abfrageintervall (mindestens 5 Sekunden, 0 = aus) |
| Fehlversuche | Anzahl fehlgeschlagener Abfragen in Folge, bevor die Instanz auf Fehler geht und ein Eintrag ins Log geschrieben wird (Standard 3) |
| Höhe über NN | Für die Berechnung des relativen Luftdrucks |
| Temperatur / Luftfeuchtigkeit | Optionale externe Variablen für die Luftdruckberechnung |

**Luftdruck-Trends (1 h, 3 h, 12 h, 24 h):** Werden berechnet, sobald im Archiv das Logging für die Variable „Luftdruck (abs)“ aktiviert ist. Das Logging wird vom Modul nicht selbst eingeschaltet.

| Funktion | Beschreibung |
|---|---|
| `GeCoSWTH_RequestData(int $InstanzID): bool` (bzw. `GeCoSWTHQ_…`, `GeCoSWSens_…`) | Messwerte sofort abfragen |

## 5. Versionshistorie

### 3.0
- Umstellung auf `IPSModuleStrict`, Mindestversion Symcon 8.2
- Darstellungen statt Variablenprofilen
- WTH/WTHQ/WSens: robustere Fehlerbehandlung (Fehlerstatus erst nach mehreren Fehlversuchen, Prüfung der JSON-Antwort, HTTP-Timeout); Archiv-Logging wird nicht mehr vom Modul gesetzt
- 1-Wire: Messbereich laut Datenblatt, DS2413-Ausgänge schaltbar, DS2438 auf GeCoS IO V2 umgestellt
- Konfiguratoren zeigen nur Instanzen am eigenen IO
- Modul GeCoS_RegVar entfernt

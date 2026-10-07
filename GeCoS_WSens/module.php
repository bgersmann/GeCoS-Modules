<?
    // Klassendefinition
    class GeCoS_WSens extends IPSModuleStrict 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create(): void
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("IPAddress", "127.0.0.1");
		$this->RegisterPropertyInteger("Timer_1", 60);
		$this->RegisterPropertyInteger("MaxErrors", 3);
		$this->RegisterTimer("Timer_1", 0, 'GeCoSWSens_RequestData($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("Altitude", 0);
 	   	$this->RegisterPropertyBoolean("LoggingTemp", false);
 	    	$this->RegisterPropertyBoolean("LoggingHum", false);
 	    	$this->RegisterPropertyBoolean("LoggingPres", false);
		$this->RegisterPropertyBoolean("LoggingAirQuality", false);
		$this->RegisterPropertyBoolean("LoggingAmbient", false);
		$this->RegisterPropertyInteger("Temperature_ID", 0);
		$this->RegisterPropertyInteger("Humidity_ID", 0);
		
		//Status-Variablen anlegen
		$this->RegisterVariableFloat("Hardware", "Hardware-Version", $this->ValuePresentation("", 1, "microchip"), 10);
		
		$this->RegisterVariableFloat("Firmware", "Firmware-Version", $this->ValuePresentation("", 1, "microchip"), 20);
		
		$this->RegisterVariableFloat("Temperature", "Temperatur", $this->ValuePresentation(" °C", 1, "temperature-half", 1), 30);
		
		$this->RegisterVariableFloat("Pressure", "Luftdruck (abs)", $this->ValuePresentation(" hPa", 1, "gauge"), 40);
		
		$this->RegisterVariableFloat("PressureRel", "Luftdruck (rel)", $this->ValuePresentation(" hPa", 1, "gauge"), 50);
		
		$this->RegisterVariableFloat("HumidityAbs", "Luftfeuchtigkeit (abs)", $this->ValuePresentation(" g/m³", 1, "droplet"), 60);
		
		$this->RegisterVariableFloat("Humidity", "Luftfeuchtigkeit (rel)", $this->ValuePresentation(" %", 1, "droplet"), 70);
		
		$this->RegisterVariableFloat("DewPointTemperature", "Taupunkt Temperatur", $this->ValuePresentation(" °C", 1, "temperature-half", 1), 80);
		
		$this->RegisterVariableFloat("PressureTrend1h", "Luftdruck 1h-Trend", $this->ValuePresentation(" hPa", 1, "gauge"), 90);
		
		$this->RegisterVariableFloat("PressureTrend3h", "Luftdruck 3h-Trend", $this->ValuePresentation(" hPa", 1, "gauge"), 100);
		
		$this->RegisterVariableFloat("PressureTrend12h", "Luftdruck 12h-Trend", $this->ValuePresentation(" hPa", 1, "gauge"), 110);
		
		$this->RegisterVariableFloat("PressureTrend24h", "Luftdruck 24h-Trend", $this->ValuePresentation(" hPa", 1, "gauge"), 120);
		
		$this->RegisterVariableInteger("AirQuality", "Luftqualität", $this->AirQualityPresentation(), 130);
		$this->SetValue("AirQuality", 0);
		
		$this->RegisterVariableInteger("AirQualityIndex", "Luftqualität Index", $this->ValuePresentation("", 0, "wind"), 140);
		
		$this->RegisterVariableInteger("AirQualityGenauigkeit", "Luftqualität Genauigkeit", $this->ValuePresentation("", 0), 142);
		
		$this->RegisterVariableInteger("CO2", "CO 2", $this->ValuePresentation(" ppm", 0, "gauge"), 144);
		
		$this->RegisterVariableInteger("CO2Genauigkeit", "CO 2 Genauigkeit", $this->ValuePresentation("", 0), 146);
		
		$this->RegisterVariableInteger("Intensity_W", "Intensität Weiß", $this->ValuePresentation(" lx", 0, "lightbulb"), 150);
		
		$this->RegisterVariableInteger("Intensity_R", "Intensität Rot", $this->ValuePresentation(" lx", 0, "lightbulb"), 160);
		
		$this->RegisterVariableInteger("Intensity_G", "Intensität Grün", $this->ValuePresentation(" lx", 0, "lightbulb"), 170);
		
		$this->RegisterVariableInteger("Intensity_B", "Intensität Blau", $this->ValuePresentation(" lx", 0, "lightbulb"), 180);
		
		$this->RegisterVariableInteger("FailureCode", "Fehlercode", $this->ValuePresentation("", 0), 190);
		$this->SetValue("FailureCode", 0);
        }
 	
	public function GetConfigurationForm(): string
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device konnte nicht gefunden werden");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
		$arrayElements[] = array("type" => "Label", "label" => "IP oder Hostname");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IPAddress", "caption" => "IP");
		$arrayElements[] = array("type" => "Label", "label" => "Miniumum 5 Sekunden, 0 => Aus");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Timer_1", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "Anzahl Fehlversuche in Folge, bevor die Instanz auf Fehler geht");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "MaxErrors", "caption" => "Fehlversuche", "minimum" => 1, "maximum" => 100);
 		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Korrektur des Luftdrucks nach Hohenangabe");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Altitude", "caption" => "Höhe über NN (m)");
		$arrayElements[] = array("type" => "Label", "label" => "Optionale Angabe von externen Quellen");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "Temperature_ID", "caption" => "Temperatur");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "Humidity_ID", "caption" => "Luftfeuchtigkeit");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingTemp", "caption" => "Logging Temperatur aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingHum", "caption" => "Logging Luftfeuchtigkeit aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingPres", "caption" => "Logging Luftdruck aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingAirQuality", "caption" => "Logging Luftqualität aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingAmbient", "caption" => "Logging Weiß-Wert aktivieren");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "label" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/'");
	
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}           
	  
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges(): void
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
            			
		// Logging setzen
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Temperature"), $this->ReadPropertyBoolean("LoggingTemp"));
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Pressure"), $this->ReadPropertyBoolean("LoggingPres"));
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Humidity"), $this->ReadPropertyBoolean("LoggingHum"));
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("AirQuality"), $this->ReadPropertyBoolean("LoggingAirQuality"));
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Intensity_W"), $this->ReadPropertyBoolean("LoggingAmbient"));
		IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
		
		// Summary setzen
		$this->SetSummary($this->ReadPropertyString('IPAddress'));
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Timer_1 = $this->ReadPropertyInteger("Timer_1");
			If (($Timer_1 > 0) AND ($Timer_1 < 5)) {
				$Timer_1 = 5;
			}
			$this->SetTimerInterval("Timer_1", ($Timer_1 * 1000));
			$this->SetBuffer("ErrorCount", 0);
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
			$this->RequestData();
		}
		else {
			$this->SetTimerInterval("Timer_1", 0);
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
		}	
	}
	
	// Beginn der Funktionen
	public function RequestData(): bool
	{
		If ($this->ReadPropertyBoolean("Open") == true)  {
			// Datenermittlung über JSON
			$data = $this->FetchData(array("Temperatur", "Luftdruck", "Luftfeuchtigkeit", "Luftqualitaet-Index", "Hardware-Version", "Firmware-Version", "Intensitaet-Weiss", "Intensitaet-Rot", "Intensitaet-Gruen", "Intensitaet-Blau"));
			If ($data === false) {
				return false;
			}
			$Temp = floatval($data->Temperatur);
			$Pressure = floatval($data->Luftdruck); 
			$Humidity = floatval($data->Luftfeuchtigkeit); 
			$IAQ = floatval($data->{'Luftqualitaet-Index'}); 
			
			$Hardware = floatval($data->{'Hardware-Version'});
			$Firmware = floatval($data->{'Firmware-Version'});
			If ($this->GetValue("Hardware") <> $Hardware) {
				$this->SetValue("Hardware", ($Hardware));
				$this->SetSummary("HW-Version: ".$Hardware." SW-Version: ".$Firmware);
			}
			If ($this->GetValue("Firmware") <> $Firmware) {
				$this->SetValue("Firmware", ($Firmware));
				$this->SetSummary("HW-Version: ".$Hardware." SW-Version: ".$Firmware);
			}
			if (isset($data->{'BME-Error'})) {
				$FailureCode = intval($data->{'BME-Error'});
			}
			else {
				$FailureCode = 0;
			}
			If ($this->GetValue("FailureCode") <> $FailureCode) {
				$this->SetValue("FailureCode", ($FailureCode));
				
			}
			
			If (property_exists($data, "Luftqualitaet-Genauigkeit")) {
				$this->SetValue("AirQualityGenauigkeit", intval($data->{'Luftqualitaet-Genauigkeit'}));
        		}  
			
			If (property_exists($data, "CO2")) {
				$this->SetValue("CO2", intval($data->CO2));
        		}  
			
			If (property_exists($data, "CO2-Genauigkeit")) {
				$this->SetValue("CO2Genauigkeit", intval($data->{'CO2-Genauigkeit'}));
        		}  
			
			$Ambient = intval($data->{'Intensitaet-Weiss'});
    			$Red = intval($data->{'Intensitaet-Rot'});
			$Green = intval($data->{'Intensitaet-Gruen'});
			$Blue = intval($data->{'Intensitaet-Blau'});
			
			$this->SendDebug("RequestData", "BME680 - iAQ: ".$IAQ." Temp: ".$Temp." C Luftfeuchte: ".$Humidity."% Luftdruck: ".$Pressure." hPa", 0);		
			$this->SendDebug("RequestData", "APDS9960 - Weiss: ".$Ambient." lx Rot: ".$Red." lx Gruen: ".$Green." lx Blau: ".$Blue." lx", 0);
			
			$this->SetValue("Intensity_W", $Ambient);
			$this->SetValue("Intensity_R", $Red);
			$this->SetValue("Intensity_G", $Green);
			$this->SetValue("Intensity_B", $Blue);
			
			$this->SetValue("Temperature", round($Temp, 2));
			
			If (($Pressure > 800) AND ($Pressure < 1200)) {
				$this->SetValue("Pressure", round($Pressure, 2));
			}
			
			$this->SetValue("Humidity", round($Humidity, 2));
			$this->SetValue("AirQualityIndex", $IAQ);
			
			
			
			// Berechnung von Taupunkt und absoluter Luftfeuchtigkeit
			if ($Temp < 0) {
				$a = 7.6; 
				$b = 240.7;
			}  
			elseif ($Temp >= 0) {
				$a = 7.5;
				$b = 237.3;
			}
			$sdd = 6.1078 * pow(10.0, (($a * $Temp) / ($b + $Temp)));
			$dd = $Humidity / 100 * $sdd;
			$v = log10($dd/6.1078);
			$td = $b * $v / ($a - $v);
			$af = pow(10,5) * 18.016 / 8314.3 * $dd / ($Temp + 273.15);
			// Taupunkttemperatur
			$this->SetValue("DewPointTemperature", round($td, 2));
			// Absolute Feuchtigkeit
			$this->SetValue("HumidityAbs", round($af, 2));
			
			// Relativen Luftdruck
			$Altitude = $this->ReadPropertyInteger("Altitude");
			If ($this->ReadPropertyInteger("Temperature_ID") > 0) {
				// Wert der Variablen zur Berechnung nutzen
				$VaribleID = $this->ReadPropertyInteger("Temperature_ID");
				$VariableType = IPS_GetVariable($VaribleID)['VariableType'];
				If ($VariableType == 1) {
					$Temperature = GetValueInteger($VaribleID);
				}
				elseif ($VariableType == 2) {
					$Temperature = GetValueFloat($VaribleID);
				}
				else {
					// Wert dieses BME680 verwenden
					$Temperature = $Temp;
				}
			}
			else {
				// Wert dieses BME680 verwenden
				$Temperature = $Temp;
			}
					
			If ($this->ReadPropertyInteger("Humidity_ID") > 0) {
				// Wert der Variablen zur Berechnung nutzen
				$VaribleID = $this->ReadPropertyInteger("Humidity_ID");
				$VariableType = IPS_GetVariable($VaribleID)['VariableType'];
				If ($VariableType == 1) {
					$Humidity = GetValueInteger($VaribleID);
				}
				elseif ($VariableType == 2) {
					$Humidity = GetValueFloat($VaribleID);
				}
			}
			
			$g_n = 9.80665; // Erdbeschleunigung (m/s^2)
			$gam = 0.0065; // Temperaturabnahme in K pro geopotentiellen Metern (K/gpm)
			$R = 287.06; // Gaskonstante für trockene Luft (R = R_0 / M)
			$M = 0.0289644; // Molare Masse trockener Luft (J/kgK)
			$R_0 = 8.314472; // allgemeine Gaskonstante (J/molK)
			$T_0 = 273.15; // Umrechnung von °C in K
			$C = 0.11; // DWD-Beiwert für die Berücksichtigung der Luftfeuchte
			$E_0 = 6.11213; // (hPa)
			$f_rel = $Humidity / 100; // relative Luftfeuchte (0-1.0)
			// momentaner Stationsdampfdruck (hPa)
			$e_d = $f_rel * $E_0 * exp((17.5043 * $Temperature) / (241.2 + $Temperature));
			$PressureRel = $Pressure * exp(($g_n * $Altitude) / ($R * ($Temperature + $T_0 + $C * $e_d + (($gam * $Altitude) / 2))));
			$this->SetValue("PressureRel", round($PressureRel, 2));
			// Luftdruck Trends
			If ($this->ReadPropertyBoolean("LoggingPres") == true) {
				$this->SetValue("PressureTrend1h", $this->PressureTrend(1));
				$this->SetValue("PressureTrend3h", $this->PressureTrend(3));
				$this->SetValue("PressureTrend12h", $this->PressureTrend(12));
				$this->SetValue("PressureTrend24h", $this->PressureTrend(24));
			}
			
			// Umrechnung für die Air-Qualität-Anzeige
			$air_quality_score = max(0, min(500, $IAQ));
			$IAQ_Index = array(50, 100, 150, 200, 300, 500);
			$i = 0;
			while ($air_quality_score > $IAQ_Index[$i]) {
			    $i++;  
			}
			$this->SetValue("AirQuality", ($i + 1));
			return true;
		}
		else {
			return false;
		}
	}
	    
	private function FetchData(array $RequiredKeys)
	{
		// Datenermittlung über JSON, liefert bei Fehler false
		$IP = $this->ReadPropertyString("IPAddress");
		$Context = stream_context_create(array("http" => array("timeout" => 5)));
		$contents = @file_get_contents('http://'.$IP.'/json', false, $Context);
		If ($contents === false) {
			$this->HandleFailure("Keine Antwort von ".$IP);
			return false;
		}
		If (!mb_check_encoding($contents, "UTF-8")) {
			$contents = mb_convert_encoding($contents, "UTF-8", "ISO-8859-1");
		}
		$data = json_decode($contents);
		If (!is_object($data)) {
			$this->HandleFailure("Ungültige JSON-Daten von ".$IP." (".json_last_error_msg().")");
			return false;
		}
		foreach ($RequiredKeys as $Key) {
			If (!property_exists($data, $Key)) {
				$this->HandleFailure("Unvollständige Daten von ".$IP." (fehlt: ".$Key.")");
				return false;
			}
		}

		// Erfolgreich - Fehlerzähler zurücksetzen
		$ErrorCount = intval($this->GetBuffer("ErrorCount"));
		If ($ErrorCount >= $this->GetMaxErrors()) {
			$this->LogMessage("Verbindung zu ".$IP." wiederhergestellt (nach ".$ErrorCount." Fehlversuchen)", KL_MESSAGE);
		}
		$this->SetBuffer("ErrorCount", 0);
		If ($this->GetStatus() <> 102) {
			$this->SetStatus(102);
		}
		return $data;
	}

	private function HandleFailure(string $Message)
	{
		// Erst nach mehreren Fehlversuchen in Folge auf Fehler gehen und ins Log schreiben
		$ErrorCount = intval($this->GetBuffer("ErrorCount")) + 1;
		$this->SetBuffer("ErrorCount", $ErrorCount);
		$MaxErrors = $this->GetMaxErrors();
		$this->SendDebug("RequestData", $Message." (Fehlversuch ".$ErrorCount."/".$MaxErrors.")", 0);
		If ($ErrorCount == $MaxErrors) {
			$this->LogMessage($Message." - ".$ErrorCount." Fehlversuche in Folge", KL_ERROR);
		}
		If (($ErrorCount >= $MaxErrors) AND ($this->GetStatus() <> 202)) {
			$this->SetStatus(202);
		}
	}

	private function GetMaxErrors()
	{
		return max(1, $this->ReadPropertyInteger("MaxErrors"));
	}

	private function PressureTrend(int $interval)
	{
		$Result = 0;
		$LoggingArray = AC_GetLoggedValues(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Pressure"), time()- (3600 * $interval), time(), 0); 
		$Result = @($LoggingArray[0]['Value'] - end($LoggingArray)['Value']); 
	return $Result;
	}   
	 
	private function ValuePresentation(string $Suffix, int $Digits, string $Icon = "", int $UsageType = 0)
	{
		return array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "SUFFIX" => $Suffix, "DIGITS" => $Digits, "ICON" => $Icon, "USAGE_TYPE" => $UsageType);
	}

	private function AirQualityPresentation()
	{
		// Wertanzeige mit Text und Farbe je Luftqualitätsstufe
		$Levels = array(
			0 => array("Kalibrierung", -1),
			1 => array("gut", 0x58FA58),
			2 => array("durchschnittlich", 0xF7FE2E),
			3 => array("etwas schlecht", 0xFE9A2E),
			4 => array("schlecht", 0xFF0000),
			5 => array("schlechter", 0x61380B),
			6 => array("sehr schlecht", 0x000000)
		);
		$Intervals = array();
		foreach ($Levels as $Value => $Level) {
			$Intervals[] = array("IntervalMinValue" => $Value, "IntervalMaxValue" => $Value, "ConstantActive" => true, "ConstantValue" => $Level[0], "ColorActive" => ($Level[1] >= 0), "ColorValue" => $Level[1]);
		}
		return array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "ICON" => "wind", "DIGITS" => 0, "INTERVALS_ACTIVE" => true, "INTERVALS" => json_encode($Intervals));
	}
}
?>

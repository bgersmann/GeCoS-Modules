<?php
    // Klassendefinition
    class GeCoS_WTHQ extends IPSModuleStrict 
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
		$this->RegisterTimer("Timer_1", 0, 'GeCoSWTHQ_RequestData($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("Altitude", 0);
		$this->RegisterPropertyFloat("TempOffset", 0);
		$this->RegisterPropertyFloat("IntensityOffset", 30);
		$this->RegisterPropertyInteger("Temperature_ID", 0);
		$this->RegisterPropertyInteger("Humidity_ID", 0);
		
		//Status-Variablen anlegen
		$this->RegisterVariableFloat("Hardware", $this->Translate("Hardware version"), $this->ValuePresentation("", 1, "microchip"), 10);
		$this->DisableAction("Hardware");
		
		$this->RegisterVariableFloat("Firmware", $this->Translate("Firmware version"), $this->ValuePresentation("", 1, "microchip"), 20);
		$this->DisableAction("Firmware");
		
		$this->RegisterVariableFloat("Temperature", $this->Translate("Temperature"), $this->ValuePresentation(" °C", 1, "temperature-half", 1), 30);
		$this->DisableAction("Temperature");
		
		$this->RegisterVariableFloat("Pressure", $this->Translate("Air pressure (abs)"), $this->ValuePresentation(" hPa", 1, "gauge"), 40);
		$this->DisableAction("Pressure");
		
		$this->RegisterVariableFloat("PressureRel", $this->Translate("Air pressure (rel)"), $this->ValuePresentation(" hPa", 1, "gauge"), 50);
		$this->DisableAction("PressureRel");
		
		$this->RegisterVariableFloat("HumidityAbs", $this->Translate("Humidity (abs)"), $this->ValuePresentation(" g/m³", 1, "droplet"), 60);
		$this->DisableAction("HumidityAbs");
		
		$this->RegisterVariableFloat("Humidity", $this->Translate("Humidity (rel)"), $this->ValuePresentation(" %", 1, "droplet"), 70);
		$this->DisableAction("Humidity");
		
		$this->RegisterVariableFloat("DewPointTemperature", $this->Translate("Dew point temperature"), $this->ValuePresentation(" °C", 1, "temperature-half", 1), 80);
		$this->DisableAction("DewPointTemperature");
		
		$this->RegisterVariableFloat("PressureTrend1h", $this->Translate("Air pressure 1h trend"), $this->ValuePresentation(" hPa", 1, "gauge"), 90);
		$this->DisableAction("PressureTrend1h");
		
		$this->RegisterVariableFloat("PressureTrend3h", $this->Translate("Air pressure 3h trend"), $this->ValuePresentation(" hPa", 1, "gauge"), 100);
		$this->DisableAction("PressureTrend3h");
		
		$this->RegisterVariableFloat("PressureTrend12h", $this->Translate("Air pressure 12h trend"), $this->ValuePresentation(" hPa", 1, "gauge"), 110);
		$this->DisableAction("PressureTrend12h");
		
		$this->RegisterVariableFloat("PressureTrend24h", $this->Translate("Air pressure 24h trend"), $this->ValuePresentation(" hPa", 1, "gauge"), 120);
		$this->DisableAction("PressureTrend24h");
    
    		$this->RegisterVariableInteger("CO2", $this->Translate("CO2"), $this->ValuePresentation(" ppm", 0, "gauge"), 130);
		$this->DisableAction("CO2");
		
		$this->RegisterVariableInteger("TVOC", $this->Translate("TVOC"), $this->ValuePresentation(" ppb", 0, "gauge"), 140);
		$this->DisableAction("TVOC");
        }
 	
	public function GetConfigurationForm(): string
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instance is being created"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instance is active");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instance is inactive");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instance is faulty");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device could not be found");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Communication error!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Active"); 
		$arrayElements[] = array("type" => "Label", "label" => "IP or hostname");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IPAddress", "caption" => "IP");
		$arrayElements[] = array("type" => "Label", "label" => "Minimum 5 seconds, 0 => off");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Timer_1", "caption" => "Seconds");
		$arrayElements[] = array("type" => "Label", "label" => "Number of consecutive failed attempts before the instance reports an error");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "MaxErrors", "caption" => "Failed attempts", "minimum" => 1, "maximum" => 100);
 		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Air pressure correction based on altitude");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Altitude", "caption" => "Altitude above sea level (m)");
		$arrayElements[] = array("type" => "Label", "label" => "Optional external sources");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "Temperature_ID", "caption" => "Temperature");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "Humidity_ID", "caption" => "Humidity");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "The air pressure trends are calculated as soon as logging is enabled in the archive for the variable \"Air pressure (abs)\".");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "label" => "Manufacturer information", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/'");
	
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "These functions are only available after the required data has been entered and applied!");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}           
	  
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges(): void
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
            			
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
			$data = $this->FetchData(array("Temperatur", "Luftdruck", "Luftfeuchtigkeit", "CO2", "TVOC", "Hardware-Version", "Firmware-Version"));
			If ($data === false) {
				return false;
			}
			$Temp = floatval($data->Temperatur);
			$Pressure = floatval($data->Luftdruck); 
			$Humidity = floatval($data->Luftfeuchtigkeit); 
			$CO2 = intval($data->CO2);
			$TVOC = intval($data->TVOC);
			
			If ($this->GetValue("CO2") <> $CO2) {
				$this->SetValue("CO2", ($CO2));
			}
			If ($this->GetValue("TVOC") <> $TVOC) {
				$this->SetValue("TVOC", ($TVOC));
			}
			
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
			
			
			$this->SendDebug("RequestData", "BME280 - Temp: ".$Temp." C Luftfeuchte: ".$Humidity."% Luftdruck: ".$Pressure." hPa", 0);		
			
			$this->SetValue("Temperature", round($Temp, 2));
			
			If (($Pressure > 800) AND ($Pressure < 1200)) {
				$this->SetValue("Pressure", round($Pressure, 2));
			}
			
			$this->SetValue("Humidity", round($Humidity, 2));
			
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
			If ($this->IsPressureLogged()) {
				$this->SetValue("PressureTrend1h", $this->PressureTrend(1));
				$this->SetValue("PressureTrend3h", $this->PressureTrend(3));
				$this->SetValue("PressureTrend12h", $this->PressureTrend(12));
				$this->SetValue("PressureTrend24h", $this->PressureTrend(24));
			}
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
			$this->HandleFailure(sprintf($this->Translate("No response from %s"), $IP));
			return false;
		}
		If (!mb_check_encoding($contents, "UTF-8")) {
			$contents = mb_convert_encoding($contents, "UTF-8", "ISO-8859-1");
		}
		$data = json_decode($contents);
		If (!is_object($data)) {
			$this->HandleFailure(sprintf($this->Translate("Invalid JSON data from %s (%s)"), $IP, json_last_error_msg()));
			return false;
		}
		foreach ($RequiredKeys as $Key) {
			If (!property_exists($data, $Key)) {
				$this->HandleFailure(sprintf($this->Translate("Incomplete data from %s (missing: %s)"), $IP, $Key));
				return false;
			}
		}

		// Erfolgreich - Fehlerzähler zurücksetzen
		$ErrorCount = intval($this->GetBuffer("ErrorCount"));
		If ($ErrorCount >= $this->GetMaxErrors()) {
			$this->LogMessage(sprintf($this->Translate("Connection to %s restored (after %d failed attempts)"), $IP, $ErrorCount), KL_MESSAGE);
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
			$this->LogMessage(sprintf($this->Translate("%s - %d consecutive failed attempts"), $Message, $ErrorCount), KL_ERROR);
		}
		If (($ErrorCount >= $MaxErrors) AND ($this->GetStatus() <> 202)) {
			$this->SetStatus(202);
		}
	}

	private function GetMaxErrors()
	{
		return max(1, $this->ReadPropertyInteger("MaxErrors"));
	}

	private function IsPressureLogged()
	{
		// Die Trends benötigen das Archiv-Logging des Luftdrucks, das der Benutzer selbst aktiviert
		$ArchiveID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];
		return AC_GetLoggingStatus($ArchiveID, $this->GetIDForIdent("Pressure"));
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
}
?>

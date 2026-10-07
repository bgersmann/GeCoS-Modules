<?php
    // Klassendefinition
    class GeCoS_DS2413 extends IPSModuleStrict 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create(): void
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("DeviceAddress", "Sensor ID");
		$this->RegisterPropertyInteger("DeviceFunction_0", 1);
		$this->RegisterPropertyInteger("DeviceFunction_1", 1);
		$this->RegisterPropertyBoolean("Invert_0", false);
		$this->RegisterPropertyBoolean("Invert_1", false);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'GeCoSDS2413_Measurement($_IPS["TARGET"]);');
		
		//Status-Variablen anlegen
		$this->RegisterPortVariables();
        }
 	
	public function GetConfigurationForm(): string
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device konnte nicht gefunden werden");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "DeviceAddress", "caption" => "Sensor ID");
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Digital Input", "value" => 1);
		$arrayOptions[] = array("label" => "Digital Output", "value" => 0);
		
		If ($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl") {
			$arrayElements[] = array("type" => "Select", "name" => "DeviceFunction_0", "caption" => "Port (0)", "options" => $arrayOptions );
			$arrayElements[] = array("name" => "Invert_0", "type" => "CheckBox",  "caption" => "Invert (0)");
			$arrayElements[] = array("type" => "Select", "name" => "DeviceFunction_1", "caption" => "Port (1)", "options" => $arrayOptions );
			$arrayElements[] = array("name" => "Invert_1", "type" => "CheckBox",  "caption" => "Invert (1)");
		}
		
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "caption" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/';");
	
		$arrayActions = array();
		If (($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl") AND ($this->ReadPropertyBoolean("Open") == true)) {
			$arrayActions[] = array("type" => "Button", "label" => "An (0)", "onClick" => 'GeCoSDS2413_SetPortStatus($id, 0, true);');
			$arrayActions[] = array("type" => "Button", "label" => "Aus (0)", "onClick" => 'GeCoSDS2413_SetPortStatus($id, 0, false);');
			$arrayActions[] = array("type" => "Button", "label" => "An (1)", "onClick" => 'GeCoSDS2413_SetPortStatus($id, 1, true);');
			$arrayActions[] = array("type" => "Button", "label" => "Aus (1)", "onClick" => 'GeCoSDS2413_SetPortStatus($id, 1, false);');
		}
		else {
			$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		}
	
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}           
	  
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges(): void
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Summary setzen
		$this->SetSummary("SC: ".$this->ReadPropertyString("DeviceAddress"));

		// Darstellung und Bedienbarkeit an die gewählte Port-Funktion anpassen
		$this->RegisterPortVariables();

		$OWDeviceArray = Array();
		$this->SetBuffer("OWDeviceArray", serialize($OWDeviceArray));
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {			
			If ($this->ReadPropertyBoolean("Open") == true) {	
				//ReceiveData-Filter setzen
				$Filter = '(.*"Function":"set_start_trigger".*|.*"InstanceID":'.$this->InstanceID.'.*)';
				//$this->SetReceiveDataFilter($Filter);
				
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_OWDevices", "DeviceSerial" => $this->ReadPropertyString("DeviceAddress"), "InstanceID" => $this->InstanceID)));		
				If ($Result == true) {
					If (($this->ReadPropertyInteger("DeviceFunction_0") == 1) OR ($this->ReadPropertyInteger("DeviceFunction_1") == 1)) {
						$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					}
					else {
						$this->SetTimerInterval("Messzyklus", 0);
					}
					$this->Setup();
					$this->Measurement();
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
					$this->SendDebug("ApplyChanges", $this->ReadPropertyString("DeviceAddress"), 0);
				}
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				If ($this->GetStatus() <> 104) {
					$this->SetStatus(104);
				}
			}	
		}
		else {
			$this->SendDebug("ApplyChanges", "Startrestriktionen nicht erfuellt!", 0);
		}	
	}
	
	public function ReceiveData(string $JSONString): string
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "status":
			   	If ($data->InstanceID == $this->InstanceID) {
				   	If ($this->ReadPropertyBoolean("Open") == true) {				
						$this->SendDebug("ReceiveData", "Statusänderung: ".$data->Status, 0);
						$this->SetStatus($data->Status);
					}
					else {
						If ($this->GetStatus() <> 104) {
							$this->SetStatus(104);
						}
					}	
			   	}
			   	break;
			case "set_start_trigger":
			   	$this->ApplyChanges();
				break;
			case "OWV":
			   	If ($data->DeviceAddress == $this->ReadPropertyString("DeviceAddress")) {
					// Bit 0 = IOA (Port 0), Bit 1 = IOB (Port 1)
					$State = intval($data->Value);
					for ($Port = 0; $Port <= 1; $Port++) {
						$Value = (bool)((($State >> $Port) & 1) ^ $this->ReadPropertyBoolean("Invert_".$Port));
						If ($this->GetValue("Status_".$Port) <> $Value) {
							$this->SetValue("Status_".$Port, $Value);
						}
					}
			   		If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
				}
			   	break;	
	 	}
		return "";
	}
	 
	public function RequestAction(string $Ident, mixed $Value): void
	{
		$Port = intval(substr($Ident, 7, 2));
		$this->SetPortStatus($Port, $Value);
	}
	    
	// Beginn der Funktionen
	private function Setup()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl")) {
			// Eingänge High (freigegeben), Ausgänge mit dem aktuellen Zustand der Variablen
			$Config = 0;
			for ($i = 0; $i <= 1; $i++) {
				If ($this->ReadPropertyInteger("DeviceFunction_".$i) <> 0) {
					$Bit = 1;
				}
				else {
					$Bit = (int)((bool)$this->GetValue("Status_".$i) ^ $this->ReadPropertyBoolean("Invert_".$i));
				}
				$Config |= ($Bit << $i);
			}
			$this->SendDebug("Setup", "Wert: ".$Config, 0);
			
			/* Value: 
			0-> IOA+IOB = LOW, 
			1-> IOA = HIGH;IOB = LOW, 
			2-> IOA = LOW; IOB = High, 
			3-> IOA+IOB = High
			{OWC;28-610119138fdf1b;3} -> {OWC;28-610119138fdf1b;3;OK}
			*/
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "OWC", "InstanceID" => $this->InstanceID, "DeviceAddress" => $this->ReadPropertyString("DeviceAddress"), "Configuration" => $Config )));
		}
	}
	    
	public function Measurement(): void
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl")) {
			// Messung ausführen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "OWV", "InstanceID" => $this->InstanceID, "DeviceAddress" => $this->ReadPropertyString("DeviceAddress") )));
		}
	}
	
	public function SetPortStatus(int $Port, bool $Value): bool
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyString("DeviceAddress") <> "")) {
			$this->SendDebug("SetPortStatus", "Port: ".(int)$Port." Value: ".(int)$Value, 0);
			// Eingabeparameter filtern
			$Port = min(1, max(0, $Port));
			If ($this->ReadPropertyInteger("DeviceFunction_".$Port) <> 0) {
				$this->SendDebug("SetPortStatus", "Port ".$Port." ist als Eingang konfiguriert!", 0);
				return false;
			}
			// Zielzustand beider Ports ermitteln, Eingänge bleiben High (freigegeben)
			$arrayValues = array();
			for ($i = 0; $i <= 1; $i++) {
				If ($this->ReadPropertyInteger("DeviceFunction_".$i) <> 0) {
					$arrayValues[$i] = 1;
				}
				else {
					$PortValue = ($i == $Port) ? $Value : $this->GetValue("Status_".$i);
					$arrayValues[$i] = (int)((bool)$PortValue ^ $this->ReadPropertyBoolean("Invert_".$i));
				}
			}
			/* Value:
			0-> IOA+IOB = LOW,
			1-> IOA = HIGH;IOB = LOW,
			2-> IOA = LOW; IOB = High,
			3-> IOA+IOB = High
			*/
			$Config = ($arrayValues[1] << 1) | $arrayValues[0];
			$this->SendDebug("SetPortStatus", "Port[0]: ".$arrayValues[0]." Port[1]: ".$arrayValues[1]." Wert: ".$Config, 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "OWC", "InstanceID" => $this->InstanceID, "DeviceAddress" => $this->ReadPropertyString("DeviceAddress"), "Configuration" => $Config )));
			If ($Result) {
				$this->SetValue("Status_".$Port, $Value);
			}
			return (bool)$Result;
		}
		return false;
	}

	private function RegisterPortVariables()
	{
		for ($Port = 0; $Port <= 1; $Port++) {
			$Ident = "Status_".$Port;
			If ($this->ReadPropertyInteger("DeviceFunction_".$Port) == 0) {
				// Ausgang: schaltbar
				$this->RegisterVariableBoolean($Ident, "Status (".$Port.")", array("PRESENTATION" => VARIABLE_PRESENTATION_SWITCH), ($Port + 1) * 10);
				$this->EnableAction($Ident);
			}
			else {
				// Eingang: nur Anzeige
				$this->RegisterVariableBoolean($Ident, "Status (".$Port.")", array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION), ($Port + 1) * 10);
				$this->DisableAction($Ident);
			}
		}
	}

}
?>

<?php
    // Klassendefinition
    class GeCoS_16Out extends IPSModuleStrict 
    {
	// PCA9655E
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create(): void
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
 	    	$this->RegisterPropertyInteger("DeviceAddress", 36);
		$this->RegisterPropertyInteger("DeviceBus", 0);
		$this->RegisterPropertyInteger("StartOption", -1);
		$this->RegisterPropertyInteger("StartValue", 0);
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterVariableBoolean("Output_X".$i, sprintf($this->Translate("Ausgang X%d"), $i), array("PRESENTATION" => VARIABLE_PRESENTATION_SWITCH), ($i + 1) * 10);
			$this->EnableAction("Output_X".$i);	
		}
        }
 	
	public function GetConfigurationForm(): string
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device konnte nicht gefunden werden");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "I²C-Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		for ($i = 36; $i <= 39; $i++) {
		    	$arrayOptions[] = array("label" => $i." / 0x".strtoupper(dechex($i))." - V2.x", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 0", "value" => 0);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 1", "value" => 1);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 2", "value" => 2);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "GeCoS I²C-Bus", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Ausgänge nach der Initialisierung setzen");

		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Status erhalten", "value" => -1);
		$arrayOptions[] = array("label" => "alle Ausgänge aus", "value" => 0);
		$arrayOptions[] = array("label" => "alle Ausgänge ein", "value" => 65535);
		$arrayOptions[] = array("label" => "bestimmter Status", "value" => -2);
		$arrayElements[] = array("type" => "Select", "name" => "StartOption", "caption" => "Start-Status", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "StartValue", "caption" => "Startwert");	
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
	
		$arrayElements[] = array("type" => "Button", "caption" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/';");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Test Center"); 
		$arrayElements[] = array("type" => "TestCenter", "name" => "TestCenter");
			
		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}           
	  
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges(): void
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Summary setzen
		$this->SetSummary("0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." - I²C-Bus ".($this->ReadPropertyInteger("DeviceBus")));

		$this->SetBuffer("OutputBank", 0);

		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				//ReceiveData-Filter setzen
				$Filter = '((.*"Function":"get_used_modules".*|.*"InstanceID":'.$this->InstanceID.'.*)|.*"Function":"status".*)';
				$this->SetReceiveDataFilter($Filter);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_modules", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->GetOutput();
				}
			}
			else {
				If ($this->GetStatus() <> 104) {
					$this->SetStatus(104);
				}
			}	
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
		}
	}
	
	public function ReceiveData(string $JSONString): string
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "SAO":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$Value = intval($data->Value);
					$this->SendDebug("ReceiveData", "SAO Value: ".$Value, 0);
					$this->SetBuffer("OutputBank", $Value);
					// Statusvariablen setzen
					for ($i = 0; $i <= 15; $i++) {
						$Bitvalue = boolval($Value & pow(2, $i));					
						If ($this->GetValue("Output_X".$i) <> $Bitvalue) {
							$this->SetValue("Output_X".$i, $Bitvalue);
						}
					}
				}
				break; 
			case "SOM":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$Value = intval($data->Value);
					$this->SendDebug("ReceiveData", "SOM Value: ".$Value, 0);
					/*
					$this->SetBuffer("OutputBank", $Value);
					// Statusvariablen setzen
					for ($i = 0; $i <= 15; $i++) {
						$Bitvalue = boolval($Value & pow(2, $i));					
						If ($this->GetValue("Output_X".$i) <> $Bitvalue) {
							$this->SetValue("Output_X".$i, $Bitvalue);
						}
					}
					*/
				}
				break; 
			case "get_used_modules":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->ApplyChanges();
				}
				break;
			case "status":
			   	If ($data->InstanceID == $this->InstanceID) {
				   	If ($this->ReadPropertyBoolean("Open") == true) {				
						$this->SetStatus($data->Status);
					}
					else {
						If ($this->GetStatus() <> 104) {
							$this->SetStatus(104);
						}
					}	
			   	}
			   	break;
	 	}
		return "";
	}
	
	public function RequestAction(string $Ident, mixed $Value): void
	{
		$Number = intval(substr($Ident, 8, 2));
		$this->SetOutputPin($Number, $Value);
	}
	    
	// Beginn der Funktionen
	public function SetOutputPin(int $Output, bool $Value): bool
	{
		$Output = min(15, max(0, $Output));
		$Value = min(1, max(0, $Value));
		$Result = false;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetOutputPin", "Value: ".$Value, 0);
			
			If (IPS_SemaphoreEnter("SetOutputPin", 2000)) {
				$Bitmask = $this->GetBuffer("OutputBank");
				If ($Value == true) {
					$Bitmask = $this->setBit($Bitmask, $Output);
				}
				else {
					$Bitmask = $this->unsetBit($Bitmask, $Output);
				}
				$this->SetBuffer("OutputBank", $Bitmask);

				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "SOM", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "Value" => $Bitmask )));
				$this->SendDebug("SetOutputPin", "Result: ".$Result, 0);
				If ($Result == true) {
					$this->SetValue("Output_X".$Output, $Value);
					$this->SetBuffer("OutputBank", $Bitmask);
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
				}
				else {
					If ($this->GetStatus() <> 202) {
						$this->SetStatus(202);
					}
				}
				IPS_SemaphoreLeave("SetOutputPin");
			}
			else {
				$this->SendDebug("SetOutputPin", "Keine Ausfuehrung moeglich!", 0);
			}
		}
	return ($Result == true);
	}	
	
	public function GetOutput(): bool
	{
		$Result = false;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetOutput", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "SAO")));
			If ($Result == true) {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
			}
			else {
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
		}
	return ($Result == true);
	}
	
	public function GetOutputPin(int $Output): bool
	{
		$Output = min(15, max(0, $Output));
		$Result = false;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetOutput", "Ausfuehrung", 0);
			$Result = $this->GetOutput();
			If ($Result == true) {
				$OutputBank  = $this->GetBuffer("OutputBank");
				$Result = boolval($OutputBank & pow(2, $Output));
			}
		}
		
	return ($Result == true);
	}    
	    
	public function SetOutput(int $Value): bool
	{
		$Value = min(65535, max(0, $Value));
		$Result = false;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetOutputBank", "Value: ".$Value, 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "SOM", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "Value" => $Value )));
			
			If ($Result == true) {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				for ($i = 0; $i <= 15; $i++) {
					$Bitvalue = boolval($Value & pow(2, $i));					
					If ($this->GetValue("Output_X".$i) <> $Bitvalue) {
						$this->SetValue("Output_X".$i, $Bitvalue);
					}
				}
				$this->GetOutput();
			}
			else {
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
		}
	return ($Result == true);
	}    
	
	private function setBit($byte, $significance) { 
 		// ein bestimmtes Bit auf 1 setzen
 		return $byte | 1<<$significance;   
 	} 
	
	private function unsetBit($byte, $significance) {
	    // ein bestimmtes Bit auf 0 setzen
	    return $byte & ~(1<<$significance);
	}
	    
}
?>

<?
    // Klassendefinition
    class GeCoS_DS18S20 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F1C0403-4A74-4F14-829F-9A217CFB2D05}");
		$this->RegisterPropertyString("DeviceAddress", "Sensor ID");
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterPropertyFloat("Offset", 0);
		$this->RegisterTimer("Messzyklus", 0, 'GeCoSDS18S20_Measurement($_IPS["TARGET"]);');
		
		//Status-Variablen anlegen
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 10);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device konnte nicht gefunden werden");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Device liefert Fehlerwert -85");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "DeviceAddress", "caption" => "Sensor ID");
		
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Offset", "caption" => "Offset", "digits" => 1, "suffix" => "°C", "minimum" => -10, "maximum" => 10);
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Messzyklus", "caption" => "Messzyklus", "suffix" => "sek", "minimum" => 0);
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "caption" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/';");		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}           
	  
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Summary setzen
		$this->SetSummary("SC: ".$this->ReadPropertyString("DeviceAddress"));
            	
		$OWDeviceArray = Array();
		$this->SetBuffer("OWDeviceArray", serialize($OWDeviceArray));
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {			
			If ($this->ReadPropertyBoolean("Open") == true) {	
				//ReceiveData-Filter setzen
				$Filter = '(.*"Function":"set_start_trigger".*|.*"InstanceID":'.$this->InstanceID.'.*)';
				//$this->SetReceiveDataFilter($Filter);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_OWDevices", "DeviceSerial" => $this->ReadPropertyString("DeviceAddress"), "InstanceID" => $this->InstanceID)));		
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					$this->Measurement();
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
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
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
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
			case "set_start_trigger":
			   	$this->ApplyChanges();
				break;
			case "OWV":
			   	If ($data->DeviceAddress == $this->ReadPropertyString("DeviceAddress")) {
					If (floatval($data->Value) <> -85) {
						$this->SetValue("Temperature", floatval($data->Value) + floatval($this->ReadPropertyFloat("Offset")));
						If ($this->GetStatus() <> 102) {
							$this->SetStatus(102);
						}
					}
					else {
						$this->SendDebug("ReceiveData", "Device liefert Fehlerwert -85", 0);
						If ($this->GetStatus() <> 202) {
							$this->SetStatus(202);
						}
					}
					
				}
			   	break;	
	 	}
 	}
	    
	// Beginn der Funktionen    
	public function Measurement()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl")) {
			// Messung ausführen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "OWV", "InstanceID" => $this->InstanceID, "DeviceAddress" => $this->ReadPropertyString("DeviceAddress") )));
		}
	}
	/*    
	private function GetData()
	{
		$OWArray = array();
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "OWS")));	
		$OWArray = unserialize($Result);
		If (is_array($OWArray)) {
			$this->SetStatus(102);
			$this->SendDebug("GetOWData", $Result, 0);
			$Devices = array();
			foreach($OWArray as $Key => $Device) {
				$OWFamilyCode = substr($Key, 0, 2);
				If ($OWFamilyCode == "10") {
					$Devices[] = $Key;
				}
			}
		}
	
	return serialize($Devices);
	}   
	*/
}
?>

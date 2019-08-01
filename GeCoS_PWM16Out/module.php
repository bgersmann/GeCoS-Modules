<?
    // Klassendefinition
    class GeCoS_PWM16Out extends IPSModule 
    {
	// PCA9685
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F1C0403-4A74-4F14-829F-9A217CFB2D05}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 80);
		$this->RegisterPropertyInteger("DeviceBus", 0);
		$this->RegisterPropertyInteger("Frequency", 100);
		
		// Profil anlegen
		$this->RegisterProfileInteger("Intensity.4096", "Intensity", "", " %", 0, 4095, 1);
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterVariableBoolean("Output_Bln_X".$i, "Ausgang X".$i, "~Switch", ($i + 1) * 10);
			$this->EnableAction("Output_Bln_X".$i);	
			$this->RegisterVariableInteger("Output_Int_X".$i, "Ausgang X".$i, "Intensity.4096", (($i + 1) * 10) + 5);
			$this->EnableAction("Output_Int_X".$i);	
		}
        }
 	
	public function GetConfigurationForm() 
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
		for ($i = 80; $i <= 87; $i++) {
		    	$arrayOptions[] = array("label" => $i." / 0x".strtoupper(dechex($i))."", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 0", "value" => 0);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 1", "value" => 1);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 2", "value" => 2);
		
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "GeCoS I²C-Bus", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "100", "value" => 100);
		$arrayOptions[] = array("label" => "200", "value" => 200);
		$arrayElements[] = array("type" => "Select", "name" => "Frequency", "caption" => "Frequenz (Hz)", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "label" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/'");
		
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}           
	  
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Summary setzen
		$this->SetSummary("0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." - I²C-Bus ".($this->ReadPropertyInteger("DeviceBus") - 4));
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				//ReceiveData-Filter setzen
				$Filter = '((.*"Function":"get_used_modules".*|.*"InstanceID":'.$this->InstanceID.'.*)|.*"Function":"status".*)';
				$this->SetReceiveDataFilter($Filter);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_modules", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->SetStatus(102);
				}
			}
			else {
				$this->SetStatus(104);
			}	
		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "SPWM":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->SendDebug("ReceiveData", "SPWM", 0);
					/*
					$Value = intval($data->Value); 
					// Statusvariablen setzen
					for ($i = 0; $i <= 15; $i++) {
						$Bitvalue = boolval($Value & pow(2, $i));					
						If (GetValueBoolean($this->GetIDForIdent("Input_X".$i)) <> $Bitvalue) {
							SetValueBoolean($this->GetIDForIdent("Input_X".$i), $Bitvalue);
						}
					}
					*/
				}
				break; 
			case "PWM":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->SendDebug("ReceiveData", "PWM", 0);
					$Channel = intval($data->Channel);
					$State = boolval($data->State);
					$Value = intval($data->Value); 
					// Statusvariablen setzen
					If (GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Channel)) <> $State) {
						SetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Channel), $State);
					}
					If (GetValueInteger($this->GetIDForIdent("Output_Int_X".$Channel)) <> $Value) {
						SetValueInteger($this->GetIDForIdent("Output_Int_X".$Channel), $Value);
					}					
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
						$this->SetStatus(104);
					}	
			   	}
			   	break;
	 	}
 	}
	
	public function RequestAction($Ident, $Value) 
	{
		$Source = substr($Ident, 7, 3);  
		$Number = intval(substr($Ident, 12, 2));
		
		switch($Source) {
		case "Bln":
			$this->SetOutputPinStatus($Number, $Value);
	            	break;
		case "Int":
	            	$this->SetOutputPinValue($Number, $Value);
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	    
	// Beginn der Funktionen
	public function SetOutputPinValue(Int $Channel, Int $Value)
	{ 
		$this->SendDebug("SetOutputPinValue", "Ausfuehrung", 0);
		$Channel = min(15, max(0, $Channel));
		$Value = min(4095, max(0, $Value));
		$State = GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Channel));
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Ausgang setzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "PWM", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "Channel" => $Channel, "State" => $State, "Value" => $Value )));
			//{PWM;I2C-Kanal;Adresse;PWMKanal;Status;Wert} Status=0/1 (0=Aus,1=Ein), Wert=0-100
		}
	}
	
	public function SetOutputPinStatus(Int $Channel, Bool $State)
	{ 
		$this->SendDebug("SetOutputPinStatus", "Ausfuehrung", 0);
		$Channel = min(15, max(0, $Channel));
		$State = min(1, max(0, $State));
		$Value = GetValueInteger($this->GetIDForIdent("Output_Int_X".$Channel));
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Ausgang setzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "PWM", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "Channel" => $Channel, "State" => $State, "Value" => $Value )));
		}
	}     
	    
	private function GetOutput()
	{
		$this->SendDebug("GetOutput", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "SPWM")));
			If ($Result == true) {
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(202);
			}
		}
	}
	
	private function SetStatusVariables(Int $Register, Int $Value)
	{
		$Intensity = $Value & 4095;
		$Status = !boolval($Value & 4096); 
		$Number = ($Register - 8) / 4;
		
		$this->SendDebug("SetStatusVariables", "Itensitaet: ".$Intensity." Status: ".(int)$Status, 0);
		
		If ($Intensity <> GetValueInteger($this->GetIDForIdent("Output_Int_X".$Number))) {
			SetValueInteger($this->GetIDForIdent("Output_Int_X".$Number), $Intensity);
		}
		If ($Status <> GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Number))) {
			SetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Number), $Status);
		}				
	}    
	
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);    
	}
	    
	protected function HasActiveParent()
    	{
		$Instance = @IPS_GetInstance($this->InstanceID);
		if ($Instance['ConnectionID'] > 0)
		{
			$Parent = IPS_GetInstance($Instance['ConnectionID']);
			if ($Parent['InstanceStatus'] == 102)
			return true;
		}
        return false;
    	}  
}
?>

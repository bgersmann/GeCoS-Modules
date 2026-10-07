<?php
    // Klassendefinition
    class GeCoS_DS2438 extends IPSModuleStrict
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create(): void
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("DeviceAddress", "Sensor ID");
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterPropertyFloat("Offset", 0);
		$this->RegisterTimer("Messzyklus", 0, 'GeCoSDS2438_Measurement($_IPS["TARGET"]);');

		//Status-Variablen anlegen
		$this->RegisterVariableFloat("Temperature", "Temperatur", array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "SUFFIX" => " °C", "DIGITS" => 1, "ICON" => "temperature-half", "USAGE_TYPE" => 1, "MIN" => -55, "MAX" => 125), 10);
          	$this->DisableAction("Temperature");

		$this->RegisterVariableFloat("VAD", "VAD", array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "SUFFIX" => " V", "DIGITS" => 2, "ICON" => "bolt"), 20);
          	$this->DisableAction("VAD");

		$this->RegisterVariableFloat("VDD", "VDD", array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "SUFFIX" => " V", "DIGITS" => 2, "ICON" => "bolt"), 30);
          	$this->DisableAction("VDD");

		$this->RegisterVariableFloat("XSENS", "XSENS", array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "DIGITS" => 2), 40);
          	$this->DisableAction("XSENS");
        }

	public function GetConfigurationForm(): string
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
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "DeviceAddress", "caption" => "Sensor ID");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Offset", "caption" => "Offset", "digits" => 1, "suffix" => "°C", "minimum" => -10, "maximum" => 10);
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Messzyklus", "caption" => "Messzyklus", "suffix" => "sek", "minimum" => 0);
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "caption" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/';");

 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements));
 	}

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges(): void
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();

		// Summary setzen
		$this->SetSummary("SC: ".$this->ReadPropertyString("DeviceAddress"));

		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
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

	public function ReceiveData(string $JSONString): string
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
					If (floatval($data->Value) == -85) {
						$this->SendDebug("ReceiveData", "Device liefert Fehlerwert -85", 0);
						If ($this->GetStatus() <> 202) {
							$this->SetStatus(202);
						}
					}
					// Messbereich laut Datenblatt: -55 °C bis +125 °C
					elseIf ((floatval($data->Value) < -55) OR (floatval($data->Value) > 125)) {
						$this->SendDebug("ReceiveData", "Wert ausserhalb des Messbereichs verworfen: ".$data->Value, 0);
					}
					else {
						$this->SetValue("Temperature", floatval($data->Value) + floatval($this->ReadPropertyFloat("Offset")));
						If ($this->GetStatus() <> 102) {
							$this->SetStatus(102);
						}
					}
				}
			   	break;
	 	}
		return "";
	}

	// Beginn der Funktionen
	public function Measurement(): void
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyString("DeviceAddress") <> "Sensor ID")) {
			// Messung ausführen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "OWV", "InstanceID" => $this->InstanceID, "DeviceAddress" => $this->ReadPropertyString("DeviceAddress") )));
		}
	}
}
?>

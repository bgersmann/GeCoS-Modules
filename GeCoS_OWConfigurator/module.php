<?php
    // Klassendefinition
    class GeCoS_OWConfigurator extends IPSModuleStrict
    {

	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create(): void
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();

        }

	public function GetConfigurationForm(): string
	{
		$arrayStatus = array();
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instance is being created");
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instance is active");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instance is inactive");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "I²C communication error!");

		// 1-Wire Devices
		$arrayElements = array();
		$arraySort = array();
		$arraySort = array("column" => "OWType", "direction" => "ascending");

		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "Type", "name" => "OWType", "width" => "250px", "visible" => true);
		$arrayColumns[] = array("caption" => "Serial", "name" => "OWSerial", "width" => "auto", "visible" => true);
		$OWArray = array();
		If ($this->HasActiveParent() == true) {
			$OWArray = unserialize($this->GetData());
		}
		// Nur Instanzen, die mit dem gleichen GeCoS-IO verbunden sind
		$Instances = $this->GetConnectedInstances();
		$arrayValues = array();
		for ($i = 0; $i < Count($OWArray); $i++) {
			$OWSerial = $OWArray[$i]["OWSerial"];
			$Row = array("OWType" => $OWArray[$i]["OWType"], "OWSerial" => $OWSerial, "instanceID" => 0);
			$GUID = $this->FamilyCodeToGUID(substr($OWSerial, 0, 2));
			If ($GUID <> "") {
				$InstanceID = array_search($OWSerial, $Instances);
				If ($InstanceID !== false) {
					$Row["instanceID"] = $InstanceID;
					unset($Instances[$InstanceID]);
				}
				$Row["create"] = array(array("moduleID" => $GUID, "configuration" => array("DeviceAddress" => $OWSerial, "Open" => true)));
			}
			$arrayValues[] = $Row;
		}
		// Instanzen am gleichen IO, deren Sensor aktuell nicht gefunden wurde
		foreach ($Instances as $InstanceID => $OWSerial) {
			$arrayValues[] = array("OWType" => IPS_GetInstance($InstanceID)['ModuleInfo']['ModuleName'], "OWSerial" => $OWSerial, "instanceID" => $InstanceID);
		}

		$arrayElements[] = array("type" => "Configurator", "name" => "OWDevices", "caption" => "1-Wire components", "rowCount" => 10, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);

		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "caption" => "Manufacturer information", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/';");

		$arrayActions = array();

 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions));
 	}

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges(): void
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();

		If (IPS_GetKernelRunlevel() == 10103) {
			If ($this->HasActiveParent() == true) {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
			}
			else {
				If ($this->GetStatus() <> 104) {
					$this->SetStatus(104);
				}
			}
		}
	}

	// Beginn der Funktionen
	private function GetData()
	{
		$OWArray = array();
		$Devices = array();
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "OWS")));
		$OWArray = @unserialize($Result);
		If (is_array($OWArray)) {
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
			$this->SendDebug("GetOWData", $Result, 0);
			$Devices = array();
			$i = 0;
			foreach($OWArray as $Key => $Device) {
				$Devices[$i]["OWType"] = $Device;
				$Devices[$i]["OWSerial"] = $Key;
				$i = $i + 1;
			}
		}

	return serialize($Devices);
	}

	private function GetConnectedInstances()
	{
		// Liefert InstanzID => Sensor-ID aller 1-Wire-Instanzen am gleichen GeCoS-IO
		$Result = array();
		$ParentID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		If ($ParentID == 0) {
			return $Result;
		}
		foreach ($this->GetFamilyCodes() as $FamilyCode => $GUID) {
			foreach (IPS_GetInstanceListByModuleID($GUID) as $InstanceID) {
				If (IPS_GetInstance($InstanceID)['ConnectionID'] == $ParentID) {
					$Result[$InstanceID] = IPS_GetProperty($InstanceID, "DeviceAddress");
				}
			}
		}
	return $Result;
	}

	private function GetFamilyCodes()
	{
		return array("10" => "{8179FCFF-E441-4FAC-BCC3-1B97E9D45052}",
			     "3a" => "{AFB9CF0C-CA31-4336-8B0F-E26168960417}",
			     "28" => "{18CFA944-CFC9-4A72-8D2A-231604FF7D2A}",
			     "26" => "{99250714-5864-43B4-BDF8-E6F62FEBFA8A}");
	}

	private function FamilyCodeToGUID(string $FamilyCode)
	{
		$FamilyCodeArray = $this->GetFamilyCodes();
		If (array_key_exists($FamilyCode, $FamilyCodeArray)) {
			return $FamilyCodeArray[$FamilyCode];
		}
	return "";
	}
}
?>

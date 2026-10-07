<?php
    // Klassendefinition
    class GeCoS_Configurator extends IPSModuleStrict
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
		// GeCoS-Module
		$arrayElements = array();
		$arraySort = array();
		$arraySort = array("column" => "DeviceType", "direction" => "ascending");

		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "Type", "name" => "DeviceType", "width" => "150px", "visible" => true);
		$arrayColumns[] = array("caption" => "GeCoS bus", "name" => "DeviceBus", "width" => "75px", "visible" => true);
		$arrayColumns[] = array("caption" => "Address", "name" => "DeviceAddress", "width" => "auto", "visible" => true);

		$DeviceArray = array();
		If ($this->HasActiveParent() == true) {
			$DeviceArray = unserialize($this->GetData());
		}
		// Nur Instanzen, die mit dem gleichen GeCoS-IO verbunden sind
		$Instances = $this->GetConnectedInstances();
		$arrayValues = array();
		for ($i = 0; $i < Count($DeviceArray); $i++) {
			$DeviceType = $DeviceArray[$i]["DeviceType"];
			$DeviceBus = intval($DeviceArray[$i]["DeviceBus"]);
			$DeviceAddress = intval($DeviceArray[$i]["DeviceAddress"]);
			$Row = array("DeviceBus" => $DeviceBus, "DeviceType" => $DeviceType, "DeviceAddress" => $DeviceAddress." / 0x".strtoupper(dechex($DeviceAddress)), "instanceID" => 0);

			If (array_key_exists($DeviceType, $this->GetDeviceTypes())) {
				$InstanceID = array_search(array($DeviceType, $DeviceBus, $DeviceAddress), $Instances);
				If ($InstanceID !== false) {
					$Row["instanceID"] = $InstanceID;
					unset($Instances[$InstanceID]);
				}
				$Row["create"] = array(array("moduleID" => $this->DeviceTypeToGUID($DeviceType),
					       "configuration" => array("DeviceAddress" => $DeviceAddress, "DeviceBus" => $DeviceBus, "Open" => true)));
			}
			$arrayValues[] = $Row;
		}
		// Instanzen am gleichen IO, deren Gerät aktuell nicht gefunden wurde
		foreach ($Instances as $InstanceID => $Instance) {
			$arrayValues[] = array("DeviceBus" => $Instance[1], "DeviceType" => $Instance[0], "DeviceAddress" => $Instance[2]." / 0x".strtoupper(dechex($Instance[2])), "instanceID" => $InstanceID);
		}

		$arrayElements[] = array("type" => "Configurator", "name" => "GeCoS_Modules", "caption" => "GeCoS modules", "rowCount" => 10, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);

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
		$DeviceArray = array();
		$Devices = array();
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "MOD")));
		$DeviceArray = @unserialize($Result);
		If (is_array($DeviceArray)) {
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
			$this->SendDebug("GetData", $Result, 0);
			$Devices = array();
			$i = 0;
			foreach($DeviceArray as $Key => $Device) {
				$Devices[$i]["DeviceType"] = $Device[0];
				$Devices[$i]["DeviceAddress"] = $Device[1];
				$Devices[$i]["DeviceBus"] = $Device[2];
				$i = $i + 1;
			}
		}

	return serialize($Devices);
	}

	private function GetConnectedInstances()
	{
		// Liefert InstanzID => array(Typ, Bus, Adresse) aller Instanzen am gleichen GeCoS-IO
		$Result = array();
		$ParentID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		If ($ParentID == 0) {
			return $Result;
		}
		foreach ($this->GetDeviceTypes() as $DeviceType => $GUID) {
			foreach (IPS_GetInstanceListByModuleID($GUID) as $InstanceID) {
				If (IPS_GetInstance($InstanceID)['ConnectionID'] == $ParentID) {
					$Result[$InstanceID] = array($DeviceType, intval(IPS_GetProperty($InstanceID, "DeviceBus")), intval(IPS_GetProperty($InstanceID, "DeviceAddress")));
				}
			}
		}
	return $Result;
	}

	private function GetDeviceTypes()
	{
		return array("IN" => "{EF63175E-F346-4A87-A828-F4C422F7F948}",
			     "OUT" => "{EC701E32-032F-4FBD-B161-F66890DD0A9C}",
			     "PWM" => "{E6CD7AEF-064A-42EF-A5CD-B81453DA762C}",
			     "RGBW" => "{8A40AFDC-979B-4688-A014-3BA2B70550E8}",
			     "ANA" => "{39E6BA4A-A94E-4058-B099-794A627B63E0}");
	}

	private function DeviceTypeToGUID(string $DeviceType)
	{
		$DeviceArray = $this->GetDeviceTypes();
	return $DeviceArray[$DeviceType];
	}
}
?>

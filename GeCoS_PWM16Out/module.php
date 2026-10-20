<?php
require_once __DIR__ . '/../libs/GeCoSLegacyIcons.php';
    // Klassendefinition
    class GeCoS_PWM16Out extends IPSModuleStrict
    {
	use GeCoSLegacyIcons;
	// PCA9685

	// Dimmkurven
	private const CURVE_NONE = 0;		// Rohwert 0-4095
	private const CURVE_LINEAR = 1;		// 0-100 % linear
	private const CURVE_LOG = 2;		// 0-100 % logarithmisch (Kennlinie des gecosDimmAktor)
	private const CURVE_CIE = 3;		// 0-100 % CIE 1931 (wahrnehmungsgleich)
	private const PWM_MAX = 4095;

	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create(): void
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterPropertyBoolean("LegacyIcons", false);
 	    	$this->RegisterPropertyBoolean("Open", false);
 	    	$this->RegisterPropertyInteger("DeviceAddress", 80);
		$this->RegisterPropertyInteger("DeviceBus", 0);
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterPropertyInteger("Curve_X".$i, self::CURVE_NONE);
		}
		// Zuletzt angewendete Dimmkurve je Ausgang, um Werte beim Wechsel umzurechnen
		$this->RegisterAttributeString("AppliedCurves", "[]");
		// Taster-Steuerung (kurz = Ein/Aus, lang = Dimmen)
		$this->RegisterPropertyString("Buttons", "[]");
		$this->RegisterPropertyInteger("PressDuration", 250);
		$this->RegisterPropertyInteger("StepDuration", 75);
		$this->RegisterPropertyInteger("MinIntensity", 5);
		$this->RegisterPropertyInteger("NightVariable", 0);
		$this->RegisterPropertyInteger("NightIntensity", 30);
		// Letzte Dimmrichtung je Ausgang (true = hoch), die nächste geht in die Gegenrichtung
		$this->RegisterAttributeString("DimDirections", "[]");

		$this->RegisterVariables();
	}
	
	private function RegisterVariables()
	{
		// Wird auch in ApplyChanges aufgerufen: bestehende Variablen behalten ID,
		// Name und Position, es wird nur die Darstellung aktualisiert
		//Status-Variablen anlegen
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterVariableBoolean("Output_Bln_X".$i, sprintf($this->Translate("Output X%d"), $i), $this->SwitchPresentation(), ($i + 1) * 10);
			$this->EnableAction("Output_Bln_X".$i);
			$this->RegisterVariableInteger("Output_Int_X".$i, sprintf($this->Translate("Output X%d"), $i), $this->IntensityPresentation($this->GetCurve($i)), (($i + 1) * 10) + 5);
			$this->EnableAction("Output_Int_X".$i);
		}
	}

	public function GetConfigurationForm(): string
	{
		$arrayStatus = array();
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instance is being created");
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instance is active");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instance is inactive");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instance is faulty");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device could not be found");

		$arrayElements = array();
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Active");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LegacyIcons", "caption" => "Legacy icons (classic WebFront)");

		$arrayOptions = array();
		for ($i = 80; $i <= 87; $i++) {
		    	$arrayOptions[] = array("label" => $i." / 0x".strtoupper(dechex($i))."", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device address", "options" => $arrayOptions );

		$arrayOptions = array();
		$arrayOptions[] = array("label" => "GeCoS I²C bus 0", "value" => 0);
		$arrayOptions[] = array("label" => "GeCoS I²C bus 1", "value" => 1);
		$arrayOptions[] = array("label" => "GeCoS I²C bus 2", "value" => 2);


		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "GeCoS I²C bus", "options" => $arrayOptions );

		// Dimmkurven je Ausgang
		$CurveOptions = array();
		$CurveOptions[] = array("caption" => "None (raw value 0-4095)", "value" => self::CURVE_NONE);
		$CurveOptions[] = array("caption" => "Linear (0-100 %)", "value" => self::CURVE_LINEAR);
		$CurveOptions[] = array("caption" => "Logarithmic (0-100 %)", "value" => self::CURVE_LOG);
		$CurveOptions[] = array("caption" => "CIE 1931 (0-100 %)", "value" => self::CURVE_CIE);
		$CurveItems = array();
		$CurveItems[] = array("type" => "Label", "caption" => "With a dimming curve the intensity variable shows 0-100 % and is converted to the PWM value 0-4095. The variable is kept, existing values are converted.");
		for ($i = 0; $i <= 15; $i++) {
			$CurveItems[] = array("type" => "Select", "name" => "Curve_X".$i, "caption" => sprintf($this->Translate("Output X%d"), $i), "options" => $CurveOptions);
		}
		$arrayElements[] = array("type" => "ExpansionPanel", "caption" => "Dimming curves", "items" => $CurveItems);

		// Taster-Steuerung
		$ChannelOptions = array();
		for ($i = 0; $i <= 15; $i++) {
			$ChannelOptions[] = array("caption" => sprintf($this->Translate("Output X%d"), $i), "value" => $i);
		}
		$ButtonItems = array();
		$ButtonItems[] = array("type" => "Label", "caption" => "Short press switches the output on/off, long press dims it. The dimming direction changes with every long press.");
		$ButtonItems[] = array("type" => "List", "name" => "Buttons", "caption" => "Buttons", "rowCount" => 5, "add" => true, "delete" => true,
			"columns" => array(
				array("caption" => "Output", "name" => "Channel", "width" => "200px", "add" => 0, "edit" => array("type" => "Select", "options" => $ChannelOptions)),
				array("caption" => "Button variable", "name" => "VariableID", "width" => "auto", "add" => 0, "edit" => array("type" => "SelectVariable", "validVariableTypes" => array(0)))
			));
		$ButtonItems[] = array("type" => "NumberSpinner", "name" => "PressDuration", "caption" => "Press duration until dimming", "suffix" => "ms", "minimum" => 50);
		$ButtonItems[] = array("type" => "NumberSpinner", "name" => "StepDuration", "caption" => "Duration per dimming step (1 %)", "suffix" => "ms", "minimum" => 10);
		$ButtonItems[] = array("type" => "NumberSpinner", "name" => "MinIntensity", "caption" => "Minimum intensity", "suffix" => "%", "minimum" => 1, "maximum" => 100);
		$ButtonItems[] = array("type" => "SelectVariable", "name" => "NightVariable", "caption" => "Night variable (night when false, optional)", "validVariableTypes" => array(0));
		$ButtonItems[] = array("type" => "NumberSpinner", "name" => "NightIntensity", "caption" => "Intensity when switched on at night", "suffix" => "%", "minimum" => 1, "maximum" => 100);
		$arrayElements[] = array("type" => "ExpansionPanel", "caption" => "Buttons (short = on/off, long = dim)", "items" => $ButtonItems);

		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "label" => "Manufacturer information", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/'");
		// Testcenter eingeklappt, damit es im Formular keinen Platz wegnimmt
		$arrayElements[] = array("type" => "ExpansionPanel", "caption" => "Test Center", "expanded" => false, "items" => array(
			array("type" => "TestCenter", "name" => "TestCenter")
		));

		// Dimmkurve für alle Ausgänge vorbelegen, wirksam erst nach "Änderungen übernehmen"
		$arrayActions = array();
		$arrayActions[] = array("type" => "RowLayout", "items" => array(
			array("type" => "Select", "name" => "CurveAll", "caption" => "Dimming curve for all outputs", "options" => $CurveOptions, "value" => self::CURVE_NONE),
			array("type" => "Button", "caption" => "Apply to all outputs", "onClick" => 'IPS_RequestAction($id, "SetAllCurves", $CurveAll);')
		));
		$arrayActions[] = array("type" => "Label", "caption" => "The selection is entered for all outputs under \"Dimming curves\" and takes effect after \"Apply changes\".");

 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions));
 	}

        public function ApplyChanges(): void
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Darstellungen aktualisieren (z.B. Legacy-Icons)
		$this->RegisterVariables();

		// Summary setzen
		$this->SetSummary("0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." - I²C-Bus ".($this->ReadPropertyInteger("DeviceBus") - 4));

		// Darstellung der Intensitäts-Variablen an die Dimmkurve anpassen
		$this->ApplyCurves();

		// Taster und Nacht-Variable überwachen
		$this->RegisterButtons();

		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				//ReceiveData-Filter setzen
				$Filter = '((.*"Function":"get_used_modules".*|.*"InstanceID":'.$this->InstanceID.'.*)|.*"Function":"status".*)';
				$this->SetReceiveDataFilter($Filter);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_modules", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
				}
				$this->GetOutput();
			}
			else {
				If ($this->GetStatus() <> 104) {
					$this->SetStatus(104);
				}
			}
		}
	}

	public function ReceiveData(string $JSONString): string
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "SPWM":
			case "PWM":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$Channel = intval($data->Channel);
					$State = boolval($data->State);
					$Value = intval($data->Value);
					$this->SendDebug("ReceiveData", $data->Function." Channel: ".$Channel." State: ".$State." Value: ".$Value, 0);
					// Statusvariablen setzen
					If ($this->GetValue("Output_Bln_X".$Channel) <> $State) {
						$this->SetValue("Output_Bln_X".$Channel, $State);
					}
					$this->UpdateIntensity($Channel, $Value);
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
		If ($Ident == "SetAllCurves") {
			// Nur das Formular vorbelegen, der Benutzer bestätigt mit "Änderungen übernehmen"
			for ($i = 0; $i <= 15; $i++) {
				$this->UpdateFormField("Curve_X".$i, "value", (int)$Value);
			}
			return;
		}

		$Source = substr($Ident, 7, 3);
		$Number = intval(substr($Ident, 12, 2));

		switch($Source) {
		case "Bln":
			$this->SetOutputPinStatus($Number, $Value);
	            	break;
		case "Int":
			If ($this->GetCurve($Number) == self::CURVE_NONE) {
				$this->SetOutputPinValue($Number, (int)$Value);
			}
			else {
				$this->SetOutputPinIntensity($Number, (int)$Value);
			}
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}

	// Beginn der Funktionen
	public function SetOutputPinValue(int $Channel, int $Value): void
	{
		// Rohwert 0-4095, unabhängig von der Dimmkurve
		$this->SendDebug("SetOutputPinValue", "Ausfuehrung", 0);
		$Channel = min(15, max(0, $Channel));
		$Value = min(self::PWM_MAX, max(0, $Value));
		If ($this->SendPWM($Channel, $this->GetValue("Output_Bln_X".$Channel), $Value)) {
			$this->UpdateIntensity($Channel, $Value);
		}
	}

	public function SetOutputPinIntensity(int $Channel, int $Percent): void
	{
		// Helligkeit 0-100 % über die Dimmkurve des Ausgangs (ohne Dimmkurve linear)
		$this->SendDebug("SetOutputPinIntensity", "Ausfuehrung", 0);
		$Channel = min(15, max(0, $Channel));
		$Percent = min(100, max(0, $Percent));
		$Curve = $this->GetCurve($Channel);
		$Value = $this->PercentToPWM(($Curve == self::CURVE_NONE) ? self::CURVE_LINEAR : $Curve, $Percent);
		$this->SendDebug("SetOutputPinIntensity", "Channel: ".$Channel." ".$Percent." % -> PWM ".$Value, 0);
		If ($this->SendPWM($Channel, $this->GetValue("Output_Bln_X".$Channel), $Value)) {
			$this->SetValue("Output_Int_X".$Channel, ($Curve == self::CURVE_NONE) ? $Value : $Percent);
		}
	}

	public function SetOutputPinStatus(int $Channel, bool $State): void
	{
		$this->SendDebug("SetOutputPinStatus", "Ausfuehrung", 0);
		$Channel = min(15, max(0, $Channel));
		If ($this->SendPWM($Channel, $State, $this->GetPWM($Channel))) {
			$this->SetValue("Output_Bln_X".$Channel, $State);
		}
	}

	private function SendPWM(int $Channel, bool $State, int $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == false) {
			return false;
		}
		$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "PWM", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "Channel" => $Channel, "State" => $State, "Value" => $Value )));
		return true;
	}

	private function GetOutput()
	{
		$this->SendDebug("GetOutput", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "SPWM")));
			If ($Result == true) {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
			}
			else {
				If ($this->GetStatus() <> 200) {
					$this->SetStatus(200);
				}
			}
		}
	}

	public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
	{
		If (($Message <> VM_UPDATE) OR !$Data[0]) {
			return;
		}
		foreach ($this->GetButtons() as $Button) {
			If ($Button["VariableID"] == $SenderID) {
				// In eigenem Thread auswerten, damit Wartezeiten beim Dimmen nichts blockieren
				$this->RegisterOnceTimer("Button_X".$Button["Channel"], 'GeCoSPWM16Out_HandleButton($_IPS["TARGET"], '.$Button["Channel"].', '.$SenderID.');');
			}
		}
	}

	public function HandleButton(int $Channel, int $ButtonID): void
	{
		// Wird über die Taster-Steuerung aufgerufen: kurz = Ein/Aus, lang = Dimmen
		$Channel = min(15, max(0, $Channel));
		If (!IPS_VariableExists($ButtonID)) {
			return;
		}
		$Semaphore = "GeCoSPWM16Out_".$this->InstanceID."_".$Channel;
		If (!IPS_SemaphoreEnter($Semaphore, 0)) {
			// Für diesen Ausgang läuft bereits eine Auswertung
			return;
		}
		$PressDuration = $this->ReadPropertyInteger("PressDuration");
		$Min = $this->ReadPropertyInteger("MinIntensity");
		If ($this->GetValue("Output_Bln_X".$Channel)) {
			IPS_Sleep($PressDuration);
			If (!GetValueBoolean($ButtonID)) {
				// Kurz gedrückt: ausschalten
				$this->SendDebug("HandleButton", "Channel ".$Channel.": aus", 0);
				$this->SetOutputPinStatus($Channel, false);
			}
			else {
				$this->DimLoop($Channel, $ButtonID, $this->GetIntensityPercent($Channel));
			}
		}
		else {
			$Intensity = max($this->GetIntensityPercent($Channel), $Min);
			If ($this->IsNight()) {
				$Intensity = $this->ReadPropertyInteger("NightIntensity");
				// Nach dem Nachtwert wird zuerst hochgedimmt
				$this->SetDimDirection($Channel, false);
			}
			$this->SendDebug("HandleButton", "Channel ".$Channel.": ein mit ".$Intensity." %", 0);
			$this->SetOutputPinIntensity($Channel, $Intensity);
			$this->SetOutputPinStatus($Channel, true);
			IPS_Sleep($PressDuration);
			If (GetValueBoolean($ButtonID) AND $this->GetValue("Output_Bln_X".$Channel)) {
				$this->DimLoop($Channel, $ButtonID, $Intensity);
			}
		}
		IPS_SemaphoreLeave($Semaphore);
	}

	private function DimLoop(int $Channel, int $ButtonID, int $Intensity)
	{
		$Min = $this->ReadPropertyInteger("MinIntensity");
		$StepDuration = $this->ReadPropertyInteger("StepDuration");
		// Richtung wechselt bei jedem Dimmvorgang, an den Grenzen immer in die mögliche Richtung
		$Up = !$this->GetDimDirection($Channel);
		If ($Intensity >= 100) {
			$Up = false;
		}
		elseIf ($Intensity <= $Min) {
			$Up = true;
		}
		$this->SendDebug("DimLoop", "Channel ".$Channel.": dimmen ".($Up ? "hoch" : "runter")." ab ".$Intensity." %", 0);
		$Dimmed = false;
		while (GetValueBoolean($ButtonID)) {
			$Intensity = min(100, max($Min, $Intensity + ($Up ? 1 : -1)));
			$this->SetOutputPinIntensity($Channel, $Intensity);
			$Dimmed = true;
			If (($Up AND ($Intensity >= 100)) OR (!$Up AND ($Intensity <= $Min))) {
				break;
			}
			IPS_Sleep($StepDuration);
		}
		If ($Dimmed) {
			$this->SetDimDirection($Channel, $Up);
		}
	}

	private function GetButtons()
	{
		// Gültige Taster aus der Liste: array(array("Channel" => int, "VariableID" => int), ...)
		$Buttons = array();
		$List = json_decode($this->ReadPropertyString("Buttons"), true);
		If (!is_array($List)) {
			return $Buttons;
		}
		foreach ($List as $Row) {
			$VariableID = intval($Row["VariableID"] ?? 0);
			If (($VariableID > 0) AND IPS_VariableExists($VariableID)) {
				$Buttons[] = array("Channel" => min(15, max(0, intval($Row["Channel"] ?? 0))), "VariableID" => $VariableID);
			}
		}
		return $Buttons;
	}

	private function RegisterButtons()
	{
		// Alte Registrierungen entfernen
		foreach ($this->GetMessageList() as $SenderID => $Messages) {
			foreach ($Messages as $Message) {
				If ($Message == VM_UPDATE) {
					$this->UnregisterMessage($SenderID, $Message);
				}
			}
		}
		foreach ($this->GetReferenceList() as $ReferenceID) {
			$this->UnregisterReference($ReferenceID);
		}
		foreach ($this->GetButtons() as $Button) {
			$this->RegisterMessage($Button["VariableID"], VM_UPDATE);
			$this->RegisterReference($Button["VariableID"]);
		}
		$NightID = $this->ReadPropertyInteger("NightVariable");
		If (($NightID > 0) AND IPS_VariableExists($NightID)) {
			$this->RegisterReference($NightID);
		}
	}

	private function IsNight()
	{
		$NightID = $this->ReadPropertyInteger("NightVariable");
		return (($NightID > 0) AND IPS_VariableExists($NightID) AND !GetValueBoolean($NightID));
	}

	private function GetIntensityPercent(int $Channel)
	{
		// Aktuelle Helligkeit in %, ohne Dimmkurve aus dem Rohwert umgerechnet
		$Value = intval($this->GetValue("Output_Int_X".$Channel));
		If ($this->GetCurve($Channel) == self::CURVE_NONE) {
			return (int)round($Value * 100 / self::PWM_MAX);
		}
		return $Value;
	}

	private function GetDimDirection(int $Channel)
	{
		$Directions = json_decode($this->ReadAttributeString("DimDirections"), true);
		return (bool)($Directions[$Channel] ?? true);
	}

	private function SetDimDirection(int $Channel, bool $Up)
	{
		$Directions = json_decode($this->ReadAttributeString("DimDirections"), true);
		If (!is_array($Directions)) {
			$Directions = array();
		}
		$Directions[$Channel] = $Up;
		$this->WriteAttributeString("DimDirections", json_encode($Directions));
	}

	// Dimmkurven
	private function GetCurve(int $Channel)
	{
		return $this->ReadPropertyInteger("Curve_X".$Channel);
	}

	private function GetPWM(int $Channel)
	{
		// Aktueller PWM-Rohwert eines Ausgangs
		return $this->PercentToPWM($this->GetCurve($Channel), intval($this->GetValue("Output_Int_X".$Channel)));
	}

	private function UpdateIntensity(int $Channel, int $PWM)
	{
		// Rohwert vom Server in die Darstellung des Ausgangs umrechnen
		$Curve = $this->GetCurve($Channel);
		$Current = intval($this->GetValue("Output_Int_X".$Channel));
		// Passt der aktuelle Wert bereits zum Rohwert, bleibt er stehen (kein Springen durch Rundung)
		If ($this->PercentToPWM($Curve, $Current) == $PWM) {
			return;
		}
		$this->SetValue("Output_Int_X".$Channel, $this->PWMToPercent($Curve, $PWM));
	}

	private function ApplyCurves()
	{
		$Applied = json_decode($this->ReadAttributeString("AppliedCurves"), true);
		If (!is_array($Applied)) {
			$Applied = array();
		}
		for ($i = 0; $i <= 15; $i++) {
			$Ident = "Output_Int_X".$i;
			$Curve = $this->GetCurve($i);
			// Bestehende Variable behält ihre ID, es wird nur die Darstellung aktualisiert
			$this->RegisterVariableInteger($Ident, sprintf($this->Translate("Output X%d"), $i), $this->IntensityPresentation($Curve), (($i + 1) * 10) + 5);
			$OldCurve = intval($Applied[$i] ?? self::CURVE_NONE);
			If ($OldCurve <> $Curve) {
				// Wert umrechnen, damit die Helligkeit gleich bleibt
				$PWM = $this->PercentToPWM($OldCurve, intval($this->GetValue($Ident)));
				$this->SetValue($Ident, $this->PWMToPercent($Curve, $PWM));
				$this->SendDebug("ApplyCurves", "Channel ".$i.": Dimmkurve ".$OldCurve." -> ".$Curve, 0);
			}
			$Applied[$i] = $Curve;
		}
		$this->WriteAttributeString("AppliedCurves", json_encode($Applied));
	}

	private function IntensityPresentation(int $Curve)
	{
		If ($Curve == self::CURVE_NONE) {
			return array("PRESENTATION" => VARIABLE_PRESENTATION_SLIDER, "MIN" => 0, "MAX" => self::PWM_MAX, "STEP_SIZE" => 1, "PERCENTAGE" => true, "SUFFIX" => " %", "DIGITS" => 0, "ICON" => $this->Icon("lightbulb", "Intensity"));
		}
		return array("PRESENTATION" => VARIABLE_PRESENTATION_SLIDER, "MIN" => 0, "MAX" => 100, "STEP_SIZE" => 1, "SUFFIX" => " %", "DIGITS" => 0, "ICON" => $this->Icon("lightbulb", "Intensity"), "USAGE_TYPE" => 2);
	}

	private function PercentToPWM(int $Curve, int $Value)
	{
		If ($Curve == self::CURVE_NONE) {
			// Wert ist bereits der Rohwert
			return min(self::PWM_MAX, max(0, $Value));
		}
		$Percent = min(100, max(0, $Value));
		If ($Percent == 0) {
			return 0;
		}
		switch ($Curve) {
			case self::CURVE_LOG:
				// Kennlinie des gecosDimmAktor: 2^((p + 350) / 37.5) - 1, 100 % = 4095
				$PWM = pow(2, ($Percent + 350) / 37.5) - 1;
				break;
			case self::CURVE_CIE:
				// CIE 1931 Helligkeit L* -> Leuchtdichte Y
				$Y = ($Percent <= 8) ? $Percent / 903.3 : pow(($Percent + 16) / 116, 3);
				$PWM = $Y * self::PWM_MAX;
				break;
			default:
				$PWM = $Percent * self::PWM_MAX / 100;
		}
		return (int)min(self::PWM_MAX, max(0, round($PWM)));
	}

	private function PWMToPercent(int $Curve, int $PWM)
	{
		$PWM = min(self::PWM_MAX, max(0, $PWM));
		If ($Curve == self::CURVE_NONE) {
			return $PWM;
		}
		If ($PWM == 0) {
			return 0;
		}
		switch ($Curve) {
			case self::CURVE_LOG:
				$Percent = 37.5 * log($PWM + 1, 2) - 350;
				break;
			case self::CURVE_CIE:
				$Y = $PWM / self::PWM_MAX;
				$Percent = ($Y <= 0.008856) ? $Y * 903.3 : 116 * pow($Y, 1 / 3) - 16;
				break;
			default:
				$Percent = $PWM * 100 / self::PWM_MAX;
		}
		// Ein eingeschalteter Ausgang zeigt mindestens 1 %
		return (int)min(100, max(1, round($Percent)));
	}
}
?>

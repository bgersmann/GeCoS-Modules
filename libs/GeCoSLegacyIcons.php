<?php

// Legacy-Modus (Eigenschaft "LegacyIcons"): klassische Variablenprofile und Icons für das klassische WebFront/die App
trait GeCoSLegacyIcons
{
	private function Icon(string $Icon, string $Legacy = ""): string
	{
		if (!$this->ReadPropertyBoolean("LegacyIcons")) {
			return $Icon;
		}
		if ($Legacy != "") {
			return $Legacy;
		}
		// Entspricht den Icons der früheren Variablenprofile
		$Map = array(
			"temperature-half" => "Temperature",
			"gauge"            => "Gauge",
			"gauge-high"       => "Speedo",
			"droplet"          => "Drops",
			"bolt"             => "Electricity",
			"lightbulb"        => "Bulb",
			"microchip"        => "Information",
			"memory"           => "Information",
			"circle-info"      => "Information",
			"wind"             => "Information"
		);
		return $Map[$Icon] ?? $Icon;
	}

	private function Presentation(array $Presentation, string $Profile): array
	{
		// Im Legacy-Modus das Variablenprofil über die Darstellung "Legacy Profil" setzen,
		// damit das klassische WebFront/die App keine Darstellung umrechnen muss
		if (!$this->ReadPropertyBoolean("LegacyIcons") || ($Profile == "")) {
			return $Presentation;
		}
		$this->EnsureLegacyProfile($Profile);
		return array("PRESENTATION" => VARIABLE_PRESENTATION_LEGACY, "PROFILE" => $Profile);
	}

	private function SwitchPresentation(): array
	{
		return $this->Presentation(array("PRESENTATION" => VARIABLE_PRESENTATION_SWITCH), "~Switch");
	}

	private function EnsureLegacyProfile(string $Name): void
	{
		// Systemprofile (~) sind immer vorhanden, bestehende Profile werden nicht verändert
		if ((substr($Name, 0, 1) == "~") || IPS_VariableProfileExists($Name)) {
			return;
		}
		// Name => Typ, Icon, Suffix, Min, Max, Schritt, Nachkommastellen, Assoziationen
		$Profiles = array(
			"GeCoS.gm3"      => array(VARIABLETYPE_FLOAT, "Drops", " g/m³", 0, 1000, 0.1, 1, array()),
			"GeCoS.ppm"      => array(VARIABLETYPE_INTEGER, "Gauge", " ppm", 0, 6000, 1, 0, array()),
			"GeCoS.ppb"      => array(VARIABLETYPE_INTEGER, "Gauge", " ppb", 0, 6000, 1, 0, array()),
			"GeCoS.Lux"      => array(VARIABLETYPE_INTEGER, "Bulb", " lx", 0, 65535, 1, 0, array()),
			"GeCoS.MB"       => array(VARIABLETYPE_FLOAT, "Information", " MB", 0, 1000000, 0.1, 1, array()),
			"GeCoS.Mhz"      => array(VARIABLETYPE_FLOAT, "Speedo", " MHz", 0, 10000, 0.1, 1, array()),
			"GeCoS.V"        => array(VARIABLETYPE_FLOAT, "Electricity", " V", -100000, 100000, 0.1, 3, array()),
			"Intensity.4096" => array(VARIABLETYPE_INTEGER, "Intensity", " %", 0, 4095, 1, 0, array()),
			"GeCoS.AirQuality" => array(VARIABLETYPE_INTEGER, "Information", "", 0, 6, 1, 0, array(
				array(0, "Calibration", -1), array(1, "good", 0x58FA58), array(2, "average", 0xF7FE2E),
				array(3, "slightly bad", 0xFE9A2E), array(4, "bad", 0xFF0000), array(5, "worse", 0x61380B),
				array(6, "very bad", 0x000000))),
			"IPS2CeCoSIO.ActiveInactive" => array(VARIABLETYPE_BOOLEAN, "Information", "", 0, 0, 0, 0, array(
				array(0, "Inactive", 0xFF0000), array(1, "Active", 0x00FF00)))
		);
		if (!array_key_exists($Name, $Profiles)) {
			return;
		}
		list($Type, $Icon, $Suffix, $Min, $Max, $Step, $Digits, $Associations) = $Profiles[$Name];
		IPS_CreateVariableProfile($Name, $Type);
		IPS_SetVariableProfileIcon($Name, $Icon);
		IPS_SetVariableProfileText($Name, "", $Suffix);
		if ($Type != VARIABLETYPE_BOOLEAN) {
			IPS_SetVariableProfileValues($Name, $Min, $Max, $Step);
		}
		if ($Type == VARIABLETYPE_FLOAT) {
			IPS_SetVariableProfileDigits($Name, $Digits);
		}
		foreach ($Associations as $Association) {
			IPS_SetVariableProfileAssociation($Name, $Association[0], $this->Translate($Association[1]), "", $Association[2]);
		}
	}
}

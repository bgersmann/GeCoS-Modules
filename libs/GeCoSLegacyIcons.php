<?php

// Klassische WebFront-Icons statt der neuen Icons, gesteuert über die Eigenschaft "LegacyIcons"
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

	private function SwitchPresentation(): array
	{
		$Presentation = array("PRESENTATION" => VARIABLE_PRESENTATION_SWITCH);
		if ($this->ReadPropertyBoolean("LegacyIcons")) {
			$Presentation["ICON_TRUE"] = "Power";
			$Presentation["ICON_FALSE"] = "Power";
		}
		return $Presentation;
	}
}

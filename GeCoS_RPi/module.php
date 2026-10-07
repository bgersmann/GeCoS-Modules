<?php
    // Klassendefinition
    class GeCoS_RPi extends IPSModuleStrict 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create(): void
        {
		// Diese Zeile nicht löschen.
		parent::Create();
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'GeCoSRPi_Measurement_1($_IPS["TARGET"]);');
		
		// Darstellungen
		$MB = array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "SUFFIX" => " MB", "DIGITS" => 1, "ICON" => "memory");
		$MHz = array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "SUFFIX" => " MHz", "DIGITS" => 1, "ICON" => "gauge-high");
		
		//Status-Variablen anlegen
		$this->RegisterVariableString("Board", $this->Translate("Board"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION), 10);
		$this->DisableAction("Board");
		$this->RegisterVariableString("Revision", $this->Translate("Revision"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION), 20);
		$this->DisableAction("Revision");
		$this->RegisterVariableString("Hardware", $this->Translate("Hardware"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION), 30);
		$this->DisableAction("Hardware");
		$this->RegisterVariableString("Serial", $this->Translate("Serial"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION), 40);
		$this->DisableAction("Serial");
		$this->RegisterVariableString("Software", $this->Translate("Software"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION), 50);
		$this->DisableAction("Software");
		$this->RegisterVariableFloat("MemoryCPU", $this->Translate("Memory CPU"), $MB, 60);
		$this->DisableAction("MemoryCPU");
		$this->RegisterVariableFloat("MemoryGPU", $this->Translate("Memory GPU"), $MB, 70);
		$this->DisableAction("MemoryGPU");
		$this->RegisterVariableString("Hostname", $this->Translate("Hostname"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION), 80);
		$this->DisableAction("Hostname");
		$this->RegisterVariableString("Uptime", $this->Translate("Uptime"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION), 90);
		$this->DisableAction("Uptime");
		// CPU/GPU
		$this->RegisterVariableFloat("TemperaturCPU", $this->Translate("Temperature CPU"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "SUFFIX" => " °C", "DIGITS" => 1, "ICON" => "temperature-half", "USAGE_TYPE" => 1), 100);
		$this->DisableAction("TemperaturCPU");
		$this->RegisterVariableFloat("TemperaturGPU", $this->Translate("Temperature GPU"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "SUFFIX" => " °C", "DIGITS" => 1, "ICON" => "temperature-half", "USAGE_TYPE" => 1), 110);
		$this->DisableAction("TemperaturGPU");
		$this->RegisterVariableFloat("VoltageCPU", $this->Translate("Voltage CPU"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "SUFFIX" => " V", "DIGITS" => 2, "ICON" => "bolt"), 120);
		$this->DisableAction("VoltageCPU");
		$this->RegisterVariableFloat("ARM_Frequenzy", $this->Translate("ARM Frequenzy"), $MHz, 130);
		$this->DisableAction("ARM_Frequenzy");
		// CPU Auslastung
		$this->RegisterVariableFloat("AverageLoad", $this->Translate("CPU AverageLoad"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "PERCENTAGE" => true, "MIN" => 0, "MAX" => 1, "SUFFIX" => " %", "DIGITS" => 1, "ICON" => "gauge"), 140);
		$this->DisableAction("AverageLoad");
		$this->SetBuffer("PrevTotal", 0);
		$this->SetBuffer("PrevIdle", 0);
		// Arbeitsspeicher
		$this->RegisterVariableFloat("MemoryTotal", $this->Translate("Memory Total"), $MB, 200);
		$this->DisableAction("MemoryTotal");
		$this->RegisterVariableFloat("MemoryFree", $this->Translate("Memory Free"), $MB, 210);
		$this->DisableAction("MemoryFree");
		$this->RegisterVariableFloat("MemoryAvailable", $this->Translate("Memory Available"), $MB, 220);
		$this->DisableAction("MemoryAvailable");
		// SD-Card
		$this->RegisterVariableFloat("SD_Card_Total", $this->Translate("SD-Card Total"), $MB, 300);
		$this->DisableAction("SD_Card_Total");
		$this->RegisterVariableFloat("SD_Card_Used", $this->Translate("SD-Card Used"), $MB, 310);
		$this->DisableAction("SD_Card_Used");
		$this->RegisterVariableFloat("SD_Card_Available", $this->Translate("SD-Card Available"), $MB, 320);
		$this->DisableAction("SD_Card_Available");
		$this->RegisterVariableFloat("SD_Card_Used_rel", $this->Translate("SD-Card Used (rel)"), array("PRESENTATION" => VARIABLE_PRESENTATION_VALUE_PRESENTATION, "PERCENTAGE" => true, "MIN" => 0, "MAX" => 1, "SUFFIX" => " %", "DIGITS" => 1, "ICON" => "gauge"), 330);
		$this->DisableAction("SD_Card_Used_rel");
      }

	public function GetConfigurationForm(): string
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instance is being created"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instance is active");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instance is inactive");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instance is faulty");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Active"); 
 			
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Seconds");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");

		$arrayElements[] = array("type" => "Button", "label" => "Manufacturer information", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/'");
	
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "These functions are only available after the required data has been entered and applied!");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}           
	    
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges(): void
        {
                 // Diese Zeile nicht löschen
                 parent::ApplyChanges();
		
                If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			//ReceiveData-Filter setzen
			$Filter = '(.*"Function":"get_start_trigger".*|.*"InstanceID":'.$this->InstanceID.'.*)';
			$this->SetReceiveDataFilter($Filter);
							
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
				$this->Measurement();
				$this->Measurement_1();
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
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
			$this->SetTimerInterval("Messzyklus", 0);
		}
	}
	
	public function ReceiveData(string $JSONString): string
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "set_RPi_connect":
				$ResultArray = unserialize(hex2bin($data->Result));
				If ($data->CommandNumber == 0) {
					for ($i = 0; $i < Count($ResultArray); $i++) {
						switch(key($ResultArray)) {
							case "0":
								// Betriebssystem
								$Result = $ResultArray[key($ResultArray)];
								$this->SetValue("Software", $Result);
								break;
							case "1":
								// Hardware-Daten
								$HardwareArray = explode("\n", $ResultArray[key($ResultArray)]);
								for ($j = 0; $j <= Count($HardwareArray) - 1; $j++) {
								    	If (Substr($HardwareArray[$j], 0, 8) == "Hardware") {
										$PartArray = explode(":", $HardwareArray[$j]);
										$this->SetValue("Hardware", trim($PartArray[1]));
									}
									If (Substr($HardwareArray[$j], 0, 8) == "Revision") {
										$PartArray = explode(":", $HardwareArray[$j]);
										$this->SetValue("Revision", trim($PartArray[1]));
										$this->SetValue("Board", $this->GetHardware(hexdec($PartArray[1])) );
									}
									If (Substr($HardwareArray[$j], 0, 6) == "Serial") {
										$PartArray = explode(":", $HardwareArray[$j]);
										$this->SetValue("Serial", trim($PartArray[1]));
									}
								}
								break;
							case "2":
								// CPU Speicher
								$Result = intval(substr($ResultArray[key($ResultArray)], 4, -1));
								$this->SetValue("MemoryCPU", $Result);
								break;
							case "3":
								// GPU Speicher
								$Result = intval(substr($ResultArray[key($ResultArray)], 4, -1));
								$this->SetValue("MemoryGPU", $Result);
								break;
							case "4":
								// Hostname
								$Result = trim($ResultArray[key($ResultArray)]);
								$this->SetValue("Hostname", $Result);
								$this->SetSummary($Result);
								break;
							
						}
						Next($ResultArray);
					}
				}
				elseIf ($data->CommandNumber == 1) {
					for ($i = 0; $i < Count($ResultArray); $i++) {
						switch(key($ResultArray)) {
							case "0":
								// GPU Temperatur
								$Result = floatval(substr($ResultArray[key($ResultArray)], 5, -2));
								$this->SetValue("TemperaturGPU", $Result);
								break;
							case "1":
								// CPU Temperatur
								$Result = floatval(intval($ResultArray[key($ResultArray)]) / 1000);
								$this->SetValue("TemperaturCPU", $Result);
								break;
							case "2":
								// CPU Spannung
								$Result = floatval(substr($ResultArray[key($ResultArray)], 5, -1));
								$this->SetValue("VoltageCPU", $Result);
								break;
							case "3":
								// ARM Frequenz
								$Result = intval(substr($ResultArray[key($ResultArray)], 14))/1000000;
								$this->SetValue("ARM_Frequenzy", $Result);
								break;
							case "4":
								// CPU Auslastung über proc/stat
								$LoadAvgArray = explode("\n", $ResultArray[key($ResultArray)]);
								$LineOneArray = explode(" ", $LoadAvgArray[0]);
								// Array mit "cpu" und "" löschen
								unset($LineOneArray[array_search("cpu", $LineOneArray)]);
								unset($LineOneArray[array_search("", $LineOneArray)]);
								// Array neu durchnummerieren
								$LineOneArray = array_merge($LineOneArray);
								If (count($LineOneArray) >= 8) {
									//IPS_LogMessage("IPS2GPIO RPi", serialize($LineOneArray));
									// Idle = idle + iowait
									$Idle = intval($LineOneArray[3]) + intval($LineOneArray[4]);
									// NonIdle = user+nice+system+irq+softrig+steal
									$NonIdle = intval($LineOneArray[0]) + intval($LineOneArray[1]) + intval($LineOneArray[2]) + intval($LineOneArray[5]) + intval($LineOneArray[6]) + intval($LineOneArray[7]);
									// Total = Idle + NonIdle
									$Total = $Idle + $NonIdle;
									// Differenzen berechnen
									$TotalDiff = $Total - intval($this->GetBuffer("PrevTotal"));
									$IdleDiff = $Idle - intval($this->GetBuffer("PrevIdle"));
									// Auslastung berechnen
									$CPU_Usage = (($TotalDiff - $IdleDiff) / $TotalDiff);
									// Wert nur ausgeben, wenn der Buffer schon einmal mit den aktuellen Werten beschrieben wurde
									If (intval($this->GetBuffer("PrevTotal")) + intval($this->GetBuffer("PrevIdle")) > 0) {
										//IPS_LogMessage("IPS2GPIO RPi", "CPU-Auslastung bei ".$CPU_Usage."%");
										$this->SetValue("AverageLoad", $CPU_Usage);
									}
									else {
										$this->SetValue("AverageLoad", 0);
									}
									// Aktuelle Werte für die nächste Berechnung in den Buffer schreiben
									$this->SetBuffer("PrevTotal", $Total);
									$this->SetBuffer("PrevIdle", $Idle);
								}
								else {
									$this->SetValue("AverageLoad", 0);
									$this->LogMessage($this->Translate("An unknown error occurred while calculating the CPU usage!"), KL_WARNING);
								}
								break;
							case "5":
								// Speicher
								$MemArray = explode("\n", $ResultArray[key($ResultArray)]);
								$this->SetValue("MemoryTotal", intval(substr($MemArray[0], 16, -3)) / 1000);
								$this->SetValue("MemoryFree", intval(substr($MemArray[1], 16, -3)) / 1000);
								$this->SetValue("MemoryAvailable", intval(substr($MemArray[2], 16, -3)) / 1000);
								break;
							case "6":
								// SD-Card
								$Result = trim(substr($ResultArray[key($ResultArray)], 10, -4));
								// Array anhand der Leerzeichen trennen
								$MemArray = explode(" ", $Result);
								// Leere ArrayValues löschen
								$MemArray = array_filter($MemArray);
								// Array neu durchnummerieren
								$MemArray = array_merge($MemArray);
								//IPS_LogMessage("IPS2GPIO RPi", serialize($MemArray));
								$this->SetValue("SD_Card_Total", intval($MemArray[0]) / 1000);
								$this->SetValue("SD_Card_Used", intval($MemArray[1]) / 1000);
								$this->SetValue("SD_Card_Available", intval($MemArray[2]) / 1000);
								$this->SetValue("SD_Card_Used_rel", intval($MemArray[3]) / 100 );
								break;
							case "7":
								// Uptime
								$UptimeArray = explode(",", $ResultArray[key($ResultArray)]);
								$pos = strpos($UptimeArray[0], "days");
								if ($pos !== false) {
								    $this->SetValue("Uptime", trim(substr($UptimeArray[0].$UptimeArray[1], 12)));
								} else {
								    $this->SetValue("Uptime", trim(substr($UptimeArray[0], 12)));
								}
								//IPS_LogMessage("IPS2GPIO RPi", $ResultArray[key($ResultArray)]);
								break;
						}
						Next($ResultArray);
					}
				}
				break;
			case "get_start_trigger":
			   	$this->ApplyChanges();
				break;
	 	}
		return "";
	}
	
	// Beginn der Funktionen
	public function Measurement(): void
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND (IPS_GetKernelRunlevel() == 10103)) {
			// Daten werden nur einmalig nach Start oder bei Änderung eingelesen
			$CommandArray = Array();
			// Betriebsystem
			$CommandArray[0] = "cat /proc/version";
			// Hardware-Daten
			$CommandArray[1] = "cat /proc/cpuinfo";
			// CPU Speicher
			$CommandArray[2] = "vcgencmd get_mem arm";
			// GPU Speicher
			$CommandArray[3] = "vcgencmd get_mem gpu";
			// Hostname
			$CommandArray[4] = "hostname";
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => serialize($CommandArray), "CommandNumber" => 0, "IsArray" => true )));
		}
	}
	    
	    
	 // Führt eine Messung aus
	public function Measurement_1(): void
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND (IPS_GetKernelRunlevel() == 10103)) {
			$CommandArray = Array();
			// GPU Temperatur
			$CommandArray[0] = "vcgencmd measure_temp";
			// CPU Temperatur
			$CommandArray[1] = "cat /sys/class/thermal/thermal_zone0/temp";
			// Spannung
			$CommandArray[2] = "vcgencmd measure_volts";
			// ARM Frequenz
			$CommandArray[3] = "vcgencmd measure_clock arm";
			// CPU Auslastung über /proc/stat
			$CommandArray[4] = "cat /proc/stat";
			// Speicher
			$CommandArray[5] = "cat /proc/meminfo | grep Mem";
			// SD-Card
			$CommandArray[6] = "df -P | grep /dev/root";
			// Uptime
			$CommandArray[7] = "uptime";
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => serialize($CommandArray), "CommandNumber" => 1, "IsArray" => true )));
		}
	}
 	
	public function PiReboot(): void
	{
		$Command = "sudo reboot";
		$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => $Command, "CommandNumber" => 3, "IsArray" => false )));
	}    
	
	public function PiShutdown(): void
	{
		$Command = "sudo shutdown –h 0";
		$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => $Command, "CommandNumber" => 3, "IsArray" => false )));
	}       
	    
	public function SetDisplayPower(bool $Value): void
	{
		If ($Value == true) {
			$Status = 1;
		}
		else {
			$Status = 0;
		}
		$Command = "vcgencmd display_power ".$Status;
		$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => $Command, "CommandNumber" => 3, "IsArray" => false )));
	}       
	    
	
	
	
	private function GetHardware(Int $RevNumber)
	{
		$Hardware = array(2 => "Rev.0002 Model B PCB-Rev. 1.0 256MB", 3 => "Rev.0003 Model B PCB-Rev. 1.0 256MB", 4 => "Rev.0004 Model B PCB-Rev. 2.0 256MB Sony", 5 => "Rev.0005 Model B PCB-Rev. 2.0 256MB Qisda", 
			6 => "Rev.0006 Model B PCB-Rev. 2.0 256MB Egoman", 7 => "Rev.0007 Model A PCB-Rev. 2.0 256MB Egoman", 8 => "Rev.0008 Model A PCB-Rev. 2.0 256MB Sony", 9 => "Rev.0009 Model A PCB-Rev. 2.0 256MB Qisda",
			13 => "Rev.000d Model B PCB-Rev. 2.0 512MB Egoman", 14 => "Rev.000e Model B PCB-Rev. 2.0 512MB Sony", 15 => "Rev.000f Model B PCB-Rev. 2.0 512MB Qisda", 16 => "Rev.0010 Model B+ PCB-Rev. 1.0 512MB Sony",
			17 => "Rev.0011 Compute Module PCB-Rev. 1.0 512MB Sony", 18 => "Rev.0012 Model A+ PCB-Rev. 1.1 256MB Sony", 19 => "Rev.0013 Model B+ PCB-Rev. 1.2 512MB", 20 => "Rev.0014 Compute Module PCB-Rev. 1.0 512MB Embest",
			21 => "Rev.0015 Model A+ PCB-Rev. 1.1 256/512MB Embest", 10489920 => "Rev.a01040 2 Model B PCB-Rev. 1.0 1GB", 10489921 => "Rev.a01041 2 Model B PCB-Rev. 1.1 1GB Sony", 10620993 => "Rev.a21041 2 Model B PCB-Rev. 1.1 1GB Embest",
			10625090 => "Rev.a22042 2 Model B PCB-Rev. 1.2 1GB Embest", 9437330 => "Rev.900092 Zero PCB-Rev. 1.2 512MB Sony", 9437331 => "Rev.900093 Zero PCB-Rev. 1.3 512MB Sony", 10494082 => "Rev.a02082 3 Model B PCB-Rev. 1.2 1GB Sony",
			10625154 => "Rev.a22082 3 Model B PCB-Rev. 1.2 1GB Embest", 44044353 => "Rev.2a01041 2 Model B PCB-Rev. 1.1 1GB Sony (overvoltage)", 10494163 => "Rev.a020d3 3 Model B+ PCB-Rev. 1.3 1GB Sony");
		If (array_key_exists($RevNumber, $Hardware)) {
			$HardwareText = $Hardware[$RevNumber];
		}
		else {
			$HardwareText = "Unbekannte Revisions Nummer!";
		}
	return $HardwareText;
	}
}
?>

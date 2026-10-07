<?php
    // Klassendefinition
    class GeCoS_RGBW extends IPSModuleStrict 
    {
	// PCA9685
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create(): void
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
 	    	$this->RegisterPropertyInteger("DeviceAddress", 88);
		$this->RegisterPropertyInteger("DeviceBus", 0);
		
		// Profil anlegen
		$Intensity = array("PRESENTATION" => VARIABLE_PRESENTATION_SLIDER, "MIN" => 0, "MAX" => 4095, "STEP_SIZE" => 1, "PERCENTAGE" => true, "SUFFIX" => " %", "DIGITS" => 0, "ICON" => "lightbulb");
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 3; $i++) {
			$this->RegisterVariableBoolean("Status_RGB_".($i + 1), sprintf($this->Translate("Status RGB %d"), $i + 1), array("PRESENTATION" => VARIABLE_PRESENTATION_SWITCH), 10 + ($i * 70));
			$this->EnableAction("Status_RGB_".($i + 1));
			$this->RegisterVariableInteger("Color_RGB_".($i + 1), sprintf($this->Translate("Color %d"), $i + 1), array("PRESENTATION" => VARIABLE_PRESENTATION_COLOR), 20 + ($i * 70));
			$this->EnableAction("Color_RGB_".($i + 1));
			$this->RegisterVariableInteger("Intensity_R_".($i + 1), sprintf($this->Translate("Intensity red %d"), $i + 1), $Intensity, 30 + ($i * 70) );
			$this->EnableAction("Intensity_R_".($i + 1));
			$this->RegisterVariableInteger("Intensity_G_".($i + 1), sprintf($this->Translate("Intensity green %d"), $i + 1), $Intensity, 40 + ($i * 70));
			$this->EnableAction("Intensity_G_".($i + 1));
			$this->RegisterVariableInteger("Intensity_B_".($i + 1), sprintf($this->Translate("Intensity blue %d"), $i + 1), $Intensity, 50 + ($i * 70));
			$this->EnableAction("Intensity_B_".($i + 1));
			$this->RegisterVariableBoolean("Status_W_".($i + 1), sprintf($this->Translate("Status white %d"), $i + 1), array("PRESENTATION" => VARIABLE_PRESENTATION_SWITCH), 60 + ($i * 70));
			$this->EnableAction("Status_W_".($i + 1));
			$this->RegisterVariableInteger("Intensity_W_".($i + 1), sprintf($this->Translate("Intensity white %d"), $i + 1), $Intensity, 70 + ($i * 70));
			$this->EnableAction("Intensity_W_".($i + 1));			
		}
		$this->RegisterVariableBoolean("Status_RGB_5", $this->Translate("Status RGB all"), array("PRESENTATION" => VARIABLE_PRESENTATION_SWITCH), 290);
		$this->EnableAction("Status_RGB_5");
		$this->RegisterVariableInteger("Color_RGB_5", $this->Translate("Color all"), array("PRESENTATION" => VARIABLE_PRESENTATION_COLOR), 300);
		$this->EnableAction("Color_RGB_5");
		$this->RegisterVariableInteger("Intensity_R_5", $this->Translate("Intensity red all"), $Intensity, 310);
		$this->EnableAction("Intensity_R_5");
		$this->RegisterVariableInteger("Intensity_G_5", $this->Translate("Intensity green all"), $Intensity, 320);
		$this->EnableAction("Intensity_G_5");
		$this->RegisterVariableInteger("Intensity_B_5", $this->Translate("Intensity blue all"), $Intensity, 330);
		$this->EnableAction("Intensity_B_5");
		$this->RegisterVariableBoolean("Status_W_5", $this->Translate("Status white all"), array("PRESENTATION" => VARIABLE_PRESENTATION_SWITCH), 340);
		$this->EnableAction("Status_W_5");
		$this->RegisterVariableInteger("Intensity_W_5", $this->Translate("Intensity white all"), $Intensity, 350);
		$this->EnableAction("Intensity_W_5");	
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
 		
		$arrayOptions = array();
		for ($i = 88; $i <= 95; $i++) {
		    	$arrayOptions[] = array("label" => $i." / 0x".strtoupper(dechex($i))."", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device address", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "GeCoS I²C bus 0", "value" => 0);
		$arrayOptions[] = array("label" => "GeCoS I²C bus 1", "value" => 1);
		$arrayOptions[] = array("label" => "GeCoS I²C bus 2", "value" => 2);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "GeCoS I²C bus", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "label" => "Manufacturer information", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/'");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Test Center"); 
		$arrayElements[] = array("type" => "TestCenter", "name" => "TestCenter");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}           
	  
        public function ApplyChanges(): void
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Summary setzen
		$this->SetSummary("0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." - I²C-Bus ".($this->ReadPropertyInteger("DeviceBus")));
		
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
			case "SRGBW":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$Group = intval($data->Group) + 1;
					$StateRGB = boolval($data->StateRGB);
					$StateW = boolval($data->StateW);
					$IntensityR = intval($data->IntensityR); 
					$IntensityG = intval($data->IntensityG);
					$IntensityB = intval($data->IntensityB);
					$IntensityW = intval($data->IntensityW);
					$this->SendDebug("ReceiveData", "SRGBW Group: ".$Group." StateRGB: ".$StateRGB." StateW: ".$StateW." IntensityR: ".$IntensityR." IntensityG: ".$IntensityG." IntensityB: ".$IntensityB." IntensityW: ".$IntensityW, 0);
					// Statusvariablen setzen
					If ($this->GetValue("Status_RGB_".$Group) <> $StateRGB) {
						$this->SetValue("Status_RGB_".$Group, $StateRGB);
					}
					If ($this->GetValue("Status_W_".$Group) <> $StateW) {
						$this->SetValue("Status_W_".$Group, $StateW);
					}
					If ($this->GetValue("Intensity_R_".$Group) <> $IntensityR) {
						$this->SetValue("Intensity_R_".$Group, $IntensityR);
					}
					If ($this->GetValue("Intensity_G_".$Group) <> $IntensityG) {
						$this->SetValue("Intensity_G_".$Group, $IntensityG);
					}
					If ($this->GetValue("Intensity_B_".$Group) <> $IntensityB) {
						$this->SetValue("Intensity_B_".$Group, $IntensityB);
					}
					If ($this->GetValue("Intensity_W_".$Group) <> $IntensityW) {
						$this->SetValue("Intensity_W_".$Group, $IntensityW);
					}
					// Werte skalieren
					$Value_R = intval(255 / 4095 * $IntensityR);
					$Value_G = intval(255 / 4095 * $IntensityG);
					$Value_B = intval(255 / 4095 * $IntensityB);
					$this->SetValue("Color_RGB_".$Group, $this->RGB2Hex($Value_R, $Value_G, $Value_B));
				}
				break;
			case "RGBW":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$Group = intval($data->Group) + 1;
					$StateRGB = boolval($data->StateRGB);
					$StateW = boolval($data->StateW);
					$IntensityR = intval($data->IntensityR); 
					$IntensityG = intval($data->IntensityG);
					$IntensityB = intval($data->IntensityB);
					$IntensityW = intval($data->IntensityW);
					$this->SendDebug("ReceiveData", "RGBW Group: ".$Group." StateRGB: ".$StateRGB." StateW: ".$StateW." IntensityR: ".$IntensityR." IntensityG: ".$IntensityG." IntensityB: ".$IntensityB." IntensityW: ".$IntensityW, 0);
					// Statusvariablen setzen
					If ($this->GetValue("Status_RGB_".$Group) <> $StateRGB) {
						$this->SetValue("Status_RGB_".$Group, $StateRGB);
					}
					If ($this->GetValue("Status_W_".$Group) <> $StateW) {
						$this->SetValue("Status_W_".$Group, $StateW);
					}
					If ($this->GetValue("Intensity_R_".$Group) <> $IntensityR) {
						$this->SetValue("Intensity_R_".$Group, $IntensityR);
					}
					If ($this->GetValue("Intensity_G_".$Group) <> $IntensityG) {
						$this->SetValue("Intensity_G_".$Group, $IntensityG);
					}
					If ($this->GetValue("Intensity_B_".$Group) <> $IntensityB) {
						$this->SetValue("Intensity_B_".$Group, $IntensityB);
					}
					If ($this->GetValue("Intensity_W_".$Group) <> $IntensityW) {
						$this->SetValue("Intensity_W_".$Group, $IntensityW);
					}
					// Werte skalieren
					$Value_R = intval(255 / 4095 * $IntensityR);
					$Value_G = intval(255 / 4095 * $IntensityG);
					$Value_B = intval(255 / 4095 * $IntensityB);
					$this->SetValue("Color_RGB_".$Group, $this->RGB2Hex($Value_R, $Value_G, $Value_B));
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
		$Parts = explode("_", $Ident);
		$Source = $Parts[0]."_".$Parts[1];
		$Group = $Parts[2];
		
		switch($Source) {
		case "Status_RGB":
			If ($Group <= 4) {
				$this->SetOutputPinStateRGB($Group, $Value);
			}
			elseif ($Group == 5) {
				$this->SetValue($Ident, $Value);
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinStateRGB($i, $Value);
				}
			}
	            	break;
		case "Status_W":
			If ($Group <= 4) {
				$this->SetOutputPinStateW($Group, $Value);
			}
			elseif ($Group == 5) {
				$this->SetValue($Ident, $Value);
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinStateW($i, $Value);					
				}
			}
	            	break;
		case "Intensity_R":
	            	If ($Group <= 4) {
				$this->SetOutputPinValueR($Group, $Value);
			}
			elseif ($Group == 5) {
				// ColorPicker und Slider setzen
				$this->SetValue($Ident, $Value);
				$this->SetAllColor();
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinValueR($i, $Value);
				}
			}
	            	break;
		case "Intensity_G":
	            	If ($Group <= 4) {
				$this->SetOutputPinValueG($Group, $Value);
			}
			elseif ($Group == 5) {
				// ColorPicker und Slider setzen
				$this->SetValue($Ident, $Value);
				$this->SetAllColor();
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinValueG($i, $Value);
				}
			}
	            	break;
		case "Intensity_B":
	            	If ($Group <= 4) {
				$this->SetOutputPinValueB($Group, $Value);
			}
			elseif ($Group == 5) {
				// ColorPicker und Slider setzen
				$this->SetValue($Ident, $Value);
				$this->SetAllColor();
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinValueB($i, $Value);
				}
			}
	            	break;
		case "Intensity_W":
	            	If ($Group <= 4) {
				$this->SetOutputPinValueW($Group, $Value);
			}
			elseif ($Group == 5) {
				// ColorPicker und Slider setzen
				$this->SetValue($Ident, $Value);
				$this->SetAllColor();
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinValueW($i, $Value);
				}
			}
	            	break;	
		case "Color_RGB":
	            	If ($Group <= 4) {
				$this->SetOutputColor($Group, $Value);
			}
			elseif ($Group == 5) {
				$this->SetValue($Ident, $Value);
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputColor($i, $Value);
				}
			}
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
		
	}
	    
	// Beginn der Funktionen
	public function SetOutput(int $Group, bool $StateRGB, bool $StateW, int $IntensityR, int $IntensityG, int $IntensityB, int $IntensityW): void
	{
		//{RGBW;I2C-Kanal;Adresse;RGBWKanal;StatusRGB;StatusW;R;G;B;W}
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Ausgang setzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "RGBW", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "Group" => ($Group - 1), 
									    "StateRGB" => $StateRGB, "StateW" => $StateW, "IntensityR" => $IntensityR, "IntensityG" => $IntensityG, "IntensityB" => $IntensityB, "IntensityW" => $IntensityW )));
		}
	}
	    
	public function SetOutputPinStateRGBW(int $Group, bool $StateRGBW): void
	{ 
		$this->SendDebug("SetOutputPinStateRGB", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$StateRGBW = min(1, max(0, $StateRGBW));
		//$StateW = $this->GetValue("Status_W_".$Group);
		//$StatusRGB = $this->GetValue("Status_RGB_".$Group);
		$IntensityR = $this->GetValue("Intensity_R_".$Group);
		$IntensityG = $this->GetValue("Intensity_G_".$Group);
		$IntensityB = $this->GetValue("Intensity_B_".$Group);
		$IntensityW = $this->GetValue("Intensity_W_".$Group);	
		
		$this->SetOutput($Group, $StateRGBW, $StateRGBW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}    	
			
	public function SetOutputPinStateRGB(int $Group, bool $StateRGB): void
	{ 
		$this->SendDebug("SetOutputPinStateRGB", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$StateRGB = min(1, max(0, $StateRGB));
		$StateW = $this->GetValue("Status_W_".$Group);
		//$StatusRGB = $this->GetValue("Status_RGB_".$Group);
		$IntensityR = $this->GetValue("Intensity_R_".$Group);
		$IntensityG = $this->GetValue("Intensity_G_".$Group);
		$IntensityB = $this->GetValue("Intensity_B_".$Group);
		$IntensityW = $this->GetValue("Intensity_W_".$Group);	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}    	    
	
	public function SetOutputPinStateW(int $Group, bool $StateW): void
	{ 
		$this->SendDebug("SetOutputPinStateW", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$StateW = min(1, max(0, $StateW));
		//$StateW = $this->GetValue("Status_W_".$Group);
		$StateRGB = $this->GetValue("Status_RGB_".$Group);
		$IntensityR = $this->GetValue("Intensity_R_".$Group);
		$IntensityG = $this->GetValue("Intensity_G_".$Group);
		$IntensityB = $this->GetValue("Intensity_B_".$Group);
		$IntensityW = $this->GetValue("Intensity_W_".$Group);	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}      
	 
	public function SetOutputPinValueR(int $Group, int $IntensityR): void
	{ 
		$this->SendDebug("SetOutputPinValueR", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$IntensityR = min(4095, max(0, $IntensityR));
		$StateW = $this->GetValue("Status_W_".$Group);
		$StateRGB = $this->GetValue("Status_RGB_".$Group);
		//$IntensityR = $this->GetValue("Intensity_R_".$Group);
		$IntensityG = $this->GetValue("Intensity_G_".$Group);
		$IntensityB = $this->GetValue("Intensity_B_".$Group);
		$IntensityW = $this->GetValue("Intensity_W_".$Group);	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}         
	
	public function SetOutputPinValueG(int $Group, int $IntensityG): void
	{ 
		$this->SendDebug("SetOutputPinValueG", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$IntensityG = min(4095, max(0, $IntensityG));
		$StateW = $this->GetValue("Status_W_".$Group);
		$StateRGB = $this->GetValue("Status_RGB_".$Group);
		$IntensityR = $this->GetValue("Intensity_R_".$Group);
		//$IntensityG = $this->GetValue("Intensity_G_".$Group);
		$IntensityB = $this->GetValue("Intensity_B_".$Group);
		$IntensityW = $this->GetValue("Intensity_W_".$Group);	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}            
	
	public function SetOutputPinValueB(int $Group, int $IntensityB): void
	{ 
		$this->SendDebug("SetOutputPinValueB", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$IntensityB = min(4095, max(0, $IntensityB));
		$StateW = $this->GetValue("Status_W_".$Group);
		$StateRGB = $this->GetValue("Status_RGB_".$Group);
		$IntensityR = $this->GetValue("Intensity_R_".$Group);
		$IntensityG = $this->GetValue("Intensity_G_".$Group);
		//$IntensityB = $this->GetValue("Intensity_B_".$Group);
		$IntensityW = $this->GetValue("Intensity_W_".$Group);	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}            
	
	public function SetOutputPinValueW(int $Group, int $IntensityW): void
	{ 
		$this->SendDebug("SetOutputPinValueR", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$IntensityW = min(4095, max(0, $IntensityW));
		$StateW = $this->GetValue("Status_W_".$Group);
		$StateRGB = $this->GetValue("Status_RGB_".$Group);
		$IntensityR = $this->GetValue("Intensity_R_".$Group);
		$IntensityG = $this->GetValue("Intensity_G_".$Group);
		$IntensityB = $this->GetValue("Intensity_B_".$Group);
		//$IntensityW = $this->GetValue("Intensity_W_".$Group);	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}            
	    
	public function SetOutputColor(int $Group, int $Color): void
	{
		$this->SendDebug("SetOutputColor", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		
		// Farbwerte aufsplitten
		list($Value_R, $Value_G, $Value_B) = $this->Hex2RGB($Color);
		// Werte skalieren
		$IntensityR = 4095 / 255 * $Value_R;
		$IntensityG = 4095 / 255 * $Value_G;
		$IntensityB = 4095 / 255 * $Value_B;
		
		$StateW = $this->GetValue("Status_W_".$Group);
		$StateRGB = $this->GetValue("Status_RGB_".$Group);
		//$IntensityR = $this->GetValue("Intensity_R_".$Group);
		//$IntensityG = $this->GetValue("Intensity_G_".$Group);
		//$IntensityB = $this->GetValue("Intensity_B_".$Group);
		$IntensityW = $this->GetValue("Intensity_W_".$Group);	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}
	    
	private function SetAllColor()
	{
		// Werte skalieren
		$Value_R = intval(255 / 4095 * $this->GetValue("Intensity_R_5"));
		$Value_G = intval(255 / 4095 * $this->GetValue("Intensity_G_5"));
		$Value_B = intval(255 / 4095 * $this->GetValue("Intensity_B_5"));
		$this->SetValue("Color_RGB_5", $this->RGB2Hex($Value_R, $Value_G, $Value_B));
	}
	    
	private function GetOutput(Int $Register)
	{
		$this->SendDebug("GetOutput", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "SRGBW")));
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
	    
	
	private function setBit($byte, $significance) { 
 		// ein bestimmtes Bit auf 1 setzen
 		return $byte | 1<<$significance;   
 	} 
	
	private function unsetBit($byte, $significance) {
	    // ein bestimmtes Bit auf 0 setzen
	    return $byte & ~(1<<$significance);
	}
	    
	private function Hex2RGB($Hex)
	{
		$r = (($Hex >> 16) & 0xFF);
		$g = (($Hex >> 8) & 0xFF);
		$b = (($Hex >> 0) & 0xFF);	
	return array($r, $g, $b);
	}
	
	private function RGB2Hex($r, $g, $b)
	{
		$Hex = hexdec(str_pad(dechex($r), 2,'0', STR_PAD_LEFT).str_pad(dechex($g), 2,'0', STR_PAD_LEFT).str_pad(dechex($b), 2,'0', STR_PAD_LEFT));
	return $Hex;
	}
	
	private function GetBoardVersion()
	{
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "getBoardVersion" )));	
	return $Result;
	}
	    
}
?>

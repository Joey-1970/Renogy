<?
    // Klassendefinition
    class RenogyWanderer extends IPSModule 
    {
	// https://docs.google.com/document/d/1OSW3gluYNK8d_gSz4Bk89LMQ4ZrzjQY6/edit?usp=sharing&ouid=110144688998608708274&rtpof=true&sd=true
	// https://stackoverflow.com/questions/69270827/c-sharp-modbus-protocol-renogy-wanderer-rover-20a-40a-solar-charge-controller
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		
		$this->ConnectParent("{A5F663AB-C400-4FE5-B207-4D67CC030564}"); // Modbus
		
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("TimerGetData", 10);
		$this->RegisterTimer("GetData", 0, 'RenogyWanderer_GetData($_IPS["TARGET"]);');
		
		// Profile anlegen
		$this->RegisterProfileInteger("RenogyWanderer.Voltage", "Information", "", " V", 0, 96, 0);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Voltage", 12, "12", "Information", -1);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Voltage", 24, "24", "Information", -1);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Voltage", 36, "36", "Information", -1);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Voltage", 48, "48", "Information", -1);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Voltage", 96, "96", "Information", -1);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Voltage", 255, "Auto", "Information", -1);
		
		$this->RegisterProfileInteger("RenogyWanderer.Current", "Information", "", " A", 0, 60, 0);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Current", 10, "10", "Information", -1);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Current", 20, "20", "Information", -1);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Current", 30, "30", "Information", -1);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Current", 45, "45", "Information", -1);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Current", 60, "60", "Information", -1);
		
		
		// Status-Variablen anlegen
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		$this->RegisterVariableInteger("SystemVoltage", "System Spannung", "RenogyWanderer.Voltage", 20);
		$this->RegisterVariableInteger("SystemCurrent", "System Strom", "RenogyWanderer.Current", 30);
		$this->RegisterVariableString("ProductModel", "Modell", "", 40);
		$this->RegisterVariableString("SoftwareVersion", "Software Version", "", 50);
		$this->RegisterVariableString("HardwareVersion", "Hardware Version", "", 60);
		
		
		
		$this->RegisterVariableInteger("BatteryCapacity", "Batterie Kapazität", "~Intensity.100", 100);
		$this->RegisterVariableFloat("BatteryVoltage", "Batterie Spannung", "~Volt", 110);
		$this->RegisterVariableFloat("BatteryChargingCurrent", "Batterie Ladestrom", "~Milliampere", 120);
		$this->RegisterVariableFloat("ControllerTemperature", "Controller Temperatur", "~Temperature", 130);
		$this->RegisterVariableFloat("BatteryTemperature", "Batterie Temperatur", "~Temperature", 140);
		$this->RegisterVariableFloat("StreetLightVoltage", "Ausgang Spannung", "~Volt", 150);
		$this->RegisterVariableFloat("StreetLightCurrent", "Ausgang Strom", "~Milliampere", 160);
		$this->RegisterVariableFloat("StreetLightPower", "Ausgang Leistung", "~Watt", 170);
		$this->RegisterVariableFloat("SolarPanelVoltage", "Solar Panel Spannung", "~Volt", 180);
		$this->RegisterVariableFloat("SolarPanelCurrent", "Solar Panel Strom", "~Milliampere", 190);
		$this->RegisterVariableFloat("SolarPanelPower", "Solar Panel Leistung", "~Watt", 200);
		
        }
       	
	public function GetConfigurationForm() { 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft"); 
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox", "caption" => "Aktiv"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "TimerGetData", "caption" => "Daten aktualisieren (10 - 360)", "minimum" => 10, "maximum" => 360, "suffix" => "sek");

		
			
		$arrayActions = array(); 
		$arrayActions[] = array("type" => "Label", "label" => "Test Center"); 
		$arrayActions[] = array("type" => "TestCenter", "name" => "TestCenter");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	} 
	
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                // Diese Zeile nicht löschen
                parent::ApplyChanges();
		
			
		If ($this->ReadPropertyBoolean("Open") == true) {
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
			$this->GetBasicData();
			$this->GetData();
			$this->SetTimerInterval("GetData", $this->ReadPropertyInteger("TimerGetData") * 1000);
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
			$this->SetTimerInterval("GetData", 0);
		}	   
	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
			case "VPNActive":
				
				break;
			
	      		
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
 		switch ($Message) {
			case IPS_KERNELSTARTED:
				If ($this->ReadPropertyBoolean("Open") == true) {
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
					$this->GetBasicData();
					$this->GetData();
					$this->SetTimerInterval("GetData", $this->ReadPropertyInteger("TimerGetData") * 1000);
				}
				else {
					If ($this->GetStatus() <> 104) {
						$this->SetStatus(104);
					}
					$this->SetTimerInterval("GetData", 0);
				}	  	
				break;
			
		}
    	}          
	    
    	public function GetBasicData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Response = false;
			$StatusVariables = array();
			$StatusVariables = array(
					10 => array("SystemVoltageCurrent"),
					12 => array("ProductModel_1"),
					13 => array("ProductModel_2"),
					14 => array("ProductModel_3"),
					15 => array("ProductModel_4"),
					16 => array("ProductModel_5"),
					17 => array("ProductModel_6"),
					18 => array("ProductModel_7"),
					19 => array("ProductModel_8"),
					20 => array("SoftwareVersion_1"),
					21 => array("SoftwareVersion_2"),
					22 => array("HardwareVersion_1"),
					23 => array("HardwareVersion_2")
					);
			
			$this->SetValue("LastUpdate", time() );
		
			foreach ($StatusVariables as $Key => $Values) {
				$Function = 3;
				$Address = $Key;
				$Quantity = 1;
				$Ident = $Values[0];
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => $Function, "Address" => $Address, "Quantity" => $Quantity, "Data" => ":")));
				$Result = (unpack("n*", substr($Result,2)));
				If (is_array($Result)) {
					If (count($Result) == 1) {
						$Value = $Result[1];
						
						$this->DataEvaluation($Address, $Ident, $Value);
						
						$this->SendDebug("GetData", $Ident.": ".$Value, 0);
					}
				}
			}
		}
	}
	    
	public function GetData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Response = false;
			$StatusVariables = array();
			$StatusVariables = array(
					256 => array("BatteryCapacity"), 
					257 => array("BatteryVoltage"), 
					258 => array("BatteryChargingCurrent"), 
					259 => array("ControllerBatteryTemperature"),
					260 => array("StreetLightVoltage"), 
					261 => array("StreetLightCurrent"), 
					262 => array("StreetLightPower"), 
					263 => array("SolarPanelVoltage"), 
					264 => array("SolarPanelCurrent"), 
					265 => array("SolarPanelPower"), 
					);
			
			$this->SetValue("LastUpdate", time() );
		
			foreach ($StatusVariables as $Key => $Values) {
				$Function = 3;
				$Address = $Key;
				$Quantity = 1;
				$Ident = $Values[0];
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => $Function, "Address" => $Address, "Quantity" => $Quantity, "Data" => ":")));
				$Result = (unpack("n*", substr($Result,2)));
				If (is_array($Result)) {
					If (count($Result) == 1) {
						$Value = $Result[1];
						$this->SendDebug("GetData", $Address." - ".$Ident.": ".$Value, 0);
						
						$this->DataEvaluation($Address, $Ident, $Value);
					}
				}
			}
		}
	}
	
	private function DataEvaluation(int $Address, string $Ident, int $Value)
	{
		switch($Address) {
			case "10":
				// Sytem Spannung (high 8 Bit) und Strom (low 8 Bit)
				$Voltage = $Value >> 8;
				$this->SetValueWhenChanged("SystemVoltage", $Voltage);
				$Current = $Value & 255;
				$this->SetValueWhenChanged("SystemCurrent", $Current * 1000);
				break;
			case "12":
				// Produkt Modell_1
				$ProductModel = array();
				$ProductModel[0] = $this->Hex2Str(dechex($Value));
				$this->SetBuffer("ProductModel", serialize($ProductModel));
				break;
			case "13":
				// Produkt Modell_2
				$ProductModel = unserialize($this->GetBuffer("ProductModel"));
				$ProductModel[1] = $this->Hex2Str(dechex($Value));
				$this->SetBuffer("ProductModel", serialize($ProductModel));
				break;
			case "14":
				// Produkt Modell_3
				$ProductModel = unserialize($this->GetBuffer("ProductModel"));
				$ProductModel[2] = $this->Hex2Str(dechex($Value));
				$this->SetBuffer("ProductModel", serialize($ProductModel));
				break;
			case "15":
				// Produkt Modell_4
				$ProductModel = unserialize($this->GetBuffer("ProductModel"));
				$ProductModel[3] = $this->Hex2Str(dechex($Value));
				$this->SetBuffer("ProductModel", serialize($ProductModel));
				break;
			case "16":
				// Produkt Modell_5
				$ProductModel = unserialize($this->GetBuffer("ProductModel"));
				$ProductModel[4] = $this->Hex2Str(dechex($Value));
				$this->SetBuffer("ProductModel", serialize($ProductModel));
				break;
			case "17":
				// Produkt Modell_6
				$ProductModel = unserialize($this->GetBuffer("ProductModel"));
				$ProductModel[5] = $this->Hex2Str(dechex($Value));
				$this->SetBuffer("ProductModel", serialize($ProductModel));
				break;
			case "18":
				// Produkt Modell_7
				$ProductModel = unserialize($this->GetBuffer("ProductModel"));
				$ProductModel[6] = $this->Hex2Str(dechex($Value));
				$this->SetBuffer("ProductModel", serialize($ProductModel));
				break;
			case "19":
				// Produkt Modell_8
				$ProductModel = unserialize($this->GetBuffer("ProductModel"));
				$ProductModel[7] = $this->Hex2Str(dechex($Value));
				$this->SetBuffer("ProductModel", serialize($ProductModel));
				$this->SetValueWhenChanged("ProductModel", implode($ProductModel));
				break;
			case "20":
				// Software Version_1
				$SoftwareVersion = array();
				$Value = $Value & 255;
				$SoftwareVersion[0] = dechex($Value);
				$this->SetBuffer("SoftwareVersion", serialize($SoftwareVersion));
				break;
			case "21":
				// Software Version_2
				$SoftwareVersion = unserialize($this->GetBuffer("SoftwareVersion"));
				$SoftwareVersion[1] = dechex($Value >> 8);
				$SoftwareVersion[2] = dechex($Value & 255);
				$this->SetBuffer("SoftwareVersion", serialize($SoftwareVersion));
				$this->SetValueWhenChanged("SoftwareVersion", "V".implode(".", $SoftwareVersion));
				break;
			case "22":
				// Hardware Version_1
				$HardwareVersion = array();
				$Value = $Value & 255;
				$HardwareVersion[0] = dechex($Value);
				$this->SetBuffer("HardwareVersion", serialize($HardwareVersion));
				break;
			case "23":
				// Hardware Version_2
				$HardwareVersion = unserialize($this->GetBuffer("HardwareVersion"));
				$HardwareVersion[1] = dechex($Value >> 8);
				$HardwareVersion[2] = dechex($Value & 255);
				$this->SetBuffer("HardwareVersion", serialize($HardwareVersion));
				$this->SetValueWhenChanged("HardwareVersion", "V".implode(".", $HardwareVersion));
				break;
			case "256":
				// Batterie Kapazität
				$this->SetValueWhenChanged($Ident, $Value);
				break;
			case "257":
				// Batterie Spannung
				$this->SetValueWhenChanged($Ident, $Value * 0.1);
				break;
			case "258":
				// Batterie Ladestrom
				$this->SetValueWhenChanged($Ident, $Value * 0.01 * 1000);
				break;
			case "259":
				// Controller Temperatur (high 8 Bit) und Battery Temperatur (low 8 Bit)
				$ControllerTemperature = $Value >> 8;
				$ControllerTemperature = $this->TwosComplement($ControllerTemperature);
				$this->SetValueWhenChanged("ControllerTemperature", $ControllerTemperature);
				$BatteryTemperature = $Value & 255;
				$BatteryTemperature = $this->TwosComplement($BatteryTemperature);
				$this->SetValueWhenChanged("BatteryTemperature", $BatteryTemperature);
				break;
			case "260":
				// Ausgang Spannung
				$this->SetValueWhenChanged($Ident, $Value * 0.1);
				break;
			case "261":
				// Ausgang Strom
				$this->SetValueWhenChanged($Ident, $Value * 0.01 * 1000);
				break;
			case "262":
				// Ausgang Leistung
				$this->SetValueWhenChanged($Ident, $Value);
				break;
			case "263":
				// Solar Panel Spannung
				$this->SetValueWhenChanged($Ident, $Value * 0.1);
				break;
			case "264":
				// Solar Panel Strom
				$this->SetValueWhenChanged($Ident, $Value * 0.01 * 1000);
				break;
			case "265":
				// Solar Panel Leistung
				$this->SetValueWhenChanged($Ident, $Value);
				break;
	      		
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	
	private function TwosComplement(int $Number) 
	{
    		if ($Number > 0xFF) { 
			return false; 
		}
    		if ($Number >= 0x80) {
        		return -(($Number ^ 0xFF)+1);
    		} else {
        		return $Number;
    		}
	}
	    
	private function Hex2Str($hex) 
	{
	    	$str = '';
	    	for ($i=0;$i<strlen($hex);$i+=2) $str .= chr(hexdec(substr($hex,$i,2)));
	return $str;
	}
	    
	private function SetValueWhenChanged($Ident, $Value)
    	{
        	if ($this->GetValue($Ident) != $Value) {
            		$this->SetValue($Ident, $Value);
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
	    
	private function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 2);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 2)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	        IPS_SetVariableProfileDigits($Name, $Digits);
	}


}
?>

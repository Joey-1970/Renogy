<?
    // Klassendefinition
    class RenogyWanderer extends IPSModule 
    {
	// https://docs.google.com/document/d/1OSW3gluYNK8d_gSz4Bk89LMQ4ZrzjQY6/edit?usp=sharing&ouid=110144688998608708274&rtpof=true&sd=true
	// https://stackoverflow.com/questions/69270827/c-sharp-modbus-protocol-renogy-wanderer-rover-20a-40a-solar-charge-controller
	// https://www.jpaul.me/2019/01/how-to-build-a-raspberry-pi-serial-console-server-with-ser2net/
	// https://forum.fhem.de/index.php/topic,124384.15.html
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		
		$this->ConnectParent("{A5F663AB-C400-4FE5-B207-4D67CC030564}"); // Modbus
		
            	$this->RegisterPropertyBoolean("Open", false);
		
		// Profile anlegen
		$this->RegisterProfileInteger("RenogyWanderer.Voltage", "Information", "", " V", 0, 96, 0);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Voltage", 12, "12", "Information", -1);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Voltage", 24, "24", "Information", -1);
		IPS_SetVariableProfileAssociation("RenogyWanderer.Voltaget", 36, "36", "Information", -1);
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
			
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
			
		}	   
	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
			case "VPNActive":
				If ($Value == true) {
					$this->StartVPN();
				}
				else {
					$this->StopVPN();
				}
				break;
			
	      		
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	    
    	public function GetData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Response = false;
			$StatusVariables = array();
			$StatusVariables = array(
					10 => array("SystemVoltageCurrent", 1, 0), 
					
					);
			
			$this->SetValue("LastUpdate", time() );
		
			foreach ($StatusVariables as $Key => $Values) {
				$Function = 3;
				$Address = $Key;
				$Quantity = 1;
				$Ident = $Values[0];
				$Devisor = floatval($Values[1]);
				$Signed = intval($Values[2]);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => $Function, "Address" => $Address, "Quantity" => $Quantity, "Data" => ":")));
				$Result = (unpack("n*", substr($Result,2)));
				If (is_array($Result)) {
					If (count($Result) == 1) {
						$Response = $Result[1];
						
						If ($Signed == 0) {
							$Value = ($Response/$Devisor);
						}
						else {
							$Value = $this->bin16dec($Response/$Devisor);
						}
						
						$this->DataEvaluation($Address, $Ident, $Value);
						
						$this->SendDebug("GetData", $Ident.": ".$Value, 0);
					}
				}
			}
			$this->GetSystemDate();
		}
	}
	
	private function DataEvaluation(int $Address, string $Ident, int $Value)
	{
		switch($Address) {
			case "10":
				// Sytem Spannung (high 4Bit) und Strom (low 4Bit)
				$Voltage = $Value >> 4;
				$this->SetValueWhenChanged("SystemVoltage", $Voltage);
				$Current = $Value & 15;
				$this->SetValueWhenChanged("SystemCurrent", $Current);
				break;
			
	      		
	        default:
	            throw new Exception("Invalid Ident");
	    	}
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

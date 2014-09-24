<?php

class Smart2Pay_Globalpay_Helper_Data extends Mage_Payment_Helper_Data
{
	
	public function computeSHA256Hash($message){
		if(function_exists(mb_strtolower))
			return hash("sha256", mb_strtolower($message,'UTF-8'));
		else
			return hash("sha256", strtolower($message));
	}
  
}

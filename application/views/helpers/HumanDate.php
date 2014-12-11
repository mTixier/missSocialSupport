<?php
/*
Adapté pour le français.
Thanks to : http://www.weberdev.com/get_example-4769.html 
*/

class Zend_View_Helper_HumanDate{

	public function HumanDate($timestamp, $short = false){
	
	    $difference = time() - $timestamp;
		$periods = array("seconde", "minute", "heure", "jour", "semaine", "mois", "année", "décennie");
		$lengths = array("60","60","24","7","4.35","12","10");

		if ($difference > 0) { // this was in the past
			
			$expr = "il y a";

			if($difference < 300){
				return "en ce moment";
				
			}else if($difference > (60*60*24*7*4.35*3)){
			// Si plus de 3 mois autant donné la date exact
				return date("d/m/Y", $timestamp);
			
			}
		
		}else{// this was in the future
			$difference = -$difference;
			$expr = "dans";
		}       
    
		for($j = 0; $difference >= $lengths[$j]; $j++){
			$difference /= $lengths[$j];
			$difference = round($difference);
		}

		if($difference != 1 && $periods[$j] != "mois"){
			$periods[$j].= "s";
		}
		
		if($short){
			$text = "$difference $periods[$j]";
		}else{
			$text = "$expr $difference $periods[$j]";
		}
		
		return $text; 
	}

}

?>
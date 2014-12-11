<?php
class Aloa_Form_Addtheme{

	protected $_view;
	protected $title;
	protected $entity_id;
	protected $viewpoint_id;
	protected $parent_topic_id = null;
	protected $id;
	protected $suggestionsList = array();

	

	public function __construct($ent_id, $ttl, $suggestion, $filter = null, $vpt_id = null, $parent_id = null){
		
		// Filtre sur les noms
		if($filter != null){
			if(!is_array($filter)){
				$filter = array($filter);
			}
			
			foreach($suggestion as $t){
				if(!in_array($t['name'], $filter)){
					$this->suggestionsList[] = $t;
				}
			}
			
		}else{
			$this->suggestionsList = $suggestion;
		}
		
		if($vpt_id == null){
		// Le point de vue par défaut est le point de vue communautaire
			$ct_vpt = Aloa_Hypertopic_Wrapper::getCommunityViewpoint();
			$this->viewpoint_id = $ct_vpt->viewpointId;
		}else{
			$this->viewpoint_id = $vpt_id;
		}
		
		// Si jamais les thèmes à ajouter doivent être rattaché à un thème parent
		$this->parent_topic_id = $parent_id;
		
		$this->entity_id = $ent_id;
		$this->id = "addtheme_".$ent_id;
		$this->title = $ttl;
		
	}

	public function getView(){
	
		$jscript = $this->makeSuggestion($this->suggestionsList);
		
		$frm = "<form action='/hypertopic/attachtheme2entity/".(($this->parent_topic_id != null)?"parent_id/".$this->parent_topic_id."/":"")."vpt_id/".$this->viewpoint_id."/entity_id/".$this->entity_id."' method='post' onsubmit='javascript:checkAutocomplete();'>";
		
		$frm .= "<div style='font-size:11pt'><div class='ui-widget'>
					<!--<label for='".$this->id."'> ".$this->title." : </label>-->
					<img src='/images/add.png' style='vertical-align:top;padding: 6px 5px 0px 0px;' title='".$this->title."'>
					<input id='".$this->id."' name='".$this->id."'>
					<input id='".$this->id."_id' type='hidden' name='".$this->id."_id'>
					<input type='submit' value='".$this->title."'/>
				</div></div>";
		
		
		$frm .= "</form>";
	
		$this->_view = $jscript."\n".$frm;
	
		return $this->_view;
	}
	
	protected function makeSuggestion($suggestion){
	// Formate la liste de thème à prendre en compte pour l'autocomplétion, avec le code javascript nécessaire
		$slist = "";
		
		foreach($suggestion as $t){
			$slist .= "{id : '".$t['topicid']."', value : '".$t['name']."'},";
		}
	
		// checkAutocomplete permet de résoudre le problème qui survient quand l'utilisateur écrit complètement le nom d'un thème qui existe sans le selectionné dans l'autocomplétion
		
	
		$code = "<script type='text/javascript'>
					var ".$this->id."_list = [".$slist."];
					$(function() {
						
						$('#".$this->id."').autocomplete({
							source: ".$this->id."_list,
							select: function(event, ui) {
								//$('#".$this->id."_id').val(ui.item.id);
								$('#".$this->id."').val(ui.item.value);
								return false;
							}
						});
					});
					
					
					function checkAutocomplete(){
						
						for(var elt in ".$this->id."_list){

							if((".$this->id."_list[elt].value).toLowerCase() == ((document.getElementById('".$this->id."').value).toLowerCase()).trim()){
								$('#".$this->id."_id').val(".$this->id."_list[elt].id);
								$('#".$this->id."').val(".$this->id."_list[elt].value);
								break;
							}
						}

						return false;
					}
					
				</script>";
		
		return $code;
	}
	
}


?> 
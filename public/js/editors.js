var oldContent;
var richEditor;

/* A rajouter dans la toolbox */

// Trick for IE 5 : http://verens.com/archives/2005/02/09/ie5s-documentgetelementsbytagname-deficiency-sorted/
function ie_getElementsByTagName(str) {
  // Map to the all collections
  if (str=="*")
   return document.all
  else
   return document.all.tags(str)
}

if(document.all)document.getElementsByTagName=ie_getElementsByTagName;


document.getElementsByClassName = function(cl) {
var retnode = [];
var myclass = new RegExp('\\b'+cl+'\\b');
var elem = this.getElementsByTagName('*');
for (var i = 0; i < elem.length; i++) {
var classes = elem[i].className;
if (myclass.test(classes)) retnode.push(elem[i]);
}
return retnode;
};

//

function updtRemoteData(id, field, content){

	xhr_handler = getXhrHandler();

	xhr_handler.open("POST", 'http://'+(document.location.host)+'/update', true); 
	
	xhr_handler.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	//alert('http://'+(document.location.host)+'/update');
	xhr_handler.send('id='+id+'&field='+field+'&content='+content);
	
	xhr_handler.onreadystatechange = function(){
		if(xhr_handler.readyState == 4){
			rps = xhr_handler.responseText
			msg = rps.split(':');
			if(msg[0] == 'error'){
				alert(msg[1]);
			}
		}
	};


	return true;
}


function editMeRichVersion(btn){

	if(this.className == 'edit_btn'){
		btn = this;
	}
	
	// masquer tous les boutons edit
	lbtn = document.getElementsByClassName('edit_btn');
	for(var i = 0; i < lbtn.length; i++){
		lbtn[i].style.display = "none";
		
	}

	parentRef = btn.previousSibling;
	//alert(parentRef.id);
	oldContent = parentRef.innerHTML;
	
	
	if(parentRef.nodeName == 'DIV'){
		//bkLib.onDomLoaded(function() {
		richEditor = new nicEditor({buttonList : ['bold','italic','underline','strikeThrough','forecolor','smiley','link', 'save', 'close'], 								maxHeight : 180,
								onSave : function(content, id, instance){
									
									divId = instance.e.id;
									
									//alert(content);
									// Pour se débarrasser des infos bulles de bouton restant									
									instance.ne.fireEvent("buttonOut",instance);
									
									p = divId.split('_');
									
									$res = updtRemoteData(p[0], p[1], content);
							
									if(!$res){
										alert("Suite à une erreur technique les données n'ont pu être mise à jour. Nous vous proposons de tenter de modifier vos données plus tard. Merci");
										instance.setContent(oldContent);
									}
								
									// afficher tous les boutons edit
									lbtn = document.getElementsByClassName('edit_btn');
									for(var i = 0; i < lbtn.length; i++){
										lbtn[i].style.display = "inline";
									}
									
									// Suppression du panel
									richEditor.removeInstance(divId);
									richEditor = null;
									
								},
								onClose : function(content, id, instance){
									
									// il s'agit de la bonne instance du div qui donne l'id normalement : alert(instance.e.id);
									divId = instance.e.id;
									
									/*
									for(att in instance){
										alert(att+" : "+instance[att]);
									}
									*/
									
									instance.setContent(oldContent);
									
									// Pour se débarrasser des infos bulles de bouton restant
									instance.ne.fireEvent("buttonOut",instance);
									
									// afficher tous les boutons edit
									lbtn = document.getElementsByClassName('edit_btn');
									for(var i = 0; i < lbtn.length; i++){
										lbtn[i].style.display = "inline";
									}
									
									// Suppression du panel
									richEditor.removeInstance(divId);
									richEditor = null;
									
								}}).panelInstance(parentRef.id, {hasPanel : true});
								
				itc = richEditor.instanceById(parentRef.id);
				//itc.ne.fireEvent("focus",itc.e);
				itc.elm.focus();
				//richEditor.selectCheck(itc.e, itc);
				//});
	

	}

}
	
function editMe(btn){
	
	if(this.className == 'edit_btn'){
		btn = this;
	}
	
	// masquer tous les boutons edit
	lbtn = document.getElementsByClassName('edit_btn');
	for(var i = 0; i < lbtn.length; i++){
		lbtn[i].style.display = "none";
		
	}
	
	// Récupérer et conserver le contenu 
	//alert(btn.parentNode.innerHTML);
	
	parentRef = btn.parentNode;
	oldContent = parentRef.firstChild.data;
	//oldContent = content;
	
	//alert(parentRef.nodeName);
	
	// Effacer l'ancien contenu 
	container = clearChildren(parentRef);
	
	// Remplacer par le champs et les boutons valider/annuler
	fld_edit = document.createElement("div");
	// Ajouter l'identifiant récupérer depuis le bouton
	fld_edit.setAttribute("id", "edit"+btn.id);
	fld_edit.className = "aloa_editor";

	if(parentRef.nodeName == 'DIV'){
		// Pb pour les retours à la ligne etc... à voir
		tarea = document.createElement("textarea");
		txt = document.createTextNode(oldContent);
		tarea.appendChild(txt);	
		tarea.className = "aloa_editor_textarea";
		fld_edit.appendChild(tarea);	
		
	}else{	
	
		inpt = document.createElement("input");
		inpt.setAttribute("type", "text");
		inpt.setAttribute("value", oldContent);
		inpt.className = "aloa_editor_input";
		fld_edit.appendChild(inpt);
	}
	
	fld_edit.appendChild(getValidateBtn());
	fld_edit.appendChild(getUndoBtn());
	container.appendChild(fld_edit);	
		
	
}


function getValidateBtn(){
	// Le bouton valider
	valid_btn = document.createElement("img");
	valid_btn.setAttribute("src", "/images/check_mnu.gif");
	valid_btn.setAttribute("title", "Modifier");
	valid_btn.className = "aloa_editor_btn";
	
	valid_btn.onclick = function(){

							// Récupérer le nouveau texte
							newContent = this.parentNode.firstChild.value;
							
							//alert(newContent);
							
							id = this.parentNode.id;
							p = id.split('_');
							
							//préparation du bouton éditer à ajouter
							btn = getEditBtn("btn_"+p[1]+"_"+p[2]);
							
							// Mise à jour distante
							$res = updtRemoteData(p[1], p[2], newContent);
							
							if(!$res){
								alert("Suite à une erreur technique les données n'ont pu être mise à jour. Nous vous proposons de tenter de modifier vos données plus tard. Merci");
								newContent = oldContent;
							}
	
							container = clearChildren(this.parentNode.parentNode);
	
							txt = document.createTextNode(newContent);
							container.appendChild(txt);

							container.appendChild(btn);	

							// afficher tous les boutons edit
							lbtn = document.getElementsByClassName('edit_btn');
							for(var i = 0; i < lbtn.length; i++){
								lbtn[i].style.display = "inline";
							}
	
						}
	return valid_btn;

}

function getUndoBtn(){
	// Le bouton annuler
	undo_btn = document.createElement("img");
	undo_btn.setAttribute("src", "/images/undo_mnu.gif");
	undo_btn.setAttribute("title", "Annuler");
	undo_btn.className = "aloa_editor_btn";
	
	undo_btn.onclick = function(){
							
							id = this.parentNode.id;
							p = id.split('_');
							//préparation du bouton éditer à ajouter
							btn = getEditBtn("btn_"+p[1]+"_"+p[2]);
							
							container = clearChildren(this.parentNode.parentNode);
							
							txt = document.createTextNode(oldContent);
							container.appendChild(txt);
							
							container.appendChild(btn);
							
							// afficher tous les boutons edit
							lbtn = document.getElementsByClassName('edit_btn');
							for(var i = 0; i < lbtn.length; i++){
								lbtn[i].style.display = "inline";
							}
	
						}
	return undo_btn;
}

function getEditBtn(id){

	btn = document.createElement("a");
	btn.className = "edit_btn";
	btn.setAttribute("id", id);
	btn.setAttribute("href", "javascript:void(0);");
	txt = document.createTextNode("Editer");
	btn.appendChild(txt);

	btn.onclick = editMe;
	
	return btn;
}

function getRichEditBtn(id){

	btn = document.createElement("a");
	btn.className = "edit_btn";
	btn.setAttribute("id", id);
	btn.setAttribute("href", "javascript:void(0);");
	txt = document.createTextNode("Editer");
	btn.appendChild(txt);

	btn.onclick = editMeRichVersion;
	
	return btn;
}

var editorList = [];

// Ajouter un id de textarea pour la conversion en rich textarea
function appendToEditorList(id){
	editorList.push(id);
}

function addRichEditor(id){
	params = {buttonList : ['bold','italic','underline','strikeThrough','forecolor','smiley','link', 'save', 'close'], maxHeight : 150};
	new nicEditor(params).panelInstance(id);
}

bkLib.onDomLoaded(function() { 
	params = {buttonList : ['bold','italic','underline','strikeThrough','forecolor','smiley','link', 'save', 'close'], maxHeight : 150};
	for(var i=0; i < editorList.length; i++){
		new nicEditor(params).panelInstance(editorList[i]); 
	}
});

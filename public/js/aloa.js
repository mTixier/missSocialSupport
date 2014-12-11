/*
Sidebar
*/

function getSidebarXhrHandler(){

	var xhrSidebar_handler = null; 
	
	if(window.XMLHttpRequest) // Firefox 
		   xhrSidebar_handler = new XMLHttpRequest(); 
	else if(window.ActiveXObject) // Internet Explorer 
		   xhrSidebar_handler = new ActiveXObject("Microsoft.XMLHTTP"); 
	else { // XMLHttpRequest non supporté par le navigateur 
		   alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest. Le système de feedback ne pourra pas fonctionner."); 
			
	}

	return xhrSidebar_handler;
}

function hideSidebar(){
	changeStyle("side_cmplt", "s_side_cmplt_hidden");
	changeStyle("pg", "s_pg_hidden");
	
}

function minimizeSidebar(){

	changeStyle("side_content", "s_side_content_minimized");
	changeStyle("side_cmplt", "s_side_cmplt_minimized");
	changeStyle("side_arrow", "s_side_arrow_lft");
	changeStyle("pg", "s_pg_minimized");
	
	handler = getSidebarXhrHandler();
	data = "state=minimize";
	handler.open("POST", 'http://'+(document.location.host)+'/sidebar/update', true); 
	handler.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	handler.send(data);
	
}

function defaultSidebar(){
	changeStyle("side_content", "s_side_content");
	changeStyle("side_cmplt", "s_side_cmplt");
	changeStyle("side_arrow", "s_side_arrow_rgt");
	changeStyle("pg", "s_pg");

	handler = getSidebarXhrHandler();
	data = "state=default";
	handler.open("POST", 'http://'+(document.location.host)+'/sidebar/update', true); 
	handler.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	handler.send(data);	

}

function maximizeSidebar(){
	changeStyle("side_content", "s_side_content_expanded");
	changeStyle("side_cmplt", "s_side_cmplt_expanded");
	changeStyle("side_menu", "s_side_menu_expanded");
	changeStyle("side_arrow", "s_side_arrow_rgt");
	changeStyle("pg", "s_pg_expanded");
	
	handler = getSidebarXhrHandler();
	data = "state=maximize";
	handler.open("POST", 'http://'+(document.location.host)+'/sidebar/update', true); 
	handler.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	handler.send(data);
	
}



window.onscroll = updateSide_arrow;

function updateSide_arrow(){
	if(document.body.scrollTop > 0){
	// For IE 5.5
		bdy_ref = document.body;
	}else{
		bdy_ref = document.body.parentNode;
	}

	if(document.getElementById("side_arrow")){
		document.getElementById("side_arrow").style.marginTop = 250+bdy_ref.scrollTop+"px";
	}
}

// Thanks to : http://www.dunnbypaul.net/js_mouse/
function getMouseXY(e){
if (!e) e = window.event; // works on IE, but not NS (we rely on NS passing us the event)
  if (e)
  { 
    if (e.pageX || e.pageY)
    { // this doesn't work on IE6!! (works on FF,Moz,Opera7)
      mousex = e.pageX;
      mousey = e.pageY;
      algor = '[e.pageX]';
      if (e.clientX || e.clientY) algor += ' [e.clientX] '
    }
    else if (e.clientX || e.clientY)
    { // works on IE6,FF,Moz,Opera7
      mousex = e.clientX + document.body.scrollLeft;
      mousey = e.clientY + document.body.scrollTop;
      algor = '[e.clientX]';
      if (e.pageX || e.pageY) algor += ' [e.pageX] '
    }  
  }
}

/*
Sélecteur d'avatar
*/

function selectAvatar(avtr){
	
	if(document.getElementById('avatar_selected').value){
		lastsel = document.getElementById('avatar_selected').value;
		document.getElementById(lastsel).className = 'avatar_frame';
	}
	avtr.className = 'avatar_frame_selected';
	document.getElementById('avatar_selected').value = avtr.id;

}

/*
Ajax
*/

function getXhrHandler(){

	var xhr_handler = null; 
	
if(window.XMLHttpRequest) // Firefox 
	   xhr_handler = new XMLHttpRequest(); 
else if(window.ActiveXObject) // Internet Explorer 
	   xhr_handler = new ActiveXObject("Microsoft.XMLHTTP"); 
else { // XMLHttpRequest non supporté par le navigateur 
	   alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest. Le système de feedback ne pourra pas fonctionner."); 
	    
}

	return xhr_handler;
}

function clearChildren(node){

	while(node.hasChildNodes()){
		first = node.firstChild;
		node.removeChild(first);
	}
	return node;
}

function listSiblingNodesAttribute(pNode, att){
	res = new Array();
	if(pNode.hasChildNodes()){	
		nd = pNode.firstChild;
		while(nd != null){
			if(nd.nodeType == 1){
				res.push(eval("nd."+att));
				//alert(eval("nd."+att));
			}
			nd = nd.nextSibling;
		}
		if(res.length <= 0){
			res = "no";
		}
	}else{
		res ="no";
	}	
	
	return res;
}

/* Toolbox */


// Thanks to : http://javascript.crockford.com/remedial.html
String.prototype.trim = function () {
    return this.replace(/^\s+|\s+$/g, "");
};

function changeStyle(id, style){
	if(isArray(id) && isArray(style)){
		for(i=0;i<id.length;i++){
			document.getElementById(id[i]).className = style[i];
		}
	}else{
		document.getElementById(id).className = style;

	}
}

function isArray(obj) {
	return obj.constructor == Array;
}

function isInArray(val, arr){
	if(isArray(arr)){
	for(j = 0;j<arr.length;j++){
		if(val == arr[j]){
			return true;
		}
	}
	}
	return false;
}

function getSiblingPage(pg){

	document.location = document.location.href.substr(0,document.location.href.lastIndexOf('/'))+'/'+pg;

}
/*
Choix des invités ou destiantaire de messages
*/

function getRcptList(){

	xhr_handler = getXhrHandler();
	
	needle = document.getElementById('recepient_selector').value;
	

	data = "needle="+needle+"&location="+document.location.href;
	
	xhr_handler.open("POST", 'http://'+(document.location.host)+'/communaute/listuser', true); 
	xhr_handler.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr_handler.send(data);
	
	xhr_handler.onreadystatechange = updtRcptList;

	return 0;	
	

}

function updtRcptList(){

	rtrn = document.getElementById('slct');
	clearChildren(rtrn);
	msg = "";
	if(xhr_handler.readyState == 4){
	
		res = makeRcptList(xhr_handler.responseText);
		
		if(isArray(res)){
			for(i=0;i<res.length;i++){
				rtrn.appendChild(res[i]);
			}
		}else{
			msg = res;
		}
		
		if(xhr_handler.status != 200){
			msg = "Erreur de transmission. Contatez l'administrateur.";
		}
		
	}else{
		msg = "En cours...";
	}
	
	if(msg != ""){
		err = document.createElement("div");
		err.setAttribute("id", "rcpt_error");
		errmsg = document.createTextNode(msg);
		err.appendChild(errmsg);
		rtrn.appendChild(err);	
	}

}


function makeRcptList(str){

	// On cherche le marqueur de séparation entre données utilisateur afin de discriminer le message d'erreur...
	if(str.indexOf("#") > -1){
		list = str.split(";");
		var res = new Array();
		
		// On construit une liste des destinataires déjà sélectionné afin de filtrer les potentiels doublons
		filter = listSiblingNodesAttribute(document.getElementById('rcpt'), "firstChild.getAttribute('name')");
		
		for(i=0;i<list.length;i++){
		
				// Récupération nom d'utilisateur et avatar
				user = list[i].split('#');
				
				// Condition de filtrage de la liste des utilisateurs pour éviter les doublons
				if(!isInArray(user[0], filter)){
					//Préparation de la structure <li><a> à insérer
					rmvbl = document.createElement("li");
					rmvbl.className = "add_profil";
					
					lnk = document.createElement("a");
					lnk.setAttribute("href", "javascript:void(0);");
					lnk.setAttribute("name", user[0]);
					lnk.setAttribute("title", "Ajouter "+user[0]+" à la liste des destinataires.");
					
					img = document.createElement("img");
					img.setAttribute("src", user[1]);
					img.className = "sml_avtr";
					lnk.appendChild(img);
					
					uName = document.createTextNode(user[0]);
					lnk.appendChild(uName);
					
					lnk.onclick = addRcpt;
					
					rmvbl.appendChild(lnk);
					res.push(rmvbl);
				}
		}
		//alert(res.length);
		if(res.length <= 0){
			res = "Les utilisateurs correspondants ont déjà tous été ajoutés à la liste des destinataires.";
		}
	}else{
		res = str;
	}
	
	return res;
}

function makeRcptToAdd(node){
	//Préparation de la structure <li><a> à insérer
	rmvbl = document.createElement("li");
	rmvbl.className = "rmv_profil";
	
	name = node.name;
	
	lnk = document.createElement("a");
	lnk.setAttribute("href", "javascript:void(0);");
	lnk.setAttribute("name", name);
	lnk.setAttribute("title", "Retirer "+name+" de la liste des destinataires.");
	//lnk.setAttribute("onClick", "javascript:supprRcpt(this);");
	lnk.onclick = supprRcpt
	
	
	// Copie du contenu de l'élément destinataire
	lnk.innerHTML = node.innerHTML;
	
	//Construction de la structure <li><a>
	rmvbl.appendChild(lnk);

	return rmvbl;
}

function makeRcptToRmv(node){
	//Préparation de la structure <li><a> à insérer
	rmvbl = document.createElement("li");
	rmvbl.className = "add_profil";
	
	name = node.name;
	
	lnk = document.createElement("a");
	lnk.setAttribute("href", "javascript:void(0);");
	lnk.setAttribute("name", name);
	lnk.setAttribute("title", "Ajouter "+name+" à la liste des destinataires.");
	//lnk.setAttribute("onClick", "javascript:addRcpt(this);");
	lnk.onclick = addRcpt
	
	// Copie du contenu de l'élément destinataire
	lnk.innerHTML = node.innerHTML;
	
	//Construction de la structure <li><a>
	rmvbl.appendChild(lnk);
	
	return rmvbl;
}

function addRcpt(){

	//document.getElementById('rcpt').innerHTML += "<li class='rmv_profil'><a href='#rcpt' title='Retirer Anna à la liste des destinataires.' onClick='javascript:supprRcpt(this);' >"+node.innerHTML+"</li>";
	
	rmvbl = makeRcptToAdd(this);

	// Ajout à la liste des destinataires
	document.getElementById('rcpt').appendChild(rmvbl);
	
	// Met à jour la liste des destinataires qui sera envoyé au serveur via un champs hidden
	rcptlist = listSiblingNodesAttribute(document.getElementById('rcpt'), "firstChild.getAttribute('name')");
	document.getElementById('recepient_list').value = isArray(rcptlist)?rcptlist.join(";"):"no";
	
	document.getElementById('slct').removeChild(this.parentNode);
	//alert(this.parentNode.nodeName+"::"+this.nodeName);
	
}

function supprRcpt(){

	//document.getElementById('slct').innerHTML += "<li class='add_profil'><a href='#rcpt' title='Ajouter Anna à la liste des destinataires.' onClick='javascript:addRcpt(this);' >"+node.innerHTML+"</li>";
	rmvbl = makeRcptToRmv(this);
	
	// Efface un message d'erreur s'il est présent
	if(document.getElementById('rcpt_error')){
		clearChildren(document.getElementById('slct'));
	}
	
	// Ajout à la liste de choix destinataires
	document.getElementById('slct').appendChild(rmvbl);	
		
	document.getElementById('rcpt').removeChild(this.parentNode);
	//alert(node.parentNode.nodeName+"::"+node.nodeName);	

	// Met à jour la liste des destinataires qui sera envoyé au serveur via un champs hidden
	rcptlist = listSiblingNodesAttribute(document.getElementById('rcpt'), "firstChild.getAttribute('name')");
	document.getElementById('recepient_list').value = isArray(rcptlist)?rcptlist.join(";"):"no";	
}

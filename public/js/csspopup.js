/*
@author : Patrick Burt
avec quelques modifications personnelles.
http://www.pat-burt.com/web-development/how-to-do-a-css-popup-without-opening-a-new-window/

*/

function toggle(div_id) {
	var el = document.getElementById(div_id);
	if ( el.style.display == 'none' ) {	el.style.display = 'block';}
	else {el.style.display = 'none';}
}

function blanket_size(popUpDivVar) {
	if (typeof window.innerWidth != 'undefined') {
		viewportheight = window.innerHeight;
	} else {
		viewportheight = document.documentElement.clientHeight;
	}
	
	
	if(document.body.scrollTop > 0){
	// For IE 5.5
		bdy_ref = document.body;
	}else{
		bdy_ref = document.body.parentNode;
	}
	
	
	if ((viewportheight > bdy_ref.scrollHeight) && (viewportheight > bdy_ref.clientHeight)) {
		blanket_height = viewportheight;
	} else {
		if (bdy_ref.clientHeight > bdy_ref.scrollHeight) {
			blanket_height = bdy_ref.clientHeight;
		} else {
			blanket_height = bdy_ref.scrollHeight;
		}
	}
	var blanket = document.getElementById('layer405');
	blanket.style.height = blanket_height + 'px';
	var popUpDiv = document.getElementById(popUpDivVar);
	//popUpDiv_height=blanket_height/2-170;//150 is half popup's height
	popUpDiv_height=screen.availHeight/2-350;//350 is the popup's height
	popUpDiv.style.top = (popUpDiv_height+bdy_ref.scrollTop) + 'px';

}

function window_pos(popUpDivVar) {
	if (typeof window.innerWidth != 'undefined') {
		viewportwidth = window.innerHeight;
	} else {
		viewportwidth = document.documentElement.clientHeight;
	}
	if ((viewportwidth > document.body.parentNode.scrollWidth) && (viewportwidth > document.body.parentNode.clientWidth)) {
		window_width = viewportwidth;
	} else {
		if (document.body.parentNode.clientWidth > document.body.parentNode.scrollWidth) {
			window_width = document.body.parentNode.clientWidth;
		} else {
			window_width = document.body.parentNode.scrollWidth;
		}
	}
	var popUpDiv = document.getElementById(popUpDivVar);
	window_width=window_width/2-210;//150 is half popup's width
	popUpDiv.style.left = window_width + 'px';
}
function popup(windowname) {
	blanket_size(windowname);
	window_pos(windowname);
	toggle('layer405');
	toggle(windowname);	
}

/* Alert Popup */
function popupAlertInit(){

	document.getElementById('alertPopUp_report').value = "";
	
	if(document.getElementById('alertPopUp_captcha_input')){
		document.getElementById('alertPopUp_captcha_input').value = "";
	}
	
	clearChildren(document.getElementById('alertPopUp_err'));
	
	popup('notificationPopUp');
	
	document.getElementById('alertPopUp_send').onmouseup = sendAlert;

}


function sendAlert(){

	alertForm = document.getElementById('alertPopUp_form').childNodes;
	
	data = "report="+document.getElementById('alertPopUp_report').value;
	data+= "&location="+escape(document.location.href);
	
	capId = document.getElementById('alertPopUp_captcha_id');
	//alert(typeof(document.getElementById('captcha[id]')));
	
	if(capId){
		
		data += "&captcha_id="+capId.value;
		data += "&captcha_input="+document.getElementById('alertPopUp_captcha_input').value;
		//alert(data);
	
	}
	
	xhr_handler = getXhrHandler();
	xhr_handler.open("POST", 'http://'+(document.location.host)+'/notification', true); 
	
	xhr_handler.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr_handler.send(data);
		
	xhr_handler.onreadystatechange = checkAlert;
	
	
}

function checkAlert(){

	if(xhr_handler.readyState == 4){
		rps = xhr_handler.responseText
		msg = rps.split(':');
		detail = msg[1].split('#');
		txt = detail[0]
		capid = detail[1]
		
		if(msg[0] == 'error'){
		
			errmsg = prepareMessage(txt);
			err = document.getElementById('alertPopUp_err');
			clearChildren(err);
			err.appendChild(errmsg);
		
		}else if(msg[0] == 'ok'){

			msg = prepareMessage("Envoi réussi.");
			err = document.getElementById('alertPopUp_err');
			clearChildren(err);
			err.appendChild(msg);
			
			// On ferme la fenêtre
			popup('notificationPopUp');
		}
		if(capid){
			document.getElementById('alertPopUp_captcha_img').src = "/upload/"+capid+".png";
			document.getElementById('alertPopUp_captcha_id').value = capid;
		}
	}else{

		msg = prepareMessage("En cours... ");
		err = document.getElementById('alertPopUp_err');
		clearChildren(err);
		err.appendChild(msg);
		
	}

}

/* Advice Popup */
var advice_contrib;

function popupAdviceInit(contrib_id){

	// On nettoie les restes éventuels de message d'erreurs et autres
	clearChildren(document.getElementById('advicePopUp_msg'));

	popup('advicePopUp');

	//advicePopUp_loader
	xhr_handler = getXhrHandler();
	xhr_handler.open("POST", 'http://'+(document.location.host)+'/hypertopic/addtoadvices', true); 
	
	xhr_handler.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr_handler.send('cid='+contrib_id);
	
	advice_contrib = contrib_id;
	
	document.getElementById("advicePopUp_loader").style.display = 'inline';
	
	
	xhr_handler.onreadystatechange = checkAdvice;
	
}

function checkAdvice(){

	if(xhr_handler.readyState == 4){
	
		document.getElementById("advicePopUp_loader").style.display = 'none';

		rps = xhr_handler.responseText;
		msg = rps.split(':');
		
		fdbk = document.getElementById('advicePopUp_msg');
		clearChildren(fdbk);
		
		if(msg[0] == 'error'){
		
			fdbk_msg = prepareMessage(msg[1]);
			
			fdbk.appendChild(fdbk_msg);
		
		}else if(msg[0] == 'ok'){

			fdbk_msg = document.createElement('p');
			//fdbk_txt = document.createTextNode(msg[1]);
			//fdbk_msg.appendChild(fdbk_txt);
			fdbk_msg.innerHTML = msg[1];
			fdbk.appendChild(fdbk_msg);
			
			// Incrémentation du compteur
			cntr = document.getElementById('nbUpCntr_'+advice_contrib);
			nbUp = cntr.firstChild.data;
			clearChildren(cntr);
			nbUp_txt = document.createTextNode(Number(nbUp)+1);
			
			cntr.appendChild(nbUp_txt);
			
		}

	}

}


/* Gift Popup */
var gift_contrib;

function popupGiftInit(to, contrib_id){
	
	// On nettoie éventuellement les restes d'un précédent gift
	document.getElementById('giftPopUp_msg').value = "";
	clearChildren(document.getElementById('giftPopUp_err'));
	
	// Ajout du uname
	dest1 = document.createTextNode(to);
	dest2 = document.createTextNode(to);
	uname_fld1 = document.getElementById('giftPopUp_dest_fld1');
	uname_fld2 = document.getElementById('giftPopUp_dest_fld2');
	clearChildren(uname_fld1);
	clearChildren(uname_fld2);
	uname_fld1.appendChild(dest1);
	uname_fld2.appendChild(dest2);
	
	popup('giftPopUp');
	
	// Initialisation du send gift
	//gift_to = to;
	gift_contrib = contrib_id;
	
	document.getElementById('giftPopUp_send').onmouseup = sendGift;
	
}


function sendGift(){

	msg = document.getElementById('giftPopUp_msg').value;
	
	xhr_handler = getXhrHandler();
	xhr_handler.open("POST", 'http://'+(document.location.host)+'/gift', true); 
	
	xhr_handler.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr_handler.send('contrib='+gift_contrib+'&content='+msg);
	
	xhr_handler.onreadystatechange = checkGift;
	
}





function checkGift(){

	if(xhr_handler.readyState == 4){
		rps = xhr_handler.responseText;
		msg = rps.split(':');
		if(msg[0] == 'error'){
		
			errmsg = prepareMessage(msg[1]);
			err = document.getElementById('giftPopUp_err');
			clearChildren(err);
			err.appendChild(errmsg);
		
		}else if(msg[0] == 'ok'){

			msg = prepareMessage("Envoi réussi.");
			err = document.getElementById('giftPopUp_err');
			clearChildren(err);
			err.appendChild(msg);
			
			// On ferme la fenêtre
			popup('giftPopUp');
		}
		
	}else{

		msg = prepareMessage("En cours... ");
		err = document.getElementById('giftPopUp_err');
		clearChildren(err);
		err.appendChild(msg);
		
	}
	
}

function prepareMessage(txt){
	errmsg = document.createElement('div');
	errmsg.setAttribute('class', "topmsg");
	msg = document.createTextNode(txt);
	errmsg.appendChild(msg);
	return errmsg;
}



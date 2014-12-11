
function getChatXhrHandler(){

	var chat_handler = null; 
	
if(window.XMLHttpRequest) // Firefox 
	   chat_handler = new XMLHttpRequest(); 
else if(window.ActiveXObject) // Internet Explorer 
	   chat_handler = new ActiveXObject("Microsoft.XMLHTTP"); 
else { // XMLHttpRequest non supporté par le navigateur 
	   alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest. Le système de chat ne pourra pas fonctionner."); 
	    
}

	return chat_handler;
}

var maxMsg = 10;
var lastMsgId = 0;
var lastdt = 0;
var chatid;
var refreshLoop;

function initChat(p_chatid){

	chatid = p_chatid;

	chat_handler = getChatXhrHandler();

	chat_handler.open("POST", 'http://'+(document.location.host)+'/chat', true); 
	
	chat_handler.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	chat_handler.send('chatid='+chatid);
	
	chat_handler.onreadystatechange = makeChat;


	return true;
}

function makeChat(){

	bvrdgDiv = document.getElementById('bavardages');
	
	
	if(chat_handler.readyState == 4){
		rps = chat_handler.responseText
		msg = rps.split('&');
		
		if(msg[0] == 'error' || msg[0] == 'message'){
		
			errmsg = prepareMessage(msg[1]);
			
			// Ajout bricolage d'un lien ancre vers la fenêtre d'envoi de message
			lnk = document.createElement('a');
			lnk.setAttribute('href', '#new_msg');
			lnk.setAttribute('target', '_self');
			lnk_msg = document.createTextNode('Cliquez ici.');
			lnk.appendChild(lnk_msg);
			errmsg.appendChild(lnk);
			
			clearChildren(bvrdgDiv);
			bvrdgDiv.appendChild(errmsg);
			
		}else if(msg[0].substr(0,2) == 'ok'){
			info = msg[0].split('-');
			// Mettre à jour la lastdt
			lastdt = info[1];
			
			msglist = msg[1].split('#');
			msglist.pop();
			
			clearChildren(bvrdgDiv);
			
			msglist.reverse();
			
			for(var i=0; i < msglist.length; i++){
				msgDiv = makeMsg(msglist[i]);
				bvrdgDiv.appendChild(msgDiv);
			}
			
			bvrdgDiv.scrollTop = 99999;
			
		
		
		}
		refreshLoop = setTimeout('getNewMsg()', 10000);

	}else{

		msg = prepareMessage("En cours... ");
		clearChildren(bvrdgDiv);
		bvrdgDiv.appendChild(msg);
		
	}
}

function getNewMsg(){

	data = "chatid="+chatid+"&lastdt="+lastdt;
	
	chat_handler = getChatXhrHandler();

	chat_handler.open("POST", 'http://'+(document.location.host)+'/chat/update', true); 
	
	chat_handler.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	chat_handler.send(data);
	
	// Pour affichage à chaque rafraîchissement
	//document.getElementById('chat_refresh').style.display = 'inline';
	
	chat_handler.onreadystatechange = updateMsgQueue;
	
	refreshLoop = setTimeout('getNewMsg()', 5000);
	
}

function updateMsgQueue(){

	bvrdgDiv = document.getElementById('bavardages');
	
	
	if(chat_handler.readyState == 4){
		rps = chat_handler.responseText
		msg = rps.split('&');
		
		if(msg[0].substr(0,2) == 'ok'){
			info = msg[0].split('-');
			// Mettre à jour la lastdt
			lastdt = info[1];
			
			msglist = msg[1].split('#');
			msglist.pop();
			

			if(msglist.length > 0){
			
				msglist.reverse();

				for(var i=0; i < msglist.length ; i++){
					// Effacer éventuellement le message d'erreur
					if(lastMsgId == 0){
						clearChildren(bvrdgDiv);
					}
					msgDiv = makeMsg(msglist[i]);
					bvrdgDiv.appendChild(msgDiv);
					
					if(lastMsgId > maxMsg){
						nd = document.getElementById("utterance_"+(lastMsgId-maxMsg));
						
						res = bvrdgDiv.removeChild(nd);
						
					}
					
					
				}
				bvrdgDiv.scrollTop = 99999;
				document.getElementById('chat_refresh').style.display = 'none';
			}
			
		}
		// Pour affichage à chaque rafraîchissement
		// document.getElementById('chat_refresh').style.display = 'none';
	}
	
}

function makeMsg(s_param){
	params = s_param.split('|');
	dt = params[0];
	uname = params[1];
	avtr = params[2];
	message = params[3];
	
	msgDiv = document.createElement('div');
	// Gare aux collisions si on installe plusieurs chat par page...
	lastMsgId++;
	msgDiv.setAttribute("id", "utterance_"+lastMsgId);
	msgDiv.className = 'bvrdg_contrib';

		mainBlock = document.createElement('div');
		mainBlock.className = 'bvrdg_contrib_content';
		
			authorBlock = document.createElement('div');
			authorBlock.className = 'bvrdg_contrib_author';
			
				avtrImg = document.createElement('img');
				avtrImg.setAttribute("src", avtr);
				avtrImg.className = 'avtr_trombi';
				avtrImg.setAttribute("title", uname);
				
				authorBlock.appendChild(avtrImg);

		
			dtBlkock = document.createElement('em');
			dtTxt = document.createTextNode('par '+uname+', '+dt);
			dtBlkock.appendChild(dtTxt);
		
			paraBlock = document.createElement('p');
			msgBlock = document.createElement('div');
			msgBlock.innerHTML = message;
			paraBlock.appendChild(msgBlock)
		
		mainBlock.appendChild(authorBlock);
		mainBlock.appendChild(dtBlkock);
		mainBlock.appendChild(paraBlock);
		
	msgDiv.appendChild(mainBlock);

	return msgDiv;
}

function appendChatMsg(author){

	// Force la synchronisation du contenu du RTE avec le contenu du textarea
	nicEditors.findEditor('newChatMsg').saveContent();
	
	message = nicEditors.findEditor('newChatMsg').getContent();
	
	xhr_handler = getXhrHandler();
	
	data = "chatid="+chatid+"&uname="+author+"&content="+message.removeEntities();
	
	xhr_handler.open("POST", 'http://'+(document.location.host)+'/chat/append', true); 
	
	xhr_handler.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	xhr_handler.send(data);
	
	document.getElementById('chat_refresh').style.display = 'inline';
	
	xhr_handler.onreadystatechange = cleanMsgWindow;
}

function cleanMsgWindow(){
	if(xhr_handler.readyState == 4){
		
		//getNewMsg();	
		nicEditors.findEditor('newChatMsg').setContent('');
	}
}
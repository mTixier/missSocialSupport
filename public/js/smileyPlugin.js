/* START CONFIG */
var nicLinkOptions = {
   buttons : {
      'smiley' : {name : 'Emoticones', type : 'nicSmileysButton'}
   },
   iconFiles : {'smiley' : '/images/smiley_mnu.gif'}
};
/* END CONFIG */

var nicSmileysButton = nicEditorAdvancedButton.extend
({
   addPane:function()
   {
		this.smileyList = {
						'Rire' : '/images/smiley/icon_lol.gif',
						'Cheese' : '/images/smiley/icon_biggrin.gif',
						'Oh?' : '/images/smiley/icon_eek.gif',
						'Sourire' : '/images/smiley/icon_smile.gif',
						'Neutre' : '/images/smiley/icon_neutral.gif',
						"Clin d'oeil" : '/images/smiley/icon_wink.gif',
						'Nah' : '/images/smiley/icon_razz.gif',		
						'Perplexe' : '/images/smiley/icon_confused.gif',
						'Oups' : '/images/smiley/icon_redface.gif',
						'Colère' : '/images/smiley/icon_angry.gif',
						'Triste' : '/images/smiley/icon_sad.gif'
						};
						
		var smileyItems = new bkElement('DIV').setStyle({width: '270px'});
			
			for(var s in this.smileyList) {

						var nm = s;
						var path = this.smileyList[s];
						
						var colorSquare = new bkElement('DIV').setStyle({'cursor' : 'pointer', 'height' : '15px', 'float' : 'left', 'padding' : '2px'}).appendTo(smileyItems);
						var colorBorder = new bkElement('DIV').setStyle({border: '2px solid #FFF'}).appendTo(colorSquare);
						var colorInner = new bkElement('DIV').setStyle({background : "url('"+path+"')", overflow : 'hidden', width : '15px', height : '15px'}).addEvent('click',this.colorSelect.closure(this,nm)).addEvent('mouseover',this.on.closure(this,colorBorder)).addEvent('mouseout',this.off.closure(this,colorBorder,"#FFF")).setAttributes({'title' : nm}).appendTo(colorBorder);
						
						if(!window.opera) {
							colorSquare.onmousedown = colorInner.onmousedown = bkLib.cancelEvent;
						}	
			}
			this.pane.append(smileyItems.noSelect());	
	},
	
	colorSelect : function(s) {
		//this.ne.nicCommand('foreColor',c);
		var B = this.smileyList[s];
		this.ne.nicCommand("InsertImage",B);
		this.removePane();
	},
	
	on : function(colorBorder) {
		colorBorder.setStyle({border : '2px solid #000'});
	},
	
	off : function(colorBorder,colorCode) {
		colorBorder.setStyle({border : '2px solid '+colorCode});		
	}
	
});

nicEditors.registerPlugin(nicPlugin,nicLinkOptions);
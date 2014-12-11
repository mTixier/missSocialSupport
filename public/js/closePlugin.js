/* START CONFIG */
var nicCloseOptions = {
	buttons : {
		'close' : {name : __('Annuler'), type : 'nicCloseButton'}
	}
};
/* END CONFIG */

var nicCloseButton = nicEditorButton.extend({
	init : function() {
		if(!this.ne.options.onClose) {
			this.margin.setStyle({'display' : 'none'});
		}
	},
	mouseClick : function() {
		var onClose = this.ne.options.onClose;
		var selectedInstance = this.ne.selectedInstance;
		onClose(selectedInstance.getContent(), selectedInstance.elm.id, selectedInstance);
	}
});

nicEditors.registerPlugin(nicPlugin,nicCloseOptions);
<?php
class Aloa_Form_Login extends Zend_Form{

	public function init(){
	
		$email = new Zend_Form_Element_Text('email');
		$email->setLabel("Email :");
		$email->addValidator('EmailAddress');
		$email->setRequired(true);
		//$email->setDescription('*');
		$this->addElement($email);
		
		$mdp = new Zend_Form_Element_Password('pwd');
		$mdp->setLabel("Mot de passe :");
		$mdp->addValidator('StringLength', false, array(6, 20));
		$mdp->setRequired(true);
		//$mdp->setDescription('*');
		$this->addElement($mdp);
		
		$remember = new Zend_Form_Element_Checkbox('remember_me');
		$remember->setLabel("Se souvenir de moi.");
		$this->addElement($remember);

		$this->setElementDecorators(array(
			'ViewHelper', 
			'Errors',
			array('Description', array('tag' => 'font', 'class' => 'redstar', 'placement' => 'prepend')),
			array('Label'),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p'))
			));
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Me connecter  ", 'class' => 'btn'));
		$this->addElement($submit);	
	
	}

}


?> 
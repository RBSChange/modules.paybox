<?php
/**
 * paybox_BankSuccessAction
 * @package modules.paybox.actions
 */
class paybox_BankSuccessAction extends paybox_BankResponseAction
{
	
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{
		
		/**

http://site2.ssxb-wf-inthause.fr/fr/action/paybox/BankSuccess?amount=28405&maref=201100000006/16080&auto=XXXXXX&trans=1261584&idtrans=848653&erreur=00000&sign=dL7NYzB3Pj1r%2FJEHpv7i6eN5jzJF%2FmZF2fD6lDpmNsdueslbNNLPqsu9Ar3X6YE4Mf0OGXBeqEg9dceeb32lh8O7uW%2FbPRVSjiMDZSZq53bYpFLUm8E0dWzrQ52LhdrUp6nhhdopliACEP7DCRBnuvzrrZs95b4eAL9faqHsvik%3D

		 */
		$this->returnType = 'Success';
		return parent::_execute($context, $request);
	}
}
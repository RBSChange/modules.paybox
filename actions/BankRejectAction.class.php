<?php
/**
 * paybox_BankRejectAction
 * @package modules.paybox.actions
 */
class paybox_BankRejectAction extends paybox_BankResponseAction
{
	
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{
		$this->returnType = 'Reject';
		return parent::_execute($context, $request);
	}
}
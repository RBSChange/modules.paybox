<?php
/**
 * paybox_BankListenerAction
 * @package modules.paybox.actions
 */
class paybox_BankListenerAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
	    $remoteAddr = $_SERVER['REMOTE_ADDR'];
        $requestUri = $_SERVER['REQUEST_URI'];
		$ms = payment_ModuleService::getInstance();
		$baseLog = "BANKING PAYBOX Listener";
		$ms->log($baseLog . " from [".$remoteAddr." : ".$requestUri."] BEGIN");      
		try
		{
			$this->getTransactionManager()->beginTransaction();
			if (strpos($requestUri, '?') === false) {throw new Exception('InvalidURL');}
			$queryString = substr($requestUri, strpos($requestUri, '?') + 1);		
        	$connectorService = paybox_PayboxconnectorService::getInstance();  
			$bankResponse = $connectorService->getBankResponse('Listener', $queryString);			
			$order = $bankResponse->getOrder();
			$connectorService->setPaymentResult($bankResponse, $order);			
			$ms->log($baseLog . " END");	
			$this->getTransactionManager()->commit();
		}
		catch(Exception $e)
		{
			$ms->log($baseLog . " FAILED : " . $e->getMessage());
			$this->getTransactionManager()->rollBack($e);
		}
		return VIEW::NONE;
	}
	
	
	/**
	 * @return Boolean
	 */
	public final function isSecure()
	{
		return false;
	}	
}
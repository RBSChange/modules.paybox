<?php
/**
 * paybox_BankResponseAction
 * @package modules.paybox.actions
 */
class paybox_BankResponseAction extends f_action_BaseAction
{
	
	protected $returnType = 'Cancel';
	
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{
	    $remoteAddr = $_SERVER['REMOTE_ADDR'];
        $requestUri = $_SERVER['REQUEST_URI'];
		$ms = payment_ModuleService::getInstance();
		$baseLog = "BANKING PAYBOX " . $this->returnType;		
		$ms->log($baseLog . " from [".$remoteAddr." : ".$requestUri."] BEGIN");     		  
		try
		{
			$this->getTransactionManager()->beginTransaction();	
			$connectorService = paybox_PayboxconnectorService::getInstance();
			$sessionInfo = $connectorService->getSessionInfo();
			if (count($sessionInfo) == 0) {throw new Exception('Session expired');}	
			
			if ($this->returnType === 'Cancel')
			{
				$url = LinkHelper::getTagUrl('contextual_website_website_modules_order_cart');
				$connectorService->setSessionInfo(array());
			}
			else
			{
				if (strpos($requestUri, '?') === false) {throw new Exception('InvalidURL');}	
				
				$queryString = substr($requestUri, strpos($requestUri, '?') + 1);
				
				$bankResponse = $connectorService->getBankResponse($this->returnType, $queryString);				
				$order = $bankResponse->getOrder();
	
				//En production le listener ce charge de compléter la commande
				if (Framework::inDevelopmentMode())
				{
					$connectorService->setPaymentResult($bankResponse, $order);
				}
	            else
	            {
					if (f_util_StringUtils::isEmpty($order->getPaymentStatus()))
					{
						$order->setPaymentStatus('waiting');
					}
	            }	            
				$connectorService->setSessionInfo(array());									
				$url = $sessionInfo['paymentURL'];
				$ms->log($baseLog ." END AND REDIRECT: " . $url);
			}
			$this->getTransactionManager()->commit();
		}
		catch(Exception $e)
		{
			$ms->log($baseLog ." FAILED : " . $e->getMessage());
			Framework::exception($e);
			$this->getTransactionManager()->rollBack($e);
						
			$currentWebsite = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			$url = $currentWebsite->getUrl();
		}
		
		$request->setParameter('location', $url);
		controller_ChangeController::getInstance()->forward('website','Redirect');
		return VIEW::NONE;	
	}

	/**
	 * @return Integer
	 */
	public function getRequestMethods()
	{
		return Request::POST | Request::GET;
	}

	/**
	 * @return Boolean
	 */
	public final function isSecure()
	{
		return false;
	}
}
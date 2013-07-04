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
		$ms->log($baseLog . " from [" . $remoteAddr . " : " . $requestUri . "] BEGIN");
		try
		{
			$this->getTransactionManager()->beginTransaction();
			if (strpos($requestUri, '?') === false)
			{
				throw new Exception('InvalidURL');
			}
			$queryString = substr($requestUri, strpos($requestUri, '?') + 1);
			$connectorService = paybox_PayboxconnectorService::getInstance();
			$bankResponse = $connectorService->getBankResponse('Listener', $queryString);
			$order = $bankResponse->getOrder();
			
			if ($bankResponse->isAccepted())
			{
				$connectorService->setPaymentResult($bankResponse, $order);
			}
			else
			{
				$paymentNumber = $order->getMeta('paymentNumber');
				$paymentNumber += 1;
				$order->setMeta('paymentNumber', $paymentNumber);
				$order->saveMeta();
				
				if ($paymentNumber >= 3)
				{
					// Cancel order
					$connectorService->setPaymentResult($bankResponse, $order);
					Framework::info('Cancel Order after 3 failed payment');
				}
				else
				{
					// Create task to cancel order in few minutes
					$date = date_Calendar::getInstance();
					$date->add(date_Calendar::MINUTE, 30);
					
					$refreshListTask = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
					$refreshListTask->setSystemtaskclassname('paybox_CancelOrderTask');
					$refreshListTask->setLabel(__METHOD__);
					$refreshListTask->setParameters(serialize(array('bankResponse' => $bankResponse)));
					$refreshListTask->setUniqueExecutiondate($date);
					$refreshListTask->save();
					
					Framework::info('Task paybox_CancelOrderTask launch');
				}
			
			}
			
			$ms->log($baseLog . " END");
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
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
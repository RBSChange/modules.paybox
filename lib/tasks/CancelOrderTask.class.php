<?php
class paybox_CancelOrderTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$ms = payment_ModuleService::getInstance();
		$baseLog = "BANKING PAYBOX CancelOrderTask";
		
		$bankResponse = $this->getParameter('bankResponse');
		$paymentOrder = $bankResponse->getOrder();
		
		$order = $paymentOrder->getOrder();
		
		if ($order->getOrderStatus() == order_OrderService::INITIATED)
		{
			Framework::info('CANCEL ORDER');
			
			$tm = f_persistentdocument_TransactionManager::getInstance();
			
			try
			{
				$tm->beginTransaction();
				
				$connectorService = paybox_PayboxconnectorService::getInstance();
				$connectorService->setPaymentResult($bankResponse, $paymentOrder);
				
				$tm->commit();
			}
			catch (Exception $e)
			{
				$ms->log($baseLog . " FAILED : " . $e->getMessage());
				$tm->rollBack($e);
			}
		}
	
	}
}
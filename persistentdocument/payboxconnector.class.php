<?php
/**
 * Class where to put your custom methods for document paybox_persistentdocument_payboxconnector
 * @package modules.paybox.persistentdocument
 */
class paybox_persistentdocument_payboxconnector extends paybox_persistentdocument_payboxconnectorbase 
{
	public function getTemplateViewName()
	{
		return 'Paybox';
	}
	
	public function getPaymentIcons()
	{
		$s = $this->getCartes();
		if (empty($s))
		{
			$data = array();
			$list = list_ListService::getInstance()->getByListId('modules_paybox/cartes');
			foreach ($list->getItems() as $item) 
			{
				/* @var $item list_Item */
				$data[] = $item->getValue();
			}
		}
		else
		{
			$data = explode(',', $s);
		}
		
		$result = array();
		foreach ($data as $name)
		{
			$result[] = MediaHelper::getFrontofficeStaticUrl('p_CHOIXPAIEMENT_' . $name . '.gif');
		}
		return $result;
	}
}
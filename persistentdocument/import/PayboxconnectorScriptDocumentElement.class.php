<?php
/**
 * paybox_PayboxconnectorScriptDocumentElement
 * @package modules.paybox.persistentdocument.import
 */
class paybox_PayboxconnectorScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return paybox_persistentdocument_payboxconnector
     */
    protected function initPersistentDocument()
    {
    	return paybox_PayboxconnectorService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_paybox/payboxconnector');
	}
}
<?php
/**
 * @package modules.paybox.setup
 */
class paybox_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->executeModuleScript('init.xml');
		
		
		$mbs = uixul_ModuleBindingService::getInstance();
		$mbs->addImportInPerspective('payment', 'paybox', 'payment.perspective');
		$mbs->addImportInActions('payment', 'paybox', 'payment.actions');
		$result = $mbs->addImportform('payment', 'modules_paybox/payboxconnector');
		if ($result['action'] == 'create')
		{
			uixul_DocumentEditorService::getInstance()->compileEditorsConfig();
		}
		f_permission_PermissionService::getInstance()->addImportInRight('payment', 'paybox', 'payment.rights');
		
		$srcPath = f_util_FileUtils::buildWebeditPath('modules', 'paybox', 'templates', 'payment');
		$destPath  = f_util_FileUtils::buildOverridePath('modules', 'payment', 'templates');
		
		f_util_FileUtils::cp($srcPath . '/Payment-Block-Payment-Paybox.all.all.html',
				$destPath . '/Payment-Block-Payment-Paybox.all.all.html', f_util_FileUtils::OVERRIDE);
		
		f_util_FileUtils::cp($srcPath . '/Payment-Inc-Selection-Paybox.all.all.html',
				$destPath . '/Payment-Inc-Selection-Paybox.all.all.html', f_util_FileUtils::OVERRIDE);
		
	}

	/**
	 * @return String[]
	 */
	public function getRequiredPackages()
	{
		// Return an array of packages name if the data you are inserting in
		// this file depend on the data of other packages.
		// Example:
		// return array('modules_website', 'modules_users');
		return array();
	}
}
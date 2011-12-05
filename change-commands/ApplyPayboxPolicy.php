<?php
class commands_ApplyPayboxPolicy extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}
	
	/**
	 * @return String
	 */
	function getDescription()
	{
		return "";
	}
	
	/**
	 * @return Boolean default false
	 */
	function isHidden()
	{
		return true;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Apply Paybox Binaries Policy ==");
		$path = f_util_FileUtils::buildWebeditPath('modules', 'paybox', 'webapp', 'bin');
		$files = scandir($path);
		foreach ($files as $file)
		{
			if ($file[0] === '.') {continue;}
			$checkPath = f_util_FileUtils::buildWebeditPath('bin', $file);
			if (file_exists($checkPath))
			{
				$this->log('Update file policy of: ' . $checkPath);
				f_util_FileUtils::chmod($checkPath, "755");
			}
		}
	}
}
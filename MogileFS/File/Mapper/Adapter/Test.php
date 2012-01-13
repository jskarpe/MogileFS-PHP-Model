<?php
/**
 *
 * Test adapter for MogileFS
 * @author Jon Skarpeteig <jon.skarpeteig@gmail.com>
 *
 */
class MogileFS_File_Mapper_Adapter_Test extends MogileFS_File_Mapper_Adapter_Abstract
{

	protected $_saveResult = array();

	public function findPaths($key)
	{
		return isset($this->_saveResult[$key]['paths']) ? $this->_saveResult[$key]['paths'] : null;
	}

	public function findInfo($key)
	{
		return isset($this->_saveResult[$key]) ? $this->_saveResult[$key] : null;
	}

	public function fetchAllPaths(array $keys)
	{
		$result = array();
		foreach ($keys as $key) {
			$paths = $this->findPaths($key);
			if (null !== $paths) {
				$result[$key] = $this->findPaths($key);
			}
		}
		return $result;
	}

	public function saveFile($key, $file, $class = null)
	{
		$options = $this->getOptions();
		if (!isset($options['domain'])) {
			require_once 'MogileFS/Exception.php';
			throw new MogileFS_Exception(
					__METHOD__ . ' Mandatory option \'domain\' missing from options',
					MogileFS_Exception::MISSING_OPTION);
		}

		$fid = rand(0, 1000);
		$this->_saveResult[$key] = array(
				'fid' => $fid,
				'key' => $key,
				'size' => 123,
				'paths' => array(
					'http://127.0.0.1/' . $fid . '.fid'
				),
				'domain' => $options['domain'],
				'class' => (null === $class) ? 'default' : $class
		);

		return $this->_saveResult[$key];
	}

	public function rename($fromKey, $toKey)
	{
		return;
	}

	public function delete($key)
	{
		if (isset($this->_saveResult[$key])) {
			unset($this->_saveResult[$key]);
		}
		return;
	}
}

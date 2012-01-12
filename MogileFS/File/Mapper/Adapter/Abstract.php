<?php
abstract class MogileFS_File_Mapper_Adapter_Abstract
{
	private $options;
	
	public function __construct(array $options = null)
	{
		if (null !== $options) {
			$this->setOptions($options);
		}
	}
	
	public function setOptions(array $options)
	{
		$this->options = $options;
		return $this;
	}
	
	public function getOptions()
	{
		return $this->options;
	}
	
	/**
	 * 
	 * Looks up paths for key
	 * @param string $key
	 * @param boolean $eagerLoad
	 * @return array of string uri paths
	 */
	abstract function findPaths($key);
	
	/**
	 * 
	 * Look up info such as class and fid
	 * @param unknown_type $key
	 */
	abstract function findInfo($key);
	
	/**
	*
	* Looks up paths for key
	* @param array $keys
	* @return array of string uri paths indexed by key
	*/
	abstract function fetchAllPaths(array $keys);
	
	/**
	 * 
	 * Saves file to MogileFS
	 * @param string $key
	 * @param string $file
	 */
	abstract function saveFile($key, $file, $class = null);
	
	/**
	 * 
	 * Renames a key
	 * @param string $fromKey
	 * @param string $toKey
	 */
	abstract function rename($fromKey, $toKey);

	/**
	 * 
	 * Deletes file from MogileFS
	 * @param string $key
	 */
	abstract function delete($key);
}
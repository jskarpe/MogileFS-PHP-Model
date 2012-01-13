<?php

class MogileFS_File_Mapper
{
	protected $_options;
	protected $_adapter;

	public function __construct(array $options = null)
	{
		if (null !== $options) {
			$this->setOptions($options);
		}
	}

	public function setOptions(array $options)
	{
		$this->_options = $options;
		return $this;
	}

	public function getOptions()
	{
		return $this->_options;
	}

	public function setAdapter(MogileFS_File_Mapper_Adapter_Abstract $adapter)
	{
		$this->_adapter = $adapter;
		return $this;
	}

	public function getAdapter()
	{
		if (!$this->_adapter instanceof MogileFS_File_Mapper_Adapter_Abstract) {
			$options = $this->getOptions();
			if (!isset($options['adapter'])) {
				require_once 'MogileFS/Exception.php';
				throw new MogileFS_Exception(
						__METHOD__
								. ' No adapter set, and no \'adapter\' section with adapter options found',
						MogileFS_Exception::MISSING_OPTION);
			}

			if ($options['adapter'] instanceof MogileFS_File_Mapper_Adapter_Abstract) {
				$this->setAdapter($options['adapter']);
				return $this->_adapter;
			}
			
			if (!isset($options['defaultadapter'])) {
				require_once 'MogileFS/Exception.php';
				throw new MogileFS_Exception(
						__METHOD__
								. ' No adapter set, and no \'defaultadapter\' option with classname found in options',
						MogileFS_Exception::MISSING_OPTION);
			}

			$adapterFile = str_replace('_', '/', $options['defaultadapter']).'.php';
			require_once $adapterFile;
			$this->setAdapter(new $options['defaultadapter']($options['adapter']));
		}
		return $this->_adapter;
	}

	public function find($key, $eagerLoad = false)
	{
		require_once 'MogileFS/File.php';
		$file = new MogileFS_File();
		$file->setKey($key);
		$file->setMapper($this);

		$adapter = $this->getAdapter();
		$result = $adapter->findPaths($key);
		if (null === $result) {
			return null;
		}
		$file->setPaths($result);

		if (false !== $eagerLoad) {
			$info = $adapter->findInfo($key);
			$file->fromArray($info);
		} else {
			// Get fid from path
			preg_match('/\d+\.fid$/', reset($result), $match);
			$file->setFid($match[0]);
		}

		return $file;
	}

	public function findInfo(MogileFS_File $file)
	{
		$info = $this->getAdapter()->findInfo($file->getKey());
		if (null == $info) {
			return null;
		}
		$file->fromArray($info);
		return $file;
	}

	public function fetchAll(array $keys = null, $eagerLoad = false)
	{
		$files = array();
		$paths = $this->getAdapter()->fetchAllPaths($keys);
		if (empty($paths)) {
			return null;
		}
		foreach ($paths as $key => $pathArray) {
			$file = new MogileFS_File(array(
				'key' => $key,
				'paths' => $pathArray
			));
			$file->setMapper($this);
			if (false !== $eagerLoad) {
				$this->findInfo($file);
			}
			$files[$key] = $file;
		}
		return $files;
	}

	/**
	 * 
	 * Download file from MogileFS into a local temp file
	 * @param MogileFS_File $file
	 * @throws MogileFS_Exception
	 */
	public function fetchFile(MogileFS_File $file)
	{
		if (!$file->isValid()) {
			throw new MogileFS_Exception(
					__METHOD__ . ' Cannot fetch file from invalid file model',
					MogileFS_Exception::INVALID_ARGUMENT);
		}

		$localFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file->getKey();
		$fp = fopen($localFile, 'w');
		$url = reset($file->getPaths());

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		$data = curl_exec($ch);
		curl_close($ch);
		fclose($fp);

		$file->setFile($localFile);
		return $file;
	}

	/**
	 * 
	 * Uploads file to MogileFS, and populate model with
	 * returned values (such as fid)
	 * @param MogileFS_File $file
	 * @throws MogileFS_Exception
	 * @return MogileFS_File stored in MogileFS
	 */
	public function save(MogileFS_File $file)
	{
		if (!$file->isValid()) {
			throw new MogileFS_Exception(__METHOD__ . ' Cannot save invalid file model',
					MogileFS_Exception::INVALID_ARGUMENT);
		}

		$result = $this->getAdapter()
				->saveFile($file->getKey(), $file->getFile(false), $file->getClass(false));
		
		$storedFile = $this->find($file->getKey(), true);
		return $storedFile;
	}

	public function delete($key)
	{
		$result = $this->getAdapter()->delete($key);
		return $result;
	}

}

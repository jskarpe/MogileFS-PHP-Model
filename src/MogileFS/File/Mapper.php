<?php
/**
 * 
 * Mapper for MogileFS_File which populates file model with properties returned from adapters
 * @author Jon Skarpeteig <jon.skarpeteig@gmail.com>
 * @package MogileFS
 *
 */
class MogileFS_File_Mapper
{
	/**
	 * 
	 * Configuration options for mapper
	 * @var array
	 */
	protected $_options;

	/**
	 * 
	 * Holds instance of adapter
	 * @var MogileFS_File_Mapper_Adapter_Abstract
	 */
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
				throw new MogileFS_Exception(
						__METHOD__ . ' No adapter set, and no \'adapter\' section with adapter options found',
						MogileFS_Exception::MISSING_OPTION);
			}

			if ($options['adapter'] instanceof MogileFS_File_Mapper_Adapter_Abstract) {
				$this->setAdapter($options['adapter']);
				return $this->_adapter;
			}

			$default = (isset($options['defaultadapter'])) ? $options['defaultadapter']
					: 'MogileFS_File_Mapper_Adapter_Tracker';

			$this->setAdapter(new $default($options['adapter']));
		}
		return $this->_adapter;
	}

	public function find($key, $eagerLoad = false)
	{
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
			$file = new MogileFS_File(array('key' => $key, 'paths' => $pathArray));
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
			throw new MogileFS_Exception(__METHOD__ . ' Cannot fetch file from invalid file model',
					MogileFS_Exception::INVALID_ARGUMENT);
		}

		$localFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file->getKey();

		$fp = fopen($localFile, 'w');
		$paths = $file->getPaths();
		$url = reset($paths);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		$response = curl_exec($ch);

		// Check for errors
		$error = curl_error($ch);
		$errno = curl_errno($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		fclose($fp);
		if ($response === false || 0 !== $errno) {
			throw new MogileFS_Exception(__METHOD__ . " $error for $url", MogileFS_Exception::UNKNOWN_ERROR);
		}

		if (200 != $statusCode) {
			throw new MogileFS_Exception(
					__METHOD__ . ' GET \'' . $url . '\' failed. Expected status code 200, got: ' . $statusCode,
					MogileFS_Exception::SERVER_ERROR);
		}

		$file->setFile($localFile);
		return $file;
	}

    public function fetchResourceHandler(MogileFS_File $file)
    {
        if (!$file->isValid()) {
            throw new MogileFS_Exception(__METHOD__ . ' Cannot fetch file from invalid file model',
                MogileFS_Exception::INVALID_ARGUMENT);
        }

        $paths = $file->getPaths();
        $url = reset($paths);

        $ch = curl_init($url);
        $bodyStream = fopen('php://temp', 'w+');

        curl_setopt($ch, CURLOPT_FILE, $bodyStream);

        $response = curl_exec($ch);

        // Check for errors
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        rewind($bodyStream);

        if ($response === false || 0 !== $errno) {
            throw new MogileFS_Exception(__METHOD__ . " $error for $url", MogileFS_Exception::UNKNOWN_ERROR);
        }

        if (200 != $statusCode) {
            throw new MogileFS_Exception(
                __METHOD__ . ' GET \'' . $url . '\' failed. Expected status code 200, got: ' . $statusCode,
                MogileFS_Exception::SERVER_ERROR);
        }
        return $bodyStream;
    }

	/**
	 * 
	 * Uploads file to MogileFS, and populate model with
	 * returned values (such as fid)
	 * @param MogileFS_File $file
	 * @throws MogileFS_Exception
	 * @return MogileFS_File stored in MogileFS
	 */
	public function save(MogileFS_File $file, $retry = 0)
	{
		if (!$file->isValid()) {
			throw new MogileFS_Exception(__METHOD__ . ' Cannot save invalid file model',
					MogileFS_Exception::INVALID_ARGUMENT);
		}
		$file->setMapper($this);
		$adapter = $this->getAdapter();
		try {
			$adapter->saveFile($file->getKey(), $file->getFile(false), $file->getClass(false));
		} catch (MogileFS_Exception $e) {
			switch ($e->getCode()) {
				case MogileFS_Exception::TRACKER_ERROR:
				case MogileFS_Exception::SERVER_ERROR:
					$options = $this->getOptions();
					if (isset($options['upload_retries']) && $retry < $options['upload_retries']) {
						$delay = (isset($options['retry_delay'])) ? $options['retry_delay'] : 5;
						sleep($delay);
						return $this->save($file, $retry++);
					} else {
						throw new MogileFS_Exception(
								'Failed saving file \'' . $file->getKey() . '\'. ' . $e->getMessage() . ' Tried '
										. ($retry + 1) . ' time(s).', $e->getCode());
					}
				default:
					throw $e;
			}
		}

		$storedFile = $this->find($file->getKey(), false);
		$storedFileArray = $storedFile->toArray();
		unset($storedFileArray['file']); // MogileFS has no information about local file
		$file->fromArray($storedFileArray);

		return $file;
	}

	public function delete($key)
	{
		$result = $this->getAdapter()->delete($key);
		return $result;
	}

}

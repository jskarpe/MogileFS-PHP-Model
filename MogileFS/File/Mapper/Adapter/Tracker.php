<?php
/**
 * 
 * Adapter for native MogileFS socket connection
 * @author Jon Skarpeteig <jon.skarpeteig@gmail.com>
 *
 */
class MogileFS_File_Mapper_Adapter_Tracker extends MogileFS_File_Mapper_Adapter_Abstract
{
	/**
	 * Socket resource
	 */
	protected $_socket;

	/**
	 * (non-PHPdoc)
	 * @see MogileFS_File_Mapper_Adapter_Abstract::findPaths()
	 */
	public function findPaths($key)
	{
		// Validate argument
		if (!is_string($key) || empty($key)) {
			throw new MogileFS_Exception(
					__METHOD__ . ' Expected non-empty string argument, got: '
							. (gettype($key) === 'string') ? $key : gettype($key),
					MogileFS_Exception::INVALID_ARGUMENT);
		}

		// Construct request
		$parameters = array(
			'key' => $key
		);
		$options = $this->getOptions();
		if (isset($options['pathcount'])) {
			$parameters['pathcount'] = $options['pathcount'];
		}
		if (isset($options['noverify'])) {
			$parameters['noverify'] = $options['noverify'];
		}

		// Send request to server
		$result = $this->_sendRequest('GET_PATHS', $parameters);

		// Parse result
		if ('unknown_key' == $result) {
			return null;
		}

		// Extract paths from result
		parse_str($result, $paths);
		unset($paths['paths']);
		return $paths;
	}

	/**
	 * (non-PHPdoc)
	 * @see MogileFS_File_Mapper_Adapter_Abstract::findInfo()
	 */
	public function findInfo($key)
	{
		// Validate argument
		if (!is_string($key) || empty($key)) {
			throw new MogileFS_Exception(
					__METHOD__ . ' Expected non-empty string argument, got: '
							. (gettype($key) === 'string') ? $key : gettype($key),
					MogileFS_Exception::INVALID_ARGUMENT);
		}

		$result = $this->_sendRequest('FILE_INFO', array(
					'key' => $key
				));
		parse_str($result, $info);
		if (!isset($info['fid'])) {
			return null;
		}

		// Format into expected format
		if (isset($info['length'])) {
			$info['size'] = $info['length'];
			unset($info['length']);
		}
		return $info;
	}

	/**
	 * (non-PHPdoc)
	 * @see MogileFS_File_Mapper_Adapter_Abstract::fetchAllPaths()
	 */
	public function fetchAllPaths(array $keys)
	{
		/**
		 * Bulk process is not supported natively in tracker,
		 * so have to iterate through keys one by one
		 * (checked versions <= 2.55)
		 */
		$paths = array();
		foreach ($keys as $key) {
			if (!is_string($key) || empty($key)) {
				throw new MogileFS_Exception(
						__METHOD__ . ' Expected non-empty string argument, got: '
								. (gettype($key) === 'string') ? $key : gettype($key),
						MogileFS_Exception::INVALID_ARGUMENT);
			}
			$pathsForKey = $this->findPaths($key);
			if (!empty($pathsForKey)) {
				$paths[$key] = $this->findPaths($key);
			}
		}
		return $paths;
	}

	/**
	 * (non-PHPdoc)
	 * @see MogileFS_File_Mapper_Adapter_Abstract::rename()
	 */
	public function rename($fromKey, $toKey)
	{
		if (!is_string($fromKey) || empty($fromKey)) {
			throw new MogileFS_Exception(
					__METHOD__ . ' Expected non-empty fromKey string argument, got: '
							. (gettype($fromKey) === 'string') ? $fromKey : gettype($fromKey),
					MogileFS_Exception::INVALID_ARGUMENT);
		}

		if (!is_string($toKey) || empty($toKey)) {
			throw new MogileFS_Exception(
					__METHOD__ . ' Expected non-empty toKey string argument, got: '
							. (gettype($toKey) === 'string') ? $toKey : gettype($toKey),
					MogileFS_Exception::INVALID_ARGUMENT);
		}

		$result = $this
				->_sendRequest('RENAME',
						array(
							'from_key' => $fromKey, 'to_key' => $toKey
						));
	}

	/**
	 * (non-PHPdoc)
	 * @see MogileFS_File_Mapper_Adapter_Abstract::saveFile()
	 */
	public function saveFile($key, $file, $class = null)
	{
		if (!is_string($key) || empty($key)) {
			throw new MogileFS_Exception(
					__METHOD__ . ' Expected non-empty string argument, got: '
							. (gettype($key) === 'string') ? $key : gettype($key),
					MogileFS_Exception::INVALID_ARGUMENT);
		}

		if (!file_exists($file)) {
			throw new MogileFS_Exception(__METHOD__ . ' File not found: ' . $file,
					MogileFS_Exception::INVALID_ARGUMENT);
		}

		$fh = fopen($file, 'r');
		if ($fh === false) {
			throw new MogileFS_Exception(__METHOD__ . ' Failed to open ' . $file . ' for reading',
					MogileFS_Exception::FOPEN_FAILIURE);
		}

		$params = array(
			'key' => $key
		);
		if (null !== $class) {
			$params['class'] = $class;
		}
		$result = $this->_sendRequest('CREATE_OPEN', $params);

		parse_str($result, $location);
		$uri = $location['path'];
		$parts = parse_url($uri);
		$host = $parts['host'];
		$port = $parts['port'];
		$path = $parts['path'];

		$requestTimeout = isset($options['request_timeout']) ? $options['request_timeout'] : null;

		$ch = curl_init();
		// 		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_INFILE, $fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
		curl_setopt($ch, CURLOPT_TIMEOUT, $requestTimeout);
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Expect: '
		));
		$response = curl_exec($ch);
		fclose($fh);
		$error = curl_error($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		if ($response === false) {
			throw new MogileFS_Exception(__METHOD__ . " $error for $uri",
					MogileFS_Exception::UNKNOWN_ERROR);
		}
		$params = array(
				'key' => $key,
				'devid' => $location['devid'],
				'fid' => $location['fid'],
				'path' => urldecode($uri)
		);
		$this->_sendRequest('CREATE_CLOSE', $params);

		unset($params['path']);
		$params['paths'] = urldecode($uri);
		$params['class'] = (null === $class) ? 'default' : $class;
		$options = $this->getOptions();
		$params['domain'] = $options['domain'];
		return $params;
	}

	/**
	 * (non-PHPdoc)
	 * @see MogileFS_File_Mapper_Adapter_Abstract::delete()
	 */
	public function delete($key)
	{
		if (!is_string($key) || empty($key)) {
			throw new MogileFS_Exception(
					__METHOD__ . ' Expected non-empty string argument, got: '
							. (gettype($key) === 'string') ? $key : gettype($key),
					MogileFS_Exception::INVALID_ARGUMENT);
		}
		$result = $this->_sendRequest('DELETE', array(
					'key' => $key
				));
	}

	/**
	 * 
	 * Send request through socket to tracker
	 * @param string $cmd
	 * @param array $args
	 * @throws MogileFS_Exception
	 * @return string result from server
	 */
	protected function _sendRequest($cmd, array $args = null)
	{
		// Validate arguments
		if (null === $cmd) {
			throw new MogileFS_Exception(__METHOD__ . ' Empty argument',
					MogileFS_Exception::EMPTY_ARGUMENT);
		}

		// Read options
		$options = $this->getOptions();
		if (!isset($options['domain'])) {
			throw new MogileFS_Exception(
					__METHOD__ . ' Mandatory option \'domain\' missing from options',
					MogileFS_Exception::MISSING_OPTION);
		}
		$args['domain'] = $options['domain'];

		// Construct full command
		$params = '';
		foreach ($args as $key => $value) {
			if ($key == 'path') {
				// Special field
				$params .= '&' . urlencode($key) . '=' . $value;
				continue;
			}
			$params .= '&' . urlencode($key) . '=' . urlencode($value);
		}

		// Send command to server
		$socket = $this->_getConnection();
		$request = $cmd . ' ' . $params;
		if (false === fwrite($socket, $request . "\n")) {
			throw new MogileFS_Exception(__METHOD__ . ' Write failed',
					MogileFS_Exception::WRITE_FAILED);
		}

		// Read response
		$line = fgets($socket);
		if ($line === false) {
			throw new MogileFS_Exception(__METHOD__ . ' Read failed',
					MogileFS_Exception::READ_FAILED);
		}

		// Parse response
		$words = explode(' ', $line);
		if ($words[0] == 'OK') {
			return trim($words[1]);
		} elseif ($words[0] == 'ERR') {
			switch (trim($words[1])) {
				case 'unknown_key':
					return trim($words[1]);
					break;
				default:
					throw new MogileFS_Exception(
							__METHOD__ . ' Fatal MogileFS error: \'' . $words[1]
									. '\' from request: \'' . $request . '\'',
							MogileFS_Exception::TRACKER_ERROR);
					break;
			}
		}

		throw new MogileFS_Exception(__METHOD__ . ' Unable to parse response: ' . $line,
				MogileFS_Exception::TRACKER_ERROR);
	}

	/**
	 * Connect to a mogilefsd; scans through the list of daemons and tries to connect one.
	 * @return resource
	 */
	protected function _getConnection()
	{
		if ($this->_socket && is_resource($this->_socket) && !feof($this->_socket)) {
			return $this->_socket;
		}

		// Read options
		$options = $this->getOptions();
		if (!isset($options['tracker'])) {
			throw new MogileFS_Exception(
					__METHOD__ . ' Required option \'tracker\' not found in options',
					MogileFS_Exception::MISSING_OPTION);
		}

		if (!is_array($options['tracker'])) {
			throw new MogileFS_Exception(__METHOD__ . ' Option \'tracker\' must be an array',
					MogileFS_Exception::INVALID_OPTION);
		}

		foreach ($options['tracker'] as $host) {
			$parts = parse_url($host);
			if (!isset($parts['port'])) {
				$parts['port'] = 7001;
			}

			$errno = null;
			$errstr = null;
			$requestTimeout = isset($options['request_timeout']) ? $options['request_timeout']
					: null;
			$this->_socket = fsockopen($parts['host'], $parts['port'], $errno, $errstr,
					$requestTimeout);
			if ($this->_socket) {
				break;
			}
		}

		if (!is_resource($this->_socket) || feof($this->_socket)) {
			throw new MogileFS_Exception(__METHOD__ . 'Failed to obtain connection',
					MogileFS_Exception::CONNECT_FAILED);
		} else {
			return $this->_socket;
		}
	}
}

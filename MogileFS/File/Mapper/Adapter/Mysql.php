<?php
class MogileFS_File_Mapper_Adapter_Mysql extends MogileFS_File_Mapper_Adapter_Abstract
{
	protected $_hostsUp;
	protected $_mysql;

	public function findPaths($key)
	{
		throw new MogileFS_Exception(__METHOD__ . ' Not supported',
				MogileFS_Exception::UNSUPPORTED_METHOD);
	}

	public function findInfo($key)
	{
		throw new MogileFS_Exception(__METHOD__ . ' Not supported',
				MogileFS_Exception::UNSUPPORTED_METHOD);
	}

	public function fetchAllPaths(array $keys)
	{
		$options = $this->getOptions();
		if (!isset($options['domain'])) {
			throw new MogileFS_Exception(__METHOD__ . ' No \'domain\' option found in config',
					MogileFS_Exception::INVALID_CONFIGURATION);
		}

		$query = "
			SELECT
			 f.fid,
			 f.dkey,
			 de.devid,
			 h.hostip,
			 h.http_port,
			 h.http_get_port
			FROM
			 file f
			INNER JOIN domain d ON d.dmid = f.dmid
			INNER JOIN file_on fo ON fo.fid = f.fid
			INNER JOIN device de ON de.devid = fo.devid
			INNER JOIN host h ON h.hostid = de.hostid
			WHERE
			 d.namespace = '" . $options['domain'] . "'
			 AND h.hostid IN (" . implode(',', $this->getHostsUp()) . ")
			 AND f.dkey IN('" . implode("','", $keys) . "')
			GROUP BY f.fid
		";

		$stm = $this->getMysql()->prepare($query);
		$stm->execute();
		$resultArray = $stm->fetchAll();

		// TODO implement ZoneLocal emulation

		$paths = array();
		foreach ($resultArray as $row) {
			$fid = sprintf('%010d', $row['fid']);
			$port = (empty($row['http_get_port'])) ? $row['http_port'] : $row['http_get_port'];
			$port = ($port == '80') ? null : ':' . $port;
			$uri = 'http://' . $row['hostip'] . $port . '/dev' . $row['devid'] . '/'
					. substr($fid, 0, 1) . '/' . substr($fid, 1, 3) . '/' . substr($fid, 4, 3)
					. '/' . $fid . '.fid';
			$paths[$row['dkey']][] = $uri;
		}

		return $paths;
	}

	public function listKeys($prefix = null, $lastKey = null, $limit = null)
	{
		throw new MogileFS_Exception(__METHOD__ . ' Not supported',
				MogileFS_Exception::UNSUPPORTED_METHOD);
	}

	public function saveFile($key, $file, $class = null)
	{
		throw new MogileFS_Exception(__METHOD__ . ' Not supported',
				MogileFS_Exception::UNSUPPORTED_METHOD);
	}

	public function rename($fromKey, $toKey)
	{
		throw new MogileFS_Exception(__METHOD__ . ' Not supported',
				MogileFS_Exception::UNSUPPORTED_METHOD);
	}

	public function delete($key)
	{
		throw new MogileFS_Exception(__METHOD__ . ' Not supported',
				MogileFS_Exception::UNSUPPORTED_METHOD);
	}

	/**
	 * 
	 * Set id of hosts that are up
	 * @param array $hostsUp
	 */
	public function setHostsUp(array $hostsUp)
	{
		$this->_hostsUp = $hostsUp;
		return $this;
	}

	public function getHostsUp()
	{
		if (null == $this->_hostsUp) {

			$hostsUp = array();

			// Find all servers
			$query = "
			SELECT hostid,hostip,http_port,http_get_port FROM host h WHERE status = 'alive';
		";
			$stm = $this->getMysql()->prepare($query);
			$stm->execute();
			$hosts = $stm->fetchAll();

			// Determine what server is online
			$mh = curl_multi_init();
			$i = 0;
			foreach ($hosts as $hostRow) {
				$port = (empty($hostRow['http_get_port'])) ? $hostRow['http_port']
						: $hostRow['http_get_port'];
				$ch[$i] = curl_init();
				curl_setopt($ch[$i], CURLOPT_URL, $hostRow['hostip'] . ':' . $port);
				curl_setopt($ch[$i], CURLOPT_CONNECTTIMEOUT, 1);
				curl_setopt($ch[$i], CURLOPT_TIMEVALUE, 1);
				curl_setopt($ch[$i], CURLOPT_FOLLOWLOCATION, false);
				curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch[$i], CURLOPT_HEADER, 0); // Don't include the header
				//curl_setopt($ch[$i], CURLOPT_FRESH_CONNECT, 1); //Don't use cache
				curl_multi_add_handle($mh, $ch[$i]);
				$i++;
			}

			// Execute the handles
			$running = 0;
			$runtime = explode(' ', microtime());
			$runtime = $runtime[1] + $runtime[0];
			do {
				$r = explode(' ', microtime());
				$r = $r[1] + $r[0];
				if (($r - $runtime) > 0.4) {
					// Only run for 0.4s
					break;
				}
				curl_multi_exec($mh, $running);
				usleep(1); // Don't hog cpu!
			} while ($running > 0);

			// Close the handles
			$i = 0;
			foreach ($hosts as $hostRow) {
				if (curl_getinfo($ch[$i], CURLINFO_CONNECT_TIME) > 0) {
					$hostsUp[] = $hostRow['hostid'];
				}
				curl_multi_remove_handle($mh, $ch[$i]);
				$i++;
			}
			curl_multi_close($mh);

			$this->_hostsUp = $hostsUp;
		}
		return $this->_hostsUp;
	}

	public function getMysql()
	{
		if ($this->_mysql instanceof PDO) {
			return $this->_mysql;
		}

		$options = $this->getOptions();

		if (!isset($options['pdo_options'])) {
			throw new MogileFS_Exception(
					__METHOD__
							. ' No mysql client set, and no \'pdo_options\' option found in config',
					MogileFS_Exception::INVALID_CONFIGURATION);
		}

		if (!isset($options['username'])) {
			throw new MogileFS_Exception(
					__METHOD__ . ' No mysql client set, and no \'username\' option found in config',
					MogileFS_Exception::INVALID_CONFIGURATION);
		}

		if (!isset($options['password'])) {
			throw new MogileFS_Exception(
					__METHOD__ . ' No mysql client set, and no \'password\' option found in config',
					MogileFS_Exception::INVALID_CONFIGURATION);
		}

		$this->_mysql = $pdo = new PDO('mysql:' . $options['pdo_options'], $options['username'],
				$options['password']);

		return $this->_mysql;
	}
}

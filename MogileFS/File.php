<?php
/**
 * 
 * File model class for files stored in MogileFS
 * @author Jon Skarpeteig <jon.skarpeteig@gmail.com>
 * @package MogileFS
 * 
 */
class MogileFS_File
{
	/**
	 * 
	 * Which replication class file belongs to
	 * @var string
	 */
	protected $_class;

	/**
	 * 
	 * Which domain the key belongs to
	 * @var string
	 */
	protected $_domain;

	/**
	 * 
	 * Assigned file id (fid) of key in database - read only
	 * @var integer
	 */
	protected $_fid;

	/**
	 * 
	 * Local copy of file stored in MogileFS
	 * @var string
	 */
	protected $_file;

	/**
	 * 
	 * Name (index) of file
	 * @var string
	 */
	protected $_key;

	/**
	 * 
	 * Filesize (length) of file - read only
	 * @var integer
	 */
	protected $_size;

	/**
	 * 
	 * List of URIs to location of file within MogileFS - read only
	 * @var array of string
	 */
	protected $_paths;

	/**
	 * 
	 * Holds instance of mapper
	 * @var MogileFS_File_Mapper
	 */
	protected $_mapper;

	public function __construct(array $attributes = null)
	{
		if (null !== $attributes) {
			$this->fromArray($attributes);
		}
	}

	public function isValid()
	{
		return ((null !== $this->getKey())
				&& (null !== $this->getPaths() || null !== $this->getFile(false)));
	}

	public function toArray()
	{
		return array('class' => $this->getClass(), 'domain' => $this->getDomain(),
				'fid' => $this->getFid(), 'file' => $this->getFile(false), 'key' => $this->getKey(),
				'paths' => $this->getPaths(), 'size' => $this->getSize()
		);
	}

	public function fromArray(array $attributes)
	{
		foreach ($attributes as $key => $value) {
			switch ($key) {
				case 'class':
					$this->setClass($value);
					break;
				case 'domain':
					$this->setDomain($value);
					break;
				case 'fid':
					$this->setFid($value);
					break;
				case 'file':
					$this->setFile($value);
					break;
				case 'key':
					$this->setKey($value);
					break;
				case 'paths':
					$this->setPaths($value);
					break;
				case 'size':
					$this->setSize($value);
					break;
			}
		}
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function getClass($forceFetch = null)
	{
		if ((true === $forceFetch) || (null === $forceFetch && null === $this->_class)) {
			if (!$this->getMapper() instanceof MogileFS_File_Mapper) {
				require_once 'MogileFS/Exception.php';
				throw new MogileFS_Exception(__METHOD__ . ' No mapper set',
						MogileFS_Exception::MISSING_MAPPER);
			}
			$this->getMapper()
					->findInfo($this);
		}
		return $this->_class;
	}

	/**
	 *
	 * @param string $class
	 * @return MogileFS_File Provides fluent interface
	 */
	public function setClass($class)
	{
		$this->_class = $class;
		return $this;
	}

	public function setDomain($domain)
	{
		$this->_domain = $domain;
		return $this;
	}

	public function getDomain($forceFetch = null)
	{
		if ((true === $forceFetch) || (null === $forceFetch && null === $this->_domain)) {
			if (!$this->getMapper() instanceof MogileFS_File_Mapper) {
				require_once 'MogileFS/Exception.php';
				throw new MogileFS_Exception(__METHOD__ . ' No mapper set',
						MogileFS_Exception::MISSING_MAPPER);
			}
			$this->getMapper()
					->findInfo($this);
		}
		return $this->_domain;
	}

	public function setFid($fid)
	{
		$this->_fid = $fid;
		return $this;
	}

	public function getFid()
	{
		return $this->_fid;
	}

	/**
	 * 
	 * Set local file in model
	 * NOTE: Invalidates fid
	 * @param string $file
	 * @return MogileFS_File Provides fluent interface
	 */
	public function setFile($file)
	{
		if (!file_exists($file)) {
			require_once 'MogileFS/Exception.php';
			throw new MogileFS_Exception(__METHOD__ . ' File does not exist: ' . $file,
					MogileFS_Exception::INVALID_ARGUMENT);
		}

		$this->_file = $file;
		return $this;
	}

	/**
	 * 
	 * Get local copy of file stored in MogileFS
	 * Default is to lazy load (download) file on demand
	 * @param boolean $fetch
	 */
	public function getFile($fetch = null)
	{
		if ((true === $fetch) || (null === $fetch && !file_exists($this->_file))) {
			if (!$this->getMapper() instanceof MogileFS_File_Mapper) {
				require_once 'MogileFS/Exception.php';
				throw new MogileFS_Exception(__METHOD__ . ' No mapper set',
						MogileFS_Exception::MISSING_MAPPER);
			}
			$this->getMapper()
					->fetchFile($this);
		}
		return $this->_file;
	}

	/**
	 * 
	 * @return string
	 */
	public function getKey()
	{
		return $this->_key;
	}

	/**
	 * 
	 * @param string $key
	 * @return MogileFS_File Provides fluent interface
	 */
	public function setKey($key)
	{
		$this->_key = $key;
		return $this;
	}

	/**
	 * 
	 * @return array
	 */
	public function getPaths()
	{
		return $this->_paths;
	}

	/**
	 * 
	 * @param array $paths
	 * @return MogileFS_File Provides fluent interface
	 */
	public function setPaths(array $paths)
	{
		$this->_paths = $paths;
		return $this;
	}

	public function setSize($size)
	{
		$this->_size = $size;
		return $this;
	}

	public function getSize($forceFetch = null)
	{
		if ((true === $forceFetch) || (null === $forceFetch && null === $this->_size)) {
			if (!$this->getMapper() instanceof MogileFS_File_Mapper) {
				require_once 'MogileFS/Exception.php';
				throw new MogileFS_Exception(__METHOD__ . ' No mapper set',
						MogileFS_Exception::MISSING_MAPPER);
			}
			$this->getMapper()
					->findInfo($this);
		}
		return $this->_size;
	}

	public function setMapper(MogileFS_File_Mapper $mapper)
	{
		$this->_mapper = $mapper;
		return $this;
	}

	/**
	 * 
	 * @return MogileFS_File_Mapper|null
	 */
	public function getMapper()
	{
		return $this->_mapper;
	}
}

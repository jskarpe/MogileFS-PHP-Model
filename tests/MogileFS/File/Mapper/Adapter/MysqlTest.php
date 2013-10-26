<?php
/**
 * 
 * Test case for mysql read only adapter
 * @author Jon Skarpeteig <jon.skarpeteig@gmail.com>
 * @package MogileFS
 * @group MogileFS
 * @group Adapter
 * @group Tracker
 * @group Functional
 */
class MysqlAdapterTest extends PHPUnit_Framework_TestCase
{
	protected $_configFile;
	protected $_tracker;

	public function setUp()
	{
		$this->_configFile = realpath(dirname(__FILE__) . '/../../../config.php');
		$config = include $this->_configFile;
		$this->_mysqlAdapter = new MogileFS_File_Mapper_Adapter_Mysql($config['mysql']);
		$this->_trackerAdapter = new MogileFS_File_Mapper_Adapter_Tracker($config['tracker']);
	}

	public function testInstance()
	{
		$this->assertInstanceOf('MogileFS_File_Mapper_Adapter_Abstract', new MogileFS_File_Mapper_Adapter_Mysql());
	}

	public function testSettersAndGetters()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Mysql();
		$this->assertInstanceOf('MogileFS_File_Mapper_Adapter_Mysql', $adapter->setHostsUp(array(1, 2, 3, 4)));
		$this->assertEquals(array(1, 2, 3, 4), $adapter->getHostsUp());

	}

	public function testFetchAllPaths()
	{
		// Setup
		$key1 = 'testFile1';
		$file1 = $this->_trackerAdapter->saveFile($key1, $this->_configFile);
		$key2 = 'testFile2';
		$file2 = $this->_trackerAdapter->saveFile($key2, $this->_configFile);

		// Test
		$files = $this->_mysqlAdapter->fetchAllPaths(array($key1, $key2));
		
		// Tear down
		$this->_trackerAdapter->delete($key1);
		$this->_trackerAdapter->delete($key2);

		$this->assertArrayHasKey($key1, $files);
		$this->assertArrayHasKey($key2, $files);
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testInvalidMysqlOptionsValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Mysql(array('domain' => 'toast'));
		try {
			$adapter->getMysql();
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testInvalidMysqlOptions2Validation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Mysql(array('domain' => 'toast', 'pdo_options' => 'host:lala'));
		try {
			$adapter->getMysql();
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testInvalidMysqlOptions3Validation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Mysql(
				array('domain' => 'toast', 'pdo_options' => 'host:lala', 'username' => 'mogile'));
		try {
			$adapter->getMysql();
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testSaveFileValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Mysql();
		try {
			$adapter->saveFile(null, '');
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testFindFileValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Mysql();
		try {
			$adapter->findPaths('adsf');
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testFindInfoValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Mysql();
		try {
			$adapter->findInfo('adsf');
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testListKeysValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Mysql();
		try {
			$adapter->listKeys('adsf', 'asdf2');
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testRenameFileValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Mysql();
		try {
			$adapter->rename('adsf', 'asdf2');
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testDeleteFileValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Mysql();
		try {
			$adapter->delete('adsf');
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testFetchAllPathsFileValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Mysql();
		try {
			$adapter->fetchAllPaths(array('arsf'));
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}
}

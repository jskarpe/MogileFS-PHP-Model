<?php
/**
 * 
 * Test case for tracker (native) adapter
 * @author Jon Skarpeteig <jon.skarpeteig@gmail.com>
 * @package MogileFS
 * @group MogileFS
 * @group Adapter
 * @group Tracker
 * @group Functional
 */
class TrackerTest extends PHPUnit_Framework_TestCase
{
	protected $_configFile;
	protected $_tracker;

	public function setUp()
	{
		$this->_configFile = realpath(dirname(__FILE__) . '/../../../config.php');
		$config = include $this->_configFile;
		$this->_tracker = new MogileFS_File_Mapper_Adapter_Tracker($config['tracker']);
	}

	public function testSaveAndDelete()
	{
		$key = 'testFile';
		$result = $this->_tracker->saveFile($key, $this->_configFile, 'dev');
		$this->assertArrayHasKey('paths', $result);
		$this->assertArrayHasKey('fid', $result);
		$this->assertArrayHasKey('key', $result);
		$this->assertArrayHasKey('class', $result);
		$this->assertArrayHasKey('domain', $result);
		$this->_tracker->delete($key);
		$this->assertNull($this->_tracker->findPaths($key));
	}

	public function testFindInfo()
	{
		$key = 'testFile';
		$this->_tracker->saveFile($key, $this->_configFile);
		$info = $this->_tracker->findInfo($key);
		$this->assertArrayHasKey('fid', $info);
		$this->assertArrayHasKey('class', $info);
		$this->assertArrayHasKey('size', $info);
		$this->assertEquals('default', $info['class']);
		$this->assertEquals(filesize($this->_configFile), $info['size']);
		$this->_tracker->delete($key);

		$this->assertNull($this->_tracker->findInfo($key));
	}

	public function testRename()
	{
		$key = 'testFile';
		$key2 = 'testFile2';
		$this->_tracker->saveFile($key, $this->_configFile);
		try {
			$this->_tracker->rename($key, $key2);
		} catch (MogileFS_Exception $e) {
			// Clean up test data on failiure
			$this->_tracker->delete($key);
			throw $e;
		}

		$info = $this->_tracker->findInfo($key2);
		$this->_tracker->delete($key2);

		$this->assertNull($this->_tracker->findPaths($key));
		$this->assertEquals(filesize($this->_configFile), $info['size']);
	}

	public function testFetchAllPaths()
	{
		$key = 'testFile';
		$this->_tracker->saveFile($key, $this->_configFile);
		$key2 = 'testFile2';
		$this->_tracker->saveFile($key2, $this->_configFile);

		$pathsArray = $this->_tracker->fetchAllPaths(array($key, $key2));
		$this->_tracker->delete($key);
		$this->_tracker->delete($key2);

		$this->assertArrayHasKey($key, $pathsArray);
		$this->assertArrayHasKey($key2, $pathsArray);
		$this->assertArrayHasKey('path1', $pathsArray[$key]);
		$this->assertArrayHasKey('path1', $pathsArray[$key2]);
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testFindPathsValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Tracker();
		try {
			$adapter->findPaths(new Exception()); // Not valid value
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
	public function testFetchAllPathsValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Tracker();
		try {
			$adapter->fetchAllPaths(array(new Exception(''))); // Not valid value
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
		$adapter = new MogileFS_File_Mapper_Adapter_Tracker();
		try {
			$adapter->findInfo(new Exception('')); // Not valid value
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
	public function testDeleteValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Tracker();
		try {
			$adapter->delete(null); // Not valid value
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
	public function testRenameValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Tracker();
		try {
			$adapter->rename(null, 'asdf'); // Not valid value
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
	public function testRename2Validation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Tracker();
		try {
			$adapter->rename('asdf', null); // Not valid value
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
		$adapter = new MogileFS_File_Mapper_Adapter_Tracker();
		try {
			$adapter->saveFile(null, ''); // Not valid value
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
	public function testSaveFile2Validation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Tracker();
		try {
			$adapter->saveFile('key', '/tmp/me_N0_exist'); // Not valid value
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}
}

<?php
/**
 *
 * Test MogileFS_File_Mapper_Adapter_Test functions
 * @author Jon Skarpeteig <jon.skarpeteig@gmail.com>
 * @package MogileFS
 * @group MogileFS
 */
class TestAdapterTest extends PHPUnit_Framework_TestCase
{

	protected $_tempFiles = array();

	public function __destruct()
	{
		foreach ($this->_tempFiles as $file) {
			@unlink($file);
		}
	}

	public function testSettersAndGetters()
	{
		$testAdapter = new MogileFS_File_Mapper_Adapter_Test(array('domain' => 'toast'));

		$key = 'MyTestKey';

		$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid();
		file_put_contents($file, 'test data');
		$testFile = new MogileFS_File(array('key' => $key, 'file' => $file));

		$result = $testAdapter->saveFile($key, $testFile->getFile());
		unlink($file);

		$this->assertArrayHasKey('fid', $result);
		$this->assertArrayHasKey('paths', $result);
		$this->assertArrayHasKey('key', $result);
		$this->assertArrayHasKey('class', $result);
		$this->assertArrayHasKey('domain', $result);

		$this->assertNull($testAdapter->rename($key, $key . '2'));
		$this->assertNull($testAdapter->delete($key));
	}

	/**
	 * Argument validation test
	 * Expecting MogileFS_Exception with 1XX code
	 */
	public function testSaveFileValidation()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Test();
		try {
			$adapter->saveFile(null, ''); // Not valid value
		} catch (MogileFS_Exception $exc) {
			$this->assertLessThan(200, $exc->getCode(), 'Got unexpected exception code');
			$this->assertGreaterThanOrEqual(100, $exc->getCode(), 'Got unexpected exception code');
			return;
		}
		$this->fail('Did not get MogileFS_Exception exception');
	}

	public function testListKeys()
	{
		$adapter = new MogileFS_File_Mapper_Adapter_Test(array('domain' => 'toast'));

		$keys = array('MyTestKey2', 'MyTestKey1', 'MyTestKey3', 'N2', 'N1');
		foreach ($keys as $key) {
			$file = $this->_createTempFile();
			$adapter->saveFile($key, $file);
		}

		$this->assertEquals(array('MyTestKey1', 'MyTestKey2', 'MyTestKey3', 'N1', 'N2'), $adapter->listKeys());
		$this->assertEquals(array('MyTestKey1', 'MyTestKey2', 'MyTestKey3'), $adapter->listKeys('My'));
		$this->assertEquals(array('N1', 'N2'), $adapter->listKeys(null, 'MyTestKey3'));
		$this->assertEquals(array('N2'), $adapter->listKeys('N', 'N1'));
		$this->assertEquals(array('MyTestKey1', 'MyTestKey2'), $adapter->listKeys(null, null, 2));
		$this->assertEquals(array('MyTestKey2', 'MyTestKey3', 'N1'), $adapter->listKeys(null, 'MyTestKey1', 3));
		$this->assertEquals(array('MyTestKey2'), $adapter->listKeys('My', 'MyTestKey1', 1));
		$this->assertEquals(array(), $adapter->listKeys('NotExistingKeyPrefix'));
	}

	protected function _createTempFile()
	{
		$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid();
		file_put_contents($file, 'test data - ' . rand(0, 1000000));
		$this->_tempFiles[] = $file;
		return $file;
	}
}

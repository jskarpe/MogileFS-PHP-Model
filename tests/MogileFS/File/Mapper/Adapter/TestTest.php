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
}

<?php
/**
 * 
 * Extended Exception to include error codes
 * @author Jon Skarpeteig <jon.skarpeteig@gmail.com>
 * @package MogileFS
 *
 */
class MogileFS_Exception extends Exception
{
	const EMPTY_ARGUMENT = 100;
	const INVALID_ARGUMENT = 101;
	const MISSING_OPTION = 103;
	const MISSING_MAPPER = 104;
	
	const MISSING_CLIENT = 150;
	const INVALID_CONFIGURATION = 151;
	const INVALID_OPTION = 173;
	const UNSUPPORTED_METHOD = 152;
	const CONNECT_FAILED = 153;
	const KEY_NOT_FOUND = 154;
	const WRITE_FAILED = 155;
	const READ_FAILED = 156;
	const FOPEN_FAILIURE = 157;
	const FILE_NOT_FOUND = 158;
	const TRACKER_ERROR = 159;
	const UNKNOWN_ERROR = 199;
}
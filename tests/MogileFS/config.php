<?php
$adapters = array(
		'tracker' => array('domain' => 'toast', 'tracker' => array('127.0.1.5:7001'), 'noverify' => false,
				'pathcount' => 999
		), 'memcached' => array('servers' => array(array('localhost', 11211, 1)), 'expiration' => 0),
		'mysql' => array('domain' => 'toast', 'pdo_options' => 'host=localhost;port=3306;dbname=mogilefs',
				'username' => 'mogile', 'password' => 'mogilepass'
		)
);

return $adapters;

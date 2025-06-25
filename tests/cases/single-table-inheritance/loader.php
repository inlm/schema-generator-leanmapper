<?php

declare(strict_types=1);

if (PHP_VERSION_ID < 70200) {
	require __DIR__ . '/php5/User.php';
	require __DIR__ . '/php5/UserIndividual.php';
	require __DIR__ . '/php5/UserCompany.php';
	require __DIR__ . '/php5/Mapper.php';
	return __DIR__ . '/php5';
}

require __DIR__ . '/latest/User.php';
require __DIR__ . '/latest/UserIndividual.php';
require __DIR__ . '/latest/UserCompany.php';
require __DIR__ . '/latest/Mapper.php';
return __DIR__ . '/latest';

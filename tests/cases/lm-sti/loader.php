<?php

declare(strict_types=1);

if (PHP_VERSION_ID < 70200) {
	require __DIR__ . '/php5/Mapper.php';
	return __DIR__ . '/php5';
}

require __DIR__ . '/latest/Mapper.php';
return __DIR__ . '/latest';

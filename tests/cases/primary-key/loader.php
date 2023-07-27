<?php

if (PHP_VERSION_ID < 70200) {
	require __DIR__ . '/php5/Mapper.php';
	require __DIR__ . '/php5/Book.php';
	require __DIR__ . '/php5/BookMeta.php';
	return __DIR__ . '/php5';
}

require __DIR__ . '/latest/Mapper.php';
require __DIR__ . '/latest/Book.php';
require __DIR__ . '/latest/BookMeta.php';
return __DIR__ . '/latest';

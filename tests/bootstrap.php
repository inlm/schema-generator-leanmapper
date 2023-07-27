<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Test/DummyAdapter.php';
require __DIR__ . '/Test/Schema.php';

Tester\Environment::setup();


/**
 * @return void
 */
function test(callable $cb)
{
	$cb();
}


/**
 * @return string
 */
function prepareTempDir()
{
	$dir = __DIR__ . '/tmp/' . getmypid();
	Tester\Helpers::purge($dir);
	return $dir;
}

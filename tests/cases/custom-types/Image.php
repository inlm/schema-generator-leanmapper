<?php

	declare(strict_types=1);

	namespace Test\LeanMapperExtractor\CustomTypes;


	class Image
	{
		public function __construct(
			public string $path,
		)
		{
		}
	}

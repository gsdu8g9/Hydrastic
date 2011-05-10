<?php

namespace Hydra\Service;

use Symfony\Component\Yaml\Parser;
use Pimple;

class Yaml extends Pimple
{

	public function __construct($c) {

		$this['parser'] = $this->share(function () use ($c) {
			return new Parser();
		});

	}

}



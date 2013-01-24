<?php

namespace li3_waffle\tests\cases\extensions;

use li3_waffle\extensions\FeatureManager;
use lithium\action\Request;
use lithium\core\Libraries;

class FeatureManagerTest extends \lithium\test\Unit {

	public function testEnabled() {
		Libraries::cache(false);
		Libraries::add('li3_waffle', array(
			'paths' => '{:library}\tests\mocks\features\{:name}Feature',
			'path' => Libraries::get('li3_waffle', 'path'),
		));
		FeatureManager::init(new Request);

		$this->assertIdentical(true, FeatureManager::enabled('Basic'));
	}
	
}

?>
<?php

namespace li3_features\extensions\helper;

use li3_features\extensions\Features;

/**
 * # Feature Helper
 * The advert helper is a small helper to return boolean if feature is enabled
 * 
 * ## Usage
 * 
 * __Lithium PHP helper usage__
 * ~~~ php
 * <?php $this->feature->enabled('cool_feature'); ?>
 * ~~~
 * 
 */
class Feature extends \lithium\template\Helper {

	public function enabled($name){
		return Features::enabled($name);
	}

}

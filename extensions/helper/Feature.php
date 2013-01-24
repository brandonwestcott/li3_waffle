<?php

namespace li3_waffle\extensions\helper;

use li3_waffle\extensions\FeatureManager;

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

	/**
	 * Determines if the given feature is enabled.
	 *
	 * @param  string $name Name of the feature
	 * @return bool
	 */
	public function enabled($name){
		return FeatureManager::enabled($name);
	}

}

?>
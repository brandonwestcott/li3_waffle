<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_waffle\extensions\data\entity;

use li3_waffle\extensions\FeatureManager;

class Document extends \lithium\data\entity\Document {

	public function __call($method, $params) {
		if ($model = $this->_model) {
			$replaced = FeatureManager::replaceMethod($model.'::'.$method);
			if(!empty($replaced) && $replaced = explode('::', $replaced)){
				if(isset($replaced[1])){
					$method = $replaced[1];
				}
				// if we are going to a different model (probably a bad idea), invoke it directly
				if($replaced[0] != $model && method_exists($replaced[0], '_object')){
					array_unshift($params, $this);
					$class = $replaced[0]::invokeMethod('_object');
					return call_user_func_array(array(&$class, $method), $params);
				}
			}
		}
		return parent::__call($method, $params);
	}

}

?>
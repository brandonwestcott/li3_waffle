<?php

namespace li3_features\extensions;

use lithium\core\Libraries;

class Features extends \lithium\core\StaticObject {

	protected static $_features = array();

	public static function init($request = null){
		$defaults = array(
			'paths' => '{:library}\config\features\{:name}Feature',
			'viewFiltering' => true,
		);

		$config = Libraries::get('li3_features') + $defaults;

		Libraries::paths(array(
			'features' => (array) $config['paths']
		));

		$features = Libraries::locate('features');

		foreach($features as $feature){
			$featureObject = Libraries::instance('features', $feature, compact('request'));
			self::$_features[$featureObject->name()] = $featureObject;
		}

		if($config['viewFiltering'] == true){
			self::filterViews();			
		}
	}

	/**
	 * Check to see if a feature is enabled or get all enabled features
	 * 
	 * @param string $name - optional name of feature to check if active
	 * @return mixed - return array of enabled features, or return bool if named feature is enabled
	 */
	public static function enabled($name = null){
		if(!empty($name)){
			if(isset(self::$_features[$name]) && self::$_features[$name]->enabled()){
				return true;
			}
		} else {
			$enabled = array();
			foreach(self::$_features as $name => $feature){
				if($feature->enabled()){
					$enabled[$name] = $feature;				
				}
			}
			return $enabled;
		}
		return false;
	}


	/**
	 * Function to grab all viewFilters from each feature and apply filter to Renderer adapater to replace views based on feature
	 * 
	 * @param string $name - optional name of feature to check if active
	 * @return mixed - return array of enabled features, or return bool if named feature is enabled
	 */
	protected static function filterViews(){
		$params['features'] = self::$_features;
		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$filterViews = array();
			foreach($params['features'] as $feature){
				if($feature->enabled()){
					$filters = $feature->viewFilters();
					if(!empty($filters)){
						$filterViews += $filters;
					}
				}
			}
			if(!empty($filterViews)){
				Libraries::applyFilter('instance', function($self, $params, $chain) use ($filterViews) {
					if($params['name'] == '\lithium\template\View'){
						$view = $chain->next($self, $params, $chain);
						$view->applyFilter('_step', function($self, $params, $chain) use ($filterViews) {
							if(!(isset($params['params']['controller']) && $params['params']['controller'] == '_errors')){
								foreach($filterViews as $filterSet){
									if(count($filterSet) == 2 && is_array($filterSet[0]) && is_array($filterSet[1])){
										if(array_intersect_key($params['params'], $filterSet[0]) == $filterSet[0]){
											$params['params'] = $filterSet[1] + $params['params'];
										}									
									}
								}
							}
							return $chain->next($self, $params, $chain);
						});
						return $view;
					}
					return $chain->next($self, $params, $chain);
				});
			}
		});
	}


}
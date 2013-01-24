<?php

namespace li3_waffle\extensions;

use lithium\core\Libraries;

class FeatureManager extends \lithium\core\StaticObject {

	protected static $_features = array();

	protected static $_config = array(
		'paths' => '{:library}\config\features\{:name}Feature',
		'viewFiltering' => true,
		'modelFiltering' => true,
		'helperFiltering' => true,
	);

	protected static $_classes = array(
		'Document' => 'li3_waffle\extensions\data\entity\Document',
		'Record'   => 'li3_waffle\extensions\data\entity\Record',
	);

	protected static $_replacedModels = array();

	public static function init($request = null){
		self::$_config = Libraries::get('li3_waffle') + self::$_config;

		if(isset(self::$_config['classes'])){
			self::$_classes = self::$_config['classes'];
			unset(self::$_config['classes']);
		}

		Libraries::paths(array(
			'features' => (array) self::$_config['paths']
		));

		self::_attachFeatures();
	}

	/**
	 * Called during init() - Seperated out of init for flexible overwritting
	 */
	protected static function _attachFeatures(){
		$features = Libraries::locate('features');

		foreach($features as $feature){
			$featureObject = Libraries::instance('features', $feature, compact('request'));
			self::$_features[$featureObject->name()] = $featureObject;
		}

		if(self::$_config['viewFiltering'] == true){
			self::_filterViews();			
		}

		if(self::$_config['helperFiltering'] == true){
			self::_filterHelpers();			
		}

		if(self::$_config['modelFiltering'] == true){
			self::_attachFilteredModels();			
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
	 * Check to see if a model replacement exists for a given model or model::method
	 * 
	 * @param string $source - Model::method syntax for checking/storing filtered methods
	 * @param string $target - Model::method syntax for storage of target method
	 * @return return string - $target Model::method or simply Model
	 */
	public static function replaceModel($source = null, $target = null){
		if(!empty($target)){
			self::$_replacedModels[$source] = $target;
		}
		if(isset(self::$_replacedModels[$source])){
			return self::$_replacedModels[$source];
		} else {
			list($class) = explode('::', $source);
			if(isset(self::$_replacedModels[$class])){
				return self::$_replacedModels[$class];		
			}
		}
		return null;
	}

	/**
	 * Function to grab all modelFilters from each feature and apply filter to entity
	 *
	 * @see li3_waffle\extensions\data\entity\Document::__call()
	 * @see li3_waffle\extensions\data\entity\Record::__call()
	 * @see lithium\core\Libraries::instance()
	 */
	protected static function _attachFilteredModels(){
		$params['classes'] = self::$_classes;
		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$features = $self::enabled();
			$count = 0;
			foreach($features as $feature){
				$filters = $feature->modelFilters();
				if(!empty($filters)){
					$count++;
					foreach($filters as $source => $target){
						$self::replaceModel($source, $target);
					}
				}
			}
			if($count > 0){
				$_classes = $params['classes'];
				Libraries::applyFilter('instance', function($self, $params, $chain) use ($_classes) {
					if(preg_match('/Document$/', $params['name'])){
						$params['name'] = $_classes['Document'];
					}
					if(preg_match('/Record$/', $params['name'])){
						$params['name'] = $_classes['Record'];
					}
					return $chain->next($self, $params, $chain);
				});
			}
		});
	}


	/**
	 * Function to grab all helperFilters from each feature 
	 * and apply filter Libraries::locate for substition
	 *
	 * @see lithium\template\view\Renderer::helper()
	 * @see lithium\core\Libraries::instance()
	 * @see lithium\core\Libraries::locate()
	 */
	protected static function _filterHelpers(){
		return static::_filter(__FUNCTION__, array(), function($self, $params) {
			$filterHelpers = array();
			$features = $self::enabled();
			foreach($features as $feature){
				$filters = $feature->helperFilters();
				if(!empty($filters)){
					$filterHelpers += $filters;
				}
			}
			if(!empty($filterHelpers)){
				Libraries::applyFilter('instance', function($self, $params, $chain) use ($filterHelpers) {
					if(isset($params['type']) && $params['type'] == 'helper' && isset($params['name'])){
						if($path = Libraries::locate($params['type'], $params['name'])){
							if(isset($filterHelpers[$path])){
								$path = $filterHelpers[$path];
							}
							$params['name'] = $path;
						}
					}
					return $chain->next($self, $params, $chain);
				});
			}
		});
	}


	/**
	 * Function to grab all viewFilters from each feature and apply filter to Renderer adapater to replace views based on feature
	 *
	 * @see lithium\core\Libraries::instance()
	 * @see lithium\template\View::_step()
	 */
	protected static function _filterViews(){
		return static::_filter(__FUNCTION__, array(), function($self, $params) {
			$filterViews = array();
			$features = $self::enabled();
			foreach($features as $feature){
				$filters = $feature->viewFilters();
				if(!empty($filters)){
					$filterViews += $filters;
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
<?php

namespace li3_waffle\config;

use lithium\core\Libraries;
use lithium\template\View;
use lithium\net\http\Media;

class Feature extends \lithium\core\Object {

	protected $_autoConfig = array('request');

	protected $_request = null;

	protected $_options = array(
		'enabled' => false
	);

	protected $_name = null;

	public function __construct($config = array()){
		parent::__construct($config);
		if(empty($this->_name)){
			$class = explode('\\', get_class($this));
			$name = array_pop($class);
			$this->_name = preg_replace('/(.*)Feature/', '$1', $name);
		}
	}

	/**
	 * Return name of feature
	 * 
	 * @return string - name of feature from _name
	 */
	public function name(){
		return $this->_name;
	}

	/**
	 * Check to see if a feature is enabled
	 * 
	 * @return boolean - true of false if feature is enabled
	 */
	public function enabled(){
		return $this->_options['enabled'];
	}

	/**
	* Manages the filtering of the Views based on multidimensional array
	* This should match the params['params'] in lithium\template\View::_step()
	* First array of a given is the matching criteria
	* Second portion of array is the replacement criteria
	*
	* For example, the following would replace blog/show.html.php with blog/show_feature1.html.php
	* {{{
	* return array(
	*	array(
	*		array(
	*			'type' => 'html',			
	*			'controller' => 'blog',
	*			'template' => 'show',	
	*		),
	*		array(
	*			'template' => 'show_feature1'
	*		),
	*	),
	*);
	* }}}
	*
	* For help in formatting _swapView method exists 
	*
	* @see FeatureManager::_filterViews();	
	*/
	public function viewFilters(){
		return array();
	}

	/**
	* Manages the filtering of the methods on Models or full Models
	* Should return an array of full namespaced source to target methods
	*
	* For example, the following would replace 
	* blog->title() to blog->titleFeature1()
	* and blog->name to blog->nameFeature1
	* {{{
	* return array(
	* 	'app\models\Blog::title' => 'app\models\Blog::titleFeature1',
	* 	'app\models\Blog::name' => 'app\models\Blog::nameFeature2',
	* );
	* }}}
	*
	* If you want to be really naughty, you could swap an entire model
	* Note, that this wouldn't return the new namespaced model for ->model()
	* but thats exactly what we want - treat our new model like an old model
	* 
	* For example, lets proxy all of Blog to BlogFeature model
	* {{{
	* return array(
	* 	'app\models\Blog' => 'app\models\BlogFeature',
	* );
	* }}}
	*
	* @see FeatureManager::_attachFilteredModels();
	* @see FeatureManager::replaceModel();
	*/
	public function modelFilters(){
		return array();
	}

	/**
	* Manages the filtering/replacement of the Helpers
	* Should return an array of full namespaced source to target model
	* 
	* For example, lets proxy replace Lists helper with ListsFeature
	* {{{
	* return array(
	* 	'app\extensions\helper\Lists' => 'app\extensions\helper\ListsFeature',
	* );
	* }}}
	*
	* @see FeatureManager::_attachFilteredHelpers();
	*/
	public function helperFilters(){
		return array();
	}	

	/**
	 * Takes array of key => array(from => to) and creates the array needed for viewFilters
	 * 
	 * @return array - should return a multidimensional array of original view params to new view params
	 */
	public function _swapView($array = array()){
		$return = array();
		foreach($array as $k => $v){
			if(is_array($v)){
				$return[0][$k] = key($v);
				$return[1][$k] = current($v);
			} else {
				$return[0][$k] = $v;
			}
		}
		return $return;
	}


}
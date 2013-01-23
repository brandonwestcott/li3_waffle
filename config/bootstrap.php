<?php
use lithium\action\Dispatcher;
use lithium\core\Libraries;

$config = Libraries::get('li3_features') + array('class' => 'li3_features\extensions\Features');

Dispatcher::applyFilter('run', function($self, $params, $chain) use ($config) {
	$config['class']::init($params['request']);
	return $chain->next($self, $params, $chain);
});

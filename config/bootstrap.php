<?php
use lithium\action\Dispatcher;
use lithium\core\Libraries;

$config = Libraries::get('li3_waffle');

if(isset($config['classes']) && isset($config['classes']['Manager'])){
	$manager = $config['classes']['Manager'];
} else {
	$manager = 'li3_waffle\extensions\FeatureManager';
}

Dispatcher::applyFilter('run', function($self, $params, $chain) use ($manager) {
	$manager::init($params['request']);
	return $chain->next($self, $params, $chain);
});

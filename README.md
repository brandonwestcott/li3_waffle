# li3_waffle: Unobtrusive #LI3 Lithium PHP Feature Flags

## Introduction
Verb 1. waffle - pause or hold back in uncertainty or unwillingness; "Authorities hesitate to quote exact figures"

Maybe you have an app that lives in an environment in which people can't make up their minds. This plugin lets you define feature flags for your Lithium PHP app in an unobtrusive, or obtrusive way, depending on your flavor development.

## Installation
To install and activate the plugin:

1: Download or clone the plugin into your `libraries` directory.

~~~ bash
cd app/libraries
git clone git://github.com/brandonwestcott/li3_waffle.git
~~~

2: Enable the plugin in your libraries (`app/config/bootstrap/libraries.php`).

~~~ php
<?php
/**
 * Add the plugin - we'll talk about options in a bit
 */
Libraries::add('li3_waffle');
?>
~~~

3: Create a feature in app/config/features/ - LI3 command coming soon :) - Best practice says give it a name explicitly

app/config/features/AwesomeFeature.php
~~~ php
<?php
namespace app\config\features;

class AwesomeFeature extends \li3_waffle\config\Feature {

	protected $_name = 'awesome';

}
?>
~~~

## Basic Usage
If you love big if/else block and lots of obtrusive checking (beyond the sarcasm, there is a time and place for this or it wouldn't exists in this plugin), you can do the following.

### Non-Views
~~~ php
<?php
use li3_waffle\extensions\FeatureManager

if(FeatureManager::enabled('awesome')){
	$flag = 'awesome';
} else {
	$flag = 'not awesome';
}
?>
~~~

### Views
~~~ php
<?php if($this->feature->enabled('awesome')){ ?>
	We are awesome
<?php } ?>
~~~

But most of the time, who wants all of these if/else blocks, thankfully lithium to the rescue.

## Awesome Usage
Given that #li3 provides us with some awesome filtering and meta programming capabilities, we can overwrite the views, models (& very soon helpers) unobtrusively.

For an overview of methods you should use, see `li3_waffle\config\Feature` class.

### Model filtering:
Simply define `modelFilters()` in your feature and return a multi-dimensional array where the `key` is the source model::method and the `value` is the target model::method.

~~~ php
<?php
namespace app\config\features;

class AwesomeFeature extends li3_waffle\config\Feature {

	protected $_name = 'awesome';

	public function modelFilters(){
		return array(
			'app\models\Blogs::title' => 'app\models\Blogs::titleAwesome'
			'app\models\Blogs::name' => 'app\models\Blogs::nameAwesome'
		);
	}

}
?>
~~~

And given you have a Blog model like such
~~~ php
<?php
namespace app\models;

class Blogs extends AppModel {

	public function title($entity){
		return $entity->title;
	}

	public function titleAwesome($entity){
		return 'Awesome '.$entity->title;
	}

	public function name($entity){
		return $entity->name;
	}

	public function nameAwesome($entity){
		return 'Authors';
	}

}
?>
~~~

Now, given that you have an instance of blog in your view, nothing changes! Thats right, nothing.
~~~ php
<h2><?=$blog->title()?></h2>
<h3><?=$blog->name()?></h3>
~~~

Wait what?, yep that’s right, #li3 meta programming forces the `name()` method to use the `nameAwesome()` method instead. Your output would look similar to:
~~~ html
<h2>Awesome Some Data Driven Title</h2>
<h3>Authors</h3>
~~~

This allows us to keep code clean without have to do if else statements. Sure, its not completely DRY, but when you are ready to make a feature live, you just change `nameAwesome()` to `name()` and `titleAwesome()` to `title()` and remove your feature.

If you want even more control over the model, you can replace entire models with this approach too. This still allows `Blogs::first()` to work, but proxies all methods to your feature model. Lets take a look.

~~~ php
<?php
namespace app\config\features;

class AwesomeFeature extends li3_waffle\config\Feature {

	protected $_name = 'awesome';

	public function modelFilters(){
		return array(
			'app\models\Blog' => 'app\models\BlogFeature'
		);
	}

}
?>
~~~

Then you create your `BlogsFeature` model, likely extending the Blog model (though it doesn't have to).
~~~ php
<?php
namespace app\models;

class BlogsFeature extends Blogs {

	public function __init(){
		parent::__init();
		// maybe a different connection?
		self::meta('connection', 'featured');

		// maybe a custom find beahvior here?
		self::applyFilter('find', function($self, $params, $chain) {
			$params['options']['conditions']['is_featured'] = true;
			return $chain->next($self, $params, $chain);
		});
	}

}
?>
~~~

The possibilities are really endless. What is great here, is that most of #li3 magic still points to your old model. Connections, meta, etc still get loaded in from the original class. Eg, when you call `$blog->model()` you get back `app/models/Blogs`. The only thing that gets hijacked is the method calls, allowing your app to function just as it already was.

Again though some code duplication may happen, this allows you to add this feature flag as unobtrusively as possible. Calls like `Blogs::first()` and `Blogs::count()` continue to work. Imagine if your feature required a different model and you had to wrap every `Blogs::find()` in a feature if block to load `BlogsFeature` instead. Ewwwwwwww....

### Helper filtering:
Similar to filtering models with `modelFilters()`, you can define `helperFilters()` in your feature and return a multi-dimensional array where the `key` is the fully namespaced source helper class and the `value` is the fully namespace target helper. Note: this is full class replacement here, not indivudal method replacement.

~~~ php
<?php
namespace app\config\features;

class AwesomeFeature extends li3_waffle\config\Feature {

	protected $_name = 'awesome';

	public function helperFilters(){
		return array(
			'app\extensions\helper\Lists' => 'app\extensions\helper\ListsFeature',
		);
	}

}
?>
~~~

As you might expect by now, anytime you call `$this->lists->` in your view, you will instead get passed to `ListsFeature`

Here is an example, starting with your original lists helper
~~~ php
<?php
namespace app\extensions\helper;

class Lists extends \lithium\template\Helper {

	public function five($entity){
		return implode(', ', range(1, 5));
	}

}
?>
~~~

Now we create our feature helper to overwrite functionality
~~~ php
<?php
namespace app\extensions\helper;

class ListsFeature extends Lists {

	public function five($entity){
		return 'I, II, III, IV, V';
	}

}
?>
~~~

Now, as you might expect, nothing in our view has to change!
~~~ php
<p><?=$this->lists->five()?></p>
~~~

Given this feature is enabled, the output would be
~~~ php
<p>I, II, III, IV, V</p>
~~~

Again, this allows you to add feature specific logic to your helpers without having to modify your views.

Note: I have the desire to implement helpers like models and allow method specific filtering. However, li3 meta magic does not extend into the helper implemention. It is possible to implement, but it requires a delegate class to be in front of all helpers. Though I've used this approach in some development only plugins, it seems a bit more obtrusive than desired. Feedback on this would be appreciated!

### View filtering:
Views are even more magical, for better or for worse.

In short, `_pathsManipuation()` prepends feature specific directories to lithiums path loading. Code definitely speaks louder than words here:
	 
First, return true in `viewFilters()`. This enables view filtering per feature
~~~ php
<?php

namespace app\config\features;

class AwesomeFeature extends li3_waffle\config\Feature {

	protected $_name = 'awesome';

	public function viewFilters(){
		return true;
	}
}
?>
~~~

Now, any `Media::type()` paths will get feature paths prepended for them. For a default li3 setup, this would create the following paths:

~~~ php
Array
(
    [template] => Array
        (
            [0] => {:library}/views/features/{:controller}/{:template}_awesome.{:type}.php
            [1] => {:library}/views/{:controller}/{:template}.{:type}.php
        )

    [layout] => Array
        (
            [0] => {:library}/views/features/{:controller}/{:layout}_awesome.{:type}.php
            [1] => {:library}/views/{:controller}/{:layout}.{:type}.php
        )

    [element] => Array
        (
            [0] => {:library}/views/features/elements/{:template}_awesome.{:type}.php
            [1] => {:library}/views/elements/{:template}.{:type}.php
        )

)
~~~

If you had another feature name `foo`, you would get the following:
~~~ php
Array
(
    [template] => Array
        (
            [0] => {:library}/views/features/{:controller}/{:template}_awesome.{:type}.php
            [1] => {:library}/views/features/{:controller}/{:template}_foo.{:type}.php
            [2] => {:library}/views/{:controller}/{:template}.{:type}.php
        )

    [layout] => Array
        (
            [0] => {:library}/views/features/{:controller}/{:layout}_awesome.{:type}.php
            [1] => {:library}/views/features/{:controller}/{:layout}_foo.{:type}.php
            [2] => {:library}/views/{:controller}/{:layout}.{:type}.php
        )

    [element] => Array
        (
            [0] => {:library}/views/features/elements/{:template}_awesome.{:type}.php
            [1] => {:library}/views/features/elements/{:template}_foo.{:type}.php
            [2] => {:library}/views/elements/{:template}.{:type}.php
        )

)
~~~

## Configuring
In short, if `$_config['enabled'] = true`, the feature is enabled. See comment 3 below of where I expect this to go.

If you have custom Document/Record you, or a plugin is using, you will need to specify your custom implementation to extend these Document/Record classes and then pass in your class to the options array below of `Libraries::add()`. If you have an issue where you need to extend more than one Document/Record, pass in your custom class and copy out the small amount of code from Document/Record (really not that much).

~~~ php
<?php
/**
 * Add the plugin w/ options
 */
Libraries::add('li3_waffle', array(
	'paths' => '{:library}\config\features\{:name}Feature', // paths to your feature
	'viewFiltering' => true, // toggle viewFiltering globally
	'modelFiltering' => true // toggle modelFiltering globally
	'helperFiltering' => true // toggle modelFiltering globally
	'classes' => array(
		'Document' => 'li3_waffle\extensions\data\entity\Document',
		'Record'   => 'li3_waffle\extensions\data\entity\Record',
		'Manager'  => 'li3_waffle\extensions\FeatureManager',
	)
));
?>
~~~

## Plans for the future
1. Fix the view definition, definitely not elegant as is.
2. Add more test coverage
3. Add robust config to `li3_waffle\config\Feature.php` class for understanding of query params, environments, hostnames, server ips, remote ips, time of day, percentage of population. Of course, as a developer you can overwrite anything in that class and customize it as much as you want. However, it would be nice to have an small dsl for the main things.
4. So eventually, I would like to create an `li3_waffle_admin` plugin for managing these features. E.g. your product team could login and enable these features based on the options for your specific needs. I attempted to set up the options in `Feature.php` class to allow these items to come from a DB, so we shall see.

## Contributing
Please fork the plugin and send in pull requests!!! If you find any bugs or need a feature that is not implemented, open a ticket.
# li3_waffle: Unobtrusive #LI3 Lithium PHP Feature Flags

## Introduction
Verb 1. waffle - pause or hold back in uncertainty or unwillingness; "Authorities hesitate to quote exact figures" 

Maybe you have an app that lives in an environment in which people can't make up their minds. This plugin lets you define feature flags for your Lithium PHP app in an unobtrusive, or obtrusive way, depending on your flavor development.  

## Installation
To install and activate the plugin:  

1: Download or clone the plugin into your `libraries` directory.  

	cd app/libraries
	git clone git://github.com/brandonwestcott/li3_waffle.git
	

2: Enable the plugin in your libraries (`app/config/bootstrap/libraries.php`).  

~~~ php
	/**
	 * Add the plugin - we'll talk about options in a bit
	 */ 
	Libraries::add('li3_waffle');
~~~

3: Create a feature in app/config/features/ - LI3 command coming soon :) - Best practice says give it a name explicitly  

app/config/features/AwesomeFeature.php
~~~ php
	namespace app\config\features;

	class AwesomeFeature extends \li3_waffle\config\Feature {

		protected $_name = 'awesome';

	}
~~~


## Basic Usage
If you love big if/else block and lots of obtrusive checking (beyond the sarcasm, there is a time and place for this or it wouldn't exists in this plugin), you can do the following:  

In any non view context you can
~~~ php
	use li3_waffle\extensions\FeatureManager

	if(FeatureManager::enabled('awesome')){
		$flag = 'awesome';
	} else {
		$flag = 'not awesome';
	}
~~~

FeatureManager::enabled simply returns a boolean if a feature is enabled (ya well get to that)  

In a view, you can use the provided helper to make your life easier
~~~ php
	<?php if($this->feature->enabled('awesome')){ ?>
		We are awesome
	<?php } ?>
~~~

But most of the time, who wants all of these if/else blocks, thankfully lithium to the rescue  

## Awesome Usage
Given that #LI3 provides us with some awesome filtering and meta programming capabilities, we can overwrite the views, models (& very soon helpers) unobtrusively

For an overview of methods you should use, see \li3_waffle\config\Feature class  

### Model method filtering:  
Simply define methodFilters() in your feature and return a multidimensional array where the key is the source method and the value is the target method  

app/config/features/AwesomeFeature.php
~~~ php
	namespace app\config\features;

	class AwesomeFeature extends li3_waffle\config\Feature {

		protected $_name = 'awesome';

		public function methodFilters(){
			return array(
				'app\models\Blogs::title' => 'app\models\Blogs::titleAwesome'
				'app\models\Blogs::name' => 'app\models\Blogs::nameAwesome'
			);
		}

	}
~~~

And given you have a Blog model like such
app/models/Blogs.php
~~~ php
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
~~~

Now, given that you have an instance of blog in your view, nothing changes! Thats right, nothing.
~~~ php
	<h2><?=$blog->title()?></h2>
	<h3><?=$blog->name()?></h3>	
~~~

Wait what?, yep that’s right, LI3 meta programming allows the name method to use the nameAwesome method instead. Your output would look like
~~~ html
	<h2>Awesome Some Data Driven Title</h2>
	<h3>Authors</h3>
~~~

This allows us to keep code clean without have to do if else statements. Sure, its not completely DRY, but when you are ready to make a feature live, you just change nameAwesome() to name() and titleAwesome() to title() and remove your feature.

If you want even more control over them model, you can replace entire models with this approach too. This still allows Blogs::first() to work, but proxies all methods to your feature model. Lets take a look

app/config/features/AwesomeFeature.php
~~~ php
	namespace app\config\features;

	class AwesomeFeature extends li3_waffle\config\Feature {

		protected $_name = 'awesome';

		public function methodFilters(){
			return array(
				'app\models\Blog' => 'app\models\BlogFeature'
			);
		}

	}
~~~

Then you create your BlogsFeature model, likely extending the Blog model (though it doesn't have to)
app/models/BlogsFeature.php
~~~ php
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
~~~

The possibilities are really endless. What is great here, is that most of li3 magic still points to your old model. Connections, meta, etc still get loaded in from the original class. Eg, when you call $blog->model() you get back app/models/Blogs. The only thing that gets hijacked is the method calls, allowing your app to function just as it already was.

Again though some code duplication may happen, this allows you to add this feature flag as unobtrusively as possible. Calls like Blogs::first() and Blogs::count() continue to work. Imagine if your feature required a different model and you had to wrap every Blogs::find in a feature if block to load BlogsFeature instead. Ewwwwwwww....

### View filtering:
So admittedly, this isn't as pretty as it could be yet. Surprisingly path matching with different view renderers was extremely hard. But for now, its livable.

Like methodFilters(), you define viewFilters() and return an array. However this array is a bit more robust. See lithium\template\View for params explanation

Here we want to replace views/blogs/show.html.php with views/blogs/show_awesome.html.php

app/config/features/AwesomeFeature.php
~~~ php
	namespace app\config\features;

	class AwesomeFeature extends li3_waffle\config\Feature {

		protected $_name = 'awesome';

		public function viewFilters(){
			return array(
				$this->_swapView(array(
					'type' => 'html',			
					'controller' => 'blogs',
					'template' => array('show' => 'show_awesome')
				)),
			);
		}
	}
~~~

Here all we have to do is pass an array to swap view, with params that match those that get passed to lithium\template\View. Here we are wanting to match type == html, controller == blogs, and template == show. If the view that is being rendered matches these parameters, it will instead overwrite template with show_awesome, our feature specific view. In short, if value is an array, the first value will be use for the match, the second will be used for replacement. We could even move to a different controller view folder 

app/config/features/AwesomeFeature.php
~~~ php
	namespace app\config\features;

	class AwesomeFeature extends li3_waffle\config\Feature {

		protected $_name = 'awesome';

		public function viewFilters(){
			return array(
				$this->_swapView(array(
					'type' => 'html',			
					'controller' => array('blogs' => 'posts'),
					'template' => 'show'
				)),
			);
		}
	}
~~~

Here we end up rewriting views/blogs/show.html.php to views/posts/show.html.php 

Obviously, copying entire views would not keep your code DRY, so this approach is mostly applicable for switching out elements and small chunks of a view. As with the models, this allows you Feature specific logic to live independent from your app. Once you are ready to move a feature into the core, you simply remove your current view file and rename your feature file to the original. Eg rm views/blogs/show.html.php && mv views/blogs/show_awesome.html.php views/blogs/show.html.php

Note: _swapView is simply a helper function to make the formatting of this nasty array a bit easier. See the docblock for li3_waffle\config\Feature::viewFitlers to understand why this was warranted.

The goal here is to get array('views/blogs/show.html.php' => 'views/posts/show.html.php') working. The initial attempts were futile, but I really hate the way you must define it now.


### Helper filtering:
Almost there, expecting this will be full helper replacement like the models above, not method specific replacement. The reason is that in order to filter all methods in a helper, you would have to create a HelperDelagate type class with all its stuff delegated to the real helper. 


## Configuring
In short, if $_config['enabled'] = true, the feature is enabled. See comment 3 below of where I expect this to go. 

If you have custom Document/Record you, or a plugin is using, you will need to specify your custom implementation to extend these Document/Record classes and then pass in your class to the options array below of Libraries::add(). If you have an issue where you need to extend more than one Document/Record, pass in your custom class and copy out the small amount of code from Document/Record (really not that much).

~~~ php
	/**
	 * Add the plugin w/ options
	 */ 
	Libraries::add('li3_waffle', array(
		'paths' => '{:library}\config\features\{:name}Feature', // paths to your feature
		'viewFiltering' => true, // toggle viewFiltering globally 
		'methodFiltering' => true // toggle methodFiltering globally
		'classes' => array(
			'Document' => 'li3_waffle\extensions\data\entity\Document',
			'Record'   => 'li3_waffle\extensions\data\entity\Record',
			'Manager'  => 'li3_waffle\extensions\FeatureManager', 
		)
	));
~~~

## Plans for the future
1. Add helper overwriting
2. Fix the view definition, definitely not elegant as is.
3. Add robust config to li3_waffle\config\Feature.php class for understanding of query params, environments, hostnames, server ips, remote ips, time of day, percentage of population. Of course, as a developer you can overwrite anything in that class and customize it as much as you want. However, it would be nice to have an small dsl for the main things.
4. So eventually, I would like to create an li3_waffle_admin plugin for managing these features. E.g. your product team could login and enable these features based on the options for your specific needs. I attempted to set up the options in Feature.php class to allow these items to come from a DB, so we shall see.

## Contributing
Please fork the plugin and send in pull requests!!! If you find any bugs or need a feature that is not implemented, open a ticket.

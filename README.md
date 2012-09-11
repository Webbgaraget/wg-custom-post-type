# WG Custom Post Type
_…or: How I Learned to Stop Worrying and Love the Custom Post Type_

`WG_Custom_Post_Type` is a library created to simplify common tasks when developing Custom Post Types (CPT:s) for WordPress.

Along with the basic functions for creating the CPT:s and adding taxonomies and meta boxes, you are
handed some convenience methods for playing with hygiene factors at the wp-admin side such as menu icons and help tabs.


1. Speed up your WordPress CPT development with a simplified interface
1. Say goodbye to all those action and filter callbacks that uglifies your code
1. Make the administration more user friendly with icons and help tabs
1. Enjoy a chained interface

### Keepin' it WP yo

While trying to make the interface as easy as possible for the developer, our aim is to still keep as close to the WordPress
way of doing things as possible. Why you ask? Simple: It's WordPress you walk, and WordPress you shall talk.

### Dependencies

* The library [wg-meta-box](http://github.com/webbgaraget/wg-meta-box "wg-meta-box @ Github") is used for adding meta boxes. `wg-meta-box`
is added as a submodule to this repository, see below on how to initialize it.

* The plugin [Multiple Post Thumbnails](http://wordpress.org/extend/plugins/multiple-post-thumbnails/) is used for adding multiple featured images. You
need to install and activate the plugin in order to use this functionality.


## Getting started

To get started using this library, go ahead and clone the repo into the folder of choice.

	git clone git@github.com:Webbgaraget/wg-custom-post-type

If you're interested in adding meta boxes for your CPT (you probably are), initialise the submodule with the following command (ran from the root of the newly cloned git repo)

	git submodule update --init

And you're golden!

## Example: Creating your first custom post type

This is a simple example on how to create a CPT for Events:

	// Labels
	$labels = array(
		'name'               => 'Events',
		'singular_name'      => 'Event',
		'all_items'          => 'All events',
		'add_new'            => 'Add new',
		'add_new_item'       => 'Add event',
		'edit_item'          => 'Edit event',
		'new_item'           => 'New event',
		'view_item'          => 'View event',
		'search_items'       => 'Search events',
		'not_found'          => 'No event found',
		'not_found_in_trash' => 'No event found in trash',
	);

	// Create the CPT
	$event = new WG_Custom_Post_Type( 'event', array(
	    'capability_type' => 'page',
		'labels'          => $labels,
		'hierarchial'     => true,
		'supports'        => array( 'title' ),
	) );
	
	// Create a meta box and set a placeholder text to the title field on the edit screen
	$event
		->set_title_placeholder( 'Enter event title here!' )
		->add_meta_box( 'event_info', 'Event information', array(
			'date' => array(
		        'type'  => 'date',
		        'label' => 'Date',
		    ),
		    'time' => array(
		        'type'  => 'text',
		        'label' => 'Time',
		    )
		) );
	
Note how the labels and options arrays are identical to the ones you usually pass to `register_post_type()`.

## Method documentation

	public function __construct( $post_type, $args = null, $label_check = 'require_labels' ) {}

Lorem

	public function add_taxonomy( $id, $args = array() ) {}

Ipsum

## Changelog

### 2012-09-12 v0.1 alpha release
* This document was created
* The library was modified to adhere more closely (but not fully) to WordPress Coding Standards

## License (MIT)

Copyright (c) 2012 Webbgaraget AB http://www.webbgaraget.se/

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the "Software"),
to deal in the Software without restriction, including without limitation the
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
sell copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
DEALINGS IN THE SOFTWARE.
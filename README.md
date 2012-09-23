# WG Custom Post Type
_…or: How I Learned to Stop Worrying and Love the Custom Post Type_

`WG_Custom_Post_Type` is a library created to simplify common tasks when developing Custom Post Types (CPT:s) for WordPress.

Along with the basic functions for creating the CPT:s and adding taxonomies and meta boxes, you are
handed some convenience methods for playing with hygiene factors at the wp-admin side such as menu icons and help tabs.


1. Speed up your WordPress CPT development with a simplified interface
1. Say goodbye to all those action and filter callbacks that uglifies your code
1. Make the administration more user friendly with icons and help tabs
1. Enjoy a chained interface

While trying to make the interface as easy as possible for the developer, our aim is to still keep as close to the WordPress
way of doing things as possible. Why you ask? Simple: It's WordPress you walk, and WordPress you shall talk.

### Dependencies

* The library [wg-meta-box](http://github.com/webbgaraget/wg-meta-box "wg-meta-box @ Github") is used for adding meta boxes. `wg-meta-box`
is added as a submodule to this repository, see below on how to initialize it.

* The plugin [Multiple Post Thumbnails](http://wordpress.org/extend/plugins/multiple-post-thumbnails/) is used for adding multiple featured images. You
need to install and activate the plugin in order to use this functionality.


## Getting started

To get started using this library, either clone it or download the zip and unpack it into the folder of choice.
A recommended place would be to put the library in a `lib/`-folder within your theme or plugin.

### Using `git clone`

	git clone git@github.com:Webbgaraget/wg-custom-post-type.git

If you're interested in adding meta boxes for your CPT (you probably are), initialise the submodule with the following command (ran from the root of the newly cloned git repo)

	git submodule update --init

### Using the zip file

Unpack the zip file where you want the library to reside. Then head on over to [wg-meta-box](http://github.com/webbgaraget/wg-meta-box "wg-meta-box @ Github") and download
and unpack that library into the `wg-custom-post-type/lib/wg-meta-box` folder.

## Example: Creating your first custom post type

This is a simple example on how to create a CPT for Events:

	// Post type labels
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

	// Create a taxonomy for event types and a meta box for event information
	$event
		->add_taxonomy( 'event-type', array(
			'labels' => array(
				'name'          => 'Event types',
				'singular_name' => 'Event type',
				'add_new_item'  => 'Add new type',
				'edit_item'     => 'Edit type',
				'update_item'   => 'Update type',
			)
		) )
		->add_meta_box( 'event-info', 'Event information', array(
			'date' => array(
		        'type'  => 'date',
		        'label' => 'Date',
		    ),
		    'time' => array(
		        'type'  => 'text',
		        'label' => 'Time',
		    )
		) );
	
Note how the labels and options arrays passed to `wg-custom-post-type` are identical to the ones you usually pass to `register_post_type()`.

## The Basics

### Constructor
	__construct( $post_type, $args, $label_check = 'require_labels' )

* **$post\_type** – Name of the post type. _(string, required)_

* **$args** – Options array as expected by WP:s [register\_post\_type()](http://codex.wordpress.org/Function_Reference/register_post_type). _(array, required)_

* **$label\_check** – Flag deciding whether the `labels` option should be checked for required labels. _(string, optional, default: 'require\_labels')_


Creates a new custom post type with the given name $post_type and the given arguments $argts.
The library by default checks the given $args array to see that all basic post type labels are set.
To disable this check, pass 'disabled' as the third parameter.

*Return*: null

### Taxonomies

	add_taxonomy( $id, $args, $admin_column = null )
	
* **$id** – Internal ID of the taxonomy. _(string, required)_

* **$args** – Options for the taxonomy as expected in the third argument of WP:s [register\_taxonomy()](http://codex.wordpress.org/Function_Reference/register_post_type). _(array, required)_

* **$admin\_column** – Options for displaying the taxonomy terms as a column in the list of posts for the current CPT. _(array|boolean, optional)_

Adds a taxonomy to the post type.

Use the last argument to set options for displaying the taxonomy terms in the admin table for the current post type.
Available options are:

* 'display\_after' – ID of the column this column should be positioned after. _(string, optional, default: 'title')_
* 'label' - Column heading label. _(string, optional, default: the name of the taxonomy)_
* 'sortable' - Flag determining if the list should be sortable on this column. _(boolean, optional, default: true)_

Pass `true` as the `$admin_column`-parameter to use the default column options mentioned above.

*Return*: **$this** – For chaining.
	
### Meta box

	add_meta_box( $id, $title, $fields, $context = 'advanced', $priority = 'default' )

This method uses the method `WGMetaBox::add_meta_box()` to create the meta box.
See information about the arguments in the documentation for [wg-meta-box](http://github.com/webbgaraget/wg-meta-box "wg-meta-box @ Github").

Return: **$this** – For chaining.

### Featured images

	add_featured_image( $id, $label, array $size_attr = null )

* **$id** – Internal ID of the image _(string, required)_

* **$label** – Label to be displayed in the admin area _(string, required)_

* **$size\_attr** – Optional array of attributes for a thumbnail size to be registered. See above for info. _(array, optional, default: null)_

Adds an additional "featured image" using the plugin [Multiple Post Thumbnails](http://wordpress.org/extend/plugins/multiple-post-thumbnails/).

The (optional) $size_attr-array should, if given, have three or four elements
corresponding to the arguments expected by WP:s [add\_image\_size()](http://codex.wordpress.org/Function_Reference/add_image_size).

Return: **$this** – For chaining.

## Customizing the admin screen

* `set_title_placeholder`
* `set_menu_icon`
* `set_screen_icon`

## Help tabs
* `add_help_tab`
* `add_help_tabs`
* `set_help_sidebar`

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
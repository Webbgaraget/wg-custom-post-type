<?php
/**
 * A class simplifying the creation of usable Custom Post Types in WordPress
 *
 * This class provides a simpler interface for commonly used (and less
 * commonly but pretty sweet) features when creating custom post types
 * for WordPress.
 *
 * @author Webbgaraget
 * @link http://www.webbgaraget.se
 * @uses wg-meta-box (http://webbgaraget.github.com/wg-meta-box/)
 */
class WG_Custom_Post_Type
{
	/**
	 * Internal ID of the CPT
	 * @var string
	 */
	public $post_type;

	/**
	 * Options for the CPT
	 * @var array
	 */
	protected $post_type_args;

	/**
	 * Taxonomies associated to this CPT
	 * @var array
	 */
	protected $_taxonomies = array();

	/**
	 * Placeholder text for the "Title" input field
	 * @var string
	 */
	protected $_title_placeholder;

	/**
	 * Help tabs to be displayed on the screens for the CPT
	 * @var array
	 */
	protected $_help_tabs;

	/**
	 * Labels that are required to be set in the CPT options.
	 * This requirement is forced if the $label_check argument in the constructor is set to require labels
	 * @var array
	 */
	protected $_required_labels = array(
		'name'				,
		'singular_name'		,
		'all_items'			,
		'add_new_item'		,
		'edit_item'			,
		'new_item'			,
		'view_item'			,
		'search_items'		,
		'not_found'			,
		'not_found_in_trash',
	);

	/**
	 * URL to the menu icon
	 * @var string
	 */
	protected $_menu_icon;

	/**
	 * URL to the screen icon
	 * @var string
	 */
	protected $_screen_icon;


/************************************************
 * Publicly available interface
 ************************************************/
	/**
	 * Creates the custom post type (CPT)
	 *
	 * @param string $post_type Internal ID of the CPT
	 * @param array $args Options as expected by WP:s register_post_type()
	 * @param string $label_check Optional flag to check if required labels are set in the $args['labels'] array. Default: require_labels
	 */
	public function __construct( $post_type, $args, $label_check = 'require_labels' )
	{
		// Reserved terms that WordPress wont let us use as custom post type ID.
		// The list can be found here: http://codex.wordpress.org/Function_Reference/register_post_type#Reserved_Post_Types
		$reserved = array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item' );
		
		if ( in_array( $post_type, $reserved ) )
		{
			throw new Exception( __CLASS__ . ": \"{$post_type}\" is a reserved word and cannot be used as ID for a custom post type. More info in the <a href=\"http://codex.wordpress.org/Function_Reference/register_post_type#Reserved_Post_Types\">WordPress Codex</a>" );
		}
		
		// According to the DB schema of WordPress, $post_type can't be longer than 20 characters
		// http://codex.wordpress.org/Post_Types#Naming_Best_Practices
		if ( strlen( $post_type ) > 20 )
		{
			throw new Exception( __CLASS__ . ": \"{$post_type}\" (length: " . strlen( $post_type ) . ") is longer than the allowed 20 characters. More info in the <a href=\"http://codex.wordpress.org/Post_Types#Naming_Best_Practices\">WordPress Codex</a>" );
		}
		
		if ( is_array( $args ) && 'require_labels' == $label_check )
		{
			// Check if all the required labels are set in the arguments
			foreach ( $this->_required_labels as $label )
			{
				if ( ! isset( $args['labels'][ $label ] ) )
				{
					throw new Exception( __CLASS__ . ': Required label "' . $label . '" not set for CPT "' . $post_type . '". Args: ' . print_r( $args, true ) );
				}
			}
		}

		$this->post_type	  = $post_type;
		$this->post_type_args = $args;

		add_action( 'init', array( &$this, '_cb_init' ) );
	}

	/**
	 * Adds a new taxonomy and associates it with the CPT.
	 *
	 * @param string $id Internal ID of the taxonomy
	 * @param array $args Options for the taxonomy as expected in the third argument of WP:s register_taxonomy()
	 * @param boolean|array $admin_column Optional options for displaying the taxonomy terms as a column list of posts for this CPT.
	 * @return $this For chaining
	 */
	public function add_taxonomy( $id, $args, $admin_column = null )
	{
		// Reserved terms that WordPress wont let us use as taxonomy ID.
		// The list can be found here: http://codex.wordpress.org/Function_Reference/register_taxonomy#Reserved_Terms
		$reserved = array( 'attachment', 'attachment_id', 'author', 'author_name', 'calendar', 'cat', 'category', 'category__and', 'category__in', 'category__not_in', 'category_name', 'comments_per_page', 'comments_popup', 'cpage', 'day', 'debug', 'error', 'exact', 'feed', 'hour', 'link_category', 'm', 'minute', 'monthnum', 'more', 'name', 'nav_menu', 'nopaging', 'offset', 'order', 'orderby', 'p', 'page', 'page_id', 'paged', 'pagename', 'pb', 'perm', 'post', 'post__in', 'post__not_in', 'post_format', 'post_mime_type', 'post_status', 'post_tag', 'post_type', 'posts', 'posts_per_archive_page', 'posts_per_page', 'preview', 'robots', 's', 'search', 'second', 'sentence', 'showposts', 'static', 'subpost', 'subpost_id', 'tag', 'tag__and', 'tag__in', 'tag__not_in', 'tag_id', 'tag_slug__and', 'tag_slug__in', 'taxonomy', 'tb', 'term', 'type', 'w', 'withcomments', 'withoutcomments', 'year' );
		
		if ( in_array( $id, $reserved ) )
		{
			throw new Exception( __CLASS__ . ": add_taxonomy() failed, \"{$id}\" is a reserved term and cannot be used as taxonomy ID. More information in the <a href=\"http://codex.wordpress.org/Function_Reference/register_taxonomy#Reserved_Terms\">WordPress Codex</a>." );
		}
		
		
		// Save taxonomy for later when the init callback is called
		$this->_taxonomies[] = array(
			'slug'         => $id,
			'args'         => $args,
		);
		
		if ( is_array( $admin_column ) || true === $admin_column )
		{
			// Set the taxonomy label as default label for the admin column
			$taxonomy_label = ( isset( $args['label'] ) ? $args['label'] : $args['labels']['name'] );
			
			// Display the admin column after the post title
			// and make it sortable by default
			$default_admin_column = array(
				'display_after' => 'title',
				'label'         => $taxonomy_label,
				'sortable'      => true,
			);
			
			if ( true === $admin_column )
			{
				// Use the default values
				$admin_column = $default_admin_column;
			}
			else
			{
				$admin_column = array_merge( $default_admin_column, $admin_column );
			}
			
			if ( ! is_array( $this->admin_columns ) )
			{
				$this->admin_columns = array();
				
				// Add columns to the admin screen
				add_filter( "manage_{$this->post_type}_posts_columns", array( &$this, '_cb_register_columns' ) );
				add_filter( "manage_edit-{$this->post_type}_sortable_columns", array( &$this, '_cb_sortable_columns' ) );
				add_action( "manage_{$this->post_type}_posts_custom_column", array( &$this, '_cb_display_column_values' ), 10, 2 );
				add_filter( 'posts_clauses', array( &$this, '_cb_orderby_column' ), 10, 2 );
			}
			
			$this->admin_columns[ $id ] = $admin_column;
		}

		return $this;
	}

	/**
	 * Adds a new meta box for the CPT using WGMetaBox::add_meta_box()
	 *
	 * @param string $id Internal ID of the meta box
	 * @param string $title The title displayed in the meta box header
	 * @param array $fields Array of fields defined as described in WGMetaBox
	 * @param string $context Optional context of the meta box. Default: 'advanced'
	 * @param string $priority Optional priority of the meta box. Default: 'default'
	 *
	 * @return $this For chaining
	 */
	public function add_meta_box( $id, $title, $fields, $context = 'advanced', $priority = 'default' )
	{
		if ( ! class_exists( 'WGMetaBox' ) )
		{
			if ( file_exists( dirname( __FILE__ ) . '/lib/wg-meta-box/WGMetaBox.php' ) )
			{
				require_once( dirname( __FILE__ ) . '/lib/wg-meta-box/WGMetaBox.php' );
			}
			else
			{
				throw new Exception( __CLASS__ . ' requires the lib wg-meta-box (http://webbgaraget.github.com/wg-meta-box/) for meta boxes' );
			}
		}

		WGMetaBox::add_meta_box( $id, $title, $fields, $this->post_type, $context, $priority );

		return $this;
	}

	/**
	 * Adds an additional "featured image" using the plugin Multiple Post Thumbnails
	 * (http://wordpress.org/extend/plugins/multiple-post-thumbnails/)
	 *
	 * The (optional) $size_attr-array should, if given, have three or four elements
	 * corresponding to the arguments expected by WordPress add_image_size():
	 * http://codex.wordpress.org/Function_Reference/add_image_size
	 *
	 * @param string $id Internal ID of the image
	 * @param string $label Label to be displayed in the admin area
	 * @param array $size_attr Optional array of attributes for a thumbnail size to be registered. See above for info.
	 * @return $this For chaining
	 */
	public function add_featured_image( $id, $label, array $size_attr = null )
	{
		if ( ! class_exists( 'MultiPostThumbnails' ) )
		{
			return;
		}

		new MultiPostThumbnails(
			array(
				'id'		=> $id,
				'label'		=> $label,
				'post_type' => $this->post_type,
			)
		);

		if ( is_array( $size_attr ) )
		{
			if ( 3 == count( $size_attr ) )
			{
				add_image_size( $size_attr[0], $size_attr[1], $size_attr[2] );
			}
			elseif ( 4 == count( $size_attr ) )
			{
				add_image_size( $size_attr[0], $size_attr[1], $size_attr[2], $size_attr[3] );
			}
		}

		return $this;
	}

	/**
	 * Adds multiple help tabs to the screens for this CPT.
	 * @param array $tabs The tabs to add
	 * @return $this For chaining
	 */
	public function add_help_tabs( array $tabs )
	{
		foreach ( $tabs as $tab )
		{
			$this->add_help_tab( $tab );
		}

		return $this;

	}

	/**
	 * Adds a help tab to the screens for this CPT.
	 * For information on the $tab argument, see documentation for WP_Screen::add_help_tab():
	 * http://codex.wordpress.org/Function_Reference/add_help_tab
	 *
	 * @param array $tab Settings for the tab
	 * @return $this For chaining
	 */
	public function add_help_tab( array $tab )
	{
		if ( is_null( $this->_help_tabs ) )
		{
			add_action( "load-{$GLOBALS['pagenow']}", array( &$this, '_cb_add_help_to_screen' ), 10, 3 );

			$this->_help_tabs = array();
		}

		$this->_help_tabs[] = $tab;

		return $this;
	}

	/**
	 * Sets the content for the help sidebar for this CPT
	 *
	 * @param string $content HTML markup for the help sidebar
	 * @return $this For chaining
	 */
	public function set_help_sidebar( $content )
	{
		if ( is_null( $this->_help_sidebar ) )
		{
			add_action( "load-{$GLOBALS['pagenow']}", array( &$this, '_cb_add_help_to_screen' ), 10, 3 );
		}

		$this->_help_sidebar = $content;

		return $this;
	}

	/**
	 * Sets the placeholder in the "Title" input field when adding or editing an item
	 * of this CPT.
	 *
	 * @param $placeholder The text to set the placeholder to
	 * @return $this For chaining
	 */
	public function set_title_placeholder( $placeholder )
	{
		if ( is_null( $this->_title_placeholder ) )
		{
			add_filter( 'enter_title_here', array( &$this, '_cb_filter_title_placeholder' ) );
		}

		$this->_title_placeholder = $placeholder;

		return $this;
	}

	/**
	 * Sets the menu icon for this CPT.
	 * This method expects an icon sprite as described by Randy Jensen here:
	 * http://randyjensenonline.com/thoughts/wordpress-custom-post-type-fugue-icons/
	 *
	 * @param string $icon_url
	 * @return $this For chaining
	 */
	public function set_menu_icon( $icon_url )
	{
		if ( is_null( $this->_menu_icon ) && is_null( $this->_screen_icon ) )
		{
			add_action( 'admin_head', array( &$this, '_cb_admin_head' ) );
		}

		$this->_menu_icon = $icon_url;

		return $this;
	}

	/**
	 * Sets the screen icon for this CPT.
	 *
	 * @param string $icon_url
	 * @return $this For chaining
	 */
	public function set_screen_icon( $icon_url )
	{
		if ( is_null( $this->_menu_icon ) && is_null( $this->_screen_icon ) )
		{
			add_action( 'admin_head', array( &$this, '_cb_admin_head' ) );
		}

		$this->_screen_icon = $icon_url;

		return $this;
	}


/************************************************
 * Callbacks called by WP hooks
 ************************************************/
	/**
	 * Action callback for registering the CPT with given name and options
	 * Action: "init"
	 */
	public function _cb_init()
	{
		$default_args = array(
			'public'	  => true,
			'rewrite'	  => true,
			'has_archive' => true,
		 );

		$args = array_merge( $default_args, $this->post_type_args );

		register_post_type( $this->post_type, $args );

		// Register any taxonomies associated to this CPT
		foreach ( $this->_taxonomies as $taxonomy )
		{
			register_taxonomy( $taxonomy['slug'], $this->post_type, $taxonomy['args'] );
		}
	}

	/**
	 * Action callback for outputting custom CSS to the admin area
	 * Action: "admin_head"
	 */
	public function _cb_admin_head()
	{
		if ( is_null( $this->_menu_icon ) && is_null( $this->_screen_icon ) )
		{
			// No icons are set, no need to output CSS
			return;
		}

		echo '<style type="text/css" media="screen">';

		if ( ! is_null( $this->_menu_icon ) )
		{
			// Let's be specific so the original CSS is overridden
			echo "#wpwrap #adminmenuwrap #adminmenu #menu-posts-{$this->post_type} .wp-menu-image { background: url({$this->_menu_icon}) no-repeat 6px -17px; } ";
			echo "#wpwrap #adminmenuwrap #adminmenu #menu-posts-{$this->post_type}:hover .wp-menu-image,";
			echo "#wpwrap #adminmenuwrap #adminmenu #menu-posts-{$this->post_type}.wp-has-current-submenu .wp-menu-image { background-position:6px 7px; } ";
		}

		if ( ! is_null( $this->_screen_icon ) )
		{
			echo "#wpwrap #wpbody-content #icon-edit.icon32.icon32-posts-{$this->post_type} { background: url({$this->_screen_icon}) no-repeat; } ";
		}

		echo '</style>';
	}

	/**
	 * Filter callback for setting the "Enter title here" placeholder on the Edit screen
	 * Filter: "enter_title_here"
	 *
	 * @param $title The title placeholder before our filter
	 * @return The filtered title placeholder
	 */
	public function _cb_filter_title_placeholder( $title )
	{
		$screen = get_current_screen();

		if	( $this->post_type == $screen->post_type )
		{
			$title = $this->_title_placeholder;
		}

		return $title;
	}

	/**
	 * Action callback for adding help tabs and help sidebar.
	 * Action: "load-{$GLOBALS['pagenow']}"
	 */
	public function _cb_add_help_to_screen()
	{
		$screen = get_current_screen();
		if ( $this->post_type != $screen->post_type )
		{
			return;
		}

		if ( is_array( $this->_help_tabs ) )
		{
			foreach ( $this->_help_tabs as $tab )
			{
				$screen->add_help_tab( $tab );
			}
		}

		if ( ! is_null( $this->_help_sidebar ) )
		{
			$screen->set_help_sidebar( $this->_help_sidebar );
		}
	}
	
	/**
	 * Adds columns to the admin screen for this post type
	 * Action: "manage_{$this->post_type}_posts_columns"
	 * 
	 * @param array $post_columns
	 * @return array
	 */
	public function _cb_register_columns( $post_columns )
	{
		foreach ( $this->admin_columns as $taxonomy_id => $admin_column )
		{
			$index = array_search( $admin_column['display_after'], array_keys( $post_columns ) ) + 1;
			$new_columns = array_slice( $post_columns, 0, $index );
			$new_columns[ $taxonomy_id ] = $admin_column['label'];
			$new_columns = array_merge( $new_columns, array_slice( $post_columns, $index ) );
			
			$post_columns = $new_columns;
		}
		return $post_columns;
	}
	
	public function _cb_sortable_columns( $columns )
	{
		foreach ( $this->admin_columns as $taxonomy_id => $admin_column )
		{
			if ( true === $admin_column['sortable'] )
			{
				$columns[ $taxonomy_id ] = $taxonomy_id;
			}
		}
		
		return $columns;
	}
	
	/**
	 * Outputs the value for the columns we have added to the admin screen
	 * Action: manage_{$this->post_type}_custom_column
	 *
	 * @param string $column_name 
	 */
	public function _cb_display_column_values( $column_name, $post_id )
	{
		foreach ( $this->admin_columns as $taxonomy_id => $admin_column )
		{
			if ( $column_name == $taxonomy_id )
			{
				echo get_the_term_list( $post_id, $taxonomy_id, '', ', ', '' );
				return;
			}
		}
	}
	
	/**
	 * Sorting on taxonomy admin columns.
	 *
	 * Thanks goes to Scribu: http://scribu.net/wordpress/sortable-taxonomy-columns.html
	 * as well as to the commenter "jessica" on StackExchange for bug fixing:
	 * http://wordpress.stackexchange.com/questions/8811/sortable-admin-columns-when-data-isnt-coming-from-post-meta#comment70352_11256
	 *
	 * Action: posts_clauses
	 * 
	 * @param array $clauses 
	 * @param WP_Query $wp_query 
	 * @return array
	 */
	public function _cb_orderby_column( $clauses, $wp_query )
	{
		global $wpdb;

		if ( isset( $wp_query->query['orderby'] ) && array_key_exists( $wp_query->query['orderby'], $this->admin_columns ) ) 
		{
			$clauses['join'] .= <<<SQL
LEFT OUTER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID={$wpdb->term_relationships}.object_id
LEFT OUTER JOIN {$wpdb->term_taxonomy} ON ({$wpdb->term_relationships}.term_taxonomy_id={$wpdb->term_taxonomy}.term_taxonomy_id) AND (taxonomy = '{$wp_query->query['orderby']}' OR taxonomy IS NULL)
LEFT OUTER JOIN {$wpdb->terms} USING (term_id)
SQL;
			$clauses['groupby'] = "ID";
			$clauses['orderby']  = "GROUP_CONCAT({$wpdb->terms}.name ORDER BY name ASC) ";
			$clauses['orderby'] .= ( 'ASC' == strtoupper( $wp_query->get('order') ) ) ? 'ASC' : 'DESC';
		}

		return $clauses;
	}
}
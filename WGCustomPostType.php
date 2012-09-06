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
class WGCustomPostType
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
     * This requirement depends on the $require_labels argument in the constructor.
     * @var array
     */
    protected $_required_labels = array(
        'name'              ,
        'singular_name'     ,
        'all_items'         ,
        'add_new_item'      ,
        'edit_item'         ,
        'new_item'          ,
        'view_item'         ,
        'search_items'      ,
        'not_found'         ,
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
     * @param boolean $require_labels Optional check if required labels are set in the $args['labels'] array. Default: true
     */
    public function __construct( $post_type, $args = null, $require_labels = true )
    {
        if ( is_array( $args ) && $require_labels )
        {
            // Check if all the required labels are set in the arguments
            foreach ( $this->_required_labels as $label )
            {
                if ( ! isset( $args['labels'][$label] ) )
                {
                    throw new Exception( __CLASS__ . ': Required label "' . $label . '" not set for CPT "' . $post_type . '". Args: ' . print_r( $args, true ) );
                }
            }
        }
        
        $this->post_type      = $post_type;
        $this->post_type_args = $args;
        
        add_action( 'init', array( &$this, '_cb_init' ) );
    }

    /**
     * Adds a new taxonomy and associates it with the CPT.
     *
     * @param string $id Internal ID of the taxonomy
     * @param array $args Options for the taxonomy as expected by WP:s register_taxonomy()
     * @return $this For chaining
     */
    public function add_taxonomy( $id, $args = array() )
    {
        // Save taxonomy for later when the init callback is called
        $this->_taxonomies[] = array(
            'slug' => $id,
            'args' => $args,
        );
        
        return $this;
    }
    
    /**
     * Adds a new meta box for the CPT using WGMetaBox::add_meta_box()
     *
     * @param string $id Internal ID of the taxonomy
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
     * Adds an additional "featured image" using the plugin
     * Multiple Post Thumbnails (http://wordpress.org/extend/plugins/multiple-post-thumbnails/)
     * @param string $id 
     * @param string $label 
     */
    public function add_featured_image( $id, $label )
    {
        if ( ! class_exists( 'MultiPostThumbnails' ) )
        {
            return;
        }
        
        new MultiPostThumbnails(
            array(
                'id'        => $id,
                'label'     => $label,
                'post_type' => $this->post_type,
            )
        );
        
        return $this;
    }
    
    /** 
     * Adds multiple help tabs to the screens for this CPT.
     * @param array $tabs The tabs to add
     * @return $this For chaining
     */
    public function add_help_tabs( $tabs = array() )
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
    public function add_help_tab( $tab = array() )
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
            'public'      => true,
            'rewrite'     => true,
            'has_archive' => true,
         );

        $args = array_merge( $default_args, $this->post_type_args );
        
        register_post_type( $this->post_type, $args );
        
        // Register any taxonomies associated with the CPT
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
     * Filter callback for setting the "Enter title here" placeholder
     * Filter: "enter_title_here"
     *
     * @param $title The title placeholder before our filter
     * @return The filtered title placeholder
     */
    public function _cb_filter_title_placeholder( $title )
    {
        $screen = get_current_screen();

        if  ( $this->post_type == $screen->post_type )
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
    
    
}
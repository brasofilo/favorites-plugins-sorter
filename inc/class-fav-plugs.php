<?php

/**
 * Class based on Plugin Class Demo, by toscho
 * https://gist.github.com/3804204
 */

class B5F_All_Favorites_Ordered
{
    /**
     * Plugin instance.
     *
     * @see get_instance()
     * @type object
     */
    protected static $instance = NULL;

    /**
     * URL to this plugin's directory.
     *
     * @type string
     */
    public $plugin_url = '';

    /**
     * Path to this plugin's directory.
     *
     * @type string
     */
    public $plugin_path = '';
    
     /**
     * Options name.
     *
     * @type string
     */
    public static $opt_name    = 'fav_plugins_settings';

    /**
     * Options default
     * Changes here should be also made in $opt_values/render_settings_page()
     * 
     * @type array 
     */
    public static $opt_defaults = array(
          'per_page'   => '100'
        , 'order_by'   => 'name'
        , 'order'      => 'ASC'
        , 'extra_info' => true
        , 'highlight'  => '100'
    );

    /**
     * Options internal holder
     * 
     * @type array 
     */
    public $params      = array();

    /**
     * Access this plugin’s working instance
     *
     * @wp-hook plugins_loaded
     * @since   2012.09.13
     * @return  object of this class
     */
    public static function get_instance()
    {
        NULL === self::$instance and self::$instance = new self;

        return self::$instance;
    }


    /**
     * Used for regular plugin work.
     *
     * @wp-hook plugins_loaded
     * @since   2012.09.10
     * @return  void
     */
    public function plugin_setup()
    {
		if ( !current_user_can( 'manage_options' ) )
			return;
		
        $this->params      = get_option( self::$opt_name );
        $this->plugin_url  = plugins_url( '/', dirname( __FILE__ ) );
        $this->plugin_path = plugin_dir_path( dirname( __FILE__ ) );
        $this->load_language( 'fav-plugs' );

        add_action( 'load-plugin-install.php', array( $this, 'extra_plugin_info' ) );
		$hook = is_multisite() ? 'network_' : '';
        add_action( "{$hook}admin_menu", array( $this, 'settings_menu' ) );
        add_action( 'admin_head-plugins_page_fav-plugins-settings', array( $this, 'settings_style' ) );
		
		# Embeded plugin INSTALL-via-URL
		include_once( 'class-install-from-url.php' );
		new B5F_Upload_Theme();

		
		# RENAME GITHUB MASTER DIRECTORY
		add_filter( 'upgrader_source_selection', array( $this, 'rename_github_zip' ), 1, 3);
	}


    /**
     * Constructor. Intentionally left empty and public.
     *
     * @see plugin_setup()
     * @since 2012.09.12
     */
    public function __construct()
    {
        
    }


    /**
     * Add filters to plugin-install page
     *
     * @return void
     */
    public function extra_plugin_info()
    {
        add_filter( 'plugins_api_args', array( $this, 'max_num_of_plugs' ), 10, 2 );
        add_filter( 'plugins_api_result', array( $this, 'order_favorite_plugins' ), 10, 3 );

        if( !isset( $_GET['tab'] ) )
            return;
        
        if ( 'favorites' == $_GET['tab'] && $this->params['extra_info'] )
            add_filter( 'plugin_install_action_links', array( $this, 'display_extra_info' ), 10, 2 );
    }


    /**
     * Add extra text to each plugin action links
     *
     * @param array $action_links Details|Install Now
     * @param string $plugin Plugin slug
     * @return array Action links
     */
    public function display_extra_info( $action_links, $plugin )
    {
        // LAST UPDATED
        $slug_hash    = md5( $plugin['slug'] );
        $last_updated = get_transient( "range_plu_{$slug_hash}" );

		// TODO: check transient time
		if ( false === $last_updated )
        {
            $last_updated = $this->get_last_updated( $plugin['slug'] );
            set_transient( "range_plu_{$slug_hash}", $last_updated, 86400 );
        }

        $last = '';
        if ( $last_updated )
            $last = '&nbsp; | &nbsp;Last Updated: ' . esc_html( $last_updated );

        // CONTRIBUTORS
        foreach ( $plugin['contributors'] as $key => $value ) {
            $contributors[] = '<a class="contribs" href="' . admin_url( 'plugin-install.php?tab=favorites&user=' ) . $key . '">' . $key . '</a>';
		}
			
        $contributors = '&nbsp; | &nbsp;Contributors: ' . implode( ', ', $contributors );
		
       // Read in existing option value from database
        $opt_val    = get_option( self::$opt_name );

		$num_ratings = ( (int)$plugin['num_ratings'] > $opt_val['highlight'] ) ? '<span style="font-weight:bolder;color:#00D">'.$plugin['num_ratings'].'</span>' : $plugin['num_ratings'];
        
		// FINAL HTML
        $action_links[] = '<br>
			<span style="color:#969696">&nbsp;Tested: '
                . $plugin['tested']
                . '&nbsp; | &nbsp;Requires: '
                . $plugin['requires']
                . '&nbsp; | &nbsp;Num ratings: '
                . $num_ratings
                . $contributors
                . $last
                . '</span>';
        return $action_links;
    }


    /**
     * Modifies the per_page argument when querying Favorites
     * 
     * @param  object $args [page, per_page, browse/user]
     * @param  string $action [query_plugins]
     * @return object $args
     */
    public function max_num_of_plugs( $args, $action )
    {
        // Viewing thickbox, exit earlier
        if( isset( $_GET['section'] ) )
            return $args;

        // Not our query, exit earlier. Other tabs have browse instead of user.
        if ( !isset( $args->user ) )
            return $args;

        $args->per_page = $this->params['per_page'];
        return $args;
    }


    /**
     * Sort result from Plugin API call
     * Add admin head action to print CSS
     * 
     * @param  object $res Api response
     * @param  string $action [query_plugins]
     * @param  object $args [per_page, order_by, order]
     * @return object $res Original or modified response
     */
    public function order_favorite_plugins( $res, $action, $args )
    {
        // Viewing thickbox, exit earlier
        if( isset( $_GET['section'] ) )
            return $res;

        // Not our query, exit earlier
        if ( !isset( $args->user ) )
            return $res;

        // Amazingly, this works here
        add_action( 'admin_head-plugin-install.php', array( $this, 'hide_pagination' ) );

		if( is_array( $res->plugins ) )
	        usort( $res->plugins, array( $this, 'sort_obj' ) );

        return $res;
    }


    /**
     * Hide elements from Favorites screen
     * 
     * @return string Echoed in admin_head
     */
    public function hide_pagination()
    {
        echo '<style>
				.install-help, .pagination-links {display:none !important}
				.tablenav.top { margin-top: -30px }
			</style>';
    }


    /**
     * Add submenu item to Plugins
     * 
     * @return void
     */
    public function settings_menu()
    {
        add_plugins_page(
                __( 'Favorites Settings', 'fav-plugs' )
                , __( 'Favorites Settings', 'fav-plugs' )
                , 'manage_options'
                , 'fav-plugins-settings'
                , array( $this, 'render_settings_page' )
        );
    }


    /**
     * Prints the plugin Settings page
     * 
     * @return Html content
     */
    public function render_settings_page()
    {

        // check the user capability 
//        if ( !current_user_can( 'manage_options' ) )
//        {
//            wp_redirect( admin_url( 'plugins.php' ) );
//            exit;
//        }

        // variables for the field and option names 
        $opt_values = array( 'per_page', 'order_by', 'order', 'extra_info', 'highlight' );
        $hidden_field_name = 'mt_submit_hidden';

        // Read in existing option value from database
        $opt_val    = get_option( self::$opt_name );
		
        // See if the user has posted us some information
        $update_msg = '';
		
		// TODO: CONVERT THIS TO NONCE!
        if ( isset( $_POST[$hidden_field_name] ) && $_POST[$hidden_field_name] == 'Y' )
        {
            foreach ( $opt_values as $val )
            {
                if ( isset( $_POST[$val] ) )
                    $opt_val[$val] = esc_attr( $_POST[$val] );
                else
                    $opt_val[$val] = false;
            }

            update_option( self::$opt_name, $opt_val );
            $update_msg = '
            <div class="updated" id="update-msg">
                <p><strong>' . __( 'Settings saved', 'fav-plugs' ) . '</strong></p>
            </div>
			   <script type="text/javascript">
			    jQuery(document).ready( function($) 
			    {
			        $("#update-msg").delay(500).slideDown();
			        $("#update-msg").delay(5000).slideUp();
			    });     
			    </script>';
        }


        // settings form
        ?>
        <div class="wrap">

            <div id="icon-plugins" class="icon32"></div>
            <h2><?php _e( 'Favorites Plugins Settings', 'fav-plugs' ); ?></h2>
			
            <sub style="margin-left: 45px">
				<a href="<?php echo admin_url( 'plugin-install.php?tab=favorites' ); ?>">
						<?php _e( 'go to favorites', 'fav-plugs' ); ?>
				</a>
			</sub>
			
            <?php echo $update_msg; ?>
			
            <form name="form1" method="post" action="" style="margin: 35px 0 0 45px">
                <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

				
                <p><?php _e( "Per page", 'fav-plugs' ); ?> 
                    <select name="<?php echo $opt_values[0]; ?>">
                        <option value="50" 
							<?php selected( $opt_val[$opt_values[0]], '50' ); ?>>50</option>
                        <option value="100" 
							<?php selected( $opt_val[$opt_values[0]], '100' ); ?>>100</option>
                        <option value="200" 
							<?php selected( $opt_val[$opt_values[0]], '200' ); ?>>200</option>          
                    </select>               
                </p>

				
                <p><?php _e( "Order by", 'fav-plugs' ); ?> 
                    <select name="<?php echo $opt_values[1]; ?>">
                        <option value="name" 
							<?php selected( $opt_val[$opt_values[1]], 'name' ); ?>>Name</option>
                        <option value="slug" 
							<?php selected( $opt_val[$opt_values[1]], 'slug' ); ?>>Slug</option>
                        <option value="requires" 
							<?php selected( $opt_val[$opt_values[1]], 'requires' ); ?>>Requires</option>  
                        <option value="tested" 
							<?php selected( $opt_val[$opt_values[1]], 'tested' ); ?>>Tested (up to)</option>
                        <option value="rating" 
							<?php selected( $opt_val[$opt_values[1]], 'rating' ); ?>>Rating</option>
                        <option value="num_ratings" 
							<?php selected( $opt_val[$opt_values[1]], 'num_ratings' ); ?>>Number of ratings</option>         
                        <option value="version" 
							<?php selected( $opt_val[$opt_values[1]], 'version' ); ?>>Version</option>         
                    </select>
                </p> 

				
                <p><?php _e( "Order by", 'fav-plugs' ); ?> 
                    <select name="<?php echo $opt_values[2]; ?>">
                        <option value="ASC" 
							<?php selected( $opt_val[$opt_values[2]], 'ASC' ); ?>>ASC</option>
                        <option value="DESC" 
							<?php selected( $opt_val[$opt_values[2]], 'DESC' ); ?>>DESC</option>
                    </select>
                </p>

				
                <p>
				<div>
					<label class="alignv">
						<input name="<?php echo $opt_values[3]; ?>" 
							   id="extra-info" type="checkbox" value="1" 
							   class="alignv" <?php 
							   echo checked( 1, $opt_val[$opt_values[3]], false ); ?> /> 
								   <?php _e( 'Enable extra plugin info', 'fav-plugs' ); ?>
					</label> 
				</div>
                </p>

				
                <p><?php _e( "Highlight number of ratings", 'fav-plugs' ); ?> 
                    <select name="<?php echo $opt_values[4]; ?>">
                        <option value="50" 
							<?php selected( $opt_val[$opt_values[4]], '50' ); ?>>50</option>
                        <option value="100" 
							<?php selected( $opt_val[$opt_values[4]], '100' ); ?>>100</option>
                        <option value="500" 
							<?php selected( $opt_val[$opt_values[4]], '500' ); ?>>500</option>          
                    </select>               
                </p>

				
                <p class="submit">
                    <input type="submit" name="Submit" 
						   class="button-primary" 
						   value="<?php esc_attr_e( 'Save Changes', 'fav-plugs' ); ?>" />
                </p>

            </form>
        </div>
        <?php
    }


    /**
     * Prints the styles for the Settings page
     * 
     * @return Html content
     */
    public function settings_style()
    {
        ?>
        <style type="text/css">
            #update-msg {display:none;margin: 5px 25% 15px; text-align: center}
            a.contribs { color:#75B5B7 !important}
            p { padding: 4px 0}
            #extra-info {margin-right: 4px; }
            label.alignh {margin-left: 10px; }
            label.alignv {
                display: block;
                padding-left: 15px;
                text-indent: -15px;
            }
            input.alignv {
                width: 13px;
                height: 13px;
                padding: 0;
                margin:0;
                vertical-align: bottom;
                position: relative;
                top: -1px;
                overflow: hidden;
            }
        </style>
        <?php
    }


	/**
	 * Removes the prefix "-master" when updating from GitHub zip files
	 * 
	 * See: https://github.com/YahnisElsts/plugin-update-checker/issues/1
	 * 
	 * @param string $source
	 * @param string $remote_source
	 * @param object $thiz
	 * @return string
	 */
	public function rename_github_zip( $source, $remote_source, $thiz )
	{
		if(  strpos( $source, 'favorite-plugins-sorter') === false )
			return $source;

		$path_parts = pathinfo($source);
		$newsource = trailingslashit($path_parts['dirname']). trailingslashit('favorite-plugins-sorter');
		rename($source, $newsource);
		return $newsource;
	}

	
    /**
     * Get last updated date for a given plugin
     * From the plugin http://wordpress.org/extend/plugins/plugin-last-updated/
     * 
     * @param strin $slug
     * @return string Date of last update
     */
    private function get_last_updated( $slug )
    {
        $request = wp_remote_post(
                'http://api.wordpress.org/plugins/info/1.0/', array(
            'body' => array(
                'action'  => 'plugin_information',
                'request' => serialize(
                        (object) array(
                            'slug'   => $slug,
                            'fields' => array( 'last_updated' => true )
                        )
                )
            )
                )
        );
        if ( 200 != wp_remote_retrieve_response_code( $request ) )
            return false;

        $response = unserialize( wp_remote_retrieve_body( $request ) );

        // Return an empty but cachable response if the plugin isn't in the .org repo
        if ( empty( $response ) )
            return '';
        if ( isset( $response->last_updated ) )
            return sanitize_text_field( $response->last_updated );

        return false;
    }


    /**
     * Sort array of objects
     * 
     * @param  int callback ( mixed $a, mixed $b )
     * @return object Sorted array
     */
    private function sort_obj( $a, $b )
    {
        $val = $this->params['order_by'];

        if ( 'ASC' == $this->params['order'] )
            return strnatcmp( strtolower( $a->$val ), strtolower( $b->$val ) );
        else
            return strnatcmp( strtolower( $b->$val ), strtolower( $a->$val ) );
    }


    /**
     * Loads translation file.
     *
     * Accessible to other classes to load different language files (admin and
     * front-end for example).
     *
     * @wp-hook init
     * @param   string $domain
     * @since   2012.09.11
     * @return  void
     */
    public function load_language( $domain )
    {
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain(
                $domain, WP_LANG_DIR . '/favorite-plugin-sorter/' . $domain . '-' . $locale . '.mo'
        );

        load_plugin_textdomain(
                $domain, FALSE, $this->plugin_path . 'languages'
        );
    }


}


<?php
/*
 * Filter favorite plugins
 * 
 * @plugin Favorites Plugin Sorter
 */

# Busted!
!defined( 'ABSPATH' ) AND exit(
        "<pre>Hi there! I'm just part of a plugin, 
            <h1>&iquest;what exactly are you looking for?" );


class B5F_FPS_Init
{
    private $options;
    public function __construct()
    {
        # Get plugin options
        $this->options = B5F_Favorites_Plugins_Sorter::get_instance()->params;
        # Go to favorites
        add_filter( 
            'plugin_action_links_'.B5F_FPS_FILE, 
            array( $this, 'go_to_favorites' ), 
            10, 2 
        );
        # Run the show
        add_action( 'load-plugin-install.php', array( $this, 'load' ) );
            ## INteresting, but not working hook
            ## add_action( 'install_plugins_pre_favorites', array( $this, 'xxx' ) );
    }
    

    /**
	 * Add link to settings in Plugins list page
	 * 
	 * @wp-hook plugin_action_links
	 * @return Plugin link
	 */
	public function go_to_favorites( $links, $file )
	{
        $links[] = sprintf(
            '<a href="%s">%s</a>',
                admin_url( 'plugin-install.php?tab=favorites' ),
                __( 'Go to favorites' )
        );
		return $links;
	}
    
    
    /**
     * Add filters to plugin-install page
     *
     * @return void
     */
    public function load()
    {
        if( !isset( $_GET['tab'] ) )
            return;
        
        # PRINT FOR BOTH
        if( in_array( $_GET['tab'], array( 'favorites', 'url' ) ) )
            add_filter( 'install_plugins_tabs', array( $this, 'go_to_settings' ) );
        
        # ONLY FAVS
        if( 'favorites' == $_GET['tab'] )
        {
            add_action( 'admin_head', array( $this, 'css' ) );
            add_filter( 
                'plugins_api_args', 
                array( $this, 'max_num_of_plugs' ), 
                10, 2 
            );
            add_filter( 
                'plugins_api_result', 
                array( $this, 'order_favorite_plugins' ), 
                10, 3 
            );
            if ( isset( $this->options['extra_info'] ) )
                add_filter( 
                    'plugin_install_action_links', 
                    array( $this, 'display_extra_info' ), 
                    10, 2 
                );
        }
    }


    /**
     * Small adjustment to the description column
     */
    public function css()
    {
        echo '<style>.column-description {width: 50% !important;}</style>';
    }

    
    /**
     * Add settings link in our pages
     * 
     * @param  array $tabs
     * @return array
     */
    public function go_to_settings( $tabs )
    {
        $tabs['goto'] = sprintf(
            '<small><a href="%s">%s</a></small>',
            admin_url( 'plugins.php?plugin_status=active#fps-settings' ),
            __( 'Favorites & URL settings' )
        );
        return $tabs;
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
        # LAST UPDATED
        $slug_hash    = md5( $plugin['slug'] );
        $last_updated = get_transient( "range_plu_{$slug_hash}" );

		# TODO: check transient time
		if ( false === $last_updated )
        {
            $last_updated = $this->get_last_updated( $plugin['slug'] );
            set_transient( "range_plu_{$slug_hash}", $last_updated, 86400 );
        }

        $last = ( $last_updated ) 
            ? '&nbsp; | &nbsp;Last Updated: ' . esc_html( $last_updated ) : '';

        # CONTRIBUTORS
        $contributors = '';
        if( !empty( $plugin['contributors'] ) )
        {
            foreach ( $plugin['contributors'] as $key => $value )
                $contributors[] = sprintf(
                    '<a class="contribs" href="%1$s%2$s">%2$s</a>',
                    admin_url( 'plugin-install.php?tab=favorites&user=' ),
                    $key
                );

            $contributors = '&nbsp; | &nbsp;Contributors: ' . implode( ', ', $contributors );
        }
		
       # Read in existing option value from database
        $opt_val    = get_option( B5F_Favorites_Plugins_Sorter::$opt_name );

		$num_ratings = ( (int)$plugin['num_ratings'] > $opt_val['highlight'] ) 
            ? '<span style="font-weight:bolder;color:#00D">'.$plugin['num_ratings'].'</span>' 
            : $plugin['num_ratings'];
        
		# FINAL HTML
        $action_links[] = sprintf(
            '<br><span style="color:#969696">&nbsp;%1$s: %2$s &nbsp; | &nbsp;%3$s: %4$s &nbsp; | &nbsp;%5$s: %6$s </span',
            __( 'Tested' ),
            $plugin['tested'],
            __( 'Requires' ),
            $plugin['requires'],
            __( 'Num ratings' ),
            $num_ratings . $contributors . $last
            
        );
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
        # Viewing thickbox, exit earlier
        if( isset( $_GET['section'] ) )
            return $args;

        # Not our query, exit earlier. Other tabs have browse instead of user.
        if ( !isset( $args->user ) )
            return $args;

        $args->per_page = $this->options['per_page'];
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
        # Viewing thickbox, exit earlier
        if( isset( $_GET['section'] ) )
            return $res;

        # Not our query, exit earlier
        if ( !isset( $args->user ) )
            return $res;

        # Amazingly, this works here
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
     * Get last updated date for a given plugin
     * From the plugin http://wordpress.org/extend/plugins/plugin-last-updated/
     * 
     * @param strin $slug
     * @return string Date of last update
     */
    private function get_last_updated( $slug )
    {
        $body_request = serialize( (object) array(
            'slug'   => $slug,
            'fields' => array( 'last_updated' => true )
        ));
        $request = wp_remote_post(
            'http://api.wordpress.org/plugins/info/1.0/', 
            array(
                'body' => array(
                    'action'  => 'plugin_information',
                    'request' => $body_request
                )
            )
        );
        if ( 200 != wp_remote_retrieve_response_code( $request ) )
            return false;

        $response = unserialize( wp_remote_retrieve_body( $request ) );

        # Return an empty but cachable response if the plugin isn't in the .org repo
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
        $val = $this->options['order_by'];

        if ( 'ASC' == $this->options['order'] )
            return strnatcmp( strtolower( $a->$val ), strtolower( $b->$val ) );
        else
            return strnatcmp( strtolower( $b->$val ), strtolower( $a->$val ) );
    }

}


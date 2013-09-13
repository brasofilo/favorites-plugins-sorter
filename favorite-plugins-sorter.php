<?php
/**
 * Plugin Name: Favorite Plugins & Install Via URL
 * Plugin URI:  http://wordpress.stackexchange.com/q/76643/12615
 * Description: Order and increment the number of plugins per page in the Favorites tab. Includes the plugin Install-Via-URL
 * Version:     2013.09.14
 * Network: true
 * Author:      Rodolfo Buaiz
 * Author URI:  http://wordpress.stackexchange.com/users/12615/brasofilo
 * License:     GPL v2
 */

# Busted!
!defined( 'ABSPATH' ) AND exit(
	"<pre>Hi there! I'm just part of a plugin, <h1>&iquest;what exactly are you looking for?"
);


# Main class: B5F_All_Favorites_Ordered
include_once( 'inc/class-fav-plugs.php' );
	

# Activate/De-activate
register_activation_hook( __FILE__, array( 'BL_All_Favs_Init', 'on_activation' ) );
register_deactivation_hook( __FILE__, array( 'BL_All_Favs_Init', 'on_uninstall' ) );


/**
 * Plugin capsule
 */
class BL_All_Favs_Init
{

    /**
     * Class constructor
     */
    public function __construct()
    {
		if( !is_admin() )
			return;
		add_action(
			'plugins_loaded', 
			array( B5F_All_Favorites_Ordered::get_instance(), 'plugin_setup' )
		);
		# PRIVATE REPO 
		include_once 'inc/plugin-updates/plugin-update-checker.php';
		$updateChecker = new PluginUpdateChecker(
			'https://raw.github.com/brasofilo/favorite-plugins-sorter/master/inc/update.json',
			__FILE__,
			'favorite-plugins-sorter-master'
		);
    }


    /**
     * Activation hook.
     */
    public static function on_activation()
    {
        $params = get_option( B5F_All_Favorites_Ordered::$opt_name );
        if ( empty( $params ) )
        {
            update_option( 
                    B5F_All_Favorites_Ordered::$opt_name, 
                    B5F_All_Favorites_Ordered::$opt_defaults 
             );
        }
    }


    /**
     * Runs on uninstall. Removes all log data.
     */
    public static function on_uninstall()
    {
        delete_option( B5F_All_Favorites_Ordered::$opt_name );
    }


}

new BL_All_Favs_Init();
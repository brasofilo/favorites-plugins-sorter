<?php
/*
 * Start up
 * 
 * Un/Install procedures, language and start all classes
 * 
 * @plugin Favorites Plugin Sorter
 */

# Busted!
!defined( 'ABSPATH' ) AND exit(
        "<pre>Hi there! I'm just part of a plugin, 
            <h1>&iquest;what exactly are you looking for?" );

class B5F_Favorites_Plugins_Sorter
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
     * Constructor. Intentionally left empty and public.
     *
     * @see plugin_setup()
     * @since 2012.09.12
     */
    public function __construct() {}

    
    /**
     * Access this plugin working instance
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
        $this->params      = get_option( self::$opt_name );
        $this->plugin_url  = plugins_url( '/', dirname( __FILE__ ) );
        $this->plugin_path = plugin_dir_path( dirname( __FILE__ ) );
        $this->load_language( 'fav-plugs' );
        # Main work 
        include_once 'class-fps-init.php';
        new B5F_FPS_Init();
        # Settings tab
        include_once 'class-fps-settings.php';
        new B5F_FPS_Settings();        
        # GitHub updater
        $this->self_updater();
		# Embeded plugin INSTALL-via-URL
		include_once( 'class-install-from-url.php' );
		new B5F_Upload_Theme();
	}


    /**
     * Activation hook.
     */
    public static function on_activation()
    {
        $params = get_option( self::$opt_name );
        if ( empty( $params ) )
        {
            update_option( 
                    self::$opt_name, 
                    self::$opt_defaults 
             );
        }
    }


    /**
     * Runs on uninstall. Removes all log data.
     */
    public static function on_uninstall()
    {
        delete_option( self::$opt_name );
    }


   /**
    * Self hosted updates
    */
   private function self_updater()
   {
        include_once __DIR__ . '/plugin-update-dispatch.php';
        $icon = '&hearts;';
        new B5F_General_Updater_and_Plugin_Love(array( 
            'repo' => 'favorite-plugins-sorter', 
            'user' => 'brasofilo',
            'plugin_file' => B5F_FPS_FILE,
            'donate_text' => 'Buy me a beer',
            'donate_icon' => "<span  class='mopt-icon'>$icon </span>",
            'donate_link' => 'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=JNJXKWBYM9JP6&lc=US&item_name=Rodolfo%20Buaiz&item_number=Plugin%20donation&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted'
        ));	
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

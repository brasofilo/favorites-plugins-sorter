<?php
/**
 * Settings Class 
 * 
 * @plugin Favorites Plugin Sorter
 */

# Busted!
!defined( 'ABSPATH' ) AND exit(
        "<pre>Hi there! I'm just part of a plugin, 
            <h1>&iquest;what exactly are you looking for?" );

class B5F_FPS_Settings
{
    /**
     * Plugin settings name
     * @var string
     */
    private $option_name;
    
    
    /**
     * Plugin settings value
     * @var array
     */
    public $option_value;
    
    private $opt_values = array( 
        'per_page'
        , 'order_by'
        , 'order'
        , 'extra_info'
        , 'highlight' 
    );

    /**
     *
     * @see plugin_setup()
     * @wp-hook plugins_loaded
     * @return  void
     */
    public function __construct()
    {
        $this->option_name = B5F_Favorites_Plugins_Sorter::$opt_name;
        $this->option_value = $this->get_options();
        add_action( 'load-plugins.php', array( $this, 'load' ) );
    }
    
    public function load()
    {
        # Check and set data
        $this->check_posted_data();
        # Add icon to plugin
       add_action(
            'after_plugin_row_' . B5F_FPS_FILE, 
            array( $this, 'add_config_form' )
        );
        # CSS
        add_action( 'admin_print_scripts-plugins.php', array( $this, 'enqueue' ) );
    }


    /**
     * Check for $_POSTed data and update settings
     * 
     * @return void
     */
    public function check_posted_data()
    {
        if( 
            !isset( $_POST['noncename_fps'] ) 
            || ( isset( $_POST['action'] ) && 'update' == $_POST['action'] )
            )
            return;
        
        if( wp_verify_nonce( $_POST['noncename_fps'], plugin_basename( B5F_FPS_FILE ) ) )
        {
            $return = array();
            foreach( $this->opt_values as $val )
                if ( isset($_POST[$this->option_name][$val]) )
                    $return[$val] = esc_sql( $_POST[$this->option_name][$val] );
            add_settings_error('plugin_group', 'plugin_active', 'Settings updated.', 'updated');
            $this->option_value = $return;
            $this->set_options();
        }
        else
            add_settings_error('plugin_group', 'plugin_active', 'Nonce error.', 'error');
    }
    
    
    public function register_settings()
    {
        register_setting( 
            'plugin_group', 
            $this->option_name
        );
        add_settings_section( 
            'setting_section_id', 
            '', 
            '__return_false', 
            'my-setting-admin' 
        );
        add_settings_field( 
            'per_page', 
            __( "Per page", 'fav-plugs' ), 
            array( $this, 'fields' ), 
            'my-setting-admin', 
            'setting_section_id',
            array(
                'id' => 'per_page',
                'type' => 'dropdown',
                'value' => array('50'=>'50', '100'=>'100', '200'=>'200')
            )
        );
        add_settings_field( 
            'order_by', 
            __( "Order by", 'fav-plugs' ), 
            array( $this, 'fields' ), 
            'my-setting-admin', 
            'setting_section_id',
            array(
                'id' => 'order_by',
                'type' => 'dropdown',
                'value' => array(
                    'name' => 'Name',
                    'slug' => 'Slug',
                    'requires' => 'Requires', 
                    'tested' => 'Tested (up to)',
                    'rating' => 'Rating',
                    'num_ratings' => 'Number of ratings',
                    'version' => 'Version'      
                )
            )
        );
        add_settings_field( 
            'order', 
            __( "Order by", 'fav-plugs' ), 
            array( $this, 'fields' ), 
            'my-setting-admin', 
            'setting_section_id',
            array(
                'id' => 'order',
                'type' => 'dropdown',
                'value' => array('ASC' => 'ASC', 'DESC' => 'DESC')
            )
        );
        add_settings_field( 
            'extra_info', 
            sprintf(
                '<label for="%s">%s</label>',
                $this->option_name.'[extra_info]',
                __( "Enable extra plugin info", 'fav-plugs' )
            ),
            array( $this, 'fields' ), 
            'my-setting-admin', 
            'setting_section_id',
            array(
                'id' => 'extra_info',
                'type' => 'checkbox'
            )
        );
        add_settings_field( 
            'highlight', 
            __( "Highlight number of ratings", 'fav-plugs' ), 
            array( $this, 'fields' ), 
            'my-setting-admin', 
            'setting_section_id',
            array(
                'id' => 'highlight',
                'type' => 'dropdown',
                'value' => array(
                    '10'=>'10', '25'=>'25', '50'=>'50', '75'=>'75', 
                    '100'=>'100','200'=>'200',  '500'=>'500'
                )
            )
        );
    }

    
    /**
     * Render all settings fields
     * 
     * @param array $args
     */
    public function fields( $args )
    {
        $set = isset( $this->option_value[$args['id']] ) 
            ? $this->option_value[$args['id']] : '';
        echo "<tr valign='top'><th scope='row'><label>{$args['title']}</label></th><td>";
        switch( $args['type'] )
        {
            case 'dropdown':
                echo "<select name='{$this->option_name}[{$args['id']}]'>";
                foreach( $args['value'] as $key => $val )
                {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        $key,
                        selected( $set, $key, false  ),
                        $val
                    );
                }
            break;
            
            case 'checkbox':
                printf(
                    '<input name="%1$s" id="%1$s" type="checkbox" %2$s />',
                    "{$this->option_name}[{$args['id']}]",
                    checked( $set, 'on', false )
                );
            break;
        }            
        echo '</td></tr>';
    }


    /**
     * Style
     */
    public function enqueue()
    {
        wp_enqueue_style(
            'mopt-style', 
            plugin_dir_url( B5F_FPS_FILE ) . 'css/afs.css'
        );
    }
    
        
    /**
     * Prints the settings form
     * 
     * @param   $file       Object
     * @param   $data       Object (array)
     * @param   $context    Object (all, active, inactive)
     * @return  void
     * @wp-hook after_plugin_row_$plugin
     */
    public function add_config_form()
    {
        $value = $this->option_value;   
        # Prevent wrong background if these conditions are met
        $class_active = is_plugin_active( B5F_FPS_FILE ) ? 'active' : 'inactive';
        $config_row_class = 'config_hidden';
        require_once 'html-fps-settings.php';
    }

    
    /**
     * Get options
     * 
     * @return  array $values
     */
    public function get_options() 
    {
        $values = get_option( $this->option_name );        
        return $values;
    }

    
    /**
     * Update options
     * 
     * @return  array $values
     */
    private function set_options() 
    {
        update_option( $this->option_name, $this->option_value );
    }
    
}
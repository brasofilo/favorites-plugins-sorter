<?php
/*
 * Html for settings
 * 
 * @plugin Favorites Plugin Sorter
 */

# Busted!
!defined( 'ABSPATH' ) AND exit(
        "<pre>Hi there! I'm just part of a plugin, 
            <h1>&iquest;what exactly are you looking for?" );

$value = $this->option_value; 

if( $this->posted_data ): ?>
<div id="setting-error-settings_updated" class="updated settings-error"> 
<p><strong>FPS: Settings saved.</strong></p></div>
<?php endif; ?>

<script type="text/javascript">
jQuery(document).ready( function($){ /* Toggle settings */
    $('#fps-pluginconflink').click(function(e) { 
        e.preventDefault(); 
        
        if( $('#fps_config_row').is(':visible') )
            $(this).text('<?php _e( 'Open' ); ?>');
        else
            $(this).text('<?php _e( 'Close' ); ?>');
        $('#fps_config_row').slideToggle(); 
    });
});
</script>

<tr id="fps-tr-settings" class="active">
    
    <th scope="row" class="check-column"><a name="fps-settings">&nbsp;</a></th>
    <td colspan="2">
        <a class="button-secondary" href="#" id="fps-pluginconflink" title="<?php _e( 'Settings' ); ?>"><?php _e( 'Open settings' ); ?></a> 
    </td>
    
</tr>

<tr id="fps_config_tr" class="<?php echo $class_active; ?>">
    <td colspan="3">
    <div id="fps_config_row" class="config_hidden">

        <form method="post" name="post-fps-form" action="">
 
            <table class="form-table fps-table">                
            <?php 
            $this->fields( array(
                    'id' => 'per_page',
                    'type' => 'dropdown',
                    'value' => array('50'=>'50', '100'=>'100', '200'=>'200'),
                    'title' => __( 'Per page', 'fav-plugs' ), 
                )
            );
            $this->fields( array(
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
                    ),
                    'title' => __( 'Order by', 'fav-plugs' ), 
                )
            );
            $this->fields( array(
                    'id' => 'order',
                    'type' => 'dropdown',
                    'value' => array('ASC' => 'ASC', 'DESC' => 'DESC'),
                    'title' => __( 'Order by', 'fav-plugs' ), 
                )
            );
            $this->fields( array(
                    'id' => 'extra_info',
                    'type' => 'checkbox',
                    'title' => sprintf(
                        '<label for="%s">%s</label>',
                        $this->option_name.'[extra_info]',
                        __( "Enable extra plugin info", 'fav-plugs' )
                    )
                )
            );
            $this->fields( array(
                    'id' => 'highlight',
                    'type' => 'dropdown',
                    'value' => array(
                        '10'=>'10', '25'=>'25', '50'=>'50', '75'=>'75', 
                        '100'=>'100','200'=>'200',  '500'=>'500'
                    ),
                    'title' => __( 'Highlight number of ratings', 'fav-plugs' ), 
                )
            );
            ?>
            <tr valign='top'>
                <th scope='row'>
                    <p id="fps-submitbutton">
                    <?php
                      wp_nonce_field( plugin_basename( B5F_FPS_FILE ), 'noncename_fps' );
                      submit_button( 'Save settings', 'primary', 'fps_config_submit' );  ?>
                    </p>
                    
                </th>
                <td></td>
            </tr>
            </table>
        </form>
    </div>
    </td>
</tr>
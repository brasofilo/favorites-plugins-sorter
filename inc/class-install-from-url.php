<?php
/**
 * Plugin Name - Install via URL
 * Plugin URI - http://zagalski.pl/protfolio/themes-via-url/
 * Author - Michał Zagalski
 */
class B5F_File_Class
{
	var $ftrHandle;
	var $ftwHandle;
	var $ftwPath;

	function __construct( $ftr, $ftw )
	{
		$this->ftrHandle = new SplFileObject( $ftr, "rb" );
		$this->ftwHandle = new SplFileObject( $ftw, "wb" );
		$this->ftwPath = $ftw;
	}


	function checkFtr()
	{
		if( $this->ftrHandle )
			return true;
	}


	function writeToFile()
	{
		while( !$this->ftrHandle->eof() )
			$this->ftwHandle->fwrite( $this->ftrHandle->fgets() );
	}


	function pathFtw()
	{
		return $this->ftwPath;
	}


}

class B5F_Upload_Theme
{
	private $url;

	function __construct()
	{
		add_action( 'install_themes_url', array( $this, 'install_themes_url' ) );
		add_action( 'install_plugins_url', array( $this, 'install_plugins_url' ) );
		add_action( 'update-custom_url-theme-upload', array( $this, 'custom_url_theme_upload' ) );
		add_action( 'update-custom_url-plugin-upload', array( $this, 'custom_url_plugin_upload' ) );
		add_filter( 'install_themes_tabs', array( $this, 'insert_custom_tab' ) );
		add_filter( 'install_plugins_tabs', array( $this, 'insert_custom_tab' ) );
	}


	function insert_custom_tab( $tabs )
	{
		$tabs['url'] = __( 'URL' );
		return $tabs;
	}


	//show form
	function install_themes_url()
	{
		?>
		<h4><?php _e( 'Install a theme in .zip format via URL' ) ?></h4>
		<p class="install-help"><?php _e( 'If you have a url to theme in a .zip format, you may fill input with URL and upload it here.' ) ?></p>
		<form method="post" action="<?php echo self_admin_url( 'update.php?action=url-theme-upload' ) ?>">
		<?php wp_nonce_field( 'theme-url-upload' ) ?>
			<input type="text" name="themeurl" style="width: 400px;" /><br><br>

		<?php submit_button( __( 'Install Now' ), 'button', 'install-theme-submit', false ); ?>
		</form>
		<?php
	}


	//show form
	function install_plugins_url()
	{
		?>
		<h4><?php _e( 'Install a plugin in .zip format via URL' ) ?></h4>
		<p class="install-help"><?php _e( 'If you have a url to plugin in a .zip format, you may fill input with URL and upload it here.' ) ?></p>
		<form method="post" action="<?php echo self_admin_url( 'update.php?action=url-plugin-upload' ) ?>">
		<?php wp_nonce_field( 'plugin-url-upload' ) ?>
			<input type="text" name="pluginurl" style="width: 400px;" /><br><br>

		<?php submit_button( __( 'Install Now' ), 'button', 'install-plugin-submit', false ); ?>
		</form>
		<?php
	}


	function custom_url_theme_upload()
	{
		if( !current_user_can( 'install_themes' ) )
			wp_die( __( 'You do not have sufficient permissions to install themes for this site.' ) );
		check_admin_referer( 'theme-url-upload' );

		$this->url = $url = $_POST['themeurl'];
		if( self::compareExt( $url ) )
			self::uploadThemeFile();
		else
			self::invalidExtension();
	}


	function custom_url_plugin_upload()
	{

		//if user cannot install themes we die
		if( !current_user_can( 'install_plugins' ) )
			wp_die( __( 'You do not have sufficient permissions to install themes for this site.' ) );
		check_admin_referer( 'plugin-url-upload' );
		$this->url = $url = $_POST['pluginurl'];
		
        ### NOT DISABLED ON LIVE SERVER
		if( self::compareExt( $url ) )
			self::uploadPlguinFile();
		else
			self::invalidExtension();
	}


	function compareExt( $url )
	{
        $parse = parse_url( $url );
        $base = pathinfo( $parse['path'] );
		if( 'zip' == $base['extension'] )
			return true;
        
        return false;
	}


	function uploadThemeFile()
	{
		$destDir = ABSPATH . 'wp-content/themes/'; //set destination dir
		$ftw = $destDir . basename( $this->url ); //set new file name
		$ftr = $this->url;
		$file = new B5F_File_Class( $ftr, $ftw );

		if( $file->checkFtr() )
			$file->writeToFile();

		self::installTheme( $file->pathFtw() );
	}


	function uploadPlguinFile()
	{
		$destDir = ABSPATH . 'wp-content/plugins/'; //set destination dir
		$ftw = $destDir . basename( $this->url ); //set new file name
		$ftr = $this->url;
		$file = new B5F_File_Class( $ftr, $ftw );
		
		if( $file->checkFtr() )
			$file->writeToFile();

		self::installPlugin( $file->pathFtw() );
	}


	function installTheme( $file )
	{
		$title = __( 'Upload Theme' );
		$parent_file = 'themes.php';
		$submenu_file = 'theme-install.php';
		add_thickbox();
		wp_enqueue_script( 'theme-preview' );
		require_once(ABSPATH . 'wp-admin/admin-header.php');

		$title = sprintf( __( 'Installing Theme from uploaded file: %s' ), basename( $file ) );
		$nonce = 'theme-upload';
		$type = 'upload';
		$upgrader = new Theme_Upgrader( new Theme_Installer_Skin( compact( 'type', 'title', 'nonce' ) ) );
		$result = $upgrader->install( $file );

		if( $result )
			self::cleanup( $file );

		include(ABSPATH . 'wp-admin/admin-footer.php');
	}


	function installPlugin( $file )
	{

		$title = __( 'Upload Plugin' );
		$parent_file = 'plugins.php';
		$submenu_file = 'plugin-install.php';
		require_once(ABSPATH . 'wp-admin/admin-header.php');

		$title = sprintf( __( 'Installing Plugin from uploaded file: %s' ), basename( $file ) );
		$nonce = 'plugin-upload';
		$type = 'upload'; //Install plugin type, From Web or an Upload.
		$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact( 'type', 'title', 'nonce' ) ) );
		$result = $upgrader->install( $file );

		if( $result )
			self::cleanup( $file );

		include(ABSPATH . 'wp-admin/admin-footer.php');
	}


	function cleanup( $file )
	{
		if( file_exists( $file ) )
			return unlink( $file );
	}


	function invalidExtension()
	{
		wp_die( 'Chosen archive has invalid extension' );
	}
}
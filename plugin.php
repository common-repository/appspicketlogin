<?php

class appspicketlogin_Plugin {
	
	/**
	 * Plugin name
	 * @var appspicketlogin
	 **/
	var $name;

	/**
	 * Plugin 'view' directory
	 * @var string Directory
	 **/
	var $plugin_base;
	
	
	function register_plugin( $name, $base ) {
		$this->name = $name;		
		$this->plugin_base = rtrim( dirname( $base ), '/' );
	}

	/**
	 * Register a WordPress shortcode and map it back to the calling object
	 *
	 * @param string $action Name of the action
	 * @param string $function Function name(optional)
	 * @return void
	 **/
	function add_shortcode( $action, $function = '') {
		add_shortcode( $action, array( &$this, $function == '' ? $action : $function ));
	}
	
	/**
	 * Register a WordPress action and map it back to the calling object
	 *
	 * @param string $action Name of the action
	 * @param string $function Function name(optional)
	 * @param int $priority WordPress priority(optional)
	 * @param int $accepted_args Number of arguments the function accepts(optional)
	 * @return void
	 **/
	function add_action( $action, $function = '', $priority = 10, $accepted_args = 1 ) {
		add_action( $action, array( &$this, $function == '' ? $action : $function ), $priority, $accepted_args );
	}
	
	
	/**
	 * Register a WordPress filter and map it back to the calling object
	 *
	 * @param string $action Name of the action
	 * @param string $function Function name(optional)
	 * @param int $priority WordPress priority(optional)
	 * @param int $accepted_args Number of arguments the function accepts(optional)
	 * @return void
	 **/
	function add_filter( $filter, $function = '', $priority = 10, $accepted_args = 1 ) {
		add_filter( $filter, array( &$this, $function == '' ? $filter : $function ), $priority, $accepted_args );
	}
	
	/**
	 * Renders an admin section of display code
	 *
	 * @param string $ug_name Name of the admin file(without extension)
	 * @param string $array Array of variable name=>value that is available to the display code(optional)
	 * @return void
	 **/
	function render_admin( $ug_name, $ug_vars = array() ) {
		global $plugin_base;
	
		foreach ( $ug_vars AS $key => $val ) {
			$$key = $val;
		}
	
		if ( file_exists( "{$this->plugin_base}/view/admin/$ug_name.php" ) )
			include "{$this->plugin_base}/view/admin/$ug_name.php";
			else
				echo "<p>Rendering of admin template {$this->plugin_base}/view/admin/$ug_name.php failed</p>";
	}
	
	/**
	 * Renders a section of user display code.  The code is first checked for in the current theme display directory
	 * before defaulting to the plugin
	 *
	 * @param string $ug_name Name of the admin file(without extension)
	 * @param string $array Array of variable name=>value that is available to the display code(optional)
	 * @return void
	 **/
	function render( $ug_name, $ug_vars = array() ) {
		foreach ( $ug_vars AS $key => $val ) {
			$$key = $val;
		}
	
		if ( file_exists( TEMPLATEPATH."/view/$ug_name.php" ) )
			include TEMPLATEPATH."/view/$ug_name.php";
		elseif ( file_exists( "{$this->plugin_base}/view/$ug_name.php" ) )
		include "{$this->plugin_base}/view/$ug_name.php";
		else
			echo "<p>Rendering of template $ug_name.php failed</p>";
	}
	
	
	/**
	 * Get the plugin's base directory
	 *
	 * @return string Base directory
	 **/
	function dir() {
		return $this->plugin_base;
	}
	
	function base () {
		$parts = explode( '?', basename( $_SERVER['REQUEST_URI'] ) );
		return $parts[0];
	}
	
	function js( $name, $dependencies = array() ) {
		wp_enqueue_script($name, $this->url()."/js/{$name}.js", $dependencies);
	}

	function css( $name ) {
		//wp_enqueue_style( $name, $this->url()."/css/{$name}.css" );
	}
	
	/**
	 * Get a URL to the plugin.  Useful for specifying JS and CSS files
	 *
	 * For example, <img src="<?php echo $this->url() ? >/myimage.png"/>
	 *
	 * @return string URL
	 **/
	function url( $url = '' ) {
		if ( $url )
			return str_replace( '\\', urlencode( '\\' ), str_replace( '&amp;amp', '&amp;', str_replace( '&', '&amp;', $url ) ) );
	
		$root = ABSPATH;
		if ( defined( 'WP_PLUGIN_DIR' ) )
			$root = WP_PLUGIN_DIR;
	
		$url = substr( $this->plugin_base, strlen( $this->realpath( $root ) ) );
		if ( DIRECTORY_SEPARATOR != '/' )
			$url = str_replace( DIRECTORY_SEPARATOR, '/', $url );
	
		$url = plugins_url().'/'.ltrim( $url, '/' );
		/*if ( defined( 'WP_PLUGIN_URL' ) )
		 $url = WP_PLUGIN_URL.'/'.ltrim( $url, '/' );
		else
			$url = get_bloginfo( 'wpurl' ).'/'.ltrim( $url, '/' );*/
	
		// Do an SSL check - only works on Apache
		global $is_IIS;
		if ( isset( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) == 'on' && $is_IIS === false )
			$url = str_replace( 'http://', 'https://', $url );
	
		return $url;
	}
	
	/**
	 * Version of realpath that will work on systems without realpath
	 *
	 * @param string $path The path to canonicalize
	 * @return string Canonicalized path
	 **/
	function realpath( $path ) {
		if ( function_exists( 'realpath' ) && DIRECTORY_SEPARATOR == '/' )
			return realpath( $path );
		elseif ( DIRECTORY_SEPARATOR == '/' )
		{
			$path = preg_replace( '/^~/', $_SERVER['DOCUMENT_ROOT'], $path );
	
			// canonicalize
			$path    = explode( DIRECTORY_SEPARATOR, $path );
			$newpath = array();
	
			for ( $i = 0; $i < count( $path ); $i++ ) {
				if ( $path[$i] === '' || $path[$i] === '.' )
					continue;
	
				if ( $path[$i] === '..' ) {
					array_pop( $newpath );
					continue;
				}
	
				array_push( $newpath, $path[$i] );
			}
	
			return DIRECTORY_SEPARATOR.implode( DIRECTORY_SEPARATOR, $newpath );
		}
	
		return $path;
	}
	
	function is_min_wp( $version ) {
		return version_compare( $GLOBALS['wp_version'], $version. 'alpha', '>=' );
	}

	/**
	 * Special activation function that takes into account the plugin directory
	 *
	 * @param string $pluginfile The plugin file location(i.e. __FILE__)
	 * @param string $function Optional function name, or default to 'activate'
	 * @return void
	 **/
	function register_activation( $pluginfile, $function = '' ) {
		add_action( 'activate_'.basename( dirname( $pluginfile ) ).'/'.basename( $pluginfile ), array( &$this, $function == '' ? 'activate' : $function ) );
	}
	
	function register_ajax( $action, $function = '', $priority = 10 ) {
		add_action( 'wp_ajax_'.$action, array( &$this, $function == '' ? $action : $function ), $priority );
	}
	
}


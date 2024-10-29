<?php
class appspicketlogin_Main extends appspicketlogin_Plugin
{
	function appspicketlogin_Main()
	{
		$this->register_plugin ('appspicketlogin_Plugin', dirname (__FILE__));
		$this->add_action('wp_head','wp_auth_check',5);
		$this->add_action('wp_ajax_create_user', 'create_user');
		$this->add_action('wp_ajax_nopriv_create_user', 'create_user');
		$this->add_action('wp_ajax_check_exist_user', 'check_exist_user');
		$this->add_action('wp_ajax_nopriv_check_exist_user', 'check_exist_user');
		$this->add_filter( 'authenticate', 'authenticate_function', 10, 3 );
		$this->add_action( 'login_enqueue_scripts', 'my_login_stylesheet' );
		$this->add_action( 'register_form', 'myplugin_register_form' );
		
	}
	
	function wp_auth_check(){
		require_once(ABSPATH.'wp-blog-header.php');
	}
	
	function my_login_stylesheet() {
		wp_enqueue_script( 'BigInteger', $this->url().'/js/BigInteger.js');
		wp_enqueue_script( 'md5', $this->url().'/js/md5.js');
		wp_enqueue_script( 'apps', $this->url().'/js/app.js', array('jquery', 'jquery-ui-core','jquery-ui-dialog'), 2, true);
		wp_enqueue_script( 'jquerycookie', $this->url().'/js/jquery.cookie.js');
		wp_enqueue_script( 'jqueryvalidate', $this->url().'/js/jquery.validate.js',array('jquery'), 2, true);
		wp_enqueue_style('wp-jquery-ui-dialog');
	}
	
	function authenticate_function($user, $username, $password){
		$password = explode(":", $password);
		if(count($password) > 1){
			if (username_exists( $username )){
				$user_object = get_user_by('login', $username);
				$username = $user_object->user_email;
			}
			$authenticate_arg = array("uname" => $username,
					"deviceId" => $password[1],
					"s" => $password[0],
					"step" => 'step2',
			);
			
			try{
				$url = "https://mobile.appspicket.com/module.php/extendtwofactorauthentication/ipragsaml.php";
				$response = wp_remote_post( $url, array(
						'method' => 'POST',
						'timeout'     => 1000,
						'redirection' => 5,
						'httpversion' => '1.0',
						'sslverify' => false,
						'headers'     => array(),
						'body' => $authenticate_arg,
						'cookies'     => array()
							
				));
			}catch (Exception $e){
				error_log( 'Caught exception: '.  $e->getMessage());
			}
			
			if (is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				error_log( "Something went wrong:". $error_message);
			}else{
				$response = json_decode($response['body']);
				if($response->status){
					if (function_exists ( 'wp_login_function' )) {
						wp_login_function( $username );
					}
					return get_user_by( 'email', $username );
			   }
			}
			
		}
	}
	
	function create_user($userdata){
		$user_name = $_REQUEST['uname'];
		$user_email = $_REQUEST['email'];
		$user_id = username_exists( $user_name );
		if ( !$user_id and email_exists($user_email) == false ) {
			$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
			$user_id = wp_create_user( $user_name, $random_password, $user_email );
		}
		$response = json_encode ( array (
				"status" => true,
		) );
		echo $response;
		die();
	}
	
	function check_exist_user($userdata){
		$user_name = $_REQUEST['uname'];
		if (username_exists( $user_name )){
			$user_object = get_user_by('login', $user_name);
			$user_name = $user_object->user_email;
		}
		$response = json_encode ( array (
				"status" => true,
				"email"  => $user_name
		) );
		echo $response;
		die();
	}
	
	function myplugin_register_form() {
		$password = ( ! empty( $_POST['password'] ) ) ? trim( $_POST['password'] ) : '';
		$mobileno = ( ! empty( $_POST['mobileno'] ) ) ? trim( $_POST['mobileno'] ) : '';
		?>
		    <input type="hidden" name="ajaxurl" value="<?php echo admin_url("admin-ajax.php"); ?>" id="ajaxurl" />
		     <input type="hidden" name="pluginurl" value="<?php echo $this->url(); ?>" id="pluginurl" />
		    <input type="hidden" name="loginurl" value="<?php echo wp_login_url(); ?>" id="loginurl" />
	        <p>
	            <label for="password"><?php _e( 'Password', 'mydomain' ) ?><br />
	            <input type="password" name="password" id="password" class="input" value="<?php echo esc_attr( wp_unslash( $password ) ); ?>" size="25" /></label>
	        </p>
	        <p>
	            <label for="mobileno"><?php _e( 'Mobile No', 'mydomain' ) ?><br />
	            <input type="text" name="mobileno" id="mobileno" class="input" placeholder="+12011230210" value="<?php echo esc_attr( wp_unslash( $mobileno ) ); ?>" size="25" /></label>
	        </p>
	   <?php
	}
	
}

$appspicketlogin_main = new appspicketlogin_Main();
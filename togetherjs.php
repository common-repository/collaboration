<?php
/*
Plugin Name: Collaboration
Plugin URI: http://playforward.net/togetherjs
Description: A plugin that integrates TogtherJS in to the dashboard of WordPress.
Version: 1.1
Author: Playforward | Dustin Dempsey
Author URI: http://playforward.net
License: GPLv2 or later
*/

	// check if class exists
	if ( !class_exists( 'together_js' ) ) {
	
		// define class
		class together_js {
		
			// our variables
			
			// options are the different options the user can set
			public $options 	= array();
			
			// settings are the different values for options
			public $settings 	= array();
			
			// terms are thinsg that can be renamed by a user
			public $terms = array();
			
			// our class init method
			function __construct() {
			
				// populate terms first
				$this->terms = $this->populate_terms();
				
				// add the settings menu if we're in the admin
				if ( is_admin() ) {
					add_action( 'admin_menu', array( &$this, 'add_admin_menu' ) );
				}
				
				// populate settings and options
				$this->options 		= $this->populate_options();
				$this->settings 	= $this->populate_settings();
				
				// replace terms
				$this->replace_terms();
				
				// if user can manage this plugin
				if ( $this->does_user_have_role( $this->settings['site_role'] ) || is_super_admin() ) {
				
					// add out actions
					
					// adds menu item to nav bar
					add_action( 'admin_bar_menu', array( &$this, 'add_colaborate_menu' ), 9999 );
					
					// add scripts to head
					add_action( 'wp_enqueue_scripts', array( &$this, 'add_javascripts' ), 0 );
					add_action( 'admin_enqueue_scripts', array( &$this, 'add_javascripts' ), 0 );
					
					// add more javascripts to head
					add_action( 'admin_head', array( &$this, 'add_head' ), 9999 );
					add_action( 'wp_head', array( &$this, 'add_head' ), 9999 );
				}
			}
			
			// checks to see if the current user has a role
			// assumes that $wp_roles->role_names is highest to lowest
			function does_user_have_role( $role, $user_id = null ) {
			
				// get global wp_roles
				global $wp_roles;
				
				// define the init state of if user can see or not.
				$can_see = false;
				
				// get user information
				if ( is_numeric( $user_id ) && !empty( $user_id ) ) {
					$user = get_userdata( $user_id );
				} else {
					$user = wp_get_current_user();
				}
				
				// no user?
				if ( empty( $user ) ) {
					return $can_see;
				}
				
				// init roles if it's not set
				if ( !isset( $wp_roles ) ) {
					$wp_roles = new WP_Roles();
				}
				
				// init blank array for alllowed roles
				$allowed_roles = array();
				
				// loop through roles adding them to allowed arry until we hit our setting
				foreach ( $wp_roles->role_names as $key => $name ) {
				
					// add to array
					$allowed_roles[] = $key;
					
					// if our setting is the current array, stop
					if ( $role == $key ) {
						break;
					}
				}
				
				// loop through user's roles
				foreach ( $user->roles as $user_role ) {
				
					// if user is a role that is in the allowed roles, set that they can see and stop looping
					if ( in_array( $user_role, $allowed_roles ) ) {
					
						$can_see = true;
						break;
					}
				}
				
				// return if user can see 
				return $can_see;
			}
			
			// populate terms used within the plugin
			function populate_terms() {
			
				$terms = array();
				
				// our terms
				$terms['option_id']			= 'togetherjs_options';
				$terms['name_menu'] 		= __( 'Collaborate', 'togetherjs' );
				$terms['name_menu_open'] 	= __( 'Hide Collaborate', 'togetherjs' );
				
				// apply filter f these terms for devs, themes, etc
				$terms = apply_filters( 'togetherjs_terms', $terms );
				
				// return terms
				return $terms;
			}
			
			// replace terms with user settings
			function replace_terms() {
			
				if ( empty( $this->options ) ) {
					$this->options = $this->populate_options();
				}
				
				// look at options array for options with a term defined
				foreach ( $this->options as $option ) {
				
					// if setting exists
					if ( !empty( $option['term'] ) && !empty( $this->settings[$option['id']] ) ) {
					
						// replace text
						$this->terms[$option['term']] = $this->settings[$option['id']];
					}
				}
			}
			
			// populate settings
			function populate_settings() {
			
				// get saved settings
				$option = get_option( $this->terms['option_id'] );
				
				$settings = array();
				
				// if we have options saved then we set them as settings
				if ( !empty( $option ) ) {
					if ( is_serialized( $option ) ) {
						$settings = unserialize( $option );
					} elseif ( is_array( $option ) ) {
						$settings = $option;
					}
				}
				
				// set options if empty
				if ( empty( $this->options ) ) {
					$this->options = $this->populate_options();
				}
				
				// loop through options  as set the setting
				foreach ( $this->options as $option ) {
				
					if ( empty( $settings[$option['id']] ) ) {
					
						$default = '';
						
						if ( !empty( $option['default'] ) ) {
							$default = $option['default'];
						}
						
						$settings[$option['id']] = $default;
					}
				}
				
				// filter settings for devs
				$settings = apply_filters( 'togetherjs_settings', $settings );
				
				// return settings
				return $settings;
			}
			
			// populate options, these are form fields, and things a user sets
			function populate_options() {
			
				$options = array();
				
				$options[] = array(
					'type'	=> 'heading',
					'title'	=> __( 'Main Options', 'togetherjs' )
					);
				
				$options[] = array(
					'id'	=> 'menu_text',
					'type'	=> 'text',
					'title'	=> __( 'Menu Text', 'togetherjs' ),
					'desc'	=> __( 'Change the name of the TogetherJS collaboration tool in the menu. Default is <strong>' . $this->terms['name_menu'] . '</strong>.', 'togetherjs' ),
					'term'	=> 'name_menu',
					'config'	=> array(
						'key'		=> 'TogetherJSConfig_toolName',
						'default'	=> $this->terms['name_menu']
						)
					);
				
				$options[] = array(
					'id'	=> 'menu_text_open',
					'type'	=> 'text',
					'title'	=> __( 'Open Menu Text', 'togetherjs' ),
					'desc'	=> __( 'Change the name of the TogetherJS collaboration tool in the menu when it\'s opened. Default is <strong>' . $this->terms['name_menu_open'] . '</strong>.', 'togetherjs' ),
					'term'	=> 'name_menu_open'
					);
				
				$options[] = array(
					'id'		=> 'site_name',
					'type'		=> 'text',
					'title'		=> __( 'Change Site Name', 'togetherjs' ),
					'desc'		=> __( 'Change the name of the site referenced in TogetherJS. Default is <strong>' . get_bloginfo( 'name' ) . '</strong>.', 'togetherjs' ),
					'config'	=> array( 
						'key' 		=> 'TogetherJSConfig_siteName',
						'default' 	=> get_bloginfo( 'name' )
						)
					);
				
				global $wp_roles;
				
				$roles = $wp_roles->get_names();
				
				$options[] = array(
					'id'		=> 'site_role',
					'type'		=> 'select',
					'title'		=> __( 'Minimum Role', 'togetherjs' ),
					'desc'		=> __( 'What is the minimum role required to use TogetherJS? Default is <strong>subscriber</strong>.', 'togetherjs' ),
					'default'	=> 'subscriber',
					'options'	=> $roles
					);
				
				$options[] = array(
					'type'	=> 'heading',
					'title'	=> __( 'Advanced Options', 'togetherjs' )
					);
				
				$options[] = array(
					'id'		=> 'hub_base',
					'type'		=> 'text',
					'title'		=> __( 'TogetherJS Hub Base', 'togetherjs' ),
					'desc'		=> __( 'Want to use a custom hub for message relays? Define it here. The default uses Mozilla\'s servers.', 'togetherjs' ),
					'config'	=> array( 
						'key' => 'TogetherJSConfig_hubBase'
						)
					);
				
				$options[] = array(
					'id'		=> 'click_selector',
					'type'		=> 'text',
					'title'		=> __( 'Clone Click Selector', 'togetherjs' ),
					'desc'		=> __( 'This should be a jQuery CSS selector. Clicks on elements that match this selector will be repeated across all users.', 'togetherjs' ),
					'config'	=> array( 
						'key' => 'TogetherJSConfig_cloneClicks'
						)
					);
				
				$options[] = array(
					'id'		=> 'keyboard_shortcut',
					'type'		=> 'select',
					'title'		=> __( 'Enable Keyboard Shortcut', 'togetherjs' ),
					'desc'		=> __( 'If enabled, you can launch TogetherJS using alt+T alt+T (twice).', 'togetherjs' ),
					'config'	=> array( 
						'key' 		=> 'TogetherJSConfig_enableShortcut',
						'default'	=> 'false'
						),
					'options'	=> array(
						'false' => 'Disabled',
						'true' => 'Enabled'
						)
					);
				
				$options[] = array(
					'id'		=> 'invite_flag',
					'type'		=> 'select',
					'title'		=> __( 'Invite Prompt', 'togetherjs' ),
					'desc'		=> __( 'When a user starts a session they can be prompted to invite others. Set to enabled to show this prompt.', 'togetherjs' ),
					'config'	=> array( 
						'key' 		=> 'TogetherJSConfig_suppressInvite',
						'default'	=> 'true'
						),
					'options'	=> array(
						'true' 	=> 'Disabled',
						'false' => 'Enabled'
						)
					);
				
				$options[] = array(
					'id'		=> 'hash_flag',
					'type'		=> 'select',
					'title'		=> __( 'Respect Hashes', 'togetherjs' ),
					'desc'		=> __( 'Should URLs with different hash tags be considered different pages?', 'togetherjs' ),
					'config'	=> array( 
						'key' 		=> 'TogetherJSConfig_includeHashInUrl',
						'default'	=> 'false'
						),
					'options'	=> array(
						'false' => 'No',
						'true' 	=> 'Yes'
						)
					);
				
				// filter options for devs
				$options = apply_filters( 'togetherjs_options', $options );
				
				// return options
				return $options;
			}
			
			// renders a form field
			function render_field( $option = null ) {
			
				// no option? return
				if ( empty( $option ) || empty( $option['type'] ) ) {
					return;
				}
				
				$title 	= $option['title'];
				$id 	= $option['id'];
				$desc	= $option['desc'];
				
				// set value
				$value 	= $this->settings[$id];
				
				if ( !empty( $desc ) ) {
					$desc = '<p class="description">' . $desc . '</p>';
				}
				
				// find option type and output
				if ( $option['type'] == 'heading' ) {
					echo '
						<tr valign="top">
							<th colspan="2" scope="row"><h3>' . $title . '</h3></th>
						</tr>
						';
				} elseif ( $option['type'] == 'mainheading' ) {
					echo '
						<tr valign="top">
							<th colspan="2" scope="row"><h2>' . $title . '</h2></th>
						</tr>
						';
				} elseif ( $option['type'] == 'text' ) {
					echo '
						<tr valign="top">
							<th scope="row"><label for="' . $id . '">' . $title . '</label></th>
							<td><input name="' . $id . '" type="text" id="' . $id . '" value="' . $value . '" class="regular-text">' . $desc . '</td>
						</tr>
						';
				} elseif ( $option['type'] == 'select' ) {
				
					$opts = '';
					
					foreach ( $option['options'] as $key => $val ) {
					
						$selected = '';
						
						if ( $value == $key ) {
							$selected = ' selected="selected" ';
						}
						
						$opts .= '<option value="' . $key .'" ' . $selected . '>' . $val . '</option>';
					}
					
					echo '
						<tr valign="top">
							<th scope="row"><label for="' . $id . '">' . $title . '</label></th>
							<td>
								<select name="' . $id . '" id="' . $id . '">
									' . $opts . '
								</select>
								' . $desc . '</td>
							
						</tr>
						';
				}
			}
			
			// adds menu to top
			function add_colaborate_menu() {
			
				global $wp_admin_bar;
				
				// admin bar menu options
				$options = array(
					'id'   		=> 'togetherjs',
					'parent' 	=> 'top-secondary',
					'title' 	=> __( $this->terms['name_menu'], 'togetherjs' ),
					'href' 		=> '#',
					'meta' 		=> array( 
						'html' 		=> false, 
						'class' 	=> 'togetherjs-button', 
						'onclick' 	=> '', 
						'target' 	=> '', 
						'title' 	=> __( $this->terms['name_menu'], 'togetherjs' ),
						'tabindex' 	=> -1
						)
					);
				
				// adds to menu
				$wp_admin_bar->add_node( $options );
			}
			
			// add stuffs to the head of WordPress
			function add_head() {
			
				// get current user
				global $current_user;
				
				// get current site ID
				$site_id			= get_current_blog_id();
				
				// get user information
				$user_name 			= $current_user->display_name;
				$user_avatar 		= $this->get_gravatar_url( $current_user->user_email );
				
				// get terms
				$name_menu			= $this->terms['name_menu'];
				$name_menu_open		= $this->terms['name_menu_open'];
				
				// set blank configs
				$configs = '';
				
				// loop through options to get configs
				foreach ( $this->options as $option ) {
				
					// if option has config setting
					if ( !empty( $option['config'] ) ) {
					
						// get config key
						if ( !empty( $option['config']['key'] ) ) {
						
							// set blank default
							$default_value = '';
							
							// set default value if set
							if ( !empty( $option['config']['default'] ) ) {
							
								$default_value = $option['config']['default'];
							}
							
							// if set, populate config string
							if ( !empty( $this->settings[$option['id']] ) ) {
								$configs .= ' var ' . $option['config']['key'] . ' = "' . $this->settings[$option['id']] . '";';
							} elseif ( !empty( $default_value ) ) {
								$configs .= ' var ' . $option['config']['key'] . ' = "' . $default_value . '";';
							}
						}
					}
				}
				
				// output javascript strings and functions for togetherjs
				echo '
					<script type="text/javascript">
					
						var TogetherJSConfig_suppressInvite		= true;
						var TogetherJSConfig_getUserName 		= function () {return "' . $user_name . '";};
						var TogetherJSConfig_getUserAvatar  	= function () {return "' . $user_avatar . '";};
						var TogetherJSConfig_findRoom			= "site_' . $site_id . '";
						
						' . $configs . '
						
						jQuery( document ).ready( function( $ ) {
							
							TogetherJS.on( "close", function () {
								$("#wp-admin-bar-togetherjs").children().first().html("' . __( $name_menu, 'togetherjs' ) . '");
							});
							
							TogetherJS.on( "ready", function () {
								$("#wp-admin-bar-togetherjs").children().first().html("' . __( $name_menu_open, 'togetherjs' ) . '");
							});
							
							$( "#wp-admin-bar-togetherjs" ).click( function( event ) {
							
								event.preventDefault();
								TogetherJS(this);
								return false;
							});
						});
					</script>
					';
			}
			
			// add javascripts to enqueue 
			function add_javascripts() {
			
				wp_register_script( 'togetherjs', 'https://togetherjs.com/togetherjs-min.js', array( 'jquery' ), '1.0', true );
				wp_enqueue_script( 'togetherjs' );
			}
			
			// shortcut to get suer's avatar from gravatar
			function get_gravatar_url( $email, $size = 40 ) {
			
				$hash = md5( strtolower( trim ( $email ) ) );
				return 'http://gravatar.com/avatar/' . $hash . '&s=' . $size;
			}
			
			// adds admin menu for plugin
			function add_admin_menu() {
				add_options_page( __( 'Collaborate Options', 'togetherjs' ), __( $this->terms['name_menu'], 'togetherjs' ), 'manage_options', 'togetherjs', array( &$this, 'options_page' ) );
			}
			
			// outputs options page for plugin
			function options_page() {
			
				// if current user can manage the options
				if ( !current_user_can( 'manage_options' ) )  {
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
				}
				
				// if data is being saved and nonce is good
				if ( !empty( $_POST['saved'] ) && wp_verify_nonce( $_POST['togetherjs_field'], 'togetherjs_action' ) ) {
				
					// loop through options setting them in the settings
					foreach ( $this->options as $key => $option ) {
						if ( isset( $_POST[$option['id']] ) ) {
							$this->settings[$option['id']] = $_POST[$option['id']];
						}
					}
					
					$settings_array = array();
					
					foreach ( $this->settings as $key => $value ) {
						if ( !empty( $key ) && !empty( $value ) ) {
							$settings_array[$key] = $value;
						}
					}
					
					// update option or add option
					if ( get_option( $this->terms['option_id'] ) !== false ) {
						$return = update_option( $this->terms['option_id'], $settings_array );
					} else {
						$return = add_option( $this->terms['option_id'], $settings_array );
					}
				}
				
				// our page contents
				echo '<div class="wrap">';
				
					echo '<h2>' . __( $this->terms['name_menu'] . ' Options', 'togetherjs' ) . '</h2>';
					
					echo '<form method="post">';
					
						echo '<input type="hidden" name="saved" value="1" />';
						
						// nonce field
						wp_nonce_field( 'togetherjs_action', 'togetherjs_field' );
						
						echo '<table class="form-table"><tbody>';
						
							// loop through options outputing them
							foreach ( $this->options as $option ) {
								$this->render_field( $option );
							}
						
						echo '</tbody></table>';
						
						echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>';
					
					echo '</form>';
				
				// bottom credits, and information
				echo '
					<p>For more information, visit <a href="https://togetherjs.com/" title="TogetherJS">TogetherJS.com</a>.</p>
					<p>For support, please use the plugin\'s support forum at <a href="http://wordpress.org/plugins/collaboration/" title="WordPress">WordPress.org</a>.</p>
					<p class="together_js_credit">This plugin is built by Dustin Dempsey of <a href="http://playforward.net/?ref=togetherjs" title="Playforward Web Development">Playforward</a> and it is not affiliated with <a href="http://mozillalabs.com" title="Mozilla labs">Mozilla labs</a>.</p>
					';
				
				echo '</div>';
			}
		}
		
		// init class on WordPress init
		function together_js_init() {
		
			// if admin bar is showing and user is logged in
			if ( is_admin_bar_showing() && is_user_logged_in() ) {
			
				// init plugin's class
				global $together_js;
				$together_js = new together_js();
			}
		}
		
		// add init action
		add_action( "init", "together_js_init" );
	}

?>
<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( WP_LOGIN_FLOW_PLUGIN_DIR . '/classes/settings/fields.php' );
require_once( WP_LOGIN_FLOW_PLUGIN_DIR . '/classes/settings/handlers.php' );

/**
 * Class WP_Login_Flow_Settings
 *
 * @since 3.0.0
 *
 */
class WP_Login_Flow_Settings extends WP_Login_Flow_Settings_Handlers {

	/**
	 * @var
	 */
	protected static $settings;
	/**
	 * @var string
	 */
	protected $settings_group;
	/**
	 * @var int
	 */
	protected $process_count;
	/**
	 * @var
	 */
	protected $field_data;

	/**
	 * WP_Login_Flow_Settings constructor.
	 */
	function __construct() {

		$this->settings_group = 'wp_login_flow';
		$this->process_count  = 0;

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'submenu' ) );
		add_action( 'wp_ajax_wp_login_flow_dl_backup', array( $this, 'download_backup' ) );

	}

	/**
	 * Add Login Flow to user submenu
	 *
	 *
	 * @since 2.0.0
	 *
	 */
	function submenu(){

		add_submenu_page(
			'users.php',
			__( 'Login Flow', 'wp-login-flow' ),
			__( 'Login Flow', 'wp-login-flow' ),
			'manage_options',
			'wp-login-flow',
			array( $this, 'output' )
		);

	}

	/**
	 * Output WP Login Flow Settings Page
	 *
	 *
	 * @since 2.0.0
	 *
	 */
	function output() {

		self::init_settings();
		settings_errors();
		?>
		<div class="wrap">

			<div id="icon-themes" class="icon32"></div>
			<h1><?php _e( 'WP Login Flow', 'wp-login-flow' ); ?></h1>
			<h2></h2>

			<form method="post" action="options.php">

				<?php settings_fields( $this->settings_group ); ?>

				<h2 id="wplf-nav-tabs" class="nav-tab-wrapper">
		<?php
					foreach ( self::$settings as $key => $tab ) {
						$title = $tab["title"];
						echo "<a href=\"#settings-{$key}\" class=\"nav-tab\" data-tab=\"{$key}\">{$title}</a>";
					}
		?>
				</h2>
				<div id="wplf-all-settings">
		<?php
						foreach ( self::$settings as $key => $tab ):
		?>
						<div id="settings-<?php echo $key ?>" class="settings_panel">
							<div id="wplf-settings-inside">
		<?php
							foreach( $tab['sections'] as $skey => $section ) {

								if ( array_key_exists( 'hide_if', $section ) && ! empty( $section['hide_if'] ) ) {
									continue;
								}

								echo "<h2 class=\"wp-ui-primary\">{$section['title']}</h2>";
								if( $skey === 'enable_rewrites' && parent::permalinks_disabled() ){
									echo "<h3 class=\"permalink-error\">" . sprintf( __( 'You <strong>must</strong> enable <a href="%1$s">permalinks</a> to use custom rewrites!', 'wp-login-flow' ), admin_url('options-permalink.php') ). "</h3>";
								}
								if( $skey === 'require_activation' && ! parent::registration_enabled() ){
									echo "<h3 class=\"permalink-error\">" . sprintf( __( '"%1$s" needs to be enabled <a href="%2$s">on this page</a> for users to register on your site!', 'wp-login-flow' ), __('Anyone can register', 'wp-login-flow') , admin_url('options-general.php') ). "</h3>";
								}
								do_settings_sections( "wplf_{$key}_{$skey}_section" );
							}
		?>
							</div>
						</div>
		<?php
						endforeach;
						submit_button();
		?>
				</div>
			</form>
		</div>

	<?php

	}

	/**
	 * Initialize Settings Fields
	 *
	 *
	 * @since 2.0.0
	 *
	 */
	public static function init_settings() {
		global $is_nginx;

		$settings = apply_filters(
			'wp_login_flow_settings',
			array(
				'rewrites' => array(
					'title'  => __( 'Permalinks', 'wp-login-flow' ),
					'sections' => array(
						'enable_auto_disable' => array(
							'title'  => __( 'Auto Disable Rewrites', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_auto_disable_rewrites',
									'std'        => '0',
									'label'      => __( 'Auto Disable', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'Automatically disable rewrites/redirects to custom URLs if htaccess or web.config file is not detected.', 'wp-login-flow' ),
								),
							)
						),
						'enable_nginx' => array(
							'title'  => __( 'Nginx Settings', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_nginx_enable',
									'std'        => '0',
									'label'      => __( 'Enable Rewrites', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'Enable rewrite handling for Nginx servers (YOU MUST MANUALLY CONFIGURE THE REWRITES!)', 'wp-login-flow' ),
								),
							)
						),
						'enable_rewrites' => array(
							'title'  => __( 'Permalinks/Rewrites', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_rewrite_login',
									'std'        => '0',
									'label'      => __( 'Login', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => '<strong>' . __( 'Default', 'wp-login-flow' ) . ':</strong> <pre>' . home_url() . '/wp-login.php</pre>',
									'disabled' => parent::permalinks_disabled(),
									'fields'     => array(
									    array(
											'name'       => 'wplf_rewrite_login_slug',
											'std'        => 'login',
											'pre'        => '<pre>' . home_url() . '/</pre>',
											'post'       => '',
											'type'       => 'textbox',
											'attributes' => array(),
									        'disabled'   => parent::permalinks_disabled()
									    )
								    )
								),
								array(
									'name'       => 'wplf_rewrite_register',
									'std'        => '0',
									'label'      => __( 'Register', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => '<strong>' . __( 'Default', 'wp-login-flow' ) . ':</strong> <pre>' . home_url() . '/wp-login.php?action=register</pre>',
									'disabled'   => parent::permalinks_disabled(),
									'endpoints' => array( 'disabled', 'checkemail' ),
									'fields'     => array(
										array(
											'name'       => 'wplf_rewrite_register_slug',
											'std'        => 'register',
											'pre'        => '<pre>' . home_url() . '/</pre>',
											'post'       => '',
											'type'       => 'textbox',
											'attributes' => array(),
											'disabled'   => parent::permalinks_disabled()
										)
									)
								),
								array(
									'name'       => 'wplf_rewrite_activate',
									'std'        => '0',
									'label'      => __( 'Activate', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => '<strong>' . __( 'Default', 'wp-login-flow' ) . ':</strong> <pre>' . home_url() . '/wp-login.php?action=rp&key=ACTIVATIONCODE&login=USERNAME</pre>',
									'disabled'   => parent::permalinks_disabled(),
									'fields'     => array(
										array(
											'name'       => 'wplf_rewrite_activate_slug',
											'std'        => 'activate',
											'pre'        => '<pre>' . home_url() . '/</pre>',
											'post'       => '<pre>/USERNAME/ACTIVATIONCODE</pre>',
											'type'       => 'textbox',
											'attributes' => array(),
											'disabled'   => parent::permalinks_disabled()
										)
									)
								),
								array(
									'name'       => 'wplf_rewrite_lost_pw',
									'std'        => '0',
									'label'      => __( 'Lost Password', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc' => '<strong>' . __( 'Default', 'wp-login-flow' ) . ':</strong> <pre>' . home_url() . '/wp-login.php?action=lostpassword</pre>',
									'endpoints' => array( 'rp', 'resetpass', 'confirm', 'expired', 'invalid' ),
									'disabled' => parent::permalinks_disabled(),
									'fields' => array(
										array(
											'name'       => 'wplf_rewrite_lost_pw_slug',
											'std'        => 'lost-password',
											'pre'        => '<pre>' . home_url() . '/</pre>',
											'post'       => '',
											'type'       => 'textbox',
											'attributes' => array(),
											'disabled' => parent::permalinks_disabled()
										)
									)
								),
								array(
									'name'       => 'wplf_rewrite_reset_pw',
									'std'        => '0',
									'label'      => __( 'Reset Password', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc' => '<strong>' . __( 'Default', 'wp-login-flow' ) . ':</strong> <pre>' . home_url() . '/wp-login.php?action=rp&key=RESETKEY&login=USERNAME</pre>',
									'disabled' => parent::permalinks_disabled(),
									'fields' => array(
										array(
											'name'       => 'wplf_rewrite_reset_pw_slug',
											'std'        => 'reset-password',
											'pre'        => '<pre>' . home_url() . '/</pre>',
											'post'       => '<pre>/USERNAME/RESETKEY</pre>',
											'type'       => 'textbox',
											'attributes' => array(),
											'disabled' => parent::permalinks_disabled()
										)
									)
								),
								array(
									'name'       => 'wplf_rewrite_loggedout',
									'std'        => '0',
									'label'      => __( 'Logged Out', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => '<strong>' . __( 'Default', 'wp-login-flow' ) . ':</strong> <pre>' . home_url() . '/wp-login.php?loggedout=true</pre>',
									'disabled'   => parent::permalinks_disabled(),
									'fields'     => array(
										array(
											'name'       => 'wplf_rewrite_loggedout_slug',
											'std'        => 'logout/complete',
											'pre'        => '<pre>' . home_url() . '/</pre>',
											'post'       => '',
											'type'       => 'textbox',
											'attributes' => array(),
											'disabled'   => parent::permalinks_disabled()
										)
									)
								),
							)
						)
					)
				),
				'registration' => array(
					'title'  => __( 'Registration', 'wp-login-flow' ),
					'sections' => array(
						'require_activation' => array(
							'title'  => __( 'Account Setup', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_require_activation',
									'std'        => '1',
									'label'      => __( 'Require Activation', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'Email link to set password in email when new users register. This is default method of registration in WordPress.', 'wp-login-flow' ),
								),
								array(
									'name'       => 'wplf_register_set_pw',
									'std'        => '0',
									'label'      => __( 'Register with Password', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'Show password input fields on registration form, and do not require account activation (disables require activation above)', 'wp-login-flow' ),
								)
							)
						),
						'registration' => array(
							'title'  => __( 'Registration', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_auto_login',
									'std'        => '0',
									'label'      => __( 'Auto Login', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'Auto login users after completing registration (regardless of account activation status).  Users will still be required to activate account (if enabled) to login again.', 'wp-login-flow' ),
								),
								array(
									'name'       => 'wplf_register_loader',
									'std'        => '0',
									'label'      => __( 'Loader', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'Add a spinning loader and disable the register button after being clicked (and form validated)', 'wp-login-flow' ),
								)
							)
						),
						'registration_fields' => array(
							'title'  => __( 'Registration Fields', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_registration_email_as_un',
									'std'        => '0',
									'label'      => __( 'Email as Username', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'Hide the Username field, and use Email as the username.', 'wp-login-flow' ),
								),
								array(
									'name'       => 'wplf_registration_custom_fields',
									'std'        => '0',
									'label'      => __( 'Custom Fields', 'wp-login-flow' ),
									'type'       => 'repeatable',
									'attributes' => array(),
									'desc'       => __( 'Add any additional custom user meta fields you want on the register form.', 'wp-login-flow' ),
									'rfields' => array(
										'label'    => array(
											'label'       => __( 'Field Label', 'wp-login-flow' ),
											'type'        => 'textbox',
											'help' => __( 'Enter the label to show above the input field', 'wp-login-flow' ),
											'default'     => '',
											'placeholder' => '',
											'multiple'    => true,
											'required' => true
										),
										'meta_key'    => array(
											'label'       => __( 'Meta Key', 'wp-login-flow' ),
											'type'        => 'textbox',
											'default'     => '',
											'help'		=> __( 'Enter the exact user meta key to save this value to.  Example would be first_name, last_name, etc.', 'wp-login-flow' ),
											'placeholder' => '',
											'multiple'    => true,
											'required'    => true
										),
										'required' => array(
											'cb_label'          => __( 'Required', 'wp-login-flow' ),
											'label'        => __( 'Required', 'wp-login-flow' ),
											'type'           => 'checkbox',
											'class'          => '',
											'default'            => '0',
											'multiple'       => true,
											'template_style' => true
										)
									)
								)
							)
						)
					)
				),
				'login' => array(
					'title'  => __( 'Login', 'wp-login-flow' ),
					'sections' => array(
						'registration' => array(
							'title'  => __( 'Login General', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_login_loader',
									'std'        => '0',
									'label'      => __( 'Loader', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'Add a spinning loader and disable the register button after being clicked (and form validated)', 'wp-login-flow' ),
								)
							)
						)
					)
				),
				'redirects' => array(
					'title'  => __( 'Redirects', 'wp-login-flow' ),
					'sections' => array(
						'login_redirects' => array(
							'title'  => __( 'Login Redirects', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'        => 'wplf_default_login_redirect',
									'label'       => __( 'Default Login Redirect', 'wp-login-flow' ),
									'desc'        => __( 'Enter the endpoint for default redirect after a user logs in (if they don\'t match any other rules below)', 'wp-login-flow' ),
									'placeholder'         => '/my-account',
									'type'        => 'textbox',
									'field_class' => '',
									'attributes'  => array(),
								),
								array(
									'name'       => 'wplf_role_login_redirects',
									'std'        => '0',
									'label'      => __( 'Role Login Redirects', 'wp-login-flow' ),
									'type'       => 'repeatable',
									'attributes' => array(),
									'single_val' => 'role', // Signify this group has single val fields to check on page init
									'desc'       => __( 'Select any custom redirects to use for specific user roles', 'wp-login-flow' ),
									'rfields'    => array(
										'role'    => array(
											'label'       => __( 'Role', 'wp-login-flow' ),
											'type'        => 'userroles',
											'help'        => __( 'Enter the label to show above the input field', 'wp-login-flow' ),
											'default'     => '',
											'placeholder' => '',
											'multiple'    => true,
											'single_val'  => true, // Means values can't be selected more than once in repeatable
											'required'    => true
										),
										'redirect' => array(
											'label'       => __( 'Redirect', 'wp-login-flow' ),
											'type'        => 'textbox',
											'default'     => '',
											'placeholder' => '/some-endpoint',
											'help'        => __( 'Endpoint (on this site) to redirect to (do NOT include website URL)', 'wp-login-flow' ),
											'placeholder' => '',
											'multiple'    => true,
											'required'    => true
										)
									)
								),
								array(
									'name'       => 'wplf_redirect_to_login_redirects',
									'std'        => '0',
									'label'      => sprintf( __( '%s Precedence', 'wp-login-flow' ), 'redirect_to' ),
									'cb_label'   => sprintf( __( 'Yes, allow a POST or GET %s variable to take priority over above settings', 'wp-login-flow' ), '<code>redirect_to</code>' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'By enabling this setting, any redirect_to value set in GET or POST params will take priority over the above rules.  More than likely you will want to leave this disabled.', 'wp-login-flow' ),
								),
							)
						),
						'logout_redirects' => array(
							'title'  => __( 'Logout Redirects', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'        => 'wplf_default_logout_redirect',
									'label'       => __( 'Default Logout Redirect', 'wp-login-flow' ),
									'desc'        => __( 'Enter the endpoint for default redirect after a user logs out (if they don\'t match any other rules below)', 'wp-login-flow' ),
									'placeholder'         => '/my-account',
									'type'        => 'textbox',
									'field_class' => '',
									'attributes'  => array(),
								),
								array(
									'name'       => 'wplf_role_logout_redirects',
									'std'        => '0',
									'label'      => __( 'Role Logout Redirects', 'wp-login-flow' ),
									'type'       => 'repeatable',
									'attributes' => array(),
									'single_val' => 'role', // Signify this group has single val fields to check on page init
									'desc'       => __( 'Select any custom redirects to use for specific user roles', 'wp-login-flow' ),
									'rfields'    => array(
										'role'    => array(
											'label'       => __( 'Role', 'wp-login-flow' ),
											'type'        => 'userroles',
											'help'        => __( 'Enter the label to show above the input field', 'wp-login-flow' ),
											'default'     => '',
											'placeholder' => '',
											'multiple'    => true,
											'single_val'  => true, // Means values can't be selected more than once in repeatable
											'required'    => true
										),
										'redirect' => array(
											'label'       => __( 'Redirect', 'wp-login-flow' ),
											'type'        => 'textbox',
											'default'     => '',
											'placeholder' => '/some-endpoint',
											'help'        => __( 'Endpoint (on this site) to redirect to (do NOT include website URL)', 'wp-login-flow' ),
											'placeholder' => '',
											'multiple'    => true,
											'required'    => true
										)
									)
								),
								array(
									'name'       => 'wplf_redirect_to_logout_redirects',
									'std'        => '0',
									'label'      => sprintf( __( '%s Precedence', 'wp-login-flow' ), 'redirect_to' ),
									'cb_label'   => sprintf( __( 'Yes, allow a POST or GET %s variable to take priority over above settings', 'wp-login-flow' ), '<code>redirect_to</code>' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'By enabling this setting, any redirect_to value set in GET or POST params will take priority over the above rules.  More than likely you will want to leave this disabled.', 'wp-login-flow' ),
								),
							)
						)
					)
				),
				'custom_page' => array(
					'title'  => __( 'Customize Page', 'wp-login-flow' ),
					'sections' => array(
						'page' => array(
							'title' => __( 'Page Customizations', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'  => 'wplf_bg_color',
									'label' => __( 'Background Color', 'wp-login-flow' ),
									'desc'  => __( 'Use a custom background for the default wp-login.php page.', 'wp-login-flow' ),
									'type'  => 'colorpicker'
								),
								array(
									'name'  => 'wplf_font_color',
									'label' => __( 'Font Color', 'wp-login-flow' ),
									'desc'  => __( 'Use a custom font color for wp-login.php page.', 'wp-login-flow' ),
									'type'  => 'colorpicker'
								),
								array(
									'name'  => 'wplf_link_color',
									'label' => __( 'Link Color', 'wp-login-flow' ),
									'desc'  => __( 'Use a custom color for links on the wp-login.php page.', 'wp-login-flow' ),
									'type'  => 'colorpicker'
								),
								array(
									'name'  => 'wplf_link_hover_color',
									'label' => __( 'Link Hover Color', 'wp-login-flow' ),
									'desc'  => __( 'Use a custom color when hovering over links on the wp-login.php page.', 'wp-login-flow' ),
									'type'  => 'colorpicker'
								),
								array(
									'name'  => 'wplf_custom_css',
									'label' => __( 'Custom CSS', 'wp-login-flow' ),
									'desc'  => __( 'Add any custom CSS you want added to login page here.', 'wp-login-flow' ),
									'type'  => 'textarea'
								),
							)
						),
						'login_styles' => array(
							'title' => __( 'Logo Customizations', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'        => 'wplf_logo_url_title',
									'label'       => __( 'Logo URL Title', 'wp-login-flow' ),
									'placeholder' => __( 'My Website', 'wp-login-flow' ),
									'desc'        => __( 'Title attribute for the logo url link', 'wp-login-flow' ),
									'type'        => 'textbox'
								),
								array(
									'name'  => 'wplf_logo_url',
									'label' => __( 'Logo URL', 'wp-login-flow' ),
									'placeholder' => 'http://mydomain.com',
									'desc'  => __( 'Custom URL to use for the logo.', 'wp-login-flow' ),
									'type'  => 'textbox'
								),
								array(
									'name'    => 'wplf_logo',
									'label'   => __( 'Custom Logo', 'wp-login-flow' ),
									'modal_title'   => __( 'Custom Logo', 'wp-login-flow' ),
									'modal_btn'   => __( 'Set Custom Logo', 'wp-login-flow' ),
									'desc'    => __( 'Use a custom logo on the default wp-login.php page.', 'wp-login-flow' ),
									'type'    => 'upload'
								)
							)
						),
						'login_box' => array(
							'title' => __( 'Login Box', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'        => 'wplf_login_box_responsive',
									'label'       => __( 'Responsive Width', 'wp-login-flow' ),
									'cb_label' => __( 'Enable', 'wp-login-flow' ),
									'desc'        => __( 'Screen sizes above 1200px use default 50%, smaller screens use 90% width.', 'wp-login-flow' ),
									'type'        => 'checkbox'
								),
								array(
									'name'  => 'wplf_login_box_color',
									'label' => __( 'Font Color', 'wp-login-flow' ),
									'desc'  => __( 'Custom font color for Login Box', 'wp-login-flow' ),
									'type'  => 'colorpicker'
								),
								array(
									'name'  => 'wplf_login_box_bg_color',
									'label' => __( 'Background Color', 'wp-login-flow' ),
									'desc'  => __( 'Custom background color for Login Box', 'wp-login-flow' ),
									'type'  => 'colorpicker'
								),
								array(
									'name'       => 'wplf_login_box_border_radius_enable',
									'std'        => '0',
									'label'      => __( 'Border Radius', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc' => __( 'Set a custom border radius on the login box, will only work with modern browsers that support CSS3.', 'wp-login-flow' ),
									'fields'     => array(
										array(
											'name'        => 'wplf_login_box_border_radius',
											'type'  => 'spinner',
											'post' => 'px'
										)
									),
								)
							)
						)
					)

				),
				'email' => array(
					'title'  => __( 'Email', 'wp-login-flow' ),
					'sections' => array(
						'email_from' => array(
							'title' => __( 'Customize Email Options', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_from_name_enable',
									'std'        => '0',
									'label'      => __( 'From Name', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'desc'       => __( 'Use a custom name on emails from WordPress.', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => '',
									'fields'     => array(
										array(
											'name'       => 'wplf_from_name',
											'std'        => '',
											'placeholder' => __( 'My Website', 'wp-login-flow' ),
											'post'       => '',
											'type'       => 'textbox',
											'attributes' => array()
										)
									),
								),
								array(
									'name'       => 'wplf_from_email_enable',
									'std'        => '0',
									'label'      => __( 'From E-Mail', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'desc'       => __( 'Use a custom e-mail on emails from WordPress.', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => '',
									'fields'     => array(
										array(
											'name'       => 'wplf_from_email',
											'std'        => '',
											'placeholder' => __( 'support@mydomain.com', 'wp-login-flow' ),
											'pre'        => '',
											'post'       => '',
											'type'       => 'textbox',
											'attributes' => array()
										)
									),
								),
							)
						)
					)
				),
				'templates' => array(
					'title'  => __( 'Email Templates', 'wp-login-flow' ),
					'sections' => array(
						'activation' => array(
							'title' => __( 'New User Activation Email Template', 'wp-login-flow' ),
							'hide_if' => get_option( 'wplf_register_set_pw', false ),
							'fields' => array(
								array(
									'name'       => 'wplf_activation_subject',
									'label'      => __( 'Email Subject', 'wp-login-flow' ),
									'desc'       => __( 'This will be used as the subject for the Activation email.  You can use any template tags available in message below.', 'wp-login-flow' ),
									'std'        => __( 'Account Activation Required', 'wp-login-flow' ),
									'type'       => 'textbox',
									'field_class'      => 'widefat',
									'attributes' => array(),
								),
								array(
									'name'       => 'wplf_activation_message',
									'label'      => __( 'Email Message', 'wp-login-flow' ),
									'desc'       => __( 'This template will be used as the first email sent to the user to activate their account.<br /><strong>Available Template Tags:</strong> <code>%wp_activate_url%</code>, <code>%wp_activation_key%</code>, <code>%wp_user_name%</code>, <code>%wp_user_email%</code>, <code>%wp_site_url%</code>, <code>%wp_login_url%</code>', 'wp-login-flow' ),
									'std'        => __( 'Thank you for registering your account:', 'wp-login-flow' ) . '<br />%wp_site_url%<br />' . sprintf( __( 'Username: %s', 'wp-login-flow' ), '%wp_user_name%' ) . '<br /><br />' . __( 'In order to activate your account and set your password, please visit the following address:', 'wp-login-flow' ) . '<br /><a href="%wp_activate_url%">%wp_activate_url%</a>',
									'type'       => 'wpeditor',
									'attributes' => array(),
								),
							)
						),
						'new_user' => array(
							'title' => __( 'New User Email Template', 'wp-login-flow' ),
							'hide_if' => get_option( 'wplf_require_activation', true ),
							'fields' => array(
								array(
									'name'       => 'wplf_new_user_subject',
									'label'      => __( 'Email Subject', 'wp-login-flow' ),
									'desc'       => __( 'This will be used as the subject for the new user registered email.  You can use any template tags available in message below.', 'wp-login-flow' ),
									'std'        => __( 'Your Account Information', 'wp-login-flow' ),
									'type'       => 'textbox',
									'field_class'      => 'widefat',
									'attributes' => array(),
								),
								array(
									'name'       => 'wplf_new_user_message',
									'label'      => __( 'Email Message', 'wp-login-flow' ),
									'desc'       => __( 'This template will be used as the first email sent to the user after creating an account (with their own password).<br /><strong>Available Template Tags:</strong> <code>%wp_user_name%</code>, <code>%wp_user_email%</code>, <code>%wp_site_url%</code>, <code>%wp_login_url%</code>', 'wp-login-flow' ),
									'std'        => __( 'Thank you for registering your account:', 'wp-login-flow' ) . '<br />%wp_site_url%<br />' . sprintf( __( 'Username: %s', 'wp-login-flow' ), '%wp_user_name%' ) . '<br /><br />' . __( 'To login to your account, please visit the following address:', 'wp-login-flow' ) . '<br /><a href="%wp_login_url%">%wp_login_url%</a>',
									'type'       => 'wpeditor',
									'attributes' => array(),
								),
							)
						),
						'lostpassword' => array(
							'title' => __( 'Lost Password Email Template', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_lostpassword_subject',
									'label'      => __( 'Email Subject', 'wp-login-flow' ),
									'desc'       => __( 'This will be used as the subject for the Lost Password email.  You can use any template tags available in message below.', 'wp-login-flow' ),
									'std'        => __( 'Password Reset', 'wp-login-flow' ),
									'type'       => 'textbox',
									'field_class'      => 'widefat',
									'attributes' => array(),
								),
								array(
									'name'       => 'wplf_lostpassword_message',
									'label'      => __( 'Email Message', 'wp-login-flow' ),
									'desc'       => __( 'This template will be used whenever someone submits a lost/reset password request.<br /><strong>Available Template Tags:</strong> <code>%wp_reset_pw_url%</code>, <code>%wp_reset_pw_key%</code>, <code>%wp_user_name%</code>, <code>%wp_user_email%</code>, <code>%wp_site_url%</code>, <code>%wp_login_url%</code>', 'wp-login-flow' ),
									'std'        => __( 'Someone requested that the password be reset for the following account:', 'wp-login-flow') . '<br />%wp_site_url%<br />' . sprintf( __( 'Username: %s', 'wp-login-flow' ), '%wp_user_name%' ) . '<br /><br />' . __( 'If this was a mistake, just ignore this email and nothing will happen.', 'wp-login-flow' ) . '<br />' . __( 'To reset your password, visit the following address:', 'wp-login-flow' ) . '<br /><a href="%wp_reset_pw_url%">%wp_reset_pw_url%</a>',
									'type'       => 'wpeditor',
									'attributes' => array(),
								),
							)
						)
					)
				),
				'notices' => array(
					'title'  => __( 'Notices', 'wp-login-flow' ),
					'sections' => array(
						'activation' => array(
							'title' => __( 'Activation Notices', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_notice_activation_required',
									'label'      => __( 'Account Requires Activation Notice', 'wp-login-flow' ),
									'std'        => __( 'Thank you for registering.  Please check your email for your activation link.<br><br>If you do not receive the email please request a <a href="%wp_lost_pw_url%">password reset</a> to have the email sent again.', 'wp-login-flow' ),
									'desc'       => __( 'This notice will be shown to the user when they attempt to login but have not activated their account.<br /><strong>Available Template Tags:</strong> <code>%wp_lost_pw_url%</code>, <code>%wp_site_url%</code>, <code>%wp_login_url%</code>', 'wp-login-flow' ),
									'type'       => 'wpeditor',
									'attributes' => array(),
								),
								array(
									'name'       => 'wplf_notice_activation_pending',
									'label'      => __( 'Pending Activation Notice', 'wp-login-flow' ),
									'std'        => __( '<strong>ERROR</strong>: Your account is still pending activation, please check your email, or you can request a <a href="%wp_lost_pw_url%">password reset</a> for a new activation code.', 'wp-login-flow' ),
									'desc'       => __( 'This notice will be shown to the user when they attempt to login but have not activated their account.<br /><strong>Available Template Tags:</strong> <code>%wp_lost_pw_url%</code>, <code>%wp_site_url%</code>, <code>%wp_login_url%</code>', 'wp-login-flow' ),
									'type'       => 'wpeditor',
									'attributes' => array(),
								),
								array(
									'name'       => 'wplf_notice_activation_thankyou',
									'label'      => __( 'Successful Activation Notice', 'wp-login-flow' ),
									'std'        => '<p>' . __( 'Your account has been successfully activated!', 'wp-login-flow' ) . '</p><p>' . sprintf( __( 'You can now <a href="%s">Log In</a>', 'wp-login-flow'), '%wp_login_url%' ) . '</p>',
									'desc'       => __( 'This notice will be shown to the user once they activate and set the password for their account.<br /><strong>Available Template Tags:</strong> <code>%wp_lost_pw_url%</code>, <code>%wp_site_url%</code>, <code>%wp_login_url%</code>', 'wp-login-flow' ),
									'type'       => 'wpeditor',
									'attributes' => array(),
								),
							)
						),
					)
				),
			    'integrations' => array(
					'title' => __( 'Integrations', 'wp-login-flow' ),
					'sections' => array(
					    'jobify' => array(
						    'title' => __( 'Jobify', 'wp-login-flow' ),
							'fields' => array(
							    array(
								    'name'       => 'wplf_jobify_pw',
								    'std'        => '1',
								    'label'      => __( 'Jobify Password Field', 'wp-login-flow' ),
								    'cb_label'   => __( 'Remove', 'wp-login-flow' ),
								    'desc'       => __( 'Remove the password box from Jobify registration form.', 'wp-login-flow' ),
								    'type'       => 'checkbox',
								    'attributes' => array()
							    ),
					        )
					    )
				    )
				),
				'login_limiter' => array(
					'title'    => __( 'Login Limiter', 'wp-login-flow' ),
					'sections' => array(
						'login_limiter_general'   => array(
							'title'  => __( 'Login Limiter Settings', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_login_limiter_enable',
									'std'        => '0',
									'label'      => __( 'Limit Login Attempts', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'Limit login attempts based on configuration below.', 'wp-login-flow' ),
								),
								array(
									'name'       => 'wplf_login_limiter_log',
									'std'        => '0',
									'label'      => __( 'Lockout Log', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'desc'       => __( 'Enable to log all lockouts', 'wp-login-flow' ),
								),
								array(
									'name'       => 'wplf_login_limiter_email_lockouts',
									'std'        => '0',
									'label'      => __( 'Lockout Email', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array(),
									'break'      => true,
									'desc'       => __( 'Send email regarding lockouts after configuration above.', 'wp-login-flow' ),
									'fields'     => array(
										array(
											'name' => 'wplf_login_limiter_email_to',
											'type' => 'textbox',
											'pre'  => __( 'Send email to:', 'wp-login-flow' ),
											'post' => __( 'after', 'wp-login-flow' )
										),
										array(
											'name' => 'wplf_login_limiter_email_after',
											'type' => 'spinner',
											'std'  => 4,
											'post' => __( 'lockouts', 'wp-login-flow' )
										)
									),
								),
								array(
									'name'       => 'wplf_login_limiter_attempts',
									'std'        => '4',
									'label'      => __( 'Allowed Attempts', 'wp-login-flow' ),
									'type'       => 'spinner',
									'attributes' => array(),
									'desc'       => __( 'Set how many failed login attempts before triggering a lockout', 'wp-login-flow' ),
								),
								array(
									'name'       => 'wplf_login_limiter_lockout',
									'std'        => '20',
									'label'      => __( 'Lockout Minutes', 'wp-login-flow' ),
									'type'       => 'spinner',
									'attributes' => array(),
									'desc'       => __( 'Set how many minutes to lockout a user/IP after failed login attempts set above.', 'wp-login-flow' ),
								),
								array(
									'name'       => 'wplf_login_limiter_lockouts_allowed',
									'std'        => '4',
									'label'      => __( 'Lockouts Allowed', 'wp-login-flow' ),
									'type'       => 'spinner',
									'attributes' => array(),
									'desc'       => __( 'Set how many lockouts will be allowed before increasing lockout time set below.', 'wp-login-flow' ),
								),
								array(
									'name'       => 'wplf_login_limiter_lockouts_increase',
									'std'        => '24',
									'label'      => __( 'Lockout Increase', 'wp-login-flow' ),
									'type'       => 'spinner',
									'attributes' => array(),
									'desc'       => __( 'Set how many hours to increase the lockout time after number of failed total lockouts above.', 'wp-login-flow' ),
								)
							)
						),
						'login_limiter_whitelist' => array(
							'title'  => __( 'Login Limiter Whitelist', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_login_limiter_whitelist_ips',
									'label'      => __( 'IP Whitelist', 'wp-login-flow' ),
									'type'       => 'repeatable',
									'attributes' => array(),
									'desc'       => __( 'Add any IP addresses to the whitelist. This can be a single IP (x.x.x.x) or a range (1.2.3.4-5.6.7.8)', 'wp-login-flow' ),
									'rfields'    => array(
										'label' => array(
											'label'       => __( 'IP Address', 'wp-login-flow' ),
											'type'        => 'textbox',
											'help'        => __( 'Format should be a single IP address (1.2.3.4) or a range separated by hyphen (1.2.3.4-5.6.7.8)', 'wp-login-flow' ),
											'default'     => '',
											'placeholder' => '',
											'multiple'    => true,
											'required'    => true
										)
									)
								),
								array(
									'name'       => 'wplf_login_limiter_whitelist_users',
									'label'      => __( 'User Whitelist', 'wp-login-flow' ),
									'type'       => 'repeatable',
									'attributes' => array(),
									'desc'       => __( 'Add any specific usernames to omit from login limiter', 'wp-login-flow' ),
									'rfields'    => array(
										'label' => array(
											'label'       => __( 'Username/Email', 'wp-login-flow' ),
											'type'        => 'textbox',
											'help'        => __( 'Enter any username or email address to omit from the login limiter', 'wp-login-flow' ),
											'default'     => '',
											'placeholder' => '',
											'multiple'    => true,
											'required'    => true
										)
									)
								)
							)
						),
						'login_limiter_blacklist' => array(
							'title'  => __( 'Login Limiter Blacklist', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_login_limiter_blacklist_ips',
									'label'      => __( 'IP Blacklist', 'wp-login-flow' ),
									'type'       => 'repeatable',
									'attributes' => array(),
									'desc'       => __( 'Add any IP addresses to the blacklist. This can be a single IP (x.x.x.x) or a range (1.2.3.4-5.6.7.8)', 'wp-login-flow' ),
									'rfields'    => array(
										'label' => array(
											'label'       => __( 'IP Address', 'wp-login-flow' ),
											'type'        => 'textbox',
											'help'        => __( 'Format should be a single IP address (1.2.3.4) or a range separated by hyphen (1.2.3.4-5.6.7.8)', 'wp-login-flow' ),
											'default'     => '',
											'placeholder' => '',
											'multiple'    => true,
											'required'    => true
										)
									)
								),
								array(
									'name'       => 'wplf_login_limiter_blacklist_users',
									'label'      => __( 'User Blacklist', 'wp-login-flow' ),
									'type'       => 'repeatable',
									'attributes' => array(),
									'desc'       => __( 'Add any specific usernames to blacklist from logging in', 'wp-login-flow' ),
									'rfields'    => array(
										'label' => array(
											'label'       => __( 'Username/Email', 'wp-login-flow' ),
											'type'        => 'textbox',
											'help'        => __( 'Enter any username or email address to blacklist from logging in', 'wp-login-flow' ),
											'default'     => '',
											'placeholder' => '',
											'multiple'    => true,
											'required'    => true
										)
									)
								)
							)
						)
					)
				),
				'settings' => array(
					'title'    => __( 'Settings', 'wp-login-flow' ),
					'sections' => array(
						'config' => array(
							'title'  => __( 'Configuration', 'wp-login-flow' ),
							'fields' => array(
								array(
									'name'       => 'wplf_uninstall_remove_options',
									'std'        => '0',
									'label'      => __( 'Remove on Uninstall', 'wp-login-flow' ),
									'cb_label'   => __( 'Enable', 'wp-login-flow' ),
									'desc'       => __( 'This will remove all configuration and options when you uninstall the plugin (disabled by default)', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array()
								),
								array(
									'name'       => 'wplf_show_admin_bar_only_admins',
									'std'        => '0',
									'label'      => __( 'Admin Bar', 'wp-login-flow' ),
									'cb_label'   => __( 'Only show admin bar for administrators (users with manage_options capability)', 'wp-login-flow' ),
									'desc'       => __( 'By default, WordPress will show the admin bar for any kind of user when browsing the site.  Enable this setting to only show for Administrators.', 'wp-login-flow' ),
									'type'       => 'checkbox',
									'attributes' => array()
								),
								array(
									'name'       => 'wplf_reset_default',
									'field_class'  => 'button-primary',
									'action' => 'reset_default',
									'label'      => __( 'Reset to Defaults', 'wp-login-flow' ),
									'caption'   => __( 'Reset to Defaults', 'wp-login-flow' ),
									'desc'       => __( '<strong>CAUTION!</strong> This will remove ALL configuration values, and reset everything to default!', 'wp-login-flow' ),
									'type'       => 'button',
									'attributes' => array()
								),

							)
						)
					)
				)
			)
		);

		if( ! $is_nginx ){
			unset( $settings['rewrites']['sections']['enable_nginx'] );
		}

		// TODO: add login limiter handling
		unset( $settings['login_limiter'] );

		self::$settings = $settings;
	}

	/**
	 * Return Settings Fields
	 *
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public static function get_settings(){

		if( ! self::$settings ) self::init_settings();

		return self::$settings;

	}

	/**
	 * register_settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_settings() {

		self::init_settings();

		foreach ( self::$settings as $key => $tab ) {

			foreach( $tab['sections'] as $skey => $section ) {

				if( array_key_exists( 'hide_if', $section ) && ! empty( $section['hide_if'] ) ){
					continue;
				}

				$section_header = "default_header";
				if ( method_exists( $this, "{$key}_{$skey}_header" ) ) $section_header = "{$key}_{$skey}_header";

				add_settings_section( "wplf_{$key}_{$skey}_section", '', array( $this, $section_header ), "wplf_{$key}_{$skey}_section" );

				foreach ( $section[ 'fields' ] as $option ) {

					$field_args = $this->build_args( $option );

					if( isset( $option[ 'fields' ] ) && ! empty( $option[ 'fields' ] ) ){

						foreach( $option[ 'fields' ] as $sf ) $this->build_args( $sf );

					}

					add_settings_field(
						$option[ 'name' ],
						$option[ 'label' ],
						array( $this, "{$option['type']}_field" ),
						"wplf_{$key}_{$skey}_section",
						"wplf_{$key}_{$skey}_section",
						$field_args
					);

				}

			}
		}
	}

	/**
	 * Build arguments to pass to settings fields/handlers
	 *
	 *
	 * @since 2.0.0
	 *
	 * @param      $option
	 * @param bool $register
	 *
	 * @return array
	 */
	function build_args( $option, $register = true ){

		$submit_handler = 'submit_handler';

		if ( method_exists( $this, "{$option['type']}_handler" ) ) $submit_handler = "{$option['type']}_handler";

		if ( isset( $option[ 'std' ] ) ) add_option( $option[ 'name' ], $option[ 'std' ] );

		if( $register ) register_setting( $this->settings_group, $option[ 'name' ], array( $this, $submit_handler ) );

		$placeholder = ( ! empty( $option[ 'placeholder' ] ) ) ? 'placeholder="' . $option[ 'placeholder' ] . '"' : '';
		$class       = ! empty( $option[ 'class' ] ) ? $option[ 'class' ] : '';
		$field_class = ! empty( $option[ 'field_class' ] ) ? $option[ 'field_class' ] : '';

		$non_escape_fields = array( 'wpeditor', 'repeatable' );
		$value       = in_array( $option['type'], $non_escape_fields ) ? get_option( $option['name'] ) : esc_attr( get_option( $option[ 'name' ] ) );

		$attributes  = "";

		if ( ! empty( $option[ 'attributes' ] ) && is_array( $option[ 'attributes' ] ) ) {

			foreach ( $option[ 'attributes' ] as $attribute_name => $attribute_value ) {
				$attribute_name  = esc_attr( $attribute_name );
				$attribute_value = esc_attr( $attribute_value );
				$attributes .= "{$attribute_name}=\"{$attribute_value}\" ";
			}

		}

		$field_args = array(
			'option'      => $option,
			'placeholder' => $placeholder,
			'value'       => $value,
			'attributes'  => $attributes,
			'class'       => $class,
		    'field_class' => $field_class
		);

		return $field_args;

	}

}
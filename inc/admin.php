<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

class Gwptb_Admin {
	
	private static $instance = NULL; //instance store
	
	private function __construct() {
		
		add_action('admin_menu', array($this, 'admin_menu_setup'), 9); // Add the options page and menu item
		
		//Link sin plugins table
		add_filter('plugin_row_meta', array($this, 'set_plugin_meta'), 10, 2);
		add_filter('plugin_action_links_'.GWPTB_PLUGIN_BASE_NAME, array($this, 'add_settings_link'));
		
		//Settings init
		add_action( 'admin_init', array($this, 'settings_init'));
	}
	
	
	/** instance */
    public static function get_instance(){
        
        if (NULL === self :: $instance)
			self :: $instance = new self;
					
		return self :: $instance;
    }
	
	public function set_plugin_meta($links, $file){
		
		if($file == GWPTB_PLUGIN_BASE_NAME) {
			$links[] = '<a href="https://github.com/Teplitsa/GWPTB">GitHub</a>';		  
        }

        return $links;		
	}
	
	public function add_settings_link($links) {

		$links[] = '<a href="'.admin_url('admin.php?page=gwptb_settings').'">'.__( 'Settings', 'gwptb').'</a>';

		return $links;
	}
	
	/** == Menu == **/
	public function admin_menu_setup() {

        //Menu root
        add_menu_page(__('Green WP Telegram Bot', 'gwptb'), __('GWPTB', 'gwptb'), 'manage_options', 'gwptb', array($this, 'dashboard_screen'), 'dashicons-nametag');

        // Dashboard
        add_submenu_page('gwptb', __('Green WP Telegram Bot', 'gwptb'), __('Dashboard', 'gwptb'), 'manage_options', 'gwptb', array($this, 'dashboard_screen'));
		
		//Log
        add_submenu_page('gwptb', __('GWPTB Log', 'gwptb'), __('Log', 'gwptb'), 'manage_options', 'gwptb_log', array($this, 'log_screen'));
	}
	
	
	/**== Menu pages ==**/
	public function dashboard_screen() {

		if( !current_user_can('manage_options') ) {
            wp_die(__('Sorry, but you do not have permissions to access this page.', 'gwptb'));
        }
		
		$token = gwptb_get_option('bot_token');
		$stage = (isset($_GET['stage'])) ? trim($_GET['stage']) : 'default';
		
		//button
		if(!empty($token) && ($stage == 'default')){
			$btn_url = add_query_arg(array('page' => 'gwptb', 'stage' => 'howto'), admin_url('admin.php'));			
			$btn = "<a href='{$btn_url}' class='page-title-action'>".__('How to create bot', 'gwptb')."</a>";
		}
		elseif(!empty($token) && ($stage != 'default')) {
			$btn_url = add_query_arg(array('page' => 'gwptb'), admin_url('admin.php'));			
			$btn = "<a href='{$btn_url}' class='page-title-action'>".__('Settings', 'gwptb')."</a>";
		}
	?>	
		<div class="wrap">
            <h2><?php _e('Green WP Telegram Bot', 'gwptb');?> <?php echo $btn;?></h2>
		
		<!-- intro section -->
		<?php if(empty($token) || $stage == 'howto') { ?>
			<div class="gwptb-page-section howto">
				How to create a bot - instructions 
			</div>
		<?php  } elseif(!empty($token) && ($stage == 'default')){ ?>
			<div class="gwptb-page-section connection">
				<div class="card pressthis">
					<h2>Set up notifications</h2>
				</div>
				
				<div class="">
					<a id="gwptb_test_token-trigger" href='#' class='button button-secondary'><?php _e('Test token', 'gwptb');?></a>
					<div id="gwptb_test_token-response" class="gwptb-test-response"></div>
				</div>
			</div>
		<?php } ?>
			
		<?php if($stage == 'default') { ?>
			<div class="gwptb-page-section settings"><form action='options.php' method='post'>
            <?php
				settings_fields( 'gwptbSettings' );
				do_settings_sections( 'gwptbSettings' );
				submit_button();
			?>
			</form></div>
		<?php } ?>
		
		
		
		</div><!-- close .wrap -->
	<?php
	}
	
	
	
	
	public function log_screen() {

		if( !current_user_can('manage_options') ) {
            wp_die(__('You do not have permissions to access this page.', 'leyka'));
        }
	?>
		<div class="wrap">
            <h2><?php _e('GWPTB Log', 'gwptb');?></h2>
            
			log table
		</div><!-- close .wrap -->
	<?php
	}
	

	/** == Settings  fields == **/
	function settings_init(  ) { 
	
		register_setting( 'gwptbSettings', 'gwptb_settings' );
	
		add_settings_section(
			'gwptb_access_section', 
			__( 'Access settings', 'gwptb' ), 
			array($this, 'access_section_callback'), 
			'gwptbSettings'
		);
	
		add_settings_field( 
			'bot_token', 
			__( 'Bot Token', 'gwptb' ), 
			array($this, 'bot_token_render'), 
			'gwptbSettings', 
			'gwptb_access_section' 
		);
	
		add_settings_field( 
			'cert_path', 
			__( 'Path to certificate file', 'gwptb' ), 
			array($this, 'cert_path_render'), 
			'gwptbSettings', 
			'gwptb_access_section' 
		);
	
	}


	function bot_token_render(  ) { 		
		$tplb = Gwptb_Core::get_instance();
		$value = $tplb->get_option_value('bot_token'); 
	?>
		<input type='text' name='gwptb_settings[bot_token]' value='<?php echo $value; ?>' class="large-text">
	<?php	
	}
	
	
	function cert_path_render(  ) { 
		$tplb = Gwptb_Core::get_instance();
		$value = $tplb->get_option_value('cert_path');
	?>
		<input type='text' name='gwptb_settings[cert_path]' value='<?php echo $value; ?>' class="large-text">
		<p class="description"><?php _e('Specify to use self-signed certificate', 'gwptb');?></p>
	<?php	
	}


	function access_section_callback(  ) { 	
		//description or help information	
	}





	
	
} //class
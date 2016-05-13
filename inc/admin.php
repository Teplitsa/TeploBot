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
		
		//Ajax
		add_action("wp_ajax_gwptb_test_token", array($this, 'test_token_screen'));
		add_action("wp_ajax_gwptb_set_hook", array($this, 'set_hook_screen'));
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
        add_submenu_page('gwptb', __('Green WP Telegram Bot', 'gwptb'), __('Settings', 'gwptb'), 'manage_options', 'gwptb', array($this, 'dashboard_screen'));
		
		//Log
        add_submenu_page('gwptb', __('GWPTB Log', 'gwptb'), __('Log', 'gwptb'), 'manage_options', 'gwptb_log', array($this, 'log_screen'));
	}
	
	
	/** == Menu pages == **/
	public function dashboard_screen() {

		if( !current_user_can('manage_options') ) {
            wp_die(__('Sorry, but you do not have permissions to access this page.', 'gwptb'));
        }
		
		$token = get_option('gwptb_bot_token');
		$stage = (isset($_GET['stage'])) ? trim($_GET['stage']) : 'default';
		$btn = '';
		
		//button
		if(!empty($token) && ($stage == 'default')){
			$btn_url = add_query_arg(array('page' => 'gwptb', 'stage' => 'howto'), admin_url('admin.php'));			
			$btn = "<a href='{$btn_url}' class='page-title-action'>".__('How to create bot', 'gwptb')."</a>";
		}
		elseif(!empty($token) && ($stage != 'default')) {
			$btn_url = add_query_arg(array('page' => 'gwptb'), admin_url('admin.php'));			
			$btn = "<a href='{$btn_url}' class='page-title-action'>".__('Settings', 'gwptb')."</a>";
		}
		
		
		do_action('gwptb_dashboard_actions'); // Collapsible
		add_meta_box('gwptb_setup', __('Connection Setup', 'gwptb'), array($this, 'setup_metabox_screen'), 'toplevel_page_gwptb', 'normal');
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
				<div class="metabox-holder" id="gwptb-widgets">
					<div class="postbox-container" id="postbox-container-1">
						<?php do_meta_boxes('toplevel_page_gwptb', 'normal', null);?>
					</div>
				
					<div class="postbox-container" id="postbox-container-2">
						<!-- branding and links -->
					</div>
				</div>
			</div>
		<?php } ?>	
		
		<!-- settings -->
		<?php if($stage == 'default') { ?>
			<div class="gwptb-page-section settings">
				<form action='options.php' method='post'>
				<?php
					settings_fields( 'gwptb_settings' );
					do_settings_sections( 'gwptb_settings' );
					submit_button();
				?>
				</form>
				<div class="settings-side">
					<?php $nonce = wp_create_nonce('gwptb_test_token'); ?>
					<a id="gwptb_test_token" href='#' class='button button-secondary' data-nonce="<?php echo $nonce;?>"><?php _e('Test token', 'gwptb');?></a>
					<div id="gwptb_test_token-response" class="gwptb-test-response"></div>
				</div>
			</div>
		<?php } ?>
		
		</div><!-- close .wrap -->
	<?php
	}
	
	public function setup_metabox_screen(){
		$set_hook = get_option('gwptb_webhook', 0);
		
		$set_nonce = wp_create_nonce('gwptb_set_hook');
		$del_nonce = wp_create_nonce('gwptb_del_hook');
	?>
	<div class="gwptb-conncetion-setup">
		<a id="gwptb_set_hook" href='#' class='button button-primary<?php if($set_hook) { echo ' green'; };?>' data-nonce="<?php echo $set_nonce;?>"><span class='for-init'><?php _e('Set connection', 'gwptb');?></span><span class='for-green'><?php _e('Your Bot is connected', 'gwptb');?></span></a>
		
		<a id="gwptb_del_hook" href='#' class='button button-secondary<?php if(!$set_hook) { echo ' hidden'; };?>' data-nonce="<?php echo $set_nonce;?>"><?php _e('Remove connection', 'gwptb');?></a>
		
		<div class="gwptb-test-response">
			<div id="gwptb_set_hook-response"></div>
			<div id="gwptb_del_hook-response"></div>
		</div>
	</div>
	
	<div class="gwptb-conncetion-data">
		<table >
			<tbody>
				<tr>
					<th><?php _e('Bot Link', 'gwptb');?></th>
					<td>link</td>
				</tr>
				<tr>
					<th><?php _e('Received messages', 'gwptb');?></th>
					<td>xx</td>
				</tr>
				<tr>
					<th><?php _e('Send search results', 'gwptb');?></th>
					<td>xx</td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php 
	}
	
	
	public function log_screen() {

		if( !current_user_can('manage_options') ) {
            wp_die(__('You do not have permissions to access this page.', 'gwptb'));
        }
	?>
		<div class="wrap">
            <h2><?php _e('GWPTB Log', 'gwptb');?></h2>
            
			log table
		</div><!-- close .wrap -->
	<?php
	}
	
	
	/** == Ajax for testing functions **/	
	public function test_token_screen() {
		
		$msg = '';
		$result = array('type' => 'ok', 'data' => '');
		
		if(!wp_verify_nonce($_REQUEST['nonce'], "gwptb_test_token")) {		
			die('nonce error');
		}   
		
		//make getme request
		$bot = Gwptb_Self::get_instance();
		$test = $bot->self_test(); //log array
		
		//build response html
		if(isset($test['user_id']) && !empty($test['user_id'])){
			$msg = sprintf(__('Your token is connected with Bot: %s (@%s).', 'gwptb'), $test['user_fname'], $test['username']);
		}
		elseif(isset($test['error']) && !empty($test['error'])){
			$msg = sprintf(__('Your token is invalid. Error message: %s.', 'gwptb'), '<i>'.$test['error'].'</i>');
		}
		else {
			die('unexpectecd error');
		}
		
		$result['data'] = "<p>{$msg}</p>";		
		echo json_encode($result);
		die();
	}
	
	public function set_hook_screen() {
		
		$msg = '';
		$result = array('type' => 'ok', 'data' => '');
		
		if(!wp_verify_nonce($_REQUEST['nonce'], "gwptb_set_hook")) {		
			die('nonce error');
		}
		
		//make sethook request
		$bot = Gwptb_Self::get_instance();
		$test = $bot->set_webhook();
		
		//build response html
		$result['data'] = "<p>{$msg}</p>";		
		echo json_encode($result);
		die();
	}

	/** == Settings  fields == **/
	function settings_init(  ) { 
	
		register_setting( 'gwptb_settings', 'gwptb_bot_token');
		register_setting( 'gwptb_settings', 'gwptb_cert_path' );
	
		add_settings_section(
			'gwptb_access_section', 
			__( 'Access settings', 'gwptb' ), 
			array($this, 'access_section_callback'), 
			'gwptb_settings'
		);
	
		add_settings_field( 
			'gwptb_bot_token', 
			__( 'Bot Token', 'gwptb' ), 
			array($this, 'bot_token_render'), 
			'gwptb_settings', 
			'gwptb_access_section' 
		);
	
		add_settings_field( 
			'gwptb_cert_path', 
			__( 'Path to certificate file', 'gwptb' ), 
			array($this, 'cert_path_render'), 
			'gwptb_settings', 
			'gwptb_access_section' 
		);
	
	}


	function bot_token_render(  ) { 		
		
		$value = get_option('gwptb_bot_token'); 
	?>
		<input type='text' name='gwptb_bot_token' value='<?php echo $value; ?>' class="large-text">
	<?php	
	}
	
	
	function cert_path_render(  ) { 
		
		$value = get_option('gwptb_cert_path'); 
	?>
		<input type='text' name='gwptb_cert_path' value='<?php echo $value; ?>' class="large-text">
		<p class="description"><?php _e('For self-signed certificates - specify the path to it\'s public key file', 'gwptb');?></p>
	<?php	
	}


	function access_section_callback(  ) { 	
		//description or help information	
	}





	
	
} //class
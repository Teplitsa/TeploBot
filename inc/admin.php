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
		add_action("wp_ajax_gwptb_del_hook", array($this, 'del_hook_screen'));
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

		$links[] = '<a href="'.admin_url('admin.php?page=gwptb').'">'.__( 'Settings', 'gwptb').'</a>';

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
		
		$set_hook = get_option('gwptb_webhook', 0);
		$token = get_option('gwptb_bot_token', '');
		$stage = (isset($_GET['stage'])) ? trim($_GET['stage']) : 'default';
				
		//header elements
		$btn = '';
		$postbox_title = "<span class='postbot-title-txt'>".__('Connection Setup', 'gwptb')."</span>";
		
		//links
		if(!empty($token) && ($stage == 'default')){
			$btn_url = add_query_arg(array('page' => 'gwptb', 'stage' => 'howto'), admin_url('admin.php'));			
			$postbox_title = $postbox_title."<a href='{$btn_url}' class='postbot-title-link'>".__('How to create a bot', 'gwptb')."</a>";
		}
		elseif(!empty($token) && ($stage != 'default')) {
			$btn_url = add_query_arg(array('page' => 'gwptb'), admin_url('admin.php'));			
			$btn = "<a href='{$btn_url}' class='page-title-action'>".__('Settings', 'gwptb')."</a>";
		}
		
		
		//connection metablox init
		do_action('gwptb_dashboard_actions'); // Collapsible		
		add_meta_box('gwptb_setup', $postbox_title, array($this, 'setup_metabox_screen'), 'toplevel_page_gwptb', 'normal');
	?>	
		<div class="wrap">
            <h2><?php _e('Green WP Telegram Bot', 'gwptb');?><?php echo $btn;?></h2>
		
		<!-- intro section -->
		<?php
			if(empty($token) || $stage == 'howto') {
				$this->print_help_section();
				
			} elseif(!empty($token) && ($stage == 'default')){
		?>
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
				
				<?php if(!empty($token)) { ?>
				<div class="settings-side">
					<?php $nonce = wp_create_nonce('gwptb_test_token'); ?>
					<a id="gwptb_test_token" href='#' class='button button-secondary' data-nonce="<?php echo $nonce;?>"><?php _e('Test token', 'gwptb');?></a>
					<div id="gwptb_test_token-response" class="gwptb-test-response"></div>
				</div>
				<?php } ?>
				
			</div>
		<?php } ?>
		
		</div><!-- close .wrap -->
	<?php
	}
	
	protected function print_help_section(){
		
		$locale = get_locale();
		$path = GWPTB_PLUGIN_DIR.'assets/html/create-bot-'.$locale.'.html';
		if(!file_exists($path))
			$path = GWPTB_PLUGIN_DIR.'assets/html/сreate-bot.html';
		
		$html = file_get_contents($path);		
	?>
		<div class="gwptb-page-section help-section">
			<?php if($html){ echo $html; } ?>			
		</div>
	<?php
	}
	
	public function setup_metabox_screen(){
		
		//current action
		$action = (isset($_GET['action'])) ? trim($_GET['action']) : 'default';
		if($action != 'default')
			check_admin_referer('connection_setup', '_gwptbnonce');
		
		//process action and return some display args	
		$show_data = $this->do_setup_metabox_action($action);			
		
		
		//get hook state after action
		$hook = get_option('gwptb_webhook', 0);
	?>
	<div class="gwptb-conncetion-setup">
		<form id="gwptb-connection" action="admin.php" method="get">
			<input type="hidden" name="page" value="gwptb">
			<?php wp_nonce_field('connection_setup', '_gwptbnonce'); ?>
			
			<!-- set connection button -->
			<?php if($hook) { ?>				
				<div class="button button-primary green"><?php _e('Your Bot is connected', 'gwptb');?></div>
				<button type="submit" class="button button-secondary"><?php _e('Remove connection', 'gwptb');?></button>
				<input type="hidden" name="action" value="del_webhook">
				
			<?php } else { ?>
				<input type="hidden" name="action" value="set_webhook">
				<button type="submit" class="button button-primary"><?php _e('Set connection', 'gwptb');?></button>
				
			<?php }?>
			
			<!-- messages -->
			<?php if(isset($show_data['msg']) && !empty($show_data['msg'])){ ?>	
				<div class="gwptb-connection-response">
					<div class="<?php echo esc_attr($show_data['msg']['css']);?>"><p><?php echo $show_data['msg']['txt'];?></p></div>
				</div>
			<?php } ?>
		</form>
		<form id="gwptb-update-bot" action="admin.php" method="get">
			<input type="hidden" name="page" value="gwptb">
			<?php wp_nonce_field('connection_setup', '_gwptbnonce'); ?>
			<?php if($hook) { ?>
				<input type="hidden" name="action" value="update_bot_data">
				<button type="submit" class="button button-secondary"><?php _e('Update bot data', 'gwptb');?></button>
			<?php } ?>
		</form>
	</div>
	
	<?php if($hook) { ?>
		<div class="gwptb-connection-data">
			<table >
				<tbody>
					<!-- bot link -->
					<tr>
						<th><?php _e('Bot Link', 'gwptb');?></th>
						<?php $bot = Gwptb_Self::get_instance(); ?>
						<td><?php echo $bot->get_self_link();?></td>
					</tr>
					
					<!-- counts -->
					<?php if(isset($show_data['updates_total']) && (int)$show_data['updates_total'] > 0) { ?>
					<tr>
						<th><?php _e('Received messages', 'gwptb');?></th>
						<td><?php echo (int)$show_data['updates_total']; ?></td>
					</tr>
					<tr>
						<th><?php _e('Send links', 'gwptb');?></th>
						<td><?php echo (isset($show_data['returns_total'])) ? (int)$show_data['returns_total'] : 0; ?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	<?php
		}
	}
	
	protected function do_setup_metabox_action($action){
		
		$result = array();
		$stat = GWPTB_Stats::get_instance();
		
		if($action == 'update_bot_data'){
			
			$stat->update_stats();
			$bot = Gwptb_Self::get_instance();
			$bot->self_test();
		}
		elseif($action == 'set_webhook' || $action == 'del_webhook') {
			
			$msg = array('css' => 'gwptb-message', 'txt' => '');			
		
			//make sethook request
			$remove = ($action == 'del_webhook') ? true : false;
			$bot = Gwptb_Self::get_instance();
			$test = $bot->set_webhook($remove);
		
			//build reply
			if(isset($test['content']) && !empty($test['content'])){			
				$msg['txt'] = $test['content'];
				$msg['css'] .= ' success';
				
				$hook = ($action == 'set_webhook') ? 1 : 0;
				update_option('gwptb_webhook', $hook);
			}
			elseif(isset($test['error']) && !empty($test['error'])){
				$msg['txt'] = sprintf(__('Connection is invalid. Error message: %s.', 'gwptb'), '<i>'.$test['error'].'</i>');
				$msg['css'] .= ' fail';				
			}
			else {
				$msg['txt'] = __('Processing failed - try again later.', 'gwptb');
				$msg['css'] .= ' fail';
			}
		
			$result['msg'] = $msg;
			
			//create entry for bot in log in case there where no prev communications
			$bot->self_test();
		}
				
		//always get stat data to display
		$result = array_merge($result, $stat->get_stats());
		
		return $result;
	}
	
	/** display log table **/
	public function log_screen() {
				
		if( !current_user_can('manage_options') ) {
            wp_die(__('Sorry, but you do not have permissions to access this page.', 'gwptb'));
        }		
	?>
		<div class="wrap">
            <h2><?php _e('GWPTB Log', 'gwptb');?></h2>            
			<?php
				$list_table = gwpt_get_list_table();				
				$list_table->prepare_items(); 
				
				$list_table->views();
			?>
			<form id="gwptb-log-filter" method="get">
			<?php
				//$list_table->search_box(__('Search', 'gwptb'), 'log_item');
				$list_table->display();
			?>		
			</form>
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
				
		$result = array('type' => 'ok', 'data' => '');
		
		if(!wp_verify_nonce($_REQUEST['nonce'], "gwptb_set_hook")) {		
			die('nonce error');
		}
		
		//make sethook request
		$bot = Gwptb_Self::get_instance();
		$test = $bot->set_webhook();
		
		//build reply
		if(isset($test['content']) && !empty($test['content'])){			
			$result['data'] = "<p>".$test['content']."</p>";	
		}
		elseif(isset($test['error']) && !empty($test['error'])){
			$msg = sprintf(__('Connection is invalid. Error message: %s.', 'gwptb'), '<i>'.$test['error'].'</i>');
			$result['data'] = "<p>".$msg."</p>";
			$result['type'] = 'ok_with_error';
		}
		else {
			$result['data'] = "<p>".__('Processing failed - try again later.', 'gwptb')."</p>";
			$result['failed'] = 'failed';
		}
		
		//return results			
		echo json_encode($result);
		die();
	}
	
	public function del_hook_screen() {
				
		$result = array('type' => 'ok', 'data' => '');
		
		if(!wp_verify_nonce($_REQUEST['nonce'], "gwptb_del_hook")) {		
			die('nonce error');
		}
		
		//make sethook request
		$bot = Gwptb_Self::get_instance();
		$test = $bot->set_webhook(true);
		
		//build reply
		if(isset($test['content']) && !empty($test['content'])){			
			$result['data'] = "<p>".$test['content']."</p>";	
		}
		elseif(isset($test['error']) && !empty($test['error'])){
			$msg = sprintf(__('Connection is invalid. Error message: %s.', 'gwptb'), '<i>'.$test['error'].'</i>');
			$result['data'] = "<p>".$msg."</p>";
			$result['type'] = 'ok_with_error';
		}
		else {
			$result['data'] = "<p>".__('Processing failed - try again later.', 'gwptb')."</p>";
			$result['failed'] = 'failed';
		}
		
		//return results			
		echo json_encode($result);
		die();
	}

	/** == Settings  fields == **/
	function settings_init(  ) { 
		
		//sanitize callback	
		register_setting( 'gwptb_settings', 'gwptb_bot_token' );
		register_setting( 'gwptb_settings', 'gwptb_cert_path' );
		register_setting( 'gwptb_settings', 'gwptb_start_text');
		register_setting( 'gwptb_settings', 'gwptb_help_text' );
	
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
		
		//default tests
		add_settings_section(
			'gwptb_response_section', 
			__( 'Response settings', 'gwptb' ), 
			array($this, 'response_section_callback'), 
			'gwptb_settings'
		);
		
		add_settings_field( 
			'gwptb_start_text', 
			__( 'Start text for bot', 'gwptb' ), 
			array($this, 'start_text_render'), 
			'gwptb_settings', 
			'gwptb_response_section' 
		);
		
		add_settings_field( 
			'gwptb_help_text', 
			__( 'Help text for bot', 'gwptb' ), 
			array($this, 'help_text_render'), 
			'gwptb_settings', 
			'gwptb_response_section' 
		);
		
		
		//init table here otherwise columns will not be bind correctly to screen
		$list_table = gwpt_get_list_table();
	}


	public function bot_token_render() { 		
		
		$set_hook = get_option('gwptb_webhook', 0);
		$value = get_option('gwptb_bot_token'); 
		if($set_hook > 0){
			//token cann't be change until hook removeв
		?>
			<p><code><?php echo $value;?></code></p>
			<p class="description"><?php _e('Your bot connected to Telegram. Token cann\'t be updated until connection removed.', 'gwptb');?></p>
		<?php
		}
		else {
	?>
		<input type='text' name='gwptb_bot_token' value='<?php echo $value; ?>' class="large-text">
	<?php
		}
	}
	
	
	public function cert_path_render() { 
		
		$value = get_option('gwptb_cert_path'); 
	?>
		<input type='text' name='gwptb_cert_path' value='<?php echo $value; ?>' class="large-text">
		<p class="description"><?php _e('For self-signed certificates - specify the path to it\'s public key file', 'gwptb');?></p>
	<?php	
	}
	
	public function start_text_render(){
		$value = get_option('start_text_render', __('Hello, %%uername%%. Let\'s find something useful. Send me _your term_ to perform a search, type /help to get help.', 'gwptb')); 
	?>
		<textarea name='start_text_render'class="large-text"><?php echo $value; ?></textarea>
		<p class="description"><?php _e('Welcom text for first-time user %%uername%% will be replaced with actual name', 'gwptb');?></p>
	<?php	
	}
	
	public function help_text_render(){
		$value = get_option('gwptb_help_text', __('I can help you to find something useful at %%home%%. Send me _your term_ to perform a search.', 'gwptb')); 
	?>
		<textarea name='gwptb_help_text'class="large-text"><?php echo $value; ?></textarea>
		<p class="description"><?php _e('Text showing as a response to /help command, %%home%% will be replaced with link to homepage', 'gwptb');?></p>
	<?php	
	}
	

	public function access_section_callback(  ) { 	
		//description or help information	
	}


	public function response_section_callback(  ) { 	
		//description or help information	
	}


	
	
} //class

/**
 * 	List table instance should be global
 * 	(☉_☉) ಥ_ಥ (☉_☉)
 **/

$gwpt_list_table = null;
function gwpt_get_list_table(){
	global $gwpt_list_table;
	
	if(null === $gwpt_list_table){
		$gwpt_list_table = new Gwptb_Log_List_Table();
	}
	
	return $gwpt_list_table;
}
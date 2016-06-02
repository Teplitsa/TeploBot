<?php
if(!defined('ABSPATH')) die; // Die if accessed directly



class Gwptb_Admin {
	
	private static $instance = NULL; //instance store
	public $github_link = 'https://github.com/Teplitsa/TeploBot';
	
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
			
			$links[] = '<a href="'.$this->github_link.'">GitHub</a>';		  
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
        add_menu_page(__('TeploBot', 'gwptb'), __('TeploBot', 'gwptb'), 'manage_options', 'gwptb', array($this, 'dashboard_screen'), 'dashicons-nametag');

        // Dashboard
        add_submenu_page('gwptb', __('TeploBot', 'gwptb'), __('Settings', 'gwptb'), 'manage_options', 'gwptb', array($this, 'dashboard_screen'));
		
		//Log
        add_submenu_page('gwptb', __('TeploBot Log', 'gwptb'), __('Log', 'gwptb'), 'manage_options', 'gwptb_log', array($this, 'log_screen'));
		
		do_action('gwptb_admin_menu_setup');
		
		//init table here otherwise columns will not be bind correctly to screen
		$list_table = gwpt_get_list_table();
	}
	
	
	/** == Menu pages == **/
	
	public function dashboard_screen() {

		if( !current_user_can('manage_options') ) {
            wp_die(__('Sorry, but you do not have permissions to access this page.', 'gwptb'));
        }
		
		$set_hook = get_option('gwptb_webhook', 0);
		$token = get_option('gwptb_bot_token', '');
		$stage = (isset($_GET['stage'])) ? trim($_GET['stage']) : 'default';
		$postbox_title = '';
		
		//metabox headers
		if(empty($token) || $stage == 'howto') {			
			$postbox_title = "<span class='postbot-title-txt'>".__('How to create a bot', 'gwptb')."</span>";
			
			if($stage == 'howto') {
				$btn_url = add_query_arg(array('page' => 'gwptb'), admin_url('admin.php'));
				$postbox_title = $postbox_title."<a href='{$btn_url}' class='postbot-title-link'>".__('Settings', 'gwptb')."</a>";
			}
		}
		elseif(!empty($token) && ($stage == 'default')){
			
			$postbox_title = "<span class='postbot-title-txt'>".__('Connection Setup', 'gwptb')."</span>";
			$btn_url = add_query_arg(array('page' => 'gwptb', 'stage' => 'howto'), admin_url('admin.php'));
			$postbox_title = $postbox_title."<a href='{$btn_url}' class='postbot-title-link'>".__('How to create a bot', 'gwptb')."</a>";
		}
		
		//metaboxes init
		do_action('gwptb_dashboard_actions'); // Collapsible
		
		if(empty($token) || $stage == 'howto') {			
			add_meta_box('gwptb_howto', $postbox_title, array($this, 'howto_metabox_screen'), 'toplevel_page_gwptb', 'normal');
		}
		elseif(!empty($token) && ($stage == 'default')){			
			add_meta_box('gwptb_setup', $postbox_title, array($this, 'setup_metabox_screen'), 'toplevel_page_gwptb', 'normal');
		}
		
	?>	
		<div class="wrap">
            <h2><?php _e('TeploBot - Telegram Bot for WP', 'gwptb');?></h2>
		
		<!-- metabox -->		
		<div class="gwptb-page-section">
			<div class="metabox-holder" id="gwptb-widgets">
				<div class="postbox-container " id="postbox-container-1">
					<?php do_meta_boxes('toplevel_page_gwptb', 'normal', null);?>
					
					<!-- settings -->
					<?php if($stage == 'default') { ?>
						<div class="gwptb-settings">
							<form action='options.php' method='post'>
							<?php
								settings_fields( 'gwptb_settings' );
								do_settings_sections( 'gwptb_settings' );
								submit_button();
							?>
							</form>
						</div>
					<?php } ?>
				</div>
			
				<div class="postbox-container" id="postbox-container-2">
					<?php if(empty($token) || $stage == 'howto') { ?>
					<?php $src = GWPTB_PLUGIN_BASE_URL.'assets/img/botfather.png';?>
						<h4><?php _e('Example', 'gwptb');?></h4>
						<div class="gwptb-help-screenshot"><img src="<?php echo esc_url($src);?>" alt="<?php _e('Botfather dialogue screenshot', 'gwptb');?>"></div>
					<?php } elseif(!empty($token) && ($stage == 'default')){ 
							$tst_util = Gwptb_TestUtil::get_instance();
							$tst_util->tst_sidebar_screen();
						}
					?>		
				</div>
			</div>
		</div>
		
		</div><!-- close .wrap -->
	<?php
	}
		
	public function howto_metabox_screen() {
		
		$locale = get_locale();
		$path = GWPTB_PLUGIN_DIR.'assets/html/create-bot-'.$locale.'.html';
		if(!file_exists($path))
			$path = GWPTB_PLUGIN_DIR.'assets/html/сreate-bot.html';
		
		$html = file_get_contents($path);		
	?>
		<div class="gwptb-help-info"><?php if($html){ echo $html; } ?></div>		
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
			<div class="gwptb-cs-button-row">
			<?php if($hook) { ?>				
				<div class="btn-col-green">
					<div class="button-green"><?php _e('Your Bot is connected', 'gwptb');?></div>
				</div>
				<div class="btn-col-grey">
					<button type="submit" class="button-grey"><?php _e('Remove connection', 'gwptb');?></button>
				</div>
				<input type="hidden" name="action" value="del_webhook">
				
			<?php } else { ?>
				<input type="hidden" name="action" value="set_webhook">
				<button type="submit" class="button button-primary"><?php _e('Set connection', 'gwptb');?></button>				
			<?php }?>
			</div>
			
			<!-- messages -->
			<?php if(isset($show_data['msg']) && !empty($show_data['msg'])){ ?>	
				<div class="gwptb-cs-response">
					<div class="<?php echo esc_attr($show_data['msg']['css']);?>"><p><?php echo $show_data['msg']['txt'];?></p></div>
				</div>
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
						<th><?php _e('Sent links', 'gwptb');?></th>
						<td><?php echo (isset($show_data['returns_total'])) ? (int)$show_data['returns_total'] : 0; ?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			
			<form id="gwptb-update-bot" action="admin.php" method="get">
				<input type="hidden" name="page" value="gwptb">
				<?php wp_nonce_field('connection_setup', '_gwptbnonce'); ?>
				
					<input type="hidden" name="action" value="update_bot_data">
					<button type="submit" class="button button-secondary"><?php _e('Update stats', 'gwptb');?></button>
				
			</form>
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
            <h2><?php _e('TeploBot Log', 'gwptb');?></h2>            
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
	
	
	
	/** == Settings  fields == **/
	function settings_init(  ) { 
		
		//sanitize callback	
		register_setting( 'gwptb_settings', 'gwptb_bot_token',  array('GWPTB_Filters', 'sanitize_url'));
		register_setting( 'gwptb_settings', 'gwptb_cert_key',   array('GWPTB_Filters', 'sanitize_string'));
		register_setting( 'gwptb_settings', 'gwptb_start_text', array('GWPTB_Filters', 'sanitize_html'));
		register_setting( 'gwptb_settings', 'gwptb_help_text',  array('GWPTB_Filters', 'sanitize_html'));
		register_setting( 'gwptb_settings', 'gwptb_custom_commands', array($this, 'custom_commands_prepare_filter'));
		
		//sections
		add_settings_section(
			'gwptb_bot_section', 
			__( 'Bot settings', 'gwptb' ), 
			array($this, 'bot_section_callback'), 
			'gwptb_settings'
		);
				
		//fields
		add_settings_field( 
			'gwptb_bot_token', 
			__( 'Bot Token', 'gwptb' ), 
			array($this, 'bot_token_render'), 
			'gwptb_settings', 
			'gwptb_bot_section' 
		);
					
		add_settings_field( 
			'gwptb_start_text', 
			__( 'Start text for bot', 'gwptb' ), 
			array($this, 'start_text_render'), 
			'gwptb_settings', 
			'gwptb_bot_section' 
		);
		
		add_settings_field( 
			'gwptb_help_text', 
			__( 'Help text for bot', 'gwptb' ), 
			array($this, 'help_text_render'), 
			'gwptb_settings', 
			'gwptb_bot_section' 
		);
		
		add_settings_field( 
			'gwptb_cert_key', 
			__( 'Public key', 'gwptb' ), 
			array($this, 'cert_key_render'), 
			'gwptb_settings', 
			'gwptb_bot_section' 
		);		
		
		add_settings_field( 
			'gwptb_custom_commands', 
			__( 'Custom commands', 'gwptb' ), 
			array($this, 'custom_commands_render'), 
			'gwptb_settings', 
			'gwptb_bot_section' 
		);		
	}


	public function bot_token_render() { 		
		
		$set_hook = get_option('gwptb_webhook', 0);
		$value = get_option('gwptb_bot_token');
		
		$bot = Gwptb_Self::get_instance(); 
		$bot_name = $bot->get_self_name();
		$bot_name = (empty($bot_name)) ? __('Anonymous', 'gwptb') : "<span class='gwptb-bot-name'>".$bot_name."</span>";
		
		if($set_hook > 0){ 	//token cann't be change until hook removed
		?>
		<input type="hidden" name="gwptb_bot_token" value="<?php echo $value;?>">
		<p><code class="gwptb-bot-token"><?php echo $value;?></code></p>
		<p class="description"><?php printf(__('Your bot - %s - connected to Telegram. Remove connection to update token.', 'gwptb'), $bot_name);?></p>
		<?php
		}
		else {
	?>
		<input type='text' name='gwptb_bot_token' value='<?php echo $value; ?>' class="large-text">
	<?php
		}
	
		if(!empty($value)) { ?>
			<div class="gwptb-token-test">
				<?php $nonce = wp_create_nonce('gwptb_test_token'); ?>
				<a id="gwptb_test_token" href='#' class='button button-secondary' data-nonce="<?php echo $nonce;?>"><?php _e('Test token', 'gwptb');?></a>
				<div id="gwptb_test_token-response" class="gwptb-test-response"></div>
			</div>
		<?php } 
	}
		
	
	public function start_text_render(){
		$default = sprintf(__('Hello, %%username%%. Let\'s find something useful. Send me %s to perform a search, type /help to get help.', 'gwptb'), "<i>".__('your term', 'gwptb')."</i>");
		$value = get_option('gwptb_start_text', $default); 
	?>
		<textarea name='gwptb_start_text' class="large-text" rows="3"><?php echo $value; ?></textarea>
		<p class="description"><?php _e('Text showing as a response to /start command. %%username%% will be replaced with actual name.', 'gwptb');?></p>
		<p class="description"><?php _e('Command should be added in dialog with @BotFather', 'gwptb');?></p>
	<?php	
	}
	
	public function help_text_render(){
		$default = sprintf(__('I can help you to find something useful at %%home%%. Send me %s to perform a search.', 'gwptb'), "<i>".__('your term', 'gwptb')."</i>");
		$value = get_option('gwptb_help_text', $default); 
	?>
		<textarea name='gwptb_help_text' class="large-text" rows="3"><?php echo $value; ?></textarea>
		<p class="description"><?php _e('Text showing as a response to /help command. %%home%% will be replaced with link to homepage.', 'gwptb');?></p>
		<p class="description"><?php _e('Command should be added in dialog with @BotFather', 'gwptb');?></p>
	<?php	
	}
	
	public function cert_key_render() {
		
		$value = get_option('gwptb_cert_key');
		$help_link = "<a href='https://core.telegram.org/bots/self-signed' target='_blank'>".__('Telegram instructions', 'gwptb')."</a>";
	?>
		<textarea name='gwptb_cert_key' class="large-text" rows="3"><?php echo $value; ?></textarea>
		<p class="description"><?php printf(__('For self-signed certificates: copy the content of public key. %s', 'gwptb'), $help_link);?></p>
	<?php
	}
	
	public function custom_commands_render(){
		
		$value = get_option('gwptb_custom_commands'); 
	?>
		<table class="gwptb-custom-command-table">
			<thead>
			<tr class="gwptb-cc-row header">
				<th class="gwptb-cc-cell command"><?php _e('Command word', 'gwptb');?></th>
				<th class="gwptb-cc-cell post_type"><?php _e('Post types (comma-separated)', 'gwptb');?></th>
				<th class="gwptb-cc-cell title"><?php _e('Title for results', 'gwptb');?></th>
			</tr>
			</thead>
			<tbody>
		<?php for($i =0 ; $i < 5; $i++) { ?>	
			<tr class="gwptb-cc-row">
				<td class="gwptb-cc-cell command">					
					<input type="text" name="gwptb_custom_commands[<?php echo $i;?>][command]" id="gwptb_cc_command-<?php echo $i;?>" value="<?php echo (isset($value[$i]['command'])) ? esc_attr($value[$i]['command']) : '';?>"/>
				</td>
				
				<td class="gwptb-cc-cell post_type">
					<input type="text" name="gwptb_custom_commands[<?php echo $i;?>][post_type]" id="gwptb_cc_post_types-<?php echo $i;?>" value="<?php echo (isset($value[$i]['command'])) ? esc_attr($value[$i]['post_type']) : '';?>"/>
				</td>				
				<td class="gwptb-cc-cell title">
					<input type="text" name="gwptb_custom_commands[<?php echo $i;?>][title]" id="gwptb_cc_title-<?php echo $i;?>" value="<?php echo (isset($value[$i]['command'])) ? esc_attr($value[$i]['title']) : '';?>"/>
				</td>
			</tr>
		<?php }?>
			</tbody>
		</table>
		<p class="description"><?php _e('Add up to 5 commands to send recent posts in chat', 'gwptb');?></p>
	<?php
	}
	
	public function custom_commands_prepare_filter($option) {
		
		if(!is_array($option))
			return '';
		
		$result = array();
		$defaults = array('command' => '', 'post_type' => '', 'title' => '');
		foreach($option as $i => $opt){
			
			$opt = wp_parse_args($opt, $defaults);
			$result[$i]['command'] = preg_replace('/[^a-zA-Z0-9\s]/u', '', $opt['command']);
			$result[$i]['post_type'] = GWPTB_Filters::sanitize_string($opt['post_type']);
			$result[$i]['title'] = GWPTB_Filters::sanitize_string($opt['title']);
		}
		
		return $result;
	}

	public function bot_section_callback(  ) { 	
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

<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

class Gwptb_TestUtil {
	
	private static $instance = NULL; //instance store
	protected $support_emails = array();
	
	private function __construct() {
		
		$this->support_emails = array(//put real ones
			'anna.ladoshkina@te-st.ru ', 
			'support@te-st.ru',
			'suvorov.gleb@gmail.com'
		);
		
		add_action('gwptb_admin_menu_setup', array($this, 'admin_menu_setup'), 5); // Add the options page and menu item
		
		//ajax for feedback form
		add_action('wp_ajax_gwptb_send_feedback', array($this, 'gwptb_send_feedback_screen'));
	}
		
	
	/** instance */
    public static function get_instance(){
        
        if (NULL === self :: $instance)
			self :: $instance = new self;
					
		return self :: $instance;
    }
	
	
	/** Question page **/
	public function admin_menu_setup() {		
        add_submenu_page('gwptb', __('Feedback', 'gwptb'), __('Feedback', 'gwptb'), 'manage_options', 'gwptb_feedback', array($this, 'feedback_screen'));
	}
	
	public function feedback_screen() {
		
		if( !current_user_can('manage_options') ) {
            wp_die(__('Sorry, but you do not have permissions to access this page.', 'gwptb'));
        }
		
		$user = wp_get_current_user();
		$github_link = Gwptb_Admin::get_instance()->github_link;
		$github_link = "<a href='{$github_link}' target='_blank'>".__('create an issue on Github', 'gwptb')."</a>";
	?>
	<div class="wrap">
		<h2><?php _e('Send us a feedback', 'gwptb');?></h2>

		<div class="gwptb-feedback-description">
			<p><?php _e('Found a bug? Need a feature?', 'gwptb'); ?></p>
			<p><?php printf(__('Please, %s or send us a message with the following form', 'gwptb'), $github_link); ?></p>
		</div>    

		<div class="feedback-columns">
			<div class="gwptb-feedback-form">
				<span id="feedback-loader"></span>
				
				<form id="feedback" action="#" method="post">
					
					<fieldset class="gwptb-ff-field">						
						<input id="feedback-topic" type="text" name="topic" placeholder="<?php _e('Topic', 'gwptb');?>" class="regular-text req">
						<div id="feedback-topic-error" class="gwptb-ff-field-error" style="display: none;"></div>
					</fieldset>
					
					<fieldset class="gwptb-ff-field">						
						<input id="feedback-name"  type="text" name="name" placeholder="<?php _e("Your name", 'gwptb');?>" value="<?php echo $user->display_name;?>" class="regular-text req">
						<div id="feedback-name-error" class="gwptb-ff-field-error" style="display: none;"></div>
					</fieldset>
					
					<fieldset class="gwptb-ff-field">						
						<input id="feedback-email"  type="email" name="email" placeholder="<?php _e('Your email', 'gwptb');?>" value="<?php echo $user->user_email;?>" class="regular-text req">
						<div id="feedback-email-error" class="gwptb-ff-field-error" style="display: none;"></div>
					</fieldset>
					
					<fieldset class="gwptb-ff-field">
						<label for="feedback-text"><?php _e('Your message:', 'gwptb');?></label>
						<textarea id="feedback-text" name="text" class="large-text req"></textarea>
						<div id="feedback-text-error" class="gwptb-ff-field-error" style="display: none;" ></div>
					</fieldset>
					
					<fieldset class="gwptb-ff-field gwptb-submit">
						<input type="hidden" id="nonce" value="<?php echo wp_create_nonce('gwptb_feedback_sending');?>">
						<input type="submit" class="button-primary" value="<?php _e('Submit');?>">
					</fieldset>
				</form>
				
				<div id="message-ok" class="gwptb-ff-msg ok" style="display: none;">
					<p><?php _e('Thank you! Your message has been sent successfully. We will be in touch soon.', 'gwptb');?></p>
				</div>
				<div id="message-error" class="gwptb-ff-msg wrong" style="display: none;">
					<p><?php _e("Sorry, but the message can't be sent. Please check your mail server settings.", 'gwptb');?></p>
				</div>
				
			</div>
			<div class="feedback-sidebar"><?php self::itv_info_widget();?></div>
		</div>
		
	</div>
	<?php
	}
	
	/** Feedback page processing */
    public function gwptb_send_feedback_screen() {

        if( !wp_verify_nonce($_POST['nonce'], 'gwptb_feedback_sending') ) {
            die('1');
        }
		
		
        $_POST['topic'] = filter_var(trim($_POST['topic']), FILTER_SANITIZE_STRING);
        $_POST['name']  = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
        $_POST['email'] = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $_POST['text']  = filter_var(strip_tags(trim($_POST['text'])), FILTER_SANITIZE_SPECIAL_CHARS);

        if( !$_POST['name'] || !$_POST['email'] || !$_POST['text'] || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ) {
            die('2');
        }

        add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));

        $res = true;
        foreach((array)$this->support_emails as $email) {

            $email = trim($email);
            if( !$email || !filter_var($email, FILTER_VALIDATE_EMAIL) )
                continue;

            $res &= wp_mail(
                $email, __('TeploBot: new incoming feedback', 'gwptb'),
                sprintf(
                "Добрый день!<br><br>
                Поступила новая обратная связь от пользователя TeploBot.<br><br>
                <strong>Тема:</strong> %s<br>
                <strong>Имя пользователя:</strong> %s<br>
                <strong>Email:</strong> %s<br>
                <strong>Текст сообщения:</strong><br>%s<br><br>
                ---------------- Технические данные сайта пользователя --------------<br><br>
                <strong>Cайт пользователя:</strong> <a href='%s'>%s</a> (IP: %s)<br>
                <strong>Версия WP:</strong> %s<br>
                <strong>Версия TeploBot:</strong> %s<br>
                <strong>Параметр admin_email:</strong> %s<br>
                <strong>Язык:</strong> %s (кодировка: %s)<br>
                <strong>ПО веб-сервера:</strong> %s<br>
                <strong>Браузер пользователя:</strong> %s",
                    $_POST['topic'], $_POST['name'], $_POST['email'], nl2br($_POST['text']),
                    home_url(), get_bloginfo('name'), $_SERVER['SERVER_ADDR'],
                    get_bloginfo('version'), GWPTB_VERSION, get_bloginfo('admin_email'),
                    get_bloginfo('language'), get_bloginfo('charset'),
                    $_SERVER['SERVER_SOFTWARE'], $_SERVER['HTTP_USER_AGENT']
                ),
                array('From: '.get_bloginfo('name').' <no_reply@gwptb.te-st.ru>',)
            );
        }

        // Reset content-type to avoid conflicts (http://core.trac.wordpress.org/ticket/23578):
        remove_filter('wp_mail_content_type', array($this, 'set_html_content_type'));

        die($res ? '0' : '3');
    }
	
	
    function set_html_content_type() {
        return 'text/html';
    }
	
	/** Teplitsa sidebar **/
	public function tst_sidebar_screen() {
		
		$github_link = Gwptb_Admin::get_instance()->github_link;
		$test_link = '<a href="https://te-st.ru/" target="_blank">'.__('Teplitsa. Technologies for Social Good', 'gwptb').'</a>';
	?>
		<div id="gwptb-card">
            <h4><?php _e('TeploBot - Telegram Bot for WP', 'gwptb');?></h4>
            <p><?php _e('TeploBot is a simple chatbot for Telegram with green effect', 'gwptb');?>.</p>
            <p>
                <?php printf(__('Developed by %s', 'gwptb'), $test_link);?>.
            </p>
            
            <ul class="gwptb-ref-links">
                <li><a href="https://gwptb.te-st.ru" target='_blank'><?php _e('Plugin\'s website', 'gwptb');?></a></li>
				<li><a href="<?php echo $github_link;?>" target='_blank'><?php _e('GitHub', 'gwptb');?></a></li>
                <li><a href="<?php echo admin_url('admin.php?page=gwptb_feedback');?>"><?php _e('Ask a question', 'gwptb');?></a></li>                
            </ul>
		</div>

	<?php self::itv_info_widget();
	}
	
	public static function itv_info_widget() {

		$locale = get_locale();
		if($locale != 'ru_RU') { // Only in Russian for now
			return;
		}
	
		$domain = parse_url(home_url());
		$itv_url = esc_url("https://itv.te-st.ru/?gwptb=".$domain['host']);?>
	
		<div id="itv-card">
			<div class="itv-logo"><a href="<?php echo $itv_url;?>" target="_blank"><img src="<?php echo esc_url(GWPTB_PLUGIN_BASE_URL.'assets/img/logo-itv.png');?>"></a></div>
	
			<p>Вам нужна помощь в настройке плагина или создании бота? Опубликуйте задачу на платформе <a href="<?php echo $itv_url;?>" target="_blank">it-волонтер.</a></p>
	
			<p><a href="<?php echo $itv_url;?>" target="_blank" class="button">Опубликовать задачу</a></p>
		</div>
	<?php
	}
	
} //class

Gwptb_TestUtil::get_instance();

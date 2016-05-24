<?php
/**
 * Class for CSSJS handling
 **/

class GWPTB_CssJs {
	
	private static $_instance = null;	
	private $manifest = null;
	
	private function __construct() {
			
		//load admin scripts
		add_action('admin_enqueue_scripts',  array($this, 'load_admin_styles'), 30);
		add_action('admin_enqueue_scripts',  array($this, 'load_admin_scripts'), 30);
	}
	
	public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if( !self::$_instance ) {
            self::$_instance = new self;
        }
		
        return self::$_instance;
    }
	
	/** revisions **/
	private function get_manifest() {
		
		if(null === $this->manifest) {
			$manifest_path = GWPTB_PLUGIN_DIR.'assets/rev/rev-manifest.json';

			if (file_exists($manifest_path)) {
				$this->manifest = json_decode(file_get_contents($manifest_path), TRUE);
			} else {
				$this->manifest = array();
			}
		}
		
		return $this->manifest;
	}
	
	
	public function get_rev_filename($filename) {
		
		$manifest = $this->get_manifest();
		if (array_key_exists($filename, $manifest)) {
			return $manifest[$filename];
		}
	
		return $filename;
	}
	
	/* frontend CSSJS */
	public function load_styles() {
		
	}
	
	public function load_scripts() {
		global $wp_query;
		
		
	}
	
	
	/* admin CSSJS */
	public function load_admin_styles() {
		
		$screen = get_current_screen();
		if(false === strpos($screen->id, 'gwptb'))
			return;
			
		wp_enqueue_style(
			'gwptb-admin-css',
			GWPTB_PLUGIN_BASE_URL.'assets/rev/'.$this->get_rev_filename('admin.css'),
			array(),
			null
		);				
	}
	
	public function load_admin_scripts() {
		
		$screen = get_current_screen();
		if(false === strpos($screen->id, 'gwptb'))
			return;
			
		// jQuery
		$script_dependencies[] = 'jquery'; //adjust gulp if we want it in footer
		
		// scripts
		wp_enqueue_script(
			'gwptb-admin-js',
			GWPTB_PLUGIN_BASE_URL.'assets/rev/'.$this->get_rev_filename('bundle.js'),
			$script_dependencies,
			null,
			true
		);
				
		wp_localize_script('gwptb-admin-js', 'gwptb', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'field_required' => __('This field is required', 'gwptb'),
            'email_invalid' => __('Please enter a correct email', 'gwptb')
		));
	}
	
} //class

GWPTB_CssJs::get_instance();

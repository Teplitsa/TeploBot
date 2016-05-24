<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

if(!class_exists('WP_List_Table')) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/** Class to present Log table **/
class Gwptb_Log_List_Table extends WP_List_Table  {
	
	protected $log_per_page = 100;
	
	
	/** constructor **/
	public function __construct( $args = array() ) {
		parent::__construct( array(
			'singular' => 'log_item',
			'plural' => 'log_items',
			'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
		) );		
	}
	
	/** capabilities **/
	public function ajax_user_can() {
		return current_user_can('manage_options');
	}
	
	
	public function prepare_items() {
		global $wpdb;
		
		//inits
		$table_name = Gwptb_Core::get_log_tablename();
		
		//order
		$orderby = array();		
		$orderby_raw = (isset($_REQUEST['orderby'])) ? trim($_REQUEST['orderby']) : 'time';
		$order = (isset($_REQUEST['order'])) ? trim($_REQUEST['order']) : 'desc';
		
		if(!in_array($orderby_raw, array_keys($this->get_sortable_columns()))){
			$orderby_raw = 'time';
		}
		
		$order = ($order == 'asc') ? 'ASC' : 'DESC';
		$orderby[] = "$orderby_raw $order";
		$orderby[] = 'id DESC';
		$orderby = implode(', ', $orderby);
		
		//paging args	
		$per_page = $this->get_items_per_page('gwptb_log_per_page', $this->log_per_page);
		$offset = 0;
		if(isset($_REQUEST['paged']) && (int)$_REQUEST['paged'] > 1){
			$offset = ((int)$_REQUEST['paged'] - 1)*$per_page;
		}		
			
		//finally sql
		$sql = "SELECT * FROM {$table_name} ORDER BY {$orderby} LIMIT {$offset},{$per_page}";
				
		//get items
		$this->items = $wpdb->get_results($sql);
		
		//paging
		$this->set_pagination_args( array(
			'total_items' => $this->count_total_items(),
			'per_page' => $per_page
		) );
	}
	
	/* count total items in log */
	protected function count_total_items(){
		global $wpdb;
		
		$table_name = Gwptb_Core::get_log_tablename();		
		return $wpdb->get_var("SELECT COUNT(id) FROM {$table_name}");
	}
	
	/** table */
	public function get_columns() {
		return array(
			//'cb'         => '<input type="checkbox" />',
			'time'		=> __( 'Date', 'gwptb' ),
			'action'	=> __( 'Action', 'gwptb' ),
			'method'	=> __( 'Method', 'gwptb' ),
			'user'		=> __( 'User', 'gwptb' ),
			'content'	=> __( 'Content', 'gwptb' ),
			//'logdata'	=> __( 'Full item', 'gwptb') popup with full data
		);
	}
	
	protected function get_sortable_columns() {
		return array(
			'time'   => 'time',
			'action' => 'action',
			'method' => 'method',
		);
	}
	
	protected function column_time( $item ) {
		
		if(isset($item->time))
			echo $this->item_wrap(date_i18n('d.m.Y H:i', strtotime($item->time)), $item);
	}
	
	protected function column_user( $item ) {
		
	$user = array();
		
		if(isset($item->user_fname) && !empty($item->user_fname))
			$user[] = $item->user_fname;
		
		if(isset($item->user_lname) && !empty($item->user_lname))
			$user[] = $item->user_lname;
			
		if(isset($item->username) && !empty($item->username)){
			$user[] = (empty($user)) ? '@'.$item->username : '(@'.$item->username.')';
		}
		
		$user = apply_filters('gwptb_print_string', implode(' ', $user));
		
		echo $this->item_wrap($user, $item);
	}
	
	protected function column_content( $item ) {
				
		if(isset($item->error) && !empty($item->error)){
			echo "<div class='gwptb-log-error'>";
			echo apply_filters('gwptb_print_text', $item->error);
			echo "</div>";
		}
		elseif(isset($item->content) && !empty($item->content)) {
			
			$c = (mb_strlen($item->content) > 140) ? mb_substr($item->content, 0, 140).'...' : $item->content;			
			$css = 'gwptb-log-content';
			if($item->action == 'response')
				$css .= ' row-response';
			
			$c = apply_filters('gwptb_print_text', $c);
			if(empty($c))
				$c = __('Invalid content in received message', 'gwptb');
			
			echo "<div class='{$css}'>{$c}</div>";			
		}
	}
	
	protected function column_default( $item, $column_name ) {
		
		if(in_array($column_name, array('action', 'method')) && isset($item->$column_name)){
			echo $this->item_wrap(apply_filters('gwptb_print_string', $item->$column_name), $item);
			
		}
		else {
			do_action( 'gwptb_log_table_custom_column', $column_name, $item );
			
		}		
	}
	
	protected function item_wrap($txt, $item){
		
		if($item->action != 'response')
			return $txt;
		
		return "<span class='row-response'>{$txt}</span>";
	}
	
} //class
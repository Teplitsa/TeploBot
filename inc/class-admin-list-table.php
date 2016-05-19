<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

if(!class_exists('WP_List_Table')) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/** Class to present Log table **/
class Gwptb_Log_List_Table extends WP_List_Table  {
	
	protected $log_per_page = 20;
	
	
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
		
		//get params about paging and sorting
		$orderby = 'time';
		$order = 'DESC';
		$offset = 0;
		$per_page = $this->log_per_page;
		
		$table_name = Gwptb_Core::get_log_tablename();
		$sql = "SELECT * FROM {$table_name} ORDER BY {$orderby} {$order} LIMIT {$offset},{$per_page}";
		
		
		//here we should get log items
		$this->items = $wpdb->get_results($sql);
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
			'logdata'	=> __( 'Full item', 'gwptb')
		);
	}
	
	protected function get_sortable_columns() {
		return array(
			'date'   => 'date',
			'action' => 'action',
			'method' => 'method',
		);
	}
	
	protected function column_date( $item ) {
		
		echo 'date';
	}
	
	protected function column_default( $item, $column_name ) {
		
		echo 'smth';
	}
	
	
	
} //class
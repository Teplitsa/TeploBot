<?php
/*
Plugin Name: TeploBot - Telegram Bot for WP
Description: Simple Telegram Bot for your site with green effect
Version: 1.1
Author: Teplitsa
Author URI: https://te-st.ru/
Text Domain: gwptb
Domain Path: /lang
Contributors:
	Gleb Suvorov aka gsuvorov (suvorov.gleb@gmail.com) - Idea, UX
	Anna Ladoshkina aka foralien (webdev@foralien.com) - Development
	

License URI: http://www.gnu.org/licenses/gpl-2.0.txt
License: GPLv2 or later
	Copyright (C) 2012-2014 by Teplitsa of Social Technologies (https://te-st.ru).

	GNU General Public License, Free Software Foundation <http://www.gnu.org/licenses/gpl-2.0.html>

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

if(!defined('ABSPATH')) die; // Die if accessed directly

// Plugin version:
if( !defined('GWPTB_VERSION') )
    define('GWPTB_VERSION', '1.1');
	
// Plugin DIR, with trailing slash:
if( !defined('GWPTB_PLUGIN_DIR') )
    define('GWPTB_PLUGIN_DIR', plugin_dir_path( __FILE__ ));

// Plugin URL:
if( !defined('GWPTB_PLUGIN_BASE_URL') )
    define('GWPTB_PLUGIN_BASE_URL', plugin_dir_url(__FILE__));
	
// Plugin ID:
if( !defined('GWPTB_PLUGIN_BASE_NAME') )
    define('GWPTB_PLUGIN_BASE_NAME', plugin_basename(__FILE__));

// Environment checks. If some failed, deactivate the plugin to save WP from possible crushes:
if( !defined('PHP_VERSION') || version_compare(PHP_VERSION, '5.3.0', '<') ) {

    echo '<div id="message" class="error"><p><strong>Внимание:</strong> версия PHP ниже <strong>5.3.0</strong>. Лейка нуждается в PHP хотя бы <strong>версии 5.3.0</strong>, чтобы работать корректно. Плагин будет деактивирован.<br /><br />Пожалуйста, направьте вашему хостинг-провайдеру запрос на повышение версии PHP для этого сайта.</p> <p><strong>Warning:</strong> your PHP version is below <strong>5.3.0</strong>. Leyka needs PHP <strong>v5.3.0</strong> or later to work. Plugin will be deactivated.<br /><br />Please contact your hosting provider to upgrade your PHP version.</p></div>';

    die();
}	
	
load_plugin_textdomain('gwptb', false, '/'.basename(GWPTB_PLUGIN_DIR).'/lang');



/** Init **/
require_once(plugin_dir_path(__FILE__).'inc/core.php');
require_once(plugin_dir_path(__FILE__).'inc/functions.php');
require_once(plugin_dir_path(__FILE__).'inc/class-gwptb.php');
require_once(plugin_dir_path(__FILE__).'inc/class-cssjs.php');
require_once(plugin_dir_path(__FILE__).'inc/class-stat.php');
require_once(plugin_dir_path(__FILE__).'inc/class-filters.php');
$tplb = Gwptb_Core::get_instance();

if(is_admin()){
	require_once(plugin_dir_path(__FILE__).'inc/class-admin-list-table.php');
	require_once(plugin_dir_path(__FILE__).'inc/class-admin-tstutil.php');
	require_once(plugin_dir_path(__FILE__).'inc/admin.php');
	$gwptb_admin = Gwptb_Admin::get_instance();
}


/** Hooks **/
register_activation_hook( __FILE__, array( 'Gwptb_Core', 'on_activation' ));
register_deactivation_hook(__FILE__, array( 'Gwptb_Core', 'on_deactivation' ));


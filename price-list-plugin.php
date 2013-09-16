<?php
/*
 * Plugin Name: Price List Plugin
 * Plugin URI:
 * Description: Alows you to create price-lists and embed them with a shortcode
 * Version: 1.0
 * Author: raucher
 * Author URI: https://github.com/raucher
 * License: GPLv2
 */

/********************************************************************************
    Copyright YEAR PLUGIN_AUTHOR_NAME (email : PLUGIN AUTHOR EMAIL)
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
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
********************************************************************************/

require_once 'includes/PriceListPlugin.php';

defined('PLP_URL') or define('PLP_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', 'load_plp_text_domain');
function load_plp_text_domain(){
    load_plugin_textdomain('plp-domain', false, dirname(plugin_basename(__FILE__)).'/translation/');
}

add_action('init', array(new PriceListPlugin, 'init'));
<?php
/*
* Plugin Name: Feed Shortcode
* Plugin URI:
* Description: A [feed] shortcode plugin.
* Version: 0.1
* Author: UBC CMS
* Author URI:
*
*
* This program is free software; you can redistribute it and/or modify it under the terms of the GNU
* General Public License as published by the Free Software Foundation; either version 2 of the License,
* or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
* even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
* You should have received a copy of the GNU General Public License along with this program; if not, write
* to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

class CTLT_Feed_Shortcode {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
	
	}
	
	function has_shortcode( $shortcode ) {
		global $shortcode_tags;
		return ( in_array( $shortcode, $shortcode_tags ) ? true: false );
	}

	function add_shortcode() {
	
	}

}

new CTLT_Feed_Shortcode();
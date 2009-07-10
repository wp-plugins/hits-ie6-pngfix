<?php
/*
	Plugin Name: HITS- IE6 PNGFix
	Version: 1
	Author: Adam Erstelle
	Author URI: http://www.homeitsolutions.ca
	Plugin URI: http://www.homeitsolutions.ca/websites/wordpress-plugins/ie6-png-fix
	Description: Adds IE6 Compatability for PNG transparency, courtesy of http://www.twinhelix.com/css/iepngfix/

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/ 

function hits_pngfix_echo() 
{
    $workdir = get_bloginfo('wpurl') . "/" . basename(WP_CONTENT_DIR) . "/plugins/hits-pngfix";
    echo "<style type='text/css'>img, div { behavior: url($workdir/iepngfix.htc) }</style>";
}

add_action('wp_head', 'hits_pngfix_echo');
?>
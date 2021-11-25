<?php

/**
 * Plugin Name: Agileware Current Menu Block
 * Plugin URI: https://github.com/agileware/agileware-current-menu-block
 * Description: Provides a Block to display the siblings and parent of the current menu item in a configured menu
 * Author: Agileware
 * Author URI: https://agileware.com.au
 * Version: 1.1.0
 * Text Domain: agileware-current-menu-block
 *
 * Copyright Agileware Pty Ltd
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// All functionality is in the CurrentMenuBlock class
include_once( 'lib/CurrentMenuBlock.php' );

$plugin = \Agileware\CurrentMenuBlock::getInstance();

add_action( 'init', [ $plugin, 'register' ] );

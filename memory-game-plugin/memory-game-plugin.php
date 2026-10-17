<?php
/**
 * Plugin Name: Memory Game
 * Description: Embeds the Memory quiz game via [memory_game] and stores results through a REST endpoint.
 * Version:     0.1.0
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * Text Domain: memory-game
 */

defined( 'ABSPATH' ) || exit;

define( 'MEMORY_GAME_VERSION', '0.1.0' );
define( 'MEMORY_GAME_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEMORY_GAME_URL', plugin_dir_url( __FILE__ ) );

require_once MEMORY_GAME_DIR . 'includes/class-db.php';
require_once MEMORY_GAME_DIR . 'includes/class-rest.php';
require_once MEMORY_GAME_DIR . 'includes/class-shortcode.php';

register_activation_hook( __FILE__, array( 'Memory_Game_DB', 'install' ) );

add_action( 'plugins_loaded', array( 'Memory_Game_DB', 'maybe_upgrade' ) );
add_action( 'rest_api_init', array( 'Memory_Game_REST', 'register_routes' ) );
add_action( 'init', array( 'Memory_Game_Shortcode', 'register' ) );

<?php
defined( 'ABSPATH' ) || exit;

class Memory_Game_Shortcode {
	public static function register() {
		add_shortcode( 'memory_game', array( __CLASS__, 'render' ) );
		// Register only; enqueued when the shortcode is rendered.
		wp_register_style( 'memory-game', MEMORY_GAME_URL . 'assets/memory-game.css', array(), MEMORY_GAME_VERSION );
		wp_register_script( 'memory-game', MEMORY_GAME_URL . 'assets/memory-game.js', array(), MEMORY_GAME_VERSION, true );
	}

	public static function render() {
		wp_enqueue_style( 'memory-game' );
		wp_enqueue_script( 'memory-game' );
		wp_localize_script(
			'memory-game',
			'MemoryGameConfig',
			array(
				'restUrl' => esc_url_raw( rest_url( Memory_Game_REST::NAMESPACE_ . '/results' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
		return '<div class="memory-game" id="memory-game-root"></div>';
	}
}

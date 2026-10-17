<?php
defined( 'ABSPATH' ) || exit;

class Memory_Game_REST {
	const NAMESPACE_    = 'memory-game/v1';
	const MAX_PER_HOUR  = 60;

	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE_,
			'/results',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_result' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
				'args'                => array(
					'session_id'   => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => array( __CLASS__, 'sanitize_session_id' ),
						'validate_callback' => function ( $v ) {
							return is_string( $v ) && '' !== self::sanitize_session_id( $v );
						},
					),
					'round_number' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1, 'maximum' => 100 ),
					'duration_ms'  => array( 'required' => true, 'type' => 'integer', 'minimum' => 0, 'maximum' => 86400000 ),
					'attempts'     => array( 'required' => true, 'type' => 'integer', 'minimum' => 0, 'maximum' => 10000 ),
					'question_id'  => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'question'     => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'answer'       => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'is_correct'   => array( 'required' => true, 'type' => 'boolean' ),
				),
			)
		);
	}

	public static function sanitize_session_id( $value ) {
		return substr( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ), 0, 64 );
	}

	/**
	 * Public endpoint: require the REST nonce (issued by the shortcode) and
	 * apply a simple per-IP rate limit. This is not full anti-fraud.
	 */
	public static function check_permission( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'memory_game_bad_nonce', __( 'Invalid or missing nonce.', 'memory-game' ), array( 'status' => 403 ) );
		}

		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'memory_game_rl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= self::MAX_PER_HOUR ) {
			return new WP_Error( 'memory_game_rate_limited', __( 'Too many submissions.', 'memory-game' ), array( 'status' => 429 ) );
		}
		set_transient( $key, $n + 1, HOUR_IN_SECONDS );

		return true;
	}

	public static function create_result( WP_REST_Request $request ) {
		$id = Memory_Game_DB::insert_result(
			array(
				'session_id'   => $request['session_id'],
				'round_number' => (int) $request['round_number'],
				'duration_ms'  => (int) $request['duration_ms'],
				'attempts'     => (int) $request['attempts'],
				'question_id'  => $request['question_id'],
				'question'     => $request['question'],
				'answer'       => $request['answer'],
				'is_correct'   => rest_sanitize_boolean( $request['is_correct'] ),
			)
		);

		if ( 0 === $id ) {
			return new WP_Error( 'memory_game_duplicate', __( 'Round already submitted.', 'memory-game' ), array( 'status' => 409 ) );
		}
		if ( false === $id ) {
			return new WP_Error( 'memory_game_db_error', __( 'Could not save result.', 'memory-game' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array( 'id' => $id ), 201 );
	}
}

<?php
defined( 'ABSPATH' ) || exit;

class Memory_Game_DB {
	const DB_VERSION = '1';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'memory_game_results';
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		// dbDelta is picky: two spaces after PRIMARY KEY, one field per line.
		$sql = "CREATE TABLE $table (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  session_id varchar(64) NOT NULL DEFAULT '',
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  round_number smallint(5) unsigned NOT NULL DEFAULT 0,
  duration_ms int(10) unsigned NOT NULL DEFAULT 0,
  attempts smallint(5) unsigned NOT NULL DEFAULT 0,
  question_id varchar(64) NOT NULL DEFAULT '',
  question text NOT NULL,
  answer text NOT NULL,
  is_correct tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY session_round (session_id,round_number),
  KEY user_id (user_id)
) $charset;";

		dbDelta( $sql );
		update_option( 'memory_game_db_version', self::DB_VERSION );
	}

	public static function maybe_upgrade() {
		if ( get_option( 'memory_game_db_version' ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * @return int|false Insert id, 0 on duplicate round, false on error.
	 */
	public static function insert_result( array $data ) {
		global $wpdb;

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . self::table() . ' WHERE session_id = %s AND round_number = %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$data['session_id'],
				$data['round_number']
			)
		);
		if ( $exists ) {
			return 0;
		}

		$ok = $wpdb->insert(
			self::table(),
			array(
				'session_id'   => $data['session_id'],
				'user_id'      => get_current_user_id(),
				'round_number' => $data['round_number'],
				'duration_ms'  => $data['duration_ms'],
				'attempts'     => $data['attempts'],
				'question_id'  => $data['question_id'],
				'question'     => $data['question'],
				'answer'       => $data['answer'],
				'is_correct'   => $data['is_correct'] ? 1 : 0,
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		return $ok ? (int) $wpdb->insert_id : false;
	}
}

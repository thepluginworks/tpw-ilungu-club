<?php

/**
 * Event to Menu relationship helpers.
 *
 * Manages a compact mapping table between events and menus and basic queries
 * to assign and fetch relationships.
 *
 * @since 1.0.0
 */
class TPW_Event_Menu_Rel {

    /**
     * Create the relationship table if missing.
     *
     * @since 1.0.0
     * @return void
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tpw_event_menu_relationship';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            event_id INT NOT NULL,
            menu_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_menu (event_id, menu_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Assign or update a menu for an event.
     *
     * @since 1.0.0
     * @param int $event_id
     * @param int $menu_id
     * @return void
     */
    public static function assign_menu_to_event($event_id, $menu_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tpw_event_menu_relationship';

        $wpdb->replace($table_name, [
            'event_id' => $event_id,
            'menu_id' => $menu_id,
            'created_at' => current_time('mysql')
        ]);
    }

    /**
     * Assign a Club menu to an iLungu Events WordPress event post.
     *
     * This is intentionally separate from assign_menu_to_event(), whose
     * domain-ID interface remains available for existing consumers.
     *
     * @since 2.12.1
     * @param int $event_post_id WordPress tpw_event post ID.
     * @param int $menu_id Existing Club menu ID.
     * @return array{success: bool, code: string, message: string, event_post_id: int, event_id: int, menu_id: int}
     */
    public static function assign_menu_to_event_post( $event_post_id, $menu_id ) {
        global $wpdb;

        $event_post_id = absint( $event_post_id );
        $menu_id       = absint( $menu_id );

        if ( $event_post_id <= 0 ) {
            return self::assignment_result( false, 'invalid_event_post', 'The event post ID is invalid.', 0, 0, $menu_id );
        }

        $event_post = get_post( $event_post_id );
        if ( ! $event_post || 'tpw_event' !== $event_post->post_type ) {
            return self::assignment_result( false, 'invalid_event_post', 'The event post is not an iLungu Events event.', $event_post_id, 0, $menu_id );
        }

        if ( $menu_id <= 0 ) {
            return self::assignment_result( false, 'invalid_menu', 'The selected menu ID is invalid.', $event_post_id, 0, 0 );
        }

        $prefix    = $wpdb->prefix;
        $event_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT event_id
                FROM {$prefix}tpw_events
                WHERE post_id = %d
                ORDER BY event_id ASC
                LIMIT 2",
                $event_post_id
            )
        );

        if ( 1 !== count( $event_ids ) ) {
            return self::assignment_result( false, 'invalid_event', 'The event does not resolve to exactly one Events record.', $event_post_id, 0, $menu_id );
        }

        $event_id = absint( $event_ids[0] );
        if ( $event_id <= 0 ) {
            return self::assignment_result( false, 'invalid_event', 'The event does not resolve to a valid Events record.', $event_post_id, 0, $menu_id );
        }

        $menu_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM {$prefix}tpw_menus
                WHERE id = %d
                LIMIT 1",
                $menu_id
            )
        );

        if ( $menu_id !== absint( $menu_exists ) ) {
            return self::assignment_result( false, 'invalid_menu', 'The selected Club menu does not exist.', $event_post_id, $event_id, $menu_id );
        }

        $relationship_table = $prefix . 'tpw_event_menu_relationship';
        if ( false === $wpdb->query( "LOCK TABLES {$relationship_table} WRITE" ) ) {
            return self::assignment_result( false, 'assignment_lock_failed', 'The menu assignment could not obtain the required database lock.', $event_post_id, $event_id, $menu_id );
        }

        $result = null;

        try {
            $relationships = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, menu_id
                    FROM {$relationship_table}
                    WHERE event_id = %d
                    ORDER BY id ASC
                    LIMIT 2",
                    $event_id
                ),
                ARRAY_A
            );

            if ( ! is_array( $relationships ) ) {
                $result = self::assignment_result( false, 'relationship_read_failed', 'The existing menu relationship could not be read.', $event_post_id, $event_id, $menu_id );
            } elseif ( 1 < count( $relationships ) ) {
                $result = self::assignment_result( false, 'ambiguous_relationship', 'The event has multiple menu relationships and must be repaired before reassignment.', $event_post_id, $event_id, $menu_id );
            } elseif ( 1 === count( $relationships ) ) {
                $updated = $wpdb->update(
                    $relationship_table,
                    array( 'menu_id' => $menu_id ),
                    array(
                        'id'       => absint( $relationships[0]['id'] ),
                        'event_id' => $event_id,
                    ),
                    array( '%d' ),
                    array( '%d', '%d' )
                );

                if ( false === $updated ) {
                    $result = self::assignment_result( false, 'assignment_update_failed', 'The menu relationship could not be updated.', $event_post_id, $event_id, $menu_id );
                } else {
                    $result = self::assignment_result( true, 'updated', 'The event menu was updated.', $event_post_id, $event_id, $menu_id );
                }
            } else {
                $inserted = $wpdb->insert(
                    $relationship_table,
                    array(
                        'event_id'   => $event_id,
                        'menu_id'    => $menu_id,
                        'created_at' => current_time( 'mysql' ),
                    ),
                    array( '%d', '%d', '%s' )
                );

                if ( false === $inserted ) {
                    $result = self::assignment_result( false, 'assignment_insert_failed', 'The menu relationship could not be created.', $event_post_id, $event_id, $menu_id );
                } else {
                    $result = self::assignment_result( true, 'created', 'The event menu was assigned.', $event_post_id, $event_id, $menu_id );
                }
            }
        } finally {
            $unlocked = $wpdb->query( 'UNLOCK TABLES' );
        }

        if ( false === $unlocked ) {
            return self::assignment_result( false, 'assignment_unlock_failed', 'The menu assignment completed but the database lock could not be released.', $event_post_id, $event_id, $menu_id );
        }

        return $result;
    }

    /**
     * Build a consistent result for post-based menu assignments.
     *
     * @since 2.12.1
     * @param bool   $success Assignment outcome.
     * @param string $code Machine-readable outcome code.
     * @param string $message Human-readable outcome message.
     * @param int    $event_post_id WordPress event post ID.
     * @param int    $event_id Events-domain event ID.
     * @param int    $menu_id Club menu ID.
     * @return array{success: bool, code: string, message: string, event_post_id: int, event_id: int, menu_id: int}
     */
    private static function assignment_result( $success, $code, $message, $event_post_id, $event_id, $menu_id ) {
        return array(
            'success'       => $success,
            'code'          => $code,
            'message'       => $message,
            'event_post_id' => $event_post_id,
            'event_id'      => $event_id,
            'menu_id'       => $menu_id,
        );
    }

    /**
     * Get the assigned menu ID for an event.
     *
     * @since 1.0.0
     * @param int $event_id
     * @return int|null
     */
    public static function get_menu_for_event($event_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tpw_event_menu_relationship';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT menu_id FROM $table_name WHERE event_id = %d",
            $event_id
        ));
    }
}

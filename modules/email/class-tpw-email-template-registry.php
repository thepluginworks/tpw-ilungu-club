<?php
/**
 * Registry for email templates. Plugins register their templates here during init.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPW_Email_Template_Registry {
    /** @var array<string,array> */
    protected static $templates = [];

    /** @var array<string,array<int,string>> */
    protected static $group_aliases = [];

    /**
     * Register legacy group names for a canonical email-template group.
     *
     * Group aliases affect discovery and admin grouping only. Template
     * overrides remain keyed by their stable template key.
     *
     * @param string       $canonical_group Canonical group identifier.
     * @param array|string $legacy_groups Legacy group identifier(s).
     * @return void
     */
    public static function register_group_aliases( $canonical_group, $legacy_groups ) {
        $canonical_group = sanitize_key( (string) $canonical_group );
        $legacy_groups   = is_array( $legacy_groups ) ? $legacy_groups : array( $legacy_groups );

        if ( '' === $canonical_group ) {
            return;
        }

        if ( ! isset( self::$group_aliases[ $canonical_group ] ) ) {
            self::$group_aliases[ $canonical_group ] = array();
        }

        foreach ( $legacy_groups as $legacy_group ) {
            $legacy_group = sanitize_key( (string) $legacy_group );
            if ( '' !== $legacy_group && $canonical_group !== $legacy_group ) {
                self::$group_aliases[ $canonical_group ][ $legacy_group ] = $legacy_group;
            }
        }
    }

    /**
     * Return a canonical group and its accepted legacy aliases.
     *
     * @param string $group Group identifier.
     * @return array<int,string>
     */
    public static function get_group_identities( $group ) {
        $group = sanitize_key( (string) $group );

        foreach ( self::$group_aliases as $canonical_group => $legacy_groups ) {
            if ( $group === $canonical_group || isset( $legacy_groups[ $group ] ) ) {
                return array_merge( array( $canonical_group ), array_values( $legacy_groups ) );
            }
        }

        return '' === $group ? array() : array( $group );
    }

    /**
     * Register a template definition.
     *
     * @param array $t { key, group, label, default_subject, default_body, editable_subject, editable_body, placeholders }
     */
    public static function register_template( $t ) {
        if ( ! is_array( $t ) ) return;
        $key_raw = isset( $t['key'] ) ? (string) $t['key'] : '';
        $key = strtolower( preg_replace( '/[^a-z0-9_-]/i', '', $key_raw ) );
        if ( ! $key ) return;
        $group = isset( $t['group'] ) ? sanitize_key( (string) $t['group'] ) : 'core';
        $legacy_groups = isset( $t['legacy_groups'] ) ? $t['legacy_groups'] : array();
        self::register_group_aliases( $group, $legacy_groups );
        $label = isset( $t['label'] ) ? sanitize_text_field( $t['label'] ) : $key;
        $default_subject = isset( $t['default_subject'] ) ? (string) $t['default_subject'] : '';
        $default_body    = isset( $t['default_body'] ) ? (string) $t['default_body'] : '';
        $editable_subject = ! empty( $t['editable_subject'] );
        $editable_body    = ! empty( $t['editable_body'] );
        $placeholders     = isset( $t['placeholders'] ) && is_array( $t['placeholders'] ) ? $t['placeholders'] : [];

        self::$templates[ $key ] = [
            'key'              => $key,
            'group'            => $group,
            'legacy_groups'    => array_values( array_diff( self::get_group_identities( $group ), array( $group ) ) ),
            'label'            => $label,
            'default_subject'  => $default_subject,
            'default_body'     => $default_body,
            'editable_subject' => (bool) $editable_subject,
            'editable_body'    => (bool) $editable_body,
            'placeholders'     => $placeholders,
        ];
    }

    /**
     * Get a single registered template by key.
     */
    public static function get( $key ) {
        $key = strtolower( preg_replace( '/[^a-z0-9_-]/i', '', (string) $key ) );
        return isset( self::$templates[ $key ] ) ? self::$templates[ $key ] : null;
    }

    /**
     * Get all registered templates grouped by group key.
     * @return array<string,array> group => [ templates ]
     */
    public static function all_grouped() {
        $grouped = [];
        foreach ( self::$templates as $t ) {
            $g = $t['group'] ?: 'core';
            if ( ! isset( $grouped[ $g ] ) ) $grouped[ $g ] = [];
            $grouped[ $g ][] = $t;
        }
        ksort( $grouped );
        return $grouped;
    }

    /**
     * Get all registered templates flat list.
     */
    public static function all() {
        return self::$templates;
    }

    /**
     * Get registered templates for one canonical or legacy group identity.
     *
     * @param string $group Group identifier.
     * @return array<string,array>
     */
    public static function get_by_group( $group ) {
        $identities = self::get_group_identities( $group );
        $templates  = array();

        foreach ( self::$templates as $key => $template ) {
            if ( in_array( $template['group'], $identities, true ) ) {
                $templates[ $key ] = $template;
            }
        }

        return $templates;
    }
}

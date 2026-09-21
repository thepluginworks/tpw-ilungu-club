<?php
/**
 * Canonical and legacy payment-log source identity registry.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TPW_Payment_Source_Registry {
	/** @var array<string,array<int,string>> */
	protected static $aliases = array();

	/**
	 * Register legacy source identifiers for a canonical source.
	 *
	 * @param string       $canonical_source Canonical source identifier.
	 * @param array|string $legacy_sources Legacy source identifier(s).
	 * @return void
	 */
	public static function register_source_aliases( $canonical_source, $legacy_sources ) {
		$canonical_source = sanitize_key( (string) $canonical_source );
		$legacy_sources   = is_array( $legacy_sources ) ? $legacy_sources : array( $legacy_sources );

		if ( '' === $canonical_source ) {
			return;
		}

		if ( ! isset( self::$aliases[ $canonical_source ] ) ) {
			self::$aliases[ $canonical_source ] = array();
		}

		foreach ( $legacy_sources as $legacy_source ) {
			$legacy_source = sanitize_key( (string) $legacy_source );
			if ( '' !== $legacy_source && $canonical_source !== $legacy_source ) {
				self::$aliases[ $canonical_source ][ $legacy_source ] = $legacy_source;
			}
		}
	}

	/**
	 * Normalize a source to its canonical logical identity.
	 *
	 * @param string $source Source identifier.
	 * @return string
	 */
	public static function normalize_source( $source ) {
		$source = sanitize_key( (string) $source );

		foreach ( self::get_aliases() as $canonical_source => $legacy_sources ) {
			if ( $source === $canonical_source || isset( $legacy_sources[ $source ] ) ) {
				return $canonical_source;
			}
		}

		return $source;
	}

	/**
	 * Return all persisted identities for a logical source.
	 *
	 * @param string $source Canonical or legacy source identifier.
	 * @return array<int,string>
	 */
	public static function get_source_identities( $source ) {
		$canonical_source = self::normalize_source( $source );
		$aliases          = self::get_aliases();

		if ( '' === $canonical_source ) {
			return array();
		}

		return isset( $aliases[ $canonical_source ] )
			? array_merge( array( $canonical_source ), array_values( $aliases[ $canonical_source ] ) )
			: array( $canonical_source );
	}

	/**
	 * Normalize the payment-log source on an array or object row.
	 *
	 * @param array|object $row Payment-log row.
	 * @return array|object
	 */
	public static function normalize_log_row( $row ) {
		if ( is_array( $row ) && isset( $row['plugin'] ) ) {
			$row['plugin'] = self::normalize_source( $row['plugin'] );
		} elseif ( is_object( $row ) && isset( $row->plugin ) ) {
			$row->plugin = self::normalize_source( $row->plugin );
		}

		return $row;
	}

	/**
	 * Return registered aliases after allowing consumer registration by filter.
	 *
	 * @return array<string,array<int,string>>
	 */
	protected static function get_aliases() {
		$aliases = apply_filters( 'tpw_core_payment_source_aliases', self::$aliases );

		return is_array( $aliases ) ? $aliases : self::$aliases;
	}
}
<?php
/**
 * Local-only fixture loaded by the Playwright WordPress test bootstrap.
 *
 * The bootstrap must define ILUNGU_CLUB_PLAYWRIGHT_TESTING before requiring
 * this file. Contributions are request-scoped and require the test query flag.
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'ILUNGU_CLUB_PLAYWRIGHT_TESTING' ) || ! ILUNGU_CLUB_PLAYWRIGHT_TESTING ) {
	return;
}

if (
	( ! isset( $_GET['tpw_club_playwright_contributions'] ) || '1' !== sanitize_text_field( wp_unslash( $_GET['tpw_club_playwright_contributions'] ) ) )
	&& ( ! isset( $_COOKIE['tpw_club_playwright_contributions'] ) || '1' !== sanitize_text_field( wp_unslash( $_COOKIE['tpw_club_playwright_contributions'] ) ) )
) {
	return;
}

if ( ! class_exists( 'TPW_FlexiEvent', false ) ) {
	class TPW_FlexiEvent {}
}

add_filter(
	'tpw_core_club_administration_contributions',
	static function( $contributions ) {
		if ( ! is_array( $contributions ) ) {
			$contributions = [];
		}

		$contributions[] = [
			'key'        => 'synthetic-club',
			'contexts'   => [ 'frontend', 'admin' ],
			'capability' => 'manage_options',
			'overview_card' => [
				'title'        => 'Synthetic Club Tool',
				'metric'       => 'Ready',
				'status_label' => 'Active',
				'status_tone'  => 'success',
				'description'  => 'Playwright fixture contribution.',
				'primary_action' => [ 'label' => 'Manage Synthetic Tool', 'url' => home_url( '/club-management/?workspace=synthetic-club&view=settings&tab=rsvp' ) ],
				'secondary_action' => [ 'label' => 'Synthetic Settings', 'url' => home_url( '/?synthetic=settings' ) ],
			],
			'extend_actions' => [
				'catalogue_key' => 'events',
				'actions'       => [
					[ 'label' => 'Manage Synthetic Events', 'url' => home_url( '/?synthetic=events' ) ],
					[ 'label' => 'Synthetic Event Settings', 'url' => home_url( '/?synthetic=event-settings' ) ],
				],
			],
			'workspace' => [
				'key'      => 'synthetic-club',
				'label'    => 'Synthetic Club Workspace',
				'frontend' => [
					'render_callback' => static function() {
						if ( current_user_can( 'manage_options' ) ) {
							echo '<section class="tpw-club-playwright-workspace"><h1>Synthetic Club Frontend Workspace</h1></section>';
						}
					},
				],
				'admin' => [
					'capability'    => 'manage_options',
					'page_callback' => static function() {
						if ( ! current_user_can( 'manage_options' ) ) {
							wp_die( esc_html__( 'Access denied.', 'tpw-core' ) );
						}

						echo '<div class="wrap"><h1>Synthetic Club Admin Workspace</h1></div>';
					},
				],
			],
		];
		$contributions[] = [
			'key'        => 'ilungu-lodge-meetings',
			'contexts'   => [ 'frontend' ],
			'capability' => 'manage_options',
			'overview_card' => [
				'title'          => 'iLungu Lodge Meetings',
				'primary_action' => [ 'label' => 'Manage Lodge Meetings', 'url' => home_url( '/rsvp_submissions/' ) ],
			],
			'workspace' => [
				'key'      => 'ilungu-lodge-meetings',
				'label'    => 'iLungu Lodge Meetings',
				'frontend' => [
					'render_callback' => static function() {
						if ( current_user_can( 'manage_options' ) ) {
							echo '<section class="tpw-club-playwright-workspace"><h1>Synthetic Lodge Meetings Workspace</h1></section>';
						}
					},
				],
			],
		];
		$contributions[] = [
			'key'        => 'synthetic-frontend-only',
			'contexts'   => [ 'frontend' ],
			'capability' => 'manage_options',
			'overview_card' => [
				'title'          => 'Synthetic Frontend Only',
				'primary_action' => [ 'label' => 'Open Frontend Only', 'url' => home_url( '/' ) ],
			],
		];
		$contributions[] = [
			'key'        => 'synthetic-admin-only',
			'contexts'   => [ 'admin' ],
			'capability' => 'manage_options',
			'overview_card' => [
				'title'          => 'Synthetic Admin Only',
				'primary_action' => [ 'label' => 'Open Admin Only', 'url' => admin_url() ],
			],
		];
		$contributions[] = [
			'key'        => 'wave1b-synthetic-duplicate',
			'contexts'   => [ 'frontend', 'admin' ],
			'capability' => 'manage_options',
			'overview_card' => [
				'title'          => 'Synthetic Duplicate First',
				'primary_action' => [ 'label' => 'Open First Duplicate', 'url' => home_url( '/?synthetic=first' ) ],
			],
		];
		$contributions[] = [
			'key'        => 'wave1b-synthetic-duplicate',
			'contexts'   => [ 'frontend', 'admin' ],
			'capability' => 'manage_options',
			'overview_card' => [
				'title'          => 'Synthetic Duplicate Second',
				'primary_action' => [ 'label' => 'Open Second Duplicate', 'url' => home_url( '/?synthetic=second' ) ],
			],
		];
		$contributions[] = [
			'key'        => 'synthetic-legacy-alias',
			'contexts'   => [ 'frontend', 'admin' ],
			'capability' => 'manage_options',
			'overview_card' => [
				'title'          => 'Synthetic Legacy Alias',
				'primary_action' => [ 'label' => 'Open Legacy Alias', 'url' => home_url( '/?synthetic=legacy-alias' ) ],
			],
		];
		$contributions[] = [
			'key'         => 'synthetic-canonical-alias',
			'legacy_keys' => [ 'synthetic-legacy-alias' ],
			'contexts'    => [ 'frontend', 'admin' ],
			'capability'  => 'manage_options',
			'overview_card' => [
				'title'          => 'Synthetic Canonical Alias',
				'primary_action' => [ 'label' => 'Open Canonical Alias', 'url' => home_url( '/?synthetic=canonical-alias' ) ],
			],
		];
		$contributions[] = [
			'key'        => 'synthetic-after-malformed',
			'contexts'   => [ 'frontend', 'admin' ],
			'capability' => 'manage_options',
			'overview_card' => [ 'title' => 'Malformed Synthetic Contribution' ],
		];
		$contributions[] = [
			'key'        => 'synthetic-after-malformed',
			'contexts'   => [ 'frontend', 'admin' ],
			'capability' => 'manage_options',
			'overview_card' => [
				'title'          => 'Synthetic Valid After Malformed',
				'primary_action' => [ 'label' => 'Open Valid Contribution', 'url' => home_url( '/?synthetic=valid' ) ],
			],
		];
		$contributions[] = [
			'key'        => 'synthetic-invalid-context',
			'contexts'   => [ 'invalid-context' ],
			'capability' => 'manage_options',
			'overview_card' => [
				'title'          => 'Unsupported Context Contribution',
				'primary_action' => [ 'label' => 'Open Unsupported Context', 'url' => home_url( '/' ) ],
			],
		];
		$contributions[] = [
			'key'        => 'synthetic-unauthorized',
			'contexts'   => [ 'frontend', 'admin' ],
			'capability' => 'do_not_allow',
			'overview_card' => [
				'title'          => 'Unauthorized Synthetic Contribution',
				'primary_action' => [ 'label' => 'Open Unauthorized Contribution', 'url' => home_url( '/' ) ],
			],
		];

		return $contributions;
	}
);

function tpw_club_playwright_render_frontend_workspace() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<section class="tpw-club-playwright-workspace"><h1>Synthetic Club Frontend Workspace</h1></section>';
}

function tpw_club_playwright_render_admin_workspace() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Access denied.', 'tpw-core' ) );
	}

	echo '<div class="wrap"><h1>Synthetic Club Admin Workspace</h1></div>';
}
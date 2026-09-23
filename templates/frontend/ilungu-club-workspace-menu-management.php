<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$workspace = isset( $dashboard['menu_management_workspace'] ) && is_array( $dashboard['menu_management_workspace'] ) ? $dashboard['menu_management_workspace'] : [];
$dashboard_url = isset( $workspace['dashboard_url'] ) ? (string) $workspace['dashboard_url'] : '';
$dedicated_url = isset( $workspace['dedicated_url'] ) ? (string) $workspace['dedicated_url'] : '';
$legacy_url = isset( $workspace['legacy_url'] ) ? (string) $workspace['legacy_url'] : '';
?>
<section id="ilungu-club-menu-management-overview" class="ilungu-club-dashboard__hero tpw-flexiclub-dashboard__hero tpw-card ilungu-club-control-workspace__hero tpw-flexiclub-control-workspace__hero">
	<div class="ilungu-club-dashboard__brand-row tpw-flexiclub-dashboard__brand-row ilungu-club-control-workspace__hero-head tpw-flexiclub-control-workspace__hero-head">
		<div class="ilungu-club-dashboard__welcome tpw-flexiclub-dashboard__welcome ilungu-club-control-workspace__hero-copy tpw-flexiclub-control-workspace__hero-copy">
			<h2><?php echo esc_html( $workspace['hero_title'] ?? __( 'Menu Management Workspace', 'tpw-core' ) ); ?></h2>
			<p><?php echo esc_html( $workspace['hero_copy'] ?? __( 'Manage the current front-end menu permissions tools from the iLungu Club portal.', 'tpw-core' ) ); ?></p>
		</div>
		<div class="ilungu-club-control-workspace__hero-actions tpw-flexiclub-control-workspace__hero-actions">
			<?php if ( '' !== $dashboard_url ) : ?>
				<a class="tpw-btn tpw-btn-outline" href="<?php echo esc_url( $dashboard_url ); ?>"><?php esc_html_e( 'Back to dashboard', 'tpw-core' ); ?></a>
			<?php endif; ?>
			<?php if ( '' !== $dedicated_url ) : ?>
				<a class="tpw-btn tpw-btn-secondary" href="<?php echo esc_url( $dedicated_url ); ?>"><?php echo esc_html( $workspace['dedicated_label'] ?? __( 'Open dedicated Menu Management page', 'tpw-core' ) ); ?></a>
			<?php endif; ?>
		</div>
	</div>

	<div class="ilungu-club-control-workspace__summary-grid tpw-flexiclub-control-workspace__summary-grid">
		<div class="ilungu-club-control-workspace__summary-card tpw-flexiclub-control-workspace__summary-card">
			<span class="ilungu-club-dashboard__status tpw-flexiclub-dashboard__status ilungu-club-dashboard__status--<?php echo esc_attr( $workspace['status_tone'] ?? 'neutral' ); ?> tpw-flexiclub-dashboard__status--<?php echo esc_attr( $workspace['status_tone'] ?? 'neutral' ); ?>"><?php echo esc_html( $workspace['status_label'] ?? __( 'Ready', 'tpw-core' ) ); ?></span>
			<strong><?php echo esc_html( $workspace['metric_value'] ?? __( 'Unavailable', 'tpw-core' ) ); ?></strong>
			<p><?php echo esc_html( $workspace['metric_text'] ?? '' ); ?></p>
		</div>
	</div>
</section>

<section id="ilungu-club-menu-management-legacy" class="ilungu-club-dashboard__section tpw-flexiclub-dashboard__section tpw-card ilungu-club-control-workspace__notice-card tpw-flexiclub-control-workspace__notice-card">
	<div class="ilungu-club-control-workspace__notice tpw-flexiclub-control-workspace__notice ilungu-club-control-workspace__notice--legacy tpw-flexiclub-control-workspace__notice--legacy">
		<span class="ilungu-club-dashboard__status tpw-flexiclub-dashboard__status ilungu-club-dashboard__status--warning tpw-flexiclub-dashboard__status--warning"><?php echo esc_html( $workspace['legacy_notice_label'] ?? __( 'Legacy Workspace', 'tpw-core' ) ); ?></span>
		<p><?php echo esc_html( $workspace['legacy_notice_text'] ?? __( 'iLungu Club Control remains available during the transition.', 'tpw-core' ) ); ?></p>
		<?php if ( '' !== $legacy_url ) : ?>
				<a class="tpw-btn tpw-btn-light" href="<?php echo esc_url( $legacy_url ); ?>"><?php esc_html_e( 'Open legacy iLungu Club Control', 'tpw-core' ); ?></a>
		<?php endif; ?>
	</div>
</section>

<section id="ilungu-club-menu-management-tool" class="ilungu-club-dashboard__section tpw-flexiclub-dashboard__section tpw-card ilungu-club-control-workspace__section tpw-flexiclub-control-workspace__section">
	<div class="ilungu-club-dashboard__section-head tpw-flexiclub-dashboard__section-head ilungu-club-control-workspace__section-head tpw-flexiclub-control-workspace__section-head">
		<div>
			<h2><?php echo esc_html( $workspace['tool_heading'] ?? __( 'Current Menu Management tool', 'tpw-core' ) ); ?></h2>
			<p><?php echo esc_html( $workspace['tool_copy'] ?? __( 'This workspace embeds the existing front-end menu-management section inside the iLungu Club portal shell.', 'tpw-core' ) ); ?></p>
		</div>
	</div>

	<div class="ilungu-club-control-workspace__embed tpw-flexiclub-control-workspace__embed">
		<?php iLungu_Club_Admin_Menu::render_frontend_tpw_control_section( $workspace['section_key'] ?? 'menu-manager' ); ?>
	</div>
</section>

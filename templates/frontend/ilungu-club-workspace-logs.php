<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$workspace      = isset( $dashboard['logs_workspace'] ) && is_array( $dashboard['logs_workspace'] ) ? $dashboard['logs_workspace'] : [];
$summary_cards  = isset( $workspace['summary_cards'] ) && is_array( $workspace['summary_cards'] ) ? $workspace['summary_cards'] : [];
$sources        = isset( $workspace['sources'] ) && is_array( $workspace['sources'] ) ? $workspace['sources'] : [];
$rows           = isset( $workspace['rows'] ) && is_array( $workspace['rows'] ) ? $workspace['rows'] : [];
$pagination     = isset( $workspace['pagination'] ) && is_array( $workspace['pagination'] ) ? $workspace['pagination'] : [];
$notice         = isset( $workspace['notice'] ) && is_array( $workspace['notice'] ) ? $workspace['notice'] : [];
$clear_form     = isset( $workspace['clear_form'] ) && is_array( $workspace['clear_form'] ) ? $workspace['clear_form'] : [];
$dashboard_url  = isset( $workspace['dashboard_url'] ) ? (string) $workspace['dashboard_url'] : '';
$settings_url   = isset( $workspace['settings_url'] ) ? (string) $workspace['settings_url'] : '';
$active_label   = isset( $workspace['active_label'] ) ? (string) $workspace['active_label'] : __( 'Logs', 'tpw-core' );
$empty_text     = isset( $workspace['empty_text'] ) ? (string) $workspace['empty_text'] : __( 'No log entries found.', 'tpw-core' );
$total_rows     = isset( $workspace['total_rows'] ) ? (int) $workspace['total_rows'] : count( $rows );

$render_status_tone = static function( $status ) {
	$status = strtolower( trim( (string) $status ) );

	if ( in_array( $status, [ 'failed', 'error', 'declined' ], true ) ) {
		return 'error';
	}

	if ( in_array( $status, [ 'sent', 'success', 'completed', 'paid' ], true ) ) {
		return 'success';
	}

	if ( in_array( $status, [ 'pending', 'queued', 'processing' ], true ) ) {
		return 'warning';
	}

	return 'neutral';
};
?>

<section id="ilungu-club-logs-overview" class="ilungu-club-dashboard__hero tpw-flexiclub-dashboard__hero tpw-card ilungu-club-logs__hero tpw-flexiclub-logs__hero">
	<div class="ilungu-club-dashboard__brand-row tpw-flexiclub-dashboard__brand-row ilungu-club-logs__hero-head tpw-flexiclub-logs__hero-head">
		<div class="ilungu-club-dashboard__welcome tpw-flexiclub-dashboard__welcome ilungu-club-logs__hero-copy tpw-flexiclub-logs__hero-copy">
			<h2><?php esc_html_e( 'Logs Workspace', 'tpw-core' ); ?></h2>
			<p><?php esc_html_e( 'Review operational email and payment logs from the iLungu Club portal without leaving the front end. Sensitive payloads and secrets remain hidden.', 'tpw-core' ); ?></p>
		</div>
		<div class="ilungu-club-logs__hero-actions tpw-flexiclub-logs__hero-actions">
			<?php if ( '' !== $dashboard_url ) : ?>
				<a class="tpw-btn tpw-btn-outline" href="<?php echo esc_url( $dashboard_url ); ?>"><?php esc_html_e( 'Back to dashboard', 'tpw-core' ); ?></a>
			<?php endif; ?>
			<?php if ( '' !== $settings_url ) : ?>
				<a class="tpw-btn tpw-btn-secondary" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Open Settings workspace', 'tpw-core' ); ?></a>
			<?php endif; ?>
		</div>
	</div>

	<div class="ilungu-club-logs__summary-grid tpw-flexiclub-logs__summary-grid">
		<?php foreach ( $summary_cards as $card ) : ?>
			<div class="ilungu-club-logs__summary-card tpw-flexiclub-logs__summary-card">
				<span class="ilungu-club-dashboard__status tpw-flexiclub-dashboard__status ilungu-club-dashboard__status--<?php echo esc_attr( $card['tone'] ?? 'neutral' ); ?> tpw-flexiclub-dashboard__status--<?php echo esc_attr( $card['tone'] ?? 'neutral' ); ?>"><?php echo esc_html( $card['label'] ?? '' ); ?></span>
				<strong><?php echo esc_html( $card['value'] ?? '' ); ?></strong>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<section id="ilungu-club-logs-sources" class="ilungu-club-dashboard__section tpw-flexiclub-dashboard__section tpw-card ilungu-club-logs__section tpw-flexiclub-logs__section">
	<div class="ilungu-club-dashboard__section-head tpw-flexiclub-dashboard__section-head ilungu-club-logs__section-head tpw-flexiclub-logs__section-head">
		<div>
			<h2><?php esc_html_e( 'Log Sources', 'tpw-core' ); ?></h2>
			<p><?php esc_html_e( 'Switch between the existing log sources that iLungu Club currently exposes through shared Core tooling.', 'tpw-core' ); ?></p>
		</div>
	</div>

	<?php if ( empty( $sources ) ) : ?>
		<p class="ilungu-club-logs__empty tpw-flexiclub-logs__empty"><?php esc_html_e( 'No front-end-safe log sources are currently available.', 'tpw-core' ); ?></p>
	<?php else : ?>
		<nav class="ilungu-club-logs__tabs tpw-flexiclub-logs__tabs" aria-label="<?php esc_attr_e( 'iLungu Club log sources', 'tpw-core' ); ?>">
			<?php foreach ( $sources as $source ) : ?>
				<?php
				$tab_classes = 'tpw-flexiclub-logs__tab-link';
				if ( ! empty( $source['current'] ) ) {
					$tab_classes .= ' tpw-flexiclub-logs__tab-link--current';
				}
				?>
				<a class="<?php echo esc_attr( $tab_classes ); ?>" href="<?php echo esc_url( $source['url'] ?? '' ); ?>" <?php echo ! empty( $source['current'] ) ? 'aria-current="page"' : ''; ?>>
					<span><?php echo esc_html( $source['label'] ?? '' ); ?></span>
					<strong><?php echo esc_html( $source['count'] ?? '' ); ?></strong>
					<small class="ilungu-club-dashboard__status tpw-flexiclub-dashboard__status ilungu-club-dashboard__status--<?php echo esc_attr( $source['status_tone'] ?? 'neutral' ); ?> tpw-flexiclub-dashboard__status--<?php echo esc_attr( $source['status_tone'] ?? 'neutral' ); ?>"><?php echo esc_html( $source['status_label'] ?? '' ); ?></small>
				</a>
			<?php endforeach; ?>
		</nav>

		<div class="ilungu-club-logs__source-notes tpw-flexiclub-logs__source-notes">
			<?php foreach ( $sources as $source ) : ?>
				<?php if ( ! empty( $source['current'] ) ) : ?>
					<p><?php echo esc_html( $source['description'] ?? '' ); ?></p>
				<?php endif; ?>
			<?php endforeach; ?>
			<p><?php echo esc_html( $workspace['additional_sources_text'] ?? '' ); ?></p>
		</div>
	<?php endif; ?>
</section>

<section id="ilungu-club-logs-table" class="ilungu-club-dashboard__section tpw-flexiclub-dashboard__section tpw-card ilungu-club-logs__section tpw-flexiclub-logs__section ilungu-club-logs__section--full tpw-flexiclub-logs__section--full">
	<div class="ilungu-club-dashboard__section-head tpw-flexiclub-dashboard__section-head ilungu-club-logs__section-head tpw-flexiclub-logs__section-head">
		<div>
			<h2><?php echo esc_html( $active_label ); ?></h2>
			<p><?php echo esc_html( sprintf( __( 'Review the latest %1$s entries available from this source.', 'tpw-core' ), number_format_i18n( $total_rows ) ) ); ?></p>
		</div>
		<?php if ( ! empty( $clear_form['enabled'] ) ) : ?>
			<form class="ilungu-club-logs__clear-form tpw-flexiclub-logs__clear-form" method="post" action="<?php echo esc_url( $clear_form['action_url'] ?? '' ); ?>">
				<?php wp_nonce_field( $clear_form['nonce_action'] ?? '', $clear_form['nonce_field'] ?? '' ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( $clear_form['action'] ?? '' ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( $clear_form['redirect_key'] ?? '' ); ?>" value="<?php echo esc_url( $clear_form['redirect_url'] ?? '' ); ?>" />
				<?php if ( ! empty( $clear_form['hidden_fields'] ) && is_array( $clear_form['hidden_fields'] ) ) : ?>
					<?php foreach ( $clear_form['hidden_fields'] as $field ) : ?>
						<input type="hidden" name="<?php echo esc_attr( $field['name'] ?? '' ); ?>" value="<?php echo esc_attr( $field['value'] ?? '' ); ?>" />
					<?php endforeach; ?>
				<?php endif; ?>
				<button class="tpw-btn tpw-btn-light" type="submit" onclick="return confirm('<?php echo esc_js( $clear_form['confirm_text'] ?? '' ); ?>');"><?php echo esc_html( $clear_form['button_label'] ?? '' ); ?></button>
			</form>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $notice['message'] ) ) : ?>
		<div class="ilungu-club-logs__notice tpw-flexiclub-logs__notice ilungu-club-logs__notice--<?php echo esc_attr( $notice['tone'] ?? 'info' ); ?> tpw-flexiclub-logs__notice--<?php echo esc_attr( $notice['tone'] ?? 'info' ); ?>">
			<span class="ilungu-club-dashboard__status tpw-flexiclub-dashboard__status ilungu-club-dashboard__status--<?php echo esc_attr( $notice['tone'] ?? 'info' ); ?> tpw-flexiclub-dashboard__status--<?php echo esc_attr( $notice['tone'] ?? 'info' ); ?>"><?php echo esc_html( ucfirst( str_replace( '-', ' ', (string) ( $notice['tone'] ?? 'info' ) ) ) ); ?></span>
			<p><?php echo esc_html( $notice['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( empty( $rows ) ) : ?>
		<p class="ilungu-club-logs__empty tpw-flexiclub-logs__empty"><?php echo esc_html( $empty_text ); ?></p>
	<?php else : ?>
		<div class="tpw-table-container ilungu-club-logs__table tpw-flexiclub-logs__table" role="table" aria-label="<?php echo esc_attr( $active_label ); ?>">
			<div class="table-row ilungu-club-logs__row tpw-flexiclub-logs__row ilungu-club-logs__row--head tpw-flexiclub-logs__row--head" role="row">
				<div class="table-cell" role="columnheader"><?php esc_html_e( 'Date / Time', 'tpw-core' ); ?></div>
				<div class="table-cell" role="columnheader"><?php esc_html_e( 'Type / Source', 'tpw-core' ); ?></div>
				<div class="table-cell" role="columnheader"><?php esc_html_e( 'Status', 'tpw-core' ); ?></div>
				<div class="table-cell" role="columnheader"><?php esc_html_e( 'Message / Summary', 'tpw-core' ); ?></div>
				<div class="table-cell" role="columnheader"><?php esc_html_e( 'Related Item', 'tpw-core' ); ?></div>
			</div>

			<?php foreach ( $rows as $row ) : ?>
				<?php $status = isset( $row['status'] ) ? (string) $row['status'] : ''; ?>
				<div class="table-row ilungu-club-logs__row tpw-flexiclub-logs__row" role="row">
					<div class="table-cell ilungu-club-logs__cell tpw-flexiclub-logs__cell" role="cell" data-label="<?php esc_attr_e( 'Date / Time', 'tpw-core' ); ?>">
						<strong><?php echo esc_html( $row['date'] ?? '' ); ?></strong>
					</div>
					<div class="table-cell ilungu-club-logs__cell tpw-flexiclub-logs__cell" role="cell" data-label="<?php esc_attr_e( 'Type / Source', 'tpw-core' ); ?>">
						<?php echo esc_html( $row['source'] ?? '' ); ?>
					</div>
					<div class="table-cell ilungu-club-logs__cell tpw-flexiclub-logs__cell" role="cell" data-label="<?php esc_attr_e( 'Status', 'tpw-core' ); ?>">
						<span class="ilungu-club-dashboard__status tpw-flexiclub-dashboard__status ilungu-club-dashboard__status--<?php echo esc_attr( $render_status_tone( $status ) ); ?> tpw-flexiclub-dashboard__status--<?php echo esc_attr( $render_status_tone( $status ) ); ?>"><?php echo esc_html( '' !== $status ? ucfirst( str_replace( '-', ' ', $status ) ) : __( 'Unknown', 'tpw-core' ) ); ?></span>
					</div>
					<div class="table-cell ilungu-club-logs__cell tpw-flexiclub-logs__cell" role="cell" data-label="<?php esc_attr_e( 'Message / Summary', 'tpw-core' ); ?>">
						<?php echo esc_html( $row['message'] ?? '' ); ?>
					</div>
					<div class="table-cell ilungu-club-logs__cell tpw-flexiclub-logs__cell" role="cell" data-label="<?php esc_attr_e( 'Related Item', 'tpw-core' ); ?>">
						<?php echo esc_html( $row['reference'] ?? '' ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( count( $pagination ) > 1 ) : ?>
			<nav class="ilungu-club-logs__pagination tpw-flexiclub-logs__pagination" aria-label="<?php esc_attr_e( 'Log pagination', 'tpw-core' ); ?>">
				<?php foreach ( $pagination as $page_link ) : ?>
					<?php if ( ! empty( $page_link['current'] ) ) : ?>
						<span class="ilungu-club-logs__page-link tpw-flexiclub-logs__page-link ilungu-club-logs__page-link--current tpw-flexiclub-logs__page-link--current"><?php echo esc_html( $page_link['label'] ?? '' ); ?></span>
					<?php else : ?>
						<a class="ilungu-club-logs__page-link tpw-flexiclub-logs__page-link" href="<?php echo esc_url( $page_link['url'] ?? '' ); ?>"><?php echo esc_html( $page_link['label'] ?? '' ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</section>
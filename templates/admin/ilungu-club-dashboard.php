<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ilungu-club-dashboard__hero tpw-flexiclub-dashboard__hero tpw-card">
	<div class="ilungu-club-dashboard__brand-row tpw-flexiclub-dashboard__brand-row">
		<div class="ilungu-club-dashboard__brand tpw-flexiclub-dashboard__brand">
			<?php if ( ! empty( $dashboard['logo_url'] ) ) : ?>
				<img class="ilungu-club-dashboard__logo tpw-flexiclub-dashboard__logo" src="<?php echo esc_url( $dashboard['logo_url'] ); ?>" alt="<?php esc_attr_e( 'iLungu™ Club', 'tpw-core' ); ?>" />
			<?php else : ?>
				<h1><?php esc_html_e( 'iLungu™ Club', 'tpw-core' ); ?></h1>
			<?php endif; ?>
			<div class="ilungu-club-dashboard__brand-copy tpw-flexiclub-dashboard__brand-copy">
				<p class="ilungu-club-dashboard__tagline tpw-flexiclub-dashboard__tagline"><?php esc_html_e( 'Your club. Your members. Your community.', 'tpw-core' ); ?></p>
			</div>
		</div>
		<?php if ( ! empty( $dashboard['version'] ) ) : ?>
			<div class="ilungu-club-dashboard__version tpw-flexiclub-dashboard__version">
				<span><?php esc_html_e( 'Version', 'tpw-core' ); ?></span>
				<strong><?php echo esc_html( $dashboard['version'] ); ?></strong>
			</div>
		<?php endif; ?>
	</div>

	<div class="ilungu-club-dashboard__hero-content tpw-flexiclub-dashboard__hero-content">
		<div class="ilungu-club-dashboard__welcome tpw-flexiclub-dashboard__welcome">
			<h2>
				<?php
				printf(
					/* translators: %s: current user display name */
					esc_html__( 'Welcome back, %s', 'tpw-core' ),
					esc_html( $dashboard['welcome_name'] )
				);
				?>
			</h2>
			<p><?php esc_html_e( 'Here’s what’s happening across your club operations today.', 'tpw-core' ); ?></p>
		</div>

		<div class="ilungu-club-dashboard__summary-grid tpw-flexiclub-dashboard__summary-grid">
			<?php foreach ( $dashboard['summary_cards'] as $card ) : ?>
				<?php if ( ! empty( $card['action_url'] ) ) : ?>
					<a class="ilungu-club-dashboard__summary-card tpw-flexiclub-dashboard__summary-card ilungu-club-dashboard__summary-card--link tpw-flexiclub-dashboard__summary-card--link" href="<?php echo esc_url( $card['action_url'] ); ?>" aria-label="<?php echo esc_attr( $card['action_label'] ?? $card['title'] ); ?>">
				<?php else : ?>
					<div class="ilungu-club-dashboard__summary-card tpw-flexiclub-dashboard__summary-card">
				<?php endif; ?>
					<div class="ilungu-club-dashboard__summary-copy tpw-flexiclub-dashboard__summary-copy">
						<span class="ilungu-club-dashboard__summary-label tpw-flexiclub-dashboard__summary-label"><?php echo esc_html( $card['title'] ); ?></span>
						<div class="ilungu-club-dashboard__metric tpw-flexiclub-dashboard__metric"><?php echo esc_html( $card['value'] ); ?></div>
					</div>
				<?php if ( ! empty( $card['action_url'] ) ) : ?>
					</a>
				<?php else : ?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<div class="ilungu-club-dashboard__layout tpw-flexiclub-dashboard__layout">
	<div class="ilungu-club-dashboard__main tpw-flexiclub-dashboard__main">
		<?php if ( ! empty( $dashboard['show_checklist'] ) ) : ?>
			<section id="ilungu-club-checklist" class="ilungu-club-dashboard__section tpw-flexiclub-dashboard__section tpw-card ilungu-club-dashboard__section--checklist tpw-flexiclub-dashboard__section--checklist ilungu-club-dashboard__section--checklist-full tpw-flexiclub-dashboard__section--checklist-full">
				<div class="ilungu-club-dashboard__section-head tpw-flexiclub-dashboard__section-head">
					<div>
						<h2><?php esc_html_e( 'Getting Started', 'tpw-core' ); ?></h2>
						<p><?php esc_html_e( 'A simple checklist for launching your club workspace.', 'tpw-core' ); ?></p>
					</div>
				</div>

				<div class="ilungu-club-dashboard__checklist tpw-flexiclub-dashboard__checklist">
					<div class="ilungu-club-dashboard__progress tpw-flexiclub-dashboard__progress" style="--tpw-progress: <?php echo esc_attr( $dashboard['checklist_progress'] ); ?>%;">
						<div class="ilungu-club-dashboard__progress-ring tpw-flexiclub-dashboard__progress-ring">
							<strong><?php echo esc_html( $dashboard['checklist_done'] ); ?>/<?php echo esc_html( $dashboard['checklist_total'] ); ?></strong>
						</div>
						<div class="ilungu-club-dashboard__progress-copy tpw-flexiclub-dashboard__progress-copy">
							<h3><?php esc_html_e( 'Setup Progress', 'tpw-core' ); ?></h3>
							<p>
								<?php
								echo ! empty( $dashboard['checklist_complete'] )
									? esc_html__( 'Everything is in place.', 'tpw-core' )
									: esc_html__( 'Almost there.', 'tpw-core' );
								?>
							</p>
						</div>
					</div>

					<div class="ilungu-club-dashboard__checklist-panel tpw-flexiclub-dashboard__checklist-panel">
						<div class="ilungu-club-dashboard__checklist-items tpw-flexiclub-dashboard__checklist-items">
							<?php foreach ( $dashboard['checklist_items'] as $item ) : ?>
								<div class="ilungu-club-dashboard__checklist-item tpw-flexiclub-dashboard__checklist-item">
									<span class="ilungu-club-dashboard__checkmark tpw-flexiclub-dashboard__checkmark ilungu-club-dashboard__checkmark--<?php echo ! empty( $item['done'] ) ? 'done' : 'pending'; ?> tpw-flexiclub-dashboard__checkmark--<?php echo ! empty( $item['done'] ) ? 'done' : 'pending'; ?>" aria-hidden="true">
										<?php echo ! empty( $item['done'] ) ? '✓' : '○'; ?>
									</span>
									<div>
										<div class="ilungu-club-dashboard__checklist-title tpw-flexiclub-dashboard__checklist-title">
											<?php echo esc_html( $item['label'] ); ?>
											<?php if ( ! empty( $item['optional'] ) ) : ?>
												<span class="ilungu-club-dashboard__optional tpw-flexiclub-dashboard__optional"><?php esc_html_e( 'Optional', 'tpw-core' ); ?></span>
											<?php endif; ?>
										</div>
										<p><?php echo esc_html( $item['description'] ); ?></p>
									</div>
									<a href="<?php echo esc_url( $item['url'] ); ?>"><?php esc_html_e( 'Open', 'tpw-core' ); ?></a>
								</div>
							<?php endforeach; ?>
						</div>

						<div class="ilungu-club-dashboard__checklist-actions tpw-flexiclub-dashboard__checklist-actions">
							<?php if ( ! empty( $dashboard['checklist_complete'] ) ) : ?>
								<a class="tpw-btn tpw-btn-outline" href="<?php echo esc_url( $dashboard['collapse_checklist_url'] ); ?>"><?php esc_html_e( 'Collapse', 'tpw-core' ); ?></a>
								<a class="ilungu-club-dashboard__muted-link tpw-flexiclub-dashboard__muted-link" href="<?php echo esc_url( $dashboard['dismiss_setup_url'] ); ?>"><?php esc_html_e( 'Dismiss', 'tpw-core' ); ?></a>
							<?php else : ?>
								<a class="tpw-btn tpw-btn-outline" href="<?php echo esc_url( $dashboard['checklist_primary_action']['url'] ); ?>"><?php echo esc_html( $dashboard['checklist_primary_action']['label'] ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</section>
		<?php elseif ( ! empty( $dashboard['show_setup_banner'] ) ) : ?>
			<section class="ilungu-club-dashboard__setup-banner tpw-flexiclub-dashboard__setup-banner tpw-card" aria-label="<?php esc_attr_e( 'Setup complete', 'tpw-core' ); ?>">
				<div class="ilungu-club-dashboard__setup-banner-copy tpw-flexiclub-dashboard__setup-banner-copy">
					<h2><?php esc_html_e( 'Setup complete', 'tpw-core' ); ?></h2>
					<p><?php esc_html_e( 'Your current onboarding checklist is complete.', 'tpw-core' ); ?></p>
				</div>
				<div class="ilungu-club-dashboard__setup-banner-actions tpw-flexiclub-dashboard__setup-banner-actions">
					<a class="tpw-btn tpw-btn-outline" href="<?php echo esc_url( $dashboard['checklist_url'] ); ?>"><?php esc_html_e( 'View checklist', 'tpw-core' ); ?></a>
					<a class="ilungu-club-dashboard__muted-link tpw-flexiclub-dashboard__muted-link" href="<?php echo esc_url( $dashboard['dismiss_setup_url'] ); ?>"><?php esc_html_e( 'Dismiss', 'tpw-core' ); ?></a>
				</div>
			</section>
		<?php endif; ?>

		<section class="ilungu-club-dashboard__section tpw-flexiclub-dashboard__section tpw-card">
			<div class="ilungu-club-dashboard__section-head tpw-flexiclub-dashboard__section-head">
				<div>
					<h2><?php esc_html_e( 'Club Overview', 'tpw-core' ); ?></h2>
					<p><?php esc_html_e( 'A snapshot of your key management areas.', 'tpw-core' ); ?></p>
				</div>
			</div>

			<div class="ilungu-club-dashboard__overview-grid tpw-flexiclub-dashboard__overview-grid">
				<?php foreach ( $dashboard['overview_cards'] as $card ) : ?>
					<div class="ilungu-club-dashboard__overview-card tpw-flexiclub-dashboard__overview-card ilungu-club-dashboard__overview-card--<?php echo esc_attr( $card['tone'] ?? 'default' ); ?> tpw-flexiclub-dashboard__overview-card--<?php echo esc_attr( $card['tone'] ?? 'default' ); ?>">
						<div class="ilungu-club-dashboard__overview-icon tpw-flexiclub-dashboard__overview-icon ilungu-club-dashboard__overview-icon--<?php echo esc_attr( $card['tone'] ?? 'default' ); ?> tpw-flexiclub-dashboard__overview-icon--<?php echo esc_attr( $card['tone'] ?? 'default' ); ?>">
							<span class="dashicons <?php echo esc_attr( $card['icon'] ); ?>" aria-hidden="true"></span>
						</div>
						<div class="ilungu-club-dashboard__overview-body tpw-flexiclub-dashboard__overview-body">
							<div class="ilungu-club-dashboard__overview-head tpw-flexiclub-dashboard__overview-head">
								<h3><?php echo esc_html( $card['title'] ); ?></h3>
								<span class="ilungu-club-dashboard__status tpw-flexiclub-dashboard__status ilungu-club-dashboard__status--<?php echo esc_attr( $card['status_tone'] ); ?> tpw-flexiclub-dashboard__status--<?php echo esc_attr( $card['status_tone'] ); ?>">
									<?php echo esc_html( $card['status_label'] ); ?>
								</span>
							</div>
							<div class="ilungu-club-dashboard__overview-metric tpw-flexiclub-dashboard__overview-metric"><?php echo esc_html( $card['metric'] ); ?></div>
							<p><?php echo esc_html( $card['description'] ); ?></p>
						</div>
						<?php if ( ! array_key_exists( 'show_action', $card ) || ! empty( $card['show_action'] ) ) : ?>
							<div class="ilungu-club-dashboard__overview-action tpw-flexiclub-dashboard__overview-action">
								<?php if ( ! empty( $card['disabled'] ) ) : ?>
									<button class="tpw-btn tpw-btn-outline" type="button" disabled><?php echo esc_html( $card['action_label'] ); ?></button>
								<?php else : ?>
									<a class="tpw-btn tpw-btn-outline" href="<?php echo esc_url( $card['action_url'] ); ?>"><?php echo esc_html( $card['action_label'] ); ?></a>
								<?php endif; ?>
								<?php if ( ! empty( $card['secondary_action'] ) ) : ?>
									<a class="tpw-btn tpw-btn-outline" href="<?php echo esc_url( $card['secondary_action']['url'] ); ?>"><?php echo esc_html( $card['secondary_action']['label'] ); ?></a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section id="ilungu-club-extend" class="ilungu-club-dashboard__section tpw-flexiclub-dashboard__section tpw-card">
			<div class="ilungu-club-dashboard__section-head tpw-flexiclub-dashboard__section-head">
				<div>
									<h2><?php esc_html_e( 'Extend iLungu Club', 'tpw-core' ); ?></h2>
					<p><?php esc_html_e( 'Add powerful add-ons to grow your club.', 'tpw-core' ); ?></p>
				</div>
			</div>

			<div class="ilungu-club-dashboard__extend-grid tpw-flexiclub-dashboard__extend-grid">
				<?php foreach ( $dashboard['extend_cards'] as $card ) : ?>
					<div class="ilungu-club-dashboard__extend-card tpw-flexiclub-dashboard__extend-card">
						<?php if ( ! empty( $card['icon_url'] ) ) : ?>
							<img src="<?php echo esc_url( $card['icon_url'] ); ?>" alt="" />
						<?php else : ?>
							<span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
						<?php endif; ?>
						<div class="ilungu-club-dashboard__extend-head tpw-flexiclub-dashboard__extend-head">
							<h3><?php echo esc_html( $card['name'] ); ?></h3>
							<span class="ilungu-club-dashboard__status tpw-flexiclub-dashboard__status ilungu-club-dashboard__status--<?php echo esc_attr( $card['status_tone'] ); ?> tpw-flexiclub-dashboard__status--<?php echo esc_attr( $card['status_tone'] ); ?>">
								<?php echo esc_html( $card['status_label'] ); ?>
							</span>
						</div>
						<p><?php echo esc_html( $card['description'] ); ?></p>
						<?php if ( ! empty( $card['actions'] ) ) : ?>
							<div class="ilungu-club-dashboard__extend-action tpw-flexiclub-dashboard__extend-action">
								<?php foreach ( $card['actions'] as $action ) : ?>
									<a class="tpw-btn tpw-btn-outline" href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['label'] ); ?></a>
								<?php endforeach; ?>
							</div>
						<?php elseif ( ! empty( $card['action_url'] ) ) : ?>
							<div class="ilungu-club-dashboard__extend-action tpw-flexiclub-dashboard__extend-action">
								<a class="tpw-btn tpw-btn-outline" href="<?php echo esc_url( $card['action_url'] ); ?>"><?php echo esc_html( $card['action_label'] ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	</div>

	<aside class="ilungu-club-dashboard__aside tpw-flexiclub-dashboard__aside">
		<section class="ilungu-club-dashboard__section tpw-flexiclub-dashboard__section tpw-card">
			<div class="ilungu-club-dashboard__section-head tpw-flexiclub-dashboard__section-head">
				<div>
					<h2><?php esc_html_e( 'Quick Actions', 'tpw-core' ); ?></h2>
				</div>
			</div>

			<div class="ilungu-club-dashboard__action-list tpw-flexiclub-dashboard__action-list">
				<?php foreach ( $dashboard['quick_actions'] as $action ) : ?>
					<?php if ( ! empty( $action['disabled'] ) ) : ?>
						<div class="ilungu-club-dashboard__action-link tpw-flexiclub-dashboard__action-link ilungu-club-dashboard__action-link--disabled tpw-flexiclub-dashboard__action-link--disabled">
							<span><?php echo esc_html( $action['label'] ); ?></span>
							<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
						</div>
					<?php else : ?>
						<a class="ilungu-club-dashboard__action-link tpw-flexiclub-dashboard__action-link" href="<?php echo esc_url( $action['url'] ); ?>">
							<span><?php echo esc_html( $action['label'] ); ?></span>
							<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="ilungu-club-dashboard__section tpw-flexiclub-dashboard__section tpw-card ilungu-club-dashboard__section--activity tpw-flexiclub-dashboard__section--activity">
			<div class="ilungu-club-dashboard__section-head tpw-flexiclub-dashboard__section-head">
				<div>
					<h2><?php esc_html_e( 'Recent Activity', 'tpw-core' ); ?></h2>
					<p><?php esc_html_e( 'Latest operational updates across members, notices, and logs.', 'tpw-core' ); ?></p>
				</div>
			</div>

			<div class="ilungu-club-dashboard__activity-list tpw-flexiclub-dashboard__activity-list">
				<?php foreach ( $dashboard['activity_items'] as $item ) : ?>
					<div class="ilungu-club-dashboard__activity-item tpw-flexiclub-dashboard__activity-item">
						<div class="ilungu-club-dashboard__activity-copy tpw-flexiclub-dashboard__activity-copy">
							<h3><?php echo esc_html( $item['title'] ); ?></h3>
							<p><?php echo esc_html( $item['meta'] ); ?></p>
						</div>
						<span class="ilungu-club-dashboard__activity-time tpw-flexiclub-dashboard__activity-time"><?php echo esc_html( $item['time'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="ilungu-club-dashboard__section tpw-flexiclub-dashboard__section tpw-card">
			<div class="ilungu-club-dashboard__section-head tpw-flexiclub-dashboard__section-head">
				<div>
					<h2><?php esc_html_e( 'System Status', 'tpw-core' ); ?></h2>
				</div>
			</div>

			<div class="ilungu-club-dashboard__system-grid tpw-flexiclub-dashboard__system-grid">
				<?php foreach ( $dashboard['system_items'] as $item ) : ?>
					<div class="ilungu-club-dashboard__system-item tpw-flexiclub-dashboard__system-item">
						<span class="ilungu-club-dashboard__system-label tpw-flexiclub-dashboard__system-label"><?php echo esc_html( $item['label'] ); ?></span>
						<strong class="ilungu-club-dashboard__status tpw-flexiclub-dashboard__status ilungu-club-dashboard__status--<?php echo esc_attr( $item['tone'] ); ?> tpw-flexiclub-dashboard__status--<?php echo esc_attr( $item['tone'] ); ?>">
							<?php echo esc_html( $item['value'] ); ?>
						</strong>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	</aside>
</div>

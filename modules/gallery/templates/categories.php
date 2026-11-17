<?php
/**
 * Gallery Admin – Categories View
 * @since 0.4.0 Gallery UI
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! function_exists( 'tpw_gallery_user_can_manage' ) || ! tpw_gallery_user_can_manage() ) {
    wp_die( esc_html__( 'You do not have permission to access this page.', 'tpw-ilungu-club' ) );
}

$cats = function_exists('tpw_gallery_get_categories') ? tpw_gallery_get_categories() : [];
?>
<div class="tpw-card">
  <div class="tpw-flex tpw-justify-between tpw-items-center">
    <h3><?php esc_html_e('Categories', 'tpw-ilungu-club'); ?></h3>
    <div class="tpw-cat-controls">
      <input type="text" id="tpw-cat-name" placeholder="<?php esc_attr_e('Category name', 'tpw-ilungu-club'); ?>">
      <button class="tpw-btn tpw-btn-primary" id="tpw-add-cat"><?php esc_html_e('Add Category', 'tpw-ilungu-club'); ?></button>
    </div>
  </div>
  <table class="tpw-table" id="tpw-cat-table">
    <thead>
      <tr>
        <th><?php esc_html_e('Name', 'tpw-ilungu-club'); ?></th>
        <th><?php esc_html_e('Slug', 'tpw-ilungu-club'); ?></th>
        <th><?php esc_html_e('Sort', 'tpw-ilungu-club'); ?></th>
        <th><?php esc_html_e('Actions', 'tpw-ilungu-club'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ( $cats as $c ) : ?>
      <tr data-id="<?php echo (int) $c['category_id']; ?>">
        <td><?php echo esc_html( $c['name'] ); ?></td>
        <td><?php echo esc_html( $c['slug'] ); ?></td>
        <td><?php echo (int) $c['sort_order']; ?></td>
        <td>
          <button class="tpw-btn tpw-btn-small tpw-btn-danger tpw-cat-delete" data-id="<?php echo (int) $c['category_id']; ?>"><?php esc_html_e('Delete', 'tpw-ilungu-club'); ?></button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if (!defined('ABSPATH')) { exit; } ?>
<div id="tpw-notice-modal" class="tpw-notice-modal" style="display:none">
  <div class="tpw-notice-modal-content">
    <div class="tpw-notice-modal-header">
      <h3 class="tpw-notice-modal-title"><?php echo esc_html__('Add/Edit Notice', 'tpw-ilungu-club'); ?></h3>
      <button type="button" class="tpw-notice-modal-close" aria-label="Close">×</button>
    </div>
    <div class="tpw-notice-modal-body">
      <form id="tpw-notice-form">
        <?php wp_nonce_field('tpw_notice_save'); ?>
        <input type="hidden" name="action" value="tpw_notice_save" />
        <input type="hidden" name="notice_id" value="" />
        <div class="tpw-field">
          <label><?php echo esc_html__('Title', 'tpw-ilungu-club'); ?></label>
          <input type="text" name="title" required />
        </div>
        <div class="tpw-field">
          <label><?php echo esc_html__('Category', 'tpw-ilungu-club'); ?></label>
          <?php
            wp_dropdown_categories([
              'taxonomy' => 'tpw_notice_category',
              'hide_empty' => false,
              'name' => 'category',
              'id' => 'tpw_notice_category',
              'show_option_none' => __('— Select —','tpw-ilungu-club'),
              'value_field' => 'term_id',
            ]);
          ?>
          <div class="tpw-inline-add-cat" style="margin-top:8px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <input type="text" id="tpw_new_category_name" placeholder="<?php echo esc_attr__('New category name','tpw-ilungu-club'); ?>" />
            <button type="button" class="tpw-btn tpw-btn-secondary" id="tpw_add_category_btn"><?php echo esc_html__('Add Category','tpw-ilungu-club'); ?></button>
            <span class="tpw-add-cat-msg" style="font-size:12px;color:#6b7280;"></span>
          </div>
        </div>
        <div class="tpw-field">
          <label><?php echo esc_html__('Featured Image', 'tpw-ilungu-club'); ?></label>
          <div class="tpw-media-picker">
            <input type="hidden" name="thumbnail_id" value="" />
            <button class="tpw-btn tpw-btn-secondary tpw-pick-image" type="button"><?php echo esc_html__('Choose Image','tpw-ilungu-club'); ?></button>
            <div class="tpw-image-preview" style="margin-top:8px"></div>
          </div>
        </div>
        <div class="tpw-field">
          <label><?php echo esc_html__('Content', 'tpw-ilungu-club'); ?></label>
          <?php wp_editor('', 'tpw_notice_content', [ 'media_buttons' => true, 'textarea_rows' => 15 ]); ?>
          <textarea name="content" id="tpw_notice_content_hidden" style="display:none;"></textarea>
        </div>
        <div class="tpw-field">
          <label><?php echo esc_html__('Excerpt', 'tpw-ilungu-club'); ?></label>
          <textarea name="excerpt" rows="3"></textarea>
        </div>
        <div class="tpw-actions">
          <button type="submit" class="tpw-btn tpw-btn-primary"><?php echo esc_html__('Save Notice', 'tpw-ilungu-club'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

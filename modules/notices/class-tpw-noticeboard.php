<?php
if (!defined('ABSPATH')) { exit; }

class TPW_Noticeboard {
    public static function init() {
        add_action('init', [__CLASS__, 'register_cpt_and_tax']);
        add_filter('single_template', [__CLASS__, 'filter_single_template']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_single_assets']);
    }

    public static function register_cpt_and_tax() {
        $labels = [
            'name'               => __('Noticeboard Notices', 'tpw-ilungu-club'),
            'singular_name'      => __('Notice', 'tpw-ilungu-club'),
            'all_items'          => __('All Noticeboard Notices', 'tpw-ilungu-club'),
            'add_new'            => __('Add New Notice', 'tpw-ilungu-club'),
            'add_new_item'       => __('Add New Notice', 'tpw-ilungu-club'),
            'edit_item'          => __('Edit Notice', 'tpw-ilungu-club'),
            'new_item'           => __('New Notice', 'tpw-ilungu-club'),
            'view_item'          => __('View Notice', 'tpw-ilungu-club'),
            'archives'           => __('Notice Archives', 'tpw-ilungu-club'),
            'search_items'       => __('Search Notices', 'tpw-ilungu-club'),
            'not_found'          => __('No notices found', 'tpw-ilungu-club'),
            'not_found_in_trash' => __('No notices found in Trash', 'tpw-ilungu-club'),
            'menu_name'          => __('Noticeboard', 'tpw-ilungu-club'),
        ];

        $args = [
            'label'              => __('Noticeboard Notices', 'tpw-ilungu-club'),
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_rest'       => true,
            'show_in_nav_menus'  => true,
            'has_archive'        => false,
            'exclude_from_search'=> true,
            'hierarchical'       => false,
            'menu_icon'          => 'dashicons-megaphone',
            'supports'           => ['title','editor','excerpt','thumbnail','custom-fields'],
            'rewrite'            => true,
            'capability_type'    => 'post',
        ];
        register_post_type('tpw_notice', $args);

        $tax_labels = [
            'name'              => __('Notice Categories', 'tpw-ilungu-club'),
            'singular_name'     => __('Notice Category', 'tpw-ilungu-club'),
            'search_items'      => __('Search Notice Categories', 'tpw-ilungu-club'),
            'all_items'         => __('All Notice Categories', 'tpw-ilungu-club'),
            'parent_item'       => __('Parent Category', 'tpw-ilungu-club'),
            'parent_item_colon' => __('Parent Category:', 'tpw-ilungu-club'),
            'edit_item'         => __('Edit Category', 'tpw-ilungu-club'),
            'update_item'       => __('Update Category', 'tpw-ilungu-club'),
            'add_new_item'      => __('Add New Category', 'tpw-ilungu-club'),
            'new_item_name'     => __('New Category Name', 'tpw-ilungu-club'),
            'menu_name'         => __('Notice Categories', 'tpw-ilungu-club'),
        ];
        $tax_args = [
            'hierarchical' => true,
            'labels'       => $tax_labels,
            'show_ui'      => true,
            'show_admin_column' => true,
            'query_var'    => true,
            'rewrite'      => [ 'slug' => 'notice-category' ],
            'show_in_rest' => true,
        ];
        register_taxonomy('tpw_notice_category', ['tpw_notice'], $tax_args);
    }

    protected static function get_compat_single_template() {
        if ( ! is_singular( 'tpw_notice' ) ) {
            return '';
        }

        $use_compat_template = apply_filters( 'tpw_notice_use_legacy_single_template_fallback', false );
        if ( ! $use_compat_template ) {
            return '';
        }

        $theme_template = locate_template( [ 'single-tpw_notice.php' ] );
        if ( ! empty( $theme_template ) ) {
            return '';
        }

        $plugin_template = TPW_CORE_PATH . 'modules/notices/templates/single-tpw_notice.php';

        return file_exists( $plugin_template ) ? $plugin_template : '';
    }

    public static function filter_single_template($template) {
        $compat_template = self::get_compat_single_template();

        if ( '' !== $compat_template ) {
            return $compat_template;
        }

        return $template;
    }

    public static function enqueue_single_assets() {
        if ( '' !== self::get_compat_single_template() ) {
            wp_enqueue_style('tpw-notice-single', TPW_CORE_URL . 'modules/notices/assets/css/notice-single.css', [], '1.0');
        }
    }
}

TPW_Noticeboard::init();

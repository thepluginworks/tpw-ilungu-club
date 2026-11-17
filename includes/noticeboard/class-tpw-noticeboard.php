<?php
if (!defined('ABSPATH')) { exit; }

/*
 * Deprecated legacy Noticeboard CPT bootstrap.
 *
 * The active Core bootstrap now loads modules/notices/class-tpw-noticeboard.php.
 * Keep this file temporarily for backwards compatibility with older custom
 * includes-based bootstraps only.
 */

class TPW_Noticeboard {
    public static function init() {
        add_action('init', [__CLASS__, 'register_cpt_and_tax']);
    }

    public static function register_cpt_and_tax() {
        // CPT: tpw_notice
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

        // Taxonomy: tpw_notice_category
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
}

TPW_Noticeboard::init();

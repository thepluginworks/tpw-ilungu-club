<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPW_Elementor_Widget_Gallery_Index extends \Elementor\Widget_Base {
    public function get_name() {
        return 'tpw_gallery_index';
    }

    public function get_title() {
        return __( 'TPW Gallery Index', 'tpw-ilungu-club' );
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'tpw-ilungu-club' ),
            ]
        );

        $this->add_control(
            'layout',
            [
                'label'   => __( 'Index layout', 'tpw-ilungu-club' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => __( 'Grid', 'tpw-ilungu-club' ),
                    'list' => __( 'List', 'tpw-ilungu-club' ),
                ],
            ]
        );

        $this->add_control(
            'columns',
            [
                'label'     => __( 'Index columns', 'tpw-ilungu-club' ),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => '3',
                'options'   => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'condition' => [
                    'layout' => 'grid',
                ],
            ]
        );

        $this->add_control(
            'detail_view',
            [
                'label'   => __( 'Selected gallery view', 'tpw-ilungu-club' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid'  => __( 'Grid', 'tpw-ilungu-club' ),
                    'list'  => __( 'List', 'tpw-ilungu-club' ),
                    'story' => __( 'Story', 'tpw-ilungu-club' ),
                ],
            ]
        );

        $this->add_control(
            'detail_columns',
            [
                'label'     => __( 'Selected gallery columns', 'tpw-ilungu-club' ),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => '3',
                'options'   => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'condition' => [
                    'detail_view' => 'grid',
                ],
            ]
        );

        $this->add_control(
            'show_gallery_heading',
            [
                'label'        => __( 'Show selected gallery heading', 'tpw-ilungu-club' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'tpw-ilungu-club' ),
                'label_off'    => __( 'No', 'tpw-ilungu-club' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        if ( function_exists( 'tpw_gallery_render_index' ) ) {
            echo tpw_gallery_render_index([
                'layout'               => isset( $settings['layout'] ) ? (string) $settings['layout'] : 'grid',
                'columns'              => isset( $settings['columns'] ) ? (int) $settings['columns'] : 3,
                'detail_view'          => isset( $settings['detail_view'] ) ? (string) $settings['detail_view'] : 'grid',
                'detail_columns'       => isset( $settings['detail_columns'] ) ? (int) $settings['detail_columns'] : 3,
                'show_gallery_heading' => ( ! isset( $settings['show_gallery_heading'] ) || (string) $settings['show_gallery_heading'] === 'yes' ) ? '1' : '0',
            ]);
        }
    }
}
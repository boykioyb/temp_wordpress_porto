<?php

function porto_shortcode_template( $name = false ) {
    if (!$name)
        return false;

    if ( $overridden_template = locate_template( 'vc_templates' . $name . '.php' ) ) {
        return $overridden_template;
    } else {
        // If neither the child nor parent theme have overridden the template,
        // we load the template from the 'templates' sub-directory of the directory this file is in
        return PORTO_SHORTCODES_TEMPLATES . $name . '.php';
    }
}

function porto_shortcode_woo_template( $name = false ) {
    if (!$name)
        return false;

    if ( $overridden_template = locate_template( 'vc_templates' . $name . '.php' ) ) {
        return $overridden_template;
    } else {
        // If neither the child nor parent theme have overridden the template,
        // we load the template from the 'templates' sub-directory of the directory this file is in
        return PORTO_SHORTCODES_WOO_TEMPLATES . $name . '.php';
    }
}

function porto_shortcode_extract_class( $el_class ) {
    $output = '';
    if ( $el_class != '' ) {
        $output = " " . str_replace( ".", "", $el_class );
    }

    return $output;
}

function porto_shortcode_end_block_comment( $string ) {
    return WP_DEBUG ? '<!-- END ' . $string . ' -->' : '';
}

function porto_shortcode_js_remove_wpautop( $content, $autop = false ) {

    if ( $autop ) {
        $content = wpautop( preg_replace( '/<\/?p\>/', "\n", $content ) . "\n" );
    }

    return do_shortcode( shortcode_unautop( $content ) );
}

function porto_shortcode_image_resize( $attach_id = null, $img_url = null, $width, $height, $crop = false ) {
    // this is an attachment, so we have the ID
    $image_src = array();
    if ( $attach_id ) {
        $image_src = wp_get_attachment_image_src( $attach_id, 'full' );
        $actual_file_path = get_attached_file( $attach_id );
        // this is not an attachment, let's use the image url
    } else if ( $img_url ) {
        $file_path = parse_url( $img_url );
        $actual_file_path = $_SERVER['DOCUMENT_ROOT'] . $file_path['path'];
        $actual_file_path = ltrim( $file_path['path'], '/' );
        $actual_file_path = rtrim( ABSPATH, '/' ) . $file_path['path'];
        $orig_size = getimagesize( $actual_file_path );
        $image_src[0] = $img_url;
        $image_src[1] = $orig_size[0];
        $image_src[2] = $orig_size[1];
    }
    if(!empty($actual_file_path)) {
        $file_info = pathinfo( $actual_file_path );
        $extension = '.' . $file_info['extension'];

        // the image path without the extension
        $no_ext_path = $file_info['dirname'] . '/' . $file_info['filename'];

        $cropped_img_path = $no_ext_path . '-' . $width . 'x' . $height . $extension;

        // checking if the file size is larger than the target size
        // if it is smaller or the same size, stop right here and return
        if ( $image_src[1] > $width || $image_src[2] > $height ) {

            // the file is larger, check if the resized version already exists (for $crop = true but will also work for $crop = false if the sizes match)
            if ( file_exists( $cropped_img_path ) ) {
                $cropped_img_url = str_replace( basename( $image_src[0] ), basename( $cropped_img_path ), $image_src[0] );
                $vt_image = array(
                    'url' => $cropped_img_url,
                    'width' => $width,
                    'height' => $height
                );

                return $vt_image;
            }

            // $crop = false
            if ( $crop == false ) {
                // calculate the size proportionaly
                $proportional_size = wp_constrain_dimensions( $image_src[1], $image_src[2], $width, $height );
                $resized_img_path = $no_ext_path . '-' . $proportional_size[0] . 'x' . $proportional_size[1] . $extension;

                // checking if the file already exists
                if ( file_exists( $resized_img_path ) ) {
                    $resized_img_url = str_replace( basename( $image_src[0] ), basename( $resized_img_path ), $image_src[0] );

                    $vt_image = array(
                        'url' => $resized_img_url,
                        'width' => $proportional_size[0],
                        'height' => $proportional_size[1]
                    );

                    return $vt_image;
                }
            }

            // no cache files - let's finally resize it
            $img_editor = wp_get_image_editor( $actual_file_path );

            if ( is_wp_error( $img_editor ) || is_wp_error( $img_editor->resize( $width, $height, $crop ) ) ) {
                return array(
                    'url' => '',
                    'width' => '',
                    'height' => ''
                );
            }

            $new_img_path = $img_editor->generate_filename();

            if ( is_wp_error( $img_editor->save( $new_img_path ) ) ) {
                return array(
                    'url' => '',
                    'width' => '',
                    'height' => ''
                );
            }
            if ( ! is_string( $new_img_path ) ) {
                return array(
                    'url' => '',
                    'width' => '',
                    'height' => ''
                );
            }

            $new_img_size = getimagesize( $new_img_path );
            $new_img = str_replace( basename( $image_src[0] ), basename( $new_img_path ), $image_src[0] );

            // resized output
            $vt_image = array(
                'url' => $new_img,
                'width' => $new_img_size[0],
                'height' => $new_img_size[1]
            );

            return $vt_image;
        }

        // default output - without resizing
        $vt_image = array(
            'url' => $image_src[0],
            'width' => $image_src[1],
            'height' => $image_src[2]
        );

        return $vt_image;
    }
    return false;
}

function porto_shortcode_get_image_by_size(
    $params = array(
        'post_id' => null,
        'attach_id' => null,
        'thumb_size' => 'thumbnail',
        'class' => ''
    )
) {
    //array( 'post_id' => $post_id, 'thumb_size' => $grid_thumb_size )
    if ( ( ! isset( $params['attach_id'] ) || $params['attach_id'] == null ) && ( ! isset( $params['post_id'] ) || $params['post_id'] == null ) ) {
        return false;
    }
    $post_id = isset( $params['post_id'] ) ? $params['post_id'] : 0;

    if ( $post_id ) {
        $attach_id = get_post_thumbnail_id( $post_id );
    } else {
        $attach_id = $params['attach_id'];
    }

    $thumb_size = $params['thumb_size'];
    $thumb_class = ( isset( $params['class'] ) && $params['class'] != '' ) ? $params['class'] . ' ' : '';

    global $_wp_additional_image_sizes;
    $thumbnail = '';

    if ( is_string( $thumb_size ) && ( ( ! empty( $_wp_additional_image_sizes[ $thumb_size ] ) && is_array( $_wp_additional_image_sizes[ $thumb_size ] ) ) || in_array( $thumb_size, array(
                'thumbnail',
                'thumb',
                'medium',
                'large',
                'full'
            ) ) )
    ) {
        $thumbnail = wp_get_attachment_image( $attach_id, $thumb_size, false, array( 'class' => $thumb_class . 'attachment-' . $thumb_size ) );
    } elseif ( $attach_id ) {
        if ( is_string( $thumb_size ) ) {
            preg_match_all( '/\d+/', $thumb_size, $thumb_matches );
            if ( isset( $thumb_matches[0] ) ) {
                $thumb_size = array();
                if ( count( $thumb_matches[0] ) > 1 ) {
                    $thumb_size[] = $thumb_matches[0][0]; // width
                    $thumb_size[] = $thumb_matches[0][1]; // height
                } elseif ( count( $thumb_matches[0] ) > 0 && count( $thumb_matches[0] ) < 2 ) {
                    $thumb_size[] = $thumb_matches[0][0]; // width
                    $thumb_size[] = $thumb_matches[0][0]; // height
                } else {
                    $thumb_size = false;
                }
            }
        }
        if ( is_array( $thumb_size ) ) {
            // Resize image to custom size
            $p_img = porto_shortcode_image_resize( $attach_id, null, $thumb_size[0], $thumb_size[1], true );
            $alt = trim( strip_tags( get_post_meta( $attach_id, '_wp_attachment_image_alt', true ) ) );
            $attachment = get_post( $attach_id );
            if(!empty($attachment)) {
                $title = trim( strip_tags( $attachment->post_title ) );

                if ( empty( $alt ) ) {
                    $alt = trim( strip_tags( $attachment->post_excerpt ) ); // If not, Use the Caption
                }
                if ( empty( $alt ) ) {
                    $alt = $title;
                } // Finally, use the title
                if ( $p_img ) {
                    $img_class = '';
                    //if ( $grid_layout == 'thumbnail' ) $img_class = ' no_bottom_margin'; class="'.$img_class.'"
                    $thumbnail = '<img class="' . esc_attr( $thumb_class ) . '" src="' . esc_attr( $p_img['url'] ) . '" width="' . esc_attr( $p_img['width'] ) . '" height="' . esc_attr( $p_img['height'] ) . '" alt="' . esc_attr( $alt ) . '" title="' . esc_attr( $title ) . '" />';
                }
            }
        }
    }

    $p_img_large = wp_get_attachment_image_src( $attach_id, 'large' );

    return apply_filters( 'vc_wpb_getimagesize', array(
        'thumbnail' => $thumbnail,
        'p_img_large' => $p_img_large
    ), $attach_id, $params );
}

function porto_vc_animation_type() {
    return array(
        "type" => "porto_animation_type",
        "heading" => __("Animation Type", 'porto-shortcodes'),
        "param_name" => "animation_type",
        "group" => __('Animation', 'porto-shortcodes')
    );
}

function porto_vc_animation_duration() {
    return array(
        "type" => "textfield",
        "heading" => __("Animation Duration", 'porto-shortcodes'),
        "param_name" => "animation_duration",
        "description" => __("numerical value (unit: milliseconds)", 'porto-shortcodes'),
        "value" => '1000',
        "group" => __('Animation', 'porto-shortcodes')
    );
}

function porto_vc_animation_delay() {
    return array(
        "type" => "textfield",
        "heading" => __("Animation Delay", 'porto-shortcodes'),
        "param_name" => "animation_delay",
        "description" => __("numerical value (unit: milliseconds)", 'porto-shortcodes'),
        "value" => '0',
        "group" => __('Animation', 'porto-shortcodes')
    );
}

function porto_vc_custom_class() {
    return array(
        'type' => 'textfield',
        'heading' => __( 'Extra class name', 'porto-shortcodes' ),
        'param_name' => 'el_class',
        'description' => __( 'If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'porto-shortcodes' )
    );
}

if (!function_exists('porto_vc_commons')) {
    function porto_vc_commons($asset = '') {
        switch ($asset) {
            case 'accordion':         return Porto_VcSharedLibrary::getAccordionType();
            case 'accordion_size':    return Porto_VcSharedLibrary::getAccordionSize();
            case 'toggle_type':       return Porto_VcSharedLibrary::getToggleType();
            case 'toggle_size':       return Porto_VcSharedLibrary::getToggleSize();
            case 'align':             return Porto_VcSharedLibrary::getTextAlign();
            case 'tabs':              return Porto_VcSharedLibrary::getTabsPositions();
            case 'tabs_type':         return Porto_VcSharedLibrary::getTabsType();
            case 'tabs_icon_style':   return Porto_VcSharedLibrary::getTabsIconStyle();
            case 'tabs_icon_effect':  return Porto_VcSharedLibrary::getTabsIconEffect();
            case 'tour':              return Porto_VcSharedLibrary::getTourPositions();
            case 'tour_type':         return Porto_VcSharedLibrary::getTourType();
            case 'separator':         return Porto_VcSharedLibrary::getSeparator();
            case 'separator_type':    return Porto_VcSharedLibrary::getSeparatorType();
            case 'separator_style':   return Porto_VcSharedLibrary::getSeparatorStyle();
            case 'separator_icon_style':   return Porto_VcSharedLibrary::getSeparatorIconStyle();
            case 'separator_icon_size':    return Porto_VcSharedLibrary::getSeparatorIconSize();
            case 'separator_icon_pos':     return Porto_VcSharedLibrary::getSeparatorIconPosition();
            case 'separator_elements':     return Porto_VcSharedLibrary::getSeparatorElements();
            case 'blog_layout':            return Porto_VcSharedLibrary::getBlogLayout();
            case 'blog_grid_columns':      return Porto_VcSharedLibrary::getBlogGridColumns();
            case 'portfolio_layout':       return Porto_VcSharedLibrary::getPortfolioLayout();
            case 'portfolio_grid_columns': return Porto_VcSharedLibrary::getPortfolioGridColumns();
            case 'portfolio_grid_view':    return Porto_VcSharedLibrary::getPortfolioGridView();
            case 'products_view_mode':     return Porto_VcSharedLibrary::getProductsViewMode();
            case 'products_columns':       return Porto_VcSharedLibrary::getProductsColumns();
            case 'products_column_width':  return Porto_VcSharedLibrary::getProductsColumnWidth();
            case 'products_addlinks_pos':  return Porto_VcSharedLibrary::getProductsAddlinksPos();
            case 'product_view_mode':      return Porto_VcSharedLibrary::getProductViewMode();
            case 'content_boxes_bg_type':  return Porto_VcSharedLibrary::getContentBoxesBgType();
            case 'content_boxes_style':    return Porto_VcSharedLibrary::getContentBoxesStyle();
            case 'content_box_effect':     return Porto_VcSharedLibrary::getContentBoxEffect();
            case 'colors':                 return Porto_VcSharedLibrary::getColors();
            case 'testimonial_styles':     return Porto_VcSharedLibrary::getTestimonialStyles();
            case 'contextual':             return Porto_VcSharedLibrary::getContextual();
            case 'progress_border_radius': return Porto_VcSharedLibrary::getProgressBorderRadius();
            case 'progress_size':          return Porto_VcSharedLibrary::getProgressSize();
            case 'circular_view_type':     return Porto_VcSharedLibrary::getCircularViewType();
            case 'circular_view_size':     return Porto_VcSharedLibrary::getCircularViewSize();
            case 'section_skin':           return Porto_VcSharedLibrary::getSectionSkin();
            case 'section_color_scale':    return Porto_VcSharedLibrary::getSectionColorScale();
            case 'section_text_color':     return Porto_VcSharedLibrary::getSectionTextColor();
            case 'position':               return Porto_VcSharedLibrary::getPosition();
            case 'size':                   return Porto_VcSharedLibrary::getSize();
            case 'trigger':                return Porto_VcSharedLibrary::getTrigger();
            case 'heading_border_type':    return Porto_VcSharedLibrary::getHeadingBorderType();
            case 'heading_border_size':    return Porto_VcSharedLibrary::getHeadingBorderSize();
            case 'bootstrap_columns':      return Porto_VcSharedLibrary::getBootstrapColumns();
            case 'price_boxes_style':      return Porto_VcSharedLibrary::getPriceBoxesStyle();
            case 'price_boxes_size':       return Porto_VcSharedLibrary::getPriceBoxesSize();
            case 'sort_style':             return Porto_VcSharedLibrary::getSortStyle();
            case 'sort_by':                return Porto_VcSharedLibrary::getSortBy();
            case 'grid_columns':           return Porto_VcSharedLibrary::getGridColumns();
            case 'preview_time':           return Porto_VcSharedLibrary::getPreviewTime();
            case 'preview_position':       return Porto_VcSharedLibrary::getPreviewPosition();
            default: return array();
        }
    }
}

function porto_vc_woo_order_by() {
    return array(
        '',
        __( 'Date', 'js_composer' ) => 'date',
        __( 'ID', 'js_composer' ) => 'ID',
        __( 'Author', 'js_composer' ) => 'author',
        __( 'Title', 'js_composer' ) => 'title',
        __( 'Modified', 'js_composer' ) => 'modified',
        __( 'Random', 'js_composer' ) => 'rand',
        __( 'Comment count', 'js_composer' ) => 'comment_count',
        __( 'Menu order', 'js_composer' ) => 'menu_order',
    );
}

function porto_vc_woo_order_way() {
    return array(
        '',
        __( 'Descending', 'js_composer' ) => 'DESC',
        __( 'Ascending', 'js_composer' ) => 'ASC',
    );
}

if (!class_exists('Porto_VcSharedLibrary')) {
    class Porto_VcSharedLibrary {

        public static function getTextAlign() {
            return array(
                __('None', 'porto-shortcodes') => '',
                __('Left', 'porto-shortcodes' ) => 'left',
                __('Right', 'porto-shortcodes' ) => 'right',
                __('Center', 'porto-shortcodes' ) => 'center',
                __('Justify', 'porto-shortcodes' ) => 'justify'
            );
        }

        public static function getTabsPositions() {
            return array(
                __('Top left', 'porto-shortcodes' ) => '',
                __('Top right', 'porto-shortcodes' ) => 'top-right',
                __('Bottom left', 'porto-shortcodes' ) => 'bottom-left',
                __('Bottom right', 'porto-shortcodes' ) => 'bottom-right',
                __('Top justify', 'porto-shortcodes' ) => 'top-justify',
                __('Bottom justify', 'porto-shortcodes' ) => 'bottom-justify',
                __('Top center', 'porto-shortcodes' ) => 'top-center',
                __('Bottom center', 'porto-shortcodes' ) => 'bottom-center',
            );
        }

        public static function getTabsType() {
            return array(
                __('Default', 'porto-shortcodes' ) => '',
                __('Simple', 'porto-shortcodes' ) => 'tabs-simple'
            );
        }

        public static function getTabsIconStyle() {
            return array(
                __('Default', 'porto-shortcodes' ) => '',
                __('Style 1', 'porto-shortcodes' ) => 'featured-boxes-style-1',
                __('Style 2', 'porto-shortcodes' ) => 'featured-boxes-style-2',
                __('Style 3', 'porto-shortcodes' ) => 'featured-boxes-style-3',
                __('Style 4', 'porto-shortcodes' ) => 'featured-boxes-style-4',
                __('Style 5', 'porto-shortcodes' ) => 'featured-boxes-style-5',
                __('Style 6', 'porto-shortcodes' ) => 'featured-boxes-style-6',
                __('Style 7', 'porto-shortcodes' ) => 'featured-boxes-style-7',
                __('Style 8', 'porto-shortcodes' ) => 'featured-boxes-style-8',
            );
        }

        public static function getTabsIconEffect() {
            return array(
                __('Default', 'porto-shortcodes' ) => '',
                __('Effect 1', 'porto-shortcodes' ) => 'featured-box-effect-1',
                __('Effect 2', 'porto-shortcodes' ) => 'featured-box-effect-2',
                __('Effect 3', 'porto-shortcodes' ) => 'featured-box-effect-3',
                __('Effect 4', 'porto-shortcodes' ) => 'featured-box-effect-4',
                __('Effect 5', 'porto-shortcodes' ) => 'featured-box-effect-5',
                __('Effect 6', 'porto-shortcodes' ) => 'featured-box-effect-6',
                __('Effect 7', 'porto-shortcodes' ) => 'featured-box-effect-7',
            );
        }

        public static function getTourPositions() {
            return array(
                __('Left', 'porto-shortcodes' ) => 'vertical-left',
                __('Right', 'porto-shortcodes' ) => 'vertical-right',
            );
        }

        public static function getTourType() {
            return array(
                __('Default', 'porto-shortcodes' ) => '',
                __('Navigation', 'porto-shortcodes' ) => 'tabs-navigation',
            );
        }

        public static function getSeparator() {
            return array(
                __('Normal', 'porto-shortcodes' ) => '',
                __('Short', 'porto-shortcodes' ) => 'short',
                __('Tall', 'porto-shortcodes' ) => 'tall',
                __('Taller', 'porto-shortcodes' ) => 'taller',
            );
        }

        public static function getSeparatorType() {
            return array(
                __('Normal', 'porto-shortcodes' ) => '',
                __('Small', 'porto-shortcodes' ) => 'small',
            );
        }

        public static function getSeparatorStyle() {
            return array(
                __('Gradient', 'porto-shortcodes' ) => '',
                __('Solid', 'porto-shortcodes' ) => 'solid',
                __('Dashed', 'porto-shortcodes' ) => 'dashed',
                __('Pattern', 'porto-shortcodes' ) => 'pattern',
            );
        }

        public static function getSeparatorIconStyle() {
            return array(
                __('Style 1', 'porto-shortcodes' ) => '',
                __('Style 2', 'porto-shortcodes' ) => 'style-2',
                __('Style 3', 'porto-shortcodes' ) => 'style-3',
                __('Style 4', 'porto-shortcodes' ) => 'style-4',
            );
        }

        public static function getSeparatorIconSize() {
            return array(
                __('Normal', 'porto-shortcodes' ) => '',
                __('Small', 'porto-shortcodes' )  => 'sm',
                __('Large', 'porto-shortcodes' )  => 'lg'
            );
        }

        public static function getSeparatorIconPosition() {
            return array(
                __('Center', 'porto-shortcodes' ) => '',
                __('Left', 'porto-shortcodes' )  => 'left',
                __('Right', 'porto-shortcodes' )  => 'right'
            );
        }

        public static function getSeparatorElements() {
            return array(
                __('h1', 'porto-shortcodes' ) => 'h1',
                __('h2', 'porto-shortcodes' ) => 'h2',
                __('h3', 'porto-shortcodes' ) => 'h3',
                __('h4', 'porto-shortcodes' ) => 'h4',
                __('h5', 'porto-shortcodes' ) => 'h5',
                __('h6', 'porto-shortcodes' ) => 'h6',
                __('p', 'porto-shortcodes' )  => 'p',
                __('div', 'porto-shortcodes' ) => 'div',
            );
        }

        public static function getAccordionType() {
            return array(
                __('Default', 'porto-shortcodes' ) => 'panel-default',
                __('Secondary', 'porto-shortcodes' ) => 'secondary',
                __('Without Background', 'porto-shortcodes' ) => 'without-bg',
                __('Without Borders and Background', 'porto-shortcodes' ) => 'without-bg without-borders',
                __('Custom', 'porto-shortcodes' ) => 'custom',
            );
        }

        public static function getAccordionSize() {
            return array(
                __('Default', 'porto-shortcodes' ) => '',
                __('Small', 'porto-shortcodes' ) => 'panel-group-sm',
                __('Large', 'porto-shortcodes' ) => 'panel-group-lg',
            );
        }

        public static function getToggleType() {
            return array(
                __('Default', 'porto-shortcodes' ) => '',
                __('Simple', 'porto-shortcodes' ) => 'toggle-simple'
            );
        }

        public static function getToggleSize() {
            return array(
                __('Default', 'porto-shortcodes' ) => '',
                __('Small', 'porto-shortcodes' ) => 'toggle-sm',
                __('Large', 'porto-shortcodes' ) => 'toggle-lg',
            );
        }

        public static function getBlogLayout() {
            return array(
                __('Full', 'porto-shortcodes' ) => 'full',
                __('Large', 'porto-shortcodes' ) => 'large',
                __('Large Alt', 'porto-shortcodes' ) => 'large-alt',
                __('Medium', 'porto-shortcodes' ) => 'medium',
                __('Grid', 'porto-shortcodes' ) => 'grid',
                __('Timeline', 'porto-shortcodes' ) => 'timeline'
            );
        }

        public static function getBlogGridColumns() {
            return array(
                __('2', 'porto-shortcodes' ) => '2',
                __('3', 'porto-shortcodes' ) => '3',
                __('4', 'porto-shortcodes' ) => '4'
            );
        }

        public static function getPortfolioLayout() {
            return array(
                __('Grid', 'porto-shortcodes' ) => 'grid',
                __('Timeline', 'porto-shortcodes' ) => 'timeline',
                __('Medium', 'porto-shortcodes' ) => 'medium',
                __('Large', 'porto-shortcodes' ) => 'large',
                __('Full', 'porto-shortcodes' ) => 'full'
            );
        }

        public static function getPortfolioGridColumns() {
            return array(
                __('2', 'porto-shortcodes' ) => '2',
                __('3', 'porto-shortcodes' ) => '3',
                __('4', 'porto-shortcodes' ) => '4',
                __('5', 'porto-shortcodes' ) => '5',
                __('6', 'porto-shortcodes' ) => '6'
            );
        }

        public static function getPortfolioGridView() {
            return array(
                __('Standard', 'porto-shortcodes' ) => 'classic',
                __('Default', 'porto-shortcodes' ) => 'default',
                __('Out of Image', 'porto-shortcodes' ) => 'outimage',
                __('Full Width', 'porto-shortcodes' ) => 'full'
            );
        }

        public static function getProductsViewMode() {
            return array(
                __( 'Grid', 'porto-shortcodes' )=> 'grid',
                __( 'List', 'porto-shortcodes' ) => 'list',
                __( 'Slider', 'porto-shortcodes' )  => 'products-slider',
            );
        }

        public static function getProductsColumns() {
            return array(
                '1' => 1,
                '2' => 2,
                '3' => 3,
                '4' => 4,
                '5' => 5,
                '6' => 6,
                '7 ' . __( '(without sidebar)', 'porto-shortcodes' ) => 7,
                '8 ' . __( '(without sidebar)', 'porto-shortcodes' ) => 8
            );
        }

        public static function getProductsColumnWidth() {
            return array(
                __( 'Default', 'porto-shortcodes' ) => '',
                '1/1' . __( ' of content width', 'porto-shortcodes' ) => 1,
                '1/2' . __( ' of content width', 'porto-shortcodes' ) => 2,
                '1/3' . __( ' of content width', 'porto-shortcodes' ) => 3,
                '1/4' . __( ' of content width', 'porto-shortcodes' ) => 4,
                '1/5' . __( ' of content width', 'porto-shortcodes' ) => 5,
                '1/6' . __( ' of content width', 'porto-shortcodes' ) => 6,
                '1/7' . __( ' of content width (without sidebar)', 'porto-shortcodes' ) => 7,
                '1/8' . __( ' of content width (without sidebar)', 'porto-shortcodes' ) => 8
            );
        }

        public static function getProductsAddlinksPos() {
            return array(
                __( 'Default', 'porto-shortcodes' ) => '',
                __( 'Out of Image', 'porto-shortcodes' ) => 'outimage',
                __( 'On Image', 'porto-shortcodes' ) => 'onimage',
                __( 'Wishlist, Quick View On Image', 'porto-shortcodes' ) => 'wq_onimage'
            );
        }

        public static function getProductViewMode() {
            return array(
                __( 'Grid', 'porto-shortcodes' )=> 'grid',
                __( 'List', 'porto-shortcodes' ) => 'list',
            );
        }

        public static function getColors() {
            return array(
                '' => 'custom',
                __( 'Primary', 'porto-shortcodes' ) => 'primary',
                __( 'Secondary', 'porto-shortcodes' ) => 'secondary',
                __( 'Tertiary', 'porto-shortcodes' ) => 'tertiary',
                __( 'Quaternary', 'porto-shortcodes' ) => 'quaternary',
                __( 'Dark', 'porto-shortcodes' ) => 'dark',
                __( 'Light', 'porto-shortcodes' ) => 'light',
            );
        }

        public static function getContentBoxesBgType() {
            return array(
                __( 'Default', 'porto-shortcodes' )=> '',
                __( 'Flat', 'porto-shortcodes' ) => 'featured-boxes-flat',
                __( 'Custom', 'porto-shortcodes' ) => 'featured-boxes-custom',
            );
        }

        public static function getContentBoxesStyle() {
            return array(
                __('Default', 'porto-shortcodes' ) => '',
                __('Style 1', 'porto-shortcodes' ) => 'featured-boxes-style-1',
                __('Style 2', 'porto-shortcodes' ) => 'featured-boxes-style-2',
                __('Style 3', 'porto-shortcodes' ) => 'featured-boxes-style-3',
                __('Style 4', 'porto-shortcodes' ) => 'featured-boxes-style-4',
                __('Style 5', 'porto-shortcodes' ) => 'featured-boxes-style-5',
                __('Style 6', 'porto-shortcodes' ) => 'featured-boxes-style-6',
                __('Style 7', 'porto-shortcodes' ) => 'featured-boxes-style-7',
                __('Style 8', 'porto-shortcodes' ) => 'featured-boxes-style-8',
            );
        }

        public static function getContentBoxEffect() {
            return array(
                __('Default', 'porto-shortcodes' ) => '',
                __('Effect 1', 'porto-shortcodes' ) => 'featured-box-effect-1',
                __('Effect 2', 'porto-shortcodes' ) => 'featured-box-effect-2',
                __('Effect 3', 'porto-shortcodes' ) => 'featured-box-effect-3',
                __('Effect 4', 'porto-shortcodes' ) => 'featured-box-effect-4',
                __('Effect 5', 'porto-shortcodes' ) => 'featured-box-effect-5',
                __('Effect 6', 'porto-shortcodes' ) => 'featured-box-effect-6',
                __('Effect 7', 'porto-shortcodes' ) => 'featured-box-effect-7',
            );
        }

        public static function getTestimonialStyles() {
            return array(
                __('Style 1', 'porto-shortcodes' ) => '',
                __('Style 2', 'porto-shortcodes' ) => 'testimonial-style-2',
                __('Style 3', 'porto-shortcodes' ) => 'testimonial-style-3',
                __('Style 4', 'porto-shortcodes' ) => 'testimonial-style-4',
                __('Style 5', 'porto-shortcodes' ) => 'testimonial-style-5',
                __('Style 6', 'porto-shortcodes' ) => 'testimonial-style-6',
            );
        }

        public static function getContextual() {
            return array(
                __('None', 'porto-shortcodes' )    => '',
                __('Success', 'porto-shortcodes' ) => 'success',
                __('Info', 'porto-shortcodes' )    => 'info',
                __('Warning', 'porto-shortcodes' ) => 'warning',
                __('Danger', 'porto-shortcodes' )  => 'danger',
            );
        }

        public static function getProgressBorderRadius() {
            return array(
                __('Default', 'porto-shortcodes' )               => '',
                __('No Border Radius', 'porto-shortcodes' )      => 'no-border-radius',
                __('Rounded Border Radius', 'porto-shortcodes' ) => 'border-radius',
                __('Circled Border Radius', 'porto-shortcodes' ) => 'circled-border-radius',
            );
        }

        public static function getProgressSize() {
            return array(
                __('Normal', 'porto-shortcodes' ) => '',
                __('Small', 'porto-shortcodes' )  => 'sm',
                __('Large', 'porto-shortcodes' )  => 'lg'
            );
        }

        public static function getCircularViewType() {
            return array(
                __('Show Title and Value', 'porto-shortcodes' ) => '',
                __('Show Only Icon', 'porto-shortcodes' )  => 'only-icon',
                __('Show Only Title', 'porto-shortcodes' )  => 'single-line'
            );
        }

        public static function getCircularViewSize() {
            return array(
                __('Normal', 'porto-shortcodes' ) => '',
                __('Small', 'porto-shortcodes' )  => 'sm',
                __('Large', 'porto-shortcodes' )  => 'lg'
            );
        }

        public static function getSectionSkin() {
            return array(
                __('Default', 'porto-shortcodes')    => 'default',
                __('Transparent', 'porto-shortcodes')    => 'parallax',
                __('Primary', 'porto-shortcodes')    => 'primary',
                __('Secondary', 'porto-shortcodes')  => 'secondary',
                __('Tertiary', 'porto-shortcodes')   => 'tertiary',
                __('Quaternary', 'porto-shortcodes') => 'quaternary',
                __('Dark', 'porto-shortcodes')       => 'dark',
                __('Light', 'porto-shortcodes')      => 'light',
            );
        }

        public static function getSectionColorScale() {
            return array(
                __('Default', 'porto-shortcodes') => '',
                __('Scale 1', 'porto-shortcodes') => 'scale-1',
                __('Scale 2', 'porto-shortcodes') => 'scale-2',
                __('Scale 3', 'porto-shortcodes') => 'scale-3',
                __('Scale 4', 'porto-shortcodes') => 'scale-4',
                __('Scale 5', 'porto-shortcodes') => 'scale-5',
                __('Scale 6', 'porto-shortcodes') => 'scale-6',
                __('Scale 7', 'porto-shortcodes') => 'scale-7',
                __('Scale 8', 'porto-shortcodes') => 'scale-8',
                __('Scale 9', 'porto-shortcodes') => 'scale-9',
            );
        }

        public static function getSectionTextColor() {
            return array(
                __('Default', 'porto-shortcodes') => '',
                __('Dark', 'porto-shortcodes')    => 'dark',
                __('Light', 'porto-shortcodes')   => 'light',
            );
        }

        public static function getPosition() {
            return array(
                __('Top', 'porto-shortcodes')     => 'top',
                __('Right', 'porto-shortcodes')   => 'right',
                __('Bottom', 'porto-shortcodes')  => 'bottom',
                __('Left', 'porto-shortcodes')    => 'left',
            );
        }

        public static function getSize() {
            return array(
                __('Normal', 'porto-shortcodes')      => '',
                __('Large', 'porto-shortcodes')       => 'lg',
                __('Small', 'porto-shortcodes')       => 'sm',
                __('Extra Small', 'porto-shortcodes') => 'xs',
            );
        }

        public static function getTrigger() {
            return array(
                __('Click', 'porto-shortcodes')      => 'click',
                __('Hover', 'porto-shortcodes')      => 'hover',
                __('Focus', 'porto-shortcodes')      => 'focus',
            );
        }

        public static function getHeadingBorderType() {
            return array(
                __('Bottom Border', 'porto-shortcodes')          => 'bottom-border',
                __('Bottom Double Border', 'porto-shortcodes')   => 'bottom-double-border',
                __('Middle Border', 'porto-shortcodes')          => 'middle-border',
                __('Middle Border Reverse', 'porto-shortcodes')  => 'middle-border-reverse',
                __('Middle Border Center', 'porto-shortcodes')   => 'middle-border-center',
            );
        }

        public static function getHeadingBorderSize() {
            return array(
                __('Normal', 'porto-shortcodes')       => '',
                __('Extra Small', 'porto-shortcodes')  => 'xs',
                __('Small', 'porto-shortcodes')        => 'sm',
                __('Large', 'porto-shortcodes')        => 'lg',
                __('Extra Large', 'porto-shortcodes')  => 'xl',
            );
        }

        public static function getBootstrapColumns() {
            return array(6, 4, 3, 2, 1);
        }

        public static function getPriceBoxesStyle() {
            return array(
                __('Default', 'porto-shortcodes')      => '',
                __('Alternative', 'porto-shortcodes')  => 'flat',
            );
        }

        public static function getPriceBoxesSize() {
            return array(
                __('Normal', 'porto-shortcodes')      => '',
                __('Small', 'porto-shortcodes')       => 'sm',
            );
        }

        public static function getSortStyle() {
            return array(
                __('Default', 'porto-shortcodes')      => '',
                __('Style 2', 'porto-shortcodes')      => 'style-2',
            );
        }

        public static function getSortBy() {
            return array(
                __('Original Order', 'porto-shortcodes')     => 'original-order',
                __('Popular Value', 'porto-shortcodes')      => 'popular',
            );
        }

        public static function getGridColumns() {
            return array(
                __('12 columns - 1/1', 'porto-shortcodes')   => '12',
                __('11 columns - 11/12', 'porto-shortcodes') => '11',
                __('10 columns - 5/6', 'porto-shortcodes')   => '10',
                __('9 columns - 3/4', 'porto-shortcodes')    => '9',
                __('8 columns - 2/3', 'porto-shortcodes')    => '8',
                __('7 columns - 7/12', 'porto-shortcodes')   => '7',
                __('6 columns - 1/2', 'porto-shortcodes')    => '6',
                __('5 columns - 5/12', 'porto-shortcodes')   => '5',
                __('4 columns - 1/3', 'porto-shortcodes')    => '4',
                __('3 columns - 1/4', 'porto-shortcodes')    => '3',
                __('2 columns - 1/6', 'porto-shortcodes')    => '2',
                __('1 columns - 1/12', 'porto-shortcodes')   => '1',
            );
        }

        public static function getPreviewTime() {
            return array(
                __('Normal', 'porto-shortcodes')   => '',
                __('Short', 'porto-shortcodes')    => 'short',
                __('Long', 'porto-shortcodes')     => 'long',
            );
        }

        public static function getPreviewPosition() {
            return array(
                __('Center', 'porto-shortcodes')   => '',
                __('Top', 'porto-shortcodes')    => 'top',
                __('Bottom', 'porto-shortcodes')     => 'bottom',
            );
        }
    }
}

function porto_shortcode_widget_title( $params = array( 'title' => '' ) ) {
    if ( $params['title'] == '' ) {
        return '';
    }

    $extraclass = ( isset( $params['extraclass'] ) ) ? " " . $params['extraclass'] : "";
    $output = '<h4 class="wpb_heading' . $extraclass . '">' . $params['title'] . '</h4>';

    return apply_filters( 'wpb_widget_title', $output, $params );
}

if (function_exists('vc_add_shortcode_param'))
    vc_add_shortcode_param('porto_animation_type', 'porto_vc_animation_type_field');

function porto_vc_animation_type_field($settings, $value) {
    $param_line = '<select name="' . $settings['param_name'] . '" class="wpb_vc_param_value dropdown wpb-input wpb-select ' . $settings['param_name'] . ' ' . $settings['type'] . '">';

    $param_line .= '<option value="">none</option>';

    $param_line .= '<optgroup label="' . __('Attention Seekers', 'porto-shortcodes') . '">';
    $options = array("bounce", "flash", "pulse", "rubberBand", "shake", "swing", "tada", "wobble");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '<optgroup label="' . __('Bouncing Entrances', 'porto-shortcodes') . '">';
    $options = array("bounceIn", "bounceInDown", "bounceInLeft", "bounceInRight", "bounceInUp");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '<optgroup label="' . __('Bouncing Exits', 'porto-shortcodes') . '">';
    $options = array("bounceOut", "bounceOutDown", "bounceOutLeft", "bounceOutRight", "bounceOutUp");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '<optgroup label="' . __('Fading Entrances', 'porto-shortcodes') . '">';
    $options = array("fadeIn", "fadeInDown", "fadeInDownBig", "fadeInLeft", "fadeInLeftBig", "fadeInRight", "fadeInRightBig", "fadeInUp", "fadeInUpBig");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '<optgroup label="' . __('Fading Exits', 'porto-shortcodes') . '">';
    $options = array("fadeOut", "fadeOutDown", "fadeOutDownBig", "fadeOutLeft", "fadeOutLeftBig", "fadeOutRight", "fadeOutRightBig", "fadeOutUp", "fadeOutUpBig");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '<optgroup label="' . __('Flippers', 'porto-shortcodes') . '">';
    $options = array("flip", "flipInX", "flipInY", "flipOutX", "flipOutY");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '<optgroup label="' . __('Lightspeed', 'porto-shortcodes') . '">';
    $options = array("lightSpeedIn", "lightSpeedOut");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '<optgroup label="' . __('Rotating Entrances', 'porto-shortcodes') . '">';
    $options = array("rotateIn", "rotateInDownLeft", "rotateInDownRight", "rotateInUpLeft", "rotateInUpRight");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '<optgroup label="' . __('Rotating Exits', 'porto-shortcodes') . '">';
    $options = array("rotateOut", "rotateOutDownLeft", "rotateOutDownRight", "rotateOutUpLeft", "rotateOutUpRight");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '<optgroup label="' . __('Sliding Entrances', 'porto-shortcodes') . '">';
    $options = array("slideInUp", "slideInDown", "slideInLeft", "slideInRight");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '<optgroup label="' . __('Sliding Exit', 'porto-shortcodes') . '">';
    $options = array("slideOutUp", "slideOutDown", "slideOutLeft", "slideOutRight");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '<optgroup label="' . __('Specials', 'porto-shortcodes') . '">';
    $options = array("hinge", "rollIn", "rollOut");
    foreach ( $options as $option ) {
        $selected = '';
        if ( $option == $value ) $selected = ' selected="selected"';
        $param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $param_line .= '</optgroup>';

    $param_line .= '</select>';

    return $param_line;
}

function porto_getCategoryChildsFull( $parent_id, $pos, $array, $level, &$dropdown ) {

    for ( $i = $pos; $i < count( $array ); $i ++ ) {
        if ( $array[ $i ]->category_parent == $parent_id ) {
            $name = str_repeat( "- ", $level ) . $array[ $i ]->name;
            $value = $array[ $i ]->slug;
            $dropdown[$name] = $value;
            porto_getCategoryChildsFull( $array[ $i ]->term_id, $i, $array, $level + 1, $dropdown );
        }
    }
}

// Add simple line icon font
if (!function_exists('vc_iconpicker_type_simpleline')) {
    add_filter( 'vc_iconpicker-type-simpleline', 'vc_iconpicker_type_simpleline' );

    function vc_iconpicker_type_simpleline( $icons ) {
        $simpleline_icons = array(
            array( 'Simple-Line-Icons-user-female' => 'User Female' ),
            array( 'Simple-Line-Icons-users' => 'Users' ),
            array( 'Simple-Line-Icons-user-follow' => 'User Follow' ),
            array( 'Simple-Line-Icons-user-following' => 'User Following' ),
            array( 'Simple-Line-Icons-user-unfollow' => 'User Unfollow' ),
            array( 'Simple-Line-Icons-user' => 'User' ),
            array( 'Simple-Line-Icons-trophy' => 'Trophy' ),
            array( 'Simple-Line-Icons-speedometer' => 'Speedometer' ),
            array( 'Simple-Line-Icons-social-youtube' => 'Youtube' ),
            array( 'Simple-Line-Icons-social-twitter' => 'Twitter' ),
            array( 'Simple-Line-Icons-social-tumblr' => 'Tumblr' ),
            array( 'Simple-Line-Icons-social-facebook' => 'Facebook' ),
            array( 'Simple-Line-Icons-social-dropbox' => 'Dropbox' ),
            array( 'Simple-Line-Icons-social-dribbble' => 'Dribbble' ),
            array( 'Simple-Line-Icons-shield' => 'Shield' ),
            array( 'Simple-Line-Icons-screen-tablet' => 'Tablet' ),
            array( 'Simple-Line-Icons-screen-smartphone' => 'Smartphone' ),
            array( 'Simple-Line-Icons-screen-desktop' => 'Desktop' ),
            array( 'Simple-Line-Icons-plane' => 'Plane' ),
            array( 'Simple-Line-Icons-notebook' => 'Notebook' ),
            array( 'Simple-Line-Icons-moustache' => 'Moustache' ),
            array( 'Simple-Line-Icons-mouse' => 'Mouse' ),
            array( 'Simple-Line-Icons-magnet' => 'Magnet' ),
            array( 'Simple-Line-Icons-magic-wand' => 'Magic Wand' ),
            array( 'Simple-Line-Icons-hourglass' => 'Hourglass' ),
            array( 'Simple-Line-Icons-graduation' => 'Graduation' ),
            array( 'Simple-Line-Icons-ghost' => 'Ghost' ),
            array( 'Simple-Line-Icons-game-controller' => 'Game Controller' ),
            array( 'Simple-Line-Icons-fire' => 'Fire' ),
            array( 'Simple-Line-Icons-eyeglasses' => 'Eyeglasses' ),
            array( 'Simple-Line-Icons-envelope-open' => 'Envelope Open' ),
            array( 'Simple-Line-Icons-envelope-letter' => 'Envelope Letter' ),
            array( 'Simple-Line-Icons-energy' => 'Energy' ),
            array( 'Simple-Line-Icons-emotsmile' => 'Emotsmile' ),
            array( 'Simple-Line-Icons-disc' => 'Disc' ),
            array( 'Simple-Line-Icons-cursor-move' => 'Cursor Move' ),
            array( 'Simple-Line-Icons-crop' => 'Crop' ),
            array( 'Simple-Line-Icons-credit-card' => 'Credit Card' ),
            array( 'Simple-Line-Icons-chemistry' => 'Chemistry' ),
            array( 'Simple-Line-Icons-bell' => 'Bell' ),
            array( 'Simple-Line-Icons-badge' => 'Badge' ),
            array( 'Simple-Line-Icons-anchor' => 'Anchor' ),
            array( 'Simple-Line-Icons-wallet' => 'Wallet' ),
            array( 'Simple-Line-Icons-vector' => 'Vector' ),
            array( 'Simple-Line-Icons-speech' => 'Speech' ),
            array( 'Simple-Line-Icons-puzzle' => 'Puzzle' ),
            array( 'Simple-Line-Icons-printer' => 'Printer' ),
            array( 'Simple-Line-Icons-present' => 'Present' ),
            array( 'Simple-Line-Icons-playlist' => 'Playlist' ),
            array( 'Simple-Line-Icons-pin' => 'Pin' ),
            array( 'Simple-Line-Icons-picture' => 'Picture' ),
            array( 'Simple-Line-Icons-map' => 'Map' ),
            array( 'Simple-Line-Icons-layers' => 'Layers' ),
            array( 'Simple-Line-Icons-handbag' => 'Handbag' ),
            array( 'Simple-Line-Icons-globe-alt' => 'Globe Alt' ),
            array( 'Simple-Line-Icons-globe' => 'Globe' ),
            array( 'Simple-Line-Icons-frame' => 'Frame' ),
            array( 'Simple-Line-Icons-folder-alt' => 'Folder Alt' ),
            array( 'Simple-Line-Icons-film' => 'Film' ),
            array( 'Simple-Line-Icons-feed' => 'Feed' ),
            array( 'Simple-Line-Icons-earphones-alt' => 'Earphones Alt' ),
            array( 'Simple-Line-Icons-earphones' => 'Earphones' ),
            array( 'Simple-Line-Icons-drop' => 'Drop' ),
            array( 'Simple-Line-Icons-drawer' => 'Drawer' ),
            array( 'Simple-Line-Icons-docs' => 'Docs' ),
            array( 'Simple-Line-Icons-directions' => 'Directions' ),
            array( 'Simple-Line-Icons-direction' => 'Direction' ),
            array( 'Simple-Line-Icons-diamond' => 'Diamond' ),
            array( 'Simple-Line-Icons-cup' => 'Cup' ),
            array( 'Simple-Line-Icons-compass' => 'Compass' ),
            array( 'Simple-Line-Icons-call-out' => 'Call Out' ),
            array( 'Simple-Line-Icons-call-in' => 'Call In' ),
            array( 'Simple-Line-Icons-call-end' => 'Call End' ),
            array( 'Simple-Line-Icons-calculator' => 'Calculator' ),
            array( 'Simple-Line-Icons-bubbles' => 'Bubbles' ),
            array( 'Simple-Line-Icons-briefcase' => 'Briefcase' ),
            array( 'Simple-Line-Icons-book-open' => 'Book Open' ),
            array( 'Simple-Line-Icons-basket-loaded' => 'Basket Loaded' ),
            array( 'Simple-Line-Icons-basket' => 'Basket' ),
            array( 'Simple-Line-Icons-bag' => 'Bag' ),
            array( 'Simple-Line-Icons-action-undo' => 'Action Undo' ),
            array( 'Simple-Line-Icons-action-redo' => 'Action Redo' ),
            array( 'Simple-Line-Icons-wrench' => 'Wrench' ),
            array( 'Simple-Line-Icons-umbrella' => 'Umbrella' ),
            array( 'Simple-Line-Icons-trash' => 'Trash' ),
            array( 'Simple-Line-Icons-tag' => 'Tag' ),
            array( 'Simple-Line-Icons-support' => 'Support' ),
            array( 'Simple-Line-Icons-size-fullscreen' => 'Size Fullscreen' ),
            array( 'Simple-Line-Icons-size-actual' => 'Size Actual' ),
            array( 'Simple-Line-Icons-shuffle' => 'Shuffle' ),
            array( 'Simple-Line-Icons-share-alt' => 'Share Alt' ),
            array( 'Simple-Line-Icons-share' => 'Share' ),
            array( 'Simple-Line-Icons-rocket' => 'Rocket' ),
            array( 'Simple-Line-Icons-question' => 'Question' ),
            array( 'Simple-Line-Icons-pie-chart' => 'Pie Chart' ),
            array( 'Simple-Line-Icons-pencil' => 'Pencil' ),
            array( 'Simple-Line-Icons-note' => 'Note' ),
            array( 'Simple-Line-Icons-music-tone-alt' => 'Music Tone Alt' ),
            array( 'Simple-Line-Icons-music-tone' => 'Music Tone' ),
            array( 'Simple-Line-Icons-microphone' => 'Microphone' ),
            array( 'Simple-Line-Icons-loop' => 'Loop' ),
            array( 'Simple-Line-Icons-logout' => 'Logout' ),
            array( 'Simple-Line-Icons-login' => 'Login' ),
            array( 'Simple-Line-Icons-list' => 'List' ),
            array( 'Simple-Line-Icons-like' => 'Like' ),
            array( 'Simple-Line-Icons-home' => 'Home' ),
            array( 'Simple-Line-Icons-grid' => 'Grid' ),
            array( 'Simple-Line-Icons-graph' => 'Graph' ),
            array( 'Simple-Line-Icons-equalizer' => 'Equalizer' ),
            array( 'Simple-Line-Icons-dislike' => 'Dislike' ),
            array( 'Simple-Line-Icons-cursor' => 'Cursor' ),
            array( 'Simple-Line-Icons-control-start' => 'Control Start' ),
            array( 'Simple-Line-Icons-control-rewind' => 'Control Rewind' ),
            array( 'Simple-Line-Icons-control-play' => 'Control Play' ),
            array( 'Simple-Line-Icons-control-pause' => 'Control Pause' ),
            array( 'Simple-Line-Icons-control-forward' => 'Control Forward' ),
            array( 'Simple-Line-Icons-control-end' => 'Control End' ),
            array( 'Simple-Line-Icons-calendar' => 'Calendar' ),
            array( 'Simple-Line-Icons-bulb' => 'Bulb' ),
            array( 'Simple-Line-Icons-bar-chart' => 'Bar Chart' ),
            array( 'Simple-Line-Icons-arrow-up' => 'Arrow Up' ),
            array( 'Simple-Line-Icons-arrow-right' => 'Arrow Right' ),
            array( 'Simple-Line-Icons-arrow-left' => 'Arrow Left' ),
            array( 'Simple-Line-Icons-arrow-down' => 'Arrow Down' ),
            array( 'Simple-Line-Icons-ban' => 'Ban' ),
            array( 'Simple-Line-Icons-bubble' => 'Bubble' ),
            array( 'Simple-Line-Icons-camcorder' => 'Camcorder' ),
            array( 'Simple-Line-Icons-camera' => 'Camera' ),
            array( 'Simple-Line-Icons-check' => 'Check' ),
            array( 'Simple-Line-Icons-clock' => 'Clock' ),
            array( 'Simple-Line-Icons-close' => 'Close' ),
            array( 'Simple-Line-Icons-cloud-download' => 'Cloud Download' ),
            array( 'Simple-Line-Icons-cloud-upload' => 'Cloud Upload' ),
            array( 'Simple-Line-Icons-doc' => 'Doc' ),
            array( 'Simple-Line-Icons-envelope' => 'Envelope' ),
            array( 'Simple-Line-Icons-eye' => 'Eye' ),
            array( 'Simple-Line-Icons-flag' => 'Flag' ),
            array( 'Simple-Line-Icons-folder' => 'Folder' ),
            array( 'Simple-Line-Icons-heart' => 'Heart' ),
            array( 'Simple-Line-Icons-info' => 'Info' ),
            array( 'Simple-Line-Icons-key' => 'Key' ),
            array( 'Simple-Line-Icons-link' => 'Link' ),
            array( 'Simple-Line-Icons-lock' => 'Lock' ),
            array( 'Simple-Line-Icons-lock-open' => 'Lock Open' ),
            array( 'Simple-Line-Icons-magnifier' => 'Magnifier' ),
            array( 'Simple-Line-Icons-magnifier-add' => 'Magnifier Add' ),
            array( 'Simple-Line-Icons-magnifier-remove' => 'Magnifier Remove' ),
            array( 'Simple-Line-Icons-paper-clip' => 'Paper Clip' ),
            array( 'Simple-Line-Icons-paper-plane' => 'Paper Plane' ),
            array( 'Simple-Line-Icons-plus' => 'Plus' ),
            array( 'Simple-Line-Icons-pointer' => 'Pointer' ),
            array( 'Simple-Line-Icons-power' => 'Power' ),
            array( 'Simple-Line-Icons-refresh' => 'Refresh' ),
            array( 'Simple-Line-Icons-reload' => 'Reload' ),
            array( 'Simple-Line-Icons-settings' => 'Settings' ),
            array( 'Simple-Line-Icons-star' => 'Star' ),
            array( 'Simple-Line-Icons-symbol-fermale' => 'Symbol Fermale' ),
            array( 'Simple-Line-Icons-symbol-male' => 'Symbol Male' ),
            array( 'Simple-Line-Icons-target' => 'Target' ),
            array( 'Simple-Line-Icons-volume-1' => 'Volume 1' ),
            array( 'Simple-Line-Icons-volume-2' => 'Volume 2' ),
            array( 'Simple-Line-Icons-volume-off' => 'Volume Off' )
        );

        return array_merge( $icons, $simpleline_icons );
    }
}
<?php
/**
 * Single Product Thumbnails
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-thumbnails.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
 *
 * @see 	    http://docs.woothemes.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post, $woocommerce, $product;

$attachment_ids = $product->get_gallery_attachment_ids();

?>
<div class="product-thumbnails thumbnails">
    <?php
    $html = '<div class="product-thumbs-slider owl-carousel">';

    if ( has_post_thumbnail() ) {

        $attachment_id = get_post_thumbnail_id();
        $image_title = esc_attr( get_the_title( $attachment_id ) ); if (!$image_title) $image_title = '';
        $image_thumb_link = wp_get_attachment_image_src($attachment_id, 'shop_thumbnail');

        $html .= '<div class="img-thumbnail"><div class="inner">';
        $html .= apply_filters( 'woocommerce_single_product_image_thumbnail_html', '<img class="woocommerce-main-thumb img-responsive" alt="' . $image_title . '" src="' . $image_thumb_link[0] . '" />', $attachment_id, $post->ID, '');
        $html .= '</div></div>';

    } else {

        $image_thumb_link = wc_placeholder_img_src();
        $html .= '<div class="img-thumbnail"><div class="inner">';
        $html .= apply_filters( 'woocommerce_single_product_image_thumbnail_html', '<img class="woocommerce-main-thumb img-responsive" alt="" src="' . $image_thumb_link . '" />', false, $post->ID, '');
        $html .= '</div></div>';
    }

    if ( $attachment_ids ) {
        foreach ( $attachment_ids as $attachment_id ) {

            $image_link = wp_get_attachment_url( $attachment_id );

            if ( ! $image_link )
                continue;

            $image       = wp_get_attachment_image( $attachment_id, apply_filters( 'single_product_small_thumbnail_size', 'shop_thumbnail' ) );
            $image_thumb_link = wp_get_attachment_image_src($attachment_id, 'shop_thumbnail');
            $image_title = esc_attr( get_the_title( $attachment_id ) ); if (!$image_title) $image_title = '';

            $html .= '<div class="img-thumbnail"><div class="inner">';
            $html .= apply_filters( 'woocommerce_single_product_image_thumbnail_html', '<img class="img-responsive" alt="' . $image_title . '" src="' . $image_thumb_link[0] . '" />', $attachment_id, $post->ID, '');
            $html .= '</div></div>';

        }
    }

    $html .= '</div>';

    echo $html;

    ?>
</div>

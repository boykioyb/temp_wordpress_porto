<?php

global $porto_settings, $post, $porto_portfolio_columns, $porto_portfolio_view;

$portfolio_columns = $porto_settings['portfolio-grid-columns'];

if ($porto_portfolio_columns)
    $portfolio_columns = $porto_portfolio_columns;

$portfolio_layout = 'grid';
$portfolio_view = $porto_settings['portfolio-grid-view'];

if ($porto_portfolio_view)
    $portfolio_view = $porto_portfolio_view;

$item_classes = ' portfolio-col-'.$portfolio_columns.' ';
$item_cats = get_the_terms($post->ID, 'portfolio_cat');
if ($item_cats) {
    foreach ($item_cats as $item_cat) {
        $item_classes .= urldecode($item_cat->slug) . ' ';
    }
}

$portfolio_link = get_post_meta($post->ID, 'portfolio_link', true);
$show_external_link = $porto_settings['portfolio-external-link'];

if (has_post_thumbnail()) :
    $attachment_id = get_post_thumbnail_id();
    $attachment = porto_get_attachment($attachment_id);
    if ($portfolio_view == 'full') {
        $attachment_grid = porto_get_attachment($attachment_id);
    } else {
        $attachment_grid = porto_get_attachment($attachment_id, 'portfolio-large');
    }
    if ($attachment && $attachment_grid) :
        ?>
        <article <?php post_class('portfolio portfolio-' . $portfolio_layout . $item_classes); ?>>
            <?php porto_render_rich_snippets(); ?>
            <div class="portfolio-item <?php echo $portfolio_view == 'outimage' ? 'align-center' : $portfolio_view ?>">
                <div class="thumbnail">
                    <div class="thumb-info">
                        <a href="<?php if ($show_external_link && $portfolio_link) echo $portfolio_link; else the_permalink() ?>">
                            <img class="img-responsive<?php echo $portfolio_view == 'outimage' ? ' tf-none' : '' ?>" width="<?php echo $attachment_grid['width'] ?>" height="<?php echo $attachment_grid['height'] ?>" src="<?php echo $attachment_grid['src'] ?>" alt="<?php echo $attachment_grid['alt'] ?>"<?php if ($porto_settings['portfolio-zoom']) : ?> data-image="<?php echo $attachment['src'] ?>" data-caption="<?php echo $attachment['caption'] ?>"<?php endif; ?> />
                        </a>
                        <?php if ($portfolio_view != 'outimage') : ?>
                        <div class="thumb-info-title">
                            <a href="<?php if ($show_external_link && $portfolio_link) echo $portfolio_link; else the_permalink() ?>" class="thumb-info-inner"><?php the_title(); ?></a>
                            <?php
                            $cat_list = get_the_term_list($post->ID, 'portfolio_cat', '', ', ', '');
                            if (in_array('cats', $porto_settings['portfolio-metas']) && $cat_list) : ?>
                                <span class="thumb-info-type"><?php echo $cat_list ?></span>
                            <?php endif ?>
                        </div>
                        <a href="<?php if ($show_external_link && $portfolio_link) echo $portfolio_link; else the_permalink() ?>" class="thumb-info-action">
                            <span class="thumb-info-action-icon"><i class="fa fa-link"></i></span>
                        </a>
                        <?php endif; ?>
                        <?php if ($porto_settings['portfolio-zoom']) : ?>
                            <span class="zoom"><i class="fa fa-search"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($portfolio_view == 'outimage') : ?>
                    <a class="text-decoration-none" href="<?php if ($show_external_link && $portfolio_link) echo $portfolio_link; else the_permalink() ?>"><h4 class="m-t-md m-b-none"><?php the_title(); ?></h4></a>
                    <?php
                    $cat_list = get_the_term_list($post->ID, 'portfolio_cat', '', ', ', '');
                    if (in_array('cats', $porto_settings['portfolio-metas']) && $cat_list) : ?>
                        <p class="m-b-sm color-body"><?php echo $cat_list ?></p>
                    <?php endif;
                endif; ?>
            </div>
        </article>
    <?php
    endif;
endif;
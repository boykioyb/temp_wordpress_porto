<?php get_header() ?>

<?php
global $porto_settings;

wp_reset_postdata();
?>

    <div id="content" role="main">

        <?php if (have_posts()) : the_post(); ?>

            <?php get_template_part('content', 'member'); ?>

            <?php get_template_part('content', 'member-portfolios'); ?>

            <?php if (class_exists('WooCommerce')) get_template_part('content', 'member-products'); ?>

            <?php get_template_part('content', 'member-posts'); ?>

            <?php
            if ($porto_settings['member-related']) :
                $related_members = porto_get_related_members($post->ID);
                if ($related_members->have_posts()) : ?>
                    <div class="related-members">
                        <h4 class="sub-title"><?php echo __('Related <strong>Members</strong>', 'porto'); ?></h4>
                        <div class="row">
                            <div class="member-carousel owl-carousel show-nav-title" data-cols-lg="<?php echo ($porto_layout == 'wide-left-sidebar' || $porto_layout == 'wide-right-sidebar' || $porto_layout == 'left-sidebar' || $porto_layout == 'right-sidebar') ? '3' : '4' ?>" data-cols-md="3" data-cols-sm="2">
                                <?php
                                while ($related_members->have_posts()) {
                                    $related_members->the_post();

                                    get_template_part('content', 'member-item');
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif;
            endif;
            ?>

        <?php endif; ?>

    </div>

<?php get_footer() ?>
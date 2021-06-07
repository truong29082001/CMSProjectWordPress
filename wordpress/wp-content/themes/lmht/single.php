<?php get_header(); ?>
<section id="main-content" class="container">   
    <div class="row">
        <div class="col-md-9">
        <?php 
            if (have_posts()) : while (have_posts()) : the_post();?>
            <?php 
            the_content();

            the_content();

                    wp_link_pages(
                        array(
                            'before' => '<div class="page-links">' . esc_html__('Pages:', 'lienminhhuyenthoai'),
                            'after' => '</div>',
                        )
                    );?>
             <?php endwhile;?>
             <?php else:?>
             <?php get_template_part('conten', 'none');?>
             <?php endif;?>


        </div>
        <div class="col-md-3">
            <section id="sidebar">
                    <?php dynamic_sidebar('main-sidebar');?>
            </section>
        </div>
    </div>
</section>
<?php get_footer(); ?>
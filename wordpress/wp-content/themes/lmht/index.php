<?php get_header(); ?>

<div id="main-content">
    <div class="row">
        <div class="col-md-9">
        <div class="post">
        <?php 
            if (have_posts()) : while (have_posts()) : the_post();
        ?>
        <div <?php post_class()?> id="post-<?php the_ID();?>">
            <div class="entry-content">
                <?php 
                        echo '<hr>';
                        ?>
                <a href="<?php the_permalink() ?>"><h2> <?php the_title();?></h2></a>
                
                <?php
                   
                    wp_link_pages(
                        array(
                            'before' => '<div class="page-links">' . esc_html__('Pages:', 'lienminhhuyenthoai'),
                            'after' => '</div>',
                        )
                    );
                ?>
            </div>
        </div>
        <?php endwhile; endif; ?>
    </div></div>
        <div class="col-md-3">
        <section id="sidebar">
                    <?php dynamic_sidebar('main-sidebar');?>
            </section>
        </div>
    </div>
</div>
<?php get_footer(); ?>
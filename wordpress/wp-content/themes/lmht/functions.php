<?php
/**
@chen bootstrap
 */

 wp_enqueue_style ('theme-bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');

 /**
 @chen css vao theme
  */
  
function nhomf_styles(){
    /*
    *hàm get stylesheet uri()
    */
    wp_register_style('main-style', get_template_directory_uri() . '/style.css', 'all');
    wp_enqueue_style('main-style');
}

add_action('wp_enqueue_scripts', 'nhomf_styles');

/**
 @thiet lap cac chuc nang duoc theme ho tro
 */

 if(!function_exists('nhomf_theme_setup')){
     
    function nhomf_theme_setup()
    {
        add_theme_support('post-formats', array(
                'video',
                'image',
                'audio',
                'gallery',
        )
        );
        register_nav_menu('primary-menu', __('Primary Menu', 'nhomf'));
        
        $sidebar = array(
            'name' => __('Main Sidebar', 'nhomf'),
            'id' => 'main-sidebar',
            'description' => 'Main sideber for nhomf theme',
            'class' => 'main-sidebar',
            'before_tilte' => '<h3 class="widgettitle">',
            'after_sidebar' => '</h3>',
        );

        register_sidebar($sidebar);
    }
    add_action('init', 'nhomf_theme_setup');
 }



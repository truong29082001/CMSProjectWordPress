<?php
/*This file is part of shopping mart child theme.

All functions of this file will be loaded before of parent theme functions.
Learn more at https://codex.wordpress.org/Child_Themes.

Note: this function loads the parent stylesheet before, then child theme stylesheet, leave it in place unless you know what you are doing.
*/


define('storefront_business_THEME_REVIEW_URL', 'https://wordpress.org/themes/storefront-business/');
define('storefront_business_THEME_DOC', 'https://www.ceylonthemes.com/wp-tutorials/');
define('storefront_business_THEME_URI', 'https://www.ceylonthemes.com/product/wordpress-storefront-theme/');

/* allowed html tags */

$storefront_business_allowed_html = array(
		'a'          => array(
			'href'  => true,
			'title' => true,
			'class'  => true,			
		),
		'option'          => array(
			'selected'  => true,
			'value' => true,
			'class'  => true,			
		),		
		'p'          => array(
			'class'  => true,
		),		
		'abbr'       => array(
			'title' => true,
		),
		'acronym'    => array(
			'title' => true,
		),
		'b'          => array(),
		'blockquote' => array(
			'cite' => true,
		),
		'cite'       => array(),
		'code'       => array(),
		'del'        => array(
			'datetime' => true,
		),
		'em'         => array(),
		'i'          => array(),
		'q'          => array(
			'cite' => true,
		),
		's'          => array(),
		'strike'     => array(),
		'strong'     => array(),
	);
	


//add new settings
require  get_stylesheet_directory().'/inc/settings.php';

add_action( 'wp_enqueue_scripts', 'storefront_business_styles' );

define('storefront_business_theme_uri', 'https://www.ceylonthemes.com/product/wordpress-storefront-theme/');

function storefront_business_styles() {
	//enqueue parent styles
	wp_enqueue_style( 'new-york-business-style', get_template_directory_uri().'/style.css' );
	wp_enqueue_style( 'storefront-business-styles', get_stylesheet_directory_uri(). '/style.css', array('new-york-business-style'));
}

add_action( 'after_setup_theme', 'storefront_business_default_header' );
/**
 * Add Default Custom Header Image To Twenty Fourteen Theme
 * 
 * @return void
 */
function storefront_business_default_header() {

    add_theme_support(
        'custom-header',
        apply_filters(
            'storefront_business_custom_header_args',
            array(
                'default-text-color' => '#ffffff',
                'default-image' => get_stylesheet_directory_uri() . '/images/header.jpg',
				'width'              => 1280,
				'height'             => 300,
				'flex-width'         => true,
				'flex-height'        => true,				
            )
        )
    );
}

/* unique id for product slider */
$storefront_business_uniqueue_id = 99;
require  get_stylesheet_directory().'/inc/custom-fonts.php';
require  get_stylesheet_directory().'/inc/product-functions.php';

/**
 * override parent admin notice 
 **/
function new_york_business_general_admin_notice(){ }
 

function storefront_business_general_admin_notice(){

         $msg = sprintf('<div data-dismissible="disable-done-notice-forever" class="notice notice-info is-dismissible" ><p>
		 		<a href=%1$s target="_blank"  style="text-decoration: none;" class="button button-primary"> %2$s </a>
				<strong>%3$s</strong>
			 	<a href="?storefront_business_dismiss_notice" target="_self"  style="text-decoration: none; float: right;" >%4$s</a>
			 	</p></div>',
				esc_url(home_url().'/wp-admin/theme-install.php?theme=ecommerce-star'),
				esc_html__('See New eCommerce Theme', 'storefront-business'),
				esc_html__('ECommerce Storefront is no longer maintained. Modern Updated Theme is Here with more features and Security Updates!', 'storefront-business'),			
				esc_html__('Dismiss', 'storefront-business')
				);
		 echo wp_kses_post($msg);
}

if ( isset( $_GET['storefront_business_dismiss_notice'] ) ){
	update_option('storefront_business_dismiss_notice', 'hide');
}

$storefront_business_dismiss_notice = (get_option('storefront_business_dismiss_notice', 1));
if($storefront_business_dismiss_notice != 'hide'){
	add_action('admin_notices', 'storefront_business_general_admin_notice');
}

/**
 * override parent theme customize control
 */
if ( class_exists( 'WP_Customize_Control' )) {

	class new_york_business_pro_Control extends WP_Customize_Control {
	
		public function render_content() {
			?>
			<p style="padding:5px;background-color:#8080FF;color:#FFFFFF;text-align: center;"><a href="<?php echo esc_url(storefront_business_THEME_URI); ?>" target="_blank" style="color:#FFFFFF"><?php echo esc_html__('See Premium Features', 'storefront-business'); ?></a></p>
			<?php
		}
	}
	
}


/**
 * Override custom fonts functions of parent theme.
 */
 
 delete_option('body_fontfamily');

function new_york_business_fonts_url() { 
	$fonts_url = '';
	/*
	 * Translators: If there are characters in your language that are not
	 * supported by "Open Sans", sans-serif;, translate this to 'off'. Do not translate
	 * into your own language.
	 */
	$typography = _x( 'on', 'Open Sans font: on or off', 'storefront-business' );

	if ( 'off' !== $typography ) {
		$font_families = array();
		
		$font_families[] = get_theme_mod('header_fontfamily','Roboto').':300,400,500';
		$font_families[] = get_theme_mod('body_fontfamily','Lora').':300,400,500';
		
 
		$query_args = array(
		'family' => urlencode( implode( '|', $font_families ) ),
		'subset' => urlencode( 'latin,latin-ext' ),
		);
        
		$fonts_url = add_query_arg( $query_args, 'https://fonts.googleapis.com/css' );
		
	}
   
	return esc_url( $fonts_url );

}
add_action('after_setup_theme', 'new_york_business_fonts_url');

//call custom fonts
add_action('after_setup_theme', 'new_york_business_custom_fonts_css');


//header_background

add_action( 'customize_register', 'storefront_business_customizer_settings' ); 

function storefront_business_customizer_settings( $wp_customize ) {

	$wp_customize->add_setting( 'header_background',
		array(
			'default' => '#ffffff',
			'sanitize_callback' => 'sanitize_hex_color'
		)
	);  
	
		
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'header_background', array(
		'label'        => __('Header Background Color', 'storefront-business'),
		'section'    => 'colors',
		'settings'   => 'header_background',
	) ) );
	
	//banner section	
	$wp_customize->add_section( 'top_banner' , array(
		'title'      => __( 'Top Banner', 'storefront-business' ),
		'priority'   => 1,
		'panel' => 'theme_options',
	) );	
	
	//top banner
	$wp_customize->add_setting('top_banner_page' , array(
		'default'    => 0,
		'sanitize_callback' => 'absint',
	));

	$wp_customize->add_control('top_banner_page' , array(
		'label' => __('Select Top banner (Page)', 'storefront-business' ),
		'section' => 'top_banner',
		'type'=> 'dropdown-pages',
	) );	
		
	//widgets section	
	$wp_customize->add_section( 'home_widgets' , array(
		'title'      => __( 'Home Header Widgets', 'storefront-business' ),
		'priority'   => 1,
		'panel' => 'theme_options',
	) );	
	
	//top banner
	$wp_customize->add_setting('top_widgets' , array(
		'default'    => 'col-sm-3',
		'sanitize_callback' => 'new_york_business_sanitize_select',
	));

	$wp_customize->add_control('top_widgets' , array(
		'label' => __('Select Number of Widgets', 'storefront-business' ),
		'section' => 'home_widgets',
		'type'=>'select',
		'choices'=>array(
			'col-sm-4'=> __('3 Widgets', 'storefront-business' ),
			'col-sm-3'=> __('4 Widgets', 'storefront-business' ),
			'col-sm-2'=> __('6 Widgets', 'storefront-business' ),
		),
	) );
		
}	

//override parent theme functions
function new_york_business_footer_foreground_css(){

	$color =  esc_attr(get_theme_mod( 'footer_foreground_color', '#191919')) ;
		
	/**
	 *
	 * @since storefront-business 1.0
	 *
	 */

	$css                = '
	
	.footer-foreground {}
	.footer-foreground .widget-title, 
	.footer-foreground a, 
	.footer-foreground p, 
	.footer-foreground td,
	.footer-foreground th,
	.footer-foreground caption,
	.footer-foreground li,
	.footer-foreground h1,
	.footer-foreground h2,
	.footer-foreground h3,
	.footer-foreground h4,
	.footer-foreground h5,
	.footer-foreground h6,
	.footer-foreground .site-info a
	{
	  color:'.$color.';
	}
	
	.footer-foreground #today {
		font-weight: 600;	
		background-color: #3ba0f4;	
		padding: 5px;
	}
	
	.footer-foreground a:hover, 
	.footer-foreground a:active {
		color:#ccc ;
	}
	
	';

return $css;

}


//add child theme widget area

function storefront_business_widgets_init(){

	/* header sidebar */

	register_sidebar(
		array(
			'name'          => __( 'Home Page Header Widgets', 'storefront-business' ),
			'id'            => 'header-banner',
			'description'   => __( 'Add widgets to appear in Header.', 'storefront-business' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s '.esc_attr(get_theme_mod('top_widgets','col-sm-3')).'">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);

}
add_action( 'widgets_init', 'storefront_business_widgets_init' );




/*Display YITH Wishlist Buttons at shop page*/
if (!function_exists('storefront_business_display_yith_wishlist_loop')) {
    /**
     * Display YITH Wishlist Buttons at product archive page
     *
     */
    function storefront_business_display_yith_wishlist_loop()
    {
        if(!function_exists('YITH_WCWL')){
            return;
        }

        echo '<div class="yith-btn">';
        echo do_shortcode("[yith_wcwl_add_to_wishlist]");
        echo '</div>';
    }
}


add_action('storefront_business_woocommerce_add_to_wishlist_button', 'storefront_business_display_yith_wishlist_loop', 16);



/*Display YITH Quickview Buttons at shop page*/
if (!function_exists('storefront_business_display_yith_quickview_loop')) {
    /**
     * Display YITH Wishlist Buttons at product archive page
     *
     */
    function storefront_business_display_yith_quickview_loop()
    {

        if(!function_exists('yith_wcqv_install')){
            return;
        }

        echo '<div class="yith-btn">';
        global $product, $post;
        $product_id = $post->ID;

        if (!$product_id) {
            $product instanceof WC_Product && $product_id = yit_get_prop($product, 'id', true);
        }

        $button = '';
        if ($product_id) {
            // get label
            $button = '<a href="#" class="button yith-wcqv-button" data-product_id="' . esc_attr($product_id) . '"><div data-toggle="tooltip" data-placement="top" title="'. esc_html__('Quick View', 'storefront-business').'"><i class="fa fa-eye" aria-hidden="true"></i></div></a>';
        }
        echo wp_kses_post($button);
        echo '</div>';


    }
}


add_action('storefront_business_woocommerce_yith_quick_view_button', 'storefront_business_display_yith_quickview_loop', 16);


/*Display YITH Compare Buttons at shop page*/
if (!function_exists('storefront_business_display_yith_compare_loop')) {
    /**
     * Display YITH Wishlist Buttons at product archive page
     *
     */
    function storefront_business_display_yith_compare_loop()
    {

        if(!class_exists('YITH_Woocompare')){
            return;
        }

        echo '<div class="yith-btn">';
        global $product, $post;
        $product_id = $post->ID;

        if (!$product_id) {
            $product instanceof WC_Product && $product_id = yit_get_prop($product, 'id', true);
        }

        $button = '';
        if ($product_id) {

            $button = do_shortcode('[yith_compare_button type="link" button_text="<i class="fa fa-adjust" aria-hidden="true"></i>"]');

        }
        echo wp_kses_post($button);
        echo '</div>';


    }
}

//$enable_wishlists_on_listings = storefront_business_get_option('enable_wishlists_on_listings', true);
//if( $enable_wishlists_on_listings ){
add_action('storefront_business_woocommerce_yith_compare_button', 'storefront_business_display_yith_compare_loop', 16);


/* customize settings*/
add_action( 'customize_register', 'storefront_business_customize_register'); //second argument is arbitrary, but cannot have hyphens because php does not allow them in function names.

function storefront_business_customize_register( $wp_customize ) {

	require  get_stylesheet_directory().'/inc/slider-options.php';

}

define('storefront_business_TEMPLATE_DIR_URI', get_stylesheet_directory_uri());



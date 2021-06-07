<?php
/**
 * Displays home slider
 * @package business-store
 * @since 1.0
 */
storefront_business_slider_nav();
function storefront_business_slider_nav(){


$storefront_business_settings = new new_york_business_settings();
$storefront_business_option = wp_parse_args(  get_option( 'new_york_business_option', array() ) , $storefront_business_settings->default_data());  


$slider_banner = get_theme_mod('slider_banner_image', '');

$slider_css = 'col-sm-9';
if ($slider_banner) {
	$slider_css = 'col-sm-7';
}

?>
<section id="slider-nav-section" style="z-index:0" >
	<div class="svc-section-body section-padding">
	<div class="container">
		<div class="row">
		
			<div class="col-md-3 product-navigation-container">
			<?php storefront_business_product_navigation(esc_html__('Product Categories','storefront-business'), $storefront_business_option['slider_nav_count']); ?>
			</div>
			
			<div class="<?php echo esc_attr($slider_css); ?>">
	<?php
	//set query args to show specified amount or show all posts from particular category. 
	$count = 0;
	$args = array ( 'post_type' => 'product','posts_per_page'=> 5, 'tax_query' => array(
					array(
						'taxonomy' => 'product_cat',
						'terms' => $storefront_business_option['slider_nav_cat'],
						'operator' => 'IN',
						)
					));
	
	if($storefront_business_option['slider_nav_cat'] == 0) {
		$args = array ( 'post_type' => 'product','posts_per_page'=> 5);
	}
		
	$loop = new WP_Query($args);
	$count = $loop->post_count;
	
	
	?>

	<div id="prod_carousal" class="carousel slide <?php if( $storefront_business_option['slider_animation_type']=='fade' ){ echo 'carousel-' . esc_attr( $storefront_business_option['slider_animation_type'] ); } ?>" data-ride="carousel"  data-interval="<?php echo absint( $storefront_business_option['slider_speed']); ?>">
		<div class="no-z-index">
		<?php if($count>1): ?>
		  <ol class="carousel-indicators">
			<?php 
					$j = 0;			
					for ($j = 0; $j < $count; $j++):							
			?>
			<li data-target="#prod_carousal" data-slide-to="<?php echo absint($j); ?>" class="<?php if($j==0){ echo 'active'; }  ?>"></li>
			<?php								
					endfor;
			?>
		  </ol>
		 <?php endif; ?>
		</div>
	
	  <div class="carousel-inner" role="listbox">
		<?php 
		 $i = 0;
		 while( $loop->have_posts() ) : $loop->the_post();
		 global $product;		  
				
		?>
		<div class="item <?php if($i==0){ echo 'active'; } $i++; ?> "> 
		<?php 
		$url = $my_title = '';
		$alt = '';
		$price = '';
		$post_id = get_the_ID();
		if( has_post_thumbnail() ){
			$thumb_id = get_post_thumbnail_id($post_id);	
			$url = esc_url(get_the_post_thumbnail_url($post_id, 'full'));
			if(!$url) {
				$url = storefront_business_TEMPLATE_DIR_URI.'/images/no-image.png';
			}

			$alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
		} else {
			$url = storefront_business_TEMPLATE_DIR_URI.'/images/no-image.png';
		}	

		$price = $product->get_price_html();
		$my_title = esc_html(get_the_title());
		$post_link = get_permalink( $post_id );		
		?>
		<a href="<?php echo esc_url($post_link) ?>" >
		<img src="<?php echo esc_url($url); ?>" style="max-height:<?php echo absint($storefront_business_option['slider_nav_image_height']); ?>px; width:100%" alt="<?php echo esc_attr($alt); ?>" ></img>	     
		</a>
		<?php
		echo '<div class="pro-slider-caption on-left">';
			echo '<div class="caption-heading">';
				echo '<h3 class="cap-title"><a href="'.'#'.'">'.esc_html($my_title).'</a></h3>';
			echo '</div>';		
			echo '<div class="price">'.wp_kses_post($price).'</div>';
		echo '</div>';		 
		?>
		</div>
		<?php
			endwhile;
			wp_reset_postdata(); 
		?>
	</div>

	<?php if($count>1): ?>
			<ul class="carousel-navigation">
				<li><a class="carousel-prev" href="#prod_carousal" data-slide="prev"></a></li>
				<li><a class="carousel-next" href="#prod_carousal" data-slide="next"></a></li>
			</ul>
	<?php endif; ?> 

  </div>
  </div>
  
  
  <?php if(get_theme_mod('slider_banner_image', '') != ''): ?>
  <div class="col-md-2 slider-banner">
  	 <img src="<?php echo esc_url(get_theme_mod('slider_banner_image', '')); ?>" />
  </div> 
  <?php endif; ?>
  
  </div>  
 </div>
</div>
</section>

<?php
}
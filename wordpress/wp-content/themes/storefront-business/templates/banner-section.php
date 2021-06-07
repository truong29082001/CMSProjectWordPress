<section id="top-banner">
	<div>
		<?php 
		$storefront_business_banner = get_theme_mod('top_banner_page', 0);
		if($storefront_business_banner != 0 ) {
	
			$storefront_business_args = array( 'post_type' => 'page','ignore_sticky_posts' => 1 , 'post__in' => array($storefront_business_banner));
			$storefront_business_result = new WP_Query($storefront_business_args);
			while ( $storefront_business_result->have_posts() ) :
				$storefront_business_result->the_post();
				the_content();
			endwhile;
			wp_reset_postdata();
		}
		 ?>
	</div>
</section> 


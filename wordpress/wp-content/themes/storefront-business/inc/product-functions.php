<?php

/*
 * display woocomemrce categories as menu
 */
if (!class_exists('WooCommerce')) return;

class storefront_business_product_navigation_widget extends WC_Widget {

		function __construct() {
				
				$this->widget_cssclass    = 'woocommerce product_navigation_widget';
				$this->widget_description = __( 'Display product navigation through categories.', 'storefront-business' );
				$this->widget_id          = 'storefront_business_product_nav_widget';
				$this->widget_name        = __( 'THEME: Product Navigation', 'storefront-business' );
		
				parent::__construct();				
		}

		public function widget($args, $instance) {
		
						
				$title = (!empty($instance['title'])) ? esc_html($instance['title']) : __('Product Navigation', 'storefront-business');
				$hide_title = (!empty($instance['hide_title'])) ? esc_html($instance['hide_title']) : '';
				$max_items = (!empty($instance['max_items'])) ? absint($instance['max_items']) : 20;

				echo $args['before_widget'];
				
				if ($hide_title) {
					$title = '';
				}
				//generate widget front end
				storefront_business_product_navigation($title, $max_items);

				echo $args['after_widget'];
										


		}

		public function form($instance) {
				$title = (!empty($instance['title'])) ? esc_html($instance['title']) : __('Product Navigation', 'storefront-business');
				$hide_title = (!empty($instance['hide_title'])) ? esc_html($instance['hide_title']) : '';
				$max_items = (!empty($instance['max_items'])) ? absint($instance['max_items']) : 20;
		?>
		<p>
		<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'storefront-business'); ?></label> 
		<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<p>
		<label for="<?php echo esc_attr($this->get_field_id('max_items')); ?>"><?php esc_html_e('Max Items to Show:', 'storefront-business'); ?></label> 
		<input class="widefat" id="<?php echo esc_attr($this->get_field_id('max_items')); ?>" name="<?php echo esc_attr($this->get_field_name('max_items')); ?>" type="number" value="<?php echo esc_attr($max_items); ?>" />
		</p>
		<p>
		<input class="checkbox" type="checkbox" <?php if ($hide_title) { echo " checked "; } ?> id="<?php echo esc_attr($this->get_field_id('hide_title')); ?>" name="<?php echo esc_attr($this->get_field_name('hide_title')); ?>" />
		<label for="<?php echo esc_attr($this->get_field_id('hide_title')); ?>"><?php esc_html_e('Hide Widget Title', 'storefront-business'); ?></label> 
		</p>
		<?php
		}

		// Updating widget replacing old instances with new
		public function update($new_instance, $old_instance) {
				$instance = array();
				$instance['title'] = (!empty($new_instance['title'])) ? esc_html($new_instance['title']) : '';
				$instance['max_items'] = (!empty($new_instance['max_items'])) ? absint($new_instance['max_items']) : '';
				$instance['hide_title'] = (!empty($new_instance['hide_title'])) ? esc_html($new_instance['hide_title']) : '';
				return $instance;
		}
} // widget ends

function storefront_business_product_navigation_widget() {
		register_widget('storefront_business_product_navigation_widget');
}
add_action('widgets_init', 'storefront_business_product_navigation_widget');



/*
 * Product navigation by categories up to 3 sub categories
 */

function storefront_business_product_navigation($widget_title, $max_items = 50){
  
  if(!class_exists( 'WooCommerce' ))	return;

  $args = array(
         'taxonomy'     => 'product_cat',
         'orderby'      => 'date',
		 'order'      	=> 'ASC',
         'show_count'   => 1,
         'pad_counts'   => 0,
         'hierarchical' => 1,
         'title_li'     => '',
         'hide_empty'   => 1,
  );
 $all_categories = get_categories( $args );
 $cat_count = 1;
 echo '<div class="product-navigation">'; 	
	 echo '<ul>';
	 if($widget_title){
	 	echo '<li class="navigation-name"><a href="#">'.esc_html($widget_title).'</a></li>';
	 }
	 foreach ($all_categories as $cat) {
		 if($cat_count > $max_items){
			break;
		 }
		 $cat_count++;
		
			if($cat->category_parent == 0) {
				$category_id = $cat->term_id; 
				$args2 = array(
						'taxonomy'     => 'product_cat',
						'child_of'     => 0,
						'parent'       => $category_id,
						'orderby'      => 'name',
						'show_count'   => 1,
						'pad_counts'   => 0,
						'hierarchical' => 1,
						'title_li'     => '',
						'hide_empty'   => 0,
				);
				$sub_cats = get_categories( $args2 );
				
				if($sub_cats) {
				echo '<li class="has-sub"> <a href="'.esc_url(get_term_link($cat->slug, 'product_cat')).'">'.esc_html($cat->name).' ('.absint($cat->count).')</a>';
				echo '<ul>';
					foreach($sub_cats as $sub_category) {
						$sub_category_id = $sub_category->term_id;
						$args3 = array(
								'taxonomy'     => 'product_cat',
								'child_of'     => 0,
								'parent'       => $sub_category_id,
								'orderby'      => 'name',
								'show_count'   => 1,
								'pad_counts'   => 0,
								'hierarchical' => 1,
								'title_li'     => '',
								'hide_empty'   => 0,
						);
						$sub_sub_cats = get_categories( $args3 );
						if($sub_sub_cats) {
						echo '<li class="has-sub"> <a href="'.esc_url(get_term_link($sub_category->slug, 'product_cat')).'">'.esc_html($sub_category->name).' ('.absint($sub_category->count).')</a>';
							echo '<ul>';
								foreach($sub_sub_cats as $sub_sub_cat) {
									echo '<li> <a href="'.esc_url(get_term_link($sub_sub_cat->slug, 'product_cat')).'">'.esc_html($sub_sub_cat->name).' ('.absint($cat->count).')</a>';
								}
							echo '</ul>';						
						} else {
						echo '<li> <a href="'.esc_url(get_term_link($sub_category->slug, 'product_cat')) .'">'.esc_html($sub_category->name).' ('.absint($cat->count).')</a>';
						}
					}
				echo '</ul>'; 
				} else {
					echo '<li> <a href="'.esc_url(get_term_link($cat->slug, 'product_cat')).'">'.esc_html($cat->name).' ('.absint($cat->count).')</a>';
				}
			}		      
	 } /* end for each */
	 echo '</ul>';
 echo '</div>';

} /* end category function */


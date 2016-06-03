<?php
/**
 * @package isotope-grid-shortcode
 * @version 1.0
 */
/*
Plugin Name: isotope Grid Shortcode
Plugin URI: http://wordpress.org/plugins/image-carousel-shortcode/
Description: <strong>Image Carousel Shortcode</strong> is a lightweight Image Carousel plugin for wordpress. It lets you create a beautiful responsive image carousel.
Author: Sazzad Hu
Version: 1.0
Author URI: http://sazzadh.com/
*/

$path_dir = trailingslashit(str_replace('\\','/',dirname(__FILE__)));
$path_abs = trailingslashit(str_replace('\\','/',ABSPATH));

define('ISOTOPEGS_URL', site_url(str_replace( $path_abs, '', $path_dir )));
define('ISOTOPEGS_DRI', $path_dir);

/*
	Register Scripts
=========================================================*/
add_action('wp_enqueue_scripts', 'isotopegs_script_loader');
function isotopegs_script_loader(){
	wp_enqueue_style('isotope-grid-shortcode', ISOTOPEGS_URL.'css/isotope-grid-shortcode.css');
	wp_enqueue_script('isotope', ISOTOPEGS_URL.'js/isotope.pkgd.min.js' , array('jquery'), '', true);
	wp_enqueue_script('imagesloaded', ISOTOPEGS_URL.'js/imagesloaded.pkgd.min.js' , array('jquery'), '', true);
}



/*
	Shortcode for Post
=========================================================*/
add_shortcode('isotope_post_grid', 'isotopegs_post_shortcode');
function isotopegs_post_shortcode( $atts, $content = null ) {
    $settings = shortcode_atts( array(
		'post' => 'post', //post, meta, option, function, taxonomy	
		'column' => '4',
		'column_m' => '2',
		'column_s' => '1',
		'tax' => '',//if mood post, use taxonomy name or function name for array or no
		'tx_child' => 'no', //only work with taxonomy filter
		'class' => '',
		'gap' => '1x', //1x, 2x, 3x, 4x, 5x, 6x
		'content_function' => '',
		'text_all' => 'All',
    ), $atts );
	
	$output = '';
	
	ob_start();
	
	$uid = 'isotopegs_'.rand();	
	$main_div_class = $uid.' isotopegs ';
	$main_div_class .= 'column_'.$settings['column'].' ';
	$main_div_class .= 'column_m_'.$settings['column_m'].' ';
	$main_div_class .= 'column_s_'.$settings['column_s'].' ';
	$main_div_class .= 'gap_'.$settings['gap'].' ';
	$main_div_class .= $settings['class'].' ';
	
	if($settings['tax'] != ''){
		
		if($settings['tx_child'] == 'yes'){ $parent = 0; }else{ $parent = ''; }
		
		$terms = get_terms( $settings['tax'], array( 'parent' => $parent ) );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
			$filter .= '<ul class="isotopegs_nav nav_'.$uid.' tx_child_'.$settings['tx_child'].'">';
				$filter .= '<li><span data-filter="*">'.$settings['text_all'].'</span></li>';
				foreach ( $terms as $term ) {
					$filter .= '<li><span data-filter=".'.$term->slug.'">'.$term->name.'</span>';
						if($parent == 0){
							$c_terms = get_terms( $settings['tax'], array( 'parent' => $term->term_id) );
							$filter .= '<ul>';
								foreach ( $c_terms as $c_term ) {
									$filter .= '<li><span data-filter=".'.$c_term->slug.'">'.$c_term->name.'</span></li>';
								}
							$filter .= '</ul>';
						}
					$filter .= '</li>';
				}
			$filter .= '</ul>';
		}
	}
		
	wp_reset_postdata();
	$p_args = array(
		'post_type' => $settings['mood_slug'],
		'posts_per_page' => '-1',
	);
	$p_query = new WP_Query( $p_args );
	if($p_query->have_posts()){
		while($p_query->have_posts()){ $p_query->the_post();
			$items .= '<div class="isotopegs_item '.isotopegs_post_terms(get_the_ID(), $settings['tax']).'">';
				$items .= '<div class="isotopegs_item_in">';
					$content_function = ($settings['content_function'] == '') ? 'isotopegs_post_content_function' : $settings['content_function'];
					$items .= $content_function();
				$items .= '</div>';
			$items .= '</div>';
		}
	}
	wp_reset_postdata();
	
	echo $filter;
	echo '<div class="'.$main_div_class.'">';
		echo $items;
	echo '</div>';
	
	isotopegs_js($uid, $settings);
	
	$output .= ob_get_contents();
	ob_end_clean();
	
	return $output;	
}


/*
	Shortcode for Custom function array
=========================================================*/
add_shortcode('isotope_fn_grid', 'isotopegs_fn_shortcode');
function isotopegs_fn_shortcode( $atts, $content = null ) {
    $settings = shortcode_atts( array(
		'column' => '4',
		'column_m' => '2',
		'column_s' => '1',
		'class' => '',
		'gap' => '1x', //1x, 2x, 3x, 4x, 5x, 6x
		'array_fn' => '',
		'filter_fn' => '',
		'content_fn' => '',
		'text_all' => 'All',
		'args' => '',
    ), $atts );
	
	$output = '';
	
	ob_start();
	
	$uid = 'isotopegs_'.rand();	
	$main_div_class = $uid.' isotopegs ';
	$main_div_class .= 'column_'.$settings['column'].' ';
	$main_div_class .= 'column_m_'.$settings['column_m'].' ';
	$main_div_class .= 'column_s_'.$settings['column_s'].' ';
	$main_div_class .= 'gap_'.$settings['gap'].' ';
	$main_div_class .= $settings['class'].' ';
	
	$array_function = $settings['array_fn'];
	$content_function = ($settings['content_fn'] == '') ? 'isotopegs_fn_content_function' : $settings['content_fn'];
	$filter_array_function = $settings['filter_fn'];
	
	if(function_exists($filter_array_function)){
		$filters = $filter_array_function($settings);
		if(is_array($filters)){
			echo '<ul class="isotopegs_nav nav_'.$uid.'">';
				echo '<li><span data-filter="*">'.$settings['text_all'].'</span></li>';
				foreach($filters as $filter){
					echo '<li><span data-filter=".'.$filter['slug'].'">'.$filter['title'].'</span></li>';
				}
			echo '</ul>';
		}
	}
	
	if(function_exists($array_function) && function_exists($content_function)){
		$items = $array_function($settings);
		if(is_array($items)){
			echo '<div class="'.$main_div_class.'">';
				foreach($items as $item){
					$content_function($item, $settings);
				}
			echo '</div>';
		}
	}
	
	isotopegs_js($uid, $settings);
	
	$output .= ob_get_contents();
	ob_end_clean();
	
	return $output;	
}



/*
	Output post terms by Post ID
=========================================================*/
function isotopegs_post_terms($post_id, $taxonomy){
	$terms = get_the_terms( $post_id, $taxonomy );
	$on_draught = '';
	if ( $terms && ! is_wp_error( $terms ) ){
		 $draught_links = array();
		 foreach ( $terms as $term ) {
			$draught_links[] = $term->slug;
		}
		$on_draught = join( " ", $draught_links );
	}
	return $on_draught;	
}



/*
	The JavaScript of the Isotope
=========================================================*/
function isotopegs_js($uid, $settings, $data = array()){
	?>   
    <script type="text/javascript">
		jQuery(document).ready(function($){
			// init Isotope
			var $grid_<?php echo $uid; ?> = $('.<?php echo $uid; ?>').isotope({
			  itemSelector: '.isotopegs_item',
			});
			
			$grid_<?php echo $uid; ?>.imagesLoaded().progress( function() {
				$grid_<?php echo $uid; ?>.isotope('layout');
			});
			
			// filter items on button click
			$('.nav_<?php echo $uid; ?>').on( 'click', 'li span', function() {
				var filterValue_<?php echo $uid; ?> = $(this).attr('data-filter');
				$grid_<?php echo $uid; ?>.isotope({ filter: filterValue_<?php echo $uid; ?> });
			});
		});
	</script>
    <?php
}





/*
	Contert a string with ", " to filter class
	This is useful when you use function shortcode
=========================================================*/
function isotopegs_string_to_filter_class($string){
	$filter = '';
	$raw_j = array();
	if($string != ''){
		$raws = explode(",", $string);
		if(is_array($raws)){
			foreach($raws as $raw){
				$raw_j[] = sanitize_title($raw);
			}
		}
	}
	$filter = join( " ", $raw_j );
	
	return $filter;
}





/*
	Create filter array from a main array
	This is useful when you use function shortcode
=========================================================*/
function isotopegs_array_to_filter_array($arrays, $filter_key = 'filter'){
	$data = array();
	$check = array();
	if(is_array($arrays)){
		foreach($arrays as $array){
			if(isset($array[$filter_key])){
				if($array[$filter_key] != ''){
					$raws = explode(",", $array[$filter_key]);
					if(is_array($raws)){
						foreach($raws as $raw){
							$slug = sanitize_title($raw);
							if(!in_array($slug, $check)){
								$check[] = $slug;
								$data[] = array( 'slug' => $slug, 'title' => $raw );
							}
						}
					}
				}
				
			}
		}
	}
	
	return $data;
}


/*
	Content function for Post Shortcode
=========================================================*/
function isotopegs_post_content_function(){
	ob_start();	
	?>
    <div class="isotopegs_post_content">
    	<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?></a>
        <h5><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h5>
    </div>
    <?php
	$output = ob_get_contents();
	ob_end_clean();
	
	return $output;	
}


/*
	Content function for Function Shortcode
=========================================================*/
function isotopegs_fn_content_function($item, $settings){		
	$filter = '';
	if(isset($item['filter'])){
		$filter = isotopegs_string_to_filter_class($item['filter']);
	}
	?>
    <div class="isotopegs_item <?php echo $filter; ?>">
    	<div class="isotopegs_item_in">
        	<a href="<?php echo $item['link']; ?>"><img src="<?php echo $item['image']; ?>" alt="<?php echo $item['title']; ?>"></a>
            <h5><a href="<?php echo $item['link']; ?>"><?php echo $item['title']; ?></a></h5>
        </div>
    </div>
    <?php
}
function isotopegs_sample_array_fn($settings){
	return array(
		array('title' => 'Fashion #1', 'image' => 'https://unsplash.it/400/300/?image=1083', 'link'=>'#', 'filter' => 'IMG, Home, Find'),
		array('title' => 'Fashion #2', 'image' => 'https://unsplash.it/400/300?image=1040', 'link'=>'#', 'filter' => 'IMG, Find'),
		array('title' => 'Fashion #3', 'image' => 'https://unsplash.it/400/300?image=1027', 'link'=>'#', 'filter' => 'Find, Home'),
		array('title' => 'Fashion #4', 'image' => 'https://unsplash.it/400/300?image=999', 'link'=>'#', 'filter' => 'Home, Land'),
		array('title' => 'Fashion #5', 'image' => 'https://unsplash.it/400/300?image=977', 'link'=>'#', 'filter' => 'Land, Find, IMG'),
		array('title' => 'Fashion #6', 'image' => 'https://unsplash.it/400/300?image=961', 'link'=>'#', 'filter' => 'IMG, Find'),
		array('title' => 'Fashion #7', 'image' => 'https://unsplash.it/400/300?image=743', 'link'=>'#', 'filter' => 'Find, Home'),
	);	
}
function isotopegs_sample_filter_array_fn($settings){
	$arrays = isotopegs_sample_array_fn($settings);
	return isotopegs_array_to_filter_array($arrays, 'filter');
}
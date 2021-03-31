<?php
if ( ! defined( 'ABSPATH' ) ) {
   exit; // Exit if accessed directly.
}

/**
 * Custom WP REST API Routes
 *
 *  @since 1.0
 */
add_action( 'rest_api_init', function () {
	// Get posts ajaxloadmore[namespace] /posts[endpoint].
	$my_namespace = 'ajaxloadmore';
	$my_endpoint  = '/posts';
	register_rest_route(
		$my_namespace,
		$my_endpoint,
		array(
			'methods'             => 'GET',
			'callback'            => 'alm_get_posts',
			'permission_callback' => '__return_true',
		)
	);
});

/**
 * Custom /posts endpoint for ajaxloadmore.
 *
 * @see http://v2.wp-api.org/extending/adding/
 * @since 1.0
 * @param array $data Options for the function.
 * @return json object of post data.
 */
function alm_get_posts( $data ) {

   $response = array();

   // Set Defaults.

   $page = $data['page']; // the current page from ALM
   $posts_per_page = !empty($data['posts_per_page']) ? $data['posts_per_page'] : 5;
   $post_status = !empty($data['post_status']) ? $data['post_status'] : 'publish';

   /*
      Set Query Arguments
   */

   $args = array(
      'post_type'             => $data['post_type'],
      'posts_per_page'        => $posts_per_page,
      'offset'                => $data['offset'] + $page * $posts_per_page,
      'order'                 => $data['order'],
      'orderby'               => $data['orderby'],
      'post_status'           => $post_status,
      'ignore_sticky_posts'   => false,
   );

   // Post Format - we can combine these queries
	if(!empty($data['post_format']) || !empty($data['taxonomy'])){
      $tax_query_total = count(explode(":", $data['taxonomy'])); // Total $taxonomy objects
      $taxonomy = explode(":", $data['taxonomy']); // convert to array
      $taxonomy_terms = explode(":", $data['taxonomy_terms']); // convert to array
      $taxonomy_operator = explode(":", $data['taxonomy_operator']); // convert to array
      $taxonomy_relation = !empty($data['taxonomy_relation']) ? $data['taxonomy_relation'] : 'AND';
      $post_format = !empty($data['post_format']) ? $data['post_format'] : '';
      if(empty($taxonomy)){ // Post Format only
         $args['tax_query'] = array(
			   alm_get_post_format($post_format),
			);
		}else{ // Taxonomy and possibly Post Formats
         if($tax_query_total === 1){
   			$args['tax_query'] = array(
      			'relation' => $taxonomy_relation,
   			   alm_get_post_format($post_format),
   			   alm_get_taxonomy_query($taxonomy[0], $taxonomy_terms[0], $taxonomy_operator[0]),
   			);
			}
			if($tax_query_total === 2){
   			$args['tax_query'] = array(
      			'relation' => $taxonomy_relation,
   			   alm_get_post_format($post_format),
   			   alm_get_taxonomy_query($taxonomy[0], $taxonomy_terms[0], $taxonomy_operator[0]),
   			   alm_get_taxonomy_query($taxonomy[1], $taxonomy_terms[1], $taxonomy_operator[1]),
   			);
			}
			if($tax_query_total === 3){
   			$args['tax_query'] = array(
      			'relation' => $taxonomy_relation,
   			   alm_get_post_format($post_format),
   			   alm_get_taxonomy_query($taxonomy[0], $taxonomy_terms[0], $taxonomy_operator[0]),
   			   alm_get_taxonomy_query($taxonomy[1], $taxonomy_terms[1], $taxonomy_operator[1]),
   			   alm_get_taxonomy_query($taxonomy[2], $taxonomy_terms[2], $taxonomy_operator[2]),
   			);
			}
		}
   }

   // Category
   if(!empty($data['category'])) $args['category_name'] = $data['category'];

   // Category Not In
	if(!empty($data['category__not_in'])){
	   $exclude_cats = explode(",", $data['category__not_in']);
		$args['category__not_in'] = $exclude_cats;
	}

   // Tag
	if(!empty($data['tag'])) $args['tag'] = $data['tag'];

   // Tag Not In
	if(!empty($data['tag__not_in'])){
	   $exclude_tags = explode(",", $data['tag__not_in']);
		$args['tag__not_in'] = $exclude_tags;
	}

	// Date Query
	if(!empty($data['year'])) $args['year'] = $data['year'];
   if(!empty($data['month'])) $args['monthnum'] = $data['month'];
   if(!empty($data['day'])) $args['day'] = $data['day'];

	// Meta Query
	$meta_key = (isset($data['meta_key'])) ? $data['meta_key'] : '';
   $meta_value = (isset($data['meta_value'])) ? $data['meta_value'] : '';
   $meta_compare = !empty($data['meta_compare']) ? $data['meta_compare'] : 'IN';
   if($meta_compare === 'lessthan')
      $meta_compare = '<'; // do_shortcode fix (shortcode was rendering as HTML)
   if($meta_compare === 'lessthanequalto')
      $meta_compare = '<='; // do_shortcode fix (shortcode was rendering as HTML)
   $meta_relation = !empty($data['meta_relation']) ? $data['meta_relation'] : 'AND';
   $meta_type = !empty($data['meta_type']) ? $data['meta_type'] : 'CHAR';

	if(!empty($meta_key) && !empty($meta_value) || !empty($meta_key) && $meta_compare !== "IN"){
      $meta_query_total = count(explode(":", $meta_key)); // Total meta_query objects
      $meta_keys = explode(":", $meta_key); // convert to array
      $meta_value = explode(":", $meta_value); // convert to array
      $meta_compare = explode(":", $meta_compare); // convert to array
      $meta_type = explode(":", $meta_type); // convert to array
      if($meta_query_total == 1){
			$args['meta_query'] = array(
			   alm_get_meta_query($meta_keys[0], $meta_value[0], $meta_compare[0], $meta_type[0]),
			);
		}
		if($meta_query_total == 2){
			$args['meta_query'] = array(
   			'relation' => $meta_relation,
			   alm_get_meta_query($meta_keys[0], $meta_value[0], $meta_compare[0], $meta_type[0]),
			   alm_get_meta_query($meta_keys[1], $meta_value[1], $meta_compare[1], $meta_type[1]),
			);
		}
		if($meta_query_total == 3){
			$args['meta_query'] = array(
   			'relation' => $meta_relation,
			   alm_get_meta_query($meta_keys[0], $meta_value[0], $meta_compare[0], $meta_type[0]),
			   alm_get_meta_query($meta_keys[1], $meta_value[1], $meta_compare[1], $meta_type[1]),
			   alm_get_meta_query($meta_keys[2], $meta_value[2], $meta_compare[2], $meta_type[2]),
			);
		}
		if($meta_query_total == 4){
			$args['meta_query'] = array(
   			'relation' => $meta_relation,
			   alm_get_meta_query($meta_keys[0], $meta_value[0], $meta_compare[0], $meta_type[0]),
			   alm_get_meta_query($meta_keys[1], $meta_value[1], $meta_compare[1], $meta_type[1]),
			   alm_get_meta_query($meta_keys[2], $meta_value[2], $meta_compare[2], $meta_type[2]),
			   alm_get_meta_query($meta_keys[3], $meta_value[3], $meta_compare[3], $meta_type[3]),
			);
		}
   }

   // Meta_key [ordering by meta value]
   if(!empty($meta_key)){
      if (strpos($data['orderby'], 'meta_value') !== false) {
         // Order by meta_key, if $data['orderby'] is set to meta_value{_num}
         $meta_key_single = explode(":", $meta_key);
         $args['meta_key'] = $meta_key_single[0];
      }
   }

   // Author
   if(!empty($data['author'])) $args['author'] = $data['author'];

   // Include posts
	if(!empty($data['post__in'])){
		$post__in = explode(",", $data['post__in']);
		$args['post__in'] = $post__in;
	}

	// Exclude posts
	if(!empty($data['post__not_in'])){
		$post__not_in = explode(",", $data['post__not_in']);
		$args['post__not_in'] = $post__not_in;
	}

	// Custom Args
	if(!empty($data['custom_args'])){
		$custom_args_array = explode(";", $data['custom_args']); // Split the $custom_args at ','
		foreach($custom_args_array as $argument){ // Loop each $argument
			$argument = preg_replace('/\s+/', '', $argument); // Remove all whitespace
		   $argument = explode(":", $argument);  // Split the $argument at ':'
		   $argument_arr = explode(",", $argument[1]);  // explode $argument[1] at ','
		   if(sizeof($argument_arr) > 1){
		      $args[$argument[0]] = $argument_arr;
		   }else{
		      $args[$argument[0]] = $argument[1];
		   }

		}
	}

   // Search Term
	if(!empty($data['search'])) $args['s'] = $data['search'];

   // Language
	if(!empty($data['lang'])) $args['lang'] = $data['lang'];

   // Run Query
   $posts = new WP_Query( $args );

   // ALM Template vars [https://connekthq.com/plugins/ajax-load-more/docs/variables/]
   $alm_item = $page * $posts_per_page;
   $alm_found_posts = $posts->found_posts;
   $alm_post_count = $posts->post_count;
   $alm_current = 0;
   $data = array();

	while ( $posts->have_posts() ) : $posts->the_post();

   	$alm_current++;

   	// Get post thumbnail
   	$thumbnail_id = get_post_thumbnail_id();
   	$thumbnail = '';
   	$alt = '';
   	if($thumbnail_id){
   	   $thumbnail_arr = wp_get_attachment_image_src($thumbnail_id, 'alm-thumbnail', true);
   	   $thumbnail = $thumbnail_arr[0];
   	   $alt = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
      }

   	// Build $data JSON object
      $data[] = array(
         'alm_page' => $page + 1,
         'alm_item' => ($alm_item++) + 1,
         'alm_current' => $alm_current,
         'alm_found_posts' => $alm_found_posts,
         'date' => get_the_time("F d, Y"),
         'link' => get_permalink(),
         'post_title' => get_the_title(),
         'post_excerpt' => get_the_excerpt(),
         'thumbnail' => $thumbnail,
         'thumbnail_alt' => $alt
      );

      // Content [Apply shortcode filter for loaded shortcodes]
      // $content = get_the_content();
      // $data['post_content'] = apply_filters('the_content', $content);

   endwhile; wp_reset_query();

   if (empty( $data )) { // Empty results
      $data = null;
      $alm_post_count = null;
      $alm_found_posts = null;
   }

   $return = array(
      'html' => $data,
      'meta'  => array(
         'postcount' => $alm_post_count,
         'totalposts' => $alm_found_posts
      )
   );

   wp_send_json($return);

}

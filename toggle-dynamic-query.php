<?php

// Extend Toggle Elementor Module

add_action( 'elementor/element/toggle/section_toggle/before_section_end', 'jutso_call_to_action_add_toggle', 10, 2 );
add_action( 'elementor/frontend/widget/before_render', 'jutso_call_to_action_add_toggle_before_render', 10, 1 );
add_action( 'elementor/widget/before_render_content', 'jutso_call_to_action_add_toggle_before_render', 10, 1 );


function jutso_call_to_action_add_toggle($element, $args)
{
	$element->add_control('enable_toggle', [
		'label' => 'Enable Dynamic Query',
		'type' => Elementor\Controls_Manager::SWITCHER,
		'default' => 'no',
	]);
	
	$element->add_group_control(
		ElementorPro\Modules\QueryControl\Controls\Group_Control_Related::get_type(),
		[
			'name' => 'toggle_posts',
			'presets' => ['full'],
			'exclude' => [
				'posts_per_page', //use the one from Layout section
			],
			'condition' => [
				'enable_toggle' => 'yes',
			],
		]
	);
	$element->add_control(
		'posts_per_page',
		[
			'label' => esc_html__('Total Posts', 'elementor-project-portfolio'),
			'type' => Elementor\Controls_Manager::NUMBER,
			'default' => 6,
			'condition' => [
				'enable_toggle' => 'yes',
			],
		]
	);
}

function jutso_call_to_action_add_toggle_print_template( $template_content, $object )
{
	if( $object->get_name() == 'toggle' ){
		$template_content = '';
	}
	return $template_content;
}


function jutso_call_to_action_add_toggle_before_render( $object )
{
	if ($object->get_name() == 'toggle') {
		
		$settings = $object->get_active_settings();
		
		if ( $object->get_settings('enable_toggle') == 'yes' ) {
			
			add_filter( "elementor/widget/print_template", "jutso_call_to_action_add_toggle_print_template", 99, 2 );
			
			$posts_per_page = $object->get_settings('posts_per_page') ? $object->get_settings('posts_per_page') : 6;
			$tabs = generate_query_tabs_custom($object, $posts_per_page, $settings);
			
			if( !empty( $tabs ) ){
				$object->set_settings( 'tabs', $tabs);
			}
		}
	}
}
function query_posts_toggle_render( $object, $posts_per_page, $settings )
{
	$query_args = [
		'posts_per_page' => $posts_per_page,
	];
	
	/** @var ElementorPro\Modules\QueryControl\Module $elementor_query */
	$controls_manager = ElementorPro\Plugin::elementor()->controls_manager;
	$posts_query = $controls_manager->get_control_groups( 'posts' );
	
	$posts_query_args = $posts_query->get_query_args( 'toggle_posts', $settings );
	return $posts_query_args;
}
function generate_query_tabs_custom($object, $posts_per_page, $settings){
	
	if( ! defined('ELEMENTOR_PRO_VERSION') ){
		return;
	}
	$toggle_query = query_posts_toggle_render($object, $posts_per_page, $settings);
	
	$toggle_query = array(
		'post_type' => $settings['toggle_posts_post_type'],
		'posts_per_page' => $settings['posts_per_page'],
		'orderby' => $settings['toggle_posts_orderby'],
		'order' => $settings['toggle_posts_order'],
		'post__in' => !empty($settings['toggle_posts_posts_ids']) ? $settings['toggle_posts_posts_ids'] : array(),
		'post__not_in' => !empty($settings['toggle_posts_exclude_ids']) ? $settings['toggle_posts_exclude_ids'] : array(),
		'author__in' => !empty($settings['toggle_posts_include_authors']) ? $settings['toggle_posts_include_authors'] : array(),
		'author__not_in' => !empty($settings['toggle_posts_exclude_authors']) ? $settings['toggle_posts_exclude_authors'] : array(),
		'offset' => $settings['toggle_posts_offset'],
		'date_query' => array(
			'after' => $settings['toggle_posts_date_after'], 
			'before' => $settings['toggle_posts_date_before'],
			'inclusive' => true,
		),
		'ignore_sticky_posts' => $settings['toggle_posts_ignore_sticky_posts'] === 'yes' ? 1 : 0,
	);
	
	if (!empty($settings['toggle_posts_include_term_ids'])) {
		$include_terms = $settings['toggle_posts_include_term_ids'];
		foreach ($include_terms as $term_id) {
			$toggle_query['tax_query'][] = array(
				'taxonomy' => 'behandlung-type', // Replace 'your_taxonomy_name' with the actual taxonomy name
				'field' => 'term_id',
				'terms' => $term_id,
				'operator' => 'IN',
			);
		}
	}

	if (!empty($settings['toggle_posts_exclude_term_ids'])) {
	   $exclude_terms = $settings['toggle_posts_exclude_term_ids'];
		foreach ($exclude_terms as $term_id) {
			$toggle_query['tax_query'][] = array(
				'taxonomy' => 'behandlung-type', 
				'field' => 'term_id',
				'terms' => $term_id,
				'operator' => 'NOT IN',
			);
		}
	}
	
	$posts = get_posts($toggle_query);
	
	if (empty( $posts )) {
		return;
	}
	$tabs = [];
	foreach ( $posts as $post ) {
		$post_id = $post->ID;
		$featured_image = '';
		
		$tab_content = apply_filters( 'the_content', $post->post_content );
		
		
		$link = get_permalink( $post );
		
		if ( has_post_thumbnail( $post_id ) ) {
			$featured_image_url = get_the_post_thumbnail_url( $post_id, 'full' );
			$tab_content .= '<img src="' . esc_url( $featured_image_url ) . '" alt="Featured Image" class="featued-image">'; 
		}
		
		$post_title = $post->post_title;
		
		$icon_image = get_post_meta( $post_id, 'icon_image', true );
		
		if( $icon_image ){
			$post_title = '<span class="icon-image"><img src="' . wp_get_attachment_url( $icon_image ) . '" alt="icon" /></span>' . $post_title;
		}
		
		$tabs[] = [
			'tab_title' => $post_title,
			'tab_content' => $tab_content
		];
	}
	
	return $tabs;
}


?>
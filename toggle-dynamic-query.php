<?php

// Extends Toggle Elementor Module with dynamic query functionality

// Hooks to add custom controls and settings for the toggle widget in Elementor
add_action('elementor/element/toggle/section_toggle/before_section_end', 'coresol_call_to_action_add_toggle', 10, 2);
add_action('elementor/frontend/widget/before_render', 'coresol_call_to_action_add_toggle_before_render', 10, 1);
add_action('elementor/widget/before_render_content', 'coresol_call_to_action_add_toggle_before_render', 10, 1);

/**
 * Adds custom controls to the Elementor Toggle widget for dynamic content querying.
 *
 * @param Elementor\Element_Base $element Elementor Element.
 * @param array $args Additional arguments.
 */
function coresol_call_to_action_add_toggle($element, $args) {
	// Control to enable or disable the dynamic query
	$element->add_control('enable_toggle', [
		'label' => 'Enable Dynamic Query',
		'type' => Elementor\Controls_Manager::SWITCHER,
		'default' => 'no',
	]);

	// Group control to select and configure the dynamic query for posts
	$element->add_group_control(
		ElementorPro\Modules\QueryControl\Controls\Group_Control_Related::get_type(),
		[
			'name' => 'toggle_posts',
			'presets' => ['full'],
			'exclude' => [
				'posts_per_page', // Use the setting from the Layout section instead
			],
			'condition' => [
				'enable_toggle' => 'yes',
			],
		]
	);

	// Control to specify the number of posts to display
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

/**
 * Removes default widget template rendering when toggle is enabled, allowing custom rendering.
 *
 * @param string $template_content Original template content.
 * @param Elementor\Element_Base $object Elementor Element.
 * @return string Modified template content.
 */
function coresol_call_to_action_add_toggle_print_template($template_content, $object) {
	if ($object->get_name() == 'toggle') {
		$template_content = ''; // Clear default rendering
	}
	return $template_content;
}

/**
 * Conditionally adds a filter to override the toggle widget template.
 *
 * @param Elementor\Element_Base $object Elementor Element.
 */
function coresol_call_to_action_add_toggle_before_render($object) {
	if ($object->get_name() == 'toggle' && $object->get_settings('enable_toggle') == 'yes') {
		// Attach the template override if dynamic querying is enabled
		add_filter("elementor/widget/print_template", "coresol_call_to_action_add_toggle_print_template", 99, 2);

		$posts_per_page = $object->get_settings('posts_per_page', 6);
		$tabs = generate_query_tabs_custom($object, $posts_per_page, $object->get_active_settings());

		if (!empty($tabs)) {
			$object->set_settings('tabs', $tabs);
		}
	}
}

/**
 * Generates custom tabs based on the queried posts.
 *
 * @param Elementor\Element_Base $object Elementor Element.
 * @param int $posts_per_page Number of posts to query.
 * @param array $settings Elementor settings for the widget.
 * @return array Tabs with titles and content.
 */
function generate_query_tabs_custom($object, $posts_per_page, $settings) {
	if (!defined('ELEMENTOR_PRO_VERSION')) {
		return []; // Exit if Elementor Pro is not activated
	}

	$toggle_query = query_posts_toggle_render($object, $posts_per_page, $settings);
	$posts = get_posts($toggle_query);

	if (empty($posts)) {
		return []; // No posts found, return empty array
	}

	$tabs = [];
	foreach ($posts as $post) {
		$post_id = $post->ID;
		$featured_image = '';

		if (has_post_thumbnail($post_id)) {
			$featured_image_url = get_the_post_thumbnail_url($post_id, 'full');
			$featured_image = '<img src="' . esc_url($featured_image_url) . '" alt="Featured Image" class="featured-image">';
		}

		$tabs[] = [
			'tab_title' => '<span class="icon-image"><img src="' . get_post_meta($post_id, 'icon_image', true) . '" alt="icon" /></span>' . get_the_title($post),
			'tab_content' => apply_filters('the_content', $post->post_content) . $featured_image
		];
	}

	return $tabs;
}

/**
 * Creates a WP_Query arguments array based on the settings provided.
 *
 * @param Elementor\Element_Base $object Elementor Element.
 * @param int $posts_per_page Number of posts per page.
 * @param array $settings Elementor settings.
 * @return array WP_Query arguments.
 */
function query_posts_toggle_render($object, $posts_per_page, $settings) {
	$query_args = [
		'post_type' => $settings['toggle_posts_post_type'],
		'posts_per_page' => $posts_per_page,
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
		'tax_query' => []
	];

	// Include terms in tax query if specified
	if (!empty($settings['toggle_posts_include_term_ids'])) {
		foreach ($settings['toggle_posts_include_term_ids'] as $term_id) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'category', // Ensure this matches your taxonomy
				'field' => 'term_id',
				'terms' => $term_id,
				'operator' => 'IN',
			);
		}
	}

	// Exclude terms in tax query if specified
	if (!empty($settings['toggle_posts_exclude_term_ids'])) {
		foreach ($settings['toggle_posts_exclude_term_ids'] as $term_id) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'category', // Ensure this matches your taxonomy
				'field' => 'term_id',
				'terms' => $term_id,
				'operator' => 'NOT IN',
			);
		}
	}

	return $query_args;
}
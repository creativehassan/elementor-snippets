<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use ElementorPro\Plugin;

class Elementor_Section_Divider_Field extends \ElementorPro\Modules\Forms\Fields\Field_Base {

	/**
	 * Get field type.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Field type.
	 */
	public function get_type() {
		return 'section-divider';
	}

	/**
	 * Get field name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Field name.
	 */
	public function get_name() {
		return esc_html__( 'Section Divider', 'elementor-form-section-divider-field' );
	}
	
	/**
	 * @param Widget_Base $widget
	 */
	public function update_controls( $widget ) {
		$elementor = Plugin::elementor();
	
		$control_data = $elementor->controls_manager->get_control_from_stack( $widget->get_unique_name(), 'form_fields' );
	
		if ( is_wp_error( $control_data ) ) {
			return;
		}
	
		$field_controls = [
			'section_number' => [
				'name' => 'section_number',
				'label' => esc_html__( 'Section Number', 'elementor' ),
				'type' => Controls_Manager::TEXT,
				'default' => 1,
				'min' => 1,
				'separator' => 'before',
				'condition' => [
					'field_type' => $this->get_type(),
				],
				'description' => esc_html__( 'Specify the section number.', 'elementor-pro' ),
				'tab' => 'content',
				'inner_tab' => 'form_fields_content_tab',
				'tabs_wrapper' => 'form_fields_tabs',
			],
			'section_text' => [
				'name' => 'section_text',
				'label' => esc_html__( 'Section Text', 'elementor' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'separator' => 'before',
				'condition' => [
					'field_type' => $this->get_type(),
				],
				'description' => esc_html__( 'Specify the section text.', 'elementor-pro' ),
				'tab' => 'content',
				'inner_tab' => 'form_fields_content_tab',
				'tabs_wrapper' => 'form_fields_tabs',
			]
		];
	
		$control_data['fields'] = $this->inject_field_controls( $control_data['fields'], $field_controls );
		$widget->update_control( 'form_fields', $control_data );
	}

	/**
	 * Render field output on the frontend.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param mixed $item
	 * @param mixed $item_index
	 * @param mixed $form
	 * @return void
	 */
	public function render( $item, $item_index, $form ) {
		$section_number = isset( $item['section_number'] ) ? $item['section_number'] : '';
		$section_text = isset( $item['section_text'] ) ? $item['section_text'] : '';

		echo '<div class="section-divider">';
		echo '<span class="section-number">' . esc_html( $section_number ) . '</span>';
		echo '<span class="section-text">' . esc_html( $section_text ) . '</span>';
		echo '</div>';
	}

	/**
	 * Field validation.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Field_Base   $field
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 * @return void
	 */
	public function validation( $field, $record, $ajax_handler ) {
		// No validation needed for section divider
	}

	/**
	 * Field constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'elementor/preview/init', [ $this, 'editor_preview_footer' ] );
	}

	/**
	 * Elementor editor preview.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function editor_preview_footer() {
		add_action( 'wp_footer', [ $this, 'content_template_script' ] );
	}

	/**
	 * Content template script.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function content_template_script() {
		?>
		<script>
		jQuery(document).ready(function(){
			elementor.hooks.addFilter(
				'elementor_pro/forms/content_template/field/section-divider',
				function (inputField, item, i) {
					const sectionNumber = item.section_number || '';
					const sectionText = item.section_text || '';

					return `
						<div class="section-divider">
							<span class="section-number">${sectionNumber}</span>
							<span class="section-text">${sectionText}</span>
						</div>`;
				}, 10, 3
			);
		});
		</script>
		<?php
	}
}

// Add the style tab controls
add_action('elementor/element/after_section_end', function($element, $section_id, $args) {
	if( $section_id !== 'section_steps_style' ){
		return;
	}

	$element->start_controls_section(
		'section_divider_style',
		[
			'label' => esc_html__( 'Section Divider', 'elementor-pro' ),
			'tab' => Controls_Manager::TAB_STYLE,
		]
	);

	$element->add_control(
		'divider_background',
		[
			'label' => esc_html__( 'Background', 'elementor-pro' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#ced4da',
			'selectors' => [
				'{{WRAPPER}} .section-divider' => 'background-color: {{VALUE}}'
			],
		]
	);

	$element->add_responsive_control(
		'divider_padding',
		[
			'label' => esc_html__( 'Padding', 'elementor-pro' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
			'selectors' => [
				'{{WRAPPER}} .section-divider' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		]
	);

	$element->add_responsive_control(
		'divider_margin',
		[
			'label' => esc_html__( 'Margin', 'elementor-pro' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
			'selectors' => [
				'{{WRAPPER}} .section-divider' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		]
	);

	$element->add_control(
		'section_number_heading',
		[
			'label' => esc_html__( 'Section Number', 'elementor-pro' ),
			'type' => Controls_Manager::HEADING,
			'separator' => 'before',
		]
	);

	$element->add_control(
		'section_number_background',
		[
			'label' => esc_html__( 'Background', 'elementor-pro' ),
			'type' => Controls_Manager::COLOR,
			'default' => 'rgba(255, 255, 255, 0.95)',
			'selectors' => [
				'{{WRAPPER}} .section-number' => 'background-color: {{VALUE}}'
			],
		]
	);

	$element->add_control(
		'section_number_color',
		[
			'label' => esc_html__( 'Color', 'elementor-pro' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#6c757d',
			'selectors' => [
				'{{WRAPPER}} .section-number' => 'color: {{VALUE}}'
			],
		]
	);

	$element->add_responsive_control(
		'section_number_margin',
		[
			'label' => esc_html__( 'Margin', 'elementor-pro' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
			'selectors' => [
				'{{WRAPPER}} .section-number' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		]
	);

	$element->end_controls_section();
}, 10, 3);

function register_form_section_divider_field( $manager ){
	$manager->register(new \Elementor_Section_Divider_Field());
}
add_action('elementor_pro/forms/fields/register', 'register_form_section_divider_field');
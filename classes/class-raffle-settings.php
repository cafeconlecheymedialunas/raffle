<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Raffle_Plugin_Settings Class
 *
 * @class Raffle_Plugin_Settings
 * @version	1.0.0
 * @since 1.0.0
 * @package	Raffle_Plugin
 * @author Jeffikus
 */
final class Raffle_Plugin_Settings {
	/**
	 * Raffle_Plugin_Admin The single instance of Raffle_Plugin_Admin.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $instance = null;

	/**
	 * Whether or not a 'select' field is present.
	 * @var     boolean
	 * @access  private
	 * @since   1.0.0
	 */
	private $has_select;

	/**
	 * Main Raffle_Plugin_Settings Instance
	 *
	 * Ensures only one instance of Raffle_Plugin_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return Main Raffle_Plugin_Settings instance
	 */
	public static function instance () {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct () {
	}

	/**
	 * Validate the settings.
	 * @access  public
	 * @since   1.0.0
	 * @param   array $input Inputted data.
	 * @param   string $section field section.
	 * @return  array        Validated data.
	 */
	public function validate_settings ( $input, $section ) {
		if ( is_array( $input ) && 0 < count( $input ) ) {
			$fields = $this->get_settings_fields( $section );

			foreach ( $input as $k => $v ) {
				if ( ! isset( $fields[ $k ] ) ) {
					continue;
				}

				// Determine if a method is available for validating this field.
				$method = 'validate_field_' . $fields[ $k ]['type'];

				if ( ! method_exists( $this, $method ) ) {
					if ( true === (bool) apply_filters( 'raffle_plugin_validate_field_' . $fields[ $k ]['type'] . '_use_default', true ) ) {
						$method = 'validate_field_text';
					} else {
						$method = '';
					}
				}

				// If we have an internal method for validation, filter and apply it.
				if ( '' !== $method ) {
					add_filter( 'raffle_plugin_validate_field_' . $fields[ $k ]['type'], array( $this, $method ) );
				}

				$method_output = apply_filters( 'raffle_plugin_validate_field_' . $fields[ $k ]['type'], $v, $fields[ $k ] );

				if ( ! is_wp_error( $method_output ) ) {
					$input[ $k ] = $method_output;
				}
			}
		}
		return $input;
	}

	/**
	 * Validate the given data, assuming it is from a text input field.
	 * @access  public
	 * @since   6.0.0
	 * @return  void
	 */
	public function validate_field_text ( $v ) {
		return (string) wp_kses_post( $v );
	}

	/**
	 * Validate the given data, assuming it is from a textarea field.
	 * @access  public
	 * @since   6.0.0
	 * @return  void
	 */
	public function validate_field_textarea ( $v ) {
		// Allow iframe, object and embed tags in textarea fields.
		$allowed           = wp_kses_allowed_html( 'post' );
		$allowed['iframe'] = array(
			'src'    => true,
			'width'  => true,
			'height' => true,
			'id'     => true,
			'class'  => true,
			'name'   => true,
		);
		$allowed['object'] = array(
			'src'    => true,
			'width'  => true,
			'height' => true,
			'id'     => true,
			'class'  => true,
			'name'   => true,
		);
		$allowed['embed']  = array(
			'src'    => true,
			'width'  => true,
			'height' => true,
			'id'     => true,
			'class'  => true,
			'name'   => true,
		);

		return wp_kses( $v, $allowed );
	}

	/**
	 * Validate the given data, assuming it is from a checkbox input field.
	 * @access public
	 * @since  6.0.0
	 * @param  string $v
	 * @return string
	 */
	public function validate_field_checkbox ( $v ) {
		if ( 'true' !== $v ) {
			return 'false';
		} else {
			return 'true';
		}
	}

	/**
	 * Validate the given data, assuming it is from a URL field.
	 * @access public
	 * @since  6.0.0
	 * @param  string $v
	 * @return string
	 */
	public function validate_field_url ( $v ) {
		return trim( esc_url( $v ) );
	}

	/**
	 * Render a field of a given type.
	 * @access  public
	 * @since   1.0.0
	 * @param   array $args The field parameters.
	 * @return  void
	 */
	public function render_field ( $args ) {
		if ( ! in_array( $args['type'], $this->get_supported_fields(), true ) ) {
			return ''; // Supported field type sanity check.
		}

		// Make sure we have some kind of default, if the key isn't set.
		if ( ! isset( $args['default'] ) ) {
			$args['default'] = '';
		}

		$method = 'render_field_' . $args['type'];

		if ( ! method_exists( $this, $method ) ) {
			$method = 'render_field_text';
		}

		// Construct the key.
		$key = Raffle_Plugin()->token . '-' . $args['section'] . '[' . $args['id'] . ']';

		echo $this->$method( $key, $args ); /* phpcs:ignore */

		// Output the description, if the current field allows it.
		if ( isset( $args['type'] ) && ! in_array( $args['type'], (array) apply_filters( 'raffle_plugin_no_description_fields', array( 'checkbox' ) ), true ) ) {
			if ( isset( $args['description'] ) ) {
				$description = $args['description'];
				if ( in_array( $args['type'], (array) apply_filters( 'raffle_plugin_new_line_description_fields', array( 'textarea', 'select' ) ), true ) ) {
					$description = wpautop( $description );
				}
				echo '<p class="description">' . wp_kses_post( $description ) . '</p>';
			}
		}
	}

	/**
	 * Retrieve the settings fields details
	 * @access  public
	 * @since   1.0.0
	 * @return  array        Settings fields.
	 */
	public function get_settings_sections () {
		$settings_sections = array();

		$settings_sections['standard-fields'] = __( 'Standard Fields', 'raffle' );
		$settings_sections['special-fields']  = __( 'Special Fields', 'raffle' );
		// Add your new sections below here.
		// Admin tabs will be created for each section.
		// Don't forget to add fields for the section in the get_settings_fields() function below

		return (array) apply_filters( 'raffle_plugin_settings_sections', $settings_sections );
	}

	/**
	 * Retrieve the settings fields details
	 * @access  public
	 * @param  string $section field section.
	 * @since   1.0.0
	 * @return  array        Settings fields.
	 */
	public function get_settings_fields ( $section ) {
		$settings_fields = array();
		// Declare the default settings fields.

		switch ( $section ) {
			case 'standard-fields':
				$settings_fields['text']     = array(
					'name'        => __( 'Example Text Input', 'raffle' ),
					'type'        => 'text',
					'default'     => '',
					'section'     => 'standard-fields',
					'description' => __( 'Place the field description text here.', 'raffle' ),
				);
				$settings_fields['textarea'] = array(
					'name'        => __( 'Example Textarea', 'raffle' ),
					'type'        => 'textarea',
					'default'     => '',
					'section'     => 'standard-fields',
					'description' => __( 'Place the field description text here.', 'raffle' ),
				);
				$settings_fields['checkbox'] = array(
					'name'        => __( 'Example Checkbox', 'raffle' ),
					'type'        => 'checkbox',
					'default'     => '',
					'section'     => 'standard-fields',
					'description' => __( 'Place the field description text here.', 'raffle' ),
				);
				$settings_fields['radio']    = array(
					'name'        => __( 'Example Radio Buttons', 'raffle' ),
					'type'        => 'radio',
					'default'     => '',
					'section'     => 'standard-fields',
					'options'     => array(
						'one'   => __( 'One', 'raffle' ),
						'two'   => __( 'Two', 'raffle' ),
						'three' => __( 'Three', 'raffle' ),
					),
					'description' => __( 'Place the field description text here.', 'raffle' ),
				);
				$settings_fields['select']   = array(
					'name'        => __( 'Example Select', 'raffle' ),
					'type'        => 'select',
					'default'     => '',
					'section'     => 'standard-fields',
					'options'     => array(
						'one'   => __( 'One', 'raffle' ),
						'two'   => __( 'Two', 'raffle' ),
						'three' => __( 'Three', 'raffle' ),
					),
					'description' => __( 'Place the field description text here.', 'raffle' ),
				);

				break;
			case 'special-fields':
				$settings_fields['select_taxonomy'] = array(
					'name'        => __( 'Example Taxonomy Selector', 'raffle' ),
					'type'        => 'select_taxonomy',
					'default'     => '',
					'section'     => 'special-fields',
					'description' => __( 'Place the field description text here.', 'raffle' ),
				);

				break;
			default:
				# code...
				break;
		}

		return (array) apply_filters( 'raffle_plugin_settings_fields', $settings_fields );
	}

	/**
	 * Render HTML markup for the "text" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_text ( $key, $args ) {
		$html = '<input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" size="40" type="text" value="' . esc_attr( $this->get_value( $args['id'], $args['default'], $args['section'] ) ) . '" />' . "\n";
		return $html;
	}

	/**
	 * Render HTML markup for the "radio" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_radio ( $key, $args ) {
		$html = '';
		if ( isset( $args['options'] ) && ( 0 < count( (array) $args['options'] ) ) ) {
			$html = '';
			foreach ( $args['options'] as $k => $v ) {
				$html .= '<input type="radio" name="' . esc_attr( $key ) . '" value="' . esc_attr( $k ) . '"' . checked( esc_attr( $this->get_value( $args['id'], $args['default'], $args['section'] ) ), $k, false ) . ' /> ' . esc_html( $v ) . '<br />' . "\n";
			}
		}
		return $html;
	}

	/**
	 * Render HTML markup for the "textarea" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_textarea ( $key, $args ) {
		// Explore how best to escape this data, as esc_textarea() strips HTML tags, it seems.
		$html = '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" cols="42" rows="5">' . $this->get_value( $args['id'], $args['default'], $args['section'] ) . '</textarea>' . "\n";
		return $html;
	}

	/**
	 * Render HTML markup for the "checkbox" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_checkbox ( $key, $args ) {
		$has_description = false;
		$html            = '';
		if ( isset( $args['description'] ) ) {
			$has_description = true;
			$html           .= '<label for="' . esc_attr( $key ) . '">' . "\n";
		}
		$html .= '<input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="checkbox" value="true"' . checked( esc_attr( $this->get_value( $args['id'], $args['default'], $args['section'] ) ), 'true', false ) . ' />' . "\n";
		if ( $has_description ) {
			$html .= wp_kses_post( $args['description'] ) . '</label>' . "\n";
		}
		return $html;
	}

	/**
	 * Render HTML markup for the "select2" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_select ( $key, $args ) {
		$this->has_select = true;

		$html = '';
		if ( isset( $args['options'] ) && ( 0 < count( (array) $args['options'] ) ) ) {
			$html .= '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . "\n";
			foreach ( $args['options'] as $k => $v ) {
				$html .= '<option value="' . esc_attr( $k ) . '"' . selected( esc_attr( $this->get_value( $args['id'], $args['default'], $args['section'] ) ), $k, false ) . '>' . esc_html( $v ) . '</option>' . "\n";
			}
			$html .= '</select>' . "\n";
		}
		return $html;
	}

	/**
	 * Render HTML markup for the "select_taxonomy" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_select_taxonomy ( $key, $args ) {
		$this->has_select = true;

		$defaults = array(
			'show_option_all'  => '',
			'show_option_none' => '',
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'show_count'       => 0,
			'hide_empty'       => 1,
			'child_of'         => 0,
			'exclude'          => '',
			'selected'         => $this->get_value( $args['id'], $args['default'], $args['section'] ),
			'hierarchical'     => 1,
			'class'            => 'postform',
			'depth'            => 0,
			'tab_index'        => 0,
			'taxonomy'         => 'category',
			'hide_if_empty'    => false,
			'walker'           => '',
		);

		if ( ! isset( $args['options'] ) ) {
			$args['options'] = array();
		}

		$args['options']         = wp_parse_args( $args['options'], $defaults );
		$args['options']['echo'] = false;
		$args['options']['name'] = esc_attr( $key );
		$args['options']['id']   = esc_attr( $key );

		$html  = '';
		$html .= wp_dropdown_categories( $args['options'] );

		return $html;
	}

	/**
	 * Return an array of field types expecting an array value returned.
	 * @access public
	 * @since  1.0.0
	 * @return array
	 */
	public function get_array_field_types () {
		return array();
	}

	/**
	 * Return an array of field types where no label/header is to be displayed.
	 * @access protected
	 * @since  1.0.0
	 * @return array
	 */
	protected function get_no_label_field_types () {
		return array( 'info' );
	}

	/**
	 * Return a filtered array of supported field types.
	 * @access  public
	 * @since   1.0.0
	 * @return  array Supported field type keys.
	 */
	public function get_supported_fields () {
		return (array) apply_filters( 'raffle_plugin_supported_fields', array( 'text', 'checkbox', 'radio', 'textarea', 'select', 'select_taxonomy' ) );
	}

	/**
	 * Return a value, using a desired retrieval method.
	 * @access  public
	 * @param  string $key option key.
	 * @param  string $default default value.
	 * @param  string $section field section.
	 * @since   1.0.0
	 * @return  mixed Returned value.
	 */
	public function get_value ( $key, $default, $section ) {
		$values = get_option( 'raffle-' . $section, array() );

		if ( is_array( $values ) && isset( $values[ $key ] ) ) {
			$response = $values[ $key ];
		} else {
			$response = $default;
		}

		return $response;
	}

	/**
	 * Return all settings keys.
	 * @access  public
	 * @param  string $section field section.
	 * @since   1.0.0
	 * @return  mixed Returned value.
	 */
	public function get_settings ( $section = '' ) {
		$response = false;

		$sections = array_keys( (array) $this->get_settings_sections() );

		if ( in_array( $section, $sections, true ) ) {
			$sections = array( $section );
		}

		if ( 0 < count( $sections ) ) {
			foreach ( $sections as $k => $v ) {
				$fields = $this->get_settings_fields( $v );
				$values = get_option( 'raffle-' . $v, array() );

				if ( is_array( $fields ) && 0 < count( $fields ) ) {
					foreach ( $fields as $i => $j ) {
						// If we have a value stored, use it.
						if ( isset( $values[ $i ] ) ) {
							$response[ $i ] = $values[ $i ];
						} else {
							// Otherwise, check for a default value. If we have one, use it. Otherwise, return an empty string.
							if ( isset( $fields[ $i ]['default'] ) ) {
								$response[ $i ] = $fields[ $i ]['default'];
							} else {
								$response[ $i ] = '';
							}
						}
					}
				}
			}
		}

		return $response;
	}
}

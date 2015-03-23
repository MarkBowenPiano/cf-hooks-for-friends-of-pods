<?php
/*
 Plugin Name: Hooks For CF
 */
/**
 * Customize Caldera Forms For Friends of Pods
 *
 * @package   @cf-fop
 * @author    Josh Pollock <Josh@Pods.io>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Pods Foundation LLC
 */
add_action( 'plugins_loaded', function() {
	new FOP_CF_Filters();
});

/**
 * Class FOP_CF_Filters
 */
class FOP_CF_Filters {

	/**
	 * ID of the become a friend form
	 *
	 * @var string
	 */
	protected $join_form_id = 'CF550c691ae51ea';

	/**
	 * ID of the rewards form
	 *
	 * @var string
	 */
	protected $rewards_form_id = 'CF5510805fd1735';

	/**
	 * IDs for the rewards fields
	 *
	 * @var array
	 */
	protected $rewards_field_ids = array(
		'rewards_1' => 'fld_8434792'
	);

	/**
	 * Constructor for this class.
	 */
	public function __construct() {
		if ( 'local.wordpress-trunk.dev' === $_SERVER[ 'HTTP_HOST' ] ) {
			$this->join_form_id = 'CF55107eb1ee6dc';
		}

		add_filter( 'caldera_forms_render_get_field_type-dropdown', array($this, 'dropdown_options' ), 10,2 );

	}

	/**
	 * Modify dropdown options.
	 *
	 * @uses 'caldera_forms_render_get_field_type-dropdown' filter
	 *
	 * @param array $field
	 * @param array $form
	 *
	 * @return array
	 */
	public function dropdown_options( $field, $form ) {

		if ( $form[ 'ID' ] !== $this->rewards_form_id && in_array( $field[ 'ID' ], $this->$rewards_field_ids ) ) {
			return $field;

		}

		$field['config']['option'] = array();

		$partners = $this->find_partners();
		if ( ! empty( $partners ) && is_array( $partners ) ) {
			$field['config']['option'] = $partners;
		}

		return $field;

	}

	/**
	 * Find the partners and the offered perks
	 *
	 * @return array
	 */
	protected function find_partners() {
		$pods = pods( 'partner', array(
				'limit' => -1,
				'cache_mode' => 'cache',
				'expires' => HOUR_IN_SECONDS
			)
		);

		$partners = array();

		if ( is_object( $pods ) && 0 < $pods->total() ) {
			while( $pods->fetch() ) {
				$name = $pods->display( 'post_title' );
				$perk = $pods->display( 'perk' );
				$label = sprintf(  '%1s : %2s', $name, $perk );

				$partners[] = array(
					'value' => sanitize_title_with_dashes( $name ),
					'label' =>  esc_html( $label )
				);

			}

		}

		return $partners;

	}


}

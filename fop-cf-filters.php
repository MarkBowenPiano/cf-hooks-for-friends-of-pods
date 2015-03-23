<?php
/*
 Plugin Name: Friends of Pods Hooks For Caldera Forms
 Author: Josh Pollock
 Version: 0.1.0
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
	protected $rewards_form_id = 'CF55109c8a71c73';

	/**
	 * IDs for the rewards fields
	 *
	 * NOTE: The key start as 1, not 0 to aid in calculating number of rewards choosers to show.
	 *
	 * @var array
	 */
	protected $rewards_field_ids = array(
		1 => 'fld_55109c8a71cb2',
		2 => 'fld_55109c8a71cf0',
		3 => 'fld_55109c8a71d2a',
		4 => 'fld_55109c8a71d64'

	);

	/**
	 * The ID of the field in the sign up form for the membership level.
	 *
	 * @var string
	 */
	protected $level_field_id = 'fld_550c691ae5318';

	/**
	 * The membership level for the current entry.
	 *
	 * @var string|void
	 */
	protected $level;

	/**
	 * ID of hidden field for level in the reward chooser from.
	 *
	 * @var string
	 */
	protected $hidden_level_field_id = 'fld_55109c8a71d9d';

	/**
	 * Constructor for this class.
	 */
	public function __construct() {
		//correct form IDs and fields IDs
		//@see https://github.com/Desertsnowman/Caldera-Forms/issues/89
		//@see https://github.com/Desertsnowman/Caldera-Forms/issues/90
		if ( 'local.wordpress-trunk.dev' === $_SERVER[ 'HTTP_HOST' ] ) {
			$this->join_form_id = 'CF55107eb1ee6dc';
			$this->rewards_form_id = 'CF5510805fd1735';
			$this->level_field_id = 'fld_55107eb1ee8be';
			$this->rewards_field_ids = array(
				1 => 'fld_8434792',
				2 => 'fld_7584920',
				3 => 'fld_7399833',
				4 => 'fld_9824495'

			);

		}



		add_filter( 'caldera_forms_render_get_field_type-dropdown', array($this, 'dropdown_options' ), 10,2 );

		$this->level = $this->find_level();

		add_filter( 'caldera_forms_submit_redirect_complete', array( $this, 'correct_redirect' ), 97, 2 );

		add_filter( 'caldera_forms_render_get_field_type-hidden', array( $this, 'hidden_level' ), 10, 2 );

	}

	/**
	 * Set level in hidden field
	 *
	 * @uses "caldera_forms_render_get_field_type-hidden" filter
	 *
	 * @param array $field
	 * @param array $form
	 *
	 * @return array
	 */
	public function hidden_level($field, $form  ) {
		if ( $form[ 'ID' ] !== $this->rewards_form_id ) {
			return $field;
		}

		$field[ 'config' ][ 'default' ] = $this->level;

		return $field;

	}

	/**
	 * Pull cf_id GET var off of URL on redirect, so it doesn't cause CF to load a
	 *
	 *
	 * @uses "caldera_forms_submit_redirect_complete" filter
	 *
	 * @param $url
	 * @param $form
	 *
	 * @return mixed
	 */
	public function correct_redirect( $url, $form ) {
		if ( $form[ 'ID' ] !== $this->join_form_id ) {
			return $url;
		}

		$url = str_replace( 'cf_id=', 'e=', $url );

		return $url;

	}


	/**
	 * Find level from previous submission.
	 *
	 * @return string|void
	 */
	protected function find_level() {
		$var = 'f_entry';
		if ( isset( $_GET[ $var ] ) ) {
			$id = strip_tags( $_GET[ $var ] );

			$entry = Caldera_Forms::get_submission_data( $this->join_form_id, $id );
			if ( is_array( $entry ) && isset( $entry[  $this->level_field_id ] ) ) {
				$level = $entry[  $this->level_field_id ];
				return $level;

			}

		}else{
			//@todo?
		}

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

		if ( $form[ 'ID' ] !== $this->rewards_form_id && ! in_array( $field[ 'ID' ], $this->rewards_field_ids ) ) {
			return $field;

		}

		if ( 'fop_green' === $this->level ) {
			return array();

		}

		if( 'fop_bronze' === $this->level || 'fop_silver'  === $this->level  ) {
			$key = array_search( $field[ 'ID'], $this->rewards_field_ids );
			if ( 'fop_bronze' === $this->level && $key > 1 ) {
				return array();

			}

			if ( 'fop_silver' === $this->level && $key > 2 ) {
				return array();

			}

		}

		$field['config']['option'] = array(
			array(
				'value' => '',
				'label' => '-- Choose A Reward --',
			),
			array(
				'value' => 'no-thanks',
				'label' => 'No Thanks'
			)

		);

		$perks = $this->find_perks();
		if ( ! empty( $perks ) && is_array( $perks ) ) {
			$field['config']['option'] = array_merge( $field['config']['option'], $perks );
		}

		return $field;

	}

	/**
	 * Find the partners and the offered perks
	 *
	 * @return array
	 */
	protected function find_perks() {
		$pods = pods( 'partner', array(
				'limit' => -1,
				'cache_mode' => 'cache',
				'expires' => HOUR_IN_SECONDS
			)
		);

		$perks = array();

		if ( is_object( $pods ) && 0 < $pods->total() ) {
			while( $pods->fetch() ) {
				$name = $pods->display( 'post_title' );
				$perk = $pods->display( 'perk' );
				$label = sprintf(  '%1s : %2s', $name, $perk );

				$perks[] = array(
					'value' => sanitize_title_with_dashes( $name ),
					'label' =>  esc_html( $label )
				);

				if ( 'fop_gold' === $this->level ) {
					$gold_perk = $pods->display( 'gold_bonus' );
					if ( $gold_perk ) {
						$label = sprintf(  'GOLD LEVEL PERK! %1s : %2s', $name, $perk );
						$perks[] = array(
							'value' => sanitize_title_with_dashes( $name ),
							'label' =>  esc_html( $label )
						);

					}

				}

			}

		}

		return $perks;

	}


}

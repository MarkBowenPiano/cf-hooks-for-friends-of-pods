<?php
/**
 * Main class for this plugin
 *
 * @package   cf-fop
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link      
 * @copyright 2015 Josh Pollock
 */
/**
 * Class FOP_CF_Filters
 */
class FOP_CF_Filters extends FOP_CF_IDs {


	/**
	 * The membership level for the current entry.
	 *
	 * @var string|void
	 */
	protected $level;



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

		add_action( 'template_redirect', array( $this, 'reward_nonce' ) );

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
		if ( $form[ 'ID' ] !== $this->rewards_form_id || $this->hidden_level_field_id !== $field[ 'ID' ] ) {
			return $field;
		}

		$field[ 'config' ][ 'default' ] = $this->level;

		return $field;

	}

	/**
	 * Pull cf_id GET var off of URL on redirect, so it does not cause CF to load same entry and adds a nonce.
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

		$nonce = wp_create_nonce( $this->nonce_action );

		$url = add_query_arg( 'nonce', $nonce, $url );

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

		if ( $form[ 'ID' ] !== $this->rewards_form_id || ! in_array( $field[ 'ID' ], $this->rewards_field_ids ) ) {

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
		$pods = pods( $this->partners_pod_name , array(
				'limit' => -1,
				'cache_mode' => 'cache',
				'expires' => HOUR_IN_SECONDS
			)
		);

		$perks = array();

		if ( is_object( $pods ) && 0 < $pods->total() ) {
			while( $pods->fetch() ) {
				$id = $pods->id();
				$name = $pods->display( 'post_title' );
				$perk = $pods->display( 'perk' );
				$label = sprintf(  '%1s : %2s', $name, $perk );


				$gold_perk = $pods->display( 'gold_bonus' );

				if ( ! empty( $perk ) ) {
					$perks[] = array(
						'value' => sanitize_title_with_dashes( $id ),
						'label' =>  esc_html( $label )
					);
				}

				if ( 'fop_gold' === $this->level && ! empty( $gold_perk ) ) {
					$label = sprintf(  'GOLD LEVEL PERK! %1s : %2s', $name, $perk );
					$perks[] = array(
						'value' => sanitize_title_with_dashes( $id . '_gold' ),
						'label' =>  esc_html( $label )
					);
				}

			}

		}


		return $perks;

	}

	/**
	 * On reward chooser, check nonce.
	 *
	 * @uses "template_redirect" filter
	 *
	 * @param $template
	 *
	 * @return string|void
	 */
	public function reward_nonce( $template ) {
		if ( is_page( 'select-rewards' ) ) {
			if ( isset( $_GET[ 'nonce' ] ) && wp_verify_nonce( strip_tags( $_GET['nonce' ] ), $this->nonce_action ) ) {
				return $template;
			}else{
				$thanks = get_permalink( 435 );
				if ( function_exists( 'pods_redirect' ) ) {
					pods_redirect( $thanks );
				}

				wp_redirect( $thanks );

			}

		}

		return $template;

	}


}

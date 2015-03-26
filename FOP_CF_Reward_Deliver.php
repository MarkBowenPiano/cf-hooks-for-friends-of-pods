<?php
/**
 * Handles reward delivery
 *
 * @package   cf-fop
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link      
 * @copyright 2015 Josh Pollock
 */

class FOP_CF_Reward_Deliver extends FOP_CF_IDs {
	/**
	 * Partners Pods object
	 *
	 * @var object|Pods
	 */
	protected $partners_pod;

	public function __construct() {
		add_action( 'caldera_forms_edit_end', array( $this, 'process_rewards' ), 51 );

	}

	/**
	 * Takes form input and creates rewards email.
	 *
	 * @uses "caldera_forms_edit_end" action
	 *
	 * @param array $form
	 *
	 * @return bool If mail was sent or not.
	 */
	public function process_rewards( $form ) {
		if ( $form[ 'ID' ] !== $this->rewards_form_id ) {
			return $form;

		}

		$codes = false;
		foreach( $this->rewards_field_ids as $field_id ) {
			if ( isset( $form[ 'fields' ][ $field_id ] ) && $form[ 'fields' ][ $field_id ] ) {
				$partner_id = $form[ 'fields' ][ $field_id ];

				$codes[] = array('@todo label' => $this->get_reward_as_string( $partner_id ) );
			}

		}

		$message = $this->make_message( $codes );

		return wp_mail( pods_v_sanatized( 'email' ), 'Your Friends of Pods Rewards', $message  );

	}

	/**
	 * Get code from partner ID
	 *
	 * @param string $partner_id Partner ID, with "_gold" prefixed if it gold level reward.
	 *
	 * @return mixed
	 */
	protected function get_reward_as_string( $partner_id ) {
		$gold = false;
		if ( strpos( $partner_id, '_gold' ) ) {
			$gold = true;
			$partner_id = str_replace( '_gold', '', $partner_id );
		}

		$pods = $this->get_partner( $partner_id );
		if ( $gold ) {
			$code = $pods->display( 'gold_perk' );
		}else{
			$code = $pods->display( 'regular_perk' );
		}

		return $code;
	}

	/**
	 * Fetch partners Pod to the selected partner.
	 *
	 * @param $id
	 *
	 * @return object|Pods
	 */
	protected function get_partner( $id ) {
		if ( is_null( $this->partners_pod ) ) {
			$this->partners_pod = pods( $this->partners_pod_name );
		}

		$this->partners_pod->reset();

		return $this->partners_pod->fetch( $id );

	}

	/**
	 * Create message to send.
	 *
	 * @param array $codes
	 *
	 * @return string
	 */
	protected function make_message( $codes ) {
		$code_message = false;
		if ( is_array( $codes ) ) {
			$code_message[] = '<ul>';
			foreach ( $codes as $label => $code ) {
				if ( filter_var( $code, FILTER_VALIDATE_URL ) ) {
					$code = sprintf( '<a href="%1s">Click here to claim reward</a>', esc_url( $code ) );
				}

				//@todo label?
				$code_message[] = sprintf( '<li>%1s : %2s</li>', $label, $code );
			}

		}

		$message[] = sprintf( '%1s - ', pods_v_sanatized( 'name' ) );
		$message[] = 'Thank you so much for your support!';
		if ( is_array( $code_message ) ) {
			$message[] = 'Here are the codes or links for the rewards from our partners that you have chosen:';
			$message[] = implode( "/n", $code_message );
		}

		$message[] = 'We will email you when we are ready to send out stickers and t-shirts.';

		$message[] = 'Thanks again for your support,';
		$message[] = 'The Pods Team';

		$message = implode( "/n", $message );

		return $message;

	}


}

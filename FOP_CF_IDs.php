<?php
/**
 * Holds field/ form IDs for the other two classes to use.
 *
 * @package   cf-fop
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link      
 * @copyright 2015 Josh Pollock
 */

abstract class FOP_CF_IDs {

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
	 * ID of hidden field for level in the reward chooser from.
	 *
	 * @var string
	 */
	protected $hidden_level_field_id = 'fld_55109c8a71d9d';

	/**
	 * Nonce action name
	 *
	 * @var string
	 */
	protected $nonce_action = 'fop-cf-rewards-redirect';

	/**
	 * ID of email address field in the rewards form
	 *
	 * @var string
	 */
	protected $email_address_field_id = 'fld_550c691ae53c3';

	/**
	 * Name of partners Pod
	 *
	 * @var string
	 */
	protected $partners_pod_name = 'partner';
}

<?php
/*
 Plugin Name: Friends of Pods Hooks For Caldera Forms
 Author: Josh Pollock
 Version: 0.1.0
 */
/**
 * Customize Caldera Forms For Friends of Pods
 *
 * @package   cf-fop
 * @author    Josh Pollock <Josh@Pods.io>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Pods Foundation LLC
 */

add_action( 'plugins_loaded', function() {
	if ( defined( 'PODS_VERSION' ) ) {
		include_once( dirname( __FILE__ ) . '/FOP_CF_IDs.php' );
		include_once( dirname( __FILE__ ) . '/FOP_CF_Filters.php' );
		include_once( dirname( __FILE__ ) . '/FOP_CF_Reward_Deliver.php' );
	}
	if ( defined( 'PODS_VERSION' ) ) {
		new FOP_CF_Filters();
	}
}, 1 );

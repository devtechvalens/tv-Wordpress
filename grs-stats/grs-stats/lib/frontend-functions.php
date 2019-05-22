<?php 
/*
* Frontend functions for frontend use for stats.
*/
if(!defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Enqueue scripts and styles.
 */
function grs_stats_load_scripts($hook) {
 
    wp_enqueue_script( 'grs_stats_common', plugins_url( 'grs-stats/assets/js/common.js'), array('jquery'), '1.0.0',true);
    wp_localize_script('grs_stats_common', 'grsScript', array(
	    'adminAjaxUrl' => admin_url('admin-ajax.php'),
	));
    
}
add_action('wp_enqueue_scripts', 'grs_stats_load_scripts');

/**
 * Ajax functions to increase the stat count.
 */
add_action( 'wp_ajax_nopriv_grs_send_stats', 'grs_send_stats_ajax' );
add_action( 'wp_ajax_grs_send_stats', 'grs_send_stats_ajax' );

function grs_send_stats_ajax()
{
	$grs_count_type = grs_get_count_type($_POST['ct']);
    $grs_stats_data = grs_get_stats_data();
    $grs_stats_data['dealer_id'] = $_POST['did'];
    $grs_stats_data['vehicle_id'] = $_POST['vid'];
    $grs_stats_data['count_type'] = $grs_count_type;
    grs_set_view_count($grs_stats_data);
	$resp = array('status' => 'success', 'msg' => "Count added successfully for ".$grs_count_type);        
    wp_send_json($resp) ;
    wp_die();
}
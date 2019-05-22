<?php 
/*
* Common functions for frontend and backend use for stats.
*/

if(!defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
* Function to get default view count types.
* @todo Need to convert it to db. Can be extended by backend.
* @return $count_type Array
*/
function grs_get_all_count_type()
{
	$data = array(
		0 => 'vehicle_vcnt',
		1 => 'dealer_web_vcnt',
		2 => 'carfax_vcnt',
		3 => 'lead_vcnt',
		4 => 'rsp_vcnt',
	);

	return $data;
}

/**
* Function to get default view count type by Key.
* @param $ctid INT
* @return $count_type String
*/
function grs_get_count_type($ctid)
{
	$data = grs_get_all_count_type();
	return $data[$ctid];
}

/**
* Get USER IP address 
* @return $ip IP Address of the User
*/
function grs_get_the_user_ip() {
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		//check ip from share internet
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		//to check ip is pass from proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	return apply_filters( 'wpb_get_ip', $ip );
}

/**
* Function to get default values of stats with 
* column in db as key and default value as null.
* @return $data Array
*/
function grs_get_stats_data()
{
	//Check for multisite if true than set blog id.
	$blog_id = '';
	if(is_multisite())
	{
		$blog_id = get_current_blog_id();
	}
	$ip = grs_get_the_user_ip();
	//$ct = time();
	$data = array(
		'blog_id' => $blog_id,
		'lead_id' => '',
		'dealer_id' => 0,
		'vehicle_id' => 0,
		'count_type' => 'vehicle_vcnt',
		//'view_dt' => $ct,
		'user_ip' => $ip,
	);
	return $data;
}

/**
 * Function to set view count.
 * @param $atts Array 
 * @return void
 */
function grs_set_view_count($atts)
{
	global $wpdb;
	$table_name = $wpdb->base_prefix."grs_stats_counts";

	//Set Vehicle data if count type is vehicle_id != 0;
	if($atts['vehicle_id'] != 0)
	{
		grs_add_inventory($atts);
	}	
	
	$wpdb->insert(
		$table_name,
		$atts
	);
	//echo $wpdb->last_query;
	//echo $wpdb->last_error;
	//echo $wpdb->insert_id;
}

/**
 * Add vehicle inventory details for deleted Inventory.
 * @param Array $atts
 * @return void
 */
function grs_add_inventory($atts)
{
	global $wpdb;
    $table_name = $wpdb->base_prefix."grs_gone_cars";
    
    //Check if vehicle entry is already exists.
    $sql = "SELECT * from ".$table_name." WHERE 1=1 ";
    //Check if Multisite
    $blog_id = '';
    if(is_multisite())
    {
        $blog_id = get_current_blog_id();
        $sql .= "AND blog_id = ".$blog_id;
    }

    //If Dealer is selected
    if($atts['vehicle_id'] != '')
    {
        $sql .= " AND vehicle_id = ".$atts['vehicle_id'];
    } 

    $rs = $wpdb->get_results($sql);
    
    if(count($rs)>0)
    {
    	foreach ($rs as $rsvl) {
    		if($rsvl->vehicle_data == '')
    		{
    			$vehicle_meta_data = get_post_meta($rsvl->vehicle_id,'decode_string',true);
    			unset($vehicle_meta_data['_vehicle_options']);
        		$vehicle_meta_data = serialize($vehicle_meta_data);
        		$wpdb->update( 
					$table_name, 
					array( 
						'vehicle_data' => $vehicle_meta_data,
					), 
					array( 'id' => $rsvl->id ), 
					array( 
						'%s',
					), 
					array( '%d' ) 
				);
    		}
    	}
    	return;
    }

    //If not exists than continue
    //Create the data array to add. 
    /** Note :: to store serialized array in mysql column, that column must be Long Text */
    $vehicle_meta_data = get_post_meta($atts['vehicle_id'],'decode_string',true);
    unset($vehicle_meta_data['_vehicle_options']);
    $vehicle_meta_data = serialize($vehicle_meta_data);
    $data = array(
    	'blog_id' => $blog_id,
    	'vehicle_id' => $atts['vehicle_id'],
    	'vehicle_title' => get_the_title($atts['vehicle_id']),
    	'vehicle_vin' => get_post_meta($atts['vehicle_id'],'_vin_value',true),
    	'vehicle_data' => $vehicle_meta_data
    );
    $wpdb->insert(
		$table_name,
		$data
	);

	return;
}

/**
 * Get Deleted Vehicle Data 
 * @param INT vehicle id
 * @return Array vehicle array
 */
function grs_get_gone_cars($vehicle_id)
{
	global $wpdb;
	$table_name = $wpdb->base_prefix."grs_gone_cars";
    
    //Check if vehicle entry is already exists.
    $sql = "SELECT * from ".$table_name." WHERE vehicle_id= ".$vehicle_id;

    //Check if Multisite
    $blog_id = '';
    if(is_multisite())
    {
        $blog_id = get_current_blog_id();
        $sql .= " AND blog_id = ".$blog_id;
    }
    $rs = $wpdb->get_row($sql);

    return $rs;
}
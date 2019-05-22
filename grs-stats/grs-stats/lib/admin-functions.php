<?php 
/*
* Admin functions for backend for stats.
*/
if(!defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Enqueue Admin scripts and styles.
 */
function grs_stats_admin_load_scripts($hook) {
 
 	// Load only on ?page=grs-stats/lib/admin-view.php
 	if($hook != 'grs-stats/lib/admin-view.php') {
            return;
    }
    wp_enqueue_style('grs_stats_admin_ui_css','https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css',false,"1.9.0",false);
    wp_enqueue_style( 'grs_stats_admin_css', plugins_url('grs-stats/assets/css/admin-stats.css') );
    wp_enqueue_script( 'grs_stats_chart', plugins_url( 'grs-stats/assets/js/Chart.min.js'), array('jquery'), '1.0.0',true);
    wp_enqueue_script( 'grs_stats_admin', plugins_url( 'grs-stats/assets/js/admin-stats.js'), array('jquery','jquery-ui-datepicker'), '1.0.0',true);
    wp_localize_script('grs_stats_admin', 'grsScript', array(
	    'adminAjaxUrl' => admin_url('admin-ajax.php'),
	));
    
}
add_action('admin_enqueue_scripts', 'grs_stats_admin_load_scripts');

/**
 * Register a reports menu page.
 */
function grs_register_stats_menu_page() {
    add_menu_page(
        __( 'Reports', 'grs-stats' ),
        'Reports',
        'manage_options',
        'grs-stats/lib/admin-view.php',
        '',
        'dashicons-analytics',
        61
    );
}
add_action( 'admin_menu', 'grs_register_stats_menu_page' );

/**
 * Create All Dealers Dropdown for Getting Stats
 * @return $html String (HTML Select Box including Dealer Data)
 */
function grs_stats_dealers_dd()
{
	$dl_query = array(
                'post_type' => 'dealers',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => 'dealers_type',
                        'value' => 'premium'
                    )
                )
            );
    $dlrs = new WP_Query($dl_query);
    $html = '';
    if($dlrs->have_posts()):
    	$html = '<select name="stats_dealers" id="stats_dealers">';
    	$html .= '<option value="">Please Select Dealer</option>';
        $dlar = array();
        while($dlrs->have_posts()):
            $dlrs->the_post();
            $dlrs_ps = $dlrs->post;
            $dlar[$dlrs_ps->ID] = $dlrs_ps->post_title;              
        endwhile;
        natcasesort($dlar);
        foreach ($dlar as $dl_key => $dl_tle) {
           $html .= '<option value="'.$dl_key.'">'.$dl_tle.'</option>'; 
        }
        $html .= '</select>';
    else:  
    $html = "Sorry! No available Dealers.";   
    endif;
    wp_reset_postdata();
    return $html;
}

/**
 * Ajax function to load the stats.
 */
add_action( 'wp_ajax_grs_get_stats', 'grs_get_stats_ajax' );

function grs_get_stats_ajax()
{
    //Get Chart Data for loading chart for all counts
    $rs = grs_get_stats_data_sql($_POST);
    $totalvcnt = 0;
    $chartdt = array();
    if(count($rs)>0)
    {
        //if(count($rs)<10)
        //{
            foreach ($rs as $rsvl) {
                $totalvcnt = $totalvcnt+$rsvl->vhcnt;
                $chartdt['labels'][] = date('M d', strtotime($rsvl->vdt));
                $chartdt['data'][] = $rsvl->vhcnt;
            }
        //}
    }

    //Get Pie Chart Data
    $count_types = grs_get_all_count_type(); 
    foreach ($count_types as $ct) {
        //Get Count 
        $rs = grs_get_stats_data_sql_cnt($_POST,$ct);
        //Set Title 
        switch ($ct) {
            case 'vehicle_vcnt':
                $tle = "Vehicle View";
                break;
            case 'dealer_web_vcnt':
                $tle = "Dealer Website Click";
                break;
            case 'carfax_vcnt':
                $tle = "Carfax Report Click";
                break;
            case 'lead_vcnt':
                $tle = "Leads Submission Count";
                break;        
            default:
                $tle = "Request Sale Price Submission Count";
                break;
        }
        $chartdt['pie_color'][] = rand_color();
        $chartdt['pie_labels'][] = $tle;
        $chartdt['pie_data'][] = $rs;
    }    
    $chartdt['totalvcnt'] = $totalvcnt;
    //Get Html of the Stats
    $html = grs_get_stats_html($_POST);
    $resp = array('status' => 'success', 'msg' => "Load the Stats",'opt' => $html,'chartdt' => $chartdt);        
    wp_send_json($resp) ;
    wp_die();
}

/**
 * Ajax function to load the stats.
 */
add_action( 'wp_ajax_grs_get_stats_ct', 'grs_get_stats_ct_ajax' );

function grs_get_stats_ct_ajax()
{
    $totalvcnt = 0;
    if($_POST['count_type'] == 'vehicle_vcnt')
    {
        //Get Chart Data for loading chart for all counts
        $rs = grs_get_stats_data_sql($_POST);        
        $chartdt = array();
        if(count($rs)>0)
        {
            //if(count($rs)<10)
            //{
                foreach ($rs as $rsvl) {
                    $totalvcnt = $totalvcnt+$rsvl->vhcnt;
                    $chartdt['labels'][] = date('M d', strtotime($rsvl->vdt));
                    $chartdt['data'][] = $rsvl->vhcnt;
                }
            //}
        }
    }
    $chartdt['totalvcnt'] = $totalvcnt;
    //Get Html of the Stats
    $html = grs_get_stats_ct_html($_POST);
    $resp = array('status' => 'success', 'msg' => "Load the Stats",'opt' => $html,'chartdt' => $chartdt);        
    wp_send_json($resp) ;
    wp_die();
}

/**
 * Create the HTML of the stats for given values.
 * @param $stats_vals Array 
 * @return $html String(HTML Output)
 */
function grs_get_stats_html($stats_vals)
{
    $html = '';
    //Call the Query According to Filters
    //$rs = grs_get_stats_data_sql_cnt($stats_vals);
    $count_types = grs_get_all_count_type();
    $dltxt = 'For All Dealers';
    if($stats_vals['did'] != '')
    {
        $dltle = get_the_title($stats_vals['did']);
        $dltxt = 'For '.$dltle;
    }
    $html .= '<div class="export_head">'.$dltxt.'</div>';
    $html .= '<div class="totalvcnt"></div>';
    $html .= '<div id="chart_content" style="max-width: 770px; max-height:200px; float:left;">
            <canvas id="loadStatsChart" width="770" height="200"></canvas>
        </div>';
    $html .= '<div id="chart_content3" style="max-width: 300px; max-height:300px; float:left;">
            <canvas id="loadStatsChart3" width="300" height="300"></canvas>
        </div>';    
    $html .= '<table class="widefat fixed striped">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Event Type</th><th>Count</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    foreach ($count_types as $ct) {
        //Get Count 
        $rs = grs_get_stats_data_sql_cnt($stats_vals,$ct);
        //Set Title 
        switch ($ct) {
            case 'vehicle_vcnt':
                $tle = "Vehicle View";
                break;
            case 'dealer_web_vcnt':
                $tle = "Dealer Website Click";
                break;
            case 'carfax_vcnt':
                $tle = "Carfax Report Click";
                break;
            case 'lead_vcnt':
                $tle = "Leads Submission Count";
                break;        
            default:
                $tle = "Request Sale Price Submission Count";
                break;
        }
        $html .= '<tr>';
        $html .= '<td>'.$tle.'</td>';
        $html .= '<td>'.$rs.'</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    $html .= '</table>';

    $html .= '<div class="drill_btns">';
    $html .= '<button id="ch_vh_btn" onclick="grsLoadStatsByCt(\'vehicle_vcnt\');">Vehicle View Details</button>';
    $html .= '<button id="ch_lead_btn" onclick="grsLoadStatsByCt(\'lead_vcnt\');">Lead Submission Details</button>';
    $html .= '</div>';
    return $html;
}

/**
 * Create the HTML of the stats for given values.
 * @param $stats_vals Array 
 * @return $html String(HTML Output)
 */
function grs_get_stats_ct_html($stats_vals)
{
    $html = '';
    $rs = grs_get_stats_ct_data_sql($stats_vals);
    if(count($rs)>0)
    {
        if($stats_vals['count_type'] == 'vehicle_vcnt'):
            $html .= '<div id="chart_content2" style="max-width: 770px; max-height:200px; margin: 0px auto;">
                        <canvas id="loadStatsChart2" width="770" height="200"></canvas>
                    </div>';
            $html .= '<table class="widefat fixed striped">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Dealer Name</th><th>Vehicle Title</th><th>Vehicle Vin</th><th>Count</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>'; 
            foreach ($rs as $vl) {
                //Get Dealer Title
                $dtle = get_the_title($vl->dealer_id);
                //Get vehicle Title
                $tle = get_the_title($vl->vehicle_id);
                //Get vehicle vin
                $vin = get_post_meta($vl->vehicle_id,'_vin_value',true);
                if(empty($tle))
                {
                    $tle = "Vehicle Deleted";
                    $vin = "Vehicle Deleted";
                    $gc = grs_get_gone_cars($vl->vehicle_id);
                    if(null !== $gc)
                    {
                        $tle = $gc->vehicle_title;
                        $vin = $gc->vehicle_vin;
                    }                    
                }
                //Get vehicle vin
                //$vin = get_post_meta($vl->vehicle_id,'_vin_value',true);
                $html .= '<tr>';
                $html .= '<td>'.$dtle.'</td>';
                $html .= '<td>'.$tle.'</td>';
                $html .= '<td>'.$vin.'</td>';
                $html .= '<td>'.$vl->vhcnt.'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';
        else:
            $html .= '<table class="widefat fixed striped">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Lead Type</th><th>Dealer Name</th><th>Vehicle Title</th><th>Date</th><th>Go To Lead</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>'; 
            foreach ($rs as $vl) {
                $ltype = '';
                if($vl->count_type == 'lead_vcnt')
                {
                    $ltype = "Leads Submission";
                }
                if($vl->count_type == 'rsp_vcnt')
                {
                    $ltype = "Request Sale Price Submission";
                }
                //Get Dealer Title
                $dtle = get_the_title($vl->dealer_id);
                //Get vehicle Title
                $tle = get_the_title($vl->vehicle_id);
                if(empty($tle))
                {
                    $tle = "Vehicle Deleted";
                    $gc = grs_get_gone_cars($vl->vehicle_id);
                    if(null !== $gc)
                    {
                        $tle = $gc->vehicle_title;
                    }
                }
                //Get Date 
                $lddte = date('F d, Y, H:i:s a',strtotime($vl->view_dt));
                $lead_url = get_home_url().'/wp-admin/post.php?post='.$vl->lead_id.'&action=edit';
                $html .= '<tr>';
                $html .= '<td>'.$ltype.'</td>';
                $html .= '<td>'.$dtle.'</td>';
                $html .= '<td>'.$tle.'</td>';
                $html .= '<td>'.$lddte.'</td>';
                $html .= '<td><a href = "'.$lead_url.'" target="_blank">View</a></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';
        endif;
    }else
    {
        $html = "Sorry, No Data is available.";
    }
    return $html;
}

/**
 * Create the HTML of the stats for given values for pdf
 * @param $stats_vals Array 
 * @return $html String(HTML Output)
 */
function grs_get_stats_ct_html_2_pdf($stats_vals)
{
    $html = '';
    $rs = grs_get_stats_ct_data_sql($stats_vals);
    if(count($rs)>0)
    {
        if($stats_vals['count_type'] == 'vehicle_vcnt'):
            $html .= '<table border="1">';
            $html .= '<tr>';
            $html .= '<td width="240" height="50" align="center">Dealer Name</td><td width="240" height="50" align="center">Vehicle Title</td><td width="200" height="50" align="center">Vehicle Vin</td><td width="80" height="50" align="center">Count</td>';
            $html .= '</tr>';
            foreach ($rs as $vl) {
                //Get Dealer Title
                $dtle = get_the_title($vl->dealer_id);
                $dtle = html_entity_decode($dtle);
                $dtle = str_replace("’","'", $dtle);
                //Get vehicle Title
                $tle = get_the_title($vl->vehicle_id);
                //Get vehicle vin
                $vin = get_post_meta($vl->vehicle_id,'_vin_value',true);
                if(empty($tle))
                {
                    $tle = "Vehicle Deleted";
                    $vin = "Vehicle Deleted";
                    $gc = grs_get_gone_cars($vl->vehicle_id);
                    if(null !== $gc)
                    {
                        $tle = $gc->vehicle_title;
                        $vin = $gc->vehicle_vin;
                    }
                }
                if($tle != 'Vehicle Deleted')
                {
                    list($vtle, $vadr) = explode("in",$tle);
                    $vtle = rtrim($vtle);
                }else
                {
                    $vtle = $tle;
                }
                $html .= '<tr>';
                $html .= '<td width="240" height="40">'.$dtle.'</td>';
                $html .= '<td width="240" height="40">'.$vtle.'</td>';
                $html .= '<td width="200" height="40">'.$vin.'</td>';
                $html .= '<td width="80" height="40">'.$vl->vhcnt.'</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        else:
            $html .= '<table border="1">';
            $html .= '<tr>';
            $html .= '<td width="200" height="50" align="center">Lead Type</td><td width="200" height="50" align="center">Dealer Name</td><td width="200" height="50" align="center">Vehicle Title</td><td width="160" height="50" align="center">Date</td>';
            $html .= '</tr>';
            foreach ($rs as $vl) {
                $ltype = '';
                if($vl->count_type == 'lead_vcnt')
                {
                    $ltype = "Leads Submission";
                }
                if($vl->count_type == 'rsp_vcnt')
                {
                    $ltype = "Request Sale Price Submission";
                }
                //Get Dealer Title
                $dtle = get_the_title($vl->dealer_id);
                $dtle = html_entity_decode($dtle);
                $dtle = str_replace("’","'", $dtle);
                //Get Date 
                $lddte = date('F d, Y',strtotime($vl->view_dt));
                //Get vehicle Title
                $tle = get_the_title($vl->vehicle_id);
                if(empty($tle))
                {
                    $tle = "Vehicle Deleted";
                    $gc = grs_get_gone_cars($vl->vehicle_id);
                    if(null !== $gc)
                    {
                        $tle = $gc->vehicle_title;
                    }
                }
                if($tle != 'Vehicle Deleted')
                {
                    list($vtle, $vadr) = explode("in",$tle);
                    $vtle = rtrim($vtle);
                }else
                {
                    $vtle = $tle;
                }
                $lead_url = get_home_url().'/wp-admin/post.php?post='.$vl->lead_id.'&action=edit';
                $html .= '<tr>';
                $html .= '<td width="200" height="40">'.$ltype.'</td>';
                $html .= '<td width="200" height="40">'.$dtle.'</td>';
                $html .= '<td width="200" height="40">'.$vtle.'</td>';
                $html .= '<td width="160" height="40">'.$lddte.'</td>';
                //$html .= '<td width="100" height="40"><a href = "'.$lead_url.'" target="_blank">Go!!</a></td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        endif;
    }else
    {
        $html = "Sorry, No Data is available.";
    }
    return $html;
}

/**
 * Generate SQL and Returns the Stats Data
 * @param $stats_vals Array
 * @return $data Array
 */
function grs_get_stats_data_sql($stats_vals)
{
    global $wpdb;
    $table_name = $wpdb->base_prefix."grs_stats_counts";
    $sql = "SELECT DATE(view_dt) as vdt, count(*) as vhcnt from ".$table_name." WHERE 1=1 ";
    //Check if Multisite
    $blog_id = '';
    if(is_multisite())
    {
        $blog_id = get_current_blog_id();
        $sql .= "AND blog_id = ".$blog_id;
    }

    //If Dealer is selected
    if($stats_vals['did'] != '')
    {
        $sql .= " AND dealer_id = ".$stats_vals['did'];
    } 
    
    //Check for the Time Frame.
    //If This Month
    if($stats_vals['stats_filter'] == 'this_month')
    {
        $sql .= " AND MONTH(view_dt) = MONTH(CURRENT_DATE()) AND YEAR(view_dt) = YEAR(CURRENT_DATE())";
    }

    //If Last Month
    if($stats_vals['stats_filter'] == 'last_month')
    {
        //get Last Month From PHP 
        $lowerdt = date('Y-m-d', strtotime('first day of last month'));
        $upperdt = date('Y-m-d', strtotime('last day of last month'));
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
    }

    //If Last Year
    if($stats_vals['stats_filter'] == 'last_year')
    {
        //get Last Year From PHP 
        $lowerdt = date('Y-m-d', strtotime('first day of january last year'));
        $upperdt = date('Y-m-d', strtotime('last day of december last year'));
        //Last Year
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
        //Current Year
        //$sql .= " AND YEAR(view_dt) = YEAR(CURRENT_DATE())";
    }

    //If Year To Date
    if($stats_vals['stats_filter'] == 'year_to_date')
    {
        $yt = strtotime('first day of january '.$stats_vals['dt_from']);
        $ut = strtotime($stats_vals['dt_to']);
        $lowerdt = date('Y-m-d', $yt);
        $upperdt = date('Y-m-d', $ut);
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
    }

    //If Date To Date
    if($stats_vals['stats_filter'] == 'date_to_date')
    {
        $yt = strtotime($stats_vals['dt_from']);
        $ut = strtotime($stats_vals['dt_to']);
        $lowerdt = date('Y-m-d', $yt);
        $upperdt = date('Y-m-d', $ut);
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
    }
    if($stats_vals['count_type'] == 'vehicle_vcnt'):
        $sql .= " AND count_type = '".$stats_vals['count_type']."'";    
    endif;
    $sql .= ' GROUP BY DATE(view_dt)';
    $rs = $wpdb->get_results($sql);
    return $rs;
}

/**
 * Generate SQL and Returns the Stats Data Count
 * @param $stats_vals Array
 * @return $data Array
 */
function grs_get_stats_data_sql_cnt($stats_vals,$count_type)
{
    global $wpdb;
    $table_name = $wpdb->base_prefix."grs_stats_counts";
    $sql = "SELECT * from ".$table_name." WHERE 1=1 ";
    //Check if Multisite
    $blog_id = '';
    if(is_multisite())
    {
        $blog_id = get_current_blog_id();
        $sql .= "AND blog_id = ".$blog_id;
    }

    //If Dealer is selected
    if($stats_vals['did'] != '')
    {
        $sql .= " AND dealer_id = ".$stats_vals['did'];
    } 
    
    //Check for the Time Frame.
    //If This Month
    if($stats_vals['stats_filter'] == 'this_month')
    {
        $sql .= " AND MONTH(view_dt) = MONTH(CURRENT_DATE()) AND YEAR(view_dt) = YEAR(CURRENT_DATE())";
    }

    //If Last Month
    if($stats_vals['stats_filter'] == 'last_month')
    {
        //get Last Month From PHP 
        $lowerdt = date('Y-m-d', strtotime('first day of last month'));
        $upperdt = date('Y-m-d', strtotime('last day of last month'));
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
    }

    //If Last Year
    if($stats_vals['stats_filter'] == 'last_year')
    {
        //get Last Year From PHP 
        $lowerdt = date('Y-m-d', strtotime('first day of january last year'));
        $upperdt = date('Y-m-d', strtotime('last day of december last year'));
        //Last Year
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
        //Current Year
        //$sql .= " AND YEAR(view_dt) = YEAR(CURRENT_DATE())";
    }

    //If Year To Date
    if($stats_vals['stats_filter'] == 'year_to_date')
    {
        $yt = strtotime('first day of january '.$stats_vals['dt_from']);
        $ut = strtotime($stats_vals['dt_to']);
        $lowerdt = date('Y-m-d', $yt);
        $upperdt = date('Y-m-d', $ut);
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
    }

    //If Date To Date
    if($stats_vals['stats_filter'] == 'date_to_date')
    {
        $yt = strtotime($stats_vals['dt_from']);
        $ut = strtotime($stats_vals['dt_to']);
        $lowerdt = date('Y-m-d', $yt);
        $upperdt = date('Y-m-d', $ut);
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
    }
    $sql .= " AND count_type = '".$count_type."'";
    //$sql .= 'GROUP BY count_type';
    $rs = $wpdb->get_results($sql);
    return $wpdb->num_rows;
}

/**
 * Generate SQL and Returns the Stats Data Count
 * @param $stats_vals Array
 * @return $data Array
 */
function grs_get_stats_ct_data_sql($stats_vals)
{
    global $wpdb;
    $table_name = $wpdb->base_prefix."grs_stats_counts";
    $count_type = $stats_vals['count_type'];
    $vhsql = '';
    if($count_type == "vehicle_vcnt")
    {
        $vhsql = ',count(*) as vhcnt';
    }
    $sql = "SELECT * ".$vhsql." from ".$table_name." WHERE 1=1 ";
    //Check if Multisite
    $blog_id = '';
    if(is_multisite())
    {
        $blog_id = get_current_blog_id();
        $sql .= "AND blog_id = ".$blog_id;
    }

    //If Dealer is selected
    if($stats_vals['did'] != '')
    {
        $sql .= " AND dealer_id = ".$stats_vals['did'];
    } 
    
    //Check for the Time Frame.
    //If This Month
    if($stats_vals['stats_filter'] == 'this_month')
    {
        $sql .= " AND MONTH(view_dt) = MONTH(CURRENT_DATE()) AND YEAR(view_dt) = YEAR(CURRENT_DATE())";
    }

    //If Last Month
    if($stats_vals['stats_filter'] == 'last_month')
    {
        //get Last Month From PHP 
        $lowerdt = date('Y-m-d', strtotime('first day of last month'));
        $upperdt = date('Y-m-d', strtotime('last day of last month'));
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
    }

    //If Last Year
    if($stats_vals['stats_filter'] == 'last_year')
    {
        //get Last Year From PHP 
        $lowerdt = date('Y-m-d', strtotime('first day of january last year'));
        $upperdt = date('Y-m-d', strtotime('last day of december last year'));
        //Last Year
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
        //Current Year
        //$sql .= " AND YEAR(view_dt) = YEAR(CURRENT_DATE())";
    }
    //If Year To Date
    if($stats_vals['stats_filter'] == 'year_to_date')
    {
        $yt = strtotime('first day of january '.$stats_vals['dt_from']);
        $ut = strtotime($stats_vals['dt_to']);
        $lowerdt = date('Y-m-d', $yt);
        $upperdt = date('Y-m-d', $ut);
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
    }

    //If Date To Date
    if($stats_vals['stats_filter'] == 'date_to_date')
    {
        $yt = strtotime($stats_vals['dt_from']);
        $ut = strtotime($stats_vals['dt_to']);
        $lowerdt = date('Y-m-d', $yt);
        $upperdt = date('Y-m-d', $ut);
        $sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
    }
    if($count_type == "vehicle_vcnt")
    {
        $sql .= " AND count_type = '".$count_type."'";
        $sql .= ' GROUP BY vehicle_id ORDER BY vhcnt DESC';
    }elseif($count_type == "lead_vcnt"){
        $sql .= " AND count_type = '".$count_type."'";
        $sql .= " ORDER BY view_dt DESC";
    }else
    {
        $sql .= " AND (count_type = '".$count_type."' OR count_type = 'rsp_vcnt')";
    }
    //$sql .= " ORDER BY view_dt DESC";
    //$sql .= " AND count_type = '".$count_type."'";
    //$sql .= 'GROUP BY count_type';
    //echo $sql;
    $rs = $wpdb->get_results($sql);
    //print_r($rs);
    //exit();
    return $rs;
}

/**
 * Ajax function to load the sub filters for date ranges.
 */
add_action( 'wp_ajax_grs_get_sub_filters', 'grs_get_sub_filters' );

function grs_get_sub_filters()
{
    extract($_POST);
    $html = '';
    if($sub_filter == 'year_to_date')
    {
        $html = grs_get_year_to_date_html();
    }
    if($sub_filter == 'date_to_date')
    {
        $html = grs_get_date_to_date_html();
    }
    $resp = array('status' => 'success', 'msg' => "Load the Sub Filters",'opt' => $html);        
    wp_send_json($resp) ;
    wp_die();
}

function grs_get_year_to_date_html()
{
    $start_year = '2010';
    $html = '<select name="fromDt" id="fromDt">';
    $html .= '<option value="">Select Year</option>';
    for ($i=2010; $i <= date('Y'); $i++) { 
        $html .= '<option value="'.$i.'">'.$i.'</option>';
    }
    $html .= '</select>';
    $html .= ' to ';
    $html .= '<input type="text" id="toDt" name="toDt" />' ;
    return $html;
}

function grs_get_date_to_date_html()
{
    $html .= '<input type="text" id="fromDt2" name="fromDt2" />';
    $html .= ' to ';
    $html .= '<input type="text" id="toDt2" name="toDt2" />' ;
    return $html;
}

/**
 * Ajax function to export the stats to PDF.
 */
add_action( 'wp_ajax_grs_export_stats', 'grs_export_stats_ajax' );

function grs_export_stats_ajax()
{
    //Get Html of the Stats
    $html = grs_export_stats_html($_POST);
    $resp = array('status' => 'success', 'msg' => "Load the Stats",'opt' => $html);        
    wp_send_json($resp) ;
    wp_die();
}


/**
 * Create the PDF of the stats for given values.
 * @param $stats_vals Array 
 * @return $html String(HTML Output)
 */
function grs_export_stats_html($stats_vals)
{
    require_once('fpdf/grspdf.php');
    $upload_dir = wp_upload_dir();
    //Convert canvas to image than upload
    $data = $stats_vals['photo'];
    list($type, $data) = explode(';', $data);
    list(, $data)      = explode(',', $data);
    $data = base64_decode($data);
    file_put_contents($upload_dir['basedir'].'/stats1.png', $data);
    $statsimg1 = $upload_dir['baseurl'].'/stats1.png';

    //Convert canvas to image than upload
    if($stats_vals['photo2'] != '')
    {
        $data2 = $stats_vals['photo2'];
        list($type2, $data2) = explode(';', $data2);
        list(, $data2)      = explode(',', $data2);
        $data2 = base64_decode($data2);
        file_put_contents($upload_dir['basedir'].'/stats2.png', $data2);
        $statsimg2 = $upload_dir['baseurl'].'/stats2.png';
    }        

    $logo = get_stylesheet_directory_uri().'/images/pdflogo.png';
    $html = '';
    if($stats_vals['did'] == '')
    {
        $dltxt = "Dealer : All";
    }else
    {
        $dlname = get_the_title($stats_vals['did']);
        $dlname = html_entity_decode($dlname);
        $dlname = str_replace("’","'", $dlname);
        //$dlname = html_entity_decode($dlname);
        //$dlname = mb_convert_encoding($dlname, "HTML-ENTITIES", 'UTF-8');
        //$dlname = str_replace("’","'", $dlname);
        //$dlname = html_entity_decode($dlname);
        $dltxt = "Dealer : ".$dlname;
    }

    //If This Month
    if($stats_vals['stats_filter'] == 'this_month')
    {
        $dt_txt = "Period : ".date('F, Y');
    }

    //If Last Month
    if($stats_vals['stats_filter'] == 'last_month')
    {
        //get Last Month From PHP 
        $lowerdt = date('F d, Y', strtotime('first day of last month'));
        $upperdt = date('F d, Y', strtotime('last day of last month'));
        $dt_txt = "Period : From - ".$lowerdt." To - ".$upperdt;
    }

    //If Last Year
    if($stats_vals['stats_filter'] == 'last_year')
    {
        //get Last Year From PHP 
        $lowerdt = date('F d, Y', strtotime('first day of january last year'));
        $upperdt = date('F d, Y', strtotime('last day of december last year'));
        //Last Year
        //$sql .= " AND view_dt BETWEEN '".$lowerdt."' AND '".$upperdt."'";
        //Current Year
        $dt_txt = "Period : Year - ".date('Y',strtotime('first day of january last year'));
    }
    //If Year To Date
    if($stats_vals['stats_filter'] == 'year_to_date')
    {
        $yt = strtotime('first day of january '.$stats_vals['dt_from']);
        $ut = strtotime($stats_vals['dt_to']);
        $lowerdt = date('F d, Y', $yt);
        $upperdt = date('F d, Y', $ut);
        $dt_txt = "Period : From - ".$lowerdt." To - ".$upperdt;
    }

    //If Date To Date
    if($stats_vals['stats_filter'] == 'date_to_date')
    {
        $yt = strtotime($stats_vals['dt_from']);
        $ut = strtotime($stats_vals['dt_to']);
        $lowerdt = date('F d, Y', $yt);
        $upperdt = date('F d, Y', $ut);
        $dt_txt = "Period : From - ".$lowerdt." To - ".$upperdt;
    }

    //Default Data
    $dfhtml = '';
    $dfhtml .= '<table border="1">';
    $dfhtml .= '<tr>';
    $dfhtml .= '<td width="380" height="50" align="center">Event Type</td><td width="380" height="50" align="center">Count</td>';
    $dfhtml .= '</tr>';
    $count_types = grs_get_all_count_type();
    $header = array('Event Type', 'Count');
    $data = array();
    foreach ($count_types as $ct) {
        //Get Count 
        $rs = grs_get_stats_data_sql_cnt($stats_vals,$ct);
        //Set Title 
        switch ($ct) {
            case 'vehicle_vcnt':
                $tle = "Vehicle View";
                break;
            case 'dealer_web_vcnt':
                $tle = "Dealer Website Click";
                break;
            case 'carfax_vcnt':
                $tle = "Carfax Report Click";
                break;
            case 'lead_vcnt':
                $tle = "Leads Submission Count";
                break;        
            default:
                $tle = "Request Sale Price Submission Count";
                break;
        }
        $data[][0] = $tle;
        $data[][1] = $rs;
        $dfhtml .= '<tr>';
        $dfhtml .= '<td width="380" height="40">'.$tle.'</td>';
        $dfhtml .= '<td width="380" height="40">'.$rs.'</td>';
        $dfhtml .= '</tr>';
    }
    $dfhtml .= '</table>';
    // Vehicle Data
    $stats_vals['count_type'] = 'vehicle_vcnt';
    $vhhtml = grs_get_stats_ct_html_2_pdf($stats_vals);
    
    // Leads Data
    $stats_vals['count_type'] = 'lead_vcnt';
    $ldhtml = grs_get_stats_ct_html_2_pdf($stats_vals);
    
    $pdf = new GRSPDF();
    $pdf->SetFont('Arial','',14);
    $pdf->AddPage();
    $pdf->image($logo,50,6,100);
    $pdf->Ln(20);
    $pdf->Cell(0,8,$dltxt,1,1,'C');
    $pdf->Ln();
    $pdf->Cell(0,8,$dt_txt,1,1,'C');
    $pdf->Ln(10);
    //$pdf->ImprovedTable($header,$data);
    $pdf->image($statsimg1,10,60,190);
    $pdf->Ln(60);
    $pdf->WriteHtml($dfhtml);

    $pdf->Ln(10);
    $pdf->SetFont('Arial','',14);
    $pdf->Cell(0,8,"Vehicle View Details",1,1,'C');
    $pdf->Ln(10);
    if($stats_vals['photo2'] != '')
    {
       $pdf->image($statsimg2,10,190,190);
        $pdf->Ln(50); 
    }    
    $pdf->SetFont('Arial','',10);
    $pdf->WriteHtml($vhhtml);
    
    $pdf->Ln(10);
    $pdf->SetFont('Arial','',14);
    $pdf->Cell(0,8,"Lead Submission Details",1,1,'C');
    $pdf->Ln();
    $pdf->SetFont('Arial','',10);
    $pdf->WriteHtml($ldhtml);
    
    
    $opt = $pdf->Output($upload_dir['basedir'].'/stats.pdf','F');
    return $upload_dir['baseurl'].'/stats.pdf';
}

function rand_color() {
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}
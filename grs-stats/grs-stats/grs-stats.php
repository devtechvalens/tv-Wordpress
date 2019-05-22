<?php
/*
 * Plugin Name: GRS Stats
 * Description: This plugin adds a statistics dashboard for dealers. 
 * Version:     1.0.0
 * Author:      
 * Author URI:  
 * Requires at least: 3.5
 * Tested up to: 5.0.1
 * Text Domain: grs-stats
 * Domain Path: /languages/
 * License:     GPL v2 or later
 */

/**
 * GRS Stats
 *
 * LICENSE
 * This file is part of GRS Stats.
 *
 * GRS Stats is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package    GRS Stats
 * @author     
 * @copyright  Copyright 2018 
 * @license    http://www.gnu.org/licenses/gpl.txt GPL 2.0
 * @link       
 * @since      1.0
 */

if(!defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// GRS Stats DB Version For future upgrades in DB Schema.
global $grs_stats_db_version;
$grs_stats_db_version = '1.0';

register_activation_hook( __FILE__, 'grs_setup_stats' );

/**
 *  Set up the default data and create the tables
 */
function grs_setup_stats() {
    global $wpdb;
    global $grs_stats_db_version;

    $table_name = $wpdb->prefix.'grs_stats_counts';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name(
			id int(11) NOT NULL AUTO_INCREMENT,
			blog_id int(11) DEFAULT NULL,
			lead_id int(11) DEFAULT NULL,
			dealer_id int(11) NOT NULL,
			vehicle_id int(11) NOT NULL,
			count_type varchar(100) NOT NULL,
			view_dt varchar(255) NOT NULL,
			user_ip varchar(100) NOT NULL,
			PRIMARY KEY (id)
			) $charset_collate;";

	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	add_option('grs_stats_db_version',$grs_stats_db_version);
}

// Include the common functions
require_once dirname(__FILE__).'/lib/common-functions.php';

// Include the admin functions
require_once dirname(__FILE__).'/lib/admin-functions.php';

// Include the frontend functions
require_once dirname(__FILE__).'/lib/frontend-functions.php';
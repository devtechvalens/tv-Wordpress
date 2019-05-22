<?php 
if(!defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="wrap">
	<h1 class="wp-heading-inline">Reports</h1>
	<hr class="wp-header-end">
	<div class="welcome_reports">
		<div class="dealer_select">
			<?php echo grs_stats_dealers_dd(); ?>
		</div>
		<div class="stats_filters">
			<select id="tf_filters" name="tf_filters">
				<option value="this_month">This Month</option>
				<option value="last_month">Last Month</option>
				<option value="last_year">Last Year</option>
				<option value="year_to_date">Year to Date</option>
				<option value="date_to_date">Select Dates</option>
			</select>
			<div id="tf_sub_filters"></div>
			<input type="hidden" name="pagecnt" id="pagecnt" value="1">
			<button type="button" onclick="grsLoadStats();">Apply</button>
		</div>
		<div class="stats_export">
			<button type="button" onclick="grsExportStats();">Export to PDF</button>
		</div>
		<div id="prepare_items">
			
		</div>
		<div id="load_items"></div>
	</div>
</div>

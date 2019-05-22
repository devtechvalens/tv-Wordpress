//Prepare the items in list for Also Call it on Page load.
jQuery(document).ready(function(){
	grsLoadStats();
	jQuery('body').on('change','#tf_filters',function(){
		if(jQuery(this).val() == 'year_to_date' || jQuery(this).val() == 'date_to_date')
		{
			grsLoadSubFilters(jQuery(this).val());
		}else
		{
			jQuery('#tf_sub_filters').html('');
		}
	});

    jQuery('body').on('click','#toDt',function(){
        if (!jQuery(this).hasClass("hasDatepicker"))
        {
            jQuery(this).datepicker({dateFormat: 'yy-mm-dd'});
            jQuery(this).datepicker("show");
        }
    });

    jQuery('body').on('click','#fromDt2',function(){
        if (!jQuery(this).hasClass("hasDatepicker"))
        {
            jQuery(this).datepicker({
              defaultDate: "+1w",
              changeMonth: true,
              numberOfMonths: 1,
              dateFormat: 'yy-mm-dd'
            })
            .on( "change", function() {
              jQuery('#toDt2').datepicker( "option", "minDate", getDate( this ) );
            });
            jQuery(this).datepicker("show");
        }
    });

    jQuery('body').on('click','#toDt2',function(){
        if (!jQuery(this).hasClass("hasDatepicker"))
        {
            jQuery(this).datepicker({
                defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: 1,
                dateFormat: 'yy-mm-dd'
              })
              .on( "change", function() {
                jQuery('#fromDt2').datepicker( "option", "maxDate", getDate( this ) );
              });
            jQuery(this).datepicker("show");
        }
    });
});

function grsLoadStats()
{
	//Get Selected Values for dealer and filter.
	var dealerId = document.getElementById('stats_dealers').value;
	var statsFilter = document.getElementById('tf_filters').value;
	var dtFrom = '';
	var dtTo = '';
    if(statsFilter === 'year_to_date')
    {
        dtFrom = document.getElementById('fromDt').value;
        dtTo = document.getElementById('toDt').value;
    }
    if(statsFilter === 'date_to_date')
    {
        dtFrom = document.getElementById('fromDt2').value;
        dtTo = document.getElementById('toDt2').value;
    }
	var pageCnt = document.getElementById('pagecnt').value;
    jQuery.ajax({
        type : "POST",
        dataType : "json",
        url : grsScript.adminAjaxUrl,
        data : {action: "grs_get_stats",did : dealerId, stats_filter : statsFilter, dt_from:dtFrom, dt_to:dtTo, pagecnt: pageCnt},
        success: function(response) {
            //console.log(response);
            jQuery('#prepare_items').html(response.opt);
            jQuery('#load_items').html('');
            jQuery('.totalvcnt').html('Total Event Count : '+response.chartdt.totalvcnt);
            grsLoadChart(response.chartdt);
            grsLoadPieChart(response.chartdt);
        }
    });
}

function grsLoadStatsByCt(count_type)
{
	//Get Selected Values for dealer and filter.
	var dealerId = document.getElementById('stats_dealers').value;
	var statsFilter = document.getElementById('tf_filters').value;
	var dtFrom = '';
	var dtTo = '';
    if(statsFilter === 'year_to_date')
    {
        dtFrom = document.getElementById('fromDt').value;
        dtTo = document.getElementById('toDt').value;
    }
    if(statsFilter === 'date_to_date')
    {
        dtFrom = document.getElementById('fromDt2').value;
        dtTo = document.getElementById('toDt2').value;
    }
	var pageCnt = document.getElementById('pagecnt').value;
    jQuery.ajax({
        type : "POST",
        dataType : "json",
        url : grsScript.adminAjaxUrl,
        data : {action: "grs_get_stats_ct",did : dealerId, stats_filter : statsFilter, dt_from:dtFrom, dt_to:dtTo, pagecnt: pageCnt, count_type:count_type},
        success: function(response) {
            console.log(response);
            jQuery('#load_items').html(response.opt);
            if(count_type == 'vehicle_vcnt')
            {
                if(response.chartdt.totalvcnt>0)
                {
                    grsLoadChart2(response.chartdt);
                }                
            }
        }
    });
}

function grsLoadSubFilters(sub_filter)
{
	jQuery.ajax({
        type : "POST",
        dataType : "json",
        url : grsScript.adminAjaxUrl,
        data : {action: "grs_get_sub_filters",sub_filter : sub_filter},
        success: function(response) {
            //console.log(response);
            jQuery('#tf_sub_filters').html(response.opt);
        }
    });
}

function getDate( element ) {
    var dateFormat = "yy-mm-dd";
    var date;
    try {
        date = jQuery.datepicker.parseDate( dateFormat, element.value );
    } catch( error ) {
        date = null;
    }

    return date;
}

function grsExportStats()
{
    var canvas = document.getElementById('loadStatsChart');
    var photo = canvas.toDataURL('image/png');

    var canvas2 = document.getElementById('loadStatsChart2');
    var photo2 = '';
    if(canvas2 != null)
    {
        var photo2 = canvas2.toDataURL('image/png');
    }    
    //Get Selected Values for dealer and filter.
    var dealerId = document.getElementById('stats_dealers').value;
    var statsFilter = document.getElementById('tf_filters').value;
    var dtFrom = '';
    var dtTo = '';
    if(statsFilter === 'year_to_date')
    {
        dtFrom = document.getElementById('fromDt').value;
        dtTo = document.getElementById('toDt').value;
    }
    if(statsFilter === 'date_to_date')
    {
        dtFrom = document.getElementById('fromDt2').value;
        dtTo = document.getElementById('toDt2').value;
    }
    var pageCnt = document.getElementById('pagecnt').value;
    jQuery.ajax({
        type : "POST",
        dataType : "json",
        url : grsScript.adminAjaxUrl,
        data : {action: "grs_export_stats",did : dealerId, stats_filter : statsFilter, dt_from:dtFrom, dt_to:dtTo, pagecnt: pageCnt,photo: photo,photo2:photo2},
        success: function(response) {
            //console.log(response);
            //window.location.href = response.opt;
            window.open(response.opt,'_blank');
            //jQuery('#prepare_items').html(response.opt);
            //jQuery('#load_items').html('');
        }
    });
}

function grsLoadChart(chartdt)
{
    var ctx = document.getElementById("loadStatsChart");
    var chart = new Chart(ctx, {
        type: 'line',
        data:   {
                "labels":chartdt.labels,
                "datasets":[{"label":"Event Counts","data":chartdt.data,
                    "fill":true,
                    "borderColor":"rgb(75, 192, 192)",
                    "lineTension":0.1
                    }]
                },
        options: {}
    });
}

function grsLoadChart2(chartdt)
{
    var ctx = document.getElementById("loadStatsChart2");
    var chart = new Chart(ctx, {
        type: 'line',
        data:   {
                "labels":chartdt.labels,
                "datasets":[{"label":"Event Counts","data":chartdt.data,
                    "fill":true,
                    "borderColor":"rgb(75, 192, 192)",
                    "lineTension":0.1
                    }]
                },
        options: {}
    });
}

function grsLoadPieChart(chartdt)
{
    var ctx = document.getElementById("loadStatsChart3");
    var chart = new Chart(ctx, {
        type: 'pie',
        data:   {
                "labels":chartdt.pie_labels,
                "datasets":[{"data":chartdt.pie_data,"backgroundColor":chartdt.pie_color}]
                },
        options: {}
    });
}
function grsSendStats(did,vid,ct)
{
    if(did === '' || vid === '' || ct === '')
    {
        return;
    }
    jQuery.ajax({
        type : "POST",
        dataType : "json",
        url : grsScript.adminAjaxUrl,
        data : {action: "grs_send_stats",did : did, vid : vid, ct:ct},
        success: function(response) {
            console.log(response);
        }
    });
}
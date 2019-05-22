jQuery(document).ready(function($) {
    $('.color-picker').wpColorPicker();

    $('#lobster-post-sortable').sortable({
        axis: 'y',
        cancel: ".ui-state-disabled",
        update: function (event, ui) {
            var sortOrder = $('#lobster-post-sortable').sortable('toArray').toString();
            $('#lobster-post-sort-order').val(sortOrder);
        }
    });
});
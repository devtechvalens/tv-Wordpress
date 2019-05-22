(function($) {
    /*
     * Attach a click event handler to the lobster post icon link
     */
    $('table.wp-list-table').on('click', 'td.column-lobster_post > a.lobster-posts', function(event) {
        toggle_lobster_post($(this));
        event.preventDefault();
    });

    /*
     * Process a ajax request and set/unset the post lobster
     */
    function toggle_lobster_post(element)
    {
        // Init variables
        var postID = element.data('id'),
            postNonce = element.data('nonce'),
            postHandle = !element.hasClass('active') ? 1 : 0;

        element.children('span.dashicons').addClass('load');

        // Queue up the sticky ajax request
        $.ajaxQueue({
            type: 'POST',
            dataType: 'json',
            url: lobsterPostObject.ajaxUrl,
            data: {
                _ajax_nonce: postNonce,
                action: lobsterPostObject.action,
                handle: postHandle,
                post_id: postID
            }
        }).done(function(response) {

            if(response.success) {
                // Toggle link state/icon and set post states to title
                console.log(element);
                console.log(response);
                set_lobster_icon(element, response.data.lobster);
                //set_lobster_post_states(postID, response.data.states, response.data.available);
            } else {
                alert(response.data);
            }

            element.children('span.dashicons').removeClass('load');

        }).fail(function(jqXHR, textStatus) {
            console.log(jqXHR.responseText);
            element.children('span.dashicons').removeClass('load');
        });
    }

    /*
     * Set the dashboard lobster icon grey or blue
     */
    function set_lobster_icon(element, sticky) {
        if(sticky) {
            element.addClass('active');
            element.children('span.dashicons').removeClass('dashicons-lobster-grey').addClass('dashicons-lobster');
        } else {
            element.removeClass('active');
            element.children('span.dashicons').removeClass('dashicons-lobster').addClass('dashicons-lobster-grey');
        }
    }

    /*
     * Set the post states to the title cell
     */
    function set_lobster_post_states(postID, states, available) {
        var postTitleLink = $('tr#post-' + postID).find('a.row-title');

        // Post title available
        if(postTitleLink.length) {

            // Reset the post link element, to delete the post-states
            postTitleLink.parent().html(postTitleLink);

            if(states !== '') {
                postTitleLink.after(states);

                // Change the post state color to red, if the sticky feature is not available
                if(!available) {
                    postTitleLink.parent().find('span.post-state').css('color', '#a00');
                }
            }
        }
    }
})(jQuery);
jQuery(document).ready(function($) {
    $('#search-contacts').click(function() {
        var identifier = $('#contact-id').val();

        $.ajax({
            url: rrzeFaudirAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'rrze_faudir_search_contacts',
                security: rrzeFaudirAjax.api_nonce,
                identifier: identifier
            },
            success: function(response) {
                console.log('AJAX response:', response); // Log the full response for debugging
                
                if (response.success) {
                    // Directly inject the HTML returned by the server into the contacts-list div
                    $('#contacts-list').html(response.data);
                } else {
                    $('#contacts-list').html('<p>' + response.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX request failed:', status, error); // Log errors
                $('#contacts-list').html('<p>An error occurred during the request.</p>');
            }
        });
    });

    $('#clear-cache-button').on('click', function() {
        if (confirm(rrzeFaudirAjax.confirm_clear_cache)) {
            $.post(rrzeFaudirAjax.ajax_url, {
                action: 'rrze_faudir_clear_cache',
                security: rrzeFaudirAjax.api_nonce
            }, function(response) {
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('Error clearing cache.');
                }
            });
        }
    });
});


jQuery(document).ready(function($) {
    $('#contact-id').on('keyup', function() {
        var identifier = $(this).val().trim();

        if (identifier.length > 0) {
            $.ajax({
                url: rrzeFaudirAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'rrze_faudir_search_contacts',
                    security: rrzeFaudirAjax.api_nonce,
                    identifier: identifier
                },
                success: function(response) {
                    console.log('AJAX response:', response);
                    $('#contacts-list').empty();

                    if (response.success) {
                        $('#contacts-list').html(response.data);
                    } else {
                        $('#contacts-list').html('<p>' + response.data + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX request failed:', status, error);
                    $('#contacts-list').html('<p>An error occurred during the request.</p>');
                }
            });
        } else {
            // If the input is empty, refresh to show all contacts
            $.ajax({
                url: rrzeFaudirAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'rrze_faudir_refresh_contacts',
                    security: rrzeFaudirAjax.api_nonce,
                },
                success: function(response) {
                    if (response.success) {
                        $('#contacts-list').html(response.data);
                    } else {
                        $('#contacts-list').html('<p>No contacts found.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX request failed:', status, error);
                    $('#contacts-list').html('<p>An error occurred during the request.</p>');
                }
            });
        }
    });
});


jQuery(document).ready(function($) {
    let currentPage = 1;

    function loadContacts(page) {
        $.ajax({
            url: rrzeFaudirAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'rrze_faudir_fetch_contacts',
                security: rrzeFaudirAjax.api_nonce,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    $('#contacts-list').html(response.data);
                    // Keep the tab active on pagination
                    localStorage.setItem('activeTab', '#tab-5');
                } else {
                    $('#contacts-list').html('<p>An error occurred while loading contacts.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX request failed:', status, error);
                $('#contacts-list').html('<p>An error occurred during the request.</p>');
            }
        });
    }

    // Restore the active tab on page load
    let activeTab = localStorage.getItem('activeTab') || '#tab-1';
    $('.nav-tab').removeClass('nav-tab-active');
    $('a[href="' + activeTab + '"]').addClass('nav-tab-active');
    $('.tab-content').hide();
    $(activeTab).show();

    // Tab switching logic
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tab-content').hide();
        $($(this).attr('href')).show();

        // Store the active tab in localStorage
        localStorage.setItem('activeTab', $(this).attr('href'));
    });

// Initial load
loadContacts(currentPage);

// Event listeners for pagination buttons
$(document).on('click', '.prev-page', function(e) {
    e.preventDefault(); // Prevent the default link behavior
    if (currentPage > 1) {
        currentPage--;
        loadContacts(currentPage);
    }
});

$(document).on('click', '.next-page', function(e) {
    e.preventDefault(); // Prevent the default link behavior
    currentPage++;
    loadContacts(currentPage);
});

    // Initial load
    if (activeTab === '#tab-5') {
        loadContacts(currentPage);
    }

    // Existing search by identifier
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
    });

    // Search by ID
    $('#search-person-by-id').click(function() {
        var personId = $('#person-id').val().trim();

        if (personId.length > 0) {
            $.ajax({
                url: rrzeFaudirAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'search_person_by_id',
                    security: rrzeFaudirAjax.api_nonce,
                    personId: personId
                },
                success: function(response) {
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
            $('#contacts-list').html('<p>Please enter a valid ID.</p>');
        }
    });

    // Clear cache button handler
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

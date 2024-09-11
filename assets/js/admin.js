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
jQuery(document).ready(function($) {

    // Search by name or ID
    $('#search-person-by-id').click(function() {
        var personId = $('#person-id').val().trim();
        var givenName = $('#given-name').val().trim();
        var familyName = $('#family-name').val().trim();
    
        console.log('Person ID:', personId);
        console.log('Given Name:', givenName);
        console.log('Family Name:', familyName);
    
        if (personId.length > 0 || givenName.length > 0 || familyName.length > 0) {
            $.ajax({
                url: rrzeFaudirAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'search_person_by_id',
                    security: rrzeFaudirAjax.api_nonce,
                    personId: personId,
                    givenName: givenName,
                    familyName: familyName
                },
                success: function(response) {
                    console.log('Response:', response); // Log the response
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
            $('#contacts-list').html('<p>Please enter a valid search term.</p>');
        }
    });
    
});

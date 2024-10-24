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

    // Initial load for contacts when on the right tab
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

    // Prevent form submission on Enter key
    $('#search-person-form input').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $('#search-person-form').submit();
        }
    });

    // Handle form submission
    $('#search-person-form').on('submit', function(e) {
        e.preventDefault(); // Prevent form submission

        var personId = $('#person-id').val().trim();
        var givenName = $('#given-name').val().trim();
        var familyName = $('#family-name').val().trim();
        var email = $('#email').val().trim();

        console.log('Person ID:', personId);
        console.log('Given Name:', givenName);
        console.log('Family Name:', familyName);
        console.log('Email:', email);

        if (personId.length > 0 || givenName.length > 0 || familyName.length > 0 || email.length > 0) {
            $.ajax({
                url: rrzeFaudirAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'rrze_faudir_search_person',
                    security: rrzeFaudirAjax.api_nonce,
                    person_id: personId,
                    given_name: givenName,
                    family_name: familyName,
                    email: email
                },
                success: function(response) {
                    console.log('Response:', response);
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

    // Handle click on add person button
    $(document).on('click', '.add-person', function() {
        var personName = $(this).data('name');
        var personId = $(this).data('id');

        $.ajax({
            url: rrzeFaudirAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'rrze_faudir_create_custom_person',
                security: rrzeFaudirAjax.api_nonce,
                person_name: personName,
                person_id: personId
            },
            success: function(response) {
                if (response.success) {
                    alert('Custom person created successfully!');
                    // Optionally, you can refresh the search results or update the UI
                } else {
                    alert('Error creating custom person: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('An error occurred while creating the custom person. Please check the console for more details.');
            }
        });
    });
});

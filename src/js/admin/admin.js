jQuery(document).ready(function ($) {
    let currentPage = 1;

    function loadContacts(page) {
        console.log('Loading contacts for page:', page);
        $.ajax({
            url: rrzeFaudirAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'rrze_faudir_fetch_contacts',
                security: rrzeFaudirAjax.api_nonce,
                page: page
            },
            success: function (response) {
                if (response.success) {
                    $('#contacts-list').html(response.data);
                    // Keep the tab active on pagination
                    localStorage.setItem('activeTab', '#tab-5');
                } else {
                    $('#contacts-list').html('<p>An error occurred while loading contacts.</p>');
                }
            },
            error: function (xhr, status, error) {
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
    $('.nav-tab').click(function (e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tab-content').hide();
        $($(this).attr('href')).show();

        // Store the active tab in localStorage
        localStorage.setItem('activeTab', $(this).attr('href'));
    });

    // Event listeners for pagination buttons
    $(document).on('click', '.prev-page', function (e) {
        e.preventDefault(); // Prevent the default link behavior
        if (currentPage > 1) {
            currentPage--;
            loadContacts(currentPage);
        }
    });

    $(document).on('click', '.next-page', function (e) {
        e.preventDefault(); // Prevent the default link behavior
        currentPage++;
        loadContacts(currentPage);
    });

    // Clear cache button handler
    $('#clear-cache-button').on('click', function () {
        if (confirm(rrzeFaudirAjax.confirm_clear_cache)) {
            $.post(rrzeFaudirAjax.ajax_url, {
                action: 'rrze_faudir_clear_cache',
                security: rrzeFaudirAjax.api_nonce
            }, function (response) {
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('Error clearing cache.');
                }
            });
        }
    });

    // Prevent form submission on Enter key
    $('#search-person-form input').on('keypress', function (e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $('#search-person-form').submit();
        }
    });

    $('#search-person-form input').on('input', function () {
        var disabled = true;
        $('#search-person-form input[type="text"], #search-person-form input[type="email"]').each(function () {
            if ($(this).val() !== '') {
                disabled = false;
            }
        });
        if ($('#given-name').val().length == 1 || $('#family-name').val().length == 1) {
            disabled = true;
        }
        $('#search-person-form button').prop('disabled', disabled);
    });

    // Handle form submission
    $('#search-person-form').on('submit', function (e) {
        e.preventDefault(); // Prevent form submission

        var personId = $('#person-id').val().trim();
        var givenName = $('#given-name').val().trim();
        var familyName = $('#family-name').val().trim();
        var email = $('#email').val().trim();
        var includeDefaultOrg = $('#include-default-org').is(':checked') ? '1' : '0';

        console.log('Person ID:', personId);
        console.log('Given Name:', givenName);
        console.log('Family Name:', familyName);
        console.log('Email:', email);
        console.log('Include Default Organization:', includeDefaultOrg);
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
                    email: email,
                    include_default_org: includeDefaultOrg
                },
                success: function (response) {
                    console.log('Response:', response);
                    if (response.success) {
                        $('#contacts-list').html(response.data);
                    } else {
                        $('#contacts-list').html('<p>' + response.data + '</p>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX request failed:', status, error);
                    $('#contacts-list').html('<p>An error occurred during the request.</p>');
                }
            });
        } else {
            $('#contacts-list').html('<p>Please enter a valid search term.</p>');
        }
    });

    // Handle click on add person button
    $(document).on('click', '.add-person', function () {
        var button = $(this); // Store reference to the button
        var personName = button.data('name');
        var personId = button.data('id');
        var organizations = button.data('organizations') || [];
        var functions = button.data('functionLabel') || [];

        // Disable the button and show loading indicator
        button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> ' + rrzeFaudirAjax.add_text);

        $.ajax({
            url: rrzeFaudirAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'rrze_faudir_create_custom_person',
                security: rrzeFaudirAjax.api_nonce,
                person_name: personName,
                person_id: personId,
                organizations: organizations,
                functions: functions
            },
            success: function (response) {
                if (response.success) {
                    // Replace the Add button with an Edit link
                    var editLink = $('<a>', {
                        href: response.data.edit_url,
                        class: 'edit-person button',
                        html: '<span class="dashicons dashicons-edit"></span> ' + rrzeFaudirAjax.edit_text
                    });
                    button.replaceWith(editLink);
                } else {
                    alert('Error creating custom person: ' + (response.data || 'Unknown error'));
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('An error occurred while creating the custom person. Please check the console for more details.');
            },
            complete: function () {
                // Enable the button and remove loading indicator
                button.prop('disabled', false).html('Add');
            }
        });
    });

    // Handle person ID input changes
    $('#person_id').on('change', function () {
        var personId = $(this).val();
        if (!personId) return;

        // Show loading indicator if needed

        $.ajax({
            url: customPerson.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_person_attributes',
                nonce: customPerson.nonce,
                person_id: personId
            },
            success: function (response) {
                if (response.success) {
                    const data = response.data;

                    // Update regular fields
                    $('#person_name').val(data.person_name);
                    $('#person_email').val(data.person_email);
                    $('#person_given_name').val(data.person_given_name);
                    $('#person_family_name').val(data.person_family_name);
                    $('#person_title').val(data.person_title);

                    // Update organizations and functions
                    const organizationsWrapper = $('.contacts-wrapper');
                    organizationsWrapper.empty();

                    data.organizations.forEach((org, index) => {
                        const orgBlock = `
                            <div class="organization-block">
                                <div class="organization-header">
                                    <h4>Organization ${index + 1}</h4>
                                </div>
                                <input type="text" name="person_contacts[${index}][organization]" value="${org.organization}" class="widefat" readonly />
                                <div class="functions-wrapper">
                                    <h5>Functions</h5>
                                    ${org.functions.map(func => `
                                        <div class="function-block">
                                            <input type="text" name="person_contacts[${index}][functions][]" value="${func}" class="widefat" readonly />
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                        organizationsWrapper.append(orgBlock);
                    });
                } else {
                    alert('Error fetching person data: ' + (response.data || 'Unknown error'));
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('Error fetching person data. Please check the console for details.');
            }
        });
    });

    // Handle organization search form submission
    $('#search-org-form').on('submit', function (e) {
        e.preventDefault();

        var searchTerm = $('#org-search').val().trim();

        if (searchTerm.length > 0) {
            $.ajax({
                url: rrzeFaudirAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'rrze_faudir_search_org',
                    security: rrzeFaudirAjax.api_nonce,
                    search_term: searchTerm
                },
                success: function (response) {
                    console.log('Organization search response:', response);
                    if (response.success) {
                        $('#organizations-list').html(response.data);
                    } else {
                        $('#organizations-list').html('<p>' + response.data + '</p>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX request failed:', status, error);
                    $('#organizations-list').html('<p>An error occurred during the request.</p>');
                }
            });
        } else {
            $('#organizations-list').html('<p>Please enter a search term.</p>');
        }
    });

    // Prevent form submission on Enter key for organization search
    $('#search-org-form input').on('keypress', function (e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $('#search-org-form').submit();
        }
    });
});

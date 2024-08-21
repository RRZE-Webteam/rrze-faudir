jQuery(document).ready(function($) {
    $('#search-person').click(function() {
        var email = $('#email').val();
        var idm_kennung = $('#idm_kennung').val();
        var full_name = $('#full_name').val();

        $.ajax({
            url: rrze_faudir_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'rrze_faudir_search_person',
                security: rrze_faudir_ajax.api_nonce,
                email: email,
                idm_kennung: idm_kennung,
                full_name: full_name
            },
            success: function(response) {
                console.log('AJAX response:', response); // Log the full response for debugging
            
                if (response.success) {
                    var personsArray = response.data[0]; // Access the first array inside response.data
            
                    if (Array.isArray(personsArray)) {
                        var resultsHtml = '<ul>';
                        personsArray.forEach(function(person) {
                            console.log('Processing person:', person); // Log each person object
            
                            // Construct the full name
                            var name = (person.personalTitle ? person.personalTitle + ' ' : '') + (person.givenName || '') + ' ' + (person.familyName || '');
                            var idm_kennung = person.identifier || 'N/A';
            
                            // Construct contacts information
                            var contactInfo = '';
                            if (Array.isArray(person.contacts)) {
                                person.contacts.forEach(function(contact) {
                                    var orgName = contact.organization && contact.organization.name ? contact.organization.name : 'N/A';
                                    var role = contact.functionLabel && contact.functionLabel.en ? contact.functionLabel.en : 'N/A';
                                    contactInfo += role + ' at ' + orgName + '<br>';
                                });
                            }
            
                            resultsHtml += '<li>';
                            resultsHtml += '<strong>Name:</strong> ' + name + '<br>';
                            resultsHtml += '<strong>IdM-Kennung:</strong> ' + idm_kennung + '<br>';
                            resultsHtml += '<strong>Contacts:</strong> <br>' + contactInfo + '<br>';
                            resultsHtml += '</li>';
                        });
                        resultsHtml += '</ul>';
                        $('#search-results').html(resultsHtml);
                    } else {
                        console.log('Data is not an array:', personsArray); // Log if data is not an array
                        $('#search-results').html('<p>No results found.</p>');
                    }
                } else {
                    console.log('AJAX error:', response.data); // Log if the response indicates failure
                    $('#search-results').html('<p>' + response.data + '</p>');
                }
            }
            
            
          
        });
    });
});

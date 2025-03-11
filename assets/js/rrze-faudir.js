/*
* JavaScript Definitions for: 
* Plugin: rrze-faudir
* Version: 2.1.26-1
*/

jQuery(document).ready(function ($) {
    $('#person_id').on('change', function() {
        var personId = $(this).val();

        if (personId) {
            $.ajax({
                url: customPerson.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_person_attributes',
                    person_id: personId,
                    nonce: customPerson.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        $('#person_name').val(data.person_name);
                        $('#person_email').val(data.person_email);
                        $('#person_given_name').val(data.person_given_name);
                        $('#person_family_name').val(data.person_family_name);
                        $('#person_title').val(data.person_title);
                        $('#person_organization').val(data.person_organization);
                        $('#person_function').val(data.person_function);
                        // Update other fields as needed
                    } else {
                        alert(response.data);
                    }
                }
            });
        }
    });
    
});

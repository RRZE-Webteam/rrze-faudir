jQuery(document).ready(function($) {
    console.log('RRZE FAUDIR JS from src directory');

    // Test API Call
    $('#test-api-call').click(function () {
        $.ajax({
            url: 'https://api.fau.de/pub/v1/opendir/persons?limit=10&offset=1',
            type: 'GET',
            headers: {
                'X-API-KEY': rrze_faudir_ajax.api_key,
                'Content-Type': 'application/json',
         
            },
            success: function (response) {
                if (response.success) {
                    console.log('API Response Data:', response.data); // Log the actual data returned from the API
                    $('#api-response').html('<pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                } else {
                    console.log('API Error:', response.data); // Log the error message
                    $('#api-response').html('<p>Error: ' + response.data + '</p>');
                }
            },
            error: function (error) {
                console.error('AJAX Error:', error); // Log the error object
                $('#api-response').html('<p>Error occurred: ' + error.responseText + '</p>');
            }
        });
    });

});

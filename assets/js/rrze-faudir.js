jQuery(document).ready(function($) {
    console.log('RRZE FAUDIR JS from src directory');

    $('#test-api-call').click(function() {
        $.ajax({
            url: 'https://api.fau.de/pub/v1/opendir/persons?limit=10&offset=1',
            type: 'GET',
            headers: {
                'X-API-KEY': rrze_faudir_ajax.api_key,
                'Content-Type': 'application/json',
         
            },
            success: function(response) {
                $('#api-response').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
            },
            error: function(error) {
                $('#api-response').html('<p>Error occurred: ' + error.responseText + '</p>');
            }
        });
    });
});

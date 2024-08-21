// Additional JavaScript functionalities for RRZE FAUDIR

jQuery(document).ready(function($) {
    console.log('RRZE FAUDIR JS from src directory');

    $('#test-api-call').click(function() {
        $.ajax({
            url: rrze_faudir_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'rrze_faudir_test_api_call',
                security: rrze_faudir_ajax.api_nonce
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
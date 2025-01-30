// custom-shortcode.js
jQuery(document).ready(function($) {
    // Retrieve person_id from hidden input and set the shortcode value directly
    var personId = $('#hidden-person-id').val(); // Retrieve person_id from hidden input
    var shortcode = '[faudir identifier="' + personId + '"]';
    $('#generated-shortcode').val(shortcode); // Set the generated shortcode

    $('#copy-shortcode').on('click', function() {
        var shortcodeInput = $('#generated-shortcode');
        shortcodeInput.select();
        document.execCommand('copy');
        alert('Shortcode copied to clipboard!');
    });
});
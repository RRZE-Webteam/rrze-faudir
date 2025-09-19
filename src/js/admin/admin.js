jQuery(function ($) {
  // --- Cache leeren ---
  $('#clear-cache-button').on('click', function () {
    if (!window.rrzeFaudirAjax) return;
    if (confirm(rrzeFaudirAjax.confirm_clear_cache)) {
      $.post(rrzeFaudirAjax.ajax_url, {
        action: 'rrze_faudir_clear_cache',
        security: rrzeFaudirAjax.api_nonce
      }).done(function (resp) {
        alert(resp && resp.success ? resp.data : 'Error clearing cache.');
      });
    }
  });

  // --- Import FAU Person ---
  $('#import-fau-person-button').on('click', function () {
    if (!window.rrzeFaudirAjax) return;
    if (!confirm(rrzeFaudirAjax.confirm_import)) return;

    var $target = $('#migration-progress');
    $target.empty().append(
      '<div id="import-progress">' +
        '<div class="progress-bar" style="width:0%;height:20px;background-color:#4CAF50;"></div>' +
      '</div>' +
      '<div id="import-response" style="margin-top:10px;"></div>'
    );

    var progressInterval = setInterval(function () {
      var $bar = $('#import-progress .progress-bar');
      var current = parseInt($bar.css('width')) / $('#import-progress').width() * 100 || 0;
      $bar.css('width', Math.min(current + 10, 90) + '%');
    }, 500);

    $.post(rrzeFaudirAjax.ajax_url, {
      action: 'rrze_faudir_import_fau_person',
      security: rrzeFaudirAjax.api_nonce
    }).done(function (response) {
      clearInterval(progressInterval);
      $('#import-progress .progress-bar').css('width', '100%');
      if (response.success) {
        var formatted = String(response.data).replace(/\n/g, '</li><li>');
        $('#import-response').html('<ul style="max-width:75ch;"><li>' + formatted + '</li></ul>');
      } else {
        $('#import-response').html('<p>No contact from FAU Person imported.</p>');
      }
    }).fail(function () {
      clearInterval(progressInterval);
      $('#import-progress .progress-bar').css('width', '100%');
      $('#import-response').html('<p>An error occurred during the import.</p>');
    });
  });

  // --- Personensuche ---
  if ($('#search-person-form').length) {
    // Enter verhindern
    $('#search-person-form input').on('keypress', function (e) {
      if (e.which === 13) {
        e.preventDefault();
        $('#search-person-form').submit();
      }
    });

    // Button enable/disable
    $('#search-person-form input').on('input', function () {
      var disabled = true;
      $('#search-person-form input[type="text"], #search-person-form input[type="email"]').each(function () {
        if ($(this).val() !== '') disabled = false;
      });
      if ($('#given-name').val().length === 1 || $('#family-name').val().length === 1) {
        disabled = true;
      }
      $('#search-person-form button').prop('disabled', disabled);
    });

    // AJAX Submit
    $('#search-person-form').on('submit', function (e) {
      e.preventDefault();
      if (!window.rrzeFaudirAjax) return;

      var personId = $('#person-id').val().trim();
      var givenName = $('#given-name').val().trim();
      var familyName = $('#family-name').val().trim();
      var email = $('#email').val().trim();
      var includeDefaultOrg = $('#include-default-org').is(':checked') ? '1' : '0';

      if (personId || givenName || familyName || email) {
        $.post(rrzeFaudirAjax.ajax_url, {
          action: 'rrze_faudir_search_person',
          security: rrzeFaudirAjax.api_nonce,
          person_id: personId,
          given_name: givenName,
          family_name: familyName,
          email: email,
          include_default_org: includeDefaultOrg
        }).done(function (response) {
          $('#contacts-list').html(response.success ? response.data : '<p>' + response.data + '</p>');
        }).fail(function () {
          $('#contacts-list').html('<p>An error occurred during the request.</p>');
        });
      } else {
        $('#contacts-list').html('<p>Please enter a valid search term.</p>');
      }
    });
  }

  // --- „Add person“-Button (Delegation) ---
  $(document).on('click', '.add-person', function () {
    if (!window.rrzeFaudirAjax) return;

    var $btn = $(this);
    var personName = $btn.data('name');
    var personId = $btn.data('id');
    var includeDefaultOrg = $btn.data('include-default-org');
    var functions = $btn.data('functionLabel') || [];

    $btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> ' + rrzeFaudirAjax.add_text);

    $.post(rrzeFaudirAjax.ajax_url, {
      action: 'rrze_faudir_create_custom_person',
      security: rrzeFaudirAjax.api_nonce,
      person_name: personName,
      person_id: personId,
      include_default_org: includeDefaultOrg,
      functions: functions
    }).done(function (response) {
      if (response.success) {
        var editLink = $('<a>', {
          href: response.data.edit_url,
          class: 'edit-person button',
          html: '<span class="dashicons dashicons-edit"></span> ' + rrzeFaudirAjax.edit_text
        });
        $btn.replaceWith(editLink);
      } else {
        alert('Error creating custom person: ' + (response.data || 'Unknown error'));
      }
    }).fail(function () {
      alert('An error occurred while creating the custom person.');
    }).always(function () {
      $btn.prop('disabled', false).html(rrzeFaudirAjax.add_text);
    });
  });

  // --- Orgsuche ---
  if ($('#search-org-form').length) {
    $('#search-org-form').on('submit', function (e) {
      e.preventDefault();
      if (!window.rrzeFaudirAjax) return;

      var searchTerm = $('#org-search').val().trim();
      if (!searchTerm) {
        $('#organizations-list').html('<p>Please enter a search term.</p>');
        return;
      }

      $.post(rrzeFaudirAjax.ajax_url, {
        action: 'rrze_faudir_search_org',
        security: rrzeFaudirAjax.api_nonce,
        search_term: searchTerm
      }).done(function (response) {
        $('#organizations-list').html(response.success ? response.data : '<p>' + response.data + '</p>');
      }).fail(function () {
        $('#organizations-list').html('<p>An error occurred during the request.</p>');
      });
    });

    $('#search-org-form input').on('keypress', function (e) {
      if (e.which === 13) {
        e.preventDefault();
        $('#search-org-form').submit();
      }
    });
  }
});

jQuery(function ($) {
  let currentPage = 1;

  // --- Tab-Erkennung: Hash-Tabs (alt) vs. Server-Tabs (neu) -----------------
  const $wrap = $('.wrap.faudir-settings');
  const $nav  = $wrap.find('.nav-tab-wrapper');
  const isHashTabs = $nav.find('a[href^="#"]').length > 0;

  if (isHashTabs) {
    // ===== Altes Verhalten: Clientseitiges Umschalten der Hash-Tabs =====
    // Aktiven Tab aus localStorage wiederherstellen
    let activeTab = localStorage.getItem('activeTab') || '#tab-1';
    $('.nav-tab').removeClass('nav-tab-active');
    $('a[href="' + activeTab + '"]').addClass('nav-tab-active');
    $('.tab-content').hide();
    $(activeTab).show();

    // Klick-Logik
    $nav.on('click', '.nav-tab', function (e) {
      e.preventDefault();
      $('.nav-tab').removeClass('nav-tab-active');
      $(this).addClass('nav-tab-active');
      $('.tab-content').hide();
      const target = $(this).attr('href');
      $(target).show();
      localStorage.setItem('activeTab', target);
    });

  } else {
    // ===== Neues Verhalten: Server-seitige Tabs =====
    // NICHT verstecken/umblenden – Inhalt wird serverseitig gerendert
    // Falls im Markup noch style="display:none;" vorhanden ist → anzeigen
    $wrap.find('.tab-content').show();

    // Klicks NICHT abfangen (volle Navigation)
    $nav.on('click', '.nav-tab', function () {
      // bewusst kein preventDefault
      // optional: hier könntest du einen kleinen Loader einblenden
    });
  }

  // --- Hilfsfunktion für Kontaktliste (falls genutzt) -----------------------
  function loadContacts(page) {
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
          // Nur im Hash-Tab-Modus den aktiven Tab „merken“
          if (isHashTabs) {
            localStorage.setItem('activeTab', '#tab-5');
          }
        } else {
          $('#contacts-list').html('<p>An error occurred while loading contacts.</p>');
        }
      },
      error: function () {
        $('#contacts-list').html('<p>An error occurred during the request.</p>');
      }
    });
  }

  // --- Pagination (nur binden, wenn Schaltflächen existieren) ---------------
  $(document).on('click', '.prev-page', function (e) {
    e.preventDefault();
    if (currentPage > 1) {
      currentPage--;
      loadContacts(currentPage);
    }
  });
  $(document).on('click', '.next-page', function (e) {
    e.preventDefault();
    currentPage++;
    loadContacts(currentPage);
  });

  // --- Cache leeren ----------------------------------------------------------
  if ($('#clear-cache-button').length) {
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
  }

  // --- Import FAU Person -----------------------------------------------------
  if ($('#import-fau-person-button').length) {
    $('#import-fau-person-button').on('click', function () {
      if (!confirm(rrzeFaudirAjax.confirm_import)) {
        return;
      }

      var $target = $('#migration-progress');
      $target.empty().append(
        '<div id="import-progress">' +
          '<div class="progress-bar" style="width:0%;height:20px;background-color:#4CAF50;"></div>' +
        '</div>' +
        '<div id="import-response" style="margin-top:10px;"></div>'
      );

      var progressInterval = setInterval(function () {
        var $bar = $('#import-progress .progress-bar');
        var currentWidth = parseInt($bar.css('width')) / $('#import-progress').width() * 100 || 0;
        currentWidth = Math.min(currentWidth + 10, 90);
        $bar.css('width', currentWidth + '%');
      }, 500);

      $.post(rrzeFaudirAjax.ajax_url, {
        action: 'rrze_faudir_import_fau_person',
        security: rrzeFaudirAjax.api_nonce
      }, function (response) {
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
  }

  // --- Personensuche ---------------------------------------------------------
  if ($('#search-person-form').length) {
    // Enter unterbinden (und via AJAX senden)
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

    // Submit → AJAX
    $('#search-person-form').on('submit', function (e) {
      e.preventDefault();

      var personId = $('#person-id').val().trim();
      var givenName = $('#given-name').val().trim();
      var familyName = $('#family-name').val().trim();
      var email = $('#email').val().trim();
      var includeDefaultOrg = $('#include-default-org').is(':checked') ? '1' : '0';

      if (personId || givenName || familyName || email) {
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
            if (response.success) {
              $('#contacts-list').html(response.data);
            } else {
              $('#contacts-list').html('<p>' + response.data + '</p>');
            }
          },
          error: function () {
            $('#contacts-list').html('<p>An error occurred during the request.</p>');
          }
        });
      } else {
        $('#contacts-list').html('<p>Please enter a valid search term.</p>');
      }
    });
  }

  // --- „Add person“-Button (Delegation) -------------------------------------
  $(document).on('click', '.add-person', function () {
    var $btn = $(this);
    var personName = $btn.data('name');
    var personId = $btn.data('id');
    var includeDefaultOrg = $btn.data('include-default-org');
    var functions = $btn.data('functionLabel') || [];

    $btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> ' + rrzeFaudirAjax.add_text);

    $.ajax({
      url: rrzeFaudirAjax.ajax_url,
      method: 'POST',
      data: {
        action: 'rrze_faudir_create_custom_person',
        security: rrzeFaudirAjax.api_nonce,
        person_name: personName,
        person_id: personId,
        include_default_org: includeDefaultOrg,
        functions: functions
      },
      success: function (response) {
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
      },
      error: function () {
        alert('An error occurred while creating the custom person.');
      },
      complete: function () {
        $btn.prop('disabled', false).html('Add');
      }
    });
  });

  // --- Orgsuche --------------------------------------------------------------
  if ($('#search-org-form').length) {
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
            if (response.success) {
              $('#organizations-list').html(response.data);
            } else {
              $('#organizations-list').html('<p>' + response.data + '</p>');
            }
          },
          error: function () {
            $('#organizations-list').html('<p>An error occurred during the request.</p>');
          }
        });
      } else {
        $('#organizations-list').html('<p>Please enter a search term.</p>');
      }
    });

    // Enter unterbinden
    $('#search-org-form input').on('keypress', function (e) {
      if (e.which === 13) {
        e.preventDefault();
        $('#search-org-form').submit();
      }
    });
  }
});

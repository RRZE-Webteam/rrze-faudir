"use strict";
(() => {
  // src/js/admin/admin.js
  jQuery(function($) {
    $("#clear-cache-button").on("click", function() {
      if (!window.rrzeFaudirAjax) return;
      if (confirm(rrzeFaudirAjax.confirm_clear_cache)) {
        $.post(rrzeFaudirAjax.ajax_url, {
          action: "rrze_faudir_clear_cache",
          security: rrzeFaudirAjax.api_nonce
        }).done(function(resp) {
          alert(resp && resp.success ? resp.data : "Error clearing cache.");
        });
      }
    });
    function rrzeFaudirModalOpen() {
      var $m = $("#rrze-faudir-modal");
      $m.attr("aria-hidden", "false").addClass("is-open");
    }
    function rrzeFaudirModalClose() {
      var $m = $("#rrze-faudir-modal");
      $m.attr("aria-hidden", "true").removeClass("is-open");
    }
    function rrzeFaudirSetModalContent(txt) {
      $("#rrze-faudir-modal-content").html(txt);
    }
    $("#rrze-faudir-modal").on("click", '[data-modal-close="1"]', function() {
      rrzeFaudirModalClose();
    });
    $(document).on("keydown", function(e) {
      if (e.key === "Escape") {
        rrzeFaudirModalClose();
      }
    });
    $("#import-fau-person-button").on("click", function() {
      if (!window.rrzeFaudirAjax) return;
      if (!confirm(rrzeFaudirAjax.confirm_import)) return;
      rrzeFaudirSetModalContent("Running import ...");
      rrzeFaudirModalOpen();
      $.post(rrzeFaudirAjax.ajax_url, {
        action: "rrze_faudir_import_fau_person",
        security: rrzeFaudirAjax.api_nonce
      }).done(function(response) {
        if (response && response.success && response.data && typeof response.data.text !== "undefined") {
          rrzeFaudirSetModalContent(response.data.text || "Done.");
        } else {
          rrzeFaudirSetModalContent("No contact from FAU Person imported.");
        }
      }).fail(function() {
        rrzeFaudirSetModalContent("An error occurred during the import.");
      });
    });
    if ($("#search-person-form").length) {
      $("#search-person-form input").on("keypress", function(e) {
        if (e.which === 13) {
          e.preventDefault();
          $("#search-person-form").submit();
        }
      });
      $("#search-person-form input").on("input", function() {
        var disabled = true;
        $('#search-person-form input[type="text"], #search-person-form input[type="email"]').each(function() {
          if ($(this).val() !== "") disabled = false;
        });
        if ($("#given-name").val().length === 1 || $("#family-name").val().length === 1) {
          disabled = true;
        }
        $("#search-person-form button").prop("disabled", disabled);
      });
      $("#search-person-form").on("submit", function(e) {
        e.preventDefault();
        if (!window.rrzeFaudirAjax) return;
        var personId = $("#person-id").val().trim();
        var givenName = $("#given-name").val().trim();
        var familyName = $("#family-name").val().trim();
        var email = $("#email").val().trim();
        if (personId || givenName || familyName || email) {
          $.post(rrzeFaudirAjax.ajax_url, {
            action: "rrze_faudir_search_person",
            security: rrzeFaudirAjax.api_nonce,
            person_id: personId,
            given_name: givenName,
            family_name: familyName,
            email
          }).done(function(response) {
            $("#contacts-list").html(response.success ? response.data : "<p>" + response.data + "</p>");
          }).fail(function() {
            $("#contacts-list").html("<p>An error occurred during the request.</p>");
          });
        } else {
          $("#contacts-list").html("<p>Please enter a valid search term.</p>");
        }
      });
    }
    $(document).on("click", ".add-person", function() {
      if (!window.rrzeFaudirAjax) return;
      var $btn = $(this);
      var personName = $btn.data("name");
      var personId = $btn.data("id");
      var functions = $btn.data("functionLabel") || [];
      $btn.prop("disabled", true).html('<span class="dashicons dashicons-update"></span> ' + rrzeFaudirAjax.add_text);
      $.post(rrzeFaudirAjax.ajax_url, {
        action: "rrze_faudir_create_custom_person",
        security: rrzeFaudirAjax.api_nonce,
        person_name: personName,
        person_id: personId,
        functions
      }).done(function(response) {
        if (response.success) {
          var editLink = $("<a>", {
            href: response.data.edit_url,
            class: "edit-person button",
            html: '<span class="dashicons dashicons-edit"></span> ' + rrzeFaudirAjax.edit_text
          });
          $btn.replaceWith(editLink);
        } else {
          alert("Error creating custom person: " + (response.data || "Unknown error"));
        }
      }).fail(function() {
        alert("An error occurred while creating the custom person.");
      }).always(function() {
        $btn.prop("disabled", false).html(rrzeFaudirAjax.add_text);
      });
    });
    if ($("#search-org-form").length) {
      $("#search-org-form").on("submit", function(e) {
        e.preventDefault();
        if (!window.rrzeFaudirAjax) return;
        var searchTerm = $("#org-search").val().trim();
        if (!searchTerm) {
          $("#organizations-list").html("<p>Please enter a search term.</p>");
          return;
        }
        $.post(rrzeFaudirAjax.ajax_url, {
          action: "rrze_faudir_search_org",
          security: rrzeFaudirAjax.api_nonce,
          search_term: searchTerm
        }).done(function(response) {
          $("#organizations-list").html(response.success ? response.data : "<p>" + response.data + "</p>");
        }).fail(function() {
          $("#organizations-list").html("<p>An error occurred during the request.</p>");
        });
      });
      $("#search-org-form input").on("keypress", function(e) {
        if (e.which === 13) {
          e.preventDefault();
          $("#search-org-form").submit();
        }
      });
    }
    if ($("#rrze-faudir-refresh-person-data").length) {
      $("#rrze-faudir-refresh-person-data").on("click", function() {
        if (!window.rrzeFaudirAjax) return;
        var $btn = $("#rrze-faudir-refresh-person-data");
        var $spinner = $("#rrze-faudir-refresh-spinner");
        var $out = $("#rrze-faudir-refresh-result");
        var postId = $("#rrze-faudir-refresh-postid").val();
        var personId = $("#rrze-faudir-refresh-personid").val();
        $out.removeClass("notice notice-error notice-success inline").empty();
        $btn.prop("disabled", true);
        $spinner.addClass("is-active");
        $.post(rrzeFaudirAjax.ajax_url, {
          action: rrzeFaudirAjax.refresh_action,
          security: rrzeFaudirAjax.refresh_nonce,
          post_id: postId,
          person_id: personId
        }).done(function(resp) {
          if (resp && resp.success) {
            var noticeMsg = rrzeFaudirAjax.refresh_success_text;
            if (resp.data && resp.data.message) {
              noticeMsg = resp.data.message;
            }
            $out.addClass("notice notice-success inline").html("<p>" + noticeMsg + "</p>");
            var confirmMsg = rrzeFaudirAjax.refresh_success_text + "\n\n" + rrzeFaudirAjax.refresh_reload_confirm;
            var doReload = window.confirm(confirmMsg);
            if (doReload) {
              window.location.reload();
              return;
            }
          } else {
            var emsg = rrzeFaudirAjax.refresh_unknown_text;
            if (resp && resp.data && resp.data.message) {
              emsg = resp.data.message;
            }
            $out.addClass("notice notice-error inline").html("<p>" + emsg + "</p>");
          }
        }).fail(function() {
          $out.addClass("notice notice-error inline").html("<p>" + rrzeFaudirAjax.refresh_failed_text + "</p>");
        }).always(function() {
          $spinner.removeClass("is-active");
          $btn.prop("disabled", false);
        });
      });
    }
  });
})();
//# sourceMappingURL=rrze-faudir-admin.js.map

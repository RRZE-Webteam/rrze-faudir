/*
* JavaScript Definitions for: 
* Plugin: rrze-faudir
* Version: 2.2.19
*/
jQuery(document).ready(function(o){let a=1;function t(e){o.ajax({url:rrzeFaudirAjax.ajax_url,method:"POST",data:{action:"rrze_faudir_fetch_contacts",security:rrzeFaudirAjax.api_nonce,page:e},success:function(e){e.success?(o("#contacts-list").html(e.data),localStorage.setItem("activeTab","#tab-5")):o("#contacts-list").html("<p>An error occurred while loading contacts.</p>")},error:function(e,a,t){o("#contacts-list").html("<p>An error occurred during the request.</p>")}})}var e=localStorage.getItem("activeTab")||"#tab-1";o(".nav-tab").removeClass("nav-tab-active"),o('a[href="'+e+'"]').addClass("nav-tab-active"),o(".tab-content").hide(),o(e).show(),o(".nav-tab").click(function(e){e.preventDefault(),o(".nav-tab").removeClass("nav-tab-active"),o(this).addClass("nav-tab-active"),o(".tab-content").hide(),o(o(this).attr("href")).show(),localStorage.setItem("activeTab",o(this).attr("href"))}),o(document).on("click",".prev-page",function(e){e.preventDefault(),1<a&&t(--a)}),o(document).on("click",".next-page",function(e){e.preventDefault(),t(++a)}),o("#clear-cache-button").on("click",function(){confirm(rrzeFaudirAjax.confirm_clear_cache)&&o.post(rrzeFaudirAjax.ajax_url,{action:"rrze_faudir_clear_cache",security:rrzeFaudirAjax.api_nonce},function(e){e.success?alert(e.data):alert("Error clearing cache.")})}),o("#search-person-form input").on("keypress",function(e){13===e.which&&(e.preventDefault(),o("#search-person-form").submit())}),o("#search-person-form input").on("input",function(){var e=!0;o('#search-person-form input[type="text"], #search-person-form input[type="email"]').each(function(){""!==o(this).val()&&(e=!1)}),1!=o("#given-name").val().length&&1!=o("#family-name").val().length||(e=!0),o("#search-person-form button").prop("disabled",e)}),o("#search-person-form").on("submit",function(e){e.preventDefault();var e=o("#person-id").val().trim(),a=o("#given-name").val().trim(),t=o("#family-name").val().trim(),r=o("#email").val().trim(),n=o("#include-default-org").is(":checked")?"1":"0";0<e.length||0<a.length||0<t.length||0<r.length?o.ajax({url:rrzeFaudirAjax.ajax_url,method:"POST",data:{action:"rrze_faudir_search_person",security:rrzeFaudirAjax.api_nonce,person_id:e,given_name:a,family_name:t,email:r,include_default_org:n},success:function(e){e.success?o("#contacts-list").html(e.data):o("#contacts-list").html("<p>"+e.data+"</p>")},error:function(e,a,t){o("#contacts-list").html("<p>An error occurred during the request.</p>")}}):o("#contacts-list").html("<p>Please enter a valid search term.</p>")}),o(document).on("click",".add-person",function(){var t=o(this),e=t.data("name"),a=t.data("id"),r=t.data("include-default-org"),n=t.data("functionLabel")||[];t.prop("disabled",!0).html('<span class="dashicons dashicons-update"></span> '+rrzeFaudirAjax.add_text),o.ajax({url:rrzeFaudirAjax.ajax_url,method:"POST",data:{action:"rrze_faudir_create_custom_person",security:rrzeFaudirAjax.api_nonce,person_name:e,person_id:a,include_default_org:r,functions:n},success:function(e){var a;e.success?(a=o("<a>",{href:e.data.edit_url,class:"edit-person button",html:'<span class="dashicons dashicons-edit"></span> '+rrzeFaudirAjax.edit_text}),t.replaceWith(a)):alert("Error creating custom person: "+(e.data||"Unknown error"))},error:function(e,a,t){alert("An error occurred while creating the custom person. Please check the console for more details.")},complete:function(){t.prop("disabled",!1).html("Add")}})}),o("#person_id").on("change",function(){var e=o(this).val();e&&o.ajax({url:customPerson.ajax_url,type:"POST",data:{action:"fetch_person_attributes",nonce:customPerson.nonce,person_id:e},success:function(e){if(e.success){var a=e.data;o("#person_name").val(a.person_name),o("#person_email").val(a.person_email),o("#person_given_name").val(a.person_given_name),o("#person_family_name").val(a.person_family_name),o("#person_title").val(a.person_title);let t=o(".contacts-wrapper");t.empty(),a.organizations.forEach((e,a)=>{e=`
                            <div class="organization-block">
                                <div class="organization-header">
                                    <h4>Organization ${a+1}</h4>
                                </div>
                                <input type="text" name="person_contacts[${a}][organization]" value="${e.organization}" class="widefat" readonly />
                                <div class="functions-wrapper">
                                    <h5>Functions</h5>
                                    ${e.functions.map(e=>`
                                        <div class="function-block">
                                            <input type="text" name="person_contacts[${a}][functions][]" value="${e}" class="widefat" readonly />
                                        </div>
                                    `).join("")}
                                </div>
                            </div>
                        `;t.append(e)})}else alert("Error fetching person data: "+(e.data||"Unknown error"))},error:function(e,a,t){alert("Error fetching person data. Please check the console for details.")}})}),o("#search-org-form").on("submit",function(e){e.preventDefault();e=o("#org-search").val().trim();0<e.length?o.ajax({url:rrzeFaudirAjax.ajax_url,method:"POST",data:{action:"rrze_faudir_search_org",security:rrzeFaudirAjax.api_nonce,search_term:e},success:function(e){e.success?o("#organizations-list").html(e.data):o("#organizations-list").html("<p>"+e.data+"</p>")},error:function(e,a,t){o("#organizations-list").html("<p>An error occurred during the request.</p>")}}):o("#organizations-list").html("<p>Please enter a search term.</p>")}),o("#search-org-form input").on("keypress",function(e){13===e.which&&(e.preventDefault(),o("#search-org-form").submit())})}),jQuery(document).ready(function(e){var a='[faudir identifier="'+e("#hidden-person-id").val()+'"]';e("#generated-shortcode").val(a),e("#copy-shortcode").on("click",function(){e("#generated-shortcode").select(),document.execCommand("copy"),alert("Shortcode copied to clipboard!")})});
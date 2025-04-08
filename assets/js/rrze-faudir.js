/*
* JavaScript Definitions for: 
* Plugin: rrze-faudir
* Version: 2.2.14
*/
jQuery(document).ready(function(a){a("#person_id").on("change",function(){var n=a(this).val();n&&a.ajax({url:customPerson.ajax_url,type:"POST",data:{action:"fetch_person_attributes",person_id:n,nonce:customPerson.nonce},success:function(n){var e;n.success?(e=n.data,a("#person_name").val(e.person_name),a("#person_email").val(e.person_email),a("#person_given_name").val(e.person_given_name),a("#person_family_name").val(e.person_family_name),a("#person_title").val(e.person_title),a("#person_organization").val(e.person_organization),a("#person_function").val(e.person_function)):alert(n.data)}})})});
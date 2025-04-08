(()=>{"use strict";var e={n:t=>{var r=t&&t.__esModule?()=>t.default:()=>t;return e.d(r,{a:r}),r},d:(t,r)=>{for(var i in r)e.o(r,i)&&!e.o(t,i)&&Object.defineProperty(t,i,{enumerable:!0,get:r[i]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const t=window.wp.blocks,r=window.ReactJSXRuntime,i=window.wp.i18n,s=window.wp.blockEditor,o=window.wp.components,l=window.wp.element,a=window.wp.serverSideRender;var d=e.n(a);function n({attributes:e}){var t;return(0,r.jsx)(d(),{block:"rrze-faudir/block",attributes:Object.assign({},e.role?Object.assign(Object.assign({role:e.role},e.orgnr&&{orgnr:e.orgnr}),{selectedFormat:e.selectedFormat,selectedFields:e.selectedFields.length>0?e.selectedFields:null,hideFields:e.hideFields,url:e.url,sort:e.sort,format_displayname:e.format_displayname}):e.selectedCategory?{selectedCategory:e.selectedCategory,selectedPersonIds:e.selectedPersonIds,selectedFormat:e.selectedFormat,selectedFields:e.selectedFields.length>0?e.selectedFields:null,hideFields:e.hideFields,url:e.url,sort:e.sort,format_displayname:e.format_displayname}:(null===(t=e.selectedPersonIds)||void 0===t?void 0:t.length)>0?{selectedPersonIds:e.selectedPersonIds,selectedFields:e.selectedFields.length>0?e.selectedFields:null,hideFields:e.hideFields,selectedFormat:e.selectedFormat,url:e.url,sort:e.sort,format_displayname:e.format_displayname}:e.orgnr?{orgnr:e.orgnr,selectedFormat:e.selectedFormat,selectedFields:e.selectedFields.length>0?e.selectedFields:null,hideFields:e.hideFields,url:e.url,sort:e.sort,format_displayname:e.format_displayname}:{selectedFormat:e.selectedFormat,selectedFields:e.selectedFields.length>0?e.selectedFields:null,hideFields:e.hideFields,url:e.url,sort:e.sort,format_displayname:e.format_displayname})})}const c=window.wp.apiFetch;var u=e.n(c);const f={image:(0,i.__)("Image","rrze-faudir"),displayName:(0,i.__)("Display Name","rrze-faudir"),honorificPrefix:(0,i.__)("Academic Title","rrze-faudir"),givenName:(0,i.__)("First Name","rrze-faudir"),familyName:(0,i.__)("Last Name","rrze-faudir"),honorificSuffix:(0,i.__)("Academic Suffix","rrze-faudir"),titleOfNobility:(0,i.__)("Title of Nobility","rrze-faudir"),email:(0,i.__)("Email","rrze-faudir"),phone:(0,i.__)("Phone","rrze-faudir"),organization:(0,i.__)("Organization","rrze-faudir"),jobTitle:(0,i.__)("Jobtitle","rrze-faudir"),url:(0,i.__)("URL","rrze-faudir"),content:(0,i.__)("Content","rrze-faudir"),teasertext:(0,i.__)("Teasertext","rrze-faudir"),socialmedia:(0,i.__)("Social Media and Websites","rrze-faudir"),room:(0,i.__)("Room","rrze-faudir"),floor:(0,i.__)("Floor","rrze-faudir"),address:(0,i.__)("Address","rrze-faudir"),street:(0,i.__)("Street","rrze-faudir"),zip:(0,i.__)("ZIP Code","rrze-faudir"),city:(0,i.__)("City","rrze-faudir"),faumap:(0,i.__)("FAU Map","rrze-faudir"),officehours:(0,i.__)("Office Hours","rrze-faudir"),consultationhours:(0,i.__)("Consultation Hours","rrze-faudir")},m={card:["image","displayName","honorificPrefix","givenName","familyName","honorificSuffix","email","phone","jobTitle","socialmedia","titleOfNobility","organization"],table:["image","displayName","honorificPrefix","givenName","familyName","honorificSuffix","email","phone","url","socialmedia","titleOfNobility","floor","room","address","organization"],list:["displayName","honorificPrefix","givenName","familyName","honorificSuffix","email","phone","url","teasertext","titleOfNobility","address"],compact:Object.keys(f),page:Object.keys(f)},_={image:"image",displayname:"displayName",honorificPrefix:"honorificPrefix",givenName:"givenName",familyName:"familyName",honorificSuffix:"honorificSuffix",titleOfNobility:"titleOfNobility",email:"email",phone:"phone",organization:"organization",jobTitle:"jobTitle",url:"url",content:"content",teasertext:"teasertext",socialmedia:"socialmedia",room:"room",floor:"floor",street:"street",zip:"zip",city:"city",faumap:"faumap",officehours:"officehours",consultationhours:"consultationhours",address:"address"};function g({attributes:e,setAttributes:t}){return(0,r.jsx)(o.TextControl,{label:(0,i.__)("Organization Number","rrze-faudir"),value:e.orgnr,onChange:e=>{t({orgnr:e})},type:"text",help:(0,i.__)("Please enter at least 10 digits.","rrze-faudir")})}function p({isLoadingPosts:e,posts:t,selectedPosts:s,togglePostSelection:l}){return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)("h4",{children:(0,i.__)("Select Persons","rrze-faudir")}),e?(0,r.jsx)("p",{children:(0,i.__)("Loading persons...","rrze-faudir")}):t.length>0?(0,r.jsx)(r.Fragment,{children:t.map((e=>(0,r.jsx)(o.CheckboxControl,{label:e.title.rendered,checked:Array.isArray(s)&&s.includes(e.id),onChange:()=>l(e.id)},e.id)))}):(0,r.jsx)("p",{children:(0,i.__)("No posts available.","rrze-faudir")})]})}function h({categories:e,selectedCategory:t,selectedPosts:s,selectedPersonIds:l,setAttributes:a}){return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)("h4",{children:(0,i.__)("Select Category","rrze-faudir")}),e.map((e=>(0,r.jsx)(o.CheckboxControl,{label:e.name,checked:t===e.name,onChange:()=>{const r=t===e.name?"":e.name;a({selectedCategory:r,selectedPosts:""===r?[]:s,selectedPersonIds:""===r?[]:l})}},e.id)))]})}function y({attributes:e,setAttributes:t}){const{selectedFormat:s}=e;return(0,r.jsx)(o.SelectControl,{label:(0,i.__)("Select Format","rrze-faudir"),value:s||"list",options:[{value:"list",label:(0,i.__)("List","rrze-faudir")},{value:"table",label:(0,i.__)("Table","rrze-faudir")},{value:"card",label:(0,i.__)("Card","rrze-faudir")},{value:"compact",label:(0,i.__)("Compact","rrze-faudir")},{value:"page",label:(0,i.__)("Page","rrze-faudir")}],onChange:r=>{t({selectedFormat:r}),e.selectedFields&&0!==e.selectedFields.length||u()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{if(null==e?void 0:e.default_output_fields){const i=m[r]||[],s=e.default_output_fields.filter((e=>i.includes(e)));t({selectedFields:s})}})).catch((e=>{console.error("Error fetching default fields:",e)}))}})}function b({attributes:e,setAttributes:t}){const{selectedFormat:s,selectedFields:l}=e;return(0,r.jsx)(r.Fragment,{children:Object.keys(m).map((a=>s===a?(0,r.jsxs)("div",{children:[(0,r.jsx)("h4",{children:(0,i.__)("Select Fields","rrze-faudir")}),m[a].map((i=>(0,r.jsx)("div",{style:{marginBottom:"8px"},children:(0,r.jsx)(o.CheckboxControl,{label:String(f[i]),checked:l.includes(i),onChange:()=>(r=>{const i=l.includes(r);let s,o=e.hideFields||[];const a=["personalTitle","givenName","familyName","honorificSuffix","titleOfNobility"];"displayName"===r?i?(s=l.filter((e=>!a.includes(e)&&"displayName"!==e)),o=[...o,"displayName",...a]):(s=[...l.filter((e=>!a.includes(e))),"displayName",...a],o=o.filter((e=>!a.includes(e)&&"displayName"!==e))):a.includes(r)?i?(s=l.filter((e=>e!==r&&"displayName"!==e)),o=[...o,r]):(s=[...l.filter((e=>"displayName"!==e)),r],o=o.filter((e=>e!==r))):i?(s=l.filter((e=>e!==r)),o=[...o,r]):(s=[...l,r],o=o.filter((e=>e!==r))),t({selectedFields:s,hideFields:o})})(i)})},i)))]},a):null))})}function x({attributes:e,setAttributes:t}){const{format_displayname:s}=e;return(0,r.jsx)(o.TextControl,{label:(0,i.__)("Change display format","rrze-faudir"),value:s,onChange:e=>{t({format_displayname:e})},type:"text"})}const z=JSON.parse('{"UU":"rrze-faudir/block"}');(0,t.registerBlockType)(z.UU,{edit:function({attributes:e,setAttributes:t}){var a;const[d,c]=(0,l.useState)([]),[f,m]=(0,l.useState)([]),[z,F]=(0,l.useState)(!1),[v,j]=(0,l.useState)(null),P=(0,s.useBlockProps)(),{selectedCategory:C="",selectedPosts:N=[],showCategory:w=!1,showPosts:S=!1,selectedPersonIds:O=[],selectedFormat:k="compact",selectedFields:A=[],role:T="",orgnr:I="",format_displayname:E="",sort:B="familyName"}=e;return(0,l.useEffect)((()=>{e.selectedFields&&0!==e.selectedFields.length||u()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{if(console.log("DATA SETTINGS in component mount",e),null==e?void 0:e.default_output_fields){const r=e.default_output_fields.map((e=>_[e])).filter((e=>void 0!==e));t({selectedFields:r})}})).catch((e=>{console.error("Error fetching default fields:",e)}))}),[]),(0,l.useEffect)((()=>{u()({path:"/wp/v2/custom_taxonomy?per_page=100"}).then((e=>{c(e)})).catch((e=>{console.error("Error fetching categories:",e)}))}),[]),(0,l.useEffect)((()=>{u()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{var t;(null===(t=null==e?void 0:e.default_organization)||void 0===t?void 0:t.orgnr)&&j(e.default_organization)})).catch((e=>{console.error("Error fetching default organization number:",e)}))}),[]),(0,l.useEffect)((()=>{F(!0);const e={per_page:100,_fields:"id,title,meta",orderby:"title",order:"asc"};C&&(e.custom_taxonomy=C),u()({path:"/wp/v2/custom_person?per_page=100",params:e}).then((e=>{if(m(e),C){const r=e.map((e=>e.id)),i=e.map((e=>{var t;return null===(t=e.meta)||void 0===t?void 0:t.person_id})).filter(Boolean);t({selectedPosts:r,selectedPersonIds:i})}F(!1)})).catch((e=>{console.error("Error fetching posts:",e),F(!1)}))}),[C,I]),(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.InspectorControls,{children:(0,r.jsxs)(o.PanelBody,{title:(0,i.__)("Settings","rrze-faudir"),children:[(0,r.jsx)(o.ToggleControl,{label:(0,i.__)("Show Category","rrze-faudir"),checked:w,onChange:()=>t({showCategory:!w})}),w&&(0,r.jsx)(h,{categories:d,selectedCategory:C,selectedPosts:N,selectedPersonIds:O,setAttributes:t}),(0,r.jsx)(o.ToggleControl,{label:(0,i.__)("Show Persons","rrze-faudir"),checked:S,onChange:()=>t({showPosts:!S})}),S&&(0,r.jsx)(p,{isLoadingPosts:z,posts:f,selectedPosts:N,togglePostSelection:e=>{const r=N.includes(e)?N.filter((t=>t!==e)):[...N,e],i=r.map((e=>{var t;const r=f.find((t=>t.id===e));return(null===(t=null==r?void 0:r.meta)||void 0===t?void 0:t.person_id)||null})).filter(Boolean);t({selectedPosts:r,selectedPersonIds:i})}}),(0,r.jsx)(y,{attributes:e,setAttributes:t}),(0,r.jsx)(b,{attributes:e,setAttributes:t}),(0,r.jsx)(g,{attributes:e,setAttributes:t}),v&&!I&&(0,r.jsxs)("div",{style:{padding:"8px",backgroundColor:"#f0f0f0",borderLeft:"4px solid #007cba",marginTop:"5px",marginBottom:"15px"},children:[(0,r.jsx)("span",{className:"dashicons dashicons-info",style:{marginRight:"5px"}}),(0,i.__)("Default organization will be used if empty.","rrze-faudir")]}),(0,r.jsx)(o.TextControl,{label:(0,i.__)("Role","rrze-faudir"),value:T,onChange:e=>t({role:e}),type:"text"}),(0,r.jsx)(x,{attributes:e,setAttributes:t})]})}),(0,r.jsx)("div",Object.assign({},P,{children:(null===(a=e.selectedPersonIds)||void 0===a?void 0:a.length)>0||e.selectedCategory||e.orgnr||e.role&&(e.orgnr||v)?(0,r.jsx)(n,{attributes:e}):(0,r.jsx)("div",{style:{padding:"20px",backgroundColor:"#f8f9fa",textAlign:"center"},children:(0,r.jsx)("p",{children:e.role?v?(0,i.__)("Using default organization.","rrze-faudir"):(0,i.__)("Please configure a default organization in the plugin settings or add an organization ID to display results.","rrze-faudir"):(0,i.__)("Please select persons or a category to display using the sidebar controls.","rrze-faudir")})})}))]})},save:function(){return null}})})();
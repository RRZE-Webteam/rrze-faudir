(()=>{"use strict";var e={n:r=>{var t=r&&r.__esModule?()=>r.default:()=>r;return e.d(t,{a:t}),t},d:(r,t)=>{for(var i in t)e.o(t,i)&&!e.o(r,i)&&Object.defineProperty(r,i,{enumerable:!0,get:t[i]})},o:(e,r)=>Object.prototype.hasOwnProperty.call(e,r)};const r=window.wp.blocks,t=window.wp.i18n,i=window.wp.blockEditor,a=window.wp.components,o=window.wp.element,l=window.wp.serverSideRender;var s=e.n(l);const d=window.wp.apiFetch;var n=e.n(d);const c=window.ReactJSXRuntime,u=JSON.parse('{"UU":"rrze-faudir/block"}');(0,r.registerBlockType)(u.UU,{edit:function({attributes:e,setAttributes:r}){const[l,d]=(0,o.useState)([]),[u,f]=(0,o.useState)([]),[m,_]=(0,o.useState)(!0),[p,g]=(0,o.useState)(!1),[h,z]=(0,o.useState)(null),[y,b]=(0,o.useState)(0),x=(0,i.useBlockProps)(),{selectedCategory:F="",selectedPosts:N=[],showCategory:C="",showPosts:v="",selectedPersonIds:j="",selectedFormat:w="compact",selectedFields:P=[],groupId:S="",function:I="",orgnr:k="",url:T="",hideFields:O=[],sort:E="familyName"}=e,A={image:(0,t.__)("Image","rrze-faudir"),displayName:(0,t.__)("Display Name","rrze-faudir"),honorificPrefix:(0,t.__)("Academic Title","rrze-faudir"),givenName:(0,t.__)("First Name","rrze-faudir"),familyName:(0,t.__)("Last Name","rrze-faudir"),honorificSuffix:(0,t.__)("Academic Suffix","rrze-faudir"),titleOfNobility:(0,t.__)("Title of Nobility","rrze-faudir"),email:(0,t.__)("Email","rrze-faudir"),phone:(0,t.__)("Phone","rrze-faudir"),organization:(0,t.__)("Organization","rrze-faudir"),jobTitle:(0,t.__)("Jobtitle","rrze-faudir"),url:(0,t.__)("URL","rrze-faudir"),content:(0,t.__)("Content","rrze-faudir"),teasertext:(0,t.__)("Teasertext","rrze-faudir"),socialmedia:(0,t.__)("Social Media and Websites","rrze-faudir"),room:(0,t.__)("Room","rrze-faudir"),floor:(0,t.__)("Floor","rrze-faudir"),address:(0,t.__)("Address","rrze-faudir"),street:(0,t.__)("Street","rrze-faudir"),zip:(0,t.__)("ZIP Code","rrze-faudir"),city:(0,t.__)("City","rrze-faudir"),faumap:(0,t.__)("FAU Map","rrze-faudir"),officehours:(0,t.__)("Office Hours","rrze-faudir"),consultationhours:(0,t.__)("Consultation Hours","rrze-faudir")},B={card:["image","displayName","honorificPrefix","givenName","familyName","honorificSuffix","email","phone","jobTitle","socialmedia","titleOfNobility","organization"],table:["image","displayName","honorificPrefix","givenName","familyName","honorificSuffix","email","phone","url","socialmedia","titleOfNobility","floor","room","address","organization"],list:["displayName","honorificPrefix","givenName","familyName","honorificSuffix","email","phone","url","teasertext","titleOfNobility","address"],kompakt:Object.keys(A),page:Object.keys(A)};(0,o.useEffect)((()=>{e.selectedFields&&0!==e.selectedFields.length||n()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{if(e?.default_output_fields){const t={image:"image",displayname:"displayName",honorificPrefix:"honorificPrefix",givenName:"givenName",familyName:"familyName",honorificSuffix:"honorificSuffix",titleOfNobility:"titleOfNobility",email:"email",phone:"phone",organization:"organization",jobTitle:"jobTitle",url:"url",teasertext:"teasertext",socialmedia:"socialmedia",room:"room",floor:"floor",street:"street",zip:"zip",city:"city",faumap:"faumap",officehours:"officehours",consultationhours:"consultationhours",address:"address"},i=e.default_output_fields.map((e=>t[e])).filter((e=>void 0!==e));r({selectedFields:i})}})).catch((e=>{console.error("Error fetching default fields:",e)}))}),[]),(0,o.useEffect)((()=>{n()({path:"/wp/v2/custom_taxonomy?per_page=100"}).then((e=>{d(e),_(!1)})).catch((e=>{console.error("Error fetching categories:",e),_(!1)}))}),[]),(0,o.useEffect)((()=>{n()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{e?.default_organization?.orgnr&&z(e.default_organization)})).catch((e=>{console.error("Error fetching default organization number:",e)}))}),[]),(0,o.useEffect)((()=>{g(!0);const e={per_page:100,_fields:"id,title,meta",orderby:"title",order:"asc"};F&&(e.custom_taxonomy=F),n()({path:"/wp/v2/custom_person?per_page=100",params:e}).then((e=>{if(f(e),setDisplayedPosts(e.slice(0,100)),F){const t=e.map((e=>e.id)),i=e.map((e=>e.meta?.person_id)).filter(Boolean);r({selectedPosts:t,selectedPersonIds:i})}g(!1)})).catch((e=>{console.error("Error fetching posts:",e),g(!1)}))}),[F,I,k]);const L={selectedPersonIds:e.selectedPersonIds,selectedFields:e.selectedFields,selectedFormat:e.selectedFormat,selectedCategory:e.selectedCategory,groupId:e.groupId,function:e.function,...e.orgnr&&{orgnr:e.orgnr},url:e.url},[U,R]=(0,o.useState)(0);(0,o.useEffect)((()=>{const e=setTimeout((()=>{R((e=>e+1))}),300);return()=>clearTimeout(e)}),[...Object.values(L),E]),(0,o.useMemo)((()=>u.map((e=>({id:e.id,title:e.title.rendered,personId:e.meta?.person_id})))),[u]);const D=function(e){const[r,t]=(0,o.useState)(e);return(0,o.useEffect)((()=>{const r=setTimeout((()=>{t(e)}),500);return()=>{clearTimeout(r)}}),[e,500]),r}(y);return(0,c.jsxs)(c.Fragment,{children:[(0,c.jsx)(i.InspectorControls,{children:(0,c.jsxs)(a.PanelBody,{title:(0,t.__)("Settings","rrze-faudir"),children:[(0,c.jsx)(a.ToggleControl,{label:(0,t.__)("Show Category","rrze-faudir"),checked:C,onChange:()=>r({showCategory:!C})}),C&&(0,c.jsxs)(c.Fragment,{children:[(0,c.jsx)("h4",{children:(0,t.__)("Select Category","rrze-faudir")}),l.map((e=>(0,c.jsx)(a.CheckboxControl,{label:e.name,checked:F===e.name,onChange:()=>{const t=F===e.name?"":e.name;r({selectedCategory:t,selectedPosts:""===t?[]:N,selectedPersonIds:""===t?[]:j})}},e.id)))]}),(0,c.jsx)(a.ToggleControl,{label:(0,t.__)("Show Persons","rrze-faudir"),checked:v,onChange:()=>r({showPosts:!v})}),v&&(0,c.jsxs)(c.Fragment,{children:[(0,c.jsx)("h4",{children:(0,t.__)("Select Persons","rrze-faudir")}),p?(0,c.jsx)("p",{children:(0,t.__)("Loading persons...","rrze-faudir")}):u.length>0?(0,c.jsx)(c.Fragment,{children:u.map((e=>(0,c.jsx)(a.CheckboxControl,{label:e.title.rendered,checked:Array.isArray(N)&&N.includes(e.id),onChange:()=>(e=>{const t=N.includes(e)?N.filter((r=>r!==e)):[...N,e],i=t.map((e=>{const r=u.find((r=>r.id===e));return r?.meta?.person_id||null})).filter(Boolean);r({selectedPosts:t,selectedPersonIds:i}),b((e=>e+1))})(e.id,e.meta)},e.id)))}):(0,c.jsx)("p",{children:(0,t.__)("No posts available.","rrze-faudir")})]}),(0,c.jsx)(a.SelectControl,{label:(0,t.__)("Select Format","rrze-faudir"),value:w||"list",options:[{value:"list",label:(0,t.__)("List","rrze-faudir")},{value:"table",label:(0,t.__)("Table","rrze-faudir")},{value:"card",label:(0,t.__)("Card","rrze-faudir")},{value:"compact",label:(0,t.__)("Compact","rrze-faudir")},{value:"page",label:(0,t.__)("Page","rrze-faudir")}],onChange:t=>{r({selectedFormat:t}),b((e=>e+1)),e.selectedFields&&0!==e.selectedFields.length||n()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{if(e?.default_output_fields){const i=B[t]||[],a=e.default_output_fields.filter((e=>i.includes(e)));r({selectedFields:a})}})).catch((e=>{console.error("Error fetching default fields:",e)}))}}),Object.keys(B).map((i=>w===i?(0,c.jsxs)("div",{children:[(0,c.jsx)("h4",{children:(0,t.__)("Select Fields","rrze-faudir")}),B[i].map((t=>(0,c.jsx)("div",{style:{marginBottom:"8px"},children:(0,c.jsx)(a.CheckboxControl,{label:(0,c.jsx)(c.Fragment,{children:A[t]}),checked:P.includes(t),onChange:()=>(t=>{const i=P.includes(t);let a,o=e.hideFields||[];const l=["personalTitle","givenName","familyName","honorificSuffix","titleOfNobility"];"displayName"===t?i?(a=P.filter((e=>!l.includes(e)&&"displayName"!==e)),o=[...o,"displayName",...l]):(a=[...P.filter((e=>!l.includes(e))),"displayName",...l],o=o.filter((e=>!l.includes(e)&&"displayName"!==e))):l.includes(t)?i?(a=P.filter((e=>e!==t&&"displayName"!==e)),o=[...o,t]):(a=[...P.filter((e=>"displayName"!==e)),t],o=o.filter((e=>e!==t))):i?(a=P.filter((e=>e!==t)),o=[...o,t]):(a=[...P,t],o=o.filter((e=>e!==t))),r({selectedFields:a,hideFields:o}),b((e=>e+1))})(t)})},t)))]},i):null)),(0,c.jsx)(a.TextControl,{label:(0,t.__)("Group Id","rrze-faudir"),value:S,onChange:e=>r({groupId:e})}),(0,c.jsx)(a.TextControl,{label:(0,t.__)("Organization Number","rrze-faudir"),value:k,onChange:e=>{r({orgnr:e})}}),h&&!k&&(0,c.jsxs)("div",{style:{padding:"8px",backgroundColor:"#f0f0f0",borderLeft:"4px solid #007cba",marginTop:"5px",marginBottom:"15px"},children:[(0,c.jsx)("span",{className:"dashicons dashicons-info",style:{marginRight:"5px"}}),(0,t.__)("Default organization will be used if empty.","rrze-faudir")]}),(0,c.jsx)(a.SelectControl,{label:(0,t.__)("Sort by","rrze-faudir"),value:E,options:[{value:"familyName",label:(0,t.__)("Last Name","rrze-faudir")},{value:"title_familyName",label:(0,t.__)("Title and Last Name","rrze-faudir")},{value:"head_first",label:(0,t.__)("Head of Department First","rrze-faudir")},{value:"prof_first",label:(0,t.__)("Professors First","rrze-faudir")},{value:"identifier_order",label:(0,t.__)("Identifier Order","rrze-faudir")}],onChange:e=>{r({sort:e}),b((e=>e+1))}})]})}),(0,c.jsx)("div",{...x,children:e.selectedPersonIds?.length>0||e.selectedCategory||e.function&&(e.orgnr||h)?(0,c.jsx)(c.Fragment,{children:(0,c.jsx)(s(),{block:"rrze-faudir/block",attributes:{...e.function?{function:e.function,...e.orgnr&&{orgnr:e.orgnr},selectedFormat:e.selectedFormat,selectedFields:e.selectedFields.length>0?e.selectedFields:null,hideFields:e.hideFields,url:e.url,sort:e.sort}:e.selectedCategory?{selectedCategory:e.selectedCategory,selectedPersonIds:e.selectedPersonIds,selectedFormat:e.selectedFormat,selectedFields:e.selectedFields.length>0?e.selectedFields:null,hideFields:e.hideFields,url:e.url,groupId:e.groupId,sort:e.sort}:{selectedPersonIds:e.selectedPersonIds,selectedFields:e.selectedFields.length>0?e.selectedFields:null,hideFields:e.hideFields,selectedFormat:e.selectedFormat,url:e.url,groupId:e.groupId,sort:e.sort}}},D)}):(0,c.jsx)("div",{style:{padding:"20px",backgroundColor:"#f8f9fa",textAlign:"center"},children:(0,c.jsx)("p",{children:e.function?h?(0,t.__)("Using default organization.","rrze-faudir"):(0,t.__)("Please configure a default organization in the plugin settings or add an organization ID to display results.","rrze-faudir"):(0,t.__)("Please select persons or a category to display using the sidebar controls.","rrze-faudir")})})})]})},save:()=>null})})();
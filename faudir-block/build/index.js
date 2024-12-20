(()=>{"use strict";var e={n:t=>{var r=t&&t.__esModule?()=>t.default:()=>t;return e.d(r,{a:r}),r},d:(t,r)=>{for(var a in r)e.o(r,a)&&!e.o(t,a)&&Object.defineProperty(t,a,{enumerable:!0,get:r[a]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const t=window.wp.blocks,r=window.wp.i18n,a=window.wp.blockEditor,l=window.wp.components,i=window.wp.element,o=window.wp.serverSideRender;var s=e.n(o);const n=window.wp.apiFetch;var d=e.n(n);const u=window.ReactJSXRuntime,c=JSON.parse('{"UU":"rrze-faudir/block"}');(0,t.registerBlockType)(c.UU,{edit:function({attributes:e,setAttributes:t}){const[o,n]=(0,i.useState)([]),[c,f]=(0,i.useState)([]),[_,p]=(0,i.useState)(!0),[m,g]=(0,i.useState)(!1),[h,x]=(0,i.useState)(""),[z,b]=(0,i.useState)(null),[y,F]=(0,i.useState)(0),C=(0,a.useBlockProps)(),{selectedCategory:v="",selectedPosts:k=[],showCategory:w="",showPosts:N="",selectedPersonIds:T="",selectedFormat:j="kompakt",selectedFields:S=[],groupId:P="",function:I="",orgnr:O="",url:E="",buttonText:B="",hideFields:A=[],sort:D="last_name"}=e,L={displayName:(0,r.__)("Display Name","rrze-faudir"),personalTitle:(0,r.__)("Academic Title","rrze-faudir"),givenName:(0,r.__)("First Name","rrze-faudir"),familyName:(0,r.__)("Last Name","rrze-faudir"),personalTitleSuffix:(0,r.__)("Academic Suffix","rrze-faudir"),titleOfNobility:(0,r.__)("Title of Nobility","rrze-faudir"),email:(0,r.__)("Email","rrze-faudir"),phone:(0,r.__)("Phone","rrze-faudir"),organization:(0,r.__)("Organization","rrze-faudir"),function:(0,r.__)("Function","rrze-faudir"),url:(0,r.__)("Url","rrze-faudir"),kompaktButton:(0,r.__)("Kompakt Button","rrze-faudir"),content:(0,r.__)("Content","rrze-faudir"),teasertext:(0,r.__)("Teasertext","rrze-faudir"),socialmedia:(0,r.__)("Social Media","rrze-faudir"),workplaces:(0,r.__)("Workplaces","rrze-faudir"),room:(0,r.__)("Room","rrze-faudir"),floor:(0,r.__)("Floor","rrze-faudir"),street:(0,r.__)("Street","rrze-faudir"),zip:(0,r.__)("Zip","rrze-faudir"),city:(0,r.__)("City","rrze-faudir"),faumap:(0,r.__)("Fau Map","rrze-faudir"),officehours:(0,r.__)("Office Hours","rrze-faudir"),consultationhours:(0,r.__)("Consultation Hours","rrze-faudir")},U={card:["displayName","personalTitle","givenName","familyName","personalTitleSuffix","email","phone","function","socialmedia","titleOfNobility"],table:["displayName","personalTitle","givenName","familyName","personalTitleSuffix","email","phone","url","socialmedia","titleOfNobility"],list:["displayName","personalTitle","givenName","familyName","personalTitleSuffix","email","phone","url","teasertext","titleOfNobility"],kompakt:Object.keys(L),page:Object.keys(L)};(0,i.useEffect)((()=>{e.selectedFields&&0!==e.selectedFields.length||d()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{if(e?.default_output_fields){const r={display_name:"displayName",academic_title:"personalTitle",first_name:"givenName",last_name:"familyName",academic_suffix:"personalTitleSuffix",nobility_title:"titleOfNobility",email:"email",phone:"phone",organization:"organization",function:"function",url:"url",kompaktButton:"kompaktButton",content:"content",teasertext:"teasertext",socialmedia:"socialmedia",workplaces:"workplaces",room:"room",floor:"floor",street:"street",zip:"zip",city:"city",faumap:"faumap",officehours:"officehours",consultationhours:"consultationhours"},a=e.default_output_fields.map((e=>r[e])).filter((e=>void 0!==e));t({selectedFields:a})}})).catch((e=>{console.error("Error fetching default fields:",e)}))}),[]),(0,i.useEffect)((()=>{d()({path:"/wp/v2/custom_taxonomy?per_page=100"}).then((e=>{n(e),p(!1)})).catch((e=>{console.error("Error fetching categories:",e),p(!1)}))}),[]),(0,i.useEffect)((()=>{d()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{e?.default_organization?.orgnr&&b(e.default_organization)})).catch((e=>{console.error("Error fetching default organization number:",e)}))}),[]),(0,i.useEffect)((()=>{g(!0);const e={per_page:100,_fields:"id,title,meta",orderby:"title",order:"asc"};v&&(e.custom_taxonomy=v),d()({path:"/wp/v2/custom_person?per_page=100",params:e}).then((e=>{if(f(e),setDisplayedPosts(e.slice(0,100)),v){const r=e.map((e=>e.id)),a=e.map((e=>e.meta?.person_id)).filter(Boolean);t({selectedPosts:r,selectedPersonIds:a})}g(!1)})).catch((e=>{console.error("Error fetching posts:",e),g(!1)}))}),[v,I,O]),(0,i.useEffect)((()=>{B||d()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{e?.business_card_title&&(x(e.business_card_title),t({buttonText:e.business_card_title}))})).catch((e=>{console.error("Error fetching button text:",e)}))}),[]);const R={selectedPersonIds:e.selectedPersonIds,selectedFields:e.selectedFields,selectedFormat:e.selectedFormat,selectedCategory:e.selectedCategory,groupId:e.groupId,function:e.function,...e.orgnr&&{orgnr:e.orgnr},url:e.url},[M,H]=(0,i.useState)(0);(0,i.useEffect)((()=>{const e=setTimeout((()=>{H((e=>e+1))}),300);return()=>clearTimeout(e)}),[...Object.values(R),D]),(0,i.useMemo)((()=>c.map((e=>({id:e.id,title:e.title.rendered,personId:e.meta?.person_id})))),[c]);const J=function(e){const[t,r]=(0,i.useState)(e);return(0,i.useEffect)((()=>{const t=setTimeout((()=>{r(e)}),500);return()=>{clearTimeout(t)}}),[e,500]),t}(y);return(0,u.jsxs)(u.Fragment,{children:[(0,u.jsx)(a.InspectorControls,{children:(0,u.jsxs)(l.PanelBody,{title:(0,r.__)("Settings","rrze-faudir"),children:[(0,u.jsx)(l.ToggleControl,{label:(0,r.__)("Show Category","rrze-faudir"),checked:w,onChange:()=>t({showCategory:!w})}),w&&(0,u.jsxs)(u.Fragment,{children:[(0,u.jsx)("h4",{children:(0,r.__)("Select Category","rrze-faudir")}),o.map((e=>(0,u.jsx)(l.CheckboxControl,{label:e.name,checked:v===e.name,onChange:()=>{const r=v===e.name?"":e.name;t({selectedCategory:r,selectedPosts:""===r?[]:k,selectedPersonIds:""===r?[]:T})}},e.id)))]}),(0,u.jsx)(l.ToggleControl,{label:(0,r.__)("Show Persons","rrze-faudir"),checked:N,onChange:()=>t({showPosts:!N})}),N&&(0,u.jsxs)(u.Fragment,{children:[(0,u.jsx)("h4",{children:(0,r.__)("Select Persons","rrze-faudir")}),m?(0,u.jsx)("p",{children:(0,r.__)("Loading persons...","rrze-faudir")}):c.length>0?(0,u.jsx)(u.Fragment,{children:c.map((e=>(0,u.jsx)(l.CheckboxControl,{label:e.title.rendered,checked:Array.isArray(k)&&k.includes(e.id),onChange:()=>(e=>{const r=k.includes(e)?k.filter((t=>t!==e)):[...k,e],a=r.map((e=>{const t=c.find((t=>t.id===e));return t?.meta?.person_id||null})).filter(Boolean);t({selectedPosts:r,selectedPersonIds:a}),F((e=>e+1))})(e.id,e.meta)},e.id)))}):(0,u.jsx)("p",{children:(0,r.__)("No posts available.","rrze-faudir")})]}),(0,u.jsx)(l.SelectControl,{label:(0,r.__)("Select Format","rrze-faudir"),value:j||"list",options:[{value:"list",label:(0,r.__)("List","rrze-faudir")},{value:"table",label:(0,r.__)("Table","rrze-faudir")},{value:"card",label:(0,r.__)("Card","rrze-faudir")},{value:"kompakt",label:(0,r.__)("Kompakt","rrze-faudir")},{value:"page",label:(0,r.__)("Page","rrze-faudir")}],onChange:r=>{t({selectedFormat:r}),F((e=>e+1)),e.selectedFields&&0!==e.selectedFields.length||d()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{if(e?.default_output_fields){const a=U[r]||[],l=e.default_output_fields.filter((e=>a.includes(e)));t({selectedFields:l})}})).catch((e=>{console.error("Error fetching default fields:",e)}))}}),Object.keys(U).map((a=>j===a?(0,u.jsxs)("div",{children:[(0,u.jsx)("h4",{children:(0,r.__)("Select Fields","rrze-faudir")}),U[a].map((r=>(0,u.jsx)("div",{style:{marginBottom:"8px"},children:(0,u.jsx)(l.CheckboxControl,{label:(0,u.jsx)(u.Fragment,{children:L[r]}),checked:S.includes(r),onChange:()=>(r=>{const a=S.includes(r);let l,i=e.hideFields||[];const o=["personalTitle","givenName","familyName","personalTitleSuffix","titleOfNobility"];"displayName"===r?a?(l=S.filter((e=>!o.includes(e)&&"displayName"!==e)),i=[...i,"displayName",...o]):(l=[...S.filter((e=>!o.includes(e))),"displayName",...o],i=i.filter((e=>!o.includes(e)&&"displayName"!==e))):o.includes(r)?a?(l=S.filter((e=>e!==r&&"displayName"!==e)),i=[...i,r]):(l=[...S.filter((e=>"displayName"!==e)),r],i=i.filter((e=>e!==r))):a?(l=S.filter((e=>e!==r)),i=[...i,r]):(l=[...S,r],i=i.filter((e=>e!==r))),t({selectedFields:l,hideFields:i}),F((e=>e+1))})(r)})},r)))]},a):null)),(0,u.jsx)(l.TextControl,{label:(0,r.__)("Group Id","rrze-faudir"),value:P,onChange:e=>t({groupId:e})}),(0,u.jsx)(l.TextControl,{label:(0,r.__)("Function","rrze-faudir"),value:e.function||"",onChange:e=>t({function:e})}),(0,u.jsx)(l.TextControl,{label:(0,r.__)("Organization Nr","rrze-faudir"),value:O,onChange:e=>{t({orgnr:e})}}),z&&!O&&(0,u.jsxs)("div",{style:{padding:"8px",backgroundColor:"#f0f0f0",borderLeft:"4px solid #007cba",marginTop:"5px",marginBottom:"15px"},children:[(0,u.jsx)("span",{className:"dashicons dashicons-info",style:{marginRight:"5px"}}),(0,r.__)("Default organization will be used if empty.","rrze-faudir")]}),(0,u.jsx)(l.TextControl,{label:(0,r.__)("Custom url","rrze-faudir"),value:E,onChange:e=>t({url:e})}),"kompakt"===j&&(0,u.jsx)(l.TextControl,{label:(0,r.__)("Button Text","rrze-faudir"),help:(0,r.__)("Default: ","rrze-faudir")+h,value:B,onChange:e=>t({buttonText:e}),placeholder:h}),(0,u.jsx)(l.SelectControl,{label:(0,r.__)("Sort by","rrze-faudir"),value:D,options:[{value:"last_name",label:(0,r.__)("Last Name","rrze-faudir")},{value:"title_last_name",label:(0,r.__)("Title and Last Name","rrze-faudir")},{value:"function_head",label:(0,r.__)("Head of Department First","rrze-faudir")},{value:"function_proffesor",label:(0,r.__)("Professors First","rrze-faudir")},{value:"identifier_order",label:(0,r.__)("Identifier Order","rrze-faudir")}],onChange:e=>{t({sort:e}),F((e=>e+1))}})]})}),(0,u.jsx)("div",{...C,children:e.selectedPersonIds?.length>0||e.selectedCategory||e.function&&(e.orgnr||z)?(0,u.jsx)(u.Fragment,{children:(0,u.jsx)(s(),{block:"rrze-faudir/block",attributes:{...e.function?{function:e.function,...e.orgnr&&{orgnr:e.orgnr},selectedFormat:e.selectedFormat,selectedFields:e.selectedFields.length>0?e.selectedFields:null,hideFields:e.hideFields,buttonText:e.buttonText,url:e.url,sort:e.sort}:e.selectedCategory?{selectedCategory:e.selectedCategory,selectedPersonIds:e.selectedPersonIds,selectedFormat:e.selectedFormat,selectedFields:e.selectedFields.length>0?e.selectedFields:null,hideFields:e.hideFields,buttonText:e.buttonText,url:e.url,groupId:e.groupId,sort:e.sort}:{selectedPersonIds:e.selectedPersonIds,selectedFields:e.selectedFields.length>0?e.selectedFields:null,hideFields:e.hideFields,selectedFormat:e.selectedFormat,buttonText:e.buttonText,url:e.url,groupId:e.groupId,sort:e.sort}}},J)}):(0,u.jsx)("div",{style:{padding:"20px",backgroundColor:"#f8f9fa",textAlign:"center"},children:(0,u.jsx)("p",{children:e.function?z?(0,r.__)("Using default organization.","rrze-faudir"):(0,r.__)("Please configure a default organization in the plugin settings or add an organization ID to display results.","rrze-faudir"):(0,r.__)("Please select persons or a category to display using the sidebar controls.","rrze-faudir")})})})]})},save:()=>null})})();
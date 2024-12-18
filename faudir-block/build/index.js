(()=>{"use strict";var e={n:r=>{var t=r&&r.__esModule?()=>r.default:()=>r;return e.d(t,{a:t}),t},d:(r,t)=>{for(var o in t)e.o(t,o)&&!e.o(r,o)&&Object.defineProperty(r,o,{enumerable:!0,get:t[o]})},o:(e,r)=>Object.prototype.hasOwnProperty.call(e,r)};const r=window.wp.blocks,t=window.wp.i18n,o=window.wp.blockEditor,l=window.wp.components,a=window.wp.element,s=window.wp.serverSideRender;var i=e.n(s);const n=window.wp.apiFetch;var d=e.n(n);const u=window.ReactJSXRuntime,c=JSON.parse('{"UU":"rrze-faudir/block"}');(0,r.registerBlockType)(c.UU,{edit:function({attributes:e,setAttributes:r}){const[s,n]=(0,a.useState)([]),[c,f]=(0,a.useState)([]),[_,p]=(0,a.useState)(!0),[g,h]=(0,a.useState)(!1),[m,b]=(0,a.useState)(""),[z,x]=(0,a.useState)(null),[C,F]=(0,a.useState)(0),y=(0,o.useBlockProps)(),{selectedCategory:v="",selectedPosts:w=[],showCategory:j="",showPosts:k="",selectedPersonIds:T="",selectedFormat:S="kompakt",selectedFields:P=[],groupId:N="",function:I="",orgnr:O="",url:E="",buttonText:B="",hideFields:U=[],sort:A="last_name"}=e,L={displayName:(0,t.__)("Display Name","rrze-faudir"),personalTitle:(0,t.__)("Academic Title","rrze-faudir"),givenName:(0,t.__)("First Name","rrze-faudir"),familyName:(0,t.__)("Last Name","rrze-faudir"),personalTitleSuffix:(0,t.__)("Academic Suffix","rrze-faudir"),titleOfNobility:(0,t.__)("Title of Nobility","rrze-faudir"),email:(0,t.__)("Email","rrze-faudir"),phone:(0,t.__)("Phone","rrze-faudir"),organization:(0,t.__)("Organization","rrze-faudir"),function:(0,t.__)("Function","rrze-faudir"),url:(0,t.__)("Url","rrze-faudir"),kompaktButton:(0,t.__)("Kompakt Button","rrze-faudir"),content:(0,t.__)("Content","rrze-faudir"),teasertext:(0,t.__)("Teasertext","rrze-faudir"),socialmedia:(0,t.__)("Social Media","rrze-faudir"),workplaces:(0,t.__)("Workplaces","rrze-faudir"),room:(0,t.__)("Room","rrze-faudir"),floor:(0,t.__)("Floor","rrze-faudir"),street:(0,t.__)("Street","rrze-faudir"),zip:(0,t.__)("Zip","rrze-faudir"),city:(0,t.__)("City","rrze-faudir"),faumap:(0,t.__)("Fau Map","rrze-faudir"),officehours:(0,t.__)("Office Hours","rrze-faudir"),consultationhours:(0,t.__)("Consultation Hours","rrze-faudir")},D={card:["displayName","personalTitle","givenName","familyName","personalTitleSuffix","email","phone","function","socialmedia","titleOfNobility"],table:["displayName","personalTitle","givenName","familyName","personalTitleSuffix","email","phone","url","socialmedia","titleOfNobility"],list:["displayName","personalTitle","givenName","familyName","personalTitleSuffix","email","phone","url","teasertext","titleOfNobility"],kompakt:Object.keys(L),page:Object.keys(L)};(0,a.useEffect)((()=>{e.selectedFields&&0!==e.selectedFields.length||d()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{e?.default_output_fields&&r({selectedFields:e.default_output_fields})})).catch((e=>{console.error("Error fetching default fields:",e)}))}),[]),(0,a.useEffect)((()=>{d()({path:"/wp/v2/custom_taxonomy?per_page=100"}).then((e=>{n(e),p(!1)})).catch((e=>{console.error("Error fetching categories:",e),p(!1)}))}),[]),(0,a.useEffect)((()=>{d()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{e?.default_organization?.orgnr&&(x(e.default_organization.orgnr),I&&!O&&r({orgnr:e.default_organization.orgnr}))})).catch((e=>{console.error("Error fetching default organization number:",e)}))}),[]),(0,a.useEffect)((()=>{h(!0);const e={per_page:100,_fields:"id,title,meta",orderby:"title",order:"asc"};if(v&&(e.custom_taxonomy=v),I){e.function=I;const r=O||z;r&&(e.organization_nr=r)}d()({path:"/wp/v2/custom_person",params:e}).then((e=>{if(f(e),v&&(!w||0===w.length)){const t=e.map((e=>e.id)),o=e.map((e=>e.meta?.person_id)).filter(Boolean);r({selectedPosts:t,selectedPersonIds:o})}h(!1)})).catch((e=>{console.error("Error fetching posts:",e),h(!1)}))}),[v,I,O,z]),(0,a.useEffect)((()=>{B||d()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{e?.business_card_title&&(b(e.business_card_title),r({buttonText:e.business_card_title}))})).catch((e=>{console.error("Error fetching button text:",e)}))}),[]),console.log("Edit component rendering with attributes:",e);const R={selectedPersonIds:e.selectedPersonIds,selectedFields:e.selectedFields,selectedFormat:e.selectedFormat,selectedCategory:e.selectedCategory,groupId:e.groupId,function:e.function,orgnr:e.orgnr,url:e.url};console.log("Block attributes:",R);const[H,M]=(0,a.useState)(0);return(0,a.useEffect)((()=>{const e=setTimeout((()=>{M((e=>e+1))}),300);return()=>clearTimeout(e)}),[...Object.values(e),A]),(0,u.jsxs)(u.Fragment,{children:[(0,u.jsx)(o.InspectorControls,{children:(0,u.jsxs)(l.PanelBody,{title:(0,t.__)("Settings","rrze-faudir"),children:[(0,u.jsx)(l.ToggleControl,{label:(0,t.__)("Show Category","rrze-faudir"),checked:j,onChange:()=>r({showCategory:!j})}),j&&(0,u.jsxs)(u.Fragment,{children:[(0,u.jsx)("h4",{children:(0,t.__)("Select Category","rrze-faudir")}),s.map((e=>(0,u.jsx)(l.CheckboxControl,{label:e.name,checked:v===e.name,onChange:()=>{const t=v===e.name?"":e.name;r({selectedCategory:t,selectedPosts:""===t?[]:w,selectedPersonIds:""===t?[]:T})}},e.id)))]}),(0,u.jsx)(l.ToggleControl,{label:(0,t.__)("Show Persons","rrze-faudir"),checked:k,onChange:()=>r({showPosts:!k})}),k&&(0,u.jsxs)(u.Fragment,{children:[(0,u.jsx)("h4",{children:(0,t.__)("Select Persons","rrze-faudir")}),g?(0,u.jsx)("p",{children:(0,t.__)("Loading persons...","rrze-faudir")}):c.length>0?c.map((e=>(0,u.jsx)(l.CheckboxControl,{label:e.title.rendered,checked:Array.isArray(w)&&w.includes(e.id),onChange:()=>(e=>{const t=w.includes(e)?w.filter((r=>r!==e)):[...w,e],o=t.map((e=>{const r=c.find((r=>r.id===e));return r?.meta?.person_id||null})).filter(Boolean);r({selectedPosts:t,selectedPersonIds:o}),F((e=>e+1))})(e.id,e.meta)},e.id))):(0,u.jsx)("p",{children:(0,t.__)("No posts available.","rrze-faudir")})]}),(0,u.jsx)(l.SelectControl,{label:(0,t.__)("Select Format","rrze-faudir"),value:S||"list",options:[{value:"list",label:(0,t.__)("List","rrze-faudir")},{value:"table",label:(0,t.__)("Table","rrze-faudir")},{value:"card",label:(0,t.__)("Card","rrze-faudir")},{value:"kompakt",label:(0,t.__)("Kompakt","rrze-faudir")},{value:"page",label:(0,t.__)("Page","rrze-faudir")}],onChange:t=>{r({selectedFormat:t}),F((e=>e+1)),e.selectedFields&&0!==e.selectedFields.length||d()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{if(e?.default_output_fields){const o=D[t]||[],l=e.default_output_fields.filter((e=>o.includes(e)));r({selectedFields:l})}})).catch((e=>{console.error("Error fetching default fields:",e)}))}}),Object.keys(D).map((o=>S===o?(0,u.jsxs)("div",{children:[(0,u.jsx)("h4",{children:(0,t.__)("Select Fields","rrze-faudir")}),D[o].map((t=>(0,u.jsx)("div",{style:{marginBottom:"8px"},children:(0,u.jsx)(l.CheckboxControl,{label:(0,u.jsx)(u.Fragment,{children:L[t]}),checked:P.includes(t),onChange:()=>(t=>{console.log("Toggling field:",t),console.log("Current selectedFields:",P),console.log("Current hideFields:",e.hideFields);const o=P.includes(t);let l,a=e.hideFields||[];o?(l=P.filter((e=>e!==t)),a=[...a,t]):(l=[...P,t],a=a.filter((e=>e!==t))),console.log("Updated selectedFields:",l),console.log("Updated hideFields:",a),r({selectedFields:l,hideFields:a})})(t)})},t)))]},o):null)),(0,u.jsx)(l.TextControl,{label:(0,t.__)("Group Id","rrze-faudir"),value:N,onChange:e=>r({groupId:e})}),(0,u.jsx)(l.TextControl,{label:(0,t.__)("Function","rrze-faudir"),value:e.function||"",onChange:e=>r({function:e})}),(0,u.jsx)(l.TextControl,{label:(0,t.__)("Organization Nr","rrze-faudir"),value:O,onChange:e=>{console.log("Setting orgnr:",e),r({orgnr:e})}}),(0,u.jsx)(l.TextControl,{label:(0,t.__)("Custom url","rrze-faudir"),value:E,onChange:e=>r({url:e})}),"kompakt"===S&&(0,u.jsx)(l.TextControl,{label:(0,t.__)("Button Text","rrze-faudir"),help:(0,t.__)("Default: ","rrze-faudir")+m,value:B,onChange:e=>r({buttonText:e}),placeholder:m}),(0,u.jsx)(l.SelectControl,{label:(0,t.__)("Sort by","rrze-faudir"),value:A,options:[{value:"last_name",label:(0,t.__)("Last Name","rrze-faudir")},{value:"title_last_name",label:(0,t.__)("Title and Last Name","rrze-faudir")},{value:"function_head",label:(0,t.__)("Head of Department First","rrze-faudir")},{value:"function_proffesor",label:(0,t.__)("Professors First","rrze-faudir")},{value:"identifier_order",label:(0,t.__)("Identifier Order","rrze-faudir")}],onChange:e=>{r({sort:e}),F((e=>e+1))}})]})}),(0,u.jsx)("div",{...y,children:e.selectedPersonIds?.length>0||e.selectedCategory||e.function&&e.orgnr?(0,u.jsx)(i(),{block:"rrze-faudir/block",attributes:{...e.function&&e.orgnr?{function:e.function,orgnr:e.orgnr,selectedFormat:e.selectedFormat,selectedFields:e.selectedFields,buttonText:e.buttonText,url:e.url,sort:e.sort}:e.selectedCategory?{selectedCategory:e.selectedCategory,selectedFormat:e.selectedFormat,selectedFields:e.selectedFields,buttonText:e.buttonText,url:e.url,groupId:e.groupId,sort:e.sort}:{selectedPersonIds:e.selectedPersonIds,selectedFields:e.selectedFields,selectedFormat:e.selectedFormat,buttonText:e.buttonText,url:e.url,groupId:e.groupId,sort:e.sort}}},C):(0,u.jsx)("div",{style:{padding:"20px",backgroundColor:"#f8f9fa",textAlign:"center"},children:(0,u.jsx)("p",{children:e.function?(0,t.__)("Please add an organization ID to display results.","rrze-faudir"):(0,t.__)("Please select persons or a category to display using the sidebar controls.","rrze-faudir")})})})]})},save:()=>null})})();
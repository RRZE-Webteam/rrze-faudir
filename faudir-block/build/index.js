(()=>{"use strict";var e,r={192:(e,r,o)=>{const t=window.wp.blocks,a=window.wp.i18n,l=window.wp.blockEditor,i=window.wp.components,n=window.wp.element,s=window.wp.serverSideRender;var d=o.n(s);const c=window.wp.apiFetch;var u=o.n(c);const f=window.ReactJSXRuntime,_=JSON.parse('{"UU":"rrze-faudir/block"}');(0,t.registerBlockType)(_.UU,{edit:function({attributes:e,setAttributes:r}){const[o,t]=(0,n.useState)([]),[s,c]=(0,n.useState)([]),[_,p]=(0,n.useState)(!0),[h,m]=(0,n.useState)(!1),[g,b]=(0,n.useState)(""),{selectedCategory:x="",selectedPosts:k=[],showCategory:j="",showPosts:C="",selectedPersonIds:v="",selectedFormat:z="kompakt",selectedFields:w=[],groupId:y="",functionField:F="",organizationNr:P="",url:S=""}=e,O={display_name:(0,a.__)("Display Name","rrze-faudir"),academic_title:(0,a.__)("Academic Title","rrze-faudir"),first_name:(0,a.__)("First Name","rrze-faudir"),last_name:(0,a.__)("Last Name","rrze-faudir"),academic_suffix:(0,a.__)("Academic Suffix","rrze-faudir"),email:(0,a.__)("Email","rrze-faudir"),phone:(0,a.__)("Phone","rrze-faudir"),organization:(0,a.__)("Organization","rrze-faudir"),function:(0,a.__)("Function","rrze-faudir"),url:(0,a.__)("Url","rrze-faudir"),kompaktButton:(0,a.__)("Kompakt Button","rrze-faudir"),content:(0,a.__)("Content","rrze-faudir"),teasertext:(0,a.__)("Teasertext","rrze-faudir"),socialmedia:(0,a.__)("Social Media","rrze-faudir"),workplaces:(0,a.__)("Workplaces","rrze-faudir"),room:(0,a.__)("Room","rrze-faudir"),floor:(0,a.__)("Floor","rrze-faudir"),street:(0,a.__)("Street","rrze-faudir"),zip:(0,a.__)("Zip","rrze-faudir"),city:(0,a.__)("City","rrze-faudir"),faumap:(0,a.__)("Fau Map","rrze-faudir"),officehours:(0,a.__)("Office Hours","rrze-faudir"),consultationhours:(0,a.__)("Consultation Hours","rrze-faudir")},I={card:["display_name","academic_title","first_name","last_name","academic_suffix","email","phone","function","socialmedia"],table:["display_name","academic_title","first_name","last_name","academic_suffix","email","phone","url","socialmedia"],list:["display_name","academic_title","first_name","last_name","academic_suffix","email","phone","url","teasertext"],kompakt:Object.keys(O),page:Object.keys(O)};(0,n.useEffect)((()=>{w.length||u()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{e?.default_output_fields&&r({selectedFields:e.default_output_fields})})).catch((e=>{console.error("Error fetching default fields:",e)}))}),[]),(0,n.useEffect)((()=>{u()({path:"/wp/v2/custom_taxonomy?per_page=100"}).then((e=>{t(e),p(!1)})).catch((e=>{console.error("Error fetching categories:",e),p(!1)}))}),[]),(0,n.useEffect)((()=>{m(!0),u()({path:"/wp/v2/custom_person?per_page=100&_fields=id,title,meta"}).then((e=>{c(e),m(!1)})).catch((e=>{console.error("Error fetching posts:",e),m(!1)}))}),[]),console.log("Edit component rendering with attributes:",e);const N={selectedPersonIds:e.selectedPersonIds,selectedFields:e.selectedFields,selectedFormat:e.selectedFormat,selectedCategory:e.selectedCategory,groupId:e.groupId,functionField:e.functionField,organizationNr:e.organizationNr,url:e.url};return console.log("Block attributes:",N),(0,f.jsxs)(f.Fragment,{children:[(0,f.jsx)(l.InspectorControls,{children:(0,f.jsxs)(i.PanelBody,{title:(0,a.__)("Settings","faudir-block"),children:[(0,f.jsx)(i.ToggleControl,{label:(0,a.__)("Show Category","faudir-block"),checked:j,onChange:()=>r({showCategory:!j})}),j&&(0,f.jsxs)(f.Fragment,{children:[(0,f.jsx)("h4",{children:(0,a.__)("Select Category","faudir-block")}),o.map((e=>(0,f.jsx)(i.CheckboxControl,{label:e.name,checked:x===e.name,onChange:()=>r({selectedCategory:e.name})},e.id)))]}),(0,f.jsx)(i.ToggleControl,{label:(0,a.__)("Show Persons","faudir-block"),checked:C,onChange:()=>r({showPosts:!C})}),C&&(0,f.jsxs)(f.Fragment,{children:[(0,f.jsx)("h4",{children:(0,a.__)("Select Persons","faudir-block")}),h?(0,f.jsx)("p",{children:(0,a.__)("Loading persons...","faudir-block")}):s.length>0?s.map((e=>(0,f.jsx)(i.CheckboxControl,{label:e.title.rendered,checked:k.includes(e.id),onChange:()=>(e=>{const o=k.includes(e)?k.filter((r=>r!==e)):[...k,e],t=o.map((e=>{const r=s.find((r=>r.id===e));return r?.meta?.person_id||null})).filter(Boolean);r({selectedPosts:o,selectedPersonIds:t})})(e.id,e.meta)},e.id))):(0,f.jsx)("p",{children:(0,a.__)("No posts available.","faudir-block")})]}),(0,f.jsx)(i.SelectControl,{label:(0,a.__)("Select Format","faudir-block"),value:z||"list",options:[{value:"list",label:(0,a.__)("List","faudir-block")},{value:"table",label:(0,a.__)("Table","faudir-block")},{value:"card",label:(0,a.__)("Card","faudir-block")},{value:"kompakt",label:(0,a.__)("Kompakt","faudir-block")},{value:"page",label:(0,a.__)("Page","faudir-block")}],onChange:e=>{r({selectedFormat:e}),r({selectedFields:I[e]||[]})}}),Object.keys(I).map((e=>z===e?(0,f.jsxs)("div",{children:[(0,f.jsx)("h4",{children:(0,a.__)("Select Fields","faudir-block")}),I[e].map((e=>(0,f.jsx)(i.CheckboxControl,{label:O[e],checked:w.includes(e),onChange:()=>(e=>{const o=w.includes(e)?w.filter((r=>r!==e)):[...w,e];r({selectedFields:o})})(e)},e)))]},e):null)),(0,f.jsx)(i.TextControl,{label:(0,a.__)("Group Id","rrze-faudir"),value:y,onChange:e=>r({groupId:e})}),(0,f.jsx)(i.TextControl,{label:(0,a.__)("Function","rrze-faudir"),value:F,onChange:e=>r({functionField:e})}),(0,f.jsx)(i.TextControl,{label:(0,a.__)("Organization Nr","rrze-faudir"),value:P,onChange:e=>r({organizationNr:e})}),(0,f.jsx)(i.TextControl,{label:(0,a.__)("Custom url","rrze-faudir"),value:S,onChange:e=>r({url:e})}),"kompakt"===z&&(0,f.jsx)(i.TextControl,{label:(0,a.__)("Button Text","rrze-faudir"),value:g,onChange:e=>b(e)})]})}),(0,f.jsx)("div",{...(0,l.useBlockProps)(),children:e.selectedPersonIds&&e.selectedPersonIds.length>0?(0,f.jsxs)(f.Fragment,{children:[(0,f.jsxs)("div",{style:{marginBottom:"10px",padding:"10px",backgroundColor:"#f8f9fa"},children:[(0,f.jsx)("strong",{children:"Selected Person IDs:"})," ",e.selectedPersonIds.join(", ")]}),(0,f.jsx)(d(),{block:"rrze-faudir/block",attributes:e,EmptyResponsePlaceholder:()=>(0,f.jsxs)("div",{style:{padding:"20px",backgroundColor:"#fff3cd",color:"#856404"},children:[(0,f.jsx)("p",{children:"No content returned from server."}),(0,f.jsxs)("details",{children:[(0,f.jsx)("summary",{children:"Debug Information"}),(0,f.jsx)("pre",{children:JSON.stringify(e,null,2)})]})]}),ErrorResponsePlaceholder:({response:r})=>(0,f.jsxs)("div",{style:{padding:"20px",backgroundColor:"#f8d7da",color:"#721c24"},children:[(0,f.jsx)("p",{children:(0,f.jsx)("strong",{children:"Error loading content:"})}),(0,f.jsx)("p",{children:r?.errorMsg||"Unknown error occurred"}),(0,f.jsxs)("details",{children:[(0,f.jsx)("summary",{children:"Debug Information"}),(0,f.jsx)("pre",{children:"Block: rrze-faudir/block"}),(0,f.jsxs)("pre",{children:["Response: ",JSON.stringify(r,null,2)]}),(0,f.jsxs)("pre",{children:["Attributes: ",JSON.stringify(e,null,2)]})]})]})})]}):(0,f.jsx)("div",{style:{padding:"20px",backgroundColor:"#f8f9fa",textAlign:"center"},children:(0,f.jsx)("p",{children:"Please select persons to display using the sidebar controls."})})})]})},save:()=>null})}},o={};function t(e){var a=o[e];if(void 0!==a)return a.exports;var l=o[e]={exports:{}};return r[e](l,l.exports,t),l.exports}t.m=r,e=[],t.O=(r,o,a,l)=>{if(!o){var i=1/0;for(c=0;c<e.length;c++){for(var[o,a,l]=e[c],n=!0,s=0;s<o.length;s++)(!1&l||i>=l)&&Object.keys(t.O).every((e=>t.O[e](o[s])))?o.splice(s--,1):(n=!1,l<i&&(i=l));if(n){e.splice(c--,1);var d=a();void 0!==d&&(r=d)}}return r}l=l||0;for(var c=e.length;c>0&&e[c-1][2]>l;c--)e[c]=e[c-1];e[c]=[o,a,l]},t.n=e=>{var r=e&&e.__esModule?()=>e.default:()=>e;return t.d(r,{a:r}),r},t.d=(e,r)=>{for(var o in r)t.o(r,o)&&!t.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:r[o]})},t.o=(e,r)=>Object.prototype.hasOwnProperty.call(e,r),(()=>{var e={57:0,350:0};t.O.j=r=>0===e[r];var r=(r,o)=>{var a,l,[i,n,s]=o,d=0;if(i.some((r=>0!==e[r]))){for(a in n)t.o(n,a)&&(t.m[a]=n[a]);if(s)var c=s(t)}for(r&&r(o);d<i.length;d++)l=i[d],t.o(e,l)&&e[l]&&e[l][0](),e[l]=0;return t.O(c)},o=globalThis.webpackChunkblock_faudir=globalThis.webpackChunkblock_faudir||[];o.forEach(r.bind(null,0)),o.push=r.bind(null,o.push.bind(o))})();var a=t.O(void 0,[350],(()=>t(192)));a=t.O(a)})();
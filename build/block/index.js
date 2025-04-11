(()=>{"use strict";var e={n:t=>{var r=t&&t.__esModule?()=>t.default:()=>t;return e.d(r,{a:r}),r},d:(t,r)=>{for(var i in r)e.o(r,i)&&!e.o(t,i)&&Object.defineProperty(t,i,{enumerable:!0,get:r[i]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const t=window.wp.blocks,r=window.ReactJSXRuntime,i=window.wp.i18n,a=window.wp.blockEditor,s=window.wp.components,o=window.wp.element,n=window.wp.serverSideRender;var l=e.n(n);function d({attributes:e}){const[t,i]=(0,o.useState)(0);return(0,o.useEffect)((()=>{i((e=>e+1))}),[e.orgnr,e.selectedCategory,e.orgid,e.selectedFields,e.selectedFormat]),(0,r.jsx)(l(),{block:"rrze-faudir/block",attributes:{role:e.role,orgnr:e.orgnr,orgid:e.orgid,selectedFormat:e.selectedFormat,selectedFields:e.selectedFields,selectedCategory:e.selectedCategory,selectedPersonIds:e.selectedPersonIds,hideFields:e.hideFields,url:e.url,sort:e.sort,format_displayname:e.format_displayname,display:e.display,identifier:e.identifier}},t)}const u=window.wp.apiFetch;var c=e.n(u);const p={image:(0,i.__)("Image","rrze-faudir"),displayName:(0,i.__)("Display Name","rrze-faudir"),honorificPrefix:(0,i.__)("Academic Title","rrze-faudir"),givenName:(0,i.__)("First Name","rrze-faudir"),familyName:(0,i.__)("Last Name","rrze-faudir"),honorificSuffix:(0,i.__)("Academic Suffix","rrze-faudir"),titleOfNobility:(0,i.__)("Title of Nobility","rrze-faudir"),email:(0,i.__)("Email","rrze-faudir"),phone:(0,i.__)("Phone","rrze-faudir"),organization:(0,i.__)("Organization","rrze-faudir"),jobTitle:(0,i.__)("Jobtitle","rrze-faudir"),url:(0,i.__)("URL","rrze-faudir"),content:(0,i.__)("Content","rrze-faudir"),teasertext:(0,i.__)("Teasertext","rrze-faudir"),socialmedia:(0,i.__)("Social Media and Websites","rrze-faudir"),room:(0,i.__)("Room","rrze-faudir"),floor:(0,i.__)("Floor","rrze-faudir"),address:(0,i.__)("Address","rrze-faudir"),street:(0,i.__)("Street","rrze-faudir"),zip:(0,i.__)("ZIP Code","rrze-faudir"),city:(0,i.__)("City","rrze-faudir"),faumap:(0,i.__)("FAU Map","rrze-faudir"),officehours:(0,i.__)("Office Hours","rrze-faudir"),consultationhours:(0,i.__)("Consultation Hours","rrze-faudir")},_=(Object.keys(p),Object.keys(p),{image:"image",displayname:"displayName",honorificPrefix:"honorificPrefix",givenName:"givenName",familyName:"familyName",honorificSuffix:"honorificSuffix",titleOfNobility:"titleOfNobility",email:"email",phone:"phone",organization:"organization",jobTitle:"jobTitle",url:"url",content:"content",teasertext:"teasertext",socialmedia:"socialmedia",room:"room",floor:"floor",street:"street",zip:"zip",city:"city",faumap:"faumap",officehours:"officehours",consultationhours:"consultationhours",address:"address"});function f({attributes:e,setAttributes:t,label:a,helpText:n}){var l;const[d,u]=(0,o.useState)(""),[c,p]=(0,o.useState)(null!==(l=e.orgnr)&&void 0!==l?l:"");return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalHeading,{level:3,children:(0,i.__)("Display Organization","rrze-faudir")}),(0,r.jsx)(s.TextControl,{label:a||(0,i.__)("FAUOrg Number","rrze-faudir"),value:c,onChange:e=>{let r=e.replace(/\D/g,"");r.length>10&&(r=r.substring(0,10)),p(r),0===r.length?(t({orgnr:""}),u("")):10===r.length?(t({orgnr:r}),u("")):(t({orgnr:""}),u((0,i.__)("Your FAUOrg-Number needs to be exactly 10 digits.","rrze-faudir")))},type:"text",help:d||n||(0,i.__)("To display all Persons from within your Organization, insert your FAUOrg Number (Cost center number).","rrze-faudir")})]})}function g({isLoadingPosts:e,posts:t,selectedPosts:a,togglePostSelection:o}){const n=new Map;t.forEach((e=>{n.set(e.title.rendered,e.id)}));const l=t.filter((e=>a.includes(e.id))).map((e=>e.title.rendered)),d=t.map((e=>e.title.rendered));return e?(0,i.__)("Loading available contacts...","rrze-faudir"):(0,i.__)("Select Contacts for Display.","rrze-faudir"),(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalHeading,{level:3,children:(0,i.__)("Select Persons","rrze-faudir")}),(0,r.jsx)(s.FormTokenField,{__next40pxDefaultSize:!0,label:(0,i.__)("Type to add persons","rrze-faudir"),value:l,suggestions:d,disabled:e||0===t.length,onChange:e=>{const t=e.map((e=>n.get(e))).filter((e=>"number"==typeof e));t.forEach((e=>{a.includes(e)||o(e)})),a.forEach((e=>{t.includes(e)||o(e)}))}}),0===t.length&&(0,r.jsx)(s.Notice,{isDismissible:!1,status:"info",children:(0,i.__)("There are currently no Contacts available. Start adding your first FAUdir Contacts via the WordPress Dashboard > Persons.","rrze-faudir")})]})}function m({categories:e,selectedCategory:t,setAttributes:a}){const o=t.trim().length>0?t.split(",").map((e=>e.trim())):[],n=e.map((e=>e.name));return(0,r.jsxs)("div",{children:[(0,r.jsx)(s.__experimentalHeading,{level:3,children:(0,i.__)("Select Categories","rrze-faudir")}),(0,r.jsx)(s.FormTokenField,{__next40pxDefaultSize:!0,label:(0,i.__)("Type to add categories","rrze-faudir"),value:o,disabled:0===n.length,suggestions:n,onChange:e=>{const t=e.filter((e=>n.includes(e))).join(", ");a({selectedCategory:t,selectedPosts:[],selectedPersons:[]})}}),0===n.length&&(0,r.jsx)(s.Notice,{isDismissible:!1,status:"info",children:(0,i.__)("There are currently no Categories available. Start adding your first FAUdir Categories via the WordPress Dashboard > Persons > Categories.","rrze-faudir")})]})}function h({attributes:e,setAttributes:t}){const{selectedFormat:a}=e,[n,l]=(0,o.useState)({}),[d,u]=(0,o.useState)([]),[p,_]=(0,o.useState)({});(0,o.useEffect)((()=>{c()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{(null==e?void 0:e.available_formats_by_display)&&l(e.available_formats_by_display),(null==e?void 0:e.format_names)&&_(e.format_names)})).catch((e=>{console.error("Fehler beim Laden der Felder:",e)}))}),[a]),(0,o.useEffect)((()=>{var r;const i="org"===e.display,s=null!==(r=i?n.org:n.person)&&void 0!==r?r:[];u(s),s.includes(a)||t({selectedFormat:i?"compact":"list"})}),[e.display,n,a,t]);const f=e=>p[e]||e,g=a||"list",m=(null!=d?d:[]).map((e=>({value:e,label:f(e)})));return(0,r.jsx)(r.Fragment,{children:(0,r.jsx)(s.SelectControl,{label:(0,i.__)("Select Format","rrze-faudir"),value:g,options:m,onChange:e=>{t({selectedFormat:e})}})})}function x({attributes:e,setAttributes:t}){const{selectedFormat:a}=e,[n,l]=(0,o.useState)([]),[d,u]=(0,o.useState)([]),[p,_]=(0,o.useState)([]),[f,g]=(0,o.useState)([]),[m,h]=(0,o.useState)({});(0,o.useEffect)((()=>{c()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{if((null==e?void 0:e.default_output_fields)&&l(e.default_output_fields),(null==e?void 0:e.avaible_fields_byformat)&&a){const t=e.avaible_fields_byformat[a]||[];u(t)}(null==e?void 0:e.available_fields)&&h(e.available_fields)})).catch((e=>{console.error("Fehler beim Laden der Felder:",e)}))}),[a,e.display]),(0,o.useEffect)((()=>{t({hideFields:p,selectedFields:f})}),[p,f,a,e.display]);const x=e=>n.includes(e)?!p.includes(e):f.includes(e),b=e=>m[e]||e;return(0,r.jsxs)("div",{children:[(0,r.jsx)("h4",{children:(0,i.__)("Felder auswählen","rrze-faudir")}),d.map((e=>(0,r.jsx)(s.CheckboxControl,{label:b(e),checked:x(e),onChange:()=>(e=>{n.includes(e)?p.includes(e)?_(p.filter((t=>t!==e))):_([...p,e]):f.includes(e)?g(f.filter((t=>t!==e))):g([...f,e])})(e)},e)))]})}function b({attributes:e,setAttributes:t}){const{format_displayname:a}=e;return(0,r.jsx)(s.TextControl,{label:(0,i.__)("Change display format","rrze-faudir"),value:a,onChange:e=>{t({format_displayname:e})},type:"text"})}const y=window.React,j=window.wp.primitives,z=(0,y.createElement)(j.SVG,{viewBox:"0 0 24 24",xmlns:"http://www.w3.org/2000/svg"},(0,y.createElement)(j.Path,{d:"M12 4c-4.4 0-8 3.6-8 8v.1c0 4.1 3.2 7.5 7.2 7.9h.8c4.4 0 8-3.6 8-8s-3.6-8-8-8zm0 15V5c3.9 0 7 3.1 7 7s-3.1 7-7 7z"})),v=(0,y.createElement)(j.SVG,{viewBox:"0 0 24 24",xmlns:"http://www.w3.org/2000/svg"},(0,y.createElement)(j.Path,{d:"M10 4.5a1 1 0 11-2 0 1 1 0 012 0zm1.5 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zm2.25 7.5v-1A2.75 2.75 0 0011 8.25H7A2.75 2.75 0 004.25 11v1h1.5v-1c0-.69.56-1.25 1.25-1.25h4c.69 0 1.25.56 1.25 1.25v1h1.5zM4 20h9v-1.5H4V20zm16-4H4v-1.5h16V16z",fillRule:"evenodd",clipRule:"evenodd"})),F=(0,y.createElement)(j.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"},(0,y.createElement)(j.Path,{d:"m19 7-3-3-8.5 8.5-1 4 4-1L19 7Zm-7 11.5H5V20h7v-1.5Z"})),S=(0,y.createElement)(j.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"},(0,y.createElement)(j.Path,{d:"M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"}));function C({attributes:e,setAttributes:t,label:a,helpText:n}){const[l,d]=(0,o.useState)(e.orgid||""),[u,c]=(0,o.useState)("");return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalHeading,{level:3,children:(0,i.__)("Display Faudir Folder","rrze-faudir")}),(0,r.jsx)(s.TextControl,{label:a||(0,i.__)("Via FAUorg-ID or FAUdir-URL","rrze-faudir"),value:l,onChange:e=>{const r=e.trim(),a=r.match(/^https?:\/\/faudir\.fau\.de\/public\/org\/([^/]+)\/?$/);let s=a?a[1]:r;s?(t({orgid:s}),c("")):(t({orgid:""}),c((0,i.__)("Please enter a valid FAUdir-URL or the identifier.","rrze-faudir"))),d(e)},type:"text",help:u||n||(0,i.__)('Please enter either a FAUdir-URL ("https://faudir.fau.de/public/org/…"), or the Identifier.',"rrze-faudir")})]})}function w({attributes:e,setAttributes:t}){const{sort:a}=e;return(0,r.jsx)(s.SelectControl,{label:(0,i.__)("Sort by","rrze-faudir"),value:a,options:[{value:"familyName",label:(0,i.__)("Last Name","rrze-faudir")},{value:"title_familyName",label:(0,i.__)("Title and Last Name","rrze-faudir")},{value:"head_first",label:(0,i.__)("Head of Department First","rrze-faudir")},{value:"prof_first",label:(0,i.__)("Professors First","rrze-faudir")},{value:"identifier_order",label:(0,i.__)("Identifier Order","rrze-faudir")}],onChange:e=>{t({sort:e})}})}function P({attributes:e,setAttributes:t}){const[a,n]=(0,o.useState)({}),[l,d]=(0,o.useState)([]);(0,o.useEffect)((()=>{c()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{(null==e?void 0:e.person_roles)&&n(e.person_roles)})).catch((e=>{console.error("Fehler beim Laden der person_roles:",e)}))}),[]);const u=Object.entries(a).map((([e,t])=>({value:e,label:t})));return(0,o.useEffect)((()=>{t({role:l.join(",")})}),[l]),(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.ComboboxControl,{options:u,onChange:e=>{d([...l,e])},label:(0,i.__)("Filter by Role","rrze-faudir"),help:(0,i.__)("Select a category to filter the downloads by.","rrze-faudir"),allowReset:!1,value:""}),l.length>0&&(0,r.jsx)(s.FormTokenField,{value:l,label:(0,i.__)("Currently selected role filters.","rrze-faudir"),onChange:e=>{d(e)}})]})}function A({attributes:e,setAttributes:t,label:a,helpText:n}){const[l,d]=(0,o.useState)(e.orgid||""),[u,c]=(0,o.useState)("");return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalHeading,{level:3,children:(0,i.__)("Direct Select via FAUdir","rrze-faudir")}),(0,r.jsx)(s.TextControl,{label:a||(0,i.__)("Via Person Identifier or FAUdir-URL","rrze-faudir"),value:l,onChange:e=>{const r=e.trim(),a=r.match(/^https?:\/\/faudir\.fau\.de\/public\/person\/([^/]+)\/?$/);let s=a?a[1]:r;s?(t({identifier:s}),c("")):(t({identifier:""}),c((0,i.__)("Please enter a valid FAUdir-URL or the identifier.","rrze-faudir"))),d(e)},type:"text",help:u||n||(0,i.__)('Please enter either a FAUdir-URL ("https://faudir.fau.de/public/person/…"), or the Person-Identifier. This will display your contact, even if the contact is not created via Dashboard > Persons.',"rrze-faudir")})]})}function T({attributes:e,setAttributes:t,isOrg:a,setIsOrg:o,isLoadingPosts:n,posts:l,selectedPosts:u,togglePostSelection:c,categories:p,isAppearancePanelOpen:_,setIsAppearancePanelOpen:y}){const j=()=>{t({initialSetup:!1})};return(0,r.jsx)(r.Fragment,{children:(0,r.jsx)(s.Placeholder,{label:(0,i.__)("Setup your FAUdir Block","rrze-faudir"),instructions:(0,i.__)("Get started by selecting your desired configuration.","rrze-faudir"),children:_?(0,r.jsx)(r.Fragment,{children:(0,r.jsx)("div",{children:(0,r.jsx)(s.__experimentalSpacer,{paddingBottom:"1.5rem",paddingTop:"1rem",children:(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalHeading,{level:2,children:(0,i.__)("Configure the appearance of your Contact","rrze-faudir")}),(0,r.jsx)(h,{attributes:e,setAttributes:t}),(0,r.jsx)(x,{attributes:e,setAttributes:t}),(0,r.jsx)(b,{attributes:e,setAttributes:t}),(0,r.jsx)(s.__experimentalSpacer,{paddingTop:"1rem"}),(0,r.jsx)(s.Button,{variant:"secondary",onClick:()=>{y(!1)},children:(0,i.__)("Configure the Data Source","rrze-faudir")}),(0,r.jsx)(s.Button,{variant:"primary",onClick:j,children:(0,i.__)("Finish initial setup","rrze-faudir")}),(0,r.jsx)("hr",{}),(0,r.jsxs)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1.5rem",children:[(0,r.jsx)(s.__experimentalHeading,{level:2,children:(0,i.__)("Preview","rrze-faudir")}),(0,r.jsx)(d,{attributes:e})]})]})})})}):(0,r.jsxs)("div",{style:{minWidth:"100%"},children:[(0,r.jsx)(s.__experimentalSpacer,{paddingBottom:"1.5rem",paddingTop:"1rem",children:(0,r.jsxs)(s.__experimentalToggleGroupControl,{__next40pxDefaultSize:!0,__nextHasNoMarginBottom:!0,isBlock:!0,label:(0,i.__)("What type of Contact do you want to display?","rrze-faudir"),help:(0,i.__)("Do you want to output a Person entry or a FAUdir Institution/Folder?","rrze-faudir"),onChange:e=>o("person"!==e),value:a?"org":"person",children:[(0,r.jsx)(s.__experimentalToggleGroupControlOption,{label:(0,i.__)("Persons","rrze-faudir"),value:"person"}),(0,r.jsx)(s.__experimentalToggleGroupControlOption,{label:(0,i.__)("Organization or FAUdir-Folder","rrze-faudir"),value:"org"})]})}),(0,r.jsx)("hr",{}),a?(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalSpacer,{paddingTop:"1rem",children:(0,r.jsx)(s.__experimentalHeading,{level:2,children:(0,i.__)("Select Organization or FAUdir-Folder to display","rrze-faudir")})}),(0,r.jsxs)(s.__experimentalSpacer,{paddingTop:"1.5rem",paddingBottom:"1rem",children:[(0,r.jsx)(f,{attributes:e,setAttributes:t,label:(0,i.__)("Display via FAUOrg Number","rrze-faudir"),helpText:(0,i.__)("To display an Institution as contact, insert your FAUOrg Number (Cost center number).","rrze-faudir")}),(0,r.jsx)(C,{attributes:e,setAttributes:t})]})]}):(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalSpacer,{paddingTop:"1rem",children:(0,r.jsx)(s.__experimentalHeading,{level:2,children:(0,i.__)("Select Contacts to display","rrze-faudir")})}),(0,r.jsx)("div",{style:{minWidth:"100%"},children:(0,r.jsxs)(s.Panel,{children:[(0,r.jsx)(s.PanelBody,{title:(0,i.__)("Select Contacts from your WordPress Site","rrze-faudir"),initialOpen:!0,children:(0,r.jsx)(r.Fragment,{children:(0,r.jsxs)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1.5rem",children:[(0,r.jsx)(g,{isLoadingPosts:n,posts:l,selectedPosts:u,togglePostSelection:c}),(0,r.jsx)(m,{categories:p,selectedCategory:e.selectedCategory,setAttributes:t})]})})}),(0,r.jsx)(s.PanelBody,{title:(0,i.__)("Select Contacts directly from FAUdir","rrze-faudir"),initialOpen:!1,children:(0,r.jsxs)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1.5rem",children:[(0,r.jsx)(f,{attributes:e,setAttributes:t}),(0,r.jsx)(A,{attributes:e,setAttributes:t})]})}),(0,r.jsx)(s.PanelBody,{title:(0,i.__)("Sorting options","rrze-faudir"),initialOpen:!1,children:(0,r.jsxs)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1.5rem",children:[(0,r.jsx)(w,{attributes:e,setAttributes:t}),(0,r.jsx)(P,{attributes:e,setAttributes:t})]})})]})})]}),(0,r.jsx)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1.5rem",children:(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.Button,{variant:"secondary",onClick:()=>{y(!0)},children:(0,i.__)("Change Appearance","rrze-faudir")}),(0,r.jsx)(s.Button,{variant:"primary",onClick:j,children:(0,i.__)("Finish initial setup","rrze-faudir")})]})}),(0,r.jsx)("hr",{}),(0,r.jsxs)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1.5rem",children:[(0,r.jsx)(s.__experimentalHeading,{level:2,children:(0,i.__)("Preview","rrze-faudir")}),(0,r.jsx)(d,{attributes:e})]})]})})})}const O=JSON.parse('{"UU":"rrze-faudir/block"}'),B=[{attributes:{selectedCategory:{type:"string",default:""},selectedPosts:{type:"array",default:[]},selectedPersonIds:{type:"array",default:[]},selectedFormat:{type:"string",default:"kompakt"},selectedFields:{type:"array",default:[]},role:{type:"string",default:""},orgnr:{type:"string",default:""},url:{type:"string",default:""},hideFields:{type:"array",default:[]},showCategory:{type:"boolean",default:!1},showPosts:{type:"boolean",default:!1},sort:{type:"string",default:"familyName"},format_displayname:{type:"string",default:""}},save:null,migrate:e=>Object.assign(Object.assign({},e),{initialSetup:!1})}];(0,t.registerBlockType)(O.UU,{edit:function({attributes:e,setAttributes:t}){const[n,l]=(0,o.useState)([]),[u,p]=(0,o.useState)([]),[y,j]=(0,o.useState)(!1),[O,B]=(0,o.useState)(null),[U,N]=(0,o.useState)("org"===e.display),[E,k]=(0,o.useState)(!1),D=(0,a.useBlockProps)(),{selectedCategory:I="",selectedPosts:L=[],showCategory:H=!1,showPosts:R=!1,selectedPersonIds:G=[],role:M="",orgnr:V="",initialSetup:W}=e,J=()=>{t({initialSetup:!W})};(0,o.useEffect)((()=>{t({display:U?"org":"person"})}),[U]),(0,o.useEffect)((()=>{e.selectedFields&&0!==e.selectedFields.length||c()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{if(null==e?void 0:e.default_output_fields){const r=e.default_output_fields.map((e=>_[e])).filter((e=>void 0!==e));t({selectedFields:r})}})).catch((e=>{console.error("Error fetching default fields:",e)}))}),[]),(0,o.useEffect)((()=>{c()({path:"/wp/v2/custom_taxonomy?per_page=100"}).then((e=>{l(e)})).catch((e=>{console.error("Error fetching categories:",e)}))}),[]),(0,o.useEffect)((()=>{c()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{var t;(null===(t=null==e?void 0:e.default_organization)||void 0===t?void 0:t.orgnr)&&B(e.default_organization)})).catch((e=>{console.error("Error fetching default organization number:",e)}))}),[]),(0,o.useEffect)((()=>{j(!0);const e={per_page:100,_fields:"id,title,meta",orderby:"title",order:"asc"};I&&(e.custom_taxonomy=I),c()({path:"/wp/v2/custom_person?per_page=100",params:e}).then((e=>{p(e),j(!1)})).catch((e=>{console.error("Error fetching posts:",e),j(!1)}))}),[I,V]);const Z=e=>{const r=L.includes(e)?L.filter((t=>t!==e)):[...L,e],i=r.map((e=>{var t;const r=u.find((t=>t.id===e));return(null===(t=null==r?void 0:r.meta)||void 0===t?void 0:t.person_id)||null})).filter(Boolean);t({selectedPosts:r,selectedPersonIds:i})};return(0,r.jsxs)("div",Object.assign({},D,{children:[(0,r.jsx)(a.BlockControls,{children:(0,r.jsxs)(s.ToolbarGroup,{children:[e.initialSetup&&(0,r.jsx)(s.ToolbarItem,{children:()=>(0,r.jsx)(r.Fragment,{children:(0,r.jsx)(s.ToolbarButton,{icon:E?v:z,label:E?(0,i.__)("Change the Data","rrze-faudir"):(0,i.__)("Change the Appearance","rrze-faudir"),onClick:()=>{k(!E)}})})}),(0,r.jsx)(s.ToolbarItem,{children:()=>(0,r.jsx)(r.Fragment,{children:(0,r.jsx)(s.ToolbarButton,{icon:e.initialSetup?S:F,label:e.initialSetup?(0,i.__)("Finish configuration","rrze-faudir"):(0,i.__)("Configure your contact","rrze-faudir"),onClick:J})})})]})}),(0,r.jsxs)(a.InspectorControls,{children:[(0,r.jsxs)(s.PanelBody,{title:(0,i.__)("Data Selection","rrze-faudir"),children:[(0,r.jsxs)(s.__experimentalToggleGroupControl,{__next40pxDefaultSize:!0,__nextHasNoMarginBottom:!0,isBlock:!0,label:(0,i.__)("What type of Contact do you want to display?","rrze-faudir"),help:(0,i.__)("Do you want to output a Person entry or a FAUdir Institution/Folder?","rrze-faudir"),onChange:e=>N("person"!==e),value:U?"org":"person",children:[(0,r.jsx)(s.__experimentalToggleGroupControlOption,{label:(0,i.__)("Persons","rrze-faudir"),value:"person"}),(0,r.jsx)(s.__experimentalToggleGroupControlOption,{label:(0,i.__)("Organization or FAUdir-Folder","rrze-faudir"),value:"org"})]}),U?(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(f,{attributes:e,setAttributes:t}),(0,r.jsx)(C,{attributes:e,setAttributes:t})]}):(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(g,{isLoadingPosts:y,posts:u,selectedPosts:L,togglePostSelection:Z}),(0,r.jsx)(m,{categories:n,selectedCategory:I,setAttributes:t}),(0,r.jsx)(f,{attributes:e,setAttributes:t}),(0,r.jsx)(A,{attributes:e,setAttributes:t})]})]}),(0,r.jsxs)(s.PanelBody,{title:(0,i.__)("Appearance","rrze-faudir"),initialOpen:!0,children:[(0,r.jsx)(h,{attributes:e,setAttributes:t}),(0,r.jsx)(x,{attributes:e,setAttributes:t}),(0,r.jsx)(b,{attributes:e,setAttributes:t})]}),"org"!==e.display&&(0,r.jsxs)(s.PanelBody,{title:(0,i.__)("Sorting","rrze-faudir"),initialOpen:!1,children:[(0,r.jsx)(w,{attributes:e,setAttributes:t}),(0,r.jsx)(P,{attributes:e,setAttributes:t})]})]}),(0,r.jsx)(r.Fragment,{children:W?(0,r.jsx)(T,{attributes:e,setAttributes:t,isOrg:U,setIsOrg:N,isLoadingPosts:y,posts:u,selectedPosts:L,togglePostSelection:Z,categories:n,isAppearancePanelOpen:E,setIsAppearancePanelOpen:k}):(0,r.jsx)(d,{attributes:e})})]}))},save:function(){return null},deprecated:B})})();
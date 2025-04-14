(()=>{"use strict";var e={n:t=>{var r=t&&t.__esModule?()=>t.default:()=>t;return e.d(r,{a:r}),r},d:(t,r)=>{for(var i in r)e.o(r,i)&&!e.o(t,i)&&Object.defineProperty(t,i,{enumerable:!0,get:r[i]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const t=window.wp.blocks,r=window.ReactJSXRuntime,i=window.wp.i18n,a=window.wp.blockEditor,s=window.wp.components,n=window.wp.element,o=window.wp.serverSideRender;var l=e.n(o);function d(){return(0,r.jsx)(s.Placeholder,{label:(0,i.__)("FAUdir Preview…","rrze-faudir"),children:(0,r.jsxs)("div",{children:[(0,r.jsx)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1rem",children:(0,r.jsx)(s.ProgressBar,{})}),(0,r.jsx)(s.__experimentalText,{children:(0,i.__)("The Preview is loading…","rrze-faudir")})]})})}function u(){return(0,r.jsx)(s.Placeholder,{label:(0,i.__)("FAUdir Preview…","rrze-faudir"),children:(0,r.jsx)("div",{children:(0,r.jsx)(s.__experimentalText,{children:(0,i.__)("Your current configuration does not return a contact. Try adjusting your filter settings.","rrze-faudir")})})})}function c({attributes:e}){const[t,i]=(0,n.useState)(0);return(0,n.useEffect)((()=>{i((e=>e+1))}),[e.orgnr,e.selectedCategory,e.orgid,e.display]),(0,r.jsx)(l(),{block:"rrze-faudir/block",attributes:{role:e.role,orgnr:e.orgnr,orgid:e.orgid,selectedFormat:e.selectedFormat,selectedFields:e.selectedFields,selectedCategory:e.selectedCategory,selectedPersonIds:e.selectedPersonIds,hideFields:e.hideFields,url:e.url,sort:e.sort,format_displayname:e.format_displayname,display:e.display,identifier:e.identifier},LoadingResponsePlaceholder:d,EmptyResponsePlaceholder:u},t)}const p=window.wp.apiFetch;var _=e.n(p);const f={image:(0,i.__)("Image","rrze-faudir"),displayName:(0,i.__)("Display Name","rrze-faudir"),honorificPrefix:(0,i.__)("Academic Title","rrze-faudir"),givenName:(0,i.__)("First Name","rrze-faudir"),familyName:(0,i.__)("Last Name","rrze-faudir"),honorificSuffix:(0,i.__)("Academic Suffix","rrze-faudir"),titleOfNobility:(0,i.__)("Title of Nobility","rrze-faudir"),email:(0,i.__)("Email","rrze-faudir"),phone:(0,i.__)("Phone","rrze-faudir"),organization:(0,i.__)("Organization","rrze-faudir"),jobTitle:(0,i.__)("Jobtitle","rrze-faudir"),url:(0,i.__)("URL","rrze-faudir"),content:(0,i.__)("Content","rrze-faudir"),teasertext:(0,i.__)("Teasertext","rrze-faudir"),socialmedia:(0,i.__)("Social Media and Websites","rrze-faudir"),room:(0,i.__)("Room","rrze-faudir"),floor:(0,i.__)("Floor","rrze-faudir"),address:(0,i.__)("Address","rrze-faudir"),street:(0,i.__)("Street","rrze-faudir"),zip:(0,i.__)("ZIP Code","rrze-faudir"),city:(0,i.__)("City","rrze-faudir"),faumap:(0,i.__)("FAU Map","rrze-faudir"),officehours:(0,i.__)("Office Hours","rrze-faudir"),consultationhours:(0,i.__)("Consultation Hours","rrze-faudir")},m=(Object.keys(f),Object.keys(f),{image:"image",displayname:"displayName",honorificPrefix:"honorificPrefix",givenName:"givenName",familyName:"familyName",honorificSuffix:"honorificSuffix",titleOfNobility:"titleOfNobility",email:"email",phone:"phone",organization:"organization",jobTitle:"jobTitle",url:"url",content:"content",teasertext:"teasertext",socialmedia:"socialmedia",room:"room",floor:"floor",street:"street",zip:"zip",city:"city",faumap:"faumap",officehours:"officehours",consultationhours:"consultationhours",address:"address"});function h({attributes:e,setAttributes:t,label:a,helpText:o}){var l;const[d,u]=(0,n.useState)(""),[c,p]=(0,n.useState)(null!==(l=e.orgnr)&&void 0!==l?l:"");return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalHeading,{level:3,children:(0,i.__)("Display Organization","rrze-faudir")}),(0,r.jsx)(s.TextControl,{label:a||(0,i.__)("FAUOrg Number","rrze-faudir"),value:c,onChange:e=>{let r=e.replace(/\D/g,"");r.length>10&&(r=r.substring(0,10)),p(r),0===r.length?(t({orgnr:""}),u("")):10===r.length?(t({orgnr:r}),u("")):(t({orgnr:""}),u((0,i.__)("Your FAUOrg-Number needs to be exactly 10 digits.","rrze-faudir")))},type:"text",help:d||o||(0,i.__)("To display all Persons from within your Organization, insert your FAUOrg Number (Cost center number).","rrze-faudir")})]})}function g({isLoadingPosts:e,posts:t,selectedPosts:a,togglePostSelection:o}){const l=new Map;t.forEach((e=>{l.set(e.title.rendered,e.id)}));const d=t.filter((e=>a.includes(e.id))).map((e=>e.title.rendered)),u=t.map((e=>e.title.rendered)),[c,p]=(e?(0,i.__)("Loading available contacts...","rrze-faudir"):(0,i.__)("Select Contacts for Display.","rrze-faudir"),(0,n.useState)(!1));return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalHeading,{level:3,children:(0,i.__)("Select Persons","rrze-faudir")}),(0,r.jsx)(s.FormTokenField,{__next40pxDefaultSize:!0,label:(0,i.__)("Type to add persons","rrze-faudir"),value:d,suggestions:u,disabled:e||0===t.length,onChange:e=>{const t=e.map((e=>l.get(e))).filter((e=>"number"==typeof e));t.forEach((e=>{a.includes(e)||o(e)})),a.forEach((e=>{t.includes(e)||o(e)}))}}),(0,r.jsx)(s.__experimentalSpacer,{paddingTop:"0.5rem",paddingBottom:"1rem",children:(0,r.jsx)(s.Button,{variant:"tertiary",onClick:()=>p(!0),disabled:e||0===t.length,children:(0,i.__)("Or choose by List.","rrze-faudir")})}),0===t.length&&(0,r.jsx)(s.Notice,{isDismissible:!1,status:"info",children:(0,i.__)("There are currently no Contacts available. Start adding your first FAUdir Contacts via the WordPress Dashboard > Persons.","rrze-faudir")}),c&&(0,r.jsxs)(s.Modal,{title:(0,i.__)("Select Persons","rrze-faudir"),onRequestClose:()=>p(!1),children:[(0,r.jsx)("p",{children:(0,i.__)("Alternatively, select persons from the checkboxes below:","rrze-faudir")}),t.map((e=>{const t=a.includes(e.id);return(0,r.jsx)(s.CheckboxControl,{label:e.title.rendered,checked:t,onChange:()=>o(e.id)},e.id)})),(0,r.jsx)("div",{style:{marginTop:"1em"},children:(0,r.jsx)(s.Button,{variant:"secondary",onClick:()=>p(!1),children:(0,i.__)("Close","rrze-faudir")})})]})]})}function x({categories:e,selectedCategory:t,setAttributes:a}){const n=t.trim().length>0?t.split(",").map((e=>e.trim())):[],o=e.map((e=>e.name));return(0,r.jsxs)("div",{children:[(0,r.jsx)(s.__experimentalHeading,{level:3,children:(0,i.__)("Select Categories","rrze-faudir")}),(0,r.jsx)(s.FormTokenField,{__next40pxDefaultSize:!0,label:(0,i.__)("Type to add categories","rrze-faudir"),value:n,disabled:0===o.length,suggestions:o,onChange:e=>{const t=e.filter((e=>o.includes(e))).join(", ");a({selectedCategory:t,selectedPosts:[],selectedPersons:[]})}}),0===o.length&&(0,r.jsx)(s.Notice,{isDismissible:!1,status:"info",children:(0,i.__)("There are currently no Categories available. Start adding your first FAUdir Categories via the WordPress Dashboard > Persons > Categories.","rrze-faudir")})]})}function b({attributes:e,setAttributes:t}){const{selectedFormat:a}=e,[o,l]=(0,n.useState)({}),[d,u]=(0,n.useState)([]),[c,p]=(0,n.useState)({});(0,n.useEffect)((()=>{_()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{(null==e?void 0:e.available_formats_by_display)&&l(e.available_formats_by_display),(null==e?void 0:e.format_names)&&p(e.format_names)})).catch((e=>{console.error("Fehler beim Laden der Felder:",e)}))}),[a]),(0,n.useEffect)((()=>{var t;const r=null!==(t="org"===e.display?o.org:o.person)&&void 0!==t?t:[];u(r)}),[e.display,o,a,t]);const f=e=>c[e]||e,m=a||"list",h=(null!=d?d:[]).map((e=>({value:e,label:f(e)})));return(0,r.jsx)(r.Fragment,{children:(0,r.jsx)(s.SelectControl,{label:(0,i.__)("Select Format","rrze-faudir"),value:m,options:h,onChange:e=>{t({selectedFormat:e})}})})}function y({attributes:e,setAttributes:t,setHasFormatDisplayName:a}){const{selectedFormat:o,hideFields:l,selectedFields:d}=e,[u,c]=(0,n.useState)([]),[p,f]=(0,n.useState)([]),[m,h]=(0,n.useState)(l||[]),[g,x]=(0,n.useState)(d||[]),[b,y]=(0,n.useState)({});(0,n.useEffect)((()=>{_()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{const t=e.avaible_fields_byformat[o]||[];if(a(t.includes("format_displayname")),(null==e?void 0:e.default_output_fields)&&c(e.default_output_fields),(null==e?void 0:e.avaible_fields_byformat)&&o){const t=e.avaible_fields_byformat[o]||[];f(t)}(null==e?void 0:e.available_fields)&&y(e.available_fields)})).catch((e=>{console.error("Fehler beim Laden der Felder:",e)}))}),[o,e.display]),(0,n.useEffect)((()=>{t({hideFields:m,selectedFields:g})}),[m,g,o,e.display]);const j=e=>u.includes(e)?!m.includes(e):g.includes(e),v=e=>b[e]||e,z=p.filter((e=>"format_displayname"!==e));return(0,r.jsxs)("div",{children:[(0,r.jsx)("h4",{children:(0,i.__)("Felder auswählen","rrze-faudir")}),z.map((e=>(0,r.jsx)(s.CheckboxControl,{label:v(e),checked:j(e),onChange:()=>(e=>{u.includes(e)?m.includes(e)?h(m.filter((t=>t!==e))):h([...m,e]):g.includes(e)?x(g.filter((t=>t!==e))):x([...g,e])})(e)},e)))]})}function j({attributes:e,setAttributes:t,hasFormatDisplayName:a}){const{format_displayname:n}=e;return(0,r.jsx)(r.Fragment,{children:a&&(0,r.jsx)(s.TextControl,{label:(0,i.__)("Change display format","rrze-faudir"),value:n,onChange:e=>{t({format_displayname:e})},type:"text",help:"Parameter: #givenName#, #displayname#, #familyName#, #honorificPrefix#, #honorificSuffix#, #titleOfNobility#"})})}const v=window.React,z=window.wp.primitives,F=(0,v.createElement)(z.SVG,{viewBox:"0 0 24 24",xmlns:"http://www.w3.org/2000/svg"},(0,v.createElement)(z.Path,{d:"M12 4c-4.4 0-8 3.6-8 8v.1c0 4.1 3.2 7.5 7.2 7.9h.8c4.4 0 8-3.6 8-8s-3.6-8-8-8zm0 15V5c3.9 0 7 3.1 7 7s-3.1 7-7 7z"})),S=(0,v.createElement)(z.SVG,{viewBox:"0 0 24 24",xmlns:"http://www.w3.org/2000/svg"},(0,v.createElement)(z.Path,{d:"M10 4.5a1 1 0 11-2 0 1 1 0 012 0zm1.5 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zm2.25 7.5v-1A2.75 2.75 0 0011 8.25H7A2.75 2.75 0 004.25 11v1h1.5v-1c0-.69.56-1.25 1.25-1.25h4c.69 0 1.25.56 1.25 1.25v1h1.5zM4 20h9v-1.5H4V20zm16-4H4v-1.5h16V16z",fillRule:"evenodd",clipRule:"evenodd"})),C=(0,v.createElement)(z.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"},(0,v.createElement)(z.Path,{d:"m19 7-3-3-8.5 8.5-1 4 4-1L19 7Zm-7 11.5H5V20h7v-1.5Z"})),P=(0,v.createElement)(z.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"},(0,v.createElement)(z.Path,{d:"M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"}));function w({attributes:e,setAttributes:t,label:a,helpText:o}){const[l,d]=(0,n.useState)(e.orgid||""),[u,c]=(0,n.useState)("");return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalHeading,{level:3,children:(0,i.__)("Display Faudir Folder","rrze-faudir")}),(0,r.jsx)(s.TextControl,{label:a||(0,i.__)("Via FAUorg-ID or FAUdir-URL","rrze-faudir"),value:l,onChange:e=>{const r=e.trim(),a=r.match(/^https?:\/\/faudir\.fau\.de\/public\/org\/([^/]+)\/?$/);let s=a?a[1]:r;s?(t({orgid:s}),c("")):(t({orgid:""}),c((0,i.__)("Please enter a valid FAUdir-URL or the identifier.","rrze-faudir"))),d(e)},type:"text",help:u||o||(0,i.__)('Please enter either a FAUdir-URL ("https://faudir.fau.de/public/org/…"), or the Identifier.',"rrze-faudir")})]})}function A({attributes:e,setAttributes:t,label:a,helpText:o}){const[l,d]=(0,n.useState)(e.orgid||""),[u,c]=(0,n.useState)("");return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalHeading,{level:3,children:(0,i.__)("Direct Select via FAUdir","rrze-faudir")}),(0,r.jsx)(s.TextControl,{label:a||(0,i.__)("Via Person Identifier or FAUdir-URL","rrze-faudir"),value:l,onChange:e=>{const r=e.trim(),a=r.match(/^https?:\/\/faudir\.fau\.de\/public\/person\/([^/]+)\/?$/);let s=a?a[1]:r;s?(t({identifier:s}),c("")):(t({identifier:""}),c((0,i.__)("Please enter a valid FAUdir-URL or the identifier.","rrze-faudir"))),d(e)},type:"text",help:u||o||(0,i.__)('Please enter either a FAUdir-URL ("https://faudir.fau.de/public/person/…"), or the Person-Identifier. This will display your contact, even if the contact is not created via Dashboard > Persons.',"rrze-faudir")})]})}function T({attributes:e,setAttributes:t,isOrg:a,setIsOrg:n,isLoadingPosts:o,posts:l,selectedPosts:d,togglePostSelection:u,categories:p,isAppearancePanelOpen:_,setIsAppearancePanelOpen:f,setHasFormatDisplayName:m,hasFormatDisplayName:v}){const z=()=>{t({initialSetup:!1})};return(0,r.jsx)(r.Fragment,{children:(0,r.jsx)(s.Placeholder,{label:(0,i.__)("Setup your FAUdir Block","rrze-faudir"),children:_?(0,r.jsx)(r.Fragment,{children:(0,r.jsx)("div",{children:(0,r.jsx)(s.__experimentalSpacer,{paddingBottom:"1.5rem",paddingTop:"1rem",children:(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalHeading,{level:2,children:(0,i.__)("Configure the appearance of your Contact","rrze-faudir")}),(0,r.jsx)(b,{attributes:e,setAttributes:t}),(0,r.jsx)(y,{attributes:e,setAttributes:t,setHasFormatDisplayName:m}),(0,r.jsx)(j,{attributes:e,setAttributes:t,hasFormatDisplayName:v}),(0,r.jsx)(s.__experimentalSpacer,{paddingTop:"1rem"}),(0,r.jsx)(s.Button,{variant:"tertiary",onClick:()=>{f(!1)},children:(0,i.__)("Back to Data selection","rrze-faudir")}),(0,r.jsx)(s.Button,{variant:"primary",onClick:z,children:(0,i.__)("Finish initial setup","rrze-faudir")}),(0,r.jsx)("hr",{}),(0,r.jsxs)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1.5rem",children:[(0,r.jsx)(s.__experimentalHeading,{level:2,children:(0,i.__)("Preview","rrze-faudir")}),(0,r.jsx)(c,{attributes:e})]})]})})})}):(0,r.jsxs)("div",{style:{minWidth:"100%"},children:[(0,r.jsxs)(s.__experimentalSpacer,{paddingBottom:"1.5rem",paddingTop:"1rem",children:[(0,r.jsx)(s.__experimentalHeading,{level:2,children:(0,i.__)("Which type of contact would you like to display?","rrze-faudir")}),(0,r.jsxs)(s.__experimentalToggleGroupControl,{__next40pxDefaultSize:!0,__nextHasNoMarginBottom:!0,isBlock:!0,label:(0,i.__)("Contact type","rrze-faudir"),help:(0,i.__)("Do you want to output a Person entry or a FAUdir Institution/Folder?","rrze-faudir"),onChange:e=>n("person"!==e),value:a?"org":"person",children:[(0,r.jsx)(s.__experimentalToggleGroupControlOption,{label:(0,i.__)("Persons","rrze-faudir"),value:"person"}),(0,r.jsx)(s.__experimentalToggleGroupControlOption,{label:(0,i.__)("Organization or FAUdir-Folder","rrze-faudir"),value:"org"})]})]}),(0,r.jsx)("hr",{}),a?(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalSpacer,{paddingTop:"1rem",children:(0,r.jsx)(s.__experimentalHeading,{level:2,children:(0,i.__)("Select Organization or FAUdir-Folder to display","rrze-faudir")})}),(0,r.jsxs)(s.__experimentalSpacer,{paddingTop:"1.5rem",paddingBottom:"1rem",children:[(0,r.jsx)(h,{attributes:e,setAttributes:t,label:(0,i.__)("Display via FAUOrg Number","rrze-faudir"),helpText:(0,i.__)("To display an Institution as contact, insert your FAUOrg Number (Cost center number).","rrze-faudir")}),(0,r.jsx)(w,{attributes:e,setAttributes:t})]})]}):(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.__experimentalSpacer,{paddingTop:"1rem",children:(0,r.jsx)(s.__experimentalHeading,{level:2,children:(0,i.__)("Select Contacts to display","rrze-faudir")})}),(0,r.jsx)("div",{style:{minWidth:"100%"},children:(0,r.jsxs)(s.Panel,{children:[(0,r.jsx)(s.PanelBody,{title:(0,i.__)("Select Contacts from your WordPress Site","rrze-faudir"),initialOpen:!1,children:(0,r.jsx)(r.Fragment,{children:(0,r.jsxs)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1.5rem",children:[(0,r.jsx)(g,{isLoadingPosts:o,posts:l,selectedPosts:d,togglePostSelection:u}),(0,r.jsx)(x,{categories:p,selectedCategory:e.selectedCategory,setAttributes:t})]})})}),(0,r.jsx)(s.PanelBody,{title:(0,i.__)("Select Contacts directly from FAUdir","rrze-faudir"),initialOpen:!1,children:(0,r.jsxs)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1.5rem",children:[(0,r.jsx)(h,{attributes:e,setAttributes:t}),(0,r.jsx)(A,{attributes:e,setAttributes:t})]})})]})})]}),(0,r.jsx)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1.5rem",children:(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.Button,{variant:"tertiary",onClick:z,children:(0,i.__)("Finish initial setup","rrze-faudir")}),(0,r.jsx)(s.Button,{variant:"primary",onClick:()=>{f(!0)},children:(0,i.__)("Step 2: Change Appearance","rrze-faudir")})]})}),(0,r.jsx)("hr",{}),(0,r.jsxs)(s.__experimentalSpacer,{paddingTop:"1rem",paddingBottom:"1.5rem",children:[(0,r.jsx)(s.__experimentalHeading,{level:2,children:(0,i.__)("Preview","rrze-faudir")}),(0,r.jsx)(c,{attributes:e})]})]})})})}function O({attributes:e,setAttributes:t}){const[a,o]=(0,n.useState)({}),[l,d]=(0,n.useState)([]);(0,n.useEffect)((()=>{_()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{(null==e?void 0:e.person_roles)&&o(e.person_roles)})).catch((e=>{console.error("Fehler beim Laden der person_roles:",e)}))}),[]);const u=Object.entries(a).map((([e,t])=>({value:e,label:t})));return(0,n.useEffect)((()=>{t({role:l.join(",")})}),[l]),(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(s.ComboboxControl,{options:u,onChange:e=>{d([...l,e])},label:(0,i.__)("Filter by Role","rrze-faudir"),help:(0,i.__)("Select a category to filter the person entries by.","rrze-faudir"),allowReset:!1,value:""}),l.length>0&&(0,r.jsx)(s.FormTokenField,{value:l,label:(0,i.__)("Currently selected role filters.","rrze-faudir"),onChange:e=>{d(e)}})]})}function N({attributes:e,setAttributes:t}){const{sort:a}=e;return(0,r.jsx)(s.SelectControl,{label:(0,i.__)("Sort by","rrze-faudir"),value:a,options:[{value:"familyName",label:(0,i.__)("Last Name","rrze-faudir")},{value:"title_familyName",label:(0,i.__)("Title and Last Name","rrze-faudir")},{value:"head_first",label:(0,i.__)("Head of Department First","rrze-faudir")},{value:"prof_first",label:(0,i.__)("Professors First","rrze-faudir")},{value:"identifier_order",label:(0,i.__)("Identifier Order","rrze-faudir")}],onChange:e=>{t({sort:e})}})}const B=[{attributes:{selectedCategory:{type:"string",default:""},selectedPosts:{type:"array",default:[]},selectedPersonIds:{type:"array",default:[]},selectedFormat:{type:"string",default:"kompakt"},selectedFields:{type:"array",default:[]},role:{type:"string",default:""},orgnr:{type:"string",default:""},url:{type:"string",default:""},hideFields:{type:"array",default:[]},showCategory:{type:"boolean",default:!1},showPosts:{type:"boolean",default:!1},sort:{type:"string",default:"familyName"},format_displayname:{type:"string",default:""}},save:()=>null,migrate:e=>{const t=Object.assign(Object.assign({},e),{initialSetup:!1});return"kompakt"===t.selectedFormat&&(t.selectedFormat="compact"),t},isEligible:({initialSetup:e})=>void 0===e}],U=JSON.parse('{"UU":"rrze-faudir/block"}');(0,t.registerBlockType)(U.UU,{edit:function({attributes:e,setAttributes:t}){const[o,l]=(0,n.useState)([]),[d,u]=(0,n.useState)([]),[p,f]=(0,n.useState)(!1),[v,z]=(0,n.useState)(null),[B,U]=(0,n.useState)("org"===e.display),[k,D]=(0,n.useState)(!1),[E,H]=(0,n.useState)(!1),L=(0,a.useBlockProps)(),{selectedCategory:I="",selectedPosts:R=[],showCategory:G=!1,showPosts:M=!1,selectedPersonIds:V=[],role:W="",orgnr:J="",initialSetup:Z}=e,Y=()=>{t({initialSetup:!Z})};(0,n.useEffect)((()=>{t({display:B?"org":"person"})}),[B]),(0,n.useEffect)((()=>{e.selectedFields&&0!==e.selectedFields.length||_()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{if(null==e?void 0:e.default_output_fields){const r=e.default_output_fields.map((e=>m[e])).filter((e=>void 0!==e));t({selectedFields:r})}})).catch((e=>{console.error("Error fetching default fields:",e)}))}),[]),(0,n.useEffect)((()=>{_()({path:"/wp/v2/custom_taxonomy?per_page=100"}).then((e=>{l(e)})).catch((e=>{console.error("Error fetching categories:",e)}))}),[]),(0,n.useEffect)((()=>{_()({path:"/wp/v2/settings/rrze_faudir_options"}).then((e=>{var t;(null===(t=null==e?void 0:e.default_organization)||void 0===t?void 0:t.orgnr)&&z(e.default_organization)})).catch((e=>{console.error("Error fetching default organization number:",e)}))}),[]),(0,n.useEffect)((()=>{f(!0);const e={per_page:100,_fields:"id,title,meta",orderby:"title",order:"asc"};I&&(e.custom_taxonomy=I),_()({path:"/wp/v2/custom_person?per_page=100",params:e}).then((e=>{u(e),f(!1)})).catch((e=>{console.error("Error fetching posts:",e),f(!1)}))}),[I,J]);const $=e=>{const r=R.includes(e)?R.filter((t=>t!==e)):[...R,e],i=r.map((e=>{var t;const r=d.find((t=>t.id===e));return(null===(t=null==r?void 0:r.meta)||void 0===t?void 0:t.person_id)||null})).filter(Boolean);t({selectedPosts:r,selectedPersonIds:i})};return(0,r.jsxs)("div",Object.assign({},L,{children:[(0,r.jsx)(a.BlockControls,{children:(0,r.jsxs)(s.ToolbarGroup,{children:[e.initialSetup&&(0,r.jsx)(s.ToolbarItem,{children:()=>(0,r.jsx)(r.Fragment,{children:(0,r.jsx)(s.ToolbarButton,{icon:k?S:F,label:k?(0,i.__)("Change the Data","rrze-faudir"):(0,i.__)("Change the Appearance","rrze-faudir"),onClick:()=>{D(!k)}})})}),(0,r.jsx)(s.ToolbarItem,{children:()=>(0,r.jsx)(r.Fragment,{children:(0,r.jsx)(s.ToolbarButton,{icon:e.initialSetup?P:C,label:e.initialSetup?(0,i.__)("Finish configuration","rrze-faudir"):(0,i.__)("Configure your contact","rrze-faudir"),onClick:Y})})})]})}),(0,r.jsxs)(a.InspectorControls,{children:[(0,r.jsxs)(s.PanelBody,{title:(0,i.__)("Data Selection","rrze-faudir"),initialOpen:!Z,children:[(0,r.jsxs)(s.__experimentalToggleGroupControl,{__next40pxDefaultSize:!0,__nextHasNoMarginBottom:!0,isBlock:!0,label:(0,i.__)("What type of Contact do you want to display?","rrze-faudir"),help:(0,i.__)("Do you want to output a Person entry or a FAUdir Institution/Folder?","rrze-faudir"),onChange:e=>U("person"!==e),value:B?"org":"person",children:[(0,r.jsx)(s.__experimentalToggleGroupControlOption,{label:(0,i.__)("Persons","rrze-faudir"),value:"person"}),(0,r.jsx)(s.__experimentalToggleGroupControlOption,{label:(0,i.__)("Organization or FAUdir-Folder","rrze-faudir"),value:"org"})]}),B?(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)("hr",{}),(0,r.jsx)(h,{attributes:e,setAttributes:t}),(0,r.jsx)("hr",{}),(0,r.jsx)(w,{attributes:e,setAttributes:t})]}):(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)("hr",{}),(0,r.jsx)(g,{isLoadingPosts:p,posts:d,selectedPosts:R,togglePostSelection:$}),(0,r.jsx)("hr",{}),(0,r.jsx)(x,{categories:o,selectedCategory:I,setAttributes:t}),(0,r.jsx)("hr",{}),(0,r.jsx)(h,{attributes:e,setAttributes:t}),(0,r.jsx)("hr",{}),(0,r.jsx)(A,{attributes:e,setAttributes:t})]})]}),(0,r.jsxs)(s.PanelBody,{title:(0,i.__)("Appearance","rrze-faudir"),initialOpen:!1,children:[(0,r.jsx)(b,{attributes:e,setAttributes:t}),(0,r.jsx)("hr",{}),(0,r.jsx)(y,{attributes:e,setAttributes:t,setHasFormatDisplayName:H}),(0,r.jsx)("hr",{}),(0,r.jsx)(j,{attributes:e,setAttributes:t,hasFormatDisplayName:E})]}),"org"!==e.display&&(0,r.jsxs)(s.PanelBody,{title:(0,i.__)("Sorting","rrze-faudir"),initialOpen:!1,children:[(0,r.jsx)(N,{attributes:e,setAttributes:t}),(0,r.jsx)("hr",{}),(0,r.jsx)(O,{attributes:e,setAttributes:t})]})]}),(0,r.jsx)(r.Fragment,{children:Z?(0,r.jsx)(T,{attributes:e,setAttributes:t,isOrg:B,setIsOrg:U,isLoadingPosts:p,posts:d,selectedPosts:R,togglePostSelection:$,categories:o,isAppearancePanelOpen:k,setIsAppearancePanelOpen:D,setHasFormatDisplayName:H,hasFormatDisplayName:E}):(0,r.jsx)(c,{attributes:e})})]}))},save:function(){return null},deprecated:B})})();
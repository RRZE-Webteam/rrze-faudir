(() => {
    "use strict";
    const e = window.wp.i18n,
        t = window.wp.blocks,
        l = window.wp.blockEditor,
        a = ({
            format: e,
            attributes: t,
            persons: l
        }) => {
            const a = l.filter((e => t.identifier.includes(e.value)))[0] || {
                    label: "Example Person"
                },
                n = {
                    padding: "15px",
                    border: "1px solid #ddd",
                    borderRadius: "4px",
                    backgroundColor: "#f9f9f9"
                };
            switch (e) {
                case "list":
                    return wp.element.createElement("div", {
                        style: n
                    }, wp.element.createElement("ul", {
                        style: {
                            listStyle: "none",
                            padding: 0
                        }
                    }, wp.element.createElement("li", null, wp.element.createElement("strong", null, a.label), wp.element.createElement("br"), "Email: example@fau.de", wp.element.createElement("br"), "Phone: +49 123 456789")));
                case "card":
                    return wp.element.createElement("div", {
                        style: {
                            ...n,
                            display: "flex",
                            gap: "15px"
                        }
                    }, wp.element.createElement("div", {
                        style: {
                            width: "100px",
                            height: "100px",
                            backgroundColor: "#ddd"
                        }
                    }, "Image"), wp.element.createElement("div", null, wp.element.createElement("strong", null, a.label), wp.element.createElement("br"), "Email: example@fau.de", wp.element.createElement("br"), "Phone: +49 123 456789"));
                case "table":
                    return wp.element.createElement("div", {
                        style: n
                    }, wp.element.createElement("table", {
                        style: {
                            width: "100%"
                        }
                    }, wp.element.createElement("tr", null, wp.element.createElement("td", null, "Name:"), wp.element.createElement("td", null, a.label)), wp.element.createElement("tr", null, wp.element.createElement("td", null, "Email:"), wp.element.createElement("td", null, "example@fau.de"))));
                case "kompakt":
                    return wp.element.createElement("div", {
                        style: {
                            ...n,
                            display: "flex",
                            gap: "10px",
                            alignItems: "center"
                        }
                    }, wp.element.createElement("div", {
                        style: {
                            width: "50px",
                            height: "50px",
                            backgroundColor: "#ddd"
                        }
                    }, ""), wp.element.createElement("div", null, wp.element.createElement("strong", null, a.label), wp.element.createElement("br"), "Email: example@fau.de"));
                case "page":
                    return wp.element.createElement("div", {
                        style: n
                    }, wp.element.createElement("h3", null, a.label), wp.element.createElement("div", {
                        style: {
                            display: "flex",
                            gap: "20px"
                        }
                    }, wp.element.createElement("div", {
                        style: {
                            width: "150px",
                            height: "150px",
                            backgroundColor: "#ddd"
                        }
                    }, "Image"), wp.element.createElement("div", null, "Contact Information", wp.element.createElement("br"), "Email: example@fau.de", wp.element.createElement("br"), "Phone: +49 123 456789", wp.element.createElement("br"), "Organization: FAU")));
                default:
                    return wp.element.createElement("div", null, "Select a format")
            }
        };
    (0, t.registerBlockType)("rrze/faudir-block", {
        apiVersion: 3,
        title: (0, e.__)("FAUDIR Block", "rrze-faudir"),
        icon: "admin-users",
        category: "rrze-blocks",
        supports: {
            html: !1,
            reusable: !0,
            lock: !1
        },
        attributes: {
            identifier: {
                type: "array",
                default: []
            },
            format: {
                type: "string",
                default: "kompakt"
            },
            url: {
                type: "string",
                default: ""
            },
            show: {
                type: "string",
                default: ""
            },
            image: {
                type: "number",
                default: 0
            },
            groupid: {
                type: "string",
                default: ""
            },
            orgnr: {
                type: "string",
                default: ""
            }
        },
        edit: function(t) {
            const n = (0, l.useBlockProps)(),
                [r, m] = wp.element.useState([]),
                [i, o] = wp.element.useState([]),
                [c, s] = wp.element.useState([]),
                [p, u] = wp.element.useState(!0),
                [d, b] = wp.element.useState(null),
                [w, f] = wp.element.useState(""),
                [E, g] = wp.element.useState(!1);
            wp.element.useEffect((() => {
                u(!0), b(null), wp.apiFetch({
                    path: "/wp/v2/custom_person?per_page=100&_fields=id,title,meta.person_id,meta.person_name,custom_taxonomy"
                }).then((e => {
                    console.log("Persons response:", e);
                    const t = e.map((e => ({
                        label: e.meta?.person_name || e.title.rendered,
                        value: e.meta?.person_id || "",
                        categories: e.custom_taxonomy || []
                    }))).filter((e => e.value));
                    m(t), o(t), u(!1)
                })).catch((e => {
                    console.error("Error fetching persons:", e), b("Error loading persons data"), u(!1)
                })), wp.apiFetch({
                    path: "/wp/v2/custom_taxonomy?per_page=100"
                }).then((e => {
                    const t = e.map((e => ({
                        label: e.name,
                        value: e.id.toString()
                    })));
                    s(t)
                })).catch((e => {
                    console.error("Error fetching categories:", e)
                }))
            }), []);
            const {
                attributes: {
                    category: v,
                    identifier: _,
                    format: k,
                    url: h,
                    show: y,
                    groupid: z,
                    orgnr: x,
                    image: N
                },
                setAttributes: C
            } = t, S = [{
                value: "displayName",
                label: (0, e.__)("Display Name", "rrze-faudir")
            }, {
                value: "personalTitle",
                label: (0, e.__)("Personal Title", "rrze-faudir")
            }, {
                value: "givenName",
                label: (0, e.__)("First Name", "rrze-faudir")
            }, {
                value: "familyName",
                label: (0, e.__)("Family Name", "rrze-faudir")
            }, {
                value: "personalTitleSuffix",
                label: (0, e.__)("Academic Suffix", "rrze-faudir")
            }, {
                value: "email",
                label: (0, e.__)("Email", "rrze-faudir")
            }, {
                value: "phone",
                label: (0, e.__)("Phone", "rrze-faudir")
            }, {
                value: "organization",
                label: (0, e.__)("Organization", "rrze-faudir")
            }, {
                value: "function",
                label: (0, e.__)("Function", "rrze-faudir")
            }, {
                value: "url",
                label: (0, e.__)("URL", "rrze-faudir")
            }, {
                value: "kompaktButton",
                label: (0, e.__)("Kompakt Button", "rrze-faudir")
            }, {
                value: "content",
                label: (0, e.__)("Content", "rrze-faudir")
            }, {
                value: "teasertext",
                label: (0, e.__)("Teasertext", "rrze-faudir")
            }, {
                value: "socialmedia",
                label: (0, e.__)("Social Media", "rrze-faudir")
            },{
                value: "workplaces",
                label: (0, e.__)("Workplaces", "rrze-faudir")
            },{
                value: "room",
                label: (0, e.__)("Room", "rrze-faudir")
            }, {
                value: "floor",
                label: (0, e.__)("Floor", "rrze-faudir")
            }, {
                value: "street",
                label: (0, e.__)("Street", "rrze-faudir")
            }, {
                value: "zip",
                label: (0, e.__)("Zip", "rrze-faudir")
            }, {
                value: "city",
                label: (0, e.__)("City", "rrze-faudir")
            }, {
                value: "faumap",
                label: (0, e.__)("Fau Map", "rrze-faudir")
            }, {
                value: "officehours",
                label: (0, e.__)("Office Hours", "rrze-faudir")
            }, {
                value: "consultationhours",
                label: (0, e.__)("Consultation Hours", "rrze-faudir")
            }], I = t.attributes.show.split(", ").filter(Boolean);
            return p ? wp.element.createElement("div", {
                className: "wp-block-rrze-faudir-block loading"
            }, "Loading...") : d ? wp.element.createElement("div", {
                className: "wp-block-rrze-faudir-block error"
            }, d) : wp.element.createElement("div", {
                ...n
            }, E ? wp.element.createElement(wp.element.Fragment, null, wp.element.createElement("div", {
                className: "block-label"
            }, wp.element.createElement(wp.components.SelectControl, {
                label: (0, e.__)("Category", "rrze-faudir"),
                value: t.attributes.category,
                options: [{
                    label: (0, e.__)("Select a category...", "rrze-faudir"),
                    value: ""
                }, ...c],
                onChange: e => {
                    t.setAttributes({
                        identifier: []
                    });
                    const l = r.filter((t => t.categories.includes(parseInt(e)))).map((e => e.value)),
                        a = t.attributes.identifier || [],
                        n = [...new Set([...a, ...l])];
                    t.setAttributes({
                        identifier: n
                    })
                }
            })), wp.element.createElement("div", {
                className: "block-label"
            }, wp.element.createElement(wp.components.SelectControl, {
                label: (0, e.__)("Add Person", "rrze-faudir"),
                value: "",
                options: [{
                    label: (0, e.__)("Select a person...", "rrze-faudir"),
                    value: ""
                }, ...r],
                onChange: e => {
                    if (e) {
                        const l = [...t.attributes.identifier];
                        l.includes(e) || t.setAttributes({
                            identifier: [...l, e]
                        })
                    }
                }
            })), wp.element.createElement("div", {
                className: "selected-persons"
            }, wp.element.createElement("h4", null, (0, e.__)("Selected Persons:", "rrze-faudir")), t.attributes.identifier.length > 0 ? t.attributes.identifier.map((e => {
                const l = r.find((t => t.value === e)),
                    a = l?.categories?.includes(parseInt(t.attributes.category));
                return wp.element.createElement("div", {
                    key: e,
                    className: "selected-person" + (a ? " in-category" : "")
                }, wp.element.createElement("span", null, l ? l.label : e), wp.element.createElement("button", {
                    onClick: () => {
                        const l = t.attributes.identifier.filter((t => t !== e));
                        t.setAttributes({
                            identifier: l
                        })
                    },
                    className: "remove-person"
                }, "×"))
            })) : wp.element.createElement("p", null, (0, e.__)("No persons selected", "rrze-faudir"))), wp.element.createElement("label", {
                className: "block-label"
            }, null, (0, e.__)("Format", "rrze-faudir"), wp.element.createElement("label", {
                className: "block-label",
                type: "text",
                value: "Format"
            }), wp.element.createElement("select", {
                className: "block-label",
                value: k,
                onChange: function(e) {
                    C({
                        format: e.target.value
                    })
                }
            }, wp.element.createElement("option", {
                value: "list"
            }, "List"), wp.element.createElement("option", {
                value: "table"
            }, "Table"), wp.element.createElement("option", {
                value: "card"
            }, "Card"), wp.element.createElement("option", {
                value: "kompakt"
            }, "Kompakt"), wp.element.createElement("option", {
                value: "page"
            }, "Page"))), wp.element.createElement("label", {
                className: "block-label"
            }, null, "Url", wp.element.createElement("label", {
                className: "block-label",
                type: "text",
                value: "Url"
            }), wp.element.createElement("input", {
                className: "block-label",
                type: "text",
                value: h,
                onChange: function(e) {
                    C({
                        url: e.target.value
                    })
                }
            })), wp.element.createElement("div", {
                className: "block-container"
            }, wp.element.createElement("label", {
                className: "block-label"
            }, (0, e.__)("Show Fields", "rrze-faudir")), S.map((e => wp.element.createElement("div", {
                key: e.value,
                className: "checkbox-container block-label"
            }, wp.element.createElement("input", {
                type: "checkbox",
                className: "checkbox-input",
                checked: I.includes(e.value),
                onChange: () => (e => {
                    let l = [...I];
                    l.includes(e) ? l = l.filter((t => t !== e)) : l.push(e), t.setAttributes({
                        show: l.join(", ")
                    })
                })(e.value)
            }), wp.element.createElement("span", null, e.label))))), wp.element.createElement("label", {
                className: "block-label"
            }, null, (0, e.__)("Group Id", "rrze-faudir"), wp.element.createElement("label", {
                className: "block-label",
                type: "text",
                value: "Group Id"
            }), wp.element.createElement("input", {
                className: "block-label",
                type: "text",
                value: z,
                onChange: function(e) {
                    C({
                        groupid: e.target.value
                    })
                }
            })), wp.element.createElement("label", {
                className: "block-label"
            }, null, (0, e.__)("Organization number", "rrze-faudir"), wp.element.createElement("label", {
                className: "block-label",
                type: "text",
                value: "Organization number"
            }), wp.element.createElement("input", {
                className: "block-label",
                type: "text",
                value: x,
                onChange: function(e) {
                    C({
                        orgnr: e.target.value
                    })
                }
            })), wp.element.createElement(wp.blockEditor.MediaUpload, {
                onSelect: e => {
                    C({
                        image: e.id
                    })
                },
                allowedTypes: ["image"],
                value: N,
                render: function(t) {
                    return wp.element.createElement("div", null, wp.element.createElement("button", {
                        onClick: t.open,
                        className: "button button-secondary block-label"
                    }, N ? (0, e.__)("Change Image", "rrze-faudir") : (0, e.__)("Select Image", "rrze-faudir")), N && wp.element.createElement("img", {
                        src: wp.media.attachment(N).attributes.url,
                        alt: "Selected Image",
                        style: {
                            maxWidth: "100%",
                            height: "auto"
                        }
                    }))
                }
            }), wp.element.createElement("button", {
                className: "components-button is-primary",
                onClick: () => g(!1)
            }, (0, e.__)("Done", "rrze-faudir"))) : wp.element.createElement((({
                attributes: t,
                onClick: l,
                persons: n
            }) => wp.element.createElement("div", {
                className: "faudir-block-preview",
                onClick: l
            }, wp.element.createElement("div", {
                className: "preview-header"
            }, wp.element.createElement("h3", null, "FAUDIR Block"), wp.element.createElement("button", {
                className: "edit-button",
                onClick: e => {
                    e.stopPropagation(), l()
                }
            }, (0, e.__)("Edit", "rrze-faudir"))), wp.element.createElement(a, {
                format: t.format,
                attributes: t,
                persons: n
            }), wp.element.createElement("div", {
                className: "preview-footer"
            }, wp.element.createElement("div", null, (0, e.__)("Selected Persons:", "rrze-faudir") + " " + t.identifier.length), wp.element.createElement("div", null, (0, e.__)("Showing:", "rrze-faudir") + " " + (t.show || (0, e.__)("Default fields", "rrze-faudir")))))), {
                attributes: t.attributes,
                onClick: () => g(!0),
                persons: r
            }))
        },
        save: function() {
            return null
        }
    })
})();
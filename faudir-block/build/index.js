/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/edit.js":
/*!*********************!*\
  !*** ./src/edit.js ***!
  \*********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_server_side_render__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/server-side-render */ "@wordpress/server-side-render");
/* harmony import */ var _wordpress_server_side_render__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_server_side_render__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./editor.scss */ "./src/editor.scss");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__);








function Edit({
  attributes,
  setAttributes
}) {
  const [categories, setCategories] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)([]);
  const [posts, setPosts] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)([]);
  const [isLoadingCategories, setIsLoadingCategories] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(true);
  const [isLoadingPosts, setIsLoadingPosts] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(false);
  const [defaultButtonText, setDefaultButtonText] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)('');
  const {
    selectedCategory = '',
    selectedPosts = [],
    showCategory = '',
    showPosts = '',
    selectedPersonIds = '',
    selectedFormat = 'kompakt',
    selectedFields = [],
    groupId = '',
    functionField = '',
    organizationNr = '',
    url = '',
    buttonText = '',
    hideFields = []
  } = attributes;
  const availableFields = {
    displayName: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Display Name', 'rrze-faudir'),
    personalTitle: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Academic Title', 'rrze-faudir'),
    givenName: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('First Name', 'rrze-faudir'),
    familyName: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Last Name', 'rrze-faudir'),
    personalTitleSuffix: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Academic Suffix', 'rrze-faudir'),
    titleOfNobility: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Title of Nobility', 'rrze-faudir'),
    email: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Email', 'rrze-faudir'),
    phone: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Phone', 'rrze-faudir'),
    organization: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Organization', 'rrze-faudir'),
    function: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Function', 'rrze-faudir'),
    url: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Url', 'rrze-faudir'),
    kompaktButton: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Kompakt Button', 'rrze-faudir'),
    content: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Content', 'rrze-faudir'),
    teasertext: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Teasertext', 'rrze-faudir'),
    socialmedia: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Social Media', 'rrze-faudir'),
    workplaces: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Workplaces', 'rrze-faudir'),
    room: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Room', 'rrze-faudir'),
    floor: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Floor', 'rrze-faudir'),
    street: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Street', 'rrze-faudir'),
    zip: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Zip', 'rrze-faudir'),
    city: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('City', 'rrze-faudir'),
    faumap: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Fau Map', 'rrze-faudir'),
    officehours: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Office Hours', 'rrze-faudir'),
    consultationhours: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Consultation Hours', 'rrze-faudir')
  };
  const formatFields = {
    card: ['displayName', 'personalTitle', 'givenName', 'familyName', 'personalTitleSuffix', 'email', 'phone', 'function', 'socialmedia', 'titleOfNobility'],
    table: ['displayName', 'personalTitle', 'givenName', 'familyName', 'personalTitleSuffix', 'email', 'phone', 'url', 'socialmedia', 'titleOfNobility'],
    list: ['displayName', 'personalTitle', 'givenName', 'familyName', 'personalTitleSuffix', 'email', 'phone', 'url', 'teasertext', 'titleOfNobility'],
    kompakt: Object.keys(availableFields),
    page: Object.keys(availableFields)
  };

  // Define required fields for each format
  const requiredFields = {
    card: ['display_name', 'academic_title', 'first_name', 'last_name'],
    table: ['display_name', 'academic_title', 'first_name', 'last_name'],
    list: ['display_name', 'academic_title', 'first_name', 'last_name'],
    kompakt: ['display_name', 'academic_title', 'first_name', 'last_name'],
    page: ['display_name', 'academic_title', 'first_name', 'last_name']
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    // Only fetch and set default fields if this is a new block (no selectedFields set)
    if (!attributes.selectedFields || attributes.selectedFields.length === 0) {
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5___default()({
        path: '/wp/v2/settings/rrze_faudir_options'
      }).then(settings => {
        if (settings?.default_output_fields) {
          setAttributes({
            selectedFields: settings.default_output_fields
          });
        }
      }).catch(error => {
        console.error('Error fetching default fields:', error);
      });
    }
  }, []); // Empty dependency array means this only runs once when component mounts

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    // Fetch categories from the REST API
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5___default()({
      path: '/wp/v2/custom_taxonomy?per_page=100'
    }).then(data => {
      setCategories(data);
      setIsLoadingCategories(false);
    }).catch(error => {
      console.error('Error fetching categories:', error);
      setIsLoadingCategories(false);
    });
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    // Fetch all posts from the custom post type
    setIsLoadingPosts(true);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5___default()({
      path: '/wp/v2/custom_person?per_page=100&_fields=id,title,meta'
    }).then(data => {
      setPosts(data);
      setIsLoadingPosts(false);
    }).catch(error => {
      console.error('Error fetching posts:', error);
      setIsLoadingPosts(false);
    });
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    if (!buttonText) {
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5___default()({
        path: '/wp/v2/settings/rrze_faudir_options'
      }).then(settings => {
        if (settings?.business_card_title) {
          setDefaultButtonText(settings.business_card_title);
          setAttributes({
            buttonText: settings.business_card_title
          });
        }
      }).catch(error => {
        console.error('Error fetching button text:', error);
      });
    }
  }, []); // Empty dependency array means this runs once on mount

  const togglePostSelection = (postId, personId) => {
    const updatedSelectedPosts = selectedPosts.includes(postId) ? selectedPosts.filter(id => id !== postId) // Deselect post
    : [...selectedPosts, postId]; // Select post
    const updatedPersonIds = updatedSelectedPosts.map(id => {
      const post = posts.find(p => p.id === id);
      // Ensure the person_id is extracted and filtered properly
      return post?.meta?.person_id || null;
    }).filter(Boolean);

    // Store both post ID and person ID
    setAttributes({
      selectedPosts: updatedSelectedPosts,
      selectedPersonIds: updatedPersonIds // Remove any null values from the person IDs array
    });
  };
  const toggleFieldSelection = field => {
    console.log('Toggling field:', field); // Debug log
    console.log('Current selectedFields:', selectedFields); // Debug log
    console.log('Current hideFields:', attributes.hideFields); // Debug log

    const isFieldSelected = selectedFields.includes(field);
    let updatedSelectedFields;
    let updatedHideFields = attributes.hideFields || [];
    if (isFieldSelected) {
      // Remove from selected fields and add to hide fields
      updatedSelectedFields = selectedFields.filter(f => f !== field);
      updatedHideFields = [...updatedHideFields, field];
    } else {
      // Add to selected fields and remove from hide fields
      updatedSelectedFields = [...selectedFields, field];
      updatedHideFields = updatedHideFields.filter(f => f !== field);
    }
    console.log('Updated selectedFields:', updatedSelectedFields); // Debug log
    console.log('Updated hideFields:', updatedHideFields); // Debug log

    setAttributes({
      selectedFields: updatedSelectedFields,
      hideFields: updatedHideFields
    });
  };

  // Modify the format change handler
  const handleFormatChange = value => {
    setAttributes({
      selectedFormat: value
    });

    // Only reset fields if explicitly changing format and no fields are selected
    if (!attributes.selectedFields || attributes.selectedFields.length === 0) {
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5___default()({
        path: '/wp/v2/settings/rrze_faudir_options'
      }).then(settings => {
        if (settings?.default_output_fields) {
          // Filter default fields based on the selected format
          const formatSpecificFields = formatFields[value] || [];
          const filteredDefaultFields = settings.default_output_fields.filter(field => formatSpecificFields.includes(field));
          setAttributes({
            selectedFields: filteredDefaultFields
          });
        }
      }).catch(error => {
        console.error('Error fetching default fields:', error);
      });
    }
  };

  // Add debug logging
  console.log('Edit component rendering with attributes:', attributes);

  // Transform attributes to match the shortcode format
  const blockAttributes = {
    selectedPersonIds: attributes.selectedPersonIds,
    selectedFields: attributes.selectedFields,
    selectedFormat: attributes.selectedFormat,
    selectedCategory: attributes.selectedCategory,
    groupId: attributes.groupId,
    functionField: attributes.functionField,
    organizationNr: attributes.organizationNr,
    url: attributes.url
  };
  console.log('Block attributes:', blockAttributes);

  // Add debug output to the rendered component
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.InspectorControls, {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Settings', 'rrze-faudir'),
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Show Category', 'rrze-faudir'),
          checked: showCategory,
          onChange: () => setAttributes({
            showCategory: !showCategory
          })
        }), showCategory && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("h4", {
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select Category', 'rrze-faudir')
          }), categories.map(category => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.CheckboxControl, {
            label: category.name,
            checked: selectedCategory === category.name,
            onChange: () => setAttributes({
              selectedCategory: category.name
            })
          }, category.id))]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Show Persons', 'rrze-faudir'),
          checked: showPosts,
          onChange: () => setAttributes({
            showPosts: !showPosts
          })
        }), showPosts && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("h4", {
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select Persons', 'rrze-faudir')
          }), isLoadingPosts ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Loading persons...', 'rrze-faudir')
          }) : posts.length > 0 ? posts.map(post => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.CheckboxControl, {
            label: post.title.rendered,
            checked: selectedPosts.includes(post.id),
            onChange: () => togglePostSelection(post.id, post.meta?.person_id)
          }, post.id)) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No posts available.', 'rrze-faudir')
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select Format', 'rrze-faudir'),
          value: selectedFormat || 'list',
          options: [{
            value: 'list',
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('List', 'rrze-faudir')
          }, {
            value: 'table',
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Table', 'rrze-faudir')
          }, {
            value: 'card',
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Card', 'rrze-faudir')
          }, {
            value: 'kompakt',
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Kompakt', 'rrze-faudir')
          }, {
            value: 'page',
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Page', 'rrze-faudir')
          }],
          onChange: handleFormatChange
        }), Object.keys(formatFields).map(format => {
          if (selectedFormat === format) {
            return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("h4", {
                children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select Fields', 'rrze-faudir')
              }), formatFields[format].map(field => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
                style: {
                  marginBottom: '8px'
                },
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.CheckboxControl, {
                  label: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
                    children: [availableFields[field], /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("span", {
                      style: {
                        marginLeft: '8px',
                        color: selectedFields.includes(field) ? '#4CAF50' : '#f44336',
                        fontSize: '12px'
                      },
                      children: ["(", selectedFields.includes(field) ? 'show' : 'hide', ")"]
                    })]
                  }),
                  checked: selectedFields.includes(field),
                  onChange: () => toggleFieldSelection(field)
                })
              }, field))]
            }, format);
          }
          return null;
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Group Id', 'rrze-faudir'),
          value: groupId,
          onChange: value => setAttributes({
            groupId: value
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Function', 'rrze-faudir'),
          value: functionField,
          onChange: value => setAttributes({
            functionField: value
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Organization Nr', 'rrze-faudir'),
          value: organizationNr,
          onChange: value => setAttributes({
            organizationNr: value
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Custom url', 'rrze-faudir'),
          value: url,
          onChange: value => setAttributes({
            url: value
          })
        }), selectedFormat === 'kompakt' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Button Text', 'rrze-faudir'),
          help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Default: ', 'rrze-faudir') + defaultButtonText,
          value: buttonText,
          onChange: value => setAttributes({
            buttonText: value
          }),
          placeholder: defaultButtonText
        })]
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
      ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockProps)(),
      children: attributes.selectedPersonIds && attributes.selectedPersonIds.length > 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)((_wordpress_server_side_render__WEBPACK_IMPORTED_MODULE_4___default()), {
          block: "rrze-faudir/block",
          attributes: attributes,
          EmptyResponsePlaceholder: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
            style: {
              padding: '20px',
              backgroundColor: '#fff3cd',
              color: '#856404'
            },
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
              children: "No content returned from server."
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("details", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("summary", {
                children: "Debug Information"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("pre", {
                children: JSON.stringify(attributes, null, 2)
              })]
            })]
          }),
          ErrorResponsePlaceholder: ({
            response
          }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
            style: {
              padding: '20px',
              backgroundColor: '#f8d7da',
              color: '#721c24'
            },
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("strong", {
                children: "Error loading content:"
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
              children: response?.errorMsg || 'Unknown error occurred'
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("details", {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("summary", {
                children: "Debug Information"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("pre", {
                children: "Block: rrze-faudir/block"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("pre", {
                children: ["Response: ", JSON.stringify(response, null, 2)]
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("pre", {
                children: ["Attributes: ", JSON.stringify(attributes, null, 2)]
              })]
            })]
          })
        })
      }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
        style: {
          padding: '20px',
          backgroundColor: '#f8f9fa',
          textAlign: 'center'
        },
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
          children: "__('Please select persons to display using the sidebar controls.', 'rrze-faudir')"
        })
      })
    })]
  });
}

/***/ }),

/***/ "./src/index.js":
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./style.scss */ "./src/style.scss");
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./edit */ "./src/edit.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./block.json */ "./src/block.json");
/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */


/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */


/**
 * Internal dependencies
 */



/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_3__.name, {
  /**
   * @see ./edit.js
   */
  edit: _edit__WEBPACK_IMPORTED_MODULE_2__["default"],
  save: () => null
});

/***/ }),

/***/ "./src/editor.scss":
/*!*************************!*\
  !*** ./src/editor.scss ***!
  \*************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/style.scss":
/*!************************!*\
  !*** ./src/style.scss ***!
  \************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["ReactJSXRuntime"];

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "@wordpress/server-side-render":
/*!******************************************!*\
  !*** external ["wp","serverSideRender"] ***!
  \******************************************/
/***/ ((module) => {

module.exports = window["wp"]["serverSideRender"];

/***/ }),

/***/ "./src/block.json":
/*!************************!*\
  !*** ./src/block.json ***!
  \************************/
/***/ ((module) => {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":2,"name":"rrze-faudir/block","title":"FAUDIR Block","category":"widgets","icon":"businessperson","description":"Display Faudir information","supports":{"html":false,"anchor":true},"attributes":{"selectedPersonIds":{"type":"array","default":[]},"selectedFields":{"type":"array","default":[]},"selectedFormat":{"type":"string","default":"kompakt"},"selectedCategory":{"type":"string","default":""},"groupId":{"type":"string","default":""},"functionField":{"type":"string","default":""},"organizationNr":{"type":"string","default":""},"url":{"type":"string","default":""},"buttonText":{"type":"string","default":""}},"textdomain":"block-faudir","editorScript":"file:./index.js","editorStyle":"file:./index.css","style":"file:./style-index.css"}');

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"index": 0,
/******/ 			"./style-index": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = globalThis["webpackChunkblock_faudir"] = globalThis["webpackChunkblock_faudir"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["./style-index"], () => (__webpack_require__("./src/index.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=index.js.map
wp.blocks.registerBlockType('rrze/faudir-block', {
    title: 'FAUDIR Block',
    icon: 'admin-users',
    category: 'common',
    attributes: {
        category: { type: 'string', default: '' },
        identifier: { type: 'string', default: '' },
        format: { type: 'string', default: 'list' },
        url: { type: 'string', default: '' },
        show: { type: 'string', default: 'personalTitle, firstName, familyName, name, email, phone, organization, function' },
        hide: { type: 'string', default: '' },
        image: { type: 'number', default: null }, // Image ID attribute
    },
    edit: function (props) {
        const {
            attributes: { category, identifier, format, url, show, hide, image },
            setAttributes
        } = props;

        const onSelectImage = (newImage) => {
            setAttributes({ image: newImage.id });
        };

        // Array of available options with actual values and display texts
        const options = [
            { value: 'personalTitle', label: 'Personal Title' },
            { value: 'firstName', label: 'First Name' },
            { value: 'familyName', label: 'Family Name' },
            { value: 'name', label: 'Name' },
            { value: 'email', label: 'Email' },
            { value: 'phone', label: 'Phone' },
            { value: 'organization', label: 'Organization' },
            { value: 'function', label: 'Function' },
        ];

        // Convert the comma-separated string into an array of selected values
        const selectedValues = show.split(', ').filter(Boolean);

        // Handle checkbox change
        const handleCheckboxChange = (optionValue) => {
            let updatedValues = [...selectedValues];
            if (updatedValues.includes(optionValue)) {
                // Remove the option if it's already selected
                updatedValues = updatedValues.filter((item) => item !== optionValue);
            } else {
                // Add the option if it's not selected
                updatedValues.push(optionValue);
            }
            // Update the show attribute with the new values as a string
            setAttributes({ show: updatedValues.join(', ') });
        };
        return wp.element.createElement(
            'div',
            null,
            // Category input field
            wp.element.createElement(
                'label',
                { className: 'block-label' },
                null,
                'Category',
                wp.element.createElement('label', {
                    className: 'block-label',
                    type: 'text',
                    value: 'Category',
                }),
                wp.element.createElement('input', {
                    className: 'block-label',
                    type: 'text',
                    value: category,
                    onChange: function(event) {
                        setAttributes({ category: event.target.value });
                    }
                })
            ),
            // Identifier input field
            wp.element.createElement(
                'label',
                { className: 'block-label' },
                null,
                'Identifier',
                wp.element.createElement('label', {
                    className: 'block-label',
                    type: 'text',
                    value: 'Identifier',
                }),
                wp.element.createElement('input', {
                    className: 'block-label',
                    type: 'text',
                    value: identifier,
                    onChange: function(event) {
                        setAttributes({ identifier: event.target.value });
                    }
                })
            ),
            // Format selection dropdown
            wp.element.createElement(
                'label',
                { className: 'block-label' },
                null,
                'Format',
                wp.element.createElement('label', {
                    className: 'block-label',
                    type: 'text',
                    value: 'Format',
                }),
                wp.element.createElement('select', {
                    className: 'block-label',
                    value: format,
                    onChange: function(event) {
                        setAttributes({ format: event.target.value });
                    }
                },
                wp.element.createElement('option', { value: 'list' }, 'List'),
                wp.element.createElement('option', { value: 'table' }, 'Table'),
                wp.element.createElement('option', { value: 'card' }, 'Card'),
                wp.element.createElement('option', { value: 'page' }, 'Page')
                )
            ),
             // Url field input
             wp.element.createElement(
                'label',
                { className: 'block-label' },
                null,
                'Url',
                wp.element.createElement('label', {
                    className: 'block-label',
                    type: 'text',
                    value: 'Url',
                }),
                wp.element.createElement('input', {
                    className: 'block-label',
                    type: 'text',
                    value: url,
                    onChange: function(event) {
                        setAttributes({ url: event.target.value });
                    }
                })
            ),
            // Show fields input
            wp.element.createElement(
                'div',
                { className: 'block-container' },
                wp.element.createElement('label', { className: 'block-label' }, 'Show Fields'),
                options.map((option) =>
                    wp.element.createElement(
                        'div',
                        { key: option.value, className: 'checkbox-container block-label' },
                        wp.element.createElement('input', {
                            type: 'checkbox',
                            className: 'checkbox-input',
                            checked: selectedValues.includes(option.value),
                            onChange: () => handleCheckboxChange(option.value),
                        }),
                        wp.element.createElement('span', null, option.label) // Display the label text
                    )
                )
            ),
            // Hide fields input
            wp.element.createElement(
                'label',
                { className: 'block-label' },
                null,
                'Hide Fields',
                wp.element.createElement('label', {
                    className: 'block-label',
                    type: 'text',
                    value: 'Hide Fields',
                }), 
                wp.element.createElement('input', {
                    className: 'block-label',
                    type: 'text',
                    value: hide,
                    onChange: function(event) {
                        setAttributes({ hide: event.target.value });
                    }
                })
            ),
            // MediaUpload for selecting an image
            wp.element.createElement(
                wp.blockEditor.MediaUpload,
                {
                    onSelect: onSelectImage,
                    allowedTypes: ['image'],
                    value: image,
                    render: function(obj) {
                        return wp.element.createElement(
                            'div',
                            null,
                            wp.element.createElement(
                                'button',
                                {
                                    onClick: obj.open,
                                    className: 'button button-secondary block-label',
                                },
                                image ? 'Change Image' : 'Select Image'
                            ),
                            image && wp.element.createElement('img', {
                                src: wp.media.attachment(image).attributes.url,
                                alt: 'Selected Image',
                                style: { maxWidth: '100%', height: 'auto' }
                            })
                        );
                    }
                }
            )
        );
    },
    save: function() {
        // This block will be rendered dynamically on the server-side, so no need to save anything here
        return null;
    },
});


jQuery(document).ready(function ($) {
    console.log('RRZE FAUDIR JS from src directory');
    $('#person_id').on('change', function() {
        var personId = $(this).val();

        if (personId) {
            $.ajax({
                url: customPerson.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_person_attributes',
                    person_id: personId,
                    nonce: customPerson.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        $('#person_name').val(data.person_name);
                        $('#person_email').val(data.person_email);
                        $('#person_given_name').val(data.person_given_name);
                        $('#person_family_name').val(data.person_family_name);
                        $('#person_title').val(data.person_title);
                        $('#person_organization').val(data.person_organization);
                        $('#person_function').val(data.person_function);
                        // Update other fields as needed
                    } else {
                        alert(response.data);
                    }
                }
            });
        }
    });
});

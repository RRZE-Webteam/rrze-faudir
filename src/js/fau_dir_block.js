wp.blocks.registerBlockType('rrze/faudir-block', {
    title: 'FAUDIR Block',
    icon: 'admin-users',
    category: 'common',
    attributes: {
        category: { type: 'string', default: '' },
        identifier: { type: 'string', default: '' },
        format: { type: 'string', default: 'list' },
        show: { type: 'string', default: 'name, email, phone, organization, function' },
        hide: { type: 'string', default: '' },
        image: { type: 'number', default: 0 }, // Image ID attribute
    },
    edit: function(props) {
        const {
            attributes: { category, identifier, format, show, hide, image },
            setAttributes
        } = props;

        const onSelectImage = (newImage) => {
            setAttributes({ image: newImage.id });
        };

        return wp.element.createElement(
            'div',
            null,
            // Category input field
            wp.element.createElement(
                'label',
                null,
                'Category',
                wp.element.createElement('input', {
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
                null,
                'Identifier',
                wp.element.createElement('input', {
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
                null,
                'Format',
                wp.element.createElement('select', {
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
            // Show fields input
            wp.element.createElement(
                'label',
                null,
                'Show Fields',
                wp.element.createElement('input', {
                    type: 'text',
                    value: show,
                    onChange: function(event) {
                        setAttributes({ show: event.target.value });
                    }
                })
            ),
            // Hide fields input
            wp.element.createElement(
                'label',
                null,
                'Hide Fields',
                wp.element.createElement('input', {
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
                                    className: 'button button-secondary',
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

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
    },
    edit: function(props) {
        return wp.element.createElement(
            'div',
            null,
            wp.element.createElement(
                'label',
                null,
                'Category',
                wp.element.createElement('input', {
                    type: 'text',
                    value: props.attributes.category,
                    onChange: function(event) {
                        props.setAttributes({ category: event.target.value });
                    }
                })
            ),
            wp.element.createElement(
                'label',
                null,
                'Identifier',
                wp.element.createElement('input', {
                    type: 'text',
                    value: props.attributes.identifier,
                    onChange: function(event) {
                        props.setAttributes({ identifier: event.target.value });
                    }
                })
            ),
            wp.element.createElement(
                'label',
                null,
                'Format',
                wp.element.createElement('select', {
                    value: props.attributes.format,
                    onChange: function(event) {
                        props.setAttributes({ format: event.target.value });
                    }
                },
                wp.element.createElement('option', { value: 'list' }, 'List'),
                wp.element.createElement('option', { value: 'table' }, 'Table'),
                wp.element.createElement('option', { value: 'card' }, 'Card'),
                wp.element.createElement('option', { value: 'page' }, 'Page')
                )
            ),
            wp.element.createElement(
                'label',
                null,
                'Show Fields',
                wp.element.createElement('input', {
                    type: 'text',
                    value: props.attributes.show,
                    onChange: function(event) {
                        props.setAttributes({ show: event.target.value });
                    }
                })
            ),
            wp.element.createElement(
                'label',
                null,
                'Hide Fields',
                wp.element.createElement('input', {
                    type: 'text',
                    value: props.attributes.hide,
                    onChange: function(event) {
                        props.setAttributes({ hide: event.target.value });
                    }
                })
            )
        );
    },
    save: function() {
        // This block will be rendered dynamically on the server-side, so no need to save anything here
        return null;
    },
});

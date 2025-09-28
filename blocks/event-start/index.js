/**
 * Event Start Date/Time Block
 */
( function( blocks, element, serverSideRender, i18n, blockEditor, components ) {
    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;
    var TextControl = components.TextControl;
    var TextControl = components.TextControl;
    
    blocks.registerBlockType( 'wp-events/event-start', {
        title: __( 'Event Start Date/Time', 'wp-events' ),
        icon: 'clock',
        category: 'wp-events',
        description: __( 'Display event start date and time', 'wp-events' ),
        attributes: {
            dateFormat: {
                type: 'string',
                default: 'full' // full, date-only, time-only, custom
            },
            customFormat: {
                type: 'string',
                default: 'j. F Y \\k\\l. H:i'
            },
            showLabel: {
                type: 'boolean',
                default: true
            },
            label: {
                type: 'string',
                default: __( 'Starts:', 'wp-events' )
            }
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            return [
                el( InspectorControls, null,
                    el( PanelBody, { title: __( 'Date/Time Settings', 'wp-events' ) },
                        el( ToggleControl, {
                            label: __( 'Show Label', 'wp-events' ),
                            checked: attributes.showLabel,
                            onChange: function( value ) {
                                setAttributes({ showLabel: value });
                            }
                        }),
                        attributes.showLabel && el( TextControl, {
                            label: __( 'Label Text', 'wp-events' ),
                            label: __( 'Label Text', 'wp-events' ),
                            value: attributes.label,
                            onChange: function( value ) {
                                setAttributes({ label: value });
                            }
                        }),
                        el( SelectControl, {
                            label: __( 'Date Format', 'wp-events' ),
                            value: attributes.dateFormat,
                            options: [
                                { label: __( 'Full Date & Time', 'wp-events' ), value: 'full' },
                                { label: __( 'Date Only', 'wp-events' ), value: 'date-only' },
                                { label: __( 'Time Only', 'wp-events' ), value: 'time-only' },
                                { label: __( 'Custom Format', 'wp-events' ), value: 'custom' }
                            ],
                            onChange: function( value ) {
                                setAttributes({ dateFormat: value });
                            }
                        }),
                        attributes.dateFormat === 'custom' && el( TextControl, {
                            label: __( 'Custom PHP Date Format', 'wp-events' ),
                            value: attributes.customFormat,
                            onChange: function( value ) {
                                setAttributes({ customFormat: value });
                            },
                            help: __( 'Use PHP date format (e.g., "j. F Y \\k\\l. H:i")', 'wp-events' )
                        })
                    )
                ),
                el( serverSideRender, {
                    block: 'wp-events/event-start',
                    attributes: attributes
                })
            ];
        },
        save: function() {
            return null; // Server-side rendered
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.serverSideRender,
    window.wp.i18n,
    window.wp.blockEditor,
    window.wp.components
);
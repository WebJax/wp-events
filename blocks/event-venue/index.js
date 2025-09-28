/**
 * Event Venue Block
 */
( function( blocks, element, serverSideRender, i18n, blockEditor, components ) {
    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;
    
    blocks.registerBlockType( 'wp-events/event-venue', {
        title: __( 'Event Venue', 'wp-events' ),
        icon: 'location',
        category: 'wp-events',
        description: __( 'Display event venue information', 'wp-events' ),
        attributes: {
            showAddress: {
                type: 'boolean',
                default: true
            },
            showContact: {
                type: 'boolean',
                default: false
            },
            linkToVenue: {
                type: 'boolean',
                default: true
            }
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            return [
                el( InspectorControls, null,
                    el( PanelBody, { title: __( 'Venue Settings', 'wp-events' ) },
                        el( ToggleControl, {
                            label: __( 'Show Address', 'wp-events' ),
                            checked: attributes.showAddress,
                            onChange: function( value ) {
                                setAttributes({ showAddress: value });
                            }
                        }),
                        el( ToggleControl, {
                            label: __( 'Show Contact Info', 'wp-events' ),
                            checked: attributes.showContact,
                            onChange: function( value ) {
                                setAttributes({ showContact: value });
                            }
                        }),
                        el( ToggleControl, {
                            label: __( 'Link to Venue Page', 'wp-events' ),
                            checked: attributes.linkToVenue,
                            onChange: function( value ) {
                                setAttributes({ linkToVenue: value });
                            }
                        })
                    )
                ),
                el( serverSideRender, {
                    block: 'wp-events/event-venue',
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
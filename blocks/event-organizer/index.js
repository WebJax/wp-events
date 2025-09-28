/**
 * Event Organizer Block
 */
( function( blocks, element, serverSideRender, i18n, blockEditor, components ) {
    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var ToggleControl = components.ToggleControl;
    
    blocks.registerBlockType( 'wp-events/event-organizer', {
        title: __( 'Event Organizer', 'wp-events' ),
        icon: 'admin-users',
        category: 'wp-events',
        description: __( 'Display event organizer information', 'wp-events' ),
        attributes: {
            showContact: {
                type: 'boolean',
                default: false
            },
            linkToOrganizer: {
                type: 'boolean',
                default: true
            }
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            return [
                el( InspectorControls, null,
                    el( PanelBody, { title: __( 'Organizer Settings', 'wp-events' ) },
                        el( ToggleControl, {
                            label: __( 'Show Contact Info', 'wp-events' ),
                            checked: attributes.showContact,
                            onChange: function( value ) {
                                setAttributes({ showContact: value });
                            }
                        }),
                        el( ToggleControl, {
                            label: __( 'Link to Organizer Page', 'wp-events' ),
                            checked: attributes.linkToOrganizer,
                            onChange: function( value ) {
                                setAttributes({ linkToOrganizer: value });
                            }
                        })
                    )
                ),
                el( serverSideRender, {
                    block: 'wp-events/event-organizer',
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
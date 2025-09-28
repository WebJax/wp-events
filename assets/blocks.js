/**
 * WP Events Blocks - Modern WordPress Standard Implementation
 */
(function(blocks, element, serverSideRender, blockEditor, components, i18n) {
    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;
    
    // Event Venue Block
    blocks.registerBlockType('wp-events/venue', {
        title: __('Event Venue', 'wp-events'),
        icon: 'location',
        category: 'wp-events',
        description: __('Display event venue information', 'wp-events'),
        
        supports: {
            align: ['left', 'center', 'right'],
            anchor: true,
            className: true,
            color: {
                gradients: true,
                link: true,
                __experimentalDefaultControls: {
                    background: true,
                    text: true
                }
            },
            spacing: {
                margin: true,
                padding: true,
                __experimentalDefaultControls: {
                    margin: false,
                    padding: false
                }
            },
            typography: {
                fontSize: true,
                fontFamily: true,
                fontStyle: true,
                fontWeight: true,
                letterSpacing: true,
                lineHeight: true,
                textDecoration: true,
                textTransform: true,
                __experimentalDefaultControls: {
                    fontSize: true
                }
            }
        },
        
        attributes: {
            showAddress: {
                type: 'boolean',
                default: true
            },
            showContact: {
                type: 'boolean',
                default: false
            },
            showDirections: {
                type: 'boolean',
                default: true
            },
            linkToVenue: {
                type: 'boolean',
                default: true
            }
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            return [
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Venue Settings', 'wp-events') },
                        el(ToggleControl, {
                            label: __('Show Address', 'wp-events'),
                            checked: attributes.showAddress,
                            onChange: function(value) {
                                setAttributes({ showAddress: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Contact Info', 'wp-events'),
                            checked: attributes.showContact,
                            onChange: function(value) {
                                setAttributes({ showContact: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Directions Link', 'wp-events'),
                            checked: attributes.showDirections,
                            onChange: function(value) {
                                setAttributes({ showDirections: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Link to Venue Page', 'wp-events'),
                            checked: attributes.linkToVenue,
                            onChange: function(value) {
                                setAttributes({ linkToVenue: value });
                            }
                        })
                    )
                ),
                el(serverSideRender, {
                    block: 'wp-events/venue',
                    attributes: attributes
                })
            ];
        },
        
        save: function() {
            return null; // Server-side rendered
        }
    });

    // Event Organizer Block
    blocks.registerBlockType('wp-events/organizer', {
        title: __('Event Organizer', 'wp-events'),
        icon: 'admin-users',
        category: 'wp-events',
        description: __('Display event organizer name', 'wp-events'),
        
        supports: {
            anchor: true,
            className: true,
            color: {
                gradients: true,
                link: true,
                __experimentalDefaultControls: {
                    background: true,
                    text: true
                }
            },
            spacing: {
                margin: true,
                padding: true,
                __experimentalDefaultControls: {
                    margin: false,
                    padding: false
                }
            },
            typography: {
                fontSize: true,
                fontFamily: true,
                fontStyle: true,
                fontWeight: true,
                letterSpacing: true,
                lineHeight: true,
                textDecoration: true,
                textTransform: true,
                __experimentalDefaultControls: {
                    fontSize: true
                }
            }
        },
        
        attributes: {
            textAlign: {
                type: 'string'
            }
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var BlockControls = blockEditor.BlockControls;
            var AlignmentToolbar = blockEditor.AlignmentToolbar;
            
            return [
                el(BlockControls, null,
                    el(AlignmentToolbar, {
                        value: attributes.textAlign,
                        onChange: function(alignment) {
                            setAttributes({ textAlign: alignment });
                        }
                    })
                ),
                el('div', { 
                    className: 'wp-block-wp-events-organizer-preview',
                    style: { textAlign: attributes.textAlign }
                },
                    el(serverSideRender, {
                        block: 'wp-events/organizer',
                        attributes: attributes
                    })
                )
            ];
        },
        
        save: function() {
            return null; // Server-side rendered
        }
    });

    // Event Start Time Block
    blocks.registerBlockType('wp-events/event-start', {
        title: __('Event Start Time', 'wp-events'),
        icon: 'clock',
        category: 'wp-events',
        description: __('Display event start date and time', 'wp-events'),
        
        supports: {
            align: ['left', 'center', 'right'],
            anchor: true,
            className: true,
            color: {
                gradients: true,
                link: true,
                __experimentalDefaultControls: {
                    background: true,
                    text: true
                }
            },
            spacing: {
                margin: true,
                padding: true,
                __experimentalDefaultControls: {
                    margin: false,
                    padding: false
                }
            },
            typography: {
                fontSize: true,
                fontFamily: true,
                fontStyle: true,
                fontWeight: true,
                letterSpacing: true,
                lineHeight: true,
                textDecoration: true,
                textTransform: true,
                __experimentalDefaultControls: {
                    fontSize: true
                }
            }
        },
        
        attributes: {
            showLabel: {
                type: 'boolean',
                default: true
            },
            customLabel: {
                type: 'string',
                default: 'Start:'
            },
            labelBold: {
                type: 'boolean',
                default: false
            },
            labelItalic: {
                type: 'boolean',
                default: false
            }
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            return [
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Start Time Settings', 'wp-events') },
                        el(ToggleControl, {
                            label: __('Show Label', 'wp-events'),
                            checked: attributes.showLabel,
                            onChange: function(value) {
                                setAttributes({ showLabel: value });
                            }
                        }),
                        attributes.showLabel && el(TextControl, {
                            label: __('Custom Label', 'wp-events'),
                            value: attributes.customLabel,
                            onChange: function(value) {
                                setAttributes({ customLabel: value });
                            },
                            help: __('Enter custom text for the label', 'wp-events')
                        }),
                        attributes.showLabel && el(ToggleControl, {
                            label: __('Bold Label', 'wp-events'),
                            checked: attributes.labelBold,
                            onChange: function(value) {
                                setAttributes({ labelBold: value });
                            }
                        }),
                        attributes.showLabel && el(ToggleControl, {
                            label: __('Italic Label', 'wp-events'),
                            checked: attributes.labelItalic,
                            onChange: function(value) {
                                setAttributes({ labelItalic: value });
                            }
                        })
                    )
                ),
                el(serverSideRender, {
                    block: 'wp-events/event-start',
                    attributes: attributes
                })
            ];
        },
        
        save: function() {
            return null; // Server-side rendered
        }
    });

    // Event End Time Block
    blocks.registerBlockType('wp-events/event-end', {
        title: __('Event End Time', 'wp-events'),
        icon: 'clock',
        category: 'wp-events',
        description: __('Display event end date and time', 'wp-events'),
        
        supports: {
            align: ['left', 'center', 'right'],
            anchor: true,
            className: true,
            color: {
                gradients: true,
                link: true,
                __experimentalDefaultControls: {
                    background: true,
                    text: true
                }
            },
            spacing: {
                margin: true,
                padding: true,
                __experimentalDefaultControls: {
                    margin: false,
                    padding: false
                }
            },
            typography: {
                fontSize: true,
                fontFamily: true,
                fontStyle: true,
                fontWeight: true,
                letterSpacing: true,
                lineHeight: true,
                textDecoration: true,
                textTransform: true,
                __experimentalDefaultControls: {
                    fontSize: true
                }
            }
        },
        
        attributes: {
            showLabel: {
                type: 'boolean',
                default: true
            },
            customLabel: {
                type: 'string',
                default: 'Slut:'
            },
            labelBold: {
                type: 'boolean',
                default: false
            },
            labelItalic: {
                type: 'boolean',
                default: false
            }
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            return [
                el(InspectorControls, null,
                    el(PanelBody, { title: __('End Time Settings', 'wp-events') },
                        el(ToggleControl, {
                            label: __('Show Label', 'wp-events'),
                            checked: attributes.showLabel,
                            onChange: function(value) {
                                setAttributes({ showLabel: value });
                            }
                        }),
                        attributes.showLabel && el(TextControl, {
                            label: __('Custom Label', 'wp-events'),
                            value: attributes.customLabel,
                            onChange: function(value) {
                                setAttributes({ customLabel: value });
                            },
                            help: __('Enter custom text for the label', 'wp-events')
                        }),
                        attributes.showLabel && el(ToggleControl, {
                            label: __('Bold Label', 'wp-events'),
                            checked: attributes.labelBold,
                            onChange: function(value) {
                                setAttributes({ labelBold: value });
                            }
                        }),
                        attributes.showLabel && el(ToggleControl, {
                            label: __('Italic Label', 'wp-events'),
                            checked: attributes.labelItalic,
                            onChange: function(value) {
                                setAttributes({ labelItalic: value });
                            }
                        })
                    )
                ),
                el(serverSideRender, {
                    block: 'wp-events/event-end',
                    attributes: attributes
                })
            ];
        },
        
        save: function() {
            return null; // Server-side rendered
        }
    });

    // Events List Block
    blocks.registerBlockType('wp-events/events-list', {
        title: __('Events List', 'wp-events'),
        icon: 'calendar',
        category: 'wp-events',
        description: __('Display a list of upcoming events', 'wp-events'),
        
        supports: {
            align: ['left', 'center', 'right'],
            anchor: true,
            className: true,
            color: {
                gradients: true,
                link: true,
                __experimentalDefaultControls: {
                    background: true,
                    text: true
                }
            },
            spacing: {
                margin: true,
                padding: true,
                __experimentalDefaultControls: {
                    margin: false,
                    padding: false
                }
            },
            typography: {
                fontSize: true,
                fontFamily: true,
                fontStyle: true,
                fontWeight: true,
                letterSpacing: true,
                lineHeight: true,
                textDecoration: true,
                textTransform: true,
                __experimentalDefaultControls: {
                    fontSize: true
                }
            }
        },
        
        attributes: {
            limit: {
                type: 'number',
                default: 5
            }
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            return [
                el(InspectorControls, null,
                    el(PanelBody, { title: __('List Settings', 'wp-events') },
                        el(components.RangeControl, {
                            label: __('Number of Events', 'wp-events'),
                            value: attributes.limit,
                            min: 1,
                            max: 20,
                            onChange: function(value) {
                                setAttributes({ limit: value });
                            }
                        })
                    )
                ),
                el(serverSideRender, {
                    block: 'wp-events/events-list',
                    attributes: attributes
                })
            ];
        },
        
        save: function() {
            return null; // Server-side rendered
        }
    });

    // Events Carousel Block
    blocks.registerBlockType('wp-events/events-carousel', {
        title: __('Events Carousel', 'wp-events'),
        icon: 'images-alt2',
        category: 'wp-events',
        description: __('Display events in a carousel format', 'wp-events'),
        
        supports: {
            align: ['left', 'center', 'right'],
            anchor: true,
            className: true,
            color: {
                gradients: true,
                link: true,
                __experimentalDefaultControls: {
                    background: true,
                    text: true
                }
            },
            spacing: {
                margin: true,
                padding: true,
                __experimentalDefaultControls: {
                    margin: false,
                    padding: false
                }
            },
            typography: {
                fontSize: true,
                fontFamily: true,
                fontStyle: true,
                fontWeight: true,
                letterSpacing: true,
                lineHeight: true,
                textDecoration: true,
                textTransform: true,
                __experimentalDefaultControls: {
                    fontSize: true
                }
            }
        },
        
        attributes: {
            limit: {
                type: 'number',
                default: 5
            }
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            return [
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Carousel Settings', 'wp-events') },
                        el(components.RangeControl, {
                            label: __('Number of Events', 'wp-events'),
                            value: attributes.limit,
                            min: 1,
                            max: 20,
                            onChange: function(value) {
                                setAttributes({ limit: value });
                            }
                        })
                    )
                ),
                el(serverSideRender, {
                    block: 'wp-events/events-carousel',
                    attributes: attributes
                })
            ];
        },
        
        save: function() {
            return null; // Server-side rendered
        }
    });

    // Event Price Block
    blocks.registerBlockType('wp-events/event-price', {
        title: __('Event Price', 'wp-events'),
        icon: 'money-alt',
        category: 'wp-events',
        description: __('Display event price with currency', 'wp-events'),
        
        supports: {
            anchor: true,
            className: true,
            color: {
                gradients: true,
                link: true,
                __experimentalDefaultControls: {
                    background: true,
                    text: true
                }
            },
            spacing: {
                margin: true,
                padding: true,
                __experimentalDefaultControls: {
                    margin: false,
                    padding: false
                }
            },
            typography: {
                fontSize: true,
                fontFamily: true,
                fontStyle: true,
                fontWeight: true,
                letterSpacing: true,
                lineHeight: true,
                textDecoration: true,
                textTransform: true,
                __experimentalDefaultControls: {
                    fontSize: true
                }
            }
        },
        
        attributes: {
            textAlign: {
                type: 'string'
            },
            showLabel: {
                type: 'boolean',
                default: false
            },
            customLabel: {
                type: 'string',
                default: 'Pris:'
            },
            labelBold: {
                type: 'boolean',
                default: false
            },
            labelItalic: {
                type: 'boolean',
                default: false
            },
            showCurrency: {
                type: 'boolean',
                default: true
            },
            priceFormat: {
                type: 'string',
                default: 'after' // before, after
            }
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var BlockControls = blockEditor.BlockControls;
            var AlignmentToolbar = blockEditor.AlignmentToolbar;
            
            return [
                el(BlockControls, null,
                    el(AlignmentToolbar, {
                        value: attributes.textAlign,
                        onChange: function(alignment) {
                            setAttributes({ textAlign: alignment });
                        }
                    })
                ),
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Price Settings', 'wp-events') },
                        el(ToggleControl, {
                            label: __('Show Label', 'wp-events'),
                            checked: attributes.showLabel,
                            onChange: function(value) {
                                setAttributes({ showLabel: value });
                            }
                        }),
                        attributes.showLabel && el(TextControl, {
                            label: __('Custom Label', 'wp-events'),
                            value: attributes.customLabel,
                            onChange: function(value) {
                                setAttributes({ customLabel: value });
                            },
                            help: __('Enter custom text for the label', 'wp-events')
                        }),
                        attributes.showLabel && el(ToggleControl, {
                            label: __('Bold Label', 'wp-events'),
                            checked: attributes.labelBold,
                            onChange: function(value) {
                                setAttributes({ labelBold: value });
                            }
                        }),
                        attributes.showLabel && el(ToggleControl, {
                            label: __('Italic Label', 'wp-events'),
                            checked: attributes.labelItalic,
                            onChange: function(value) {
                                setAttributes({ labelItalic: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Currency Symbol', 'wp-events'),
                            checked: attributes.showCurrency,
                            onChange: function(value) {
                                setAttributes({ showCurrency: value });
                            }
                        }),
                        attributes.showCurrency && el(SelectControl, {
                            label: __('Currency Position', 'wp-events'),
                            value: attributes.priceFormat,
                            options: [
                                { label: __('After amount (100 DKK)', 'wp-events'), value: 'after' },
                                { label: __('Before amount (DKK 100)', 'wp-events'), value: 'before' }
                            ],
                            onChange: function(value) {
                                setAttributes({ priceFormat: value });
                            }
                        })
                    )
                ),
                el('div', { 
                    className: 'wp-block-wp-events-price-preview',
                    style: { textAlign: attributes.textAlign }
                },
                    el(serverSideRender, {
                        block: 'wp-events/event-price',
                        attributes: attributes
                    })
                )
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
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n
);
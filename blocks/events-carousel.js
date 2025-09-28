/**
 * Events Carousel Block (Legacy)
 */
( function( blocks, element, serverSideRender, i18n ) {
    var el = element.createElement;
    var __ = i18n.__;
    
    blocks.registerBlockType( 'wp-events/events-carousel', {
        title: __( 'Events Carousel', 'wp-events' ),
        icon: 'images-alt2',
        category: 'wp-events',
        description: __( 'Display events in a carousel format', 'wp-events' ),
        
        edit: function( props ) {
            return el( serverSideRender, {
                block: 'wp-events/events-carousel'
            });
        },
        
        save: function() {
            return null; // Server-side rendered
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.serverSideRender,
    window.wp.i18n
);

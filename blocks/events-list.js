/**
 * Events List Block (Legacy)
 */
( function( blocks, element, serverSideRender, i18n ) {
    var el = element.createElement;
    var __ = i18n.__;
    
    blocks.registerBlockType( 'wp-events/events-list', {
        title: __( 'Events List', 'wp-events' ),
        icon: 'calendar',
        category: 'wp-events',
        description: __( 'Display a list of events', 'wp-events' ),
        
        edit: function( props ) {
            return el( serverSideRender, {
                block: 'wp-events/events-list'
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

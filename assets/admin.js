/**
 * WP Events Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    // Featured Image functionality for Venue
    var frame;
    
    $('#venue_featured_image_button').on('click', function(e) {
        e.preventDefault();
        
        // If the media frame already exists, reopen it.
        if (frame) {
            frame.open();
            return;
        }
        
        // Create a new media frame
        frame = wp.media({
            title: 'Select Featured Image',
            button: {
                text: 'Use as Featured Image'
            },
            multiple: false
        });
        
        // When an image is selected in the media frame...
        frame.on('select', function() {
            // Get media attachment details from the frame state
            var attachment = frame.state().get('selection').first().toJSON();
            
            // Set as featured image via AJAX
            $.post(ajaxurl, {
                action: 'set_venue_featured_image',
                post_id: $('#post_ID').val(),
                attachment_id: attachment.id,
                nonce: wp_events_admin.nonce
            }, function(response) {
                if (response.success) {
                    location.reload(); // Reload to show the new image
                } else {
                    alert('Error setting featured image: ' + response.data);
                }
            });
        });
        
        // Finally, open the modal on click
        frame.open();
    });
    
    // Remove featured image
    $('#venue_remove_image_button').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('Are you sure you want to remove the featured image?')) {
            $.post(ajaxurl, {
                action: 'remove_venue_featured_image',
                post_id: $('#post_ID').val(),
                nonce: wp_events_admin.nonce
            }, function(response) {
                if (response.success) {
                    location.reload(); // Reload to hide the image
                } else {
                    alert('Error removing featured image: ' + response.data);
                }
            });
        }
    });
});
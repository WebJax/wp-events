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

    var i18n = (typeof wp_events_admin !== 'undefined' && wp_events_admin.i18n) ? wp_events_admin.i18n : {};

    $(document).on('click', '.wpevents-related-toggle', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $form = $('#' + $button.attr('aria-controls'));
        var open = $form.prop('hidden');
        $form.prop('hidden', !open);
        $button.attr('aria-expanded', open ? 'true' : 'false');
        if (open) {
            $form.find('.wpevents-related-title').trigger('focus');
        }
    });

    $(document).on('keydown', '.wpevents-related-form input', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).closest('.wpevents-related').find('.wpevents-related-create').trigger('click');
        }
    });

    $(document).on('click', '.wpevents-related-create', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $root = $button.closest('.wpevents-related');
        var target = $root.data('target');
        var title = $.trim($root.find('.wpevents-related-title').val());
        var $message = $root.find('.wpevents-related-message');
        var ajaxUrl = (typeof wp_events_admin !== 'undefined' && wp_events_admin.ajax_url) ? wp_events_admin.ajax_url : ajaxurl;

        $message.empty();
        if (!title) {
            $message.text(i18n.nameRequired || 'Enter a name.');
            return;
        }

        var payload = {
            action: target === 'organizer' ? 'wpevents_create_organizer' : 'wpevents_create_venue',
            nonce: wp_events_admin.nonce,
            title: title
        };
        $root.find('[data-field]').each(function() {
            payload[$(this).data('field')] = $(this).val();
        });

        $button.prop('disabled', true).data('label', $button.text()).text(i18n.saving || 'Saving…');

        $.post(ajaxUrl, payload).done(function(response) {
            if (!response || !response.success || !response.data) {
                $message.text((response && response.data) ? response.data : (i18n.createFailed || 'Could not save. Try again.'));
                return;
            }

            var createdId = parseInt(response.data.id, 10);
            if (!createdId) {
                $message.text(i18n.createFailed || 'Could not save. Try again.');
                return;
            }

            if (target === 'organizer') {
                var $list = $('#wpevents-organizer-list');
                var $existing = $list.find('input').filter(function() {
                    return parseInt(this.value, 10) === createdId;
                });
                if (!$existing.length) {
                    var $label = $('<label>', { css: { display: 'block', margin: '5px 0' } });
                    $('<input>', {
                        type: 'checkbox',
                        name: 'event_organizer[]',
                        value: String(createdId),
                        checked: true
                    }).appendTo($label);
                    $label.append(document.createTextNode(' ' + response.data.title));
                    $list.append($label);
                } else {
                    $existing.prop('checked', true);
                }
            } else {
                var $select = $('#wpevents-event-venue');
                if (!$select.find('option').filter(function() {
                    return parseInt(this.value, 10) === createdId;
                }).length) {
                    $select.append($('<option>', {
                        value: String(createdId),
                        text: response.data.title
                    }));
                }
                $select.val(String(createdId));
            }

            $root.find('.wpevents-related-form').prop('hidden', true).find('input').val('');
            $root.find('.wpevents-related-toggle').attr('aria-expanded', 'false');
            $message.append(document.createTextNode((target === 'organizer' ? (i18n.organizerCreated || 'Organizer created.') : (i18n.venueCreated || 'Venue created.')) + ' '));
            if (response.data.editUrl) {
                $('<a>', {
                    href: response.data.editUrl,
                    target: '_blank',
                    rel: 'noopener noreferrer',
                    text: i18n.editDetails || 'Edit details'
                }).appendTo($message);
            }
        }).fail(function(xhr) {
            var message = i18n.createFailed || 'Could not save. Try again.';
            if (xhr.responseJSON && xhr.responseJSON.data) {
                message = xhr.responseJSON.data;
            }
            $message.text(message);
        }).always(function() {
            $button.prop('disabled', false).text($button.data('label'));
        });
    });
});
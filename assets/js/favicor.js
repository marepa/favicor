jQuery(function($){

	// on upload button click
	jQuery('body').on( 'click', '.favicor-upload', function(e){

		e.preventDefault();

		var button = jQuery(this),
		custom_uploader = wp.media({
			title: 'Insert image',
			library : {
				// uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
				type : 'image'
			},
			button: {
				text: 'Use this image' // button label text
			},
			multiple: false
		}).on('select', function() { // it also has "open" and "close" events
			var attachment = custom_uploader.state().get('selection').first().toJSON();

			jQuery( '.favicor-upload img' ).attr( 'src', attachment.url );
			jQuery( '[name="favicor_main_image"]' ).val( attachment.id );
            // console.log( attachment );
		}).open();
	
	});
});
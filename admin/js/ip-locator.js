jQuery(document).ready( function($) {
	$( ".iplocator-about-logo" ).css({opacity:1});
	$( ".iplocator-select" ).each(
		function() {
			var chevron  = 'data:image/svg+xml;base64,PHN2ZwogIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICB3aWR0aD0iMjQiCiAgaGVpZ2h0PSIyNCIKICB2aWV3Qm94PSIwIDAgMjQgMjQiCiAgZmlsbD0ibm9uZSIKICBzdHJva2U9IiM3Mzg3OUMiCiAgc3Ryb2tlLXdpZHRoPSIyIgogIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIKICBzdHJva2UtbGluZWpvaW49InJvdW5kIgo+CiAgPHBvbHlsaW5lIHBvaW50cz0iNiA5IDEyIDE1IDE4IDkiIC8+Cjwvc3ZnPgo=';
			var classes  = $( this ).attr( "class" ),
				id           = $( this ).attr( "id" ),
				name         = $( this ).attr( "name" );
			var template = '<div class="' + classes + '">';
			template    += '<span class="iplocator-select-trigger">' + $( this ).attr( "placeholder" ) + '&nbsp;<img style="width:18px;vertical-align:top;" src="' + chevron + '" /></span>';
			template    += '<div class="iplocator-options">';
			$( this ).find( "option" ).each(
				function() {
					template += '<span class="iplocator-option ' + $( this ).attr( "class" ) + '" data-value="' + $( this ).attr( "value" ) + '">' + $( this ).html().replace("~-", "<br/><span class=\"iplocator-option-subtext\">").replace("-~", "</span>") + '</span>';
				}
			);
			template += '</div></div>';

			$( this ).wrap( '<div class="iplocator-select-wrapper"></div>' );
			$( this ).after( template );
		}
	);
	$( ".iplocator-option:first-of-type" ).hover(
		function() {
			$( this ).parents( ".iplocator-options" ).addClass( "option-hover" );
		},
		function() {
			$( this ).parents( ".iplocator-options" ).removeClass( "option-hover" );
		}
	);
	$( ".iplocator-select-trigger" ).on(
		"click",
		function() {
			$( 'html' ).one(
				'click',
				function() {
					$( ".iplocator-select" ).removeClass( "opened" );
				}
			);
			$( this ).parents( ".iplocator-select" ).toggleClass( "opened" );
			event.stopPropagation();
		}
	);
	$( ".iplocator-option" ).on(
		"click",
		function() {
			$(location).attr("href", $( this ).data( "value" ));
		}
	);
} );

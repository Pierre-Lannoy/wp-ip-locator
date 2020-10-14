jQuery(document).ready( function($) {
	function testIP() {
		$("#iplocator_test_ip_action").addClass('disabled');
		$("#iplocator_test_ip_value").addClass('disabled');
		$("#iplocator_test_ip_text").hide();
		$("#iplocator_test_ip_describer").hide();
		$("#iplocator_test_ip_wait").show();
		root.innerHTML = '';
		$.ajax(
			{
				type: 'GET',
				url: describer.restUrl,
				data: {ip: $("#iplocator_test_ip_value").val(), locale: describer.locale},
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', describer.restNonce);
				},
				success: function (response) {
					if (response) {
						//elem           = document.createElement( 'div' );
						root.innerHTML = '<div id="iplocator_test_ip_flag"><img src="' + response.flag.rectangle + '" /></div><div id="iplocator_test_ip_maintext"><span id="iplocator_test_ip_country">' + response.country.name + '</span><span id="iplocator_test_ip_language" class="dashicons dashicons-translation">&nbsp;<em>' + response.language.name + '</em></span></div>';
						//root.appendChild( elem );
					}
				},
				error: function (response) {
					console.log(response);
				},
				complete: function (response) {
					$("#iplocator_test_ip_action").removeClass('disabled');
					$("#iplocator_test_ip_value").removeClass('disabled');
					$("#iplocator_test_ip_wait").hide();
					$("#iplocator_test_ip_describer").show();
					$("#iplocator_test_ip_text").show();
				}
			}
		);
	}
	$( ".iplocator-about-detectiono" ).css({opacity:1});
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
	$( "#iplocator_test_ip_action" ).on(
		"click",
		function() {
			testIP();
		}
	);

	$( "#iplocator-chart-button-country" ).on(
		"click",
		function() {
			$( "#iplocator-chart-country" ).addClass( "active" );
			$( "#iplocator-chart-language" ).removeClass( "active" );
			$( "#iplocator-chart-access" ).removeClass( "active" );
			$( "#iplocator-chart-detection" ).removeClass( "active" );
			$( "#iplocator-chart-button-country" ).addClass( "active" );
			$( "#iplocator-chart-button-language" ).removeClass( "active" );
			$( "#iplocator-chart-button-access" ).removeClass( "active" );
			$( "#iplocator-chart-button-detection" ).removeClass( "active" );
		}
	);
	$( "#iplocator-chart-button-language" ).on(
		"click",
		function() {
			$( "#iplocator-chart-country" ).removeClass( "active" );
			$( "#iplocator-chart-language" ).addClass( "active" );
			$( "#iplocator-chart-access" ).removeClass( "active" );
			$( "#iplocator-chart-detection" ).removeClass( "active" );
			$( "#iplocator-chart-button-country" ).removeClass( "active" );
			$( "#iplocator-chart-button-language" ).addClass( "active" );
			$( "#iplocator-chart-button-access" ).removeClass( "active" );
			$( "#iplocator-chart-button-detection" ).removeClass( "active" );
		}
	);
	$( "#iplocator-chart-button-access" ).on(
		"click",
		function() {
			$( "#iplocator-chart-country" ).removeClass( "active" );
			$( "#iplocator-chart-language" ).removeClass( "active" );
			$( "#iplocator-chart-access" ).addClass( "active" );
			$( "#iplocator-chart-detection" ).removeClass( "active" );
			$( "#iplocator-chart-button-country" ).removeClass( "active" );
			$( "#iplocator-chart-button-language" ).removeClass( "active" );
			$( "#iplocator-chart-button-access" ).addClass( "active" );
			$( "#iplocator-chart-button-detection" ).removeClass( "active" );
		}
	);
	$( "#iplocator-chart-button-detection" ).on(
		"click",
		function() {
			$( "#iplocator-chart-country" ).removeClass( "active" );
			$( "#iplocator-chart-language" ).removeClass( "active" );
			$( "#iplocator-chart-access" ).removeClass( "active" );
			$( "#iplocator-chart-detection" ).addClass( "active" );
			$( "#iplocator-chart-button-country" ).removeClass( "active" );
			$( "#iplocator-chart-button-language" ).removeClass( "active" );
			$( "#iplocator-chart-button-access" ).removeClass( "active" );
			$( "#iplocator-chart-button-detection" ).addClass( "active" );
		}
	);

	const root = document.querySelector( '#iplocator_test_ip_describer' );
	
} );

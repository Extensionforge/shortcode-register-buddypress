(function( $ ) {
	'use strict';

	$( document ).ready( function () {

	  var j = jQuery;

	$( "input#com_username" ).wrap( "<div id='username_checker'></div> " );
	$( "#username_checker" ).append( "<span class='loading' style='display:none'></span>" )
	$( "#username_checker" ).append( "<span id='name-info'></span> " );
	
	$( "input#com_username" ).bind( "blur", function () {

		$( "#username_checker #name-info" ).empty();
		//show loading icon
		$( "#username_checker .loading" ).css( { display: 'block' } );

		var user_name = j( "input#com_username" ).val();

		var user_email = j( "input#com_email" ).val();



		user_name = user_name.toLowerCase();
		//string = user_name.replace(/[^a-zA-Z ]/g, "");
		$("input#com_username").val(user_name);

		   $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
               action: 'bpdev_ua_check_username',
               user_name: user_name,
               user_email: user_email
            },  
           success: function(resp) {
                      if( resp && resp.code != undefined && resp.code == 'success' ) {
						//alert(resp.message);
						show_message(resp.message,0);
						} else {
						//alert(resp.message);
						show_message(resp.message,1);
						}
                      },
                      error: function() {
                        alert('There was some error performing!');
                      }
        });
	} );



function removeSpecialCharacter(s)
{
	for (let i = 0; i < s.length; i++)
		{

			// Finding the character whose
			// ASCII value fall under this
			// range
			if (s[i] < 'A' || s[i] > 'Z' &&
					s[i] < 'a' || s[i] > 'z')
			{
				
				// erase function to erase
				// the character
				s = s.substring(0, i) + s.substring(i + 1);
				i--;
			}
		}
		return s;
}

	
	function show_message( msg, is_error )	{//hide ajax loader
		
		j( "#username_checker #name-info" ).removeClass();
		j( "#username_checker .loading" ).css( { display: 'none' } );
		j( "#username_checker #name-info" ).empty().html( msg );
		
		if ( is_error ) {
			j( "#username_checker #name-info" ).addClass( "error" );
		} else {
			j( "#username_checker #name-info" ).addClass( "available" );
		}
	}
} );

})( jQuery );

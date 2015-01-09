<?php

return array(
	'versions' => array(
		'master' => array(
			'jquery' => 
"
$( 'form' ).each(function() {
    var form = this;

    // Suppress the default bubbles
    form.addEventListener( 'invalid', function( event ) {
        event.preventDefault();
    }, true );

    // Support Safari, iOS Safari, and the Android browser—each of which do not prevent
    // form submissions by default
    $( form ).on( 'submit', function( event ) {
        if ( !this.checkValidity() ) {
            event.preventDefault();
        }
    });

    $( 'input, select, textarea', form )
        // Destroy the tooltip on blur if the field contains valid data
        .on( 'blur', function() {
            var field = $( this );
            if ( field.data( 'kendoTooltip' ) ) {
                if ( this.validity.valid ) {
                    field.kendoTooltip( 'destroy' );
                } else {
                    field.kendoTooltip( 'hide' );
                }
            }
        })
        // Show the tooltip on focus
        .on( 'focus', function() {
            var field = $( this );
            if ( field.data( 'kendoTooltip' ) ) {
                field.kendoTooltip( 'show' );
            }
        });

    $( 'button:not([type=button]), input[type=submit]', form ).on( 'click', function( event ) {
        // Destroy any tooltips from previous runs
        $( 'input, select, textarea', form ).each( function() {
            var field = $( this );
            if ( field.data( 'kendoTooltip' ) ) {
                field.kendoTooltip( 'destroy' );
            }
        });

        // Add a tooltip to each invalid field
        var invalidFields = $( ':invalid', form ).each(function() {
            var field = $( this ).kendoTooltip({
                content: function() {
                    return field[ 0 ].validationMessage;
                }
            });
        });

        // If there are errors, give focus to the first invalid field
        invalidFields.first().trigger( 'focus' ).eq( 0 ).focus();
    });
});

"
		),
	),
	'require' => array(
		'asset' => 'kendoui'
	),
);

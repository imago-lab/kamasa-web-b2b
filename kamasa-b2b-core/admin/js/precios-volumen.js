jQuery( function ( $ ) {
    var $tableBody   = $( '#kamasa-precios-volumen-rows' );
    var $templateRow = $( '#kamasa-precios-volumen-row-template tr' ).first();

    if ( ! $tableBody.length || ! $templateRow.length ) {
        return;
    }

    $( '.kamasa-add-range' ).on( 'click', function ( event ) {
        event.preventDefault();

        var $newRow = $templateRow.clone();

        $newRow.find( 'input' ).val( '' );

        $tableBody.append( $newRow );
    } );

    $tableBody.on( 'click', '.kamasa-remove-range', function ( event ) {
        event.preventDefault();

        var $rows = $tableBody.find( '.kamasa-precios-volumen__row' );

        if ( $rows.length <= 1 ) {
            $rows.first().find( 'input' ).val( '' );
            return;
        }

        $( this ).closest( 'tr' ).remove();
    } );
} );

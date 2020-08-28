# Archives

## Get a list of archives

```
jQuery.ajax( {
    url: jQuery( '#bgbkup_site_url' ).val()  + '/wp-json/bgbkup/v1/archives/',
    method: 'GET',
    beforeSend: function ( xhr ) {
        xhr.setRequestHeader( 'X-WP-Nonce', jQuery( '#wp_rest' ).val() );
    }
} ).done( function ( response ) {
    console.log( response );
} );
```

## Create

```
jQuery.ajax( {
    url: jQuery( '#bgbkup_site_url' ).val()  + '/wp-json/bgbkup/v1/archives',
    method: 'POST',
    beforeSend: function ( xhr ) {
        xhr.setRequestHeader( 'X-WP-Nonce', jQuery( '#wp_rest' ).val() );
    }
} ).done( function ( response ) {
    console.log( response );
} );
```

## Restore

### Restore via id

Take note of the `id=4` in the url.

```
jQuery.ajax( {
    url: jQuery( '#bgbkup_site_url' ).val()  + '/wp-json/bgbkup/v1/archives/?id=4',
    method: 'PUT',
    beforeSend: function ( xhr ) {
        xhr.setRequestHeader( 'X-WP-Nonce', jQuery( '#wp_rest' ).val() );
    }
} ).done( function ( response ) {
    console.log( response );
} );
```

### Restore via url

This needs to be fleshed out more.

```
jQuery.ajax({
	url: 'https://domain.com/wp-json/bgbkup/v1/archives/?url=' + encodeURIComponent( <URL> ),
	type: 'put'
});
```
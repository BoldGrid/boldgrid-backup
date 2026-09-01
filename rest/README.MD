# Rest API

## Authentication

### How are Total Upkeep's Rest API calls authenticated?

REST API calls are authenticated with a [permissions callback](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#permissions-callback).

Each registered route includes a permission_callback:

```php
register_rest_route( $this->namespace, '/' . $this->resource, [
	[
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => [ $this, 'get_items' ],
		'permission_callback' => [ $this, 'permission_check' ],
	],
	'schema' => [ $this, 'get_schema' ],
] );
```

Our rest classes extend `Boldgrid_Backup_Rest_Controller`, which includes the `permission_check` method.

### Are there any public Rest calls?

No.

### How can I make a Rest call if no routes are public?

All calls will need to be authenticated. Please see [Using the REST API / Authentication](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/).

> For developers making manual Ajax requests, the nonce will need to be passed with each request. The API uses nonces with the
> action set to wp_rest. These can then be passed to the API via the _wpnonce data parameter (either POST data or in the query for
> GET requests), or via the X-WP-Nonce header. If no nonce is provided the API will set the current user to 0, turning the request
> into an unauthenticated request, even if you’re logged into WordPress.

The examples on this page use the `X-WP-Nonce` method.

# Manual Testing

## Setup

Before testing the jQuery calls on this page, be sure to add the following to your wp-config.php:

`define( 'WP_ENVIRONMENT_TYPE', 'development' );`

This will:

1. Add the `wp_rest` nonce to all your admin pages
1. Add your site url as `bgbkup_site_url`. This is a hidden input, who's value is used in the example calls below.

Failure to do the above, and you will have unauthenticated calls, resulting in a 403.

## Archives

### Get a list of archives

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

### Create

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

### Restore

#### Restore via id

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

#### Restore via url

This needs to be fleshed out more.

```
jQuery.ajax({
	url: 'https://domain.com/wp-json/bgbkup/v1/archives/?url=' + encodeURIComponent( <URL> ),
	type: 'put'
});
```

## Site URL ##

### Get ###

```
jQuery.ajax( {
    url: jQuery( '#bgbkup_site_url' ).val()  + '/wp-json/bgbkup/v1/siteurl',
    method: 'GET',
    beforeSend: function ( xhr ) {
        xhr.setRequestHeader( 'X-WP-Nonce', jQuery( '#wp_rest' ).val() );
    }
} ).done( function ( response ) {
    console.log( response );
} );
```

### Set ###

```
jQuery.ajax( {
    url: jQuery( '#bgbkup_site_url' ).val()  + '/wp-json/bgbkup/v1/siteurl/?siteurl=https://[NEW_SITE_URL]',
    method: 'POST',
    beforeSend: function ( xhr ) {
        xhr.setRequestHeader( 'X-WP-Nonce', jQuery( '#wp_rest' ).val() );
    }
} ).done( function ( response ) {
    console.log( response );
} );
```
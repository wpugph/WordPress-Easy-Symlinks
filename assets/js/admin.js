/**
 * Easy Symlink admin js.
 *
 *  @package Easy Symlink admin/JS
 */

jQuery( document ).ready(
	function (e) {
		var url_string = window.location.href;
		var url        = new URL( url_string );
		var page       = url.searchParams.get( 'page' );
		var tab        = url.searchParams.get( 'tab' );
		var deleted    = url.searchParams.get( 'caes_deleted' );
		var refer      = document.getElementsByName( '_wp_http_referer' )[0].value;

		if ( 'easy_symlinks_settings' === page ) {
			if ( ( null === tab ) || ( 'add' === tab ) ) {

				var caesTarget = document.getElementById( 'target' );
				if ( caesTarget ) {
					document.getElementById( 'target' ).value = '';
				}

				var caesLink = document.getElementById( 'link' );
				if ( caesLink ) {
					document.getElementById( 'link' ).value = '';
				}
			}
		}

		var url3     = new URL( url.origin + refer );
		var page3    = url3.searchParams.get( 'page' );
		var tab3     = url3.searchParams.get( 'tab' );
		var updated3 = url3.searchParams.get( 'settings-updated' );
		if ( ( 'easy_symlinks_settings' === page3 ) && ( 'delete' === tab ) && ( 'true' === updated3 ) ) {
			var redir = url.origin + '/wp-admin/options-general.php?page=easy_symlinks_settings&tab=delete';
			window.location.replace( redir );
		}
	}
);

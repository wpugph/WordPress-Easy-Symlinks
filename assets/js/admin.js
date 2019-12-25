/**
 * Easy Symlink admin js.
 *
 *  @package Easy Symlink admin./JS
 */

jQuery(document).ready(
	function (e) {
		var url_string = window.location.href;
		var url = new URL(url_string);
		var page = url.searchParams.get("page");
		var tab = url.searchParams.get("tab");
		var deleted = url.searchParams.get('caes_deleted');
		var refer = document.getElementsByName("_wp_http_referer")[0].value;

		if ( 'easy_symlinks_settings' === page ) {
			if ( ( null === tab ) || ( 'add' === tab ) ) {
				document.getElementById('target').value = '';
				document.getElementById('link').value = '';
			}
		}

		if ( '/wp-admin/options-general.php?page=easy_symlinks_settings&tab=delete&settings-updated=true' === refer ) {
			var redir = url.origin + '/wp-admin/options-general.php?page=easy_symlinks_settings&tab=delete';
			console.log( redir );
			window.location.replace( redir );
		}
	}
);

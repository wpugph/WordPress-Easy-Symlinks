=== Easy Symlinks ===
Contributors: carl-alberto
Donate link: https://carlalberto.code.blog/
Tags: symlink, symlinks, pantheon, file-management, developer-tools
Requires at least: 4.9
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage symbolic links from the WordPress admin. Create and delete symlinks without CLI access, ideal for Pantheon environments.

== Description ==

Easy Symlinks lets you create and manage symbolic links (symlinks) directly from the WordPress admin dashboard — no command line required.

**Features:**

* Create symlinks by specifying a target directory and link path
* Delete symlinks individually through a simple dropdown interface
* Automatic environment detection for Pantheon hosting (Dev, Test, Live, Multidev)
* Filesystem writability checks — prevents symlink creation in read-only environments
* Displays existing symlinks and their targets
* Tracks only symlinks created through the plugin
* Duplicate symlink detection — prevents creating symlinks that already exist
* Optional database cleanup on plugin uninstall via Settings tab

**How to use:**

Navigate to **Settings > Easy Symlinks** in your WordPress admin dashboard. Use the "Add Symlinks" tab to create new symlinks, the "Delete Symlinks" tab to remove them, and the "Settings" tab to configure plugin options like database cleanup on uninstall.

For common symlink configurations on Pantheon, refer to the [list of common symlinks](https://wordpress.org/support/topic/list-of-common-symlinks-in-pantheon).

**Important notes:**

* This plugin only tracks symlinks created within the application. Symlinks created from the filesystem or command line are not tracked.
* Best used in Pantheon Dev or Multidev environments in SFTP mode.
* Symlink creation is disabled in read-only environments (e.g., Pantheon Test and Live).

== Installation ==

= Automatic installation =

1. Go to **Plugins > Add New** in your WordPress admin dashboard
2. Search for "Easy Symlinks"
3. Click **Install Now** and then **Activate**

= Manual installation =

1. Download the plugin ZIP file from WordPress.org
2. Go to **Plugins > Add New > Upload Plugin** in your WordPress admin dashboard
3. Upload the ZIP file and click **Install Now**
4. Activate the plugin through the **Plugins** menu

= Configuration =

After activation, navigate to **Settings > Easy Symlinks** to start creating symlinks.

== Frequently Asked Questions ==

= What is a symlink? =

A symbolic link (symlink) is a file that points to another file or directory. It acts as a shortcut, allowing you to access files from a different location without duplicating them.

= What is the difference between Target and Link? =

The **Target** is the relative path to an existing directory (e.g., `./uploads/cache`). The **Link** is the path where the symlink will be created (e.g., `/wp-content/cache`).

= Why can I not create symlinks on Pantheon Test or Live environments? =

Pantheon Test and Live environments have read-only filesystems. Symlinks can only be created in Dev or Multidev environments with SFTP connection mode enabled.

= Does this plugin work on non-Pantheon hosting? =

Yes, the plugin works on any hosting environment where the filesystem is writable. The Pantheon-specific features (environment detection, connection mode display) will simply not appear.

= What happens to my symlinks if I deactivate the plugin? =

The symlinks themselves remain on the filesystem. Deactivating the plugin only removes the admin interface — the actual symlinks are not deleted.

= Can I remove all plugin data from the database? =

Yes. Go to **Settings > Easy Symlinks > Settings** tab and check "Delete data on uninstall." When the plugin is deleted, all saved options will be removed from the database.

= Can I manage symlinks that were created via the command line? =

No. This plugin only tracks symlinks that were created through its own interface. Symlinks created via the command line or filesystem are not tracked or displayed.

== Screenshots ==

1. The Add Symlinks form in the WordPress admin — enter a target directory and link path to create a new symlink.
2. Environment detection showing writable and read-only status indicators for Pantheon hosting environments.
3. The Delete Symlinks interface with a dropdown list of active symlinks to remove.

== Changelog ==

= 1.0.5 =
* 2026-05-11
* Compatibility update for WordPress 6.8
* Modernized admin UI with card-based layout and WordPress-style tabs
* Fixed duplicate admin notices on settings save
* Fixed PHP warnings in create_folder and save_symlinks that broke HTTP headers
* Fixed PHP warning in delete_symlink when unlinking fails
* Added duplicate symlink detection — prevents re-creating existing symlinks
* Added clear error/success notifications for create and delete actions
* Removed redundant "Settings saved" notice when plugin shows its own messages
* Added Settings tab with "Delete data on uninstall" option for database cleanup
* Added empty state message in delete dropdown when no symlinks exist
* Fixed PHPCS violations — plugin now passes WordPress coding standards
* Added Requires PHP header (7.4) and Tested PHP up to (8.4)
* Fixed readme.txt tags and short description for WordPress.org compliance
* Updated GitHub Actions deploy workflow to v4/stable

= 1.0.3 =
* 2022-10-24
* Compatibility bump for WordPress 6.0.3

= 1.0.2 =
* 2021-10-09
* Compatibility bump for WordPress 5.8.1

= 1.0.1 =
* 2020-01-08
* Bug fixes and improvements — see [closed issues for 1.0.1](https://github.com/wpugph/WordPress-Easy-Symlinks/issues?q=is%3Aissue+milestone%3A1.0.1+is%3Aclosed)

= 1.0.0 =
* 2019-12-26
* Initial public release on WordPress.org
* Improved deleting symlinks UI
* Only one symlink is deleted at a time
* Added checks for Pantheon environment and writable filesystem

== Upgrade Notice ==

= 1.0.5 =
Compatibility update for WordPress 6.8. Modernized admin UI, duplicate symlink detection, database cleanup option, and multiple bug fixes. No breaking changes.

= 1.0.3 =
Compatibility update for WordPress 6.0.3. No breaking changes.

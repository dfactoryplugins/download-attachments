=== Download Attachments ===
Contributors: dfactory
Donate link: http://www.dfactory.co/
Tags: attachment, attachments, file, files, download, downloads, upload, doc, document, documents, monitor, manager
Requires at least: 4.7
Requires PHP: 7.0.0
Tested up to: 6.4.1
Stable tag: 1.2.24
License: MIT License
License URI: http://opensource.org/licenses/MIT

Download Attachments is a new approach to managing downloads in WordPress. It allows you to easily add and display download links in any post or page.

== Description ==

[Download Attachments](http://www.dfactory.co/products/download-attachments/) is a new approach to managing downloads in WordPress. Instead of bloated interface it enables simple, drag & drop and AJAX driven metabox where you can insert and manage your Media Library files and automatically or manually display them after, before or inside posts content.

For more information, check out plugin page at [dFactory](http://www.dfactory.co/) site.

= Features include: =

* Automatic or manual download links display
* Select post types where Download Attachments should be used
* Select list, table or sortable, dynamic table display style
* Downloads count
* Advanced attachments sorting
* Most Downloaded Attachments widget
* Drag & drop files ordering
* Based on Media Library attachments
* Easy customisation of Frontend & Backend display
* Pretty URLs for download links
* Option to encrypt URLs
* Customizable tamplates engine
* Custom download slug
* Custom permission for metabox display
* Option to exclude selected attachments from display
* Option to select from all Media Library files or only those attached to a post
* 2 shortcodes
* 5 functions and multiple filter hooks for developers
* Option to use attachment caption and/or description for download links description
* Compatible with WPML & Polylang
* .pot file for translations included

= Get involved =

Feel free to contribute to the source code on the [dFactory GitHub Repository](https://github.com/dfactoryplugins).

== Installation ==

1. Install Download Attachments either via the WordPress.org plugin directory, or by uploading the files to your server
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Download Attachments settings under News menu and set your desired options.

== Frequently Asked Questions ==

= Q. I have a question =

A. Chances are, someone else has asked it. Check out the support forum at: http://www.dfactory.co/support/

== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png

== Changelog ==

= 1.2.24 =
* New: Link target option for "Redirect to file" method
* Fix: Invalid attachments link attributes 
* Fix: Shortcode positioning broken
* Tweak: Improved protected posts support
* Tweak: Elementor compatibility improvement

= 1.2.23 =
* Fix: Hide widget if no attachments found

= 1.2.22 =
* Fix: Attachment disappearing on autosave

= 1.2.21 =
* Fix: Attachment disappearing form the backend and frontend
* Fix: Attachment title not visible in admin interface
* Fix: Exclude / include shortcode paramaters not working

= 1.2.20 =
* Fix: Fatal error on da_get_download_attachment function call
* Tweak: Style none for attachments list display

= 1.2.19 =
* New: Dynamic, sortable table display mode
* New: Templates engine for the attachment display
* New: Option to edit attachment downloads count
* New: Attachments widget redesign
* Fix: 404 error with pretty urls enabled
* Tweak: Stronger openssl url encryption method

= 1.2.18 =
* Fix: Undefined encrypt_urls variable notice
* Tweak: Added plugin documentation link

= 1.2.17 =
* New: Encrypt URL's option
* Fix: Missing docx and xlsx file icons
* Tweak: Revamped admin inteface

= 1.2.16 =
* New: Most Downloaded Attachments widget

= 1.2.15 =
* Tweak: Removed local translation files in favor of WP repository translations.

= 1.2.14 =
* Fix: TinyMCE editor broken on post types
* Fix: Attachments not displayed outside of the loop

= 1.2.13 =
* New: Insert download attachment link TinyMCE editor button
* Fix: Attached to a post option not working properly
* Fix: Switched from wp_upload_dir() url to baseurl for redirect method

= 1.2.12 =
* New: Option to select download method - force download or redirect to file

= 1.2.11 =
* Tweak: More flexible way of including wp-load.php

= 1.2.10 =
* New: Czech translation, thanks to Martin Kokes

= 1.2.9 =
* New: Romanian translation, thanks to Andrei Gabriel Grimpels
* Fix: Pretty URL 404 issue for wp installed in a separate folder - switched from site_url() to home_url()

= 1.2.8 =
* New: Swedish translation, thanks to [Daniel Storgards](www.danielstorgards.com)

= 1.2.7 =
* New: Finnish translation, thanks to [Daniel Storgards](www.danielstorgards.com)

= 1.2.6 =
* Fix: Download issues with pretty urls disabled

= 1.2.5 =
* New: Italian translation, thanks to Enzo Costantini

= 1.2.4 =
* New: da_get_download_attachment() function, to get complete single attachment data

= 1.2.3 =
* Tweak: Option to pass post_id parameter to [download-attachments] shortcode

= 1.2.2 =
* New: Hungarian translation, thanks to Meszaros Tamas
* Tweak: Uploaded files are now attached to a post

= 1.2.1 =
* New: French translation, thanks to Jean-Philippe Gurecki
* Fix: Attachment title display html fix
* Fix: Undefined notice index in admin and frontend

= 1.2.0 =
* New: Select list or table display style
* New: Option to display attached file index

= 1.1.2 =
* New: Option to donate this plugin :)

= 1.1.1 =
* New: German translation, thanks to [Sascha Brendel](http://sascha-brendel.de/blog/)

= 1.1.0 =
* New: Advanced attachments sorting - sponsored by [Capitol City Janitorial](http://www.ccjanitorial.com/)
* New: Option to exclude selected attachments from display - sponsored by [Capitol City Janitorial](http://www.ccjanitorial.com/)
* Tweak: UI adjusted to native WP interface

= 1.0.10 =
* Fix: Important attachments query optimization and general plugin performance, especially on sites with large number of attachments

= 1.0.9 =
* Tweak: Removed shop_order from default post types support
* Tweak: Confirmed WordPress 3.9 compatibility

= 1.0.8 =
* New: Dutch translation, thanks to [Sebas Blom](http://www.basbva.nl/)
* Tweak: Changed default attachment editing option to modal

= 1.0.7 =
* Tweak: UI improvements for WordPress 3.8

= 1.0.6 =
* New: Spanish translation, thanks to Cristian Sierra

= 1.0.5 =
* New: Danish translation, thanks to Martin Schulze

= 1.0.4 =
* New: Russian translation, thanks to Semion Zuev
* Tweak: Greek translation updated

= 1.0.3 =
* Fix: Include & exclude attributes not working for download-attachments shortcode

= 1.0.2 =
* New: Greek translation, thanks to vas gargan
* Fix: Attachments box cutsom title not working
* Tweak: Added file type classes for attachments list

= 1.0.0 =
Initial release

== Upgrade Notice ==

= 1.2.24 =
* New: Link target option for "Redirect to file" method
* Fix: Invalid attachments link attributes 
* Fix: Shortcode positioning broken
* Tweak: Improved protected posts support
* Tweak: Elementor compatibility improvement
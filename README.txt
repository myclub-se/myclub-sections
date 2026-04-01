=== MyClub Sections ===
Contributors: myclubse
Donate link: https://www.myclub.se
Tags: groups, members, administration
Requires at least: 6.4
Tested up to: 6.9
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Retrieves section information from the MyClub member administration platform. Generates pages for all sections in the MyClub platform.

== Description ==

This plugin is intended for associations and organizations that use the MyClub membership system and need to fetch data to display on their website. Examples of such data include calendars, news, and sections.

Please ensure that your server is running on PHP 7.4 or higher and your WordPress version is at least 6.4 to utilize this plugin fully.

=== Components ===
The components fetch objects from MyClub and store them in WordPress. These objects are retrieved continuously and automatically, but not in real time. You can choose to display the various components in different places on your website. The available components are:
* News
* Calendar
* Upcoming games
* Section description
* Sections

See the blocks below for more information.

This plugin loads a page for all sections that are present for the club in MyClub.

=== Appearance ===
All components are minimally designed to make them easier to customize and fit your website’s design. All headers, tables, images, and similar items have their own CSS classes, allowing you to style them according to your preferences.

== Dependencies ==

The plugin has no external plugin dependencies. All requirements are bundled in the plugin itself. However we are using the following opensource library (which is included in the plugin):
* FullCalendar (v5.11.5), which can be seen [here](https://fullcalendar.io/). All source to the plugin is available [here](https://github.com/fullcalendar/fullcalendar). No data is being sent to the FullCalendar plugin website.

== Installation ==

To fetch data from MyClub, you must first install this plugin:
1. Login to your WordPress Dashboard
2. Go to Plugins -> Add New
3. Search for MyClub sections plugin
4. Install the MyClub sections plugin
5. Activate it.
6. Add your API key to the plugin settings.

You can generate an API key within MyClub under Productions and prices in MyClub. Please note that once the key is generated, you need to save it immediately and paste it into the newly installed plugin.

Once the plugin is installed with the API key, you can begin using it. The plugin consists of various components that can be added to any page via either Gutenberg blocks, Shortcodes, or section-specific pages that are designed using a template and then applied to all sections. For example, you can place a calendar at the top and news below it. This setup will look the same across all section pages, but the plugin dynamically determines which calendar to display based on the section currently being viewed.

== External services ==

This plugin connects to the MyClub member administration platform (https://member.myclub.se/) to fetch data. This is required for the plugin to work.
This service is provided by MyClub AB: https://www.myclub.se/
Privacy policy: https://www.myclub.se/integritetspolicy

== Privacy ==

This plugin communicates with https://member.myclub.se/ to provide data for the plugin.

The following information is transmitted:
- Site URL

No personal user data is collected or stored. This is only sent when the data is being updated.

== Frequently Asked Questions ==

=== Caching ===
The plugin will try to clear cache on the following cache plugins for MyClub sections:
* Breeze
* Cache Enabler
* Hummingbird performance
* Hyper Cache
* LiteSpeed Cache
* SiteGround Optimizer
* Swift Performance
* WP Fastest Cache
* WP Rocket
* WP Super Cache
* W3 Total Cache
* NitroPack
* Redis or Memcache cache

For unsupported cache systems, please contact us to request integration.

== Changelog ==
= 1.1.4 =
* Updated news image handling to use 16/9 aspect ratio
* Fixed news handling for synchronization with the groups plugin
* Updated the calendar blocks fullcalendar version
* Fixed settings layout

= 1.1.3 =
* Fix missing import

= 1.1.2 =
* Fix missing import

= 1.1.1 =
* Fix version number

= 1.1.0 =
* Add support for options on calendar views

= 1.0.0 =
* Initial release

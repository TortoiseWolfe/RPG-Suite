=== RPG-Suite ===
Contributors: turtlewolfe
Tags: rpg, characters, buddypress, game, role-playing
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Role-playing characters for WordPress: character profiles, health, dice rolls, and a BuddyPress profile display.

== Description ==

RPG-Suite lets the people on your site create role-playing characters and show the active one on their BuddyPress profile.

* A Characters post type with a class and four attributes: Fortitude, Precision, Intellect and Charisma.
* A health system where every character has 100 hit points and a plain-language status such as Healthy or Critical.
* Dice rolls (a seven-sided die plus an attribute) for damage, healing and skill checks.
* Several characters per member, with one marked active and a switcher on the profile.
* A REST API under rpg-suite/v1 for reading characters, switching the active one, applying damage and healing, and rolling dice.
* A React display and switcher built on the scripts WordPress already ships (wp-element, wp-data and wp-api-fetch). Without JavaScript, a plain PHP display still shows the character.

BuddyPress is optional for the Characters post type and required for the profile display.

= Source code and build steps =

The plugin ships a compiled (minified) React build in react-app/build/. The human-readable source is in react-app/src/ inside the plugin, and in the public repository at https://github.com/TortoiseWolfe/RPG-Suite

To rebuild the compiled files from the source (needs Node.js 18 or newer):

1. Get the source from https://github.com/TortoiseWolfe/RPG-Suite or use react-app/ inside this plugin.
2. Run: cd react-app
3. Run: npm install
4. Run: npm run build (this runs wp-scripts build and writes react-app/build/index.js)

== Installation ==

1. Upload the rpg-suite folder to the /wp-content/plugins/ directory, or install the plugin through the Plugins screen in WordPress.
2. Activate the plugin through the Plugins screen.
3. Go to Characters in the admin menu and add a character. Set its class, attributes and health, and tick Active Character.
4. With BuddyPress active, the active character appears on the member's profile.

== Frequently Asked Questions ==

= Does it need BuddyPress? =

The Characters post type and the REST API work without it. The profile display needs BuddyPress.

= Where is the readable React code? =

In react-app/src/ in the plugin, and at https://github.com/TortoiseWolfe/RPG-Suite

== Changelog ==

= 0.1.3 =
* Added this readme.txt and the source-code and build steps for the compiled React files.
* Include react-app/src/ in the plugin package.
* Unslash and sanitize every value read from $_POST, including the nonce.
* Escape all admin and profile output (esc_html_e instead of _e).
* Corrected the Plugin URI and added an Author URI.
* Debug log lines are written only when WP_DEBUG_LOG is on.

= 0.1.2 =
* Percentage-based health system, with 100 hit points for every character.

= 0.1.1 =
* Character switching through the REST API and the BuddyPress profile display.

== Upgrade Notice ==

= 0.1.3 =
Security and coding-standards fixes requested by the WordPress Plugin Directory review.

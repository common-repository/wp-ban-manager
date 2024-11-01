=== Plugin Name ===
Contributors: jberculo
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7312978
Tags: comments, banning, tor, abuse
Requires at least: 3.3
Tested up to: 5.0
Stable tag: 1.1

WP Ban Manager enables you to block ips and users from posting comments to your site

== Description ==

WP Ban Manager was created out of a need to manage bans. Sometimes, when the discussion becomes ugly, you want to temporarily ban people rather than permanently, in order to let them cool down. Or you want to implement a "three strikes you're out" policy. When you have a lot of comments and heated discussions, managing this manually can take a substantial amount of your precious time. WP Ban Manager keeps track of all this for you, so you can focus on more rewarding activities.

As an additional feature, you can also ban comments from the Tor network. In our experience, it was used a lot by banned people to circumvent our bans.

Features:

- Ban IP-based, user based or both
- Multiple IP addresses assignable to one entry
- Wildcards in IP addresses allowed (xxx.yyy.*.* and xxx.yyy.zzz.*)
- Define your own ban "packages", eg. a two week ban, or a three month ban
- Custom messages to the offender can be defined individually or by "package"
- Assign these packages to your bans
- Block comments originating from the Tor network
- Allow or disallow Tor comments when a user is logged in (default is allow)
- Automatically unban users
- Automatically remove bans 1 year after expiration (configurable, GDPR-measure)

Note: This plugin stores its bans in the WP options table, and as such is not suitable for sites having hundreds of bans simultaneously. If there is a large demand for it
(or when people are willing to pay me :-)) I will consider updating this plugin.

== Installation ==

Installation instructions:

1. Upload the folder 'wp_ban_manager' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Why aren't there any questions? =

You guys did not ask any!

== Changelog ==

= 1.0 =
* First version... Feedback appreciated

= 1.1 =
* Fixed bug that would ban anyone when the banning on username was switched on without providing a username
* Added ban start date to the overview page
* Added option after what time an expired ban will be removed from the list (AVG/GDPR-compliance)
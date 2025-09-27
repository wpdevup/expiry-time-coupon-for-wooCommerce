=== Expiry Time Coupon for WooCommerce ===
Contributors: man4toman
Tags: woocommerce, coupons, expiry, expiration, time
Requires at least: 6.1
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3

Add a time-of-day component to WooCommerce coupon expiry that works together with the built-in expiry date.

== Description ==

WooCommerce lets you set a coupon "Expiry date" but not an exact time.
This lightweight plugin adds a "Coupon expiry time" field (HH:MM, 24h) to the coupon edit screen.  
When both date and time are set, the coupon will expire on that date *at* the time you specify.

**Key points**
* Works alongside WooCommerce’s native coupon expiry date.
* Time is stored internally as seconds since midnight and combined with the date.
* No changes to your theme or checkout templates required.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the ZIP via **Plugins → Add New → Upload**.
2. Activate **Expiry Time Coupon for WooCommerce**.
3. Edit or create a coupon in **Marketing → Coupons**, set an **Expiry date** and a **Coupon expiry time**.

== Usage ==

* Set **Expiry date** as usual in WooCommerce.
* Set **Coupon expiry time** in `HH:MM` (24-hour) format, for example `17:30`.
* The coupon will be valid until the combined timestamp; after that, it will be treated as expired.

== FAQ ==

= Do I have to set both date and time? =
No. If you don’t set a time, WooCommerce’s default date-only logic applies. If you set a time without a date, the time is ignored.

= What if my admin doesn’t have a time picker UI? =
The field accepts a simple `HH:MM` string. If a time picker is available in your admin, it will be used; otherwise you can type the time.

= Does this affect performance? =
No. It only adds a small check during coupon validation.

== Screenshots ==
1. Timepiker coupon for WooCommerce

== Changelog ==

= 1.0.0 =
* Initial release: add time-of-day to coupon expiry.

== Upgrade Notice ==

= 1.0.0 =
First release.

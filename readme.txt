=== MotionPoint Express - Website Translation ===
Contributors: motionpointexpress
Tags: translate, translation, localization, multilingual, language
Requires at least: 4.7
Tested up to: 6.4.1
Stable tag: 1.5
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


The plugin enables the integration of MotionPoint Express paid website translation services.

== Description ==

Introducing MP Express, the ultimate quick-start solution for effortless website
translation. With plans starting from $12 per month, MP Express offers faster,
more accurate translations than Google Translate, ensuring clear communication
with your multilingual audiences. Unlike traditional translation tools, MP Express
provides flexible, editable translations, empowering you to tailor your content for
maximum impact. Its intuitive interface allows you to edit your website live from
any device, ensuring seamless integration and quick updates.

As your business grows, MP Express scales with you, offering a suite of AI-
driven solutions to meet your evolving needs. With over 1 in 5 Americans
speaking a second language at home, MP Express enables you to engage with a
broader audience in and outside the United States, enhancing your community
relations and driving sales.

### Try risk free!
- Easy Translation Editing and Team Collaboration.
- Built for Marketers with No-Code Implementation.
- Superior to Google Translate.

Experience the power of MP Express with a free trial today. Benefit from a
proactive support team, valuable insights, and quick turnaround times, just like
our satisfied clients worldwide. Join the ranks of enterprise brands
and start your journey towards enhanced multilingual customer experiences with MP Express.

Start your website localization journey at [https://www.motionpoint.com/express/](https://www.motionpoint.com/express/)

### Services

The plugin relies on 2 external services:
1) app.motionpointexpress.com -- Mandatory external service; a Translation proxy solution that carries out the translation management & on-the-fly translation of the website. For more info on the service, see [https://www.motionpoint.com/express/](https://www.motionpoint.com/express/).

The plugin calls files remotely from https://app.motionpointexpress.com/client/{$project_code}/0/stub.json?deployed={$deployed} -- to obtain existing user settings & configuration from the platform. As this is adjustable on the fly, therefore cannot be included in the plugin itself.

ToS: https://content.motionpoint.com/MP-Express-Terms-of-Services
Privacy: https://www.motionpoint.com/company/privacy-policy/

2) https://prerender.io/ -- is an optional external service to increase the SEO visibility of the translated site by pre-rendering it for search engine bots not fully capable of executing JavaScript. For more info on the service, see https://prerender.io/

ToS: https://prerender.io/privacy-and-terms/

== Installation ==

**Requirements**

1. WordPress 4.7 or later 
2. PHP 7.0 or later.
3. If you tried other multilingual plugins, deactivate them before activating this plugin, otherwise, you may get unexpected results!

**Setup**

First, sign-up for <a href="https://www.motionpointexpress.com/">MotionPoint Express</a> and follow through the setup steps.

**Installation**

Install and activate the plugin as usual from the ‘Plugins’ menu in WordPress or by uploading the plugin-zip unzipped folder to the /wp-content/plugins directory.

== Screenshots ==

1. Plugin settings.
  
== Changelog ==
= 1.5 =
Release Date: October 10th, 2024

Enhancements:

* Minor fix in the admin area

= 1.4 =
Release Date: September 5th, 2024

Enhancements:

* Minor updates in the admin area
* Screenshot update

= 1.3 =
Release Date: September 3rd, 2024

Enhancements:

* Minor updates in the admin area

= 1.2 =
Release Date: June 6th, 2024

Enhancements:

* Minor updates in the admin area

= 1.1 =
Release Date: May 29th, 2024

Enhancements:

* Small tweaks in admin area

= 1.0 =
Release Date: March 22nd, 2024

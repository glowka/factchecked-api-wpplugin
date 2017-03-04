=== FactChecked API ===
Contributors: krzysztofmadejski
Tags: civic tech, fact checking, api, json api
Donate link: http://epf.org.pl/en
Requires at least: 4.0
Tested up to: 4.6.1
Stable tag: master
License: GPL v3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Implementation of to-be-standardized API for fact-checked statements. Publish data that can be used for example by browser plugins highlighting fact-checked statements. See http://bit.ly/factual-chrome for working example.

== Installation ==
1) Download and activate plugin
2) Run `composer install` in the plugin's directory
3) Implement iSite interface with custom code that maps your WP data to the standard data model (/schemas). See /sites for examples.
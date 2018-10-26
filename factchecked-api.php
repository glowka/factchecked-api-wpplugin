<?php
/* 
 * Plugin Name: FactChecked API
 * Plugin URI: http://transparencee.org
 * Version: 0.2.0
 * Description: Implementation of to-be-standardized API for fact-checked statements. Publish data that can be used for example by browser plugins highlighting fact-checked statemets. See http://bit.ly/factual-chrome for working example. Two implementations available at /wp-json/factchecked/wp/v1/sources_list and factchecked/jsonapi/v1/sources_list
 * Author: Krzysztof Madejski
 * License: GPLv3
*/

defined('ABSPATH') or die('No script kiddies please!');

require __DIR__ . '/api-wp.php';
require __DIR__ . '/api-jsonapi.php';
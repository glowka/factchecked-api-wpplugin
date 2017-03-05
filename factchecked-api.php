<?php
/* 
 * Plugin Name: FactChecked API
 * Plugin URI: http://transparencee.org
 * Version: 0.1.0
 * Description: Implementation of to-be-standardized API for fact-checked statements. Publish data that can be used for example by browser plugins highlighting fact-checked statemets. See http://bit.ly/factual-chrome for working example.
 * Author: Krzysztof Madejski
 * License: GPLv3
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

use \Neomerx\JsonApi\Encoder\Encoder;
use \Neomerx\JsonApi\Encoder\EncoderOptions;

require 'vendor/autoload.php';

foreach (glob(__DIR__ . '/schemas/*.php') as $file) {
    require $file;
}

require 'iSite.php';
require 'StatementPerPostSite.php';
foreach (glob(__DIR__ . '/sites/*.php') as $file) {
    require $file;
}

/*
 * Notes
 *
 *
 * Solution 1 - seperate database and update hooks ->
 *    https://codex.wordpress.org/Plugin_API - publish_post, save_post
 *    https://codex.wordpress.org/Creating_Tables_with_Plugins
 *
 * Solution 2 - traversing custom fields
 *
 * Use the WordPress "option" to store mapping for fields
 *
 * API examples:
 * * https://wordpress.org/plugins/json-api/other_notes/
 * https://plugins.svn.wordpress.org/json-api/trunk/singletons/api.php
 * * https://wordpress.org/plugins/twig-anything-api-endpoints/
 * https://github.com/jbrinley/WP-Router
 *
 */

// TODO warning jak wp_router nie wlaczony
function factchecked_api_check_for_router() {
    if (is_admin() && current_user_can( 'activate_plugins' ) && !is_plugin_active('wp-router/wp-router.php')) {
        ?>
    <div class="notice notice-error">
        <p>FactChecked API cannot work with <a href="/wp-admin/plugin-install.php?tab=search&s=wp+router">WP-Router plugin</a>!</p>
    </div>
    <?php
    }
}
add_action('admin_notices', 'factchecked_api_check_for_router');

add_action( 'wp_router_init', 'API::init');

/*
 * JSON API specs:
 *
 * https://github.com/tobscure/json-api
 * https://github.com/neomerx/json-api
 *
 */

class API {
    const API_URL = 'api/v1';
    const SITE_CLASS = 'Demagog';
    // TODO settings https://codex.wordpress.org/Function_Reference/register_setting

    static function output($data, $http_status = 200) {
        $http_status = apply_filters('json_api_http_status', $http_status);
        $charset = get_option('blog_charset');
        
        if (!headers_sent()) {
            status_header($http_status);
            header("Content-Type: application/json; charset=$charset", true);
        }

        $encoder = Encoder::instance([
            'SourceList' => '\SourceListSchema',
            'Statement' => '\StatementSchema',
        ], new EncoderOptions(JSON_PRETTY_PRINT, get_site_url(null, API::API_URL)));

        if ($data !== null) {
            echo $encoder->encodeData($data);
        }
	}

    public static function init() {
        add_action('wp_router_generate_routes', array(get_class(), 'register_routes'), 10, 1);
    }

    static function register_routes(WP_Router $router) {
        // TODO / versions
        // TODO /:version API home -> links
        $router->add_route( 'statements', array(
            'path' => '^' . API::API_URL . '/statements$',
            'query_vars' => array( ),
            'page_callback' => array(get_class(), 'statements'),
            'page_arguments' => array(),
            'access_callback' => true,
            'template' => FALSE
        ));
        $router->add_route( 'statement', array(
            'path' => '^' . API::API_URL . '/statements/(.+)$',
            'query_vars' => array( 'id' => 1),
            'page_callback' => array(get_class(), 'statement'),
            'page_arguments' => array('id'),
            'access_callback' => true,
            'template' => FALSE
        ));
        $router->add_route( 'sources_list', array(
            'path' => '^' . API::API_URL. '/sources_list$',
            'query_vars' => array( ),
            'page_callback' => array(get_class(), 'sources_list'),
            'page_arguments' => array( ),
            'access_callback' => true,
            'template' => FALSE
        ));
    }

    static function statement($id) {
        $siteclass = API::SITE_CLASS;
        $site = new $siteclass();

        $st = $site->get_statement($id);

        API::output($st);
    }

    static function statements() {
        $siteclass = API::SITE_CLASS;
        $site = new $siteclass();

        $statements = $site->get_statements($_GET['uri']);

//       $links = [ // TODO paging
//            Link::FIRST => new Link('/authors?page=1'),
//            Link::LAST  => new Link('/authors?page=4'),
//            Link::NEXT  => new Link('/authors?page=6'),
//            Link::LAST  => new Link('/authors?page=9'),
//        ];
//        $encoder->withLinks($links)->encodeData($authors);
        API::output($statements);
    }

    static function sources_list() {
        $siteclass = API::SITE_CLASS;
        $site = new $siteclass();

        API::output($site->get_sources_list());
    }
}

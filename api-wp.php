<?php
/**
 * A implementation using native Wordpress REST API
 */

namespace epforgpl\factchecked\wp;
const NS = 'epforgpl\factchecked\wp\\';

defined('ABSPATH') or die('No script kiddies please!');

// accessible at /wp-json/factchecked/v1/...
const API_NAMESPACE = 'factchecked/wp/v1';
const API_URL = 'wp-json/' . API_NAMESPACE;

require_once __DIR__ . '/model/Statement.php';

require_once __DIR__ . '/tools.php';
require_once __DIR__ . '/iSite.php';
require_once __DIR__ . '/StatementPerPostSite.php';
foreach (glob(__DIR__ . '/sites/*.php') as $file) {
    require_once $file;
}

/*
 * Notes
 *
 *
 * Solution 1 - seperate database and update hooks ->
 *    https://codex.wordpress.org/Plugin_API - publish_post, save_post
 *    https://codex.wordpress.org/Creating_Tables_with_Plugins
 *
 */

// init
add_action('rest_api_init', array(NS . 'API', 'init'));

class API
{
    static private $singleton = null;

    private $site;

    const SITE_CLASS = 'Demagog'; // TODO move to settings

// TODO settings https://codex.wordpress.org/Function_Reference/register_setting

    public static function init()
    {
        if (null === self::$singleton)
            self:: $singleton = new self;

        return self:: $singleton;
    }

    public function __construct()
    {
        $siteClass = self::SITE_CLASS;
        $this->site = new $siteClass();

        $this->registerRoutes();
    }

    public function registerRoutes()
    {
        // TODO / versions
        // TODO /:version API home -> links

        register_rest_route(API_NAMESPACE, '/statements', array(
            'methods' => 'GET',
            'callback' => array($this, 'statements'),
        ));

        register_rest_route(API_NAMESPACE, '/statements/(?P<id>\d+)$', array(
            'methods' => 'GET',
            'callback' => array($this, 'statement'),
        ));

        register_rest_route(API_NAMESPACE, '/sources_list', array(
            'methods' => 'GET',
            'callback' => array($this, 'sources_list')
        ));
    }

    function statement($request)
    {
        return $this->site->get_statement($request['id']);
    }

    function statements($request)
    {
        $statements = $this->site->get_statements($request['uri']);

//       $links = [ // TODO paging
//            Link::FIRST => new Link('/authors?page=1'),
//            Link::LAST  => new Link('/authors?page=4'),
//            Link::NEXT  => new Link('/authors?page=6'),
//            Link::LAST  => new Link('/authors?page=9'),
//        ];
//        $encoder->withLinks($links)->encodeData($authors);
        return $statements;
    }

    function sources_list()
    {
        // TODO repackage that in JSON-api or at least some schema
        // TODO adding controllers https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#the-controller-pattern
        return $this->site->get_sources_list();
    }
}



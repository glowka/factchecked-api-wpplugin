<?php
/**
 * A JSON-API compliant implementation using neomerx/json-api
 */

// TODO it is not finished

defined('ABSPATH') or die('No script kiddies please!');

use \Neomerx\JsonApi\Encoder\Encoder;
use \Neomerx\JsonApi\Encoder\EncoderOptions;

require_once __DIR__ . '/vendor/autoload.php';

foreach (glob(__DIR__ . '/schemas/*.php') as $file) {
    require_once $file;
}

require_once __DIR__ . '/tools.php';
require_once __DIR__ . '/iSite.php';
require_once __DIR__ . '/StatementPerPostSite.php';

foreach (glob(__DIR__ . '/sites/*.php') as $file) {
    require_once $file;
}

add_action('init', 'API::init');

class API
{
    static private $singleton = null;

    private $site;

    const API_URL = 'factchecked/jsonapi/v1';

    const SITE_CLASS = 'Demagog'; // TODO move to settings https://codex.wordpress.org/Function_Reference/register_setting

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

        add_filter('query_vars', array($this, 'add_query_vars'), 0);
        add_action('parse_request', array($this, 'sniff_requests'), 0);

        $this->registerRoutes();
    }

    public function add_query_vars($vars)
    {
        $vars[] = '__api';
        $vars[] = 'id';
        return $vars;
    }

    /**    Sniff Requests
     *    This is where we hijack all API requests
     *    If $_GET['__api'] is set, we kill WP and serve up API
     * @return die if API request
     */
    public function sniff_requests()
    {
        global $wp;
        if (isset($wp->query_vars['__api'])) {
            switch ($wp->query_vars['__api']) {
                // TODO this could be dynamic call followed by output - would be cleaner
                case 'statement':
                    $id = $wp->query_vars['id'];
                    $this->statement($id);
                    break;

                case 'statements':
                    $this->statements();
                    break;

                case 'sources_list':
                    $this->sources_list();
                    break;

                default:
                    // TODO error
                    exit(1);
            };

            exit;
        }
    }


    function registerRoutes()
    {
        // TODO / versions
        // TODO /:version API home -> links

        add_rewrite_rule('^' . self::API_URL . '/statements$', 'index.php?__api=statements', 'top');
        add_rewrite_rule('^' . self::API_URL . '/statements/(.+)$', 'index.php?__api=statement&id=$matches[1]', 'top');
        add_rewrite_rule('^' . self::API_URL . '/sources_list$', 'index.php?__api=sources_list', 'top');
    }

    function statement($id)
    {
        $st = $this->site->get_statement($id);

        $this->output($st);
    }

    function statements()
    {
        $statements = $this->site->get_statements(isset($_GET['uri']) ? $_GET['uri'] : null);

//       $links = [ // TODO paging
//            Link::FIRST => new Link('/authors?page=1'),
//            Link::LAST  => new Link('/authors?page=4'),
//            Link::NEXT  => new Link('/authors?page=6'),
//            Link::LAST  => new Link('/authors?page=9'),
//        ];
//        $encoder->withLinks($links)->encodeData($authors);
        $this->output($statements);
    }

    function sources_list()
    {
        $this->output($this->site->get_sources_list());
    }


    function output($data, $http_status = 200)
    {
        $http_status = apply_filters('json_api_http_status', $http_status);
        $charset = get_option('blog_charset');

        // set http error status
        if ($data instanceof WP_Error) {
            if (isset($data->get_error_data()['status'])) {
                $http_status = $data->get_error_data()['status'];

            } else {
                $http_status = 500;
            }
        }

        if (!headers_sent()) {
            status_header($http_status);
            header("Content-Type: application/json; charset=$charset", true);
        }

        if ($data instanceof WP_Error) {
            $meta = $data->get_error_data();
            unset($meta['status']);

            $error = new Neomerx\JsonApi\Document\Error(null, null,
                $http_status,
                $data->get_error_code(), // TODO handle multiple errors
                $data->get_error_message(),
                null, null, $meta);

            echo Encoder::instance()->encodeError($error);
            return;
        }

        $encoder = Encoder::instance([
            'SourceList' => '\SourceListSchema',
            'Statement' => '\StatementSchema'
        ], new EncoderOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES, get_site_url(null, API::API_URL)));

        if ($data !== null) {
            echo $encoder->encodeData($data);
        }
    }
}

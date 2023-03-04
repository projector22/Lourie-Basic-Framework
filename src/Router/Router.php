<?php

namespace LBF\Router;

use LBF\App\Config;
use LBF\Auth\Cookie;
use LBF\Config\AppMode;
use LBF\HTML\HTML;
use LBF\HTML\Injector\PagePositions;
use LBF\Layout\Layout;
use LBF\Router\Routes;

class Router {

    private readonly array $path;

    private readonly string $page;

    private readonly ?string $subpage;

    private readonly ?string $tab;

    public readonly Routes $route;

    public readonly HTTPMethod $http_method; 



    public function __construct() {
        if ( isset( Config::$payload->static_routes[$_SERVER['REDIRECT_URL']] ) ) {
            $this->path = Config::$payload->static_routes[$_SERVER['REDIRECT_URL']];
        } else {
            $this->path = array_values( 
                array_filter( 
                    // REDIRECT_URL is generate by apache2
                    explode( '/', ( $_SERVER['REDIRECT_URL'] ?? '' ) ), 
                    function($value) {
                        return trim($value) !== '';
                    }
                )
            );
        }

        $this->route = $this->determine_route();

        if ( $this->route == Routes::HTTP ) {
        }
        Config::load( [
            'http_method' => $this->http_method,
            'route'       => $this->route,
        ] );
    }


    public function route() {
        switch ( $this->route ) {
            case Routes::CLI:
                $this->execute_cli();
                break;
            case Routes::API:
                $this->execute_api();
                break;
            case Routes::HTTP:
                $this->render_webpage();
                break;
        }
    }



    public function render_webpage(): void {
        $injector = new HTML;
        $cookie = new Cookie;
        $layout = new Layout;

        $page = $this->page == 'home' ? 'index' : $this->page;

        $page_class = 'Web\\' . ucfirst( $page ) . 'Page';

        $page = new $page_class( $this->path );
        ob_start();
        $code = $page->construct_page();
        if ( $code == 200 ) {
            $html = ob_get_clean();
        }

        $cookie->inject_cookies( ( Config::$payload->ENVIRONMENT  ?? AppMode::DEVELOPEMENT ) !== AppMode::DEVELOPEMENT );

        $layout->init_header( 
            Config::$payload->meta['page_title'], 
            Config::$payload->meta['description'],
            Config::$payload->meta['site_language'],
            Config::$payload->meta['block_robots'],
        );
        $layout->set_favicon( Config::$payload->meta['favicon'] );
        $layout->append_to_header( $injector->insert_css( PagePositions::IN_HEAD ) );
        $layout->append_to_header( $injector->insert_js( PagePositions::IN_HEAD ) );
        $layout->render_header();

        Layout::append_to_body( $injector->insert_css( PagePositions::TOP_OF_PAGE ), true );
        Layout::append_to_body( $injector->insert_js( PagePositions::TOP_OF_PAGE ), true );
        Layout::append_to_body( '<main>' . $html . '</main>' );
        $layout->render_body();

        $layout->append_to_footer( $injector->insert_css( PagePositions::BOTTOM_OF_PAGE ) );
        $layout->append_to_footer( $injector->insert_js( PagePositions::BOTTOM_OF_PAGE ) );
        $layout->render_footer();
    }

    public function execute_api(): void {}
    public function execute_cli(): void {}


    public static function load_lrs_functions(): void {
        require __DIR__ . '/../Functions/functions.php';
    }


    private function determine_route(): Routes {
        if ( PHP_SAPI == 'cli' ) {
            return Routes::CLI;
        }

        $this->http_method = $this->determine_http_method();

        $this->page = $this->path[0] ?? 'index';
        $this->subpage = $this->path[1] ?? null;
        $this->tab = $this->path[2] ?? null;
        Config::load( [
            'current_page' => [
                'page'    => $this->page,
                'subpage' => $this->subpage,
                'tab'     => $this->tab,
            ],
        ] );


        if ( true == false ) {
            /**
             * @todo    Detect an API call. Header needs to be parsed
             */
            return Routes::API;
        }
        return Routes::HTTP;
    }


    private function determine_http_method(): HTTPMethod {
        if ( count( $_POST ) > 0 ) {
            return HTTPMethod::POST;
        } else {
            return match ( $_SERVER['REQUEST_METHOD'] ) {
                'GET'    => HTTPMethod::GET,
                'POST'   => HTTPMethod::POST,
                'PUT'    => HTTPMethod::PUT,
                'DELETE' => HTTPMethod::DELETE,
                default  => HTTPMethod::GET,
            };
        }
    }

    public function get_page(): string {
        return $this->page;
    }
    public function get_subpage(): string {
        return $this->subpage;
    }
    public function get_tab(): string {
        return $this->tab;
    }
}

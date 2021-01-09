<?php

namespace MiladRahimi\PhpRouter\Tests;

use MiladRahimi\PhpRouter\Exceptions\InvalidCallableException;
use MiladRahimi\PhpRouter\Exceptions\RouteNotFoundException;
use MiladRahimi\PhpRouter\Router;
use MiladRahimi\PhpRouter\Tests\Testing\SampleController;
use MiladRahimi\PhpRouter\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Laminas\Diactoros\ServerRequest;

class RoutingTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function test_injection_of_request_by_interface()
    {
        $router = $this->router();
        $router->get('/', function (ServerRequestInterface $r) {
            return $r->getMethod();
        });
        $router->dispatch();

        $this->assertEquals('GET', $this->output($router));
    }

    /**
     * @throws Throwable
     */
    public function test_injection_of_request_by_type()
    {
        $router = $this->router();
        $router->get('/', function (ServerRequest $r) {
            return $r->getMethod();
        });
        $router->dispatch();

        $this->assertEquals('GET', $this->output($router));
    }

    /**
     * @throws Throwable
     */
    public function test_injection_of_default_value()
    {
        $router = $this->router();
        $router->get('/', function ($default = "Default") {
            return $default;
        });
        $router->dispatch();

        $this->assertEquals('Default', $this->output($router));
    }

    /**
     * @throws Throwable
     */
    public function test_default_publisher()
    {
        ob_start();

        $router = Router::create();

        $router->get('/', function () {
            return 'home';
        });
        $router->dispatch();

        $this->assertEquals('home', ob_get_contents());

        ob_end_clean();
    }

    /**
     * @throws Throwable
     */
    public function test_with_fully_namespaced_controller()
    {
        $router = $this->router();
        $router->get('/', [SampleController::class, 'home']);
        $router->dispatch();

        $this->assertEquals('Home', $this->output($router));
    }

    /**
     * @throws Throwable
     */
    public function test_not_found_error()
    {
        $this->mockRequest('GET', 'http://example.com/unknowon');

        $this->expectException(RouteNotFoundException::class);

        $router = $this->router();
        $router->get('/', $this->OkController());
        $router->dispatch();
    }

    /**
     * @throws Throwable
     */
    public function test_with_class_method_but_invalid_controller_class()
    {
        $this->expectException(InvalidCallableException::class);

        $router = $this->router();
        $router->get('/', 'UnknownController@method');
        $router->dispatch();
    }

    /**
     * @throws Throwable
     */
    public function test_with_class_but_invalid_method()
    {
        $this->expectException(InvalidCallableException::class);

        $router = $this->router();
        $router->get('/', SampleController::class . '@invalid');
        $router->dispatch();
    }

    /**
     * @throws Throwable
     */
    public function test_with_invalid_controller_class()
    {
        $this->expectException(InvalidCallableException::class);

        $router = $this->router();
        $router->get('/', 666);
        $router->dispatch();
    }

    /**
     * @throws Throwable
     */
    public function test_current_route()
    {
        $router = $this->router();
        $router->get('/', function (Route $r) {
            return join(',', [
                $r->getName(),
                $r->getPath(),
                $r->getUri(),
                $r->getParameters(),
                $r->getMethod(),
                count($r->getMiddleware()),
                $r->getDomain() ?? '-',
            ]);
        }, 'home');
        $router->dispatch();

        $value = join(',', ['home', '/', '/', [], 'GET', 0, '-']);
        $this->assertEquals($value, $this->output($router));
    }
}

# Web Server

Kuiper starts the web server by default, and http requests are processed using the [Slim](https://www.slimframework.com/) framework.

## Installation

```bash
composer require kuiper/web:^0.8
```

If you use the TWIG template engine, you need to install:
```bash
composer require twig/twig
```

## Routing

We recommend configuring routes using annotations. During the DI namespace scan, all control classes marked with `kuiper\di\attribute\Controller` are logged, and `kuiper\web\AttributeProcessor` handles controller classes under the specified namespace according to the `application.web.namespace` configuration. Methods marked by the controller with the `RequestMapping` annotation are added to the Slim app as routing methods. For example: 

```php
<?php

namespace app\controller;

use kuiper\di\attribute\Controller;
use kuiper\web\AbstractController;
use kuiper\web\attribute\GetMapping;

#[Controller]
class IndexController extends AbstractController
{
    #[GetMapping("/")]
    public function index(): void
    {
        $this->getResponse()->getBody()->write("<h1>it works!</h1> n");
    }
}
```

`RequestMapping` can use the `method` attribute to set the http method of the request, usually we can use `GetMapping` and `PostMapping` to specify the request method. The following is an example of annotation and corresponding code route registration:

```php
#[GetMapping("/books/{id}")]               // $app->get("/books/{id}", 'Controller:method')
#[PostMapping("/books")]                   // $app->post("/books", 'Controller:method')
#[PutMapping("/books/{id}")]               // $app->put("/books/{id}", 'Controller:method')
#[DeleteMapping("/books/{id}")]            // $app->delete("/books/{id}", 'Controller:method')
#[OptionsMapping("/books/{id}")]           // $app->options("/books/{id}", 'Controller:method')
#[PatchMapping("/books/{id}")]             // $app->patch("/books/{id}", 'Controller:method')
#[RequestMapping("/books/{id}")]           // $app->any("/books/{id}", 'Controller:method')
#[RequestMapping("/books", method={"GET", "POST"})]   // $app->map(["GET", "POST"], "/books", 'Controller:method')
#[GetMapping({"/page1", "/page2"})]        // $app->get("/page1", 'Controller:method'); $app->get("/page2", 'Controller:method'); 
```

The placeholder on the route can be obtained via controller parameters:

```php
<?php

#[Controller]
class BookController extends AbstractController
{
    #[GetMapping("/books/{id}")]
    public function getBook(string $bookId): void
    {
    }
}
```

The route name can be set by the name of the annotation:

```php
<?php

use kuiper\web\ControllerTrait;

#[Controller]
class BookController extends AbstractController
{
    use ControllerTrait;
    
    #[GetMapping("/hello/{name}", name: "hello")]
    public function hello(string $name): void
    {
         echo $this->uriFor('hello', ['name' => 'Josh'], ['query1' => 'value']);
    }
}
```

The configuration item `application.web.context_url` is used to configure the global routing prefix, which can be uniformly added with the URI prefix.

## Controller

In the controller, PSR-7 ServerRequestInterface and ResponseInterface objects can be accessed using `$this->request` and `$this-> response`.
The controller method can return a new Response object, or you can set the response body using `$this->response->getBody()->write($content)`, in which case void can be returned.

The controller `initialize` method is executed before the method call corresponding to the route, and if this method returns a ResponseInterface, it will terminate the call to the routed method. Can be used as a request pre-check for controllers.

`kuiperwebControllerTrait` provides some common methods:
- `json($data)` outputs the JSON response
- `redirect($url)` jumps to the URL
- `urlFor($routeName, $data, $query)` generates the URL
- `fullUrlFor($routeName, $data, $query)` generates a URL with a domain name
- `getSession()` gets the Session object
- `getFlash()` gets the Flash object

## View

There are two view templates available in `kuiper\web\WebConfiguration`: php and [twig](https://twig.symfony.com/).
When you install the `twig/twig` package using composer in your project, the twig template is automatically enabled. If you need to force the use of PHP templates, you can set configuration items 
`application.web.view.engine` is `php`.

PHP template configuration options:
```php
[
    `application' => [
        'web' => [
            'view' => [
                'engine' => 'php',
                'path' => '{application.base_path}/resources/view',
                'extension' => '.php'
            ]
        ]
    ]
]
```

- path is required, set the template directory path
- extension Optional, set the template file extension (calling the '$view->render('page')' function does not need to specify the template file extension)

TWIG template configuration options:

```php
[
    'application' => [
        'web' => [
            'view' => [
                'engine' => 'twig',
                'path' => '{application.base_path}/resources/view',
                'alias' => [
                    'alias1' => 'path/to/template'
                ],
                'extension' => '.twig',
                'globals' => [
                    'var1' => 'value1'
                ],
            ]
        ]
    ]
]
```

- path sets the template directory path
- extension Sets the template file name extension
- alias is used to set the alias of other template directories, you can call `$view->render('@alias1/file')` to use the template in the directory corresponding to the alias
- globals is used to set variables that can be used in the template file

For other configuration options such as debug, cache, etc., refer to the `TwigEnvironment` constructor documentation.

`kuiperwebControllerViewTrait` provides methods related to view rendering, such as `render`, `renderAsString`, etc.


## Middleware

The `application.web.middleware` configuration item can set global middleware. For example:

```php
[
    'application' => [
        'web' => [
            'middleware' => [
                \Slim\Middleware\MethodOverrideMiddleware::class,
                \Slim\Middleware\BodyParsingMiddleware::class
            ]
        ]
    ]
]
```

If you need to add middleware to the Controller or a route, you can use annotations to implement it. For example:

```php
<?php

use kuiper\web\attribute\CsrfToken;

#[Controller]
class BookController extends AbstractController
{
    use ControllerTrait;
    
    #[PostMapping("/books")]
    #[CsrfToken]
    public function createBook(): void
    {
    }
}
```

## Session

Since the Swoole service is a resident memory process, PHP sessions are no longer applicable. Session data needs to be manipulated by using the `kuiper\web\session\SessionInterface` interface.

The session uses the PSR-6 `CacheItemPoolInterface` object store. Session configuration items:

```php
[
    'application' => [
        'web' => [
            'session' => [
                'prefix' => 'session_',
                'cookie_name' => 'PHPSESSIONID',
                'cookie_lifetime' => 1800,
                'auto_start' => true,
            ]
        ]
    ]
]
```

- prefix: Cache key prefix
- cookie_name: The cookie name
- cookie_lifetime: Cookie expiry
- auto_start: Whether to automatically open the session

Session enablement must add `kuiper\web\middleware\Session` middleware in `application.web.middleware`.

Example of reading session data in the controller:

```php
<?php

#[Controller]
class BookController extends AbstractController
{
    use ControllerTrait;
    
    #[PostMapping("/books")]
    public function createBook(): void
    {
        $this->getSession()->set('foo', 'bar');
        $this->getSession()->get('foo');
    }
}
```

## Authentication

User authentication is possible after the session is opened. First we need to create the user object:

```php
<?php

use kuiper\web\security\UserIdentity;

class User implements UserIdentity
{
    /**
     * @var string
     */
    private $username;
    /**
     * @var array
     */
    private $authorities;

    public function __construct(string $username, array $authorities)
    {
        $this->username = $username;
        $this->authorities = $authorities;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getAuthorities(): array
    {
        return $this->authorities;
    }
}
```

In the controller, we can set the current user via `kuiper\web\security\SecurityContext::setIdentity()`, for example

```php
<?php

use kuiper\web\security\SecurityContext;

#[Controller]
class LoginController extends AbstractController
{
    use ControllerTrait;
    
    #[PostMapping("/login")]
    public function login(): void
    {
        $params = $this->request->getParsedBody();
        if ($this->passwordMatches($params['username'], $params['password'])) {
            SecurityContext::setIdentity(new User($params['username'], []));
        }
    }
    
    #[GetMapping("/profile")]
    public function profile(): void
    {
        $user = SecurityContext::getIdentity();
        if ($user === null) {
            throw new HttpUnauthorizedException($this->request);
        }
        $this->response->getBody()->write("Hello, " . $user->getUsername());
    }
}
```
## Authorization

Kuiper provides simple role-based permission judgment. A permission is made up of `{resource}:{action}`, e.g. `blog:view` 
is permission to view blogs. Access is set via the `kuiper\web\attribute\PreAuthorize` annotation
The permissions of the method user. For example:

```php
<?php

use kuiper\di\attribute\Controller;
use kuiper\web\AbstractController;
use kuiper\web\attribute\PreAuthorize;
use kuiper\web\attribute\GetMapping;

#[Controller]
class BlogController extends AbstractController
{
    #[GetMapping("/home")]
    #[PreAuthorize("blog:view")]
    public function home(): void
    {
    }
}
```

`kuiper\web\security\UserIdentity::getAuthorities` returns a list of user roles. This list of roles
It can be a role name, such as `blog_admin`, or a specific permission, such as `blog:view`. If it is a role name,
You must establish an association between the role name and the permission, which is created by `kuiper\web\security\Acl::allow($role, $authority)`.
For example:

```php
<?php

use kuiper\web\security\Acl;

$containerBuilder->addDefinitions([
    AclInterface::class => factory(function() {
        $acl = new Acl();
        $acl->allow('blog_admin', 'blog:*');
        return $acl;
    })
]);
```

If the user permissions do not match the permissions declared by the routing method, a `Slim\Exception\HttpSpecializedException` exception is thrown.

## Error handling

On top of [Slim error handling](https://www.slimframework.com/docs/v4/middleware/error-handling.html), add some procedures to simplify error handling.
We can control the default error log behavior with the following configuration:

```php
[
    'application' => [
        'web' => [
            'error' => [
                'display_error' => true,
                'log_error' => true,
                'include_stacktrace' => 'always',
                'handlers' => [
                    FooException::class => FooExceptionHandler::class
                ]
            ]
        ]
    ]
]
```

- display_error: Whether the page displays detailed error information, default false
- log_error: Whether to log errors to the log, default true
- include_stacktrace: Whether stack information is logged when logging, optional values never, always, on_trace_param (log when there is a trace parameter in the request), default never
- handlers: Sets the handling class that specifies the exception

The configuration `application.web.error.handlers` can be configured to specify the exception handling class, or in the project via the `kuiper\web\annotation\ErrorHandler` annotation
Mark an exception handling class, such as:

```php
<?php

use kuiper\web\handler\AbstractErrorHandler;
use kuiper\web\attribute\ErrorHandler;

#[ErrorHandler(FooException::class)] 
class FooExceptionHandler extends AbstractErrorHandler
{
}
```

Error display is handled by the `Slim\Interfaces\ErrorRendererInterface` class, and the error output can be customized by overriding the corresponding class.
For example, in the container, configure:

```php
$containerBuilder->addDefinitions([
    \Slim\Error\Renderers\JsonErrorRenderer::class => autowire(MyJsonErrorRenderer::class)
]);
```

## Configuration items

| Configuration Item | Environment variables | Description |
|------------------------------|------------------------------|------------------------------|
| web.log_file                 | WEB_LOG_FILE                 | Access log file name, default to access.log |
| web.log_post_body            | WEB_LOG_POST_BODY            | Whether the access log records the POST request body |
| web.log_sample_rate          | WEB_LOG_SAMPLE_RATE          | Log sampling rate, default is 1, set to 0 Do not log |
| web.middleware               |                              | Web Middleware |
| web.health_check_enabled     | WEB_HEALTH_CHECK_ENABLED     | Whether to enable health check routing |
| web.namespace                | WEB_NAMESPACE                | Web routing rules scan namespace |
| web.context_url              | WEB_CONTEXT_URL              | Route URL prefix |
| web.error.display            | WEB_ERROR_DISPLAY            | Whether to display error details | on the page when an error occurs
| web.error.logging            | WEB_ERROR_LOGGING            | Whether to write error information to the log when an error occurs |
| web.error.include_stacktrace | WEB_ERROR_INCLUDE_STACKTRACE | Whether to display error stack information | on error
| web.error.handlers           |                              | Set the exception error handler |
| web.view.engine              | WEB_VIEW_ENGINE              | Set the View Engine type, which currently supports TWIG and PHP |
| web.view.path                | WEB_VIEW_PATH                | Set Template Page Directory |
| web.session.enabled          | WEB_SESSION_ENABLED          | Whether session | is enabled
| web.session.prefix           | WEB_SESSION_PREFIX           | Set the session storage key prefix |
| web.session.cookie_name      | WEB_SESSION_COOKIE_NAME      | Set the session cookie name |
| web.session.cookie_lifetime  | WEB_SESSION_COOKIE_LIFETIME  | Set session cookie expiration |

Next: [RPC](rpc.md)

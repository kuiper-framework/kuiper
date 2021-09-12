# Web Server

Kuiper 默认启动了 Web Server，http 请求使用 [Slim](https://www.slimframework.com/) 框架进行处理。

## 安装

```bash
composer require kuiper/web
```

如果使用 twig 模板引擎，需要安装：
```bash
composer require twig/tiwg
```

## 路由

和 Slim 框架中使用 php 代码配置路由不同，Kuiper 中通过注解配置路由。

```php
<?php

namespace app\controller;

use kuiper\di\annotation\Controller;
use kuiper\web\AbstractController;
use kuiper\web\annotation\GetMapping;

/**
 * @Controller
 */
class IndexController extends AbstractController
{
    /**
     * @GetMapping("/")
     */
    public function index(): void
    {
        $this->getResponse()->getBody()->write("<h1>it works!</h1>\n");
    }
}
```

`@Controller` 标记的类会扫描 `@RequestMapping` 注解。`@RequestMapping` 可以使用 `method` 属性设置
请求的 http 方法，可以使用 `@GetMapping` , `@PostMapping` 等 指定。以下是注解和对应的代码路由注册示例：
```php
@GetMapping("/books/{id}")               // $app->get("/books/{id}", 'Controller:method')
@PostMapping("/books")                   // $app->post("/books", 'Controller:method')
@PutMapping("/books/{id}")               // $app->put("/books/{id}", 'Controller:method')
@DeleteMapping("/books/{id}")            // $app->delete("/books/{id}", 'Controller:method')
@OptionsMapping("/books/{id}")           // $app->options("/books/{id}", 'Controller:method')
@PatchMapping("/books/{id}")             // $app->patch("/books/{id}", 'Controller:method')
@RequestMapping("/books/{id}")           // $app->any("/books/{id}", 'Controller:method')
@RequestMapping("/books", method={"GET", "POST"})   // $app->map(["GET", "POST"], "/books", 'Controller:method')
@GetMapping({"/page1", "/page2"})        // $app->get("/page1", 'Controller:method'); $app->get("/page2", 'Controller:method'); 
```

路由上的 placeholder 可以通过控制器参数获取：

```php
<?php

/**
 * @Controller
 */
class BookController extends AbstractController
{
    /**
     * @GetMapping("/books/{id}")
     */
    public function getBook(string $bookId): void
    {
    }
}
```

路由名字可以通过注解的 name 设置：

```php
<?php

use kuiper\web\ControllerTrait;

/**
 * @Controller
 */
class BookController extends AbstractController
{
    use ControllerTrait;
    
    /**
     * @GetMapping("/hello/{name}", name="hello")
     */
    public function hello(string $name): void
    {
         echo $this->uriFor('hello', ['name' => 'Josh'], ['example' => 'name']);
    }
}
```

配置项 `application.web.context_url` 用于配置全局的路由前缀，可以统一加上 URI 前缀。

## 控制器

在控制器中，可以使用 `$this->request` 和 `$this->response` 访问 PSR-7 ServerRequestInterface 和 ResponseInterface 对象。
控制器方法可以返回新的 Response 对象，也可以使用 `$this->response->getBody()->write($content)` 设置响应体，此时可以返回 void。

控制器 `initialize` 方法会在路由对应的方法调用前执行，这个方法如果返回 ResponseInterface，则会终止调用路由方法。可以作为控制器的请求前置检查使用。

`kuiper\web\ControllerTrait` 中提供一些常用的方法：
- `json($data)` 输出 json 响应
- `redirect($url)` 跳转到 url
- `urlFor($routeName, $data, $query)` 生成URL
- `fullUrlFor($routeName, $data, $query)` 生成带域名的URL
- `getSession()` 获取 Session 对象
- `getFlash()` 获取 Flash 对象

## 视图

在 `\kuiper\web\WebConfiguration` 中提供两种视图模板 php 和 [twig](https://twig.symfony.com/)。
当项目中使用 composer 安装 `twig/twig` 包后会自动启用 twig 模板。如果需要强制使用 php 模板，可以设置配置项 
`application.web.view.engine` 为 `php` 。

php 模板配置选项：
```php
[
    'application' => [
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

- path 必选，设置模板目录路径
- extension 可选，设置模板文件扩展名（调用 `$view->render('page')` 函数不需要指定模板文件扩展）

twig 模板配置选项：

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

- path 设置模板目录路径
- extension 设置模板文件扩展名
- alias 用于设置其他模板目录的别名，可以调用 `$view->render('@alias1/file')` 使用别名对应目录下的模板
- globals 用于设置模板文件中可以使用的变量

其他配置选项如 debug, cache 等参考 `\Twig\Environment` 构造函数文档。

`\kuiper\web\ControllerViewTrait` 提供视图渲染相关的方法例如 `render`, `renderAsString` 等。

## 中间件

`application.web.middleware` 配置项可以设置全局中间件。例如：

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

需要对 Controller 或者某个路由添加中间件，可以使用注解实现。例如：

```php
<?php

use kuiper\web\annotation\filter\CsrfToken;

/**
 * @Controller
 */
class BookController extends AbstractController
{
    use ControllerTrait;
    
    /**
     * @PostMapping("/books")
     * @CsrfToken
     */
    public function createBook(): void
    {
    }
}
```

## 会话

会话使用 PSR-6 CacheItemPoolInterface 对象存储。会话配置项：

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

- prefix: 缓存 key 前缀
- cookie_name: cookie 名字
- cookie_lifetime: cookie 有效期
- auto_start: 是否自动开启 session

会话启用必须在 `application.web.middleware` 中添加 `\kuiper\web\middleware\Session` 中间件。

在控制器中读取会话数据示例：

```php
<?php

/**
 * @Controller
 */
class BookController extends AbstractController
{
    use ControllerTrait;
    
    /**
     * @PostMapping("/books")
     */
    public function createBook(): void
    {
        $this->getSession()->set('foo', 'bar');
        $this->getSession()->get('foo');
    }
}
```

## 认证

在开启会话后可以进行用户认证。首先我们需要创建用户对象：

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

在控制器中，我们可以通过 `\kuiper\web\security\SecurityContext::setIdentity()` 设置当前用户，例如

```php
<?php

use kuiper\web\security\SecurityContext;

/**
 * @Controller
 */
class LoginController extends AbstractController
{
    use ControllerTrait;
    
    /**
     * @PostMapping("/login")
     */
    public function login(): void
    {
        $params = $this->request->getParsedBody();
        if ($this->passwordMatches($params['username'], $params['password'])) {
            SecurityContext::setIdentity(new User($params['username'], []));
        }
    }
    
    /**
     * @GetMapping("/profile")
     */
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

## 授权

Kuiper 提供简单的基于角色的权限判断。一个权限是由 `{resource}:{action}` 构成，例如 `blog:view` 
是查看 blog 的权限。 通过 `@\kuiper\web\annotation\filter\PreAuthorize` 注解设置访问
该方法用户的权限。例如：

```php
<?php

use kuiper\di\annotation\Controller;
use kuiper\web\AbstractController;
use kuiper\web\annotation\filter\PreAuthorize;
use kuiper\web\annotation\GetMapping;

/**
 * @Controller()
 */
class BlogController extends AbstractController
{
    /**
     * @GetMapping("/home")
     * @PreAuthorize("blog:view")
     */
    public function home(): void
    {
    }
}
```

`kuiper\web\security\UserIdentity::getAuthorities` 返回用户角色列表。这个角色列表
可以是一个角色名，例如 `blog_admin`，也可以是某个具体的权限，例如 `blog:view`。如果是角色名，
则必须建立角色名和权限的关联关系，这个关系是通过 `\kuiper\web\security\Acl::allow($role, $authority)` 创建。
例如：

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

如果用户权限不匹配路由方法声明的权限，则会抛出 `\Slim\Exception\HttpSpecializedException` 异常。

## 错误处理

在 [Slim 错误处理基础上](https://www.slimframework.com/docs/v4/middleware/error-handling.html)，加入一些简化错误处理的过程。
我们可以通过以下配置控制默认错误日志行为：

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

- display_error: 页面是否显示详细错误信息，默认 false
- log_error: 是否记录错误到日志，默认 true
- include_stacktrace: 日志记录时是否记录堆栈信息，可选值 never(不记录), always(记录), on_trace_param(在请求中有trace参数时记录)，默认 never
- handlers: 设置指定异常的处理类

配置中 `application.web.error.handlers` 可以配置指定异常的处理类，在项目中也可以通过 `@\kuiper\web\annotation\ErrorHandler` 注解
标记异常处理类，例如：

```php
<?php

use kuiper\web\handler\AbstractErrorHandler;
use kuiper\web\annotation\ErrorHandler;

/**
 * @ErrorHandler(FooException::class)
 */
class FooExceptionHandler extends AbstractErrorHandler
{
}
```

错误显示通过 `\Slim\Interfaces\ErrorRendererInterface` 类处理，通过覆盖对应的类实现可以自定义错误输出。
例如在容器中配置:

```php
$containerBuilder->addDefinitions([
    \Slim\Error\Renderers\JsonErrorRenderer::class => autowire(MyJsonErrorRenderer::class)
]);
```

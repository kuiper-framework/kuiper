# Kuiper Framework

## 简介

Kuiper 希望能像 Spring Boot 一样简化 PHP 应用开发，通过简单的配置甚至零配置就能启动服务。

## 特性

目前 PHP 框架不断推陈出新，与已经发展很多年的成熟框架（例如 Laravel、Yii、Hyperf ）相比较，Kuiper 框架有哪些特性呢？

首先 Kuiper 框架关注易用性，在了解基本概念之后可以快速上手。Kuiper 框架基于 [PSR 标准](https://www.php-fig.org/psr)实现，如果应用代码已经基于 PSR 组件接口开发，基本上可以不需要修改代码就可以使用 Kuiper 框架。

其次 Kuiper 框架提供一套完整的 rpc 支持库，支持包括 JsonRPC, Tars 等多种 RPC 协议，可以快速开发一个 RPC 服务。

Kuiper 框架中大量使用了 PHP 8 Attribute，可以让配置更加简洁。

## 服务器要求

Kuiper 0.8 版本支持 PHP 8.1 以上版本，swoole PHP 扩展 >=4.5，以及以下扩展：
- JSON PHP 扩展
- Pcntl PHP 扩展
- PDO 扩展(如果需要使用 db 组件)
- Redis 扩展(如果需要使用 redis 缓存)

接下来，通过一些[示例](tutorial.md)让我们熟悉 Kuiper 框架。

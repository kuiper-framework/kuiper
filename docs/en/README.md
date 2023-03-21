# Kuiper Framework

## Introduction

Kuiper wanted to simplify PHP application development like Spring Boot, and start services with simple or even zero configuration.

## Features

What are the features of the Kuiper framework compared to mature frameworks that have been developed for many years (e.g. Laravel, Yii, Hyperf)?

First of all, the Kuiper framework focuses on ease of use, and you can quickly get started after understanding the basic concepts. The Kuiper framework is based on the [PSR standard](https://www.php-fig.org/psr) implementation, if the application code has been developed based on the PSR component interface, you can basically use the Kuiper framework without modifying the code.

Secondly, the Kuiper framework provides a complete set of RPC support libraries, supporting a variety of RPC protocols including JsonRPC, Tars, etc., which can quickly develop an RPC service.

The Kuiper framework makes extensive use of PHP 8 Attribute, which can make configuration more concise.

## Server requirements

Kuiper version 0.8 requires PHP 8.1 and above, swoole PHP extension >=4.5, and the following extensions:
- JSON PHP extension
- Pcntl PHP extension
- PDO extension (if db component required)
- Redis extensions (if Redis cache is required)

Next, let's start to learning Kuiper framework with some [examples](tutorial.md).

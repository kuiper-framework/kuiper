<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use kuiper\web\TestCase;
use Nyholm\Psr7\Uri;

class MessageUtilTest extends TestCase
{
    public function testExtractUri()
    {
        $server = [
            'CONTENT_LENGTH' => '',
            'CONTENT_TYPE' => '',
            'DOCUMENT_ROOT' => '/var/www/html',
            'DOCUMENT_URI' => '/test.php',
            'HOSTNAME' => 'erp-web-0',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br',
            'HTTP_ACCEPT_LANGUAGE' => 'en,zh-CN;q=0.9,zh;q=0.8',
            'HTTP_CACHE_CONTROL' => 'max-age=0',
            'HTTP_COOKIE' => '_ga=GA1.2.249122248.1680165958; PHPSESSID=0935d3004aeba6cc9f5e8713b1496156; _gid=GA1.2.1014701418.1682480220',
            'HTTP_HOST' => 'ying.pre.banmahui.cn',
            'HTTP_REMOTEIP' => '111.199.82.67',
            'HTTP_SEC_CH_UA' => '"Chromium";v="112", "Google Chrome";v="112", "Not:A-Brand";v="99"',
            'HTTP_SEC_CH_UA_MOBILE' => '?0',
            'HTTP_SEC_CH_UA_PLATFORM' => '"macOS"',
            'HTTP_SEC_FETCH_DEST' => 'document',
            'HTTP_SEC_FETCH_MODE' => 'navigate',
            'HTTP_SEC_FETCH_SITE' => 'none',
            'HTTP_SEC_FETCH_USER' => '?1',
            'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
            'HTTP_X_FORWARDED_FOR' => '111.199.82.67, 172.16.1.50',
            'HTTP_X_FORWARDED_HOST' => 'ying.pre.banmahui.cn',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_REAL_IP' => '172.16.1.50',
            'PATH_INFO' => '/',
            'QUERY_STRING' => '',
            'REDIRECT_STATUS' => '200',
            'REMOTE_ADDR' => '10.141.3.86',
            'REMOTE_PORT' => '52000',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_SCHEME' => 'http',
            'REQUEST_TIME' => 1682517654,
            'REQUEST_TIME_FLOAT' => 1682517654.8911581,
            'REQUEST_URI' => '/test.php',
            'SCRIPT_FILENAME' => '/var/www/html/test.php',
            'SCRIPT_NAME' => '/test.php',
            'SERVER_ADDR' => '10.141.10.203',
            'SERVER_NAME' => '_',
            'SERVER_PORT' => '80',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'nginx/1.21.3',
        ];
        $uri = MessageUtil::extractUri(new Uri(), $server);
        error_log(var_export($uri, true));
        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals(null, $uri->getPort());
    }
}

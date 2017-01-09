<?php
namespace kuiper\web\session;

use Dflydev\FigCookies\SetCookies;
use kuiper\cache\driver\Memory;
use kuiper\cache\Pool;
use kuiper\web\session\CacheSessionHandler;
use kuiper\web\session\ManagedSession;
use kuiper\web\TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class ManagedSessionTest extends TestCase
{
    const SID = "447a490aa4b2ccc0e17a06e43baf983c22a3c017b6d40e08";
    
    public function createSession()
    {
        $cache = new Pool(new Memory());
        $handler = new CacheSessionHandler($cache);
        return $session = new ManagedSession($handler, [
            'cookie_name' => 'SID'
        ]);
    }

    public function createRequest()
    {
        return ServerRequestFactory::fromGlobals(
            null, null, null, [
                'SID' => self::SID
            ], null
        );
    }

    public function testStart()
    {
        $session = $this->createSession();
        $session->setRequest($this->createRequest());
        $session->start();
        $this->assertNull($session->nick);
    }

    public function testStartWithData()
    {
        $session = $this->createSession();
        $cache = $this->readAttribute($this->readAttribute($session, 'handler'), 'cache');
        $item = $cache->getItem('session:'.self::SID);
        $cache->save($item->set(['nick' => 'kuiper']));
        $session->setRequest($this->createRequest());
        $session->start();
        $this->assertEquals('kuiper', $session->nick);
    }

    public function testSetSession()
    {
        $session = $this->createSession();
        $session->setRequest(ServerRequestFactory::fromGlobals());
        $session->start();
        $session->nick = 'john';
        $response = $session->respond(new Response);
        // print_r($response->getHeaders());
        $cookies = SetCookies::fromResponse($response);
        $cookie = $cookies->get('SID');
        // print_r($cookie);
        $cache = $this->readAttribute($this->readAttribute($session, 'handler'), 'cache');
        // print_r($cache);
        // print_r($response);
        $item = $cache->getItem('session:'.$cookie->getValue());
        // print_r($item);
        $this->assertTrue($item->isHit());
        $this->assertEquals(['nick' => 'john'], $item->get());
    }
}

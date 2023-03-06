<?php

declare(strict_types=1);

namespace kuiper\tracing;

use PHPUnit\Framework\TestCase;

class ScopeTest extends TestCase
{
    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeManager;

    /**
     * @var Span|\PHPUnit\Framework\MockObject\MockObject
     */
    private $span;

    public function setUp(): void
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->span = $this->createMock(Span::class);
    }

    public function testCloseDoNotFinishSpanOnClose()
    {
        $scope = new Scope($this->scopeManager, $this->span, false);

        $this->scopeManager->method('getActive')->willReturn($scope);
        $this->scopeManager->expects($this->once())->method('getActive');
        $this->span->expects($this->never())->method('finish');
        $this->scopeManager->expects($this->once())->method('setActive');

        $scope->close();
    }

    public function testCloseFinishSpanOnClose()
    {
        $scope = new Scope($this->scopeManager, $this->span, true);

        $this->scopeManager->method('getActive')->willReturn($scope);
        $this->scopeManager->expects($this->once())->method('getActive');
        $this->span->expects($this->once())->method('finish');
        $this->scopeManager->expects($this->once())->method('setActive');

        $scope->close();
    }

    public function testGetSpan()
    {
        $scope = new Scope($this->scopeManager, $this->span, false);

        $this->assertEquals($this->span, $scope->getSpan());
    }
}

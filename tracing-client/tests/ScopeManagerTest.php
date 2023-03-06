<?php

declare(strict_types=1);

namespace kuiper\tracing;

use PHPUnit\Framework\TestCase;

class ScopeManagerTest extends TestCase
{
    /**
     * @var ScopeManager
     */
    private $scopeManager;

    public function setUp(): void
    {
        $this->scopeManager = new ScopeManager();
    }

    public function testActivate()
    {
        $span = $this->createMock(Span::class);

        $scope = $this->scopeManager->activate($span, true);

        $this->assertEquals($scope->getSpan(), $span);
    }

    public function testAbleGetActiveScope()
    {
        $span = $this->createMock(Span::class);

        $this->assertNull($this->scopeManager->getActive());
        $scope = $this->scopeManager->activate($span, false);

        $this->assertEquals($scope, $this->scopeManager->getActive());
    }

    public function testScopeClosingDeactivates()
    {
        $span = $this->createMock(Span::class);

        $scope = $this->scopeManager->activate($span, false);
        $scope->close();

        $this->assertNull($this->scopeManager->getActive());
    }
}

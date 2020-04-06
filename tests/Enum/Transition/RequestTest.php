<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests\Enum\Transition;

use Aeviiq\FormFlow\Enum\Transition\Request;
use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Aeviiq\FormFlow\FormFlowInterface;
use Aeviiq\FormFlow\Step\StepCollection;
use Aeviiq\FormFlow\Step\StepInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

final class RequestTest extends TestCase
{
    public function testCreateByHttpRequestAndFlowWithForwardTransition(): void
    {
        $flow = $this->createFlow(1);
        $httpRequest = $this->createHttpRequest('forwards');

        $request = Request::createByHttpRequestAndFlow($httpRequest, $flow);
        self::assertSame(2, $request->getRequestedStepNumber());
        self::assertSame(Request::FORWARDS, $request->getValue());
    }

    public function testCreateByHttpRequestAndFlowWithInvalidForwardTransition(): void
    {
        $flow = $this->createFlow(0);
        $httpRequest = $this->createHttpRequest('forwards');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A requested step number must be above 1 when going forwards. "1" given.');
        Request::createByHttpRequestAndFlow($httpRequest, $flow);
    }

    public function testCreateByHttpRequestAndFlowWithBackwardTransition(): void
    {
        $flow = $this->createFlow(2);
        $httpRequest = $this->createHttpRequest('backwards');

        $request = Request::createByHttpRequestAndFlow($httpRequest, $flow);
        self::assertSame(1, $request->getRequestedStepNumber());
        self::assertSame(Request::BACKWARDS, $request->getValue());
    }

    public function testCreateByHttpRequestAndFlowWithInvalidBackwardTransition(): void
    {
        $flow = $this->createFlow(1);
        $httpRequest = $this->createHttpRequest('backwards');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A requested step number must be above 0 when going backwards. "0" given.');
        Request::createByHttpRequestAndFlow($httpRequest, $flow);
    }

    public function testCreateByHttpRequestAndFlowWithNegativeBackwardTransition(): void
    {
        $flow = $this->createFlow(0);
        $httpRequest = $this->createHttpRequest('backwards');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A requested step number must be greater than or equal to 0. "-1" given.');
        Request::createByHttpRequestAndFlow($httpRequest, $flow);
    }

    public function testCreateByHttpRequestAndFlowWithResetTransition(): void
    {
        $flow = $this->createFlow(2);
        $httpRequest = $this->createHttpRequest('reset_1');

        $request = Request::createByHttpRequestAndFlow($httpRequest, $flow);
        self::assertSame(1, $request->getRequestedStepNumber());
        self::assertSame(Request::RESET, $request->getValue());
    }

    public function testCreateByHttpRequestAndFlowWithCompleteTransition(): void
    {
        $flow = $this->createFlow(2);
        $httpRequest = $this->createHttpRequest('complete_1');

        $request = Request::createByHttpRequestAndFlow($httpRequest, $flow);
        self::assertSame(1, $request->getRequestedStepNumber());
        self::assertSame(Request::COMPLETE, $request->getValue());
    }

    public function testCreateByHttpRequestAndFlowWithInvalidTransition(): void
    {
        $flow = $this->createFlow(2);
        $httpRequest = $this->createHttpRequest('another_1');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"another_1" is an invalid transition request for flow "some-name".');
        Request::createByHttpRequestAndFlow($httpRequest, $flow);
    }

    private function createFlow(int $currentStep): FormFlowInterface
    {
        $flow = $this->createStub(FormFlowInterface::class);
        $flow->method('getName')->willReturn('some-name');
        $flow->method('getCurrentStepNumber')->willReturn($currentStep);
        $flow->method('getTransitionKey')->willReturn('some-trans-key');
        $flow->method('getSteps')->willReturn(new StepCollection([
            $this->createStub(StepInterface::class),
            $this->createStub(StepInterface::class),
        ]));

        return $flow;
    }

    private function createHttpRequest(string $action): HttpRequest
    {
        $request = $this->createMock(HttpRequest::class);
        $request->method('get')->with('some-trans-key')->willReturn($action);

        return $request;
    }
}

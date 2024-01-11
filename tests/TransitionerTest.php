<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests;

use Aeviiq\Collection\StringCollection;
use Aeviiq\FormFlow\Context;
use Aeviiq\FormFlow\Enum\Transition\Status;
use Aeviiq\FormFlow\Event\CompletedEvent;
use Aeviiq\FormFlow\Event\Event;
use Aeviiq\FormFlow\Event\ResetEvent;
use Aeviiq\FormFlow\Event\SkipEvent;
use Aeviiq\FormFlow\Event\TransitionedEvent;
use Aeviiq\FormFlow\Event\TransitionEvent;
use Aeviiq\FormFlow\Exception\LogicException;
use Aeviiq\FormFlow\Exception\TransitionException;
use Aeviiq\FormFlow\FormFlowInterface;
use Aeviiq\FormFlow\Step\StepCollection;
use Aeviiq\FormFlow\Step\StepInterface;
use Aeviiq\FormFlow\Transitioner;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TransitionerTest extends TestCase
{
    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var FormFlowInterface|MockObject
     */
    private $flow;

    /**
     * @var Transitioner
     */
    private $transitioner;

    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var int
     */
    private $currentStepNumber = 0;

    public function testHasTransitionRequest(): void
    {
        $this->flow->method('getTransitionKey')->willReturn('some-trans-key');

        $httpRequest = $this->createMock(HttpRequest::class);
        $request = new InputBag(['some-trans-key' => 'some-value']);
        $httpRequest->request = $request;
        $this->setCurrentRequest($httpRequest);

        self::assertTrue($this->transitioner->hasTransitionRequest($this->flow));
    }

    public function testHasTransitionRequestWithoutRequestStack(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No request available.');
        $this->transitioner->hasTransitionRequest($this->flow);
    }

    public function testHasTransitionRequestWithoutCurrentRequest(): void
    {
        $this->setCurrentRequest(null);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No request available.');
        $this->transitioner->hasTransitionRequest($this->flow);
    }

    public function testTransitionForwards(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('forwards'));
        $this->setCurrentStepNumber(1);
        $this->setCurrentStepForm(true, true);

        $matcher = self::exactly(9);
        $this->eventDispatcher->expects($matcher)->method('dispatch')
            ->willReturnCallback(function (Event $event, string $eventId) use ($matcher) {
                $expected = match ($matcher->numberOfInvocations()) {
                    1 => [TransitionEvent::class, 'form_flow.pre_forwards.some-name.step_1'],
                    2 => [TransitionEvent::class, 'form_flow.pre_forwards.group-1'],
                    3 => [TransitionEvent::class, 'form_flow.pre_forwards.some-name'],
                    4 => [TransitionEvent::class, 'form_flow.pre_forwards'],
                    5 => [SkipEvent::class, 'form_flow.skip.some-name.step_1'],
                    6 => [TransitionedEvent::class, 'form_flow.post_forwards.some-name.step_1'],
                    7 => [TransitionedEvent::class, 'form_flow.post_forwards.group-1'],
                    8 => [TransitionedEvent::class, 'form_flow.post_forwards.some-name'],
                    9 => [TransitionedEvent::class, 'form_flow.post_forwards'],
                    default => [TransitionEvent::class, ''],
                };
                [$expectedInstance, $expectedEventId] = $expected;

                self::assertInstanceOf($expectedInstance, $event);
                self::assertEquals($expectedEventId, $eventId);

                return $event;
            });

        self::assertEquals(1, $this->flow->getCurrentStepNumber());
        $status = $this->transitioner->transition($this->flow);
        self::assertEquals(2, $this->flow->getCurrentStepNumber());
        self::assertTrue($status->equals(new Status(Status::SUCCESS | Status::VALID_FORM)));
    }

    public function testTransitionForwardsWithHardSkip(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('forwards'));
        $this->setCurrentStepNumber(1);
        $this->setCurrentStepForm(true, true);

        $this->context->expects(self::once())->method('setHardSkipped')->with($this->flow->getSteps()->getStepByNumber(2));

        $matcher = self::exactly(10);
        $this->eventDispatcher->expects($matcher)->method('dispatch')
            ->willReturnCallback(function (Event $event, string $eventId) use ($matcher) {
                $expected = match ($matcher->numberOfInvocations()) {
                    1 => [TransitionEvent::class, 'form_flow.pre_forwards.some-name.step_1'],
                    2 => [TransitionEvent::class, 'form_flow.pre_forwards.group-1'],
                    3 => [TransitionEvent::class, 'form_flow.pre_forwards.some-name'],
                    4 => [TransitionEvent::class, 'form_flow.pre_forwards'],
                    5 => [SkipEvent::class, 'form_flow.skip.some-name.step_1'],
                    6 => [SkipEvent::class, 'form_flow.skip.some-name.step_2'],
                    7 => [TransitionedEvent::class, 'form_flow.post_forwards.some-name.step_1'],
                    8 => [TransitionedEvent::class, 'form_flow.post_forwards.group-1'],
                    9 => [TransitionedEvent::class, 'form_flow.post_forwards.some-name'],
                    10 => [TransitionedEvent::class, 'form_flow.post_forwards'],
                    default => [TransitionEvent::class, ''],
                };
                if ($event instanceof SkipEvent && 'form_flow.skip.some-name.step_1' === $eventId) {
                    $event->hardSkip();
                }

                return $event;
            });

        self::assertEquals(1, $this->flow->getCurrentStepNumber());
        $status = $this->transitioner->transition($this->flow);
        self::assertEquals(3, $this->flow->getCurrentStepNumber());
        self::assertTrue($status->equals(new Status(Status::SUCCESS | Status::VALID_FORM)));
    }

    public function testTransitionForwardsWithSoftSkip(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('forwards'));
        $this->setCurrentStepNumber(1);
        $this->setCurrentStepForm(true, true);

        $this->context->expects(self::once())->method('setSoftSkipped')->with($this->flow->getSteps()->getStepByNumber(2));

        $matcher = self::exactly(10);
        $this->eventDispatcher->expects($matcher)->method('dispatch')
            ->willReturnCallback(function (Event $event, string $eventId) use ($matcher) {
                $expected = match ($matcher->numberOfInvocations()) {
                    1 => [TransitionEvent::class, 'form_flow.pre_forwards.some-name.step_1'],
                    2 => [TransitionEvent::class, 'form_flow.pre_forwards.group-1'],
                    3 => [TransitionEvent::class, 'form_flow.pre_forwards.some-name'],
                    4 => [TransitionEvent::class, 'form_flow.pre_forwards'],
                    5 => [SkipEvent::class, 'form_flow.skip.some-name.step_1'],
                    6 => [SkipEvent::class, 'form_flow.skip.some-name.step_2'],
                    7 => [TransitionedEvent::class, 'form_flow.post_forwards.some-name.step_1'],
                    8 => [TransitionedEvent::class, 'form_flow.post_forwards.group-1'],
                    9 => [TransitionedEvent::class, 'form_flow.post_forwards.some-name'],
                    10 => [TransitionedEvent::class, 'form_flow.post_forwards'],
                    default => [TransitionEvent::class, ''],
                };
                if ($event instanceof SkipEvent && 'form_flow.skip.some-name.step_1' === $eventId) {
                    $event->softSkip();
                }

                return $event;
            });

        self::assertEquals(1, $this->flow->getCurrentStepNumber());
        $status = $this->transitioner->transition($this->flow);
        self::assertEquals(3, $this->flow->getCurrentStepNumber());
        self::assertTrue($status->equals(new Status(Status::SUCCESS | Status::VALID_FORM)));
    }

    public function testTransitionForwardWithHardAndSoftSkip(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('forwards'));
        $this->setCurrentStepNumber(1);
        $this->setCurrentStepForm(true, true);

        $matcher = self::exactly(5);
        $this->eventDispatcher->expects($matcher)->method('dispatch')
            ->willReturnCallback(function (Event $event) use ($matcher) {
                $expected = match ($matcher->numberOfInvocations()) {
                    1 => 'form_flow.pre_forwards.some-name.step_1',
                    2 => 'form_flow.pre_forwards.group-1',
                    3 => 'form_flow.pre_forwards.some-name',
                    4 => 'form_flow.pre_forwards',
                    5 => 'form_flow.skip.some-name.step_1',
                    default => '',
                };
                if ($event instanceof SkipEvent && 'form_flow.skip.some-name.step_1' === $expected) {
                    $event->hardSkip();
                    $event->softSkip();
                }

                return $event;
            });

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A step can not be both hard and soft skipped.');
        $this->transitioner->transition($this->flow);
    }

    public function testTransitionForwardOnTheLastStep(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('forwards'));
        $this->setCurrentStepNumber(1);
        $this->setCurrentStepForm(false, false);

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::FAILURE | Status::INVALID_FORM)));
    }

    public function testTransitionForwardWithInvalidForm(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('forwards'));
        $this->setCurrentStepNumber(3);

        $this->expectException(TransitionException::class);
        $this->expectExceptionMessage('The flow is on the last step and can not transition forwards.');
        $this->transitioner->forwards($this->flow);
    }

    public function testTransitionForwardWithBlockedTransition(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('forwards'));
        $this->setCurrentStepNumber(1);
        $this->setCurrentStepForm(true, true);

        $matcher = self::exactly(4);
        $this->eventDispatcher->expects($matcher)->method('dispatch')
            ->willReturnCallback(function (TransitionEvent $event) use ($matcher) {
                $expected = match ($matcher->numberOfInvocations()) {
                    1 => [TransitionEvent::class, 'form_flow.pre_forwards.some-name.step_1'],
                    2 => [TransitionEvent::class, 'form_flow.pre_forwards.group-1'],
                    3 => [TransitionEvent::class, 'form_flow.pre_forwards.some-name'],
                    4 => [TransitionEvent::class, 'form_flow.pre_forwards'],
                    default => [TransitionEvent::class, ''],
                };
                $event->blockTransition();

                return $event;
            });

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::FAILURE | Status::VALID_FORM | Status::BLOCKED)));
    }

    public function testTransitionBackwards(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('backwards'));
        $this->setCurrentStepNumber(3);
        $this->setCurrentStepForm(true, true);

        $matcher = self::exactly(8);
        $this->eventDispatcher->expects($matcher)->method('dispatch')
            ->willReturnCallback(function (Event $event, string $eventId) use ($matcher) {
                $expected = match ($matcher->numberOfInvocations()) {
                    1 => [TransitionEvent::class, 'form_flow.pre_backwards.some-name.step_3'],
                    2 => [TransitionEvent::class, 'form_flow.pre_backwards.group-1'],
                    3 => [TransitionEvent::class, 'form_flow.pre_backwards.some-name'],
                    4 => [TransitionEvent::class, 'form_flow.pre_backwards'],
                    5 => [TransitionedEvent::class, 'form_flow.post_backwards.some-name.step_3'],
                    6=> [TransitionedEvent::class, 'form_flow.post_backwards.group-1'],
                    7 => [TransitionedEvent::class, 'form_flow.post_backwards.some-name'],
                    8 => [TransitionedEvent::class, 'form_flow.post_backwards'],
                    default => [TransitionEvent::class, ''],
                };
                [$expectedInstance, $expectedEventId] = $expected;
                self::assertInstanceOf($expectedInstance, $event);
                self::assertEquals($expectedEventId, $eventId);

                return $event;
            });

        self::assertEquals(3, $this->flow->getCurrentStepNumber());
        $status = $this->transitioner->transition($this->flow);
        self::assertEquals(2, $this->flow->getCurrentStepNumber());
        self::assertTrue($status->equals(new Status(Status::SUCCESS | Status::VALID_FORM)));
    }

    public function testTransitionBackwardsOnFirstStep(): void
    {
        $this->setCurrentStepNumber(1);

        $this->expectException(TransitionException::class);
        $this->expectExceptionMessage('The flow is on the first step and can not transition backwards.');
        $this->transitioner->backwards($this->flow);
    }

    public function testTransitionBackwardsWithBlockedTransition(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('backwards'));
        $this->setCurrentStepNumber(2);
        $this->setCurrentStepForm(true, true);

        $matcher = self::exactly(4);
        $this->eventDispatcher->expects($matcher)->method('dispatch')
            ->willReturnCallback(function (TransitionEvent $event, string $eventId) use ($matcher) {
                $expected = match ($matcher->numberOfInvocations()) {
                    1 => 'form_flow.pre_backwards.some-name.step_2',
                    2 => 'form_flow.pre_backwards.group-1',
                    3 => 'form_flow.pre_backwards.some-name',
                    4 => 'form_flow.pre_backwards',
                    default => 'form_flow.pre_backwards',
                };

                self::assertEquals($eventId, $expected);

                $event->blockTransition();

                return $event;
            });

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::FAILURE | Status::BLOCKED | Status::VALID_FORM)));
    }

    public function testTransitionBackwardsToInvalidStep(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('backwards_2'));
        $this->setCurrentStepNumber(2);

        $this->expectException(TransitionException::class);
        $this->expectExceptionMessage('"2" is an invalid requested step number in the current context.');
        $this->transitioner->transition($this->flow);
    }

    public function testTransitionComplete(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('complete'));
        $this->setCurrentStepNumber(3);
        $this->setCurrentStepForm(true, true);

        $this->context->method('isCompleted')->willReturn(true);
        $this->context->method('isSkipped')->willReturn(false);

        $matcher = self::exactly(6);
        $this->eventDispatcher->expects($matcher)->method('dispatch')
            ->willReturnCallback(function (Event $event, string $eventId) use ($matcher) {
                $expected = match ($matcher->numberOfInvocations()) {
                    1 => [TransitionEvent::class, 'form_flow.pre_complete.group-1'],
                    2 => [TransitionEvent::class, 'form_flow.pre_complete.some-name'],
                    3 => [TransitionEvent::class, 'form_flow.pre_complete'],
                    4 => [CompletedEvent::class, 'form_flow.completed.group-1'],
                    5 => [CompletedEvent::class, 'form_flow.completed.some-name'],
                    6 => [CompletedEvent::class, 'form_flow.completed'],
                    default => [CompletedEvent::class, 'form_flow.completed'],
                };
                [$expectedInstance, $expectedEventId] = $expected;

                self::assertInstanceOf($expectedInstance, $event);
                self::assertEquals($expectedEventId, $eventId);

                return $event;
            });

        $this->flow->expects(self::once())->method('reset');

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::SUCCESS | Status::VALID_FORM | Status::COMPLETED)));
    }

    public function testTransitionCompleteWithBlockedTransition(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('complete'));
        $this->setCurrentStepNumber(3);
        $this->setCurrentStepForm(true, true);

        $this->context->method('isCompleted')->willReturn(true);
        $this->context->method('isSkipped')->willReturn(false);
        $this->eventDispatcher->expects(self::exactly(3))->method('dispatch')->willReturnCallback(static function (TransitionEvent $event): object {
            $event->blockTransition();

            return $event;
        });

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::FAILURE | Status::VALID_FORM | Status::BLOCKED)));
    }

    public function testTransitionCompleteWithIncompletePreviousStep(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('complete'));
        $this->setCurrentStepNumber(3);
        $this->setCurrentStepForm(true, true);

        $this->context->method('isCompleted')->willReturn(false);
        $this->context->method('isSkipped')->willReturn(false);

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::FAILURE)));
    }

    public function testTransitionCompleteWithInvalidForm(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('complete'));
        $this->setCurrentStepNumber(3);
        $this->setCurrentStepForm(false, false);

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::FAILURE | Status::INVALID_FORM)));
    }

    public function testTransitionCompleteWhenNotOnLastStep(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('complete'));
        $this->setCurrentStepNumber(2);

        $this->expectException(TransitionException::class);
        $this->expectExceptionMessage('The flow must be on the last step in order to be completed.');
        $this->transitioner->transition($this->flow);
    }

    public function testTransitionReset(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('reset'));
        $this->setCurrentStepNumber(1);

        $this->eventDispatcher->expects(self::once())->method('dispatch')->with(
            $this->assertFlowEvent(ResetEvent::class), 'form_flow.reset'
        );

        $this->flow->expects(self::once())->method('reset');

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::SUCCESS | Status::RESET)));
    }

    public function testTransitionWithoutTransitionRequest(): void
    {
        $this->flow->method('getTransitionKey')->willReturn('some-trans-key');
        $this->flow->method('getName')->willReturn('some-name');

        $httpRequest = $this->createMock(HttpRequest::class);
        $request = new InputBag(['some-trans-key' => '']);
        $httpRequest->request = $request;
        $query = new InputBag(['some-trans-key' => '']);
        $httpRequest->query = $query;
        $this->setCurrentRequest($httpRequest);

        $this->expectException(TransitionException::class);
        $this->expectExceptionMessage('Unable to transition flow "some-name". Use TransitionerInterface#hasTransitionRequest() to ensure there is a transition request before attempting to transition.');
        $this->transitioner->transition($this->flow);
    }

    protected function setUp(): void
    {
        $this->flow = $this->createMock(FormFlowInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->context = $this->createMock(Context::class);
        $this->transitioner = new Transitioner($this->eventDispatcher);

        $firstStep = self::createStub(StepInterface::class);
        $firstStep->method('getNumber')->willReturn(1);
        $secondStep = self::createStub(StepInterface::class);
        $secondStep->method('getNumber')->willReturn(2);
        $thirdStep = self::createStub(StepInterface::class);
        $thirdStep->method('getNumber')->willReturn(3);

        $this->flow->method('getName')->willReturn('some-name');
        $this->flow->method('getTransitionKey')->willReturn('some-trans-key');
        $this->flow->method('getContext')->willReturn($this->context);
        $this->flow->method('getFirstStep')->willReturn($firstStep);
        $this->flow->method('getLastStep')->willReturn($thirdStep);
        $this->flow->method('getGroups')->willReturn(new StringCollection(['group-1']));

        $this->flow->method('getCurrentStep')->willReturnCallback(function () use ($thirdStep, $secondStep, $firstStep): StepInterface {
            if (1 === $this->flow->getCurrentStepNumber()) {
                return $firstStep;
            }

            if (2 === $this->flow->getCurrentStepNumber()) {
                return $secondStep;
            }

            if (3 === $this->flow->getCurrentStepNumber()) {
                return $thirdStep;
            }

            throw new AssertionFailedError(sprintf('No step found for number "%d".', $this->flow->getCurrentStepNumber()));
        });

        $this->flow->method('getSteps')->willReturn(new StepCollection([
            $firstStep,
            $secondStep,
            $thirdStep,
        ]));

        $this->flow->method('getCurrentStepNumber')->willReturnCallback(function (): int {
            return $this->currentStepNumber;
        });

        $this->context->method('setCurrentStepNumber')->willReturnCallback(function (int $number): void {
            $this->currentStepNumber = $number;
        });
    }

    private function setCurrentRequest(?HttpRequest $request): void
    {
        $stack = self::createStub(RequestStack::class);
        $stack->method('getCurrentRequest')->willReturn($request);

        $this->transitioner->setRequestStack($stack);
    }

    private function setCurrentStepForm(bool $valid, bool $submitted): void
    {
        $form = self::createStub(FormInterface::class);
        $form->method('isValid')->willReturn($valid);
        $form->method('isSubmitted')->willReturn($submitted);
        $form->method('getName')->willReturn('form_name');

        $this->flow->method('getCurrentStepForm')->willReturn($form);
    }

    private function setCurrentStepNumber(int $currentStep): void
    {
        $this->currentStepNumber = $currentStep;
    }

    private function createHttpRequest(string $action): HttpRequest
    {
        $httpRequest = $this->createMock(HttpRequest::class);
        $request = new InputBag(['some-trans-key' => $action]);
        $httpRequest->request = $request;

        return $httpRequest;
    }

    private function assertFlowEvent(string $expectedInstance): Constraint
    {
        return new Callback(function (Event $event) use ($expectedInstance): bool {
            self::assertSame($this->flow, $event->getFlow());
            if (class_exists($expectedInstance)) {
                self::assertInstanceOf($expectedInstance, $event);
            }

            return true;
        });
    }
}

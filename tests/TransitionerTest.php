<?php declare(strict_types=1);

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
use Symfony\Component\HttpFoundation\ParameterBag;
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

        $request = $this->createMock(HttpRequest::class);
        $request->method('get')->with('some-trans-key', '')->willReturnOnConsecutiveCalls('some-value', '');
        $this->setCurrentRequest($request);

        self::assertTrue($this->transitioner->hasTransitionRequest($this->flow));
        self::assertFalse($this->transitioner->hasTransitionRequest($this->flow));
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

        $this->eventDispatcher->expects(self::exactly(9))->method('dispatch')->withConsecutive(
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.some-name.step_1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.group-1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.some-name'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards'],
            [$this->assertFlowEvent(SkipEvent::class), 'form_flow.skip.some-name.step_1'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards.some-name.step_1'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards.group-1'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards.some-name'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards']
        );

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::SUCCESS | Status::VALID_FORM)));
    }

    public function testTransitionForwardsWithHardSkip(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('forwards'));
        $this->setCurrentStepNumber(1);
        $this->setCurrentStepForm(true, true);

        $this->context->expects(self::once())->method('setHardSkipped')->with($this->flow->getSteps()->getStepByNumber(2));

        $this->eventDispatcher->expects(self::exactly(10))->method('dispatch')->withConsecutive(
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.some-name.step_1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.group-1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.some-name'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards'],
            [$this->assertFlowEvent(SkipEvent::class), 'form_flow.skip.some-name.step_1'],
            [$this->assertFlowEvent(SkipEvent::class), 'form_flow.skip.some-name.step_2'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards.some-name.step_1'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards.group-1'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards.some-name'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards']
        )->willReturnCallback(static function (Event $event, string $eventName): object {
            if ($event instanceof SkipEvent && 'form_flow.skip.some-name.step_1' === $eventName) {
                $event->hardSkip();
            }

            return $event;
        });

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::SUCCESS | Status::VALID_FORM)));
    }

    public function testTransitionForwardsWithSoftSkip(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('forwards'));
        $this->setCurrentStepNumber(1);
        $this->setCurrentStepForm(true, true);

        $this->context->expects(self::once())->method('setSoftSkipped')->with($this->flow->getSteps()->getStepByNumber(2));

        $this->eventDispatcher->expects(self::exactly(10))->method('dispatch')->withConsecutive(
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.some-name.step_1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.group-1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.some-name'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards'],
            [$this->assertFlowEvent(SkipEvent::class), 'form_flow.skip.some-name.step_1'],
            [$this->assertFlowEvent(SkipEvent::class), 'form_flow.skip.some-name.step_2'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards.some-name.step_1'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards.group-1'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards.some-name'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_forwards']
        )->willReturnCallback(static function (Event $event, string $eventName): object {
            if ($event instanceof SkipEvent && 'form_flow.skip.some-name.step_1' === $eventName) {
                $event->softSkip();
            }

            return $event;
        });

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::SUCCESS | Status::VALID_FORM)));
    }

    public function testTransitionForwardWithHardAndSoftSkip(): void
    {
        $this->setCurrentRequest($this->createHttpRequest('forwards'));
        $this->setCurrentStepNumber(1);
        $this->setCurrentStepForm(true, true);

        $this->eventDispatcher->expects(self::exactly(5))->method('dispatch')->withConsecutive(
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.some-name.step_1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.group-1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.some-name'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards'],
            [$this->assertFlowEvent(SkipEvent::class), 'form_flow.skip.some-name.step_1']
        )->willReturnCallback(static function (Event $event, string $eventName): object {
            if ($event instanceof SkipEvent && 'form_flow.skip.some-name.step_1' === $eventName) {
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

        $this->eventDispatcher->expects(self::exactly(4))->method('dispatch')->withConsecutive(
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.some-name.step_1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.group-1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards.some-name'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_forwards']
        )->willReturnCallback(static function (TransitionEvent $event): object {
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

        $this->context->expects(self::exactly(2))->method('isHardSkipped')->withConsecutive(
            [$this->flow->getSteps()->getStepByNumber(2)],
            [$this->flow->getSteps()->getStepByNumber(1)]
        )->willReturnOnConsecutiveCalls(true, false);

        $this->eventDispatcher->expects(self::exactly(8))->method('dispatch')->withConsecutive(
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_backwards.some-name.step_3'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_backwards.group-1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_backwards.some-name'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_backwards'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_backwards.some-name.step_3'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_backwards.group-1'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_backwards.some-name'],
            [$this->assertFlowEvent(TransitionedEvent::class), 'form_flow.post_backwards']
        );

        $status = $this->transitioner->transition($this->flow);
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

        $this->eventDispatcher->expects(self::exactly(4))->method('dispatch')->withConsecutive(
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_backwards.some-name.step_2'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_backwards.group-1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_backwards.some-name'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_backwards']
        )->willReturnCallback(static function (TransitionEvent $event): object {
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

        $this->eventDispatcher->expects(self::exactly(6))->method('dispatch')->withConsecutive(
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_complete.group-1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_complete.some-name'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_complete'],
            [$this->assertFlowEvent(CompletedEvent::class), 'form_flow.completed.group-1'],
            [$this->assertFlowEvent(CompletedEvent::class), 'form_flow.completed.some-name'],
            [$this->assertFlowEvent(CompletedEvent::class), 'form_flow.completed']
        );

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

        $this->eventDispatcher->expects(self::exactly(3))->method('dispatch')->withConsecutive(
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_complete.group-1'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_complete.some-name'],
            [$this->assertFlowEvent(TransitionEvent::class), 'form_flow.pre_complete']
        )->willReturnCallback(static function (TransitionEvent $event): object {
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

        $this->eventDispatcher->expects(self::once())->method('dispatch')->withConsecutive(
            [$this->assertFlowEvent(ResetEvent::class), 'form_flow.reset']
        );

        $this->flow->expects(self::once())->method('reset');

        $status = $this->transitioner->transition($this->flow);
        self::assertTrue($status->equals(new Status(Status::SUCCESS | Status::RESET)));
    }

    public function testTransitionWithoutTransitionRequest(): void
    {
        $this->flow->method('getTransitionKey')->willReturn('some-trans-key');
        $this->flow->method('getName')->willReturn('some-name');

        $request = $this->createMock(HttpRequest::class);
        $request->method('get')->with('some-trans-key', '')->willReturn('');
        $this->setCurrentRequest($request);

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

        $firstStep = $this->createStub(StepInterface::class);
        $firstStep->method('getNumber')->willReturn(1);
        $secondStep = $this->createStub(StepInterface::class);
        $secondStep->method('getNumber')->willReturn(2);
        $thirdStep = $this->createStub(StepInterface::class);
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
        $stack = $this->createStub(RequestStack::class);
        $stack->method('getCurrentRequest')->willReturn($request);

        $this->transitioner->setRequestStack($stack);
    }

    private function setCurrentStepForm(bool $valid, bool $submitted): void
    {
        $form = $this->createStub(FormInterface::class);
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
        $httpRequest->method('get')->with('some-trans-key')->willReturn($action);
        $request = $this->createMock(ParameterBag::class);
        $request->method('has')->willReturn(true);
        $httpRequest->request = $request;

        return $httpRequest;
    }

    private function assertFlowEvent(string $expectedInstance): Constraint
    {
        return new Callback(function (Event $event) use ($expectedInstance): bool {
            self::assertSame($this->flow, $event->getFlow());
            self::assertInstanceOf($expectedInstance, $event);

            return true;
        });
    }
}

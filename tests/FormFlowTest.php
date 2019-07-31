<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests;

use Aeviiq\FormFlow\Context;
use Aeviiq\FormFlow\Definition;
use Aeviiq\FormFlow\Enum\TransitionEnum;
use Aeviiq\FormFlow\Exception\LogicException;
use Aeviiq\FormFlow\FormFlow;
use Aeviiq\FormFlow\FormFlowInterface;
use Aeviiq\FormFlow\Step\StepCollection;
use Aeviiq\FormFlow\Step\StepInterface;
use Aeviiq\StorageManager\StorageManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class FormFlowTest extends TestCase
{
    /**
     * @var StorageManagerInterface|MockObject
     */
    private $mockedStorageManager;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private $mockedEventDispatcher;

    /**
     * @var FormFactoryInterface|MockObject
     */
    private $mockedFormFactory;

    public function testTransitionForwards(): void
    {
        $step1 = $this->createMock(StepInterface::class);
        $step1->method('getNumber')->willReturn(1);
        $step2 = $this->createMock(StepInterface::class);
        $step2->method('getNumber')->willReturn(2);
        $this->createdFormWillBeValid();

        $flow = $this->createStartedValidFormFlow($this->createDefinition([$step1, $step2]));
        $this->assertEquals(1, $flow->getCurrentStepNumber());
        $this->assertSame($step1, $flow->getCurrentStep());
        $this->assertFalse($flow->hasPreviousStep());
        $flow->transitionForwards();
        $this->assertEquals(2, $flow->getCurrentStepNumber());
        $this->assertSame($step2, $flow->getCurrentStep());
        $this->assertTrue($flow->hasPreviousStep());
    }

    public function testTransitionForwardsWhenFlowIsNotStarted(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you start() the flow?');
        $flow->transitionForwards();
    }

    public function testTransitionForwardsWhenNoMoreStepsAreLeft(): void
    {
        $this->createdFormWillBeValid();

        $flow = $this->createStartedValidFormFlowOnFinalStep();
        $flow->transitionForwards();
        $this->assertTrue($flow->isCompleted());
    }

    public function testCanTransitionForwards(): void
    {
        $this->createdFormWillBeValid();
        $flow = $this->createStartedValidFormFlow();
        $this->assertTrue($flow->canTransitionForwards());
        // TODO implement conditions for when blockable is implemented.
    }

    public function testCanTransitionForwardsWhenFormInvalid(): void
    {
        $this->createdFormWillBeInValid();

        $flow = $this->createStartedValidFormFlow();

        $this->assertFalse($flow->canTransitionForwards());
    }

    public function testTransitionBackwards(): void
    {
        $step1 = $this->createMock(StepInterface::class);
        $step1->method('getNumber')->willReturn(1);
        $step2 = $this->createMock(StepInterface::class);
        $step2->method('getNumber')->willReturn(2);
        $step3 = $this->createMock(StepInterface::class);
        $step3->method('getNumber')->willReturn(3);
        $this->createdFormWillBeValid();

        $flow = $this->createStartedValidFormFlowOnFinalStep($this->createDefinition([$step1, $step2, $step3]));
        $this->assertEquals(3, $flow->getCurrentStepNumber());
        $this->assertSame($step3, $flow->getCurrentStep());
        $this->assertFalse($flow->hasNextStep());
        $flow->transitionBackwards();
        $this->assertEquals(2, $flow->getCurrentStepNumber());
        $this->assertSame($step2, $flow->getCurrentStep());
        $this->assertTrue($flow->hasNextStep());
    }

    public function testTransitionBackwardsWhenFlowIsNotStarted(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you start() the flow?');
        $flow->transitionBackwards();
    }

    public function testTransitionBackwardsWhenNoPreviousStepsArePresent(): void
    {
        $flow = $this->createStartedValidFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable to transition backwards. Use canTransitionBackwards() to ensure the flow is in a valid state before attempting to transition.');
        $flow->transitionBackwards();
    }

    public function testHasTransitionedWhenGoneForwards(): void
    {
        $this->createdFormWillBeValid();

        $flow = $this->createStartedValidFormFlow();
        $this->assertFalse($flow->hasTransitioned());
        $flow->transitionForwards();
        $this->assertTrue($flow->hasTransitioned());
    }

    public function testHasTransitionedWhenGoneBackwards(): void
    {
        $this->createdFormWillBeValid();

        $flow = $this->createStartedValidFormFlowOnFinalStep();

        $this->assertFalse($flow->hasTransitioned());
        $flow->transitionBackwards();
        $this->assertTrue($flow->hasTransitioned());
    }

    public function testCanTransitionBackwards(): void
    {
        $this->createdFormWillBeValid();

        $flow = $this->createStartedValidFormFlow();
        $this->assertFalse($flow->canTransitionBackwards());
        $flow->transitionForwards();
        $this->assertTrue($flow->canTransitionBackwards());
    }

    /**
     * @dataProvider transitionDataProvider
     */
    public function testTransition(string $requestedTransition, int $startingStepNumber, int $resultingStepNumber): void
    {
        $max = max([$startingStepNumber, $resultingStepNumber]);
        $steps = [];
        for ($i = 1; $i <= $max; $i++) {
            $step = $this->createMock(StepInterface::class);
            $step->method('getNumber')->willReturn($i);
            $steps[] = $step;
        }
        $this->createdFormWillBeValid();
        $mockedRequestStack = $this->createMockedRequestStack($requestedTransition);

        $flow = $this->createStartedValidFormFlow($this->createDefinition($steps));
        $flow->setRequestStack($mockedRequestStack);

        while ($flow->getCurrentStepNumber() !== $startingStepNumber) {
            $flow->transitionForwards();
        }

        $this->assertEquals($startingStepNumber, $flow->getCurrentStepNumber());
        $flow->transition();
        $this->assertEquals($resultingStepNumber, $flow->getCurrentStepNumber());
    }

    public function transitionDataProvider(): array
    {
        return [
            'transition_forwards_1_to_2' => [
                TransitionEnum::FORWARDS,
                1,
                2,
            ],
            'transition_forwards_3_to_4' => [
                TransitionEnum::FORWARDS,
                3,
                4,
            ],
            'transition_backwards_2_to_1' => [
                TransitionEnum::BACKWARDS,
                2,
                1,
            ],
            'transition_backwards_5_to_4' => [
                TransitionEnum::BACKWARDS,
                5,
                4,
            ],
            // TODO implement RESET test cases
        ];
    }

    public function testTransitionWithInvalidRequestTransition(): void
    {
        $mockedRequestStack = $this->createMockedRequestStack('some_invalid_value');

        $flow = $this->createStartedValidFormFlow();
        $flow->setRequestStack($mockedRequestStack);
        $this->assertFalse($flow->transition());
    }

    public function testTransitionSavesTheDataExceptWhenItisTransitioningToTheLastStep(): void
    {
        $mockedForm = $this->createMock(FormInterface::class);
        $mockedForm->method('isSubmitted')->willReturn(true);
        $mockedForm->method('isValid')->willReturn(true);
        $this->mockedFormFactory->method('create')->willReturn($mockedForm);

        $mockedRequestStack = $this->createMockedRequestStack();

        $flow = $this->createStartedValidFormFlow();
        $flow->setRequestStack($mockedRequestStack);
        $this->mockedStorageManager->expects($this->once())->method('save');
        $flow->transition();
    }

    public function testGetSteps(): void
    {
        $step1 = $this->createMock(StepInterface::class);
        $step1->method('getNumber')->willReturn(1);
        $step2 = $this->createMock(StepInterface::class);
        $step2->method('getNumber')->willReturn(2);
        $steps[] = $step1;
        $steps[] = $step2;
        $stepCollection = new StepCollection($steps);
        $flow = $this->createStartedValidFormFlow(new Definition('form_flow', $stepCollection, \stdClass::class));
        $this->assertSame($stepCollection, $flow->getSteps());
    }

    public function testGetCurrentStep(): void
    {
        $step1 = $this->createMock(StepInterface::class);
        $step1->method('getNumber')->willReturn(1);
        $step2 = $this->createMock(StepInterface::class);
        $step2->method('getNumber')->willReturn(2);
        $steps[] = $step1;
        $steps[] = $step2;
        $flow = $this->createStartedValidFormFlow(new Definition('form_flow', new StepCollection($steps), \stdClass::class));
        $mockedRequestStack = $this->createMockedRequestStack();
        $flow->setRequestStack($mockedRequestStack);
        $this->createdFormWillBeValid();
        $this->assertSame($step1, $flow->getCurrentStep());
        $flow->transition();
        $this->assertSame($step2, $flow->getCurrentStep());
    }

    public function testGetCurrentStepWithoutAContext(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you start() the flow?');
        $flow->getCurrentStep();
    }

    public function testGetNextStep(): void
    {
        $step1 = $this->createMock(StepInterface::class);
        $step1->method('getNumber')->willReturn(1);
        $step2 = $this->createMock(StepInterface::class);
        $step2->method('getNumber')->willReturn(2);
        $steps[] = $step1;
        $steps[] = $step2;
        $flow = $this->createStartedValidFormFlow(new Definition('form_flow', new StepCollection($steps), \stdClass::class));
        $this->assertSame($step2, $flow->getNextStep());
    }

    public function testGetNextStepWithoutAContext(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you start() the flow?');
        $flow->getNextStep();
    }

    public function testGetNextStepWhenThereIsNone(): void
    {
        $flow = $this->createStartedValidFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is no previous step.');
        $flow->getPreviousStep();
    }
//
//    public function testHasNextStep(): void
//    {
//
//    }
//
    public function testHasNextStepWithoutAContext(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you start() the flow?');
        $flow->getCurrentStep();
    }

    public function testGetPreviousStep(): void
    {
        $step1 = $this->createMock(StepInterface::class);
        $step1->method('getNumber')->willReturn(1);
        $step2 = $this->createMock(StepInterface::class);
        $step2->method('getNumber')->willReturn(2);
        $steps[] = $step1;
        $steps[] = $step2;
        $flow = $this->createStartedValidFormFlowOnFinalStep(new Definition('form_flow', new StepCollection($steps), \stdClass::class));
        $this->assertSame($step1, $flow->getPreviousStep());
    }

    public function testGetPreviousStepWithoutAContext(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you start() the flow?');
        $flow->getPreviousStep();
    }

    public function testGetPreviousStepWhenThereIsNone(): void
    {
        $flow = $this->createStartedValidFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is no previous step.');
        $flow->getPreviousStep();
    }

//
//    public function testHasPreviousStep(): void
//    {
//
//    }
//
    public function testHasPreviousStepWithoutAContext(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you start() the flow?');
        $flow->hasPreviousStep();
    }

    public function testGetFirstStep(): void
    {
        $step1 = $this->createMock(StepInterface::class);
        $step1->method('getNumber')->willReturn(1);
        $step2 = $this->createMock(StepInterface::class);
        $step2->method('getNumber')->willReturn(2);
        $step3 = $this->createMock(StepInterface::class);
        $step3->method('getNumber')->willReturn(3);
        $steps[] = $step1;
        $steps[] = $step2;
        $steps[] = $step3;
        $flow = $this->createStartedValidFormFlow(new Definition('form_flow', new StepCollection($steps), \stdClass::class));
        $this->assertSame($step1, $flow->getFirstStep());
    }

    public function testGetLastStep(): void
    {
        $step1 = $this->createMock(StepInterface::class);
        $step1->method('getNumber')->willReturn(1);
        $step2 = $this->createMock(StepInterface::class);
        $step2->method('getNumber')->willReturn(2);
        $step3 = $this->createMock(StepInterface::class);
        $step3->method('getNumber')->willReturn(3);
        $steps[] = $step1;
        $steps[] = $step2;
        $steps[] = $step3;
        $flow = $this->createStartedValidFormFlow(new Definition('form_flow', new StepCollection($steps), \stdClass::class));
        $this->assertSame($step3, $flow->getLastStep());
    }

    protected function setUp(): void
    {
        $this->mockedStorageManager = $this->createMock(StorageManagerInterface::class);
        $this->mockedEventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->mockedFormFactory = $this->createMock(FormFactoryInterface::class);
    }

    private function createdFormWillBeInValid(): void
    {
        $mockedForm = $this->createMock(FormInterface::class);
        $mockedForm->method('isSubmitted')->willReturn(true);
        $mockedForm->method('isValid')->willReturn(false);
        $this->mockedFormFactory->method('create')->willReturn($mockedForm);
    }

    private function createdFormWillBeValid(): void
    {
        $mockedForm = $this->createMock(FormInterface::class);
        $mockedForm->method('isSubmitted')->willReturn(true);
        $mockedForm->method('isValid')->willReturn(true);
        $this->mockedFormFactory->method('create')->willReturn($mockedForm);
    }

    private function createMockedRequestStack(string $requestedTransition = TransitionEnum::FORWARDS): RequestStack
    {
        $mockedRequestStack = $this->createMock(RequestStack::class);
        $mockedRequest = $this->createMock(Request::class);
        $mockedRequestStack->method('getCurrentRequest')->willReturn($mockedRequest);
        $mockedRequest->method('get')->willReturn($requestedTransition);

        return $mockedRequestStack;
    }

    private function createDefinition(array $steps = [], string $expectedInstance = \stdClass::class, string $name = 'form_flow'): Definition
    {
        if (empty($steps)) {
            $step1 = $this->createMock(StepInterface::class);
            $step1->method('getNumber')->willReturn(1);
            $steps[] = $step1;
            $step2 = $this->createMock(StepInterface::class);
            $step2->method('getNumber')->willReturn(2);
            $steps[] = $step2;
        }

        return new Definition($name, new StepCollection($steps), $expectedInstance);
    }

    private function createStartedValidFormFlowOnFinalStep(?Definition $definition = null): FormFlowInterface
    {
        $flow = $this->createValidFormFlow($definition);
        $context = new Context(new \stdClass(), $flow->getSteps()->count());
        while ($context->canTransitionForwards()) {
            $context->transitionForwards();
        }
        $flow->start($context);

        return $flow;
    }

    private function createStartedValidFormFlow(?Definition $definition = null): FormFlowInterface
    {
        $flow = $this->createValidFormFlow($definition);
        $flow->start(new \stdClass());

        return $flow;
    }

    private function createDefaultFormFlow(?Definition $definition = null): FormFlowInterface
    {
        return new FormFlow($this->mockedStorageManager, $this->mockedEventDispatcher, $this->mockedFormFactory, $definition ?? $this->createDefinition());
    }

    private function createValidFormFlow(?Definition $definition = null): FormFlowInterface
    {
        $flow = $this->createDefaultFormFlow($definition);
        $mockedRequestStack = $this->createMock(RequestStack::class);
        $mockedRequest = $this->createMock(Request::class);
        $mockedRequestStack->method('getCurrentRequest')->willReturn($mockedRequest);
        $flow->setRequestStack($mockedRequestStack);

        return $flow;
    }
}

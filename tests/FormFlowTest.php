<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests;

use Aeviiq\FormFlow\Definition;
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
        $mockedForm = $this->createMock(FormInterface::class);
        $mockedForm->method('isSubmitted')->willReturn(true);
        $mockedForm->method('isValid')->willReturn(true);
        $this->mockedFormFactory->method('create')->willReturn($mockedForm);

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
        $mockedForm = $this->createMock(FormInterface::class);
        $mockedForm->method('isSubmitted')->willReturn(true);
        $mockedForm->method('isValid')->willReturn(true);
        $this->mockedFormFactory->method('create')->willReturn($mockedForm);

        $flow = $this->createStartedValidFormFlowOnFinalStep();
        $flow->transitionForwards();
        $this->assertTrue($flow->isCompleted());
    }

    // TODO implement tests below
//    public function testCanTransitionForwards(): void
//    {
//    }

    public function testCanTransitionForwardsWhenFormInvalid(): void
    {
        $mockedForm = $this->createMock(FormInterface::class);
        $mockedForm->method('isSubmitted')->willReturn(true);
        $mockedForm->method('isValid')->willReturn(false);
        $this->mockedFormFactory->method('create')->willReturn($mockedForm);

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
        $mockedForm = $this->createMock(FormInterface::class);
        $mockedForm->method('isSubmitted')->willReturn(true);
        $mockedForm->method('isValid')->willReturn(true);
        $this->mockedFormFactory->method('create')->willReturn($mockedForm);

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

    // TODO implement tests below
//    public function testCanTransitionBackwards(): void
//    {
//    }

    // TODO implement tests below
//    public function testTransition(): void
//    {
//
//    }
//
//    public function testTransitionWithInvalidRequestTransition(): void
//    {
//
//    }
//
//    public function testTransitionSavesTheDataExceptWhenItisTransitioningToTheLastStep(): void
//    {
//
//    }

    protected function setUp(): void
    {
        $this->mockedStorageManager = $this->createMock(StorageManagerInterface::class);
        $this->mockedEventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->mockedFormFactory = $this->createMock(FormFactoryInterface::class);
    }

    private function createStartedValidFormFlowOnFinalStep(?Definition $definition = null): FormFlowInterface
    {
        $flow = $this->createStartedValidFormFlow($definition);
        while ($flow->canTransitionForwards() && $flow->getCurrentStepNumber() < $flow->getSteps()->count()) {
            $flow->transitionForwards();
        }

        return $flow;
    }

    private function createStartedValidFormFlow(?Definition $definition = null): FormFlowInterface
    {
        $formFlow = $this->createValidFormFlow($definition);
        $formFlow->start(new \stdClass());

        return $formFlow;
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

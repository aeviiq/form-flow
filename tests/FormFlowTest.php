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
use Symfony\Component\Form\FormFactoryInterface;

final class FormFlowTest extends TestCase
{
    /**
     * @var StorageManagerInterface|MockObject
     */
    private $mockedStorageManager;

    /**
     * @var FormFactoryInterface|MockObject
     */
    private $mockedFormFactory;


    public function testGetCurrentStepWithoutAContext(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you FormFlow#start() the flow?');
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
        // TODO cases when skippable is added.
    }

    public function testGetNextStepWithoutAContext(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you FormFlow#start() the flow?');
        $flow->getNextStep();
    }

    public function testGetNextStepWhenThereIsNone(): void
    {
        $flow = $this->createStartedValidFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is no previous step.');
        $flow->getPreviousStep();
    }

    public function testHasNextStep(): void
    {
        $flow = $this->createStartedValidFormFlow();
        $this->assertTrue($flow->hasNextStep());
        $flow = $this->createStartedValidFormFlowOnFinalStep();
        $this->assertFalse($flow->hasNextStep());
    }

    public function testHasNextStepWithoutAContext(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you FormFlow#start() the flow?');
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
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you FormFlow#start() the flow?');
        $flow->getPreviousStep();
    }

    public function testGetPreviousStepWhenThereIsNone(): void
    {
        $flow = $this->createStartedValidFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is no previous step.');
        $flow->getPreviousStep();
    }

    public function testHasPreviousStep(): void
    {
        $flow = $this->createStartedValidFormFlow();
        $this->assertFalse($flow->hasPreviousStep());
        $flow = $this->createStartedValidFormFlowOnFinalStep();
        $this->assertTrue($flow->hasPreviousStep());
    }

    public function testHasPreviousStepWithoutAContext(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you FormFlow#start() the flow?');
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
        $this->mockedFormFactory = $this->createMock(FormFactoryInterface::class);
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
            $step3 = $this->createMock(StepInterface::class);
            $step3->method('getNumber')->willReturn(3);
            $steps[] = $step3;
            $step4 = $this->createMock(StepInterface::class);
            $step4->method('getNumber')->willReturn(4);
            $steps[] = $step4;
        }

        return new Definition($name, new StepCollection($steps), $expectedInstance);
    }

    private function createStartedValidFormFlowOnFinalStep(?Definition $definition = null): FormFlowInterface
    {
        $flow = $this->createStartedValidFormFlow($definition);
        $flow->getContext()->setCurrentStepNumber($flow->getSteps()->count());

        return $flow;
    }

    private function createStartedValidFormFlow(?Definition $definition = null): FormFlowInterface
    {
        $flow = $this->createDefaultFormFlow($definition);
        $flow->start(new \stdClass());

        return $flow;
    }

    private function createDefaultFormFlow(?Definition $definition = null): FormFlowInterface
    {
        return new FormFlow($this->mockedStorageManager, $this->mockedFormFactory, $definition ?? $this->createDefinition());
    }
}

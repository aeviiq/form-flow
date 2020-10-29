<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Tests;

use Aeviiq\FormFlow\Context;
use Aeviiq\FormFlow\Definition;
use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Aeviiq\FormFlow\Exception\LogicException;
use Aeviiq\FormFlow\Exception\UnexpectedValueException;
use Aeviiq\FormFlow\FormFlow;
use Aeviiq\FormFlow\FormFlowInterface;
use Aeviiq\FormFlow\Step\StepCollection;
use Aeviiq\FormFlow\Step\StepInterface;
use Aeviiq\StorageManager\StorageManagerInterface;
use DateTime;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

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

    public function testInitialize(): void
    {
        $this->mockedStorageManager->expects(self::once())->method('has')
            ->with('form_flow.storage.form_flow')->willReturn(true);

        $this->mockedStorageManager->expects(self::once())->method('load')
            ->with('form_flow.storage.form_flow')->willReturn($this->createStub(Context::class));

        $this->createDefaultFormFlow();
    }

    public function testInitializeWithCorruptedContext(): void
    {
        $this->mockedStorageManager->expects(self::once())->method('has')
            ->with('form_flow.storage.form_flow')->willReturn(true);
        $this->mockedStorageManager->expects(self::once())->method('load')
            ->with('form_flow.storage.form_flow')->willReturn(new stdClass());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The stored context is corrupted.');
        $this->createDefaultFormFlow();
    }

    public function testStartWhenFlowIsAlreadyStarted(): void
    {
        $flow = $this->createDefaultFormFlow();
        $flow->start(new stdClass());

        $this->expectException(LogicException::class);
        $this->expectDeprecationMessage('The flow is already started. In order to start it again, you need to reset() it.');
        $flow->start(new stdClass());
    }

    public function testStartWithInvalidDataInstance(): void
    {
        $flow = $this->createDefaultFormFlow();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data must be an instanceof stdClass, DateTime given.');
        $flow->start(new DateTime());
    }

    public function testReset(): void
    {
        $flow = $this->createDefaultFormFlow();

        $this->mockedStorageManager->expects(self::once())->method('remove')->with('form_flow.storage.form_flow');

        $flow->reset();
    }

    public function testSave(): void
    {
        $flow = $this->createDefaultFormFlow();
        $flow->start(new stdClass());

        $this->mockedStorageManager->expects(self::once())->method('save')->with(
            'form_flow.storage.form_flow',
            new IsInstanceOf(Context::class)
        );

        $flow->save();
    }

    public function testSaveWhenFlowIsNotStarted(): void
    {
        $flow = $this->createDefaultFormFlow();

        $this->mockedStorageManager->expects(self::never())->method('save');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable to save the flow without a context. Did you FormFlow#start() the flow?');
        $flow->save();
    }

    public function testGetData(): void
    {
        $data = new stdClass();
        $flow = $this->createDefaultFormFlow();

        $flow->start($data);
        self::assertSame($data, $flow->getData());
        $flow->reset();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you FormFlow#start() the flow?');
        $flow->getData();
    }

    public function testGetGroups(): void
    {
        $flow = $this->createDefaultFormFlow();

        self::assertSame([Definition::DEFAULT_GROUP], $flow->getGroups()->toArray());
    }

    public function testGetCurrentStepForm(): void
    {
        $data = new stdClass();
        $flow = $this->createDefaultFormFlow();

        $flow->start($data);
        $form = $this->createStub(FormInterface::class);
        $this->mockedFormFactory->expects(self::once())->method('create')->with('', $data)->willReturn($form);
        self::assertSame($form, $flow->getCurrentStepForm());
        $flow->reset();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you FormFlow#start() the flow?');
        $flow->getCurrentStepForm();
    }

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
        $flow = $this->createStartedValidFormFlow(new Definition('form_flow', stdClass::class, new StepCollection($steps)));
        static::assertSame($step2, $flow->getNextStep());
    }

    public function testGetNextStepWithoutAContext(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you FormFlow#start() the flow?');
        $flow->getNextStep();
    }

    public function testGetNextStepWithoutNextStep(): void
    {
        $flow = $this->createDefaultFormFlow();
        $flow->start(new stdClass());
        $flow->getContext()->setCurrentStepNumber(4);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is no next step.');
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
        static::assertTrue($flow->hasNextStep());
        $flow = $this->createStartedValidFormFlowOnFinalStep();
        static::assertFalse($flow->hasNextStep());
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
        $flow = $this->createStartedValidFormFlowOnFinalStep(new Definition('form_flow', stdClass::class, new StepCollection($steps)));
        static::assertSame($step1, $flow->getPreviousStep());
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
        static::assertFalse($flow->hasPreviousStep());
        $flow = $this->createStartedValidFormFlowOnFinalStep();
        static::assertTrue($flow->hasPreviousStep());
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
        $flow = $this->createStartedValidFormFlow(new Definition('form_flow', stdClass::class, new StepCollection($steps)));
        static::assertSame($step1, $flow->getFirstStep());
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
        $flow = $this->createStartedValidFormFlow(new Definition('form_flow', stdClass::class, new StepCollection($steps)));
        static::assertSame($step3, $flow->getLastStep());
    }

    public function testGetTransitionKey(): void
    {
        self::assertSame('flow_form_flow_transition', $this->createDefaultFormFlow()->getTransitionKey());
    }

    public function testGetFormByStepNumber(): void
    {
        $data = new stdClass();
        $flow = $this->createDefaultFormFlow();

        $flow->start($data);
        $form = $this->createStub(FormInterface::class);
        $this->mockedFormFactory->expects(self::once())->method('create')->with('', $data)->willReturn($form);
        self::assertSame($form, $flow->getFormByStepNumber(1));
        $flow->reset();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The flow is missing it\'s context. Did you FormFlow#start() the flow?');
        $flow->getFormByStepNumber(1);
    }

    public function testGetStorageKey(): void
    {
        $flow = $this->createDefaultFormFlow();
        $this->assertSame(\sprintf(FormFlow::STORAGE_KEY_PREFIX, $flow->getName()), $flow->getStorageKey());
        $flow->setStorageKey('12345');
        $this->assertSame(\sprintf(FormFlow::STORAGE_KEY_PREFIX . '.%s', $flow->getName(), '12345'), $flow->getStorageKey());
        $flow->setStorageKey(null);
        $this->assertSame(\sprintf(FormFlow::STORAGE_KEY_PREFIX, $flow->getName()), $flow->getStorageKey());
    }

    protected function setUp(): void
    {
        $this->mockedStorageManager = $this->createMock(StorageManagerInterface::class);
        $this->mockedFormFactory = $this->createMock(FormFactoryInterface::class);
    }

    private function createDefinition(
        array $steps = [],
        string $expectedInstance = stdClass::class,
        string $name = 'form_flow'
    ): Definition {
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

        return new Definition($name, $expectedInstance, new StepCollection($steps));
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
        $flow->start(new stdClass());

        return $flow;
    }

    private function createDefaultFormFlow(?Definition $definition = null): FormFlowInterface
    {
        return new FormFlow($this->mockedStorageManager, $this->mockedFormFactory, $definition ?? $this->createDefinition());
    }
}

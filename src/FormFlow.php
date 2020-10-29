<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Aeviiq\Collection\StringCollection;
use Aeviiq\FormFlow\Exception\InvalidArgumentException;
use Aeviiq\FormFlow\Exception\LogicException;
use Aeviiq\FormFlow\Exception\UnexpectedValueException;
use Aeviiq\FormFlow\Step\StepCollection;
use Aeviiq\FormFlow\Step\StepInterface;
use Aeviiq\StorageManager\StorageManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class FormFlow implements FormFlowInterface
{
    public const STORAGE_KEY_PREFIX = 'form_flow.storage.%s';

    /**
     * @var StorageManagerInterface
     */
    private $storageManager;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var Definition
     */
    private $definition;

    /**
     * @var Context|null
     */
    private $context;

    /**
     * @var FormInterface[]
     */
    private $forms = [];

    /**
     * @var string|null
     */
    private $storageKey;

    public function __construct(
        StorageManagerInterface $storageManager,
        FormFactoryInterface $formFactory,
        Definition $definition
    ) {
        $this->storageManager = $storageManager;
        $this->formFactory = $formFactory;
        $this->definition = $definition;
        $this->initialize();
    }

    public function isStarted(): bool
    {
        return null !== $this->context;
    }

    public function start(object $data): void
    {
        if ($this->isStarted()) {
            throw new LogicException('The flow is already started. In order to start it again, you need to reset() it.');
        }

        $this->checkExpectedInstance($data);
        $this->context = new Context($data, $this->definition->getSteps()->count());
    }

    public function getContext(): Context
    {
        if (null === $this->context) {
            throw new LogicException('The flow is missing it\'s context. Did you FormFlow#start() the flow?');
        }

        return $this->context;
    }

    public function reset(): void
    {
        $this->context = null;
        $this->storageManager->remove($this->getStorageKey());
        $this->forms = [];
    }

    public function getName(): string
    {
        return $this->definition->getName();
    }

    public function save(): void
    {
        if (!$this->isStarted()) {
            throw new LogicException('Unable to save the flow without a context. Did you FormFlow#start() the flow?');
        }

        $this->storageManager->save($this->getStorageKey(), $this->getContext());
    }

    public function getData(): object
    {
        return $this->getContext()->getData();
    }

    public function getGroups(): StringCollection
    {
        return $this->definition->getGroups();
    }

    public function getCurrentStepForm(): FormInterface
    {
        return $this->getFormByStep($this->getCurrentStep());
    }

    public function getCurrentStepNumber(): int
    {
        return $this->getContext()->getCurrentStepNumber();
    }

    public function getSteps(): StepCollection
    {
        return $this->definition->getSteps();
    }

    public function getCurrentStep(): StepInterface
    {
        return $this->getSteps()->getStepByNumber($this->getCurrentStepNumber());
    }

    public function getNextStep(): StepInterface
    {
        if (!$this->hasNextStep()) {
            throw new LogicException('There is no next step.');
        }

        return $this->getSteps()->getStepByNumber($this->getCurrentStepNumber() + 1);
    }

    public function hasNextStep(): bool
    {
        return !$this->getSteps()
            ->filterStepsGreaterThanNumber($this->getCurrentStepNumber())
            ->isEmpty();
    }

    public function getPreviousStep(): StepInterface
    {
        if (!$this->hasPreviousStep()) {
            throw new LogicException('There is no previous step.');
        }

        return $this->getSteps()->getStepByNumber($this->getCurrentStepNumber() - 1);
    }

    public function hasPreviousStep(): bool
    {
        return $this->getSteps()->hasStepWithNumber($this->getCurrentStepNumber() - 1);
    }

    public function getFirstStep(): StepInterface
    {
        $steps = $this->getSteps();

        return $steps->getStepByNumber(1);
    }

    public function getLastStep(): StepInterface
    {
        $steps = $this->getSteps();

        return $steps->getStepByNumber($steps->count());
    }

    public function getTransitionKey(): string
    {
        return \sprintf('flow_%s_%s', $this->getName(), 'transition');
    }

    public function getFormByStep(StepInterface $step): FormInterface
    {
        $stepNumber = $step->getNumber();
        if (!isset($this->forms[$stepNumber])) {
            $this->forms[$stepNumber] = $this->formFactory->create(
                $step->getFormType(),
                $this->getData()
            );
        }

        return $this->forms[$stepNumber];
    }

    public function getFormByStepNumber(int $stepNumber): FormInterface
    {
        return $this->getFormByStep($this->getSteps()->getStepByNumber($stepNumber));
    }

    public function setStorageKey(?string $storageKey): void
    {
        $this->storageKey = $storageKey;
    }

    public function getStorageKey(): string
    {
        if (null === $this->storageKey || '' === $this->storageKey) {
            return \sprintf(self::STORAGE_KEY_PREFIX, $this->getName());
        }

        return \sprintf(self::STORAGE_KEY_PREFIX . '.%s', $this->getName(), $this->storageKey);
    }

    private function checkExpectedInstance(object $data): void
    {
        $expectedInstance = $this->definition->getExpectedDataInstance();
        if (!($data instanceof $expectedInstance)) {
            throw new InvalidArgumentException(\sprintf('The data must be an instanceof %s, %s given.', $expectedInstance, \get_class($data)));
        }
    }

    private function initialize(): void
    {
        $key = $this->getStorageKey();
        if (!$this->storageManager->has($key)) {
            return;
        }

        $context = $this->storageManager->load($key);
        if (!($context instanceof Context)) {
            throw new UnexpectedValueException('The stored context is corrupted.');
        }

        $this->context = $context;
    }
}

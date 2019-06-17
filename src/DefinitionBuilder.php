<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Exception\LogicException;

final class DefinitionBuilder
{
    /**
     * @var Step[]
     */
    private $steps = [];

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $expectedDataInstance;

    public function build(): Definition
    {
        $this->validateState();
        $definition = new Definition($this->name, new StepCollection($this->steps), $this->expectedDataInstance);
        $this->reset();

        return $definition;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function addStep(string $formType, ?string $label = null, ?string $nextLabel = null, ?string $previousLabel = null): void
    {
        $number = $this->getStepCount();

        $this->steps[$number] = new PersistentStep($number, $formType, $label ?? 'Step', $nextLabel ?? 'Next', $previousLabel ?? 'Previous');
    }

    public function setRequiredInstanceOf(string $expectedDataInstance): void
    {
        $this->expectedDataInstance = $expectedDataInstance;
    }

    public function reset(): void
    {
        $this->steps = [];
        $this->instanceOfChecker = null;
        $this->name = null;
    }

    private function validateState(): void
    {
        if (empty($this->steps)) {
            throw new LogicException(\sprintf('Unable to build a definition without steps.'));
        }

        if (null === $this->expectedDataInstance) {
            throw new LogicException(\sprintf('Unable to build a definition without an expected data instance.'));
        }

        if (null === $this->name || '' === $this->name) {
            throw new LogicException(\sprintf('Unable to build a definition without a valid name.'));
        }
    }

    private function getStepCount(): int
    {
        return \count($this->steps) + 1;
    }
}

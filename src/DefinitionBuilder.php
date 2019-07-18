<?php declare(strict_types = 1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Step\Step;
use Aeviiq\FormFlow\Step\StepCollection;

final class DefinitionBuilder
{
    /**
     * @var Step[]
     */
    private $steps = [];

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $expectedDataInstance = '';

    public function build(): Definition
    {
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
        // TODO check if it is a valid form type.

        $this->steps[$number] = new Step($number, $formType, $label ?? 'Step', $nextLabel ?? 'Next', $previousLabel ?? 'Previous');
    }

    public function setRequiredInstanceOf(string $expectedDataInstance): void
    {
        $this->expectedDataInstance = $expectedDataInstance;
    }

    public function reset(): void
    {
        $this->steps = [];
        $this->instanceOfChecker = '';
        $this->name = '';
    }

    private function getStepCount(): int
    {
        return \count($this->steps) + 1;
    }
}

<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

use Aeviiq\FormFlow\Enum\Transition\Status;
use Aeviiq\FormFlow\Exception\TransitionException;

interface TransitionerInterface
{
    /**
     * Will check if the current HttpRequest has a transition request for the given flow.
     * This method should always be called before calling TransitionerInterface#transition().
     */
    public function hasTransitionRequest(FormFlowInterface $flow): bool;

    /**
     * Always ensure there is a transition request for the given flow by calling TransitionerInterface#hasTransitionRequest().
     * e.g.: if ($transitioner->canTransition($flow) && $status = $transitioner->transition($flow)) {
     *
     * Will call one of the follow methods, depending on the transition request.
     *  - TransitionerInterface#forwards()
     *  - TransitionerInterface#backwards()
     *      Multiple backwards transitions could be done in a single request. This will basically rewind the flow,
     *      firing the backwards events for each step.
     *
     *  - TransitionerInterface#complete()
     *  - TransitionerInterface#reset()
     *
     * Should return a failed status when an unsupported request is made.
     *
     * @throws TransitionException When the flow can not transition and this method is called.
     */
    public function transition(FormFlowInterface $flow): Status;

    /**
     * Attempts to transition the given flow forwards.
     *
     * Fail conditions:
     *  - A forwards transition could 'fail' when the submitted form is invalid.
     *  - A forwards transition could 'fail' when the fired TransitionEvent, gets blocked.
     *
     * This should always fire the following events (also see Aeviiq\FormFlow\FormFlowEvents):
     *     - Aeviiq\FormFlow\Event\TransitionEvent
     *     - Aeviiq\FormFlow\Event\TransitionedEvent
     *
     * Each of these events should fire a global, flow-specific and flow-step-specific event.
     *      e.g.:
     *          global:             form_flow.pre_backwards
     *          flow-specific:      form_flow.pre_backwards.flow_name
     *          flow-step-specific: form_flow.pre_backwards.flow_name.step_1
     *
     * When all was successful and this method can no longer fail, the current step (current as in, before this method was called)
     * should be marked as complete in the context of the given flow.
     *
     * After updating the flow context to the new step number, a FormFlowInterface#save() should be called,
     * to ensure the valid state is persisted.
     *
     * @throws TransitionException When the current step is the last step. In this case, TransitionerInterface#complete() should be called instead.
     */
    public function forwards(FormFlowInterface $flow): Status;

    /**
     * Attempts to transition the given flow backwards.
     *
     * Fail conditions:
     *  - A backwards transition could 'fail' when the fired TransitionEvent, gets blocked.
     *    * Unlike the TransitionerInterface#forwards(), an invalid form will not block this transition.
     *
     * This should always fire the following events (also see Aeviiq\FormFlow\FormFlowEvents):
     *     - Aeviiq\FormFlow\Event\TransitionEvent
     *     - Aeviiq\FormFlow\Event\TransitionedEvent
     *
     * Each of these events should fire a global, flow-specific and flow-step-specific event.
     *      e.g.:
     *          global:             form_flow.pre_backwards
     *          flow-specific:      form_flow.pre_backwards.flow_name
     *          flow-step-specific: form_flow.pre_backwards.flow_name.step_1
     *
     * When all was successful and this method can no longer fail, the current step (current as in, before this method was called)
     * should be marked as incomplete in the context of the given flow.
     *
     * After updating the flow context to the new step number, a FormFlowInterface#save() should be called,
     * to ensure the valid state is persisted.
     *
     * @throws TransitionException When the current step is the first step.
     */
    public function backwards(FormFlowInterface $flow): Status;

    /**
     * Attempts to complete the given flow.
     *
     * Fail conditions:
     *  - A complete transition could 'fail' when the submitted form is invalid.
     *  - A complete transition could 'fail' when the fired TransitionEvent, gets blocked.
     *  - A complete transition could 'fail' when one or more previous steps is not marked as completed in the context of the given flow.
     *
     * This should always fire the following events (also see Aeviiq\FormFlow\FormFlowEvents):
     *     - Aeviiq\FormFlow\Event\TransitionEvent
     *     - Aeviiq\FormFlow\Event\TransitionedEvent
     *
     * Each of these events should fire a global, flow-specific and flow-step-specific event.
     *      e.g.:
     *          global:             form_flow.pre_backwards
     *          flow-specific:      form_flow.pre_backwards.flow_name
     *          flow-step-specific: form_flow.pre_backwards.flow_name.step_1
     *
     * After updating the flow context to the new step number, a FormFlowInterface#save() should be called,
     * to ensure the valid state is persisted.
     *
     * @throws TransitionException When the current step is not the last step.
     */
    public function complete(FormFlowInterface $flow): Status;

    /**
     * Will reset the flow, removing any stored data and setting the current step back to the first one.
     *
     * This should always fire the following events (also see Aeviiq\FormFlow\FormFlowEvents):
     *     - Aeviiq\FormFlow\Event\ResetEvent
     */
    public function reset(FormFlowInterface $flow): Status;
}

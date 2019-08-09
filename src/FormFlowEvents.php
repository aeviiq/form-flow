<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

final class FormFlowEvents
{
    /**
     * This event is fired when the flow starts.
     *
     * Aeviiq\FormFlow\Event\StartEvent
     */
    public const STARTED = 'form_flow.started';

    /**
     * This event is fired when the flow starts to transition forwards.
     * The flow could be blocked inside any of these events.
     *
     * Typical use cases for the PRE_TRANSITION_FORWARDS event could be:
     *  - Additional (complex) validation on the submitted data.
     *      - This could prevent further progress by using the FormFlow#block() method.
     *  - When the next step is dependend on the processed submitted data.
     *
     * Aeviiq\FormFlow\Event\TransitionForwardsEvent
     */
    public const PRE_TRANSITION_FORWARDS = 'form_flow.pre_transition_forwards';

    /**
     *  This event is fired when the flow transitioned forwards.
     *
     * Aeviiq\FormFlow\Event\TransitionForwardsEvent
     */
    public const TRANSITIONED_FORWARDS = 'form_flow.transitioned_forwards';

    /**
     * TODO description
     */
    public const PRE_TRANSITION_BACKWARDS = 'form_flow.pre_transition_backwards';

    /**
     * TODO description
     */
    public const TRANSITIONED_BACKWARDS = 'form_flow.transitioned_backwards';

    /**
     * TODO description
     */
    public const PRE_COMPLETE = 'form_flow.pre_complete';

    /**
     * TODO description
     */
    public const COMPLETED = 'form_flow.completed';

    /**
     * TODO description
     */
    public const RESET = 'form_flow.reset';

    private function __construct()
    {
    }
}

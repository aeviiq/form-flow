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
     * This event is fired before the flow transitions forwards.
     * This is fired after form submit and validation.
     * The flow can still be blocked during this event.
     *
     * Typical use cases for this event could be:
     *  - Additional (complex) validation on the submitted data.
     *      - This could prevent further progress by using the FormFlow#block() method.
     *  - When the next step is dependend on the processed submitted data and requires additional logic.
     *  - To (temporarily) block any further process.
     *
     * Aeviiq\FormFlow\Event\TransitionForwardsEvent
     */
    public const PRE_TRANSITION_FORWARDS = 'form_flow.pre_transition_forwards';

    /**
     * This event is fired when the flow transitioned forwards.
     *
     * Aeviiq\FormFlow\Event\TransitionForwardsEvent
     */
    public const TRANSITIONED_FORWARDS = 'form_flow.transitioned_forwards';

    /**
     * This event is fired when the flow start to transition backwards.
     * The flow can still be blocked during this event.
     *
     * Aeviiq\FormFlow\Event\TransitionBackwardsEvent
     */
    public const PRE_TRANSITION_BACKWARDS = 'form_flow.pre_transition_backwards';

    /**
     * This event is fired when the flow transitioned backwards.
     *
     * Aeviiq\FormFlow\Event\TransitionBackwardsEvent
     */
    public const TRANSITIONED_BACKWARDS = 'form_flow.transitioned_backwards';

    /**
     * This event is fired when the flow transitioned forwards.
     *
     * Aeviiq\FormFlow\Event\CompleteEvent
     */
    public const PRE_COMPLETE = 'form_flow.pre_complete';

    /**
     * This event is fired when the flow has completed.
     *
     * Typical use cases for this event could be:
     *  - Persist the flow data.
     *  - Store the data as a placed order.
     *  - Send a confirmation mail to the user.
     *
     * Aeviiq\FormFlow\Event\CompleteEvent
     */
    public const COMPLETED = 'form_flow.completed';

    /**
     * This event is fired when the flow has reset.
     *
     * Aeviiq\FormFlow\Event\ResetEvent
     */
    public const RESET = 'form_flow.reset';

    private function __construct()
    {
    }
}

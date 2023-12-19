<?php

declare(strict_types=1);

namespace Aeviiq\FormFlow;

final class FormFlowEvents
{
    /**
     * This event is fired before the flow transitions forwards.
     * This is fired after form submit and validation.
     * The further progress can be blocked in this event.
     *
     * Typical use cases for this event could be:
     *  - Additional (complex) domain validation on the submitted data.
     *      - This could prevent further progress by using the TransitionEvent::blockTransition() method.
     *  - When the next step is dependend on the processed submitted data and requires additional logic.
     *  - To (temporarily) block any further process.
     *
     * Aeviiq\FormFlow\Event\TransitionEvent
     */
    public const PRE_FORWARDS = 'form_flow.pre_forwards';

    /**
     * This event is fired when the flow transitioned forwards.
     *
     * Aeviiq\FormFlow\Event\TransitionedEvent
     */
    public const POST_FORWARDS = 'form_flow.post_forwards';

    /**
     * This event is fired when the flow start to transition backwards.
     * The further progress can be blocked in this event.
     *
     * Aeviiq\FormFlow\Event\TransitionEvent
     */
    public const PRE_BACKWARDS = 'form_flow.pre_backwards';

    /**
     * This event is fired when the flow transitioned backwards.
     *
     * Aeviiq\FormFlow\Event\TransitionedEvent
     */
    public const POST_BACKWARDS = 'form_flow.post_backwards';

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
     * This event is fired when the flow is almost done transitioning forwards.
     * A step can only be skipped if it is the next step and not the last one.
     *
     * Typical use cases for this event could be:
     *  - Optional steps.
     *  - Store the data as a placed order.
     *  - Send a confirmation mail to the user.
     *
     * Aeviiq\FormFlow\Event\CompleteEvent
     */
    public const SKIP = 'form_flow.skip';

    /**
     * This event is fired when the flow has reset.
     *
     * Aeviiq\FormFlow\Event\ResetEvent
     */
    public const RESET = 'form_flow.reset';

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}

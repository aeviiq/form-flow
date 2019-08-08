<?php declare(strict_types=1);

namespace Aeviiq\FormFlow;

final class FormFlowEvents
{
    /**
     * TODO description
     */
    public const PRE_START = 'form_flow.pre_start';
    /**
     * TODO description
     */
    public const START = 'form_flow.start';
    /**
     * TODO description
     */
    public const POST_START = 'form_flow.post_start';
    /**
     * TODO description
     */
    public const PRE_TRANSITION_FORWARDS = 'form_flow.pre_transition_forwards';
    /**
     * TODO description
     */
    public const TRANSITION_FORWARDS = 'form_flow.transition_forwards';
    /**
     * TODO description
     */
    public const POST_TRANSITION_FORWARDS = 'form_flow.post_transition_forwards';
    /**
     * TODO description
     */
    public const PRE_TRANSITION_BACKWARDS = 'form_flow.pre_transition_backwards';
    /**
     * TODO description
     */
    public const TRANSITION_BACKWARDS = 'form_flow.transition_backwards';
    /**
     * TODO description
     */
    public const POST_TRANSITION_BACKWARDS = 'form_flow.post_transition_backwards';
    /**
     * TODO description
     */
    public const PRE_COMPLETE = 'form_flow.pre_complete';
    /**
     * TODO description
     */
    public const COMPLETE = 'form_flow.complete';
    /**
     * TODO description
     */
    public const POST_COMPLETE = 'form_flow.post_complete';
    /**
     * TODO description
     */
    public const PRE_RESET = 'form_flow.pre_reset';
    /**
     * TODO description
     */
    public const RESET = 'form_flow.reset';
    /**
     * TODO description
     */
    public const POST_RESET = 'form_flow.post_reset';
}

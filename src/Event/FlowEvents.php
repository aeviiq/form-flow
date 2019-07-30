<?php declare(strict_types=1);

namespace Aeviiq\FormFlow\Event;

final class FlowEvents
{
    public const PRE_START = 'aeviiq.form_flow.event.pre_start';
    public const POST_START = 'aeviiq.form_flow.event.post_start';
    public const PRE_NEXT = 'aeviiq.form_flow.event.pre_next';
    public const POST_NEXT = 'aeviiq.form_flow.event.post_next';
    public const PRE_PREVIOUS = 'aeviiq.form_flow.event.pre_previous';
    public const POST_PREVIOUS = 'aeviiq.form_flow.event.post_previous';
    public const PRE_COMPLETE = 'aeviiq.form_flow.event.pre_complete';
    public const POST_COMPLETE = 'aeviiq.form_flow.event.post_complete';
}

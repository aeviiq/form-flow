# Aeviiq Form Flow

## Installation
```
composer require aeviiq/form-flow
```

## Declaration
TODO

## Usage
TODO

#### Skip events
```php
final class SkipEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormFlowEvents::SKIP . '.example_flow.step_1' => ['foo'],
        ];
    }

    public function foo(SkipEvent $event): void
    {
        if ('bar' === $event->getFlow()->getData()->getBar()) {
            /**
             * Soft skipped steps will still be accessible by the user directly. Meaning that a backwards transition will
             * allow the user to edit the skipped skip.
             *
             * Typical use cases for this are prefilled billing information in a checkout flow.
             */
            $event->softSkip();
        } elseif ('not_bar' === $event->getFlow()->getData()->getBar()) {
            /**
             * Hard skipped steps will not be accessible by the user directly. Meaning that a backwards transition will
             * skip the step as well.
             *
             * Typical use cases for this are optional steps, depending on the choices of the user.
             *
             * A hard skipped skip can be reached again, if the defined condition which caused it to be hard skipped, changes.
             */
            $event->hardSkip();
        }
    }
}
```


<?php

declare(strict_types=1);

namespace Thenpingme;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Lorisleiva\CronTranslator\CronParsingException;
use Lorisleiva\CronTranslator\CronTranslator;
use ReflectionClass;
use ReflectionFunction;
use Thenpingme\Collections\ScheduledTaskCollection;

class Thenpingme
{
    /** @var string */
    public const VERSION = '3.3.0';

    /** @var int */
    public const STORE_OUTPUT = 1;

    /** @var int */
    public const STORE_OUTPUT_ON_SUCCESS = 2;

    /** @var int */
    public const STORE_OUTPUT_ON_FAILURE = 4;

    /** @var int */
    public const STORE_OUTPUT_IF_PRESENT = 8;

    public function generateSigningKey(): string
    {
        return Str::random(512);
    }

    public function scheduledTasks(): ScheduledTaskCollection
    {
        return with(app(Schedule::class), function (Schedule $scheduler) {
            return ScheduledTaskCollection::make($scheduler->events())
                ->filter(function (Event $event) {
                    return App::environment($event->environments)
                        || empty($event->environments);
                })
                ->transform(function (Event $event) {
                    $this->fingerprintTask($event);

                    return $event;
                });
        });
    }

    public function translateExpression(string $expression): string
    {
        try {
            return CronTranslator::translate($expression);
        } catch (CronParsingException) {
            return $expression;
        }
    }

    public function fingerprintTask(Event $event): string
    {
        if ($event instanceof CallbackEvent) {
            return $this->fingerprintCallbackEvent($event);
        }

        return sprintf('thenpingme:%s', sha1(trim(
            "{$event->expression}.{$event->command}.{$event->description}",
            '.'
        )));
    }

    public function fingerprintCallbackEvent(CallbackEvent &$event): string
    {
        $callbackMutex = with(new ReflectionClass($event), function (ReflectionClass $class) use (&$event): string {
            $callback = $class->getProperty('callback');
            $callback->setAccessible(true);

            $command = $callback->getValue($event);

            if (is_string($command)) {
                return $command;
            }

            if (! is_callable($command)) {
                return md5(serialize($command));
            }

            if (is_object($command) && ($class = $command::class) !== 'Closure') {
                return $class;
            }

            tap(new ReflectionFunction($command), function (ReflectionFunction $function) use (&$event) {
                /* @phpstan-ignore-next-line */
                $event->extra = [
                    'file' => $function->getClosureScopeClass()->getName(),
                    'line' => "{$function->getStartLine()} to {$function->getEndLine()}",
                ];
            });

            return '';
        });

        return sprintf('thenpingme:%s', sha1(
            str_replace('..', '.', "{$event->expression}.{$callbackMutex}.{$event->description}")
        ));
    }

    public function version(): string
    {
        return static::VERSION;
    }
}

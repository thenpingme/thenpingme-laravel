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
    public const VERSION = '3.0.0';

    public static array $defaultSettings = [];

    public function generateSigningKey(): string
    {
        return Str::random(512);
    }

    public static function defaults(
        ?int $grace_period = null,
        ?int $allowed_run_time = null,
        ?int $notify_after_consecutive_alerts = null
    ) {
        if (func_num_args() === 0) {
            return static::$defaultSettings;
        }

        static::$defaultSettings = [
            'grace_period' => $grace_period,
            'allowed_run_time' => $allowed_run_time,
            'notify_after_consecutive_alerts' => $notify_after_consecutive_alerts,
        ];
    }

    public static function default(string $setting): mixed
    {
        return data_get(static::$defaultSettings, $setting);
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

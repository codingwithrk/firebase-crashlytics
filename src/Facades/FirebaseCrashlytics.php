<?php

namespace Codingwithrk\FirebaseCrashlytics\Facades;

use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * @method static array|null recordError(Throwable $exception, bool $fatal = false)
 * @method static array|null recordException(Throwable $exception, bool $fatal = false)
 * @method static array|null log(string $message)
 * @method static array|null setUserId(string $identifier)
 * @method static array|null setCustomKey(string $key, string|int|float|bool $value)
 * @method static void setCustomKeys(array $keys)
 * @method static array|null crash(?string $reason = null)
 * @method static array|null setCrashlyticsCollectionEnabled(bool $enabled = true)
 * @method static bool isCrashlyticsCollectionEnabled()
 * @method static bool checkForUnsentReports()
 * @method static array|null sendUnsentReports()
 * @method static array|null deleteUnsentReports()
 * @method static bool didCrashOnPreviousExecution()
 *
 * @see \Codingwithrk\FirebaseCrashlytics\FirebaseCrashlytics
 */
class FirebaseCrashlytics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Codingwithrk\FirebaseCrashlytics\FirebaseCrashlytics::class;
    }
}

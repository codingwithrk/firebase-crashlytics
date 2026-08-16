<?php

namespace Codingwithrk\FirebaseCrashlytics;

use Throwable;

class FirebaseCrashlytics
{
    /**
     * Record a caught exception/error with Crashlytics. It is grouped as a
     * non-fatal issue by default; pass $fatal = true to flag it as fatal-style
     * in the console so it stands out.
     */
    public function recordError(Throwable $exception, bool $fatal = false): ?array
    {
        return $this->call('RecordError', [
            'message' => $exception->getMessage() ?: $exception::class,
            'className' => $exception::class,
            'stackTrace' => $exception->getTraceAsString(),
            'fatal' => $fatal,
        ]);
    }

    /**
     * Alias for recordError(), matching the naming used by the native
     * Crashlytics SDKs (recordException on Android, record(error:) on iOS).
     */
    public function recordException(Throwable $exception, bool $fatal = false): ?array
    {
        return $this->recordError($exception, $fatal);
    }

    /**
     * Add a custom log message. Logs are attached to the next crash/error
     * report as breadcrumbs but are not sent on their own.
     */
    public function log(string $message): ?array
    {
        return $this->call('Log', ['message' => $message]);
    }

    /**
     * Associate a user identifier with subsequent crash reports.
     */
    public function setUserId(string $identifier): ?array
    {
        return $this->call('SetUserId', ['identifier' => $identifier]);
    }

    /**
     * Set a custom key/value pair attached to subsequent crash reports.
     */
    public function setCustomKey(string $key, string|int|float|bool $value): ?array
    {
        return $this->call('SetCustomKey', [
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Convenience helper to set several custom keys at once.
     *
     * @param  array<string, string|int|float|bool>  $keys
     */
    public function setCustomKeys(array $keys): void
    {
        foreach ($keys as $key => $value) {
            $this->setCustomKey((string) $key, $value);
        }
    }

    /**
     * Force an immediate, unrecoverable native crash. Use this only to verify
     * your Crashlytics integration - it will terminate the running app.
     */
    public function crash(?string $reason = null): ?array
    {
        return $this->call('Crash', $reason !== null ? ['reason' => $reason] : []);
    }

    /**
     * Enable or disable automatic crash report collection at runtime.
     */
    public function setCrashlyticsCollectionEnabled(bool $enabled = true): ?array
    {
        return $this->call('SetCollectionEnabled', ['enabled' => $enabled]);
    }

    /**
     * Whether automatic crash report collection is currently enabled.
     */
    public function isCrashlyticsCollectionEnabled(): bool
    {
        return (bool) ($this->call('IsCollectionEnabled')['enabled'] ?? false);
    }

    /**
     * Check whether there are any unsent crash reports still on disk.
     */
    public function checkForUnsentReports(): bool
    {
        return (bool) ($this->call('CheckForUnsentReports')['hasUnsentReports'] ?? false);
    }

    /**
     * Upload any unsent crash reports to Firebase. Only needed when automatic
     * collection is disabled, or after prompting the user for consent.
     */
    public function sendUnsentReports(): ?array
    {
        return $this->call('SendUnsentReports');
    }

    /**
     * Delete any unsent crash reports from disk instead of uploading them.
     */
    public function deleteUnsentReports(): ?array
    {
        return $this->call('DeleteUnsentReports');
    }

    /**
     * Whether the app crashed during its previous execution.
     */
    public function didCrashOnPreviousExecution(): bool
    {
        return (bool) ($this->call('DidCrashOnPreviousExecution')['didCrash'] ?? false);
    }

    /**
     * Call a FirebaseCrashlytics bridge function and decode its response payload.
     */
    protected function call(string $method, array $params = []): ?array
    {
        if (! function_exists('nativephp_call')) {
            return null;
        }

        $result = nativephp_call("FirebaseCrashlytics.{$method}", json_encode($params));

        if (! $result) {
            return null;
        }

        $decoded = json_decode($result, true);

        return $decoded['data'] ?? null;
    }
}

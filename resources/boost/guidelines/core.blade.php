## codingwithrk/firebase-crashlytics

A NativePHP plugin that wraps Firebase Crashlytics for Android and iOS, modeled after the Flutter
`firebase_crashlytics` package. It records fatal/non-fatal errors, breadcrumb logs, user identifiers, and custom
keys, and reports them to the Firebase console.

### Prerequisite: Firebase must already be configured

This plugin reuses the Firebase setup NativePHP already uses for Push Notifications. `google-services.json` and
`GoogleService-Info.plist` must be placed in the root of the app (NativePHP wires them into the native projects
automatically), and Crashlytics must be enabled for the project in the Firebase console.

### Installation

```bash
# Install the package
composer require codingwithrk/firebase-crashlytics

# Publish the plugins provider (first time only)
php artisan vendor:publish --tag=nativephp-plugins-provider

# Register the plugin (adds \Codingwithrk\FirebaseCrashlytics\FirebaseCrashlyticsServiceProvider::class)
php artisan native:plugin:register codingwithrk/firebase-crashlytics

# Verify registration
php artisan native:plugin:list
```

### PHP Usage (Livewire/Blade)

Use the `FirebaseCrashlytics` facade:

@verbatim
<code-snippet name="Recording errors with FirebaseCrashlytics" lang="php">
use Codingwithrk\FirebaseCrashlytics\Facades\FirebaseCrashlytics;

try {
    $this->riskyOperation();
} catch (\Throwable $e) {
    // Non-fatal by default
    FirebaseCrashlytics::recordError($e);

    // Or flag it as fatal-style so it stands out in the console
    FirebaseCrashlytics::recordError($e, fatal: true);
}

// recordException() is an alias for recordError()
FirebaseCrashlytics::recordException($e);
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Breadcrumbs, user id, and custom keys" lang="php">
use Codingwithrk\FirebaseCrashlytics\Facades\FirebaseCrashlytics;

FirebaseCrashlytics::log('User reached checkout');
FirebaseCrashlytics::setUserId((string) $user->id);
FirebaseCrashlytics::setCustomKey('subscription_tier', 'pro');
FirebaseCrashlytics::setCustomKeys([
    'subscription_tier' => 'pro',
    'cart_items' => 3,
]);
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Collection control and unsent reports" lang="php">
use Codingwithrk\FirebaseCrashlytics\Facades\FirebaseCrashlytics;

// Enable/disable automatic collection at runtime (e.g. after consent)
FirebaseCrashlytics::setCrashlyticsCollectionEnabled(true);
$enabled = FirebaseCrashlytics::isCrashlyticsCollectionEnabled();

if (FirebaseCrashlytics::checkForUnsentReports()) {
    FirebaseCrashlytics::sendUnsentReports();
    // or: FirebaseCrashlytics::deleteUnsentReports();
}

if (FirebaseCrashlytics::didCrashOnPreviousExecution()) {
    // e.g. show a "sorry, we crashed" message
}

// Force a real, unrecoverable crash to verify the integration end-to-end
FirebaseCrashlytics::crash();
</code-snippet>
@endverbatim

### Available Methods

- `FirebaseCrashlytics::recordError(\Throwable $exception, bool $fatal = false)`: Records a caught exception
- `FirebaseCrashlytics::recordException(\Throwable $exception, bool $fatal = false)`: Alias for `recordError()`
- `FirebaseCrashlytics::log(string $message)`: Adds a breadcrumb log
- `FirebaseCrashlytics::setUserId(string $identifier)`: Associates a user id with subsequent reports
- `FirebaseCrashlytics::setCustomKey(string $key, string|int|float|bool $value)`: Sets a custom key/value pair
- `FirebaseCrashlytics::setCustomKeys(array $keys)`: Sets several custom keys at once
- `FirebaseCrashlytics::crash(?string $reason = null)`: Forces an immediate, unrecoverable crash (testing only)
- `FirebaseCrashlytics::setCrashlyticsCollectionEnabled(bool $enabled = true)`: Enables/disables collection
- `FirebaseCrashlytics::isCrashlyticsCollectionEnabled()`: Returns whether collection is enabled
- `FirebaseCrashlytics::checkForUnsentReports()`: Returns whether unsent reports exist on disk
- `FirebaseCrashlytics::sendUnsentReports()`: Uploads unsent reports
- `FirebaseCrashlytics::deleteUnsentReports()`: Deletes unsent reports instead of uploading them
- `FirebaseCrashlytics::didCrashOnPreviousExecution()`: Returns whether the app crashed last run

This plugin does not dispatch any native-to-PHP events - every method is a direct, synchronous bridge call.

### JavaScript Usage (Vue/React/Inertia)

@verbatim
<code-snippet name="Using FirebaseCrashlytics in JavaScript" lang="javascript">
import { FirebaseCrashlytics } from '@codingwithrk/firebase-crashlytics';

try {
    riskyOperation();
} catch (error) {
    await FirebaseCrashlytics.recordError(error);
}

await FirebaseCrashlytics.log('User reached checkout');
await FirebaseCrashlytics.setUserId('user-123');
await FirebaseCrashlytics.setCustomKey('subscription_tier', 'pro');
await FirebaseCrashlytics.setCustomKeys({ subscription_tier: 'pro', cart_items: 3 });

const hasReports = await FirebaseCrashlytics.checkForUnsentReports();
if (hasReports) {
    await FirebaseCrashlytics.sendUnsentReports();
}

const crashedLastRun = await FirebaseCrashlytics.didCrashOnPreviousExecution();

await FirebaseCrashlytics.crash();
</code-snippet>
@endverbatim

# FirebaseCrashlytics Plugin for NativePHP Mobile

A [NativePHP Mobile](https://nativephp.com) plugin that wraps [Firebase Crashlytics](https://firebase.google.com/docs/crashlytics)
for Android and iOS. It records fatal and non-fatal errors, breadcrumb logs, user identifiers, and custom keys, and reports them to
the Firebase console.

## Requirements

This plugin relies on Firebase already being configured for your app (the same setup used by NativePHP's
[Push Notifications](https://nativephp.com/docs/mobile/4/digging-deeper/push-notifications)):

1. Create a project at [firebase.google.com](https://firebase.google.com/).
2. Download `google-services.json` (Android) and `GoogleService-Info.plist` (iOS).
3. Place both files in the root of your NativePHP application. NativePHP wires them into the native projects
   automatically - you don't need to follow Firebase's own setup instructions.
4. In the Firebase console, open **Crashlytics** and enable it for your project.

## Installation

```bash
composer require codingwithrk/firebase-crashlytics

# Publish the plugins provider (first time only)
php artisan vendor:publish --tag=nativephp-plugins-provider

# Register the plugin
php artisan native:plugin:register codingwithrk/firebase-crashlytics

# Verify registration
php artisan native:plugin:list
```

This adds `\Codingwithrk\FirebaseCrashlytics\FirebaseCrashlyticsServiceProvider::class` to your `plugins()` array.

## Usage

```php
use Codingwithrk\FirebaseCrashlytics\Facades\FirebaseCrashlytics;

// Record a caught exception (non-fatal by default)
try {
    $this->riskyOperation();
} catch (\Throwable $e) {
    FirebaseCrashlytics::recordError($e);

    // or, if it should stand out as a fatal-style issue in the console
    FirebaseCrashlytics::recordError($e, fatal: true);
}

// recordException() is an alias for recordError(), matching the native SDKs' naming
FirebaseCrashlytics::recordException($e);

// Add a breadcrumb log, included in the next crash/error report
FirebaseCrashlytics::log('User reached checkout');

// Identify the current user in crash reports
FirebaseCrashlytics::setUserId((string) $user->id);

// Attach custom context to crash reports
FirebaseCrashlytics::setCustomKey('subscription_tier', 'pro');
FirebaseCrashlytics::setCustomKeys([
    'subscription_tier' => 'pro',
    'cart_items' => 3,
]);

// Enable/disable automatic collection at runtime (e.g. after consent)
FirebaseCrashlytics::setCrashlyticsCollectionEnabled(true);
$enabled = FirebaseCrashlytics::isCrashlyticsCollectionEnabled();

// Manage unsent reports when automatic collection is disabled
if (FirebaseCrashlytics::checkForUnsentReports()) {
    FirebaseCrashlytics::sendUnsentReports();
    // or: FirebaseCrashlytics::deleteUnsentReports();
}

// Check whether the app crashed last time it ran
if (FirebaseCrashlytics::didCrashOnPreviousExecution()) {
    // e.g. show a "sorry, we crashed" message
}

// Force a real, unrecoverable crash to verify your integration end-to-end
FirebaseCrashlytics::crash();
```

## JavaScript Usage (Vue/React/Inertia)

```javascript
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
```

## API Reference

| Method | Description |
|---|---|
| `recordError(Throwable $exception, bool $fatal = false)` | Records a caught exception as a (non-)fatal issue |
| `recordException(Throwable $exception, bool $fatal = false)` | Alias for `recordError()` |
| `log(string $message)` | Adds a breadcrumb log attached to the next report |
| `setUserId(string $identifier)` | Associates a user identifier with subsequent reports |
| `setCustomKey(string $key, string\|int\|float\|bool $value)` | Sets a single custom key/value pair |
| `setCustomKeys(array $keys)` | Sets several custom keys at once |
| `crash(?string $reason = null)` | Forces an immediate, unrecoverable native crash (for testing) |
| `setCrashlyticsCollectionEnabled(bool $enabled = true)` | Enables/disables automatic crash collection |
| `isCrashlyticsCollectionEnabled()` | Returns whether automatic collection is enabled |
| `checkForUnsentReports()` | Returns whether unsent reports exist on disk |
| `sendUnsentReports()` | Uploads unsent reports to Firebase |
| `deleteUnsentReports()` | Deletes unsent reports instead of uploading them |
| `didCrashOnPreviousExecution()` | Returns whether the app crashed last run |

## Notes

- Crashlytics is **enabled by default**. To ask for consent first, disable automatic collection in your app's
  config, call `setCrashlyticsCollectionEnabled(false)` on boot, and re-enable it once the user opts in.
- `crash()` intentionally terminates the running app - only call it to verify reports are reaching the Firebase
  console (it's the same technique used by Firebase's own "force a crash" testing guides).
- On Android, this plugin doesn't apply the Crashlytics Gradle plugin, so build ID/`mapping.txt` upload for fully
  deobfuscated stack traces isn't automated. The SDK itself works out of the box; if you need automatic mapping
  file upload you'll need to configure that separately in your app's native project.

## License

MIT

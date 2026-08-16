<?php

namespace Codingwithrk\FirebaseCrashlytics\Commands;

use Native\Mobile\Plugins\Commands\NativePluginHookCommand;

/**
 * pre_compile hook for FirebaseCrashlytics.
 *
 * Modern Firebase Crashlytics requires the `com.google.firebase.crashlytics`
 * Gradle plugin to be *applied* to the `:app` module - without it, the SDK
 * throws `IllegalStateException: The Crashlytics build ID is missing` from
 * Firebase's own startup ContentProvider and crashes the app on every launch.
 *
 * The plugin manifest's `android.gradle_plugins` field only adds the plugin's
 * classpath to the root `build.gradle.kts` (with `apply false`) - NativePHP
 * doesn't auto-apply arbitrary plugins at the app module level (it only does
 * this itself for `com.google.gms.google-services`). So this hook applies it
 * directly, idempotently, right before Gradle compiles.
 *
 * @see \Native\Mobile\Plugins\Commands\NativePluginHookCommand
 */
class ApplyGradlePluginCommand extends NativePluginHookCommand
{
    protected $signature = 'nativephp:firebase-crashlytics:apply-gradle-plugin';

    protected $description = 'Apply the Firebase Crashlytics Gradle plugin to the app module';

    private const PLUGIN_ID = 'com.google.firebase.crashlytics';

    public function handle(): int
    {
        if (! $this->isAndroid()) {
            return self::SUCCESS;
        }

        $path = $this->buildPath().'/app/build.gradle.kts';

        if (! file_exists($path)) {
            $this->warn("Could not find app/build.gradle.kts at {$path}, skipping.");

            return self::SUCCESS;
        }

        $content = file_get_contents($path);

        if (str_contains($content, self::PLUGIN_ID)) {
            $this->info('Firebase Crashlytics Gradle plugin already applied.');

            return self::SUCCESS;
        }

        $anchor = 'apply(plugin = "com.google.gms.google-services")';

        if (str_contains($content, $anchor)) {
            // Piggyback on the existing "only if google-services.json exists" guard.
            $content = str_replace(
                $anchor,
                $anchor.PHP_EOL.'    apply(plugin = "'.self::PLUGIN_ID.'")',
                $content
            );
        } else {
            // Fallback: no google-services apply block found (unexpected core
            // layout) - insert our own self-contained, guarded apply after
            // the `plugins { ... }` block.
            $block = PHP_EOL
                .'val googleServicesJsonForCrashlytics = file("google-services.json")'.PHP_EOL
                .'if (googleServicesJsonForCrashlytics.exists()) {'.PHP_EOL
                .'    apply(plugin = "'.self::PLUGIN_ID.'")'.PHP_EOL
                .'}'.PHP_EOL;

            $content = preg_replace('/^plugins\s*\{.*?\n\}\n/ms', '$0'.$block, $content, 1);
        }

        file_put_contents($path, $content);

        $this->info('Applied Firebase Crashlytics Gradle plugin to app/build.gradle.kts');

        return self::SUCCESS;
    }
}

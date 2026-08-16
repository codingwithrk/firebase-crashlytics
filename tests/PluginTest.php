<?php

/**
 * Plugin validation tests for FirebaseCrashlytics.
 *
 * Run with: ./vendor/bin/pest
 */

beforeEach(function () {
    $this->pluginPath = dirname(__DIR__);
    $this->manifestPath = $this->pluginPath . '/nativephp.json';
});

describe('Plugin Manifest', function () {
    it('has a valid nativephp.json file', function () {
        expect(file_exists($this->manifestPath))->toBeTrue();

        $content = file_get_contents($this->manifestPath);
        $manifest = json_decode($content, true);

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
    });

    it('has required fields', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest)->toHaveKeys(['name', 'namespace', 'bridge_functions']);
        expect($manifest['name'])->toBe('codingwithrk/firebase-crashlytics');
        expect($manifest['namespace'])->toBe('FirebaseCrashlytics');
    });

    it('has valid bridge functions', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['bridge_functions'])->toBeArray();
        expect($manifest['bridge_functions'])->toHaveCount(11);

        foreach ($manifest['bridge_functions'] as $function) {
            expect($function)->toHaveKeys(['name']);
            expect(array_intersect_key($function, array_flip(['android', 'ios'])))->not->toBeEmpty();
            expect($function['name'])->toStartWith('FirebaseCrashlytics.');
        }
    });

    it('has valid marketplace metadata', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        if (isset($manifest['keywords'])) {
            expect($manifest['keywords'])->toBeArray();
        }

        if (isset($manifest['category'])) {
            expect($manifest['category'])->toBeString();
        }

        if (isset($manifest['platforms'])) {
            expect($manifest['platforms'])->toBeArray();
            foreach ($manifest['platforms'] as $platform) {
                expect($platform)->toBeIn(['android', 'ios']);
            }
        }
    });

    it('declares the required Android and iOS dependencies', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['android']['dependencies']['implementation'] ?? [])
            ->toContain('com.google.firebase:firebase-crashlytics');

        $pods = collect($manifest['ios']['dependencies']['pods'] ?? [])->pluck('name');
        expect($pods)->toContain('Firebase/Crashlytics');
    });
});

describe('Native Code', function () {
    it('has Android Kotlin file', function () {
        $kotlinFile = $this->pluginPath . '/resources/android/FirebaseCrashlyticsFunctions.kt';

        expect(file_exists($kotlinFile))->toBeTrue();

        $content = file_get_contents($kotlinFile);
        expect($content)->toContain('package com.codingwithrk.plugins.firebase_crashlytics');
        expect($content)->toContain('object FirebaseCrashlyticsFunctions');
        expect($content)->toContain('BridgeFunction');
    });

    it('has iOS Swift file', function () {
        $swiftFile = $this->pluginPath . '/resources/ios/FirebaseCrashlyticsFunctions.swift';

        expect(file_exists($swiftFile))->toBeTrue();

        $content = file_get_contents($swiftFile);
        expect($content)->toContain('enum FirebaseCrashlyticsFunctions');
        expect($content)->toContain('BridgeFunction');
    });

    it('has matching bridge function classes in native code', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        $kotlinFile = $this->pluginPath . '/resources/android/FirebaseCrashlyticsFunctions.kt';
        $swiftFile = $this->pluginPath . '/resources/ios/FirebaseCrashlyticsFunctions.swift';

        $kotlinContent = file_get_contents($kotlinFile);
        $swiftContent = file_get_contents($swiftFile);

        foreach ($manifest['bridge_functions'] as $function) {
            if (isset($function['android'])) {
                $parts = explode('.', $function['android']);
                $className = end($parts);
                expect($kotlinContent)->toContain("class {$className}");
            }

            if (isset($function['ios'])) {
                $parts = explode('.', $function['ios']);
                $className = end($parts);
                expect($swiftContent)->toContain("class {$className}");
            }
        }
    });
});

describe('PHP Classes', function () {
    it('has service provider', function () {
        $file = $this->pluginPath . '/src/FirebaseCrashlyticsServiceProvider.php';
        expect(file_exists($file))->toBeTrue();

        $content = file_get_contents($file);
        expect($content)->toContain('namespace Codingwithrk\FirebaseCrashlytics');
        expect($content)->toContain('class FirebaseCrashlyticsServiceProvider');
    });

    it('has facade', function () {
        $file = $this->pluginPath . '/src/Facades/FirebaseCrashlytics.php';
        expect(file_exists($file))->toBeTrue();

        $content = file_get_contents($file);
        expect($content)->toContain('namespace Codingwithrk\FirebaseCrashlytics\Facades');
        expect($content)->toContain('class FirebaseCrashlytics extends Facade');
    });

    it('has main implementation class', function () {
        $file = $this->pluginPath . '/src/FirebaseCrashlytics.php';
        expect(file_exists($file))->toBeTrue();

        $content = file_get_contents($file);
        expect($content)->toContain('namespace Codingwithrk\FirebaseCrashlytics');
        expect($content)->toContain('class FirebaseCrashlytics');
    });

    it('exposes the full Crashlytics-style API on the main class', function () {
        $file = $this->pluginPath . '/src/FirebaseCrashlytics.php';
        $content = file_get_contents($file);

        $methods = [
            'recordError', 'recordException', 'log', 'setUserId', 'setCustomKey',
            'setCustomKeys', 'crash', 'setCrashlyticsCollectionEnabled',
            'isCrashlyticsCollectionEnabled', 'checkForUnsentReports',
            'sendUnsentReports', 'deleteUnsentReports', 'didCrashOnPreviousExecution',
        ];

        foreach ($methods as $method) {
            expect($content)->toContain("function {$method}(");
        }
    });
});

describe('Composer Configuration', function () {
    it('has valid composer.json', function () {
        $composerPath = $this->pluginPath . '/composer.json';
        expect(file_exists($composerPath))->toBeTrue();

        $content = file_get_contents($composerPath);
        $composer = json_decode($content, true);

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
        expect($composer['type'])->toBe('nativephp-plugin');
        expect($composer['extra']['nativephp']['manifest'])->toBe('nativephp.json');
        expect($composer['require']['nativephp/mobile'])->toBe('^4.0');
    });
});

/**
 * FirebaseCrashlytics Plugin for NativePHP Mobile
 *
 * @example
 * import { FirebaseCrashlytics } from '@codingwithrk/firebase-crashlytics';
 *
 * try {
 *   riskyOperation();
 * } catch (error) {
 *   await FirebaseCrashlytics.recordError(error);
 * }
 *
 * await FirebaseCrashlytics.log('User reached checkout');
 * await FirebaseCrashlytics.setUserId('user-123');
 * await FirebaseCrashlytics.setCustomKey('screen', 'checkout');
 */

const baseUrl = '/_native/api/call';

/**
 * Internal bridge call function
 * @private
 */
async function bridgeCall(method, params = {}) {
    const response = await fetch(baseUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ method, params })
    });

    const result = await response.json();

    if (result.status === 'error') {
        throw new Error(result.message || 'Native call failed');
    }

    const nativeResponse = result.data;
    if (nativeResponse && nativeResponse.data !== undefined) {
        return nativeResponse.data;
    }

    return nativeResponse;
}

/**
 * Record a caught error/exception with Crashlytics.
 * @param {Error|string} error - An Error instance or a plain message string
 * @param {Object} [options]
 * @param {boolean} [options.fatal=false] - Flag the issue as fatal-style
 * @param {string} [options.className] - Override the reported error class/name
 * @returns {Promise<{recorded: boolean}>}
 */
export async function recordError(error, options = {}) {
    const isError = error instanceof Error;

    return bridgeCall('FirebaseCrashlytics.RecordError', {
        message: isError ? error.message : String(error),
        className: options.className ?? (isError ? error.name : 'JavaScript.Error'),
        stackTrace: isError ? error.stack : undefined,
        fatal: options.fatal ?? false
    });
}

/**
 * Alias for recordError(), matching the naming used by the native SDKs.
 * @param {Error|string} error
 * @param {Object} [options]
 * @returns {Promise<{recorded: boolean}>}
 */
export async function recordException(error, options = {}) {
    return recordError(error, options);
}

/**
 * Add a custom breadcrumb log message attached to the next report.
 * @param {string} message
 * @returns {Promise<{logged: boolean}>}
 */
export async function log(message) {
    return bridgeCall('FirebaseCrashlytics.Log', { message });
}

/**
 * Associate a user identifier with subsequent crash reports.
 * @param {string} identifier
 * @returns {Promise<{set: boolean}>}
 */
export async function setUserId(identifier) {
    return bridgeCall('FirebaseCrashlytics.SetUserId', { identifier });
}

/**
 * Set a single custom key/value pair attached to subsequent crash reports.
 * @param {string} key
 * @param {string|number|boolean} value
 * @returns {Promise<{set: boolean}>}
 */
export async function setCustomKey(key, value) {
    return bridgeCall('FirebaseCrashlytics.SetCustomKey', { key, value });
}

/**
 * Set several custom keys at once.
 * @param {Object<string, string|number|boolean>} keys
 * @returns {Promise<void>}
 */
export async function setCustomKeys(keys) {
    await Promise.all(
        Object.entries(keys).map(([key, value]) => setCustomKey(key, value))
    );
}

/**
 * Force an immediate, unrecoverable native crash. Use this only to verify
 * your Crashlytics integration - it will terminate the running app.
 * @param {string} [reason]
 * @returns {Promise<{crashing: boolean}>}
 */
export async function crash(reason) {
    return bridgeCall('FirebaseCrashlytics.Crash', reason ? { reason } : {});
}

/**
 * Enable or disable automatic crash report collection at runtime.
 * @param {boolean} [enabled=true]
 * @returns {Promise<{enabled: boolean}>}
 */
export async function setCrashlyticsCollectionEnabled(enabled = true) {
    return bridgeCall('FirebaseCrashlytics.SetCollectionEnabled', { enabled });
}

/**
 * Whether automatic crash report collection is currently enabled.
 * @returns {Promise<boolean>}
 */
export async function isCrashlyticsCollectionEnabled() {
    const result = await bridgeCall('FirebaseCrashlytics.IsCollectionEnabled');
    return Boolean(result?.enabled);
}

/**
 * Check whether there are any unsent crash reports still on disk.
 * @returns {Promise<boolean>}
 */
export async function checkForUnsentReports() {
    const result = await bridgeCall('FirebaseCrashlytics.CheckForUnsentReports');
    return Boolean(result?.hasUnsentReports);
}

/**
 * Upload any unsent crash reports to Firebase.
 * @returns {Promise<{sent: boolean}>}
 */
export async function sendUnsentReports() {
    return bridgeCall('FirebaseCrashlytics.SendUnsentReports');
}

/**
 * Delete any unsent crash reports from disk instead of uploading them.
 * @returns {Promise<{deleted: boolean}>}
 */
export async function deleteUnsentReports() {
    return bridgeCall('FirebaseCrashlytics.DeleteUnsentReports');
}

/**
 * Whether the app crashed during its previous execution.
 * @returns {Promise<boolean>}
 */
export async function didCrashOnPreviousExecution() {
    const result = await bridgeCall('FirebaseCrashlytics.DidCrashOnPreviousExecution');
    return Boolean(result?.didCrash);
}

/**
 * FirebaseCrashlytics namespace object
 */
export const FirebaseCrashlytics = {
    recordError,
    recordException,
    log,
    setUserId,
    setCustomKey,
    setCustomKeys,
    crash,
    setCrashlyticsCollectionEnabled,
    isCrashlyticsCollectionEnabled,
    checkForUnsentReports,
    sendUnsentReports,
    deleteUnsentReports,
    didCrashOnPreviousExecution
};

export default FirebaseCrashlytics;

package com.codingwithrk.plugins.firebase_crashlytics

import android.os.Handler
import android.os.Looper
import androidx.fragment.app.FragmentActivity
import com.google.android.gms.tasks.Tasks
import com.google.firebase.crashlytics.FirebaseCrashlytics
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse
import java.util.concurrent.TimeUnit
import java.util.concurrent.TimeoutException

object FirebaseCrashlyticsFunctions {

    /** How long we're willing to block waiting on a Crashlytics Task before giving up. */
    private const val AWAIT_TIMEOUT_SECONDS = 8L

    /**
     * Records a caught exception with Crashlytics as a (non-)fatal issue.
     *
     * Parameters:
     * - message: String - Exception message
     * - className: String? - Fully qualified PHP exception class name, used to label the issue
     * - stackTrace: String? - Rendered PHP stack trace, logged as a breadcrumb before recording
     * - fatal: Boolean - Whether to flag this issue as fatal-style via a custom key
     *
     * Returns:
     * - recorded: Boolean
     */
    class RecordError(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val message = parameters["message"] as? String
                ?: return BridgeResponse.error("INVALID_PARAMETERS", "message is required")
            val className = parameters["className"] as? String ?: "PHP\\Throwable"
            val stackTrace = parameters["stackTrace"] as? String
            val fatal = parameters["fatal"] as? Boolean ?: false

            return try {
                val crashlytics = FirebaseCrashlytics.getInstance()

                if (fatal) {
                    crashlytics.setCustomKey("fatal_error", true)
                }
                stackTrace?.let { crashlytics.log("PHP stack trace:\n$it") }

                crashlytics.recordException(PhpThrowable(className, message))

                BridgeResponse.success(mapOf("recorded" to true))
            } catch (e: Exception) {
                BridgeResponse.error("RECORD_ERROR_FAILED", e.message ?: "Failed to record error")
            }
        }
    }

    /**
     * Parameters:
     * - message: String - Breadcrumb message to attach to the next report
     *
     * Returns:
     * - logged: Boolean
     */
    class Log(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val message = parameters["message"] as? String
                ?: return BridgeResponse.error("INVALID_PARAMETERS", "message is required")

            FirebaseCrashlytics.getInstance().log(message)

            return BridgeResponse.success(mapOf("logged" to true))
        }
    }

    /**
     * Parameters:
     * - identifier: String - User identifier to attach to subsequent reports
     *
     * Returns:
     * - set: Boolean
     */
    class SetUserId(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val identifier = parameters["identifier"] as? String
                ?: return BridgeResponse.error("INVALID_PARAMETERS", "identifier is required")

            FirebaseCrashlytics.getInstance().setUserId(identifier)

            return BridgeResponse.success(mapOf("set" to true))
        }
    }

    /**
     * Parameters:
     * - key: String - Custom key name
     * - value: String|Boolean|Number - Custom key value
     *
     * Returns:
     * - set: Boolean
     */
    class SetCustomKey(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val key = parameters["key"] as? String
                ?: return BridgeResponse.error("INVALID_PARAMETERS", "key is required")
            val value = parameters["value"]
                ?: return BridgeResponse.error("INVALID_PARAMETERS", "value is required")

            val crashlytics = FirebaseCrashlytics.getInstance()
            when (value) {
                is Boolean -> crashlytics.setCustomKey(key, value)
                is Int -> crashlytics.setCustomKey(key, value)
                is Long -> crashlytics.setCustomKey(key, value)
                is Float -> crashlytics.setCustomKey(key, value)
                is Double -> crashlytics.setCustomKey(key, value)
                is Number -> crashlytics.setCustomKey(key, value.toDouble())
                else -> crashlytics.setCustomKey(key, value.toString())
            }

            return BridgeResponse.success(mapOf("set" to true))
        }
    }

    /**
     * Forces an immediate, unrecoverable crash - used to verify a Crashlytics
     * integration end-to-end. The throw is posted to the next main-thread loop
     * iteration so it happens outside of the bridge dispatcher's call stack,
     * guaranteeing a real crash instead of being caught and turned into an
     * error response.
     *
     * Parameters:
     * - reason: String? - Optional crash message
     */
    class Crash(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val reason = parameters["reason"] as? String
                ?: "Forced test crash from NativePHP FirebaseCrashlytics::crash()"

            Handler(Looper.getMainLooper()).post {
                throw RuntimeException(reason)
            }

            return BridgeResponse.success(mapOf("crashing" to true))
        }
    }

    /**
     * Parameters:
     * - enabled: Boolean
     *
     * Returns:
     * - enabled: Boolean
     */
    class SetCollectionEnabled(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val enabled = parameters["enabled"] as? Boolean ?: true

            FirebaseCrashlytics.getInstance().setCrashlyticsCollectionEnabled(enabled)

            return BridgeResponse.success(mapOf("enabled" to enabled))
        }
    }

    /**
     * Returns:
     * - enabled: Boolean
     */
    class IsCollectionEnabled(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            return BridgeResponse.success(mapOf(
                "enabled" to FirebaseCrashlytics.getInstance().isCrashlyticsCollectionEnabled
            ))
        }
    }

    /**
     * Blocks (with a timeout) on the async Crashlytics Task so PHP receives a
     * synchronous boolean result.
     *
     * Returns:
     * - hasUnsentReports: Boolean
     */
    class CheckForUnsentReports(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            return try {
                val hasReports = Tasks.await(
                    FirebaseCrashlytics.getInstance().checkForUnsentReports(),
                    AWAIT_TIMEOUT_SECONDS,
                    TimeUnit.SECONDS
                )

                BridgeResponse.success(mapOf("hasUnsentReports" to hasReports))
            } catch (e: TimeoutException) {
                BridgeResponse.error("TIMEOUT", "Timed out checking for unsent reports")
            } catch (e: Exception) {
                BridgeResponse.error("CHECK_FAILED", e.message ?: "Failed to check for unsent reports")
            }
        }
    }

    /**
     * Returns:
     * - sent: Boolean
     */
    class SendUnsentReports(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            FirebaseCrashlytics.getInstance().sendUnsentReports()

            return BridgeResponse.success(mapOf("sent" to true))
        }
    }

    /**
     * Returns:
     * - deleted: Boolean
     */
    class DeleteUnsentReports(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            FirebaseCrashlytics.getInstance().deleteUnsentReports()

            return BridgeResponse.success(mapOf("deleted" to true))
        }
    }

    /**
     * Returns:
     * - didCrash: Boolean
     */
    class DidCrashOnPreviousExecution(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            return BridgeResponse.success(mapOf(
                "didCrash" to FirebaseCrashlytics.getInstance().didCrashOnPreviousExecution()
            ))
        }
    }

    /**
     * Lightweight throwable used to preserve the PHP exception's class name
     * and message when forwarding it to Crashlytics as a recorded exception.
     * It is never thrown - only passed to recordException() for its class
     * name/message/synthetic stack trace.
     */
    private class PhpThrowable(className: String, message: String) : Throwable("$className: $message")
}

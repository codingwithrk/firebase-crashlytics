import FirebaseCrashlytics
import Foundation

enum FirebaseCrashlyticsFunctions {

    /// How long we're willing to wait on the async checkForUnsentReports callback.
    private static let awaitTimeoutSeconds: TimeInterval = 8.0

    /// Records a caught exception with Crashlytics as a (non-)fatal issue.
    ///
    /// - Parameters:
    ///   - message: String - Exception message
    ///   - className: String? - Fully qualified PHP exception class name, used as the NSError domain
    ///   - stackTrace: String? - Rendered PHP stack trace, logged as a breadcrumb before recording
    ///   - fatal: Bool - Whether to flag this issue as fatal-style via a custom key
    ///
    /// - Returns: `recorded: Bool`
    class RecordError: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let message = parameters["message"] as? String else {
                return BridgeResponse.error(code: "INVALID_PARAMETERS", message: "message is required")
            }
            let className = parameters["className"] as? String ?? "PHP.Throwable"
            let stackTrace = parameters["stackTrace"] as? String
            let fatal = parameters["fatal"] as? Bool ?? false

            let crashlytics = Crashlytics.crashlytics()

            if fatal {
                crashlytics.setCustomValue(true, forKey: "fatal_error")
            }
            if let stackTrace = stackTrace {
                crashlytics.log("PHP stack trace:\n\(stackTrace)")
            }

            let error = NSError(
                domain: className,
                code: 0,
                userInfo: [NSLocalizedDescriptionKey: message]
            )
            crashlytics.record(error: error)

            return BridgeResponse.success(data: ["recorded": true])
        }
    }

    /// - Parameters:
    ///   - message: String - Breadcrumb message to attach to the next report
    ///
    /// - Returns: `logged: Bool`
    class Log: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let message = parameters["message"] as? String else {
                return BridgeResponse.error(code: "INVALID_PARAMETERS", message: "message is required")
            }

            Crashlytics.crashlytics().log(message)

            return BridgeResponse.success(data: ["logged": true])
        }
    }

    /// - Parameters:
    ///   - identifier: String - User identifier to attach to subsequent reports
    ///
    /// - Returns: `set: Bool`
    class SetUserId: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let identifier = parameters["identifier"] as? String else {
                return BridgeResponse.error(code: "INVALID_PARAMETERS", message: "identifier is required")
            }

            Crashlytics.crashlytics().setUserID(identifier)

            return BridgeResponse.success(data: ["set": true])
        }
    }

    /// - Parameters:
    ///   - key: String - Custom key name
    ///   - value: String|Bool|Number - Custom key value
    ///
    /// - Returns: `set: Bool`
    class SetCustomKey: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let key = parameters["key"] as? String else {
                return BridgeResponse.error(code: "INVALID_PARAMETERS", message: "key is required")
            }
            guard let value = parameters["value"] else {
                return BridgeResponse.error(code: "INVALID_PARAMETERS", message: "value is required")
            }

            Crashlytics.crashlytics().setCustomValue(value, forKey: key)

            return BridgeResponse.success(data: ["set": true])
        }
    }

    /// Forces an immediate, unrecoverable crash - used to verify a Crashlytics
    /// integration end-to-end. `fatalError` traps unconditionally and cannot be
    /// intercepted by any `try`/`catch`, guaranteeing a real crash.
    ///
    /// - Parameters:
    ///   - reason: String? - Optional crash message
    class Crash: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let reason = parameters["reason"] as? String
                ?? "Forced test crash from NativePHP FirebaseCrashlytics::crash()"

            fatalError(reason)
        }
    }

    /// - Parameters:
    ///   - enabled: Bool
    ///
    /// - Returns: `enabled: Bool`
    class SetCollectionEnabled: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let enabled = parameters["enabled"] as? Bool ?? true

            Crashlytics.crashlytics().isCrashlyticsCollectionEnabled = enabled

            return BridgeResponse.success(data: ["enabled": enabled])
        }
    }

    /// - Returns: `enabled: Bool`
    class IsCollectionEnabled: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            return BridgeResponse.success(data: [
                "enabled": Crashlytics.crashlytics().isCrashlyticsCollectionEnabled
            ])
        }
    }

    /// Blocks (with a timeout) on the async completion handler so PHP receives
    /// a synchronous boolean result.
    ///
    /// - Returns: `hasUnsentReports: Bool`
    class CheckForUnsentReports: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let semaphore = DispatchSemaphore(value: 0)
            var hasReports = false

            Crashlytics.crashlytics().checkForUnsentReports { result in
                hasReports = result
                semaphore.signal()
            }

            let result = semaphore.wait(timeout: .now() + FirebaseCrashlyticsFunctions.awaitTimeoutSeconds)

            if result == .timedOut {
                return BridgeResponse.error(code: "TIMEOUT", message: "Timed out checking for unsent reports")
            }

            return BridgeResponse.success(data: ["hasUnsentReports": hasReports])
        }
    }

    /// - Returns: `sent: Bool`
    class SendUnsentReports: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            Crashlytics.crashlytics().sendUnsentReports()

            return BridgeResponse.success(data: ["sent": true])
        }
    }

    /// - Returns: `deleted: Bool`
    class DeleteUnsentReports: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            Crashlytics.crashlytics().deleteUnsentReports()

            return BridgeResponse.success(data: ["deleted": true])
        }
    }

    /// - Returns: `didCrash: Bool`
    class DidCrashOnPreviousExecution: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            return BridgeResponse.success(data: [
                "didCrash": Crashlytics.crashlytics().didCrashDuringPreviousExecution()
            ])
        }
    }
}

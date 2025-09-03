<?php

namespace App\Http\Controllers;

use App\Mail\AdminNewRequestNotification;
use App\Mail\UserEntryPublishedNotification;
use App\Mail\UserStatusLinkNotification;
use App\Models\WikibaseEntryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class EntryStatusController extends Controller
{
    /**
     * Get the status of an entry request by its token.
     *
     * @param string $token The unique token identifying the entry request
     * @return JsonResponse
     */
    public function getStatus(string $token): JsonResponse
    {
        try {
            \Log::info("Entry status requested", ["token" => $token]);

            if (empty($token)) {
                \Log::warning("Empty token provided to getStatus method");
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Kein gültiger Token übermittelt. Bitte überprüfen Sie den Link.",
                    ],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            // Find the entry request by token
            $entryRequest = WikibaseEntryRequest::where(
                "status_token",
                $token,
            )->first();

            if (!$entryRequest) {
                \Log::warning("Entry not found for token", ["token" => $token]);
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Eintrag nicht gefunden. Bitte überprüfen Sie den Link.",
                    ],
                    Response::HTTP_NOT_FOUND,
                );
            }

            // Prepare the response data
            $responseData = [
                "id" => $entryRequest->id,
                "title" => $entryRequest->title,
                "description" => $entryRequest->description,
                "type" => $entryRequest->type,
                "status" => $entryRequest->status,
                "created_at" => $entryRequest->created_at,
                "status_updated_at" => $entryRequest->status_updated_at,
                "comment" => $entryRequest->comment,
                "status_history" => $entryRequest->status_history,
            ];

            // Add Wikidata ID if available
            if ($entryRequest->wikidata_id) {
                $responseData["wikidata_id"] = $entryRequest->wikidata_id;
            }

            // Add created entities if available
            if ($entryRequest->created_entities) {
                $responseData["created_entities"] =
                    $entryRequest->created_entities;
            }

            \Log::info("Entry status retrieved successfully", [
                "token" => $token,
                "entry_id" => $entryRequest->id,
                "status" => $entryRequest->status,
            ]);

            return response()->json([
                "success" => true,
                "data" => $responseData,
            ]);
        } catch (\Exception $e) {
            \Log::error("Entry Status Error", [
                "token" => $token,
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Ein Fehler ist aufgetreten beim Abrufen des Eintragsstatus. Bitte versuchen Sie es später erneut.",
                    "error_code" => "entry_status_error",
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Update the status history when a status changes.
     * This is an internal method to be called from other controllers.
     *
     * @param WikibaseEntryRequest $entryRequest The entry request to update
     * @param string $newStatus The new status
     * @param string|null $comment Optional comment about the status change
     * @return void
     */
    public function updateStatusHistory(
        WikibaseEntryRequest $entryRequest,
        string $newStatus,
        ?string $comment = null,
    ): void {
        // Get current status history or initialize if empty
        $history = $entryRequest->status_history ?? [];

        // Add new status entry
        $history[] = [
            "status" => $newStatus,
            "date" => now(),
            "comment" => $comment,
        ];

        // Update the entry
        $entryRequest->status = $newStatus;
        $entryRequest->status_history = $history;
        $entryRequest->status_updated_at = now();
        $entryRequest->comment = $comment;

        // Reset notification_sent_at when changing to published status
        if (
            $newStatus === "wikidata_published" &&
            Schema::hasColumn("wikidata_entry_requests", "notification_sent_at")
        ) {
            $entryRequest->notification_sent_at = null;
        }

        $entryRequest->save();

        // Send notifications based on status changes
        Log::info("Instance method calling sendStatusChangeNotifications", [
            "status" => $newStatus,
            "entry_id" => $entryRequest->id,
            "has_email" => !empty($entryRequest->email),
            "notify_on_publish" => $entryRequest->notify_on_publish
                ? "true"
                : "false",
        ]);

        $this->sendStatusChangeNotifications($entryRequest, $newStatus);
    }

    /**
     * Static method to update status history from anywhere in the application
     *
     * @param WikibaseEntryRequest $entryRequest The entry request to update
     * @param string $newStatus The new status
     * @param string|null $comment Optional comment about the status change
     * @return void
     */
    public static function updateStatusHistoryStatic(
        WikibaseEntryRequest $entryRequest,
        string $newStatus,
        ?string $comment = null,
    ): void {
        // Get current status history or initialize if empty
        $history = $entryRequest->status_history ?? [];

        // Add new status entry
        $history[] = [
            "status" => $newStatus,
            "date" => now(),
            "comment" => $comment,
        ];

        // Update the entry
        $entryRequest->status = $newStatus;
        $entryRequest->status_history = $history;
        $entryRequest->status_updated_at = now();
        $entryRequest->comment = $comment;
        $entryRequest->save();

        // Send notifications based on status changes
        Log::info("Static method calling sendStatusChangeNotifications", [
            "status" => $newStatus,
            "entry_id" => $entryRequest->id,
            "has_email" => !empty($entryRequest->email),
            "notify_on_publish" => $entryRequest->notify_on_publish
                ? "true"
                : "false",
        ]);

        $controller = new self();
        $controller->sendStatusChangeNotifications($entryRequest, $newStatus);
    }

    /**
     * Send email notifications based on entry status changes
     *
     * @param WikibaseEntryRequest $entryRequest The entry request
     * @param string $newStatus The new status
     * @return void
     */
    public function sendStatusChangeNotifications(
        WikibaseEntryRequest $entryRequest,
        string $newStatus,
        bool $force = false,
    ): void {
        try {
            // Check if notification_sent_at column exists
            $hasNotificationColumn = Schema::hasColumn(
                "wikidata_entry_requests",
                "notification_sent_at",
            );

            // Check if notification was already sent for this entity
            $alreadySent =
                $hasNotificationColumn &&
                $entryRequest->notification_sent_at !== null &&
                !$force;

            // Force notify on published entries
            $forceNotify = $newStatus === "wikidata_published";

            // Log entry to track execution path with more details
            Log::info("Checking notification conditions", [
                "already_sent" => $alreadySent ? "yes" : "no",
                "force_resend" => $force ? "yes" : "no",
                "entry_id" => $entryRequest->id,
                "status" => $newStatus,
                "has_email" => !empty($entryRequest->email),
                "notify_on_publish" => $forceNotify
                    ? "true (forced)"
                    : ($entryRequest->notify_on_publish
                        ? "true"
                        : "false"),
                "email" => $entryRequest->email ?? "not set",
                "wikidata_id" => $entryRequest->wikidata_id ?? "not set",
                "status_exactly_wikidata_published" =>
                    $newStatus === "wikidata_published" ? "true" : "false",
                "email_empty_check" => empty($entryRequest->email)
                    ? "true"
                    : "false",
                "notify_flag_check" => $entryRequest->notify_on_publish
                    ? "true"
                    : "false",
            ]);

            // Send notification for wikidata_published status
            Log::info("Evaluating email notification conditions", [
                "status" => $newStatus,
                "status_is_wikidata_published" =>
                    $newStatus === "wikidata_published" ? "true" : "false",
                "notify_on_publish" => $entryRequest->notify_on_publish
                    ? "true"
                    : "false",
                "email" => $entryRequest->email ?? "empty",
                "condition_result" =>
                    $newStatus === "wikidata_published" &&
                    $entryRequest->notify_on_publish
                        ? "true"
                        : "false",
            ]);

            // For wikidata_published status, force notification regardless of notify_on_publish flag
            // Skip if already sent and not forced
            if (
                $newStatus === "wikidata_published" &&
                ($entryRequest->notify_on_publish || $forceNotify) &&
                (!$alreadySent || $force)
            ) {
                if (empty($entryRequest->email)) {
                    Log::warning(
                        "Cannot send publication notification - email is empty",
                        [
                            "entry_id" => $entryRequest->id,
                            "status" => $newStatus,
                        ],
                    );
                    return;
                }

                Log::info("Sending publication notification email", [
                    "to" => $entryRequest->email,
                    "entry_id" => $entryRequest->id,
                    "status" => $newStatus,
                    "wikidata_id" => $entryRequest->wikidata_id ?? "not set",
                ]);

                try {
                    // Send the email immediately without queuing for wikidata_published status
                    if ($newStatus === "wikidata_published") {
                        try {
                            $mailable = new UserEntryPublishedNotification(
                                $entryRequest,
                            );
                            $mailable->onQueue(null); // Don't use queue
                            Mail::to($entryRequest->email)->send($mailable);
                            Log::info(
                                "Attempted to send published notification without queuing",
                                [
                                    "entry_id" => $entryRequest->id,
                                    "email" => $entryRequest->email,
                                ],
                            );
                        } catch (\Exception $e) {
                            Log::error(
                                "Primary mail method failed, trying alternative",
                                [
                                    "entry_id" => $entryRequest->id,
                                    "error" => $e->getMessage(),
                                ],
                            );
                            throw $e; // Re-throw to trigger fallback
                        }
                    } else {
                        Mail::to($entryRequest->email)->send(
                            new UserEntryPublishedNotification($entryRequest),
                        );
                    }

                    // Mark notification as sent
                    if (
                        Schema::hasColumn(
                            "wikidata_entry_requests",
                            "notification_sent_at",
                        )
                    ) {
                        $entryRequest->notification_sent_at = now();
                        $entryRequest->save();
                    }

                    Log::info(
                        "Notification sent successfully and marked as sent",
                        [
                            "entry_id" => $entryRequest->id,
                            "email" => $entryRequest->email,
                            "notification_sent_at" =>
                                $entryRequest->notification_sent_at,
                        ],
                    );
                } catch (\Exception $mailException) {
                    Log::error("Exception while sending notification email", [
                        "entry_id" => $entryRequest->id,
                        "email" => $entryRequest->email,
                        "exception" => $mailException->getMessage(),
                        "exception_class" => get_class($mailException),
                        "trace" => $mailException->getTraceAsString(),
                    ]);

                    // Try an alternative mail method for important notifications
                    if ($newStatus === "wikidata_published") {
                        try {
                            Log::info(
                                "Trying alternative mail method for published notification",
                                [
                                    "entry_id" => $entryRequest->id,
                                ],
                            );
                            $to = $entryRequest->email;
                            $subject =
                                "Ihr Eintrag wurde veröffentlicht: " .
                                $entryRequest->title;
                            $message = "Ihr Eintrag '{$entryRequest->title}' wurde in Wikidata veröffentlicht.\n\n";
                            $message .= "Sie können den Eintrag unter https://www.wikidata.org/wiki/{$entryRequest->wikidata_id} ansehen.";

                            // Try PHP's mail function directly as a last resort
                            $headers =
                                "From: " .
                                config("mail.from.address") .
                                "\r\n" .
                                "Reply-To: " .
                                config("mail.from.address") .
                                "\r\n" .
                                "X-Mailer: PHP/" .
                                phpversion();

                            $mailResult = mail(
                                $to,
                                $subject,
                                $message,
                                $headers,
                            );

                            // Even if we used the fallback method, mark as sent if successful
                            if (
                                $mailResult &&
                                Schema::hasColumn(
                                    "wikidata_entry_requests",
                                    "notification_sent_at",
                                )
                            ) {
                                $entryRequest->notification_sent_at = now();
                                $entryRequest->save();

                                Log::info(
                                    "Alternative mail sent successfully, marked as sent",
                                    [
                                        "entry_id" => $entryRequest->id,
                                        "email" => $entryRequest->email,
                                        "notification_sent_at" =>
                                            $entryRequest->notification_sent_at,
                                    ],
                                );
                            } else {
                                Log::info("Alternative mail attempt result", [
                                    "entry_id" => $entryRequest->id,
                                    "result" => $mailResult
                                        ? "success"
                                        : "failed",
                                ]);
                            }
                        } catch (\Exception $altMailEx) {
                            Log::error("Alternative mail method also failed", [
                                "entry_id" => $entryRequest->id,
                                "exception" => $altMailEx->getMessage(),
                            ]);
                        }
                    }
                }

                Log::info(
                    "Completed published notification email process for user",
                    [
                        "entry_id" => $entryRequest->id,
                        "email" => $entryRequest->email,
                        "status" => $newStatus,
                        "wikidata_id" =>
                            $entryRequest->wikidata_id ?? "not set",
                        "forced_notify" => $forceNotify ? "yes" : "no",
                        "original_notify_setting" => $entryRequest->notify_on_publish
                            ? "yes"
                            : "no",
                    ],
                );
            } else {
                Log::info("Skipping notification - conditions not met", [
                    "status" => $newStatus,
                    "status_match" =>
                        $newStatus === "wikidata_published" ? "true" : "false",
                    "notify_on_publish" => $entryRequest->notify_on_publish
                        ? "true"
                        : "false",
                    "entry_id" => $entryRequest->id,
                    "has_email" => !empty($entryRequest->email)
                        ? "true"
                        : "false",
                    "email" => $entryRequest->email ?? "not set",
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send status notification email", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
                "entry_id" => $entryRequest->id,
                "status" => $newStatus,
                "email" => $entryRequest->email ?? "not set",
            ]);
        }
    }

    /**
     * Send admin notification about a new entry request
     *
     * @param WikibaseEntryRequest $entryRequest The new entry request
     * @return void
     */
    public function notifyAdmin(WikibaseEntryRequest $entryRequest): void
    {
        try {
            $adminEmail = config(
                "mail.admin_address",
                config("mail.from.address"),
            );

            Mail::to($adminEmail)->send(
                new AdminNewRequestNotification($entryRequest),
            );

            Log::info("Sent new request notification to admin", [
                "entry_id" => $entryRequest->id,
                "admin_email" => $adminEmail,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send admin notification email", [
                "error" => $e->getMessage(),
                "entry_id" => $entryRequest->id,
            ]);
        }
    }

    /**
     * Send status link to user via email
     *
     * @param Request $request The HTTP request
     * @return JsonResponse
     */
    public function sendStatusLinkToUser(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                "token" => "required|string",
                "email" => "required|email",
            ]);

            // Find the entry request by token
            $entryRequest = WikibaseEntryRequest::where(
                "status_token",
                $validated["token"],
            )->first();

            if (!$entryRequest) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Eintrag nicht gefunden. Bitte überprüfen Sie den Link.",
                    ],
                    Response::HTTP_NOT_FOUND,
                );
            }

            // Store user email for future notifications
            // Always set the email and track notification preference
            $entryRequest->email = $validated["email"];

            // Set notify_on_publish based on request parameter, default to true if not specified
            $entryRequest->notify_on_publish = $request->has(
                "notify_on_publish",
            )
                ? (bool) $request->input("notify_on_publish")
                : true;

            $entryRequest->save();

            Log::info("Updated entry with email notification settings", [
                "entry_id" => $entryRequest->id,
                "email" => $validated["email"],
                "notify_on_publish" => $entryRequest->notify_on_publish
                    ? "true"
                    : "false",
            ]);

            // Send the status link email
            Mail::to($validated["email"])->send(
                new UserStatusLinkNotification($entryRequest),
            );

            return response()->json([
                "success" => true,
                "message" =>
                    "Status-Link wurde an Ihre E-Mail-Adresse gesendet.",
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send status link email", [
                "error" => $e->getMessage(),
                "token" => $request->token ?? "not provided",
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Ein Fehler ist aufgetreten beim Senden der E-Mail. Bitte versuchen Sie es später erneut.",
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Manually trigger a notification for testing purposes
     *
     * @param Request $request The HTTP request
     * @return JsonResponse
     */
    public function sendManualNotification(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                "token" => "required|string",
                "type" => "required|string|in:status,published",
                "force" => "sometimes|boolean",
            ]);

            $force = $request->has("force")
                ? (bool) $request->input("force")
                : false;

            // Find the entry request by token
            $entryRequest = WikibaseEntryRequest::where(
                "status_token",
                $validated["token"],
            )->first();

            if (!$entryRequest) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Eintrag nicht gefunden. Bitte überprüfen Sie den Token.",
                    ],
                    Response::HTTP_NOT_FOUND,
                );
            }

            // If no email is set, we can't send a notification
            if (empty($entryRequest->email)) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Keine E-Mail-Adresse für diesen Eintrag hinterlegt.",
                    ],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            Log::info("Manually triggering notification", [
                "entry_id" => $entryRequest->id,
                "email" => $entryRequest->email,
                "type" => $validated["type"],
            ]);

            if ($validated["type"] === "status") {
                // Send status link notification
                Mail::to($entryRequest->email)->send(
                    new UserStatusLinkNotification($entryRequest),
                );

                return response()->json([
                    "success" => true,
                    "message" =>
                        "Status-Benachrichtigung wurde manuell gesendet.",
                ]);
            } elseif ($validated["type"] === "published") {
                if (
                    Schema::hasColumn(
                        "wikidata_entry_requests",
                        "notification_sent_at",
                    ) &&
                    $entryRequest->notification_sent_at &&
                    !$force
                ) {
                    return response()->json([
                        "success" => false,
                        "already_sent" => true,
                        "sent_at" => $entryRequest->notification_sent_at,
                        "message" =>
                            "Veröffentlichungs-Benachrichtigung wurde bereits gesendet. Verwenden Sie 'force=true', um erneut zu senden.",
                    ]);
                }

                // Send published notification
                Mail::to($entryRequest->email)->send(
                    new UserEntryPublishedNotification($entryRequest),
                );

                // Record that notification was sent if column exists
                if (
                    Schema::hasColumn(
                        "wikidata_entry_requests",
                        "notification_sent_at",
                    )
                ) {
                    $entryRequest->notification_sent_at = now();
                    $entryRequest->save();
                }

                return response()->json([
                    "success" => true,
                    "message" =>
                        "Veröffentlichungs-Benachrichtigung wurde manuell gesendet.",
                    "notification_sent_at" => Schema::hasColumn(
                        "wikidata_entry_requests",
                        "notification_sent_at",
                    )
                        ? $entryRequest->notification_sent_at
                        : null,
                ]);
            }

            return response()->json(
                [
                    "success" => false,
                    "message" => "Ungültiger Benachrichtigungstyp.",
                ],
                Response::HTTP_BAD_REQUEST,
            );
        } catch (\Exception $e) {
            Log::error("Failed to send manual notification", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Ein Fehler ist aufgetreten: " . $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Check if email notifications are enabled for an entry
     *
     * @param string $token The status token
     * @return JsonResponse
     */
    public function checkNotificationStatus(string $token): JsonResponse
    {
        try {
            if (empty($token)) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Kein gültiger Token übermittelt.",
                    ],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            // Find the entry request by token
            $entryRequest = WikibaseEntryRequest::where(
                "status_token",
                $token,
            )->first();

            if (!$entryRequest) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Eintrag nicht gefunden.",
                    ],
                    Response::HTTP_NOT_FOUND,
                );
            }

            return response()->json([
                "success" => true,
                "data" => [
                    "has_email" => !empty($entryRequest->email),
                    "email" => $entryRequest->email,
                    "notify_on_publish" =>
                        (bool) $entryRequest->notify_on_publish,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error checking notification status", [
                "token" => $token,
                "message" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Ein Fehler ist aufgetreten.",
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}

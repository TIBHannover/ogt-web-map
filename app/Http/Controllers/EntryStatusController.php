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
    protected function sendStatusChangeNotifications(
        WikibaseEntryRequest $entryRequest,
        string $newStatus,
    ): void {
        try {
            // Log entry to track execution path
            Log::info("Checking notification conditions", [
                "entry_id" => $entryRequest->id,
                "status" => $newStatus,
                "has_email" => !empty($entryRequest->email),
                "notify_on_publish" => $entryRequest->notify_on_publish
                    ? "true"
                    : "false",
                "email" => $entryRequest->email ?? "not set",
                "wikidata_id" => $entryRequest->wikidata_id ?? "not set",
            ]);

            // Send notification for wikidata_published status
            if (
                $newStatus === "wikidata_published" &&
                $entryRequest->notify_on_publish
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
                ]);

                Mail::to($entryRequest->email)->send(
                    new UserEntryPublishedNotification($entryRequest),
                );

                Log::info("Sent published notification email to user", [
                    "entry_id" => $entryRequest->id,
                    "email" => $entryRequest->email,
                ]);
            } else {
                Log::info("Skipping notification - conditions not met", [
                    "status" => $newStatus,
                    "is_published_status" =>
                        $newStatus === "wikidata_published" ? "true" : "false",
                    "notify_on_publish" => $entryRequest->notify_on_publish
                        ? "true"
                        : "false",
                    "entry_id" => $entryRequest->id,
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
                // Send published notification
                Mail::to($entryRequest->email)->send(
                    new UserEntryPublishedNotification($entryRequest),
                );

                return response()->json([
                    "success" => true,
                    "message" =>
                        "Veröffentlichungs-Benachrichtigung wurde manuell gesendet.",
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

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use ZipArchive;

class PDFDownloadController extends Controller
{
    /**
     * Download multiple PDF files as a ZIP archive
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadPdfs(
        Request $request,
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse {
        try {
            // Validate the request
            $request->validate([
                "files" => "required|string",
            ]);

            // Parse the JSON string of files
            $filesData = json_decode($request->input("files"), true);

            if (!is_array($filesData) || empty($filesData)) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Keine gültigen Dateien zum Herunterladen angegeben.",
                    ],
                    400,
                );
            }

            Log::info("PDF files to download: " . count($filesData));

            // Create a temporary file for the ZIP archive
            $zipFileName = "erinnerungskacheln_" . date("Y-m-d_His") . ".zip";
            $zipFilePath = storage_path("app/public/temp/" . $zipFileName);

            // Create directory if it doesn't exist
            $tempDir = storage_path("app/public/temp");
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Create a new ZIP archive
            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Fehler beim Erstellen des ZIP-Archivs.",
                    ],
                    500,
                );
            }

            $filesAdded = 0;
            $errors = [];

            // Add files to the ZIP archive
            foreach ($filesData as $fileData) {
                $fileName = isset($fileData["fileName"])
                    ? $fileData["fileName"]
                    : null;
                $label = isset($fileData["label"])
                    ? $fileData["label"]
                    : "Dokument";

                if (!$fileName) {
                    $errors[] = "Dateiname fehlt";
                    continue;
                }

                // Extract just the filename and use resources/tiles directory
                $fileName = basename($fileName);
                $pdfPath = public_path("resources/tiles/" . $fileName);

                Log::info("Adding PDF to ZIP: " . $pdfPath);

                // Check if file exists
                if (!file_exists($pdfPath)) {
                    $errors[] = "Datei nicht gefunden: " . $fileName;
                    continue;
                }

                // Create a readable filename for the ZIP entry
                // Remove illegal characters and replace spaces with underscores
                $zipEntryName = preg_replace(
                    "/[^a-zA-Z0-9äöüÄÖÜß\-_. ]/",
                    "",
                    $label,
                );
                $zipEntryName = str_replace(" ", "_", $zipEntryName);
                $zipEntryName = $zipEntryName . ".pdf";

                // Add the file to the ZIP archive
                if ($zip->addFile($pdfPath, $zipEntryName)) {
                    $filesAdded++;
                } else {
                    $errors[] =
                        "Fehler beim Hinzufügen der Datei: " . $fileName;
                }
            }

            // Close the ZIP archive
            $zip->close();

            if ($filesAdded === 0) {
                // Delete the empty ZIP file
                if (file_exists($zipFilePath)) {
                    unlink($zipFilePath);
                }

                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Keine Dateien konnten zum ZIP-Archiv hinzugefügt werden.",
                        "errors" => $errors,
                    ],
                    404,
                );
            }

            // Log any errors
            if (!empty($errors)) {
                Log::warning(
                    "Some files could not be added to the ZIP: " .
                        implode(", ", $errors),
                );
            }

            // Return the ZIP file for download
            return Response::download($zipFilePath, $zipFileName, [
                "Content-Type" => "application/zip",
                "Content-Disposition" =>
                    'attachment; filename="' . $zipFileName . '"',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error("Error creating ZIP archive: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler beim Erstellen des ZIP-Archivs: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }
}

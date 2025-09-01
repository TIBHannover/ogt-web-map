<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PDFThumbnailController extends Controller
{
    /**
     * Generate a thumbnail for a PDF file
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateThumbnail(Request $request): JsonResponse
    {
        $request->validate([
            "pdf_file" => "required|string",
        ]);

        $pdfFile = $request->input("pdf_file");

        // Log the original path
        Log::info("Original PDF path requested: " . $pdfFile);

        // Extract just the filename and use resources/tiles directory
        $filename = basename($pdfFile);
        $pdfPath = public_path("resources/tiles/" . $filename);

        Log::info("Looking for PDF at: " . $pdfPath);

        // Check if file exists
        if (!file_exists($pdfPath)) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "PDF file not found: " . $filename,
                ],
                404,
            );
        }

        // Define thumbnail path
        $thumbnailName = pathinfo($filename, PATHINFO_FILENAME) . ".jpg";
        $thumbnailDir = "thumbnails";

        // Create the thumbnails directory if it doesn't exist
        if (!Storage::disk("public")->exists($thumbnailDir)) {
            Storage::disk("public")->makeDirectory($thumbnailDir);
        }

        $thumbnailPath = $thumbnailDir . "/" . $thumbnailName;
        $fullThumbnailPath = Storage::disk("public")->path($thumbnailPath);

        Log::info("Thumbnail path: " . $thumbnailPath);
        Log::info("Full thumbnail path: " . $fullThumbnailPath);

        // Create thumbnails directory if it doesn't exist
        $thumbnailsDir = dirname($fullThumbnailPath);
        if (!is_dir($thumbnailsDir)) {
            Log::info("Creating thumbnails directory: {$thumbnailsDir}");
            mkdir($thumbnailsDir, 0755, true);
        }

        // Check if thumbnail already exists
        if (Storage::disk("public")->exists($thumbnailPath)) {
            Log::info("Thumbnail already exists, returning existing thumbnail");
            return response()->json([
                "success" => true,
                "thumbnail_url" => asset("storage/" . $thumbnailPath),
            ]);
        }

        try {
            // Create a simple placeholder image
            $this->createPlaceholderImage($fullThumbnailPath);
            Log::info(
                "Thumbnail created successfully at: {$fullThumbnailPath}",
            );

            return response()->json([
                "success" => true,
                "thumbnail_url" => asset("storage/" . $thumbnailPath),
            ]);
        } catch (\Exception $e) {
            Log::error("Error generating PDF thumbnail: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Failed to generate thumbnail: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get all thumbnails for PDF files
     *
     * @return JsonResponse
     */
    public function getAllThumbnails(): JsonResponse
    {
        $thumbnails = [];

        // Look for PDF files in resources/tiles directory
        $files = glob(public_path("resources/tiles/*.pdf"));
        Log::info("Found " . count($files) . " PDF files in resources/tiles");

        foreach ($files as $file) {
            $filename = basename($file);
            $thumbnailName = pathinfo($file, PATHINFO_FILENAME) . ".jpg";
            $thumbnailPath = "thumbnails/" . $thumbnailName;

            $thumbnails[] = [
                "pdf_file" => $filename,
                "thumbnail_url" => Storage::disk("public")->exists(
                    $thumbnailPath,
                )
                    ? asset("storage/" . $thumbnailPath)
                    : null,
            ];
        }

        return response()->json([
            "success" => true,
            "thumbnails" => $thumbnails,
        ]);
    }

    /**
     * Create a placeholder image for PDF thumbnail
     *
     * @param string $path The path where to save the image
     * @return void
     */
    private function createPlaceholderImage(string $path): void
    {
        // Create a simple placeholder image
        $width = 600;
        $height = 800;

        $image = imagecreatetruecolor($width, $height);

        // Fill with light gray background
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        imagefill($image, 0, 0, $bgColor);

        // Add text
        $textColor = imagecolorallocate($image, 100, 100, 100);
        $text = "PDF Dokument";
        $fontSize = 5;

        // Position text in center
        $x = 250;
        $y = 400;

        // Draw text
        imagestring($image, $fontSize, $x, $y, $text, $textColor);

        // Save the image
        imagejpeg($image, $path, 90);
        imagedestroy($image);
    }

    /**
     * Serve a PDF file
     *
     * @param string $filename
     * @return BinaryFileResponse
     */
    public function servePdf($filename): BinaryFileResponse
    {
        // Clean up the filename by removing any URI path encoding
        $filename = urldecode($filename);

        Log::info("PDF requested: " . $filename);

        // Extract just the filename and use resources/tiles directory
        $pdfPath = public_path("resources/tiles/" . basename($filename));

        Log::info("Looking for PDF at: " . $pdfPath);

        if (!file_exists($pdfPath)) {
            abort(404, "PDF file not found: " . basename($filename));
        }

        return Response::file($pdfPath, [
            "Content-Type" => "application/pdf",
            "Content-Disposition" =>
                'inline; filename="' . basename($filename) . '"',
        ]);
    }

    /**
     * Check if a PDF file exists
     *
     * @param string $filename
     * @return JsonResponse
     */
    public function checkPdfExists($filename): JsonResponse
    {
        // Clean up the filename by removing any URI path encoding
        $filename = urldecode($filename);

        Log::info("Checking if PDF exists: " . $filename);

        // Extract just the filename and use resources/tiles directory
        $pdfPath = public_path("resources/tiles/" . basename($filename));

        Log::info("Looking for PDF at: " . $pdfPath);

        $exists = file_exists($pdfPath);

        return response()->json([
            "success" => true,
            "exists" => $exists,
            "filename" => basename($filename),
            "path" => $pdfPath,
        ]);
    }
}

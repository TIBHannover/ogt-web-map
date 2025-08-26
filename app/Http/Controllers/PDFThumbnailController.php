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
        // Handle paths that might already include 'resources'
        $cleanPath = preg_replace("/^resources\//", "", $pdfFile);
        $pdfPath = public_path($cleanPath);

        // Check if file exists
        if (!file_exists($pdfPath)) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "PDF file not found",
                ],
                404,
            );
        }

        // Define thumbnail path
        $thumbnailName = pathinfo($pdfFile, PATHINFO_FILENAME) . ".jpg";
        $thumbnailDir = "thumbnails";

        // Create the thumbnails directory if it doesn't exist
        if (!Storage::disk("public")->exists($thumbnailDir)) {
            Storage::disk("public")->makeDirectory($thumbnailDir);
        }

        $thumbnailPath = $thumbnailDir . "/" . $thumbnailName;
        $fullThumbnailPath = Storage::disk("public")->path($thumbnailPath);

        // Check if thumbnail already exists
        if (Storage::disk("public")->exists($thumbnailPath)) {
            return response()->json([
                "success" => true,
                "thumbnail_url" => asset("storage/" . $thumbnailPath),
            ]);
        }

        try {
            // Create a simple placeholder image
            $this->createPlaceholderImage($fullThumbnailPath);

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
        $files = glob(public_path("resources/tiles/*.pdf"));

        foreach ($files as $file) {
            $relativePath = "resources/tiles/" . basename($file);
            $filename = pathinfo($file, PATHINFO_BASENAME);
            $thumbnailName = pathinfo($file, PATHINFO_FILENAME) . ".jpg";
            $thumbnailPath = "thumbnails/" . $thumbnailName;

            $thumbnails[] = [
                "pdf_file" => $relativePath,
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

        // Handle paths that might already include 'resources'
        $cleanPath = preg_replace("/^resources\//", "", $filename);
        $path = public_path($cleanPath);

        if (!file_exists($path)) {
            abort(404);
        }

        return Response::file($path, [
            "Content-Type" => "application/pdf",
            "Content-Disposition" => 'inline; filename="' . $filename . '"',
        ]);
    }
}

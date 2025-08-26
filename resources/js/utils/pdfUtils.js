/**
 * Utility functions for working with PDFs in the frontend
 */

/**
 * Generate a thumbnail URL for a PDF file
 * @param {string} pdfFile - The PDF filename
 * @returns {Promise<string>} - The thumbnail URL
 */
export const generateThumbnail = async (pdfFile) => {
    try {
        // Check if the file path is valid
        if (!pdfFile || typeof pdfFile !== "string") {
            console.error("Invalid PDF file path provided");
            return null;
        }

        // Clean up the file path
        const cleanPath = pdfFile.replace(/^\.\//, "");

        const response = await fetch("/api/pdf/generate-thumbnail", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content"),
            },
            body: JSON.stringify({ pdf_file: cleanPath }),
        });

        if (!response.ok) {
            throw new Error(
                `Error generating thumbnail: ${response.statusText}`,
            );
        }

        const data = await response.json();
        if (data.success) {
            return data.thumbnail_url;
        } else {
            console.error("Error:", data.message);
            return null;
        }
    } catch (error) {
        console.error("Error generating thumbnail:", error);
        return null;
    }
};

/**
 * Get all available PDF thumbnails
 * @returns {Promise<Array>} - Array of PDF files with their thumbnail URLs
 */
export const getAllThumbnails = async () => {
    try {
        const response = await fetch("/api/pdf/thumbnails");
        if (!response.ok) {
            throw new Error(
                `Error fetching thumbnails: ${response.statusText}`,
            );
        }

        const data = await response.json();
        return data.success ? data.thumbnails : [];
    } catch (error) {
        console.error("Error fetching thumbnails:", error);
        return [];
    }
};

/**
 * Get PDF URL
 * @param {string} pdfFile - The PDF filename
 * @returns {string} - The PDF URL
 */
export const getPdfUrl = (pdfFile) => {
    if (!pdfFile) return "";

    // Clean the path by removing any leading "./"
    const cleanPath = pdfFile.replace(/^\.\//, "");

    // If it's already a full URL, return it as-is
    if (cleanPath.startsWith("http")) {
        return cleanPath;
    }

    // Ensure correct URI encoding for spaces in the path
    const encodedPath = encodeURI(cleanPath);

    return `/api/pdf/${encodedPath}`;
};

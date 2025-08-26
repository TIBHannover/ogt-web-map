/**
 * Utility functions for working with PDFs in the frontend
 */

/**
 * Get PDF URL
 * @param {string} pdfFile - The PDF filename
 * @returns {string} - The PDF URL
 */
export const getPdfUrl = (pdfFile) => {
    if (!pdfFile) {
        console.error("No PDF file provided");
        return "";
    }

    // If it's already a full URL, return it as-is
    if (pdfFile.startsWith("http")) {
        return pdfFile;
    }

    // Just use the basename (filename without path)
    const filename = pdfFile.split("/").pop();

    // Ensure correct URI encoding for spaces and special characters in the path
    const encodedPath = encodeURIComponent(filename);

    console.log(`PDF URL generated: /api/pdf/${encodedPath}`);
    return `/api/pdf/${encodedPath}`;
};

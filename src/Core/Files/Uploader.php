<?php

declare(strict_types=1);

namespace App\Core\Files;

use finfo;
use RuntimeException;

/**
 * Utility for file uploads.
 * Handles moving uploaded files from temporary location to a permanent storage.
 */
class Uploader
{
    private string $uploadDir;

    /**
     * @param string $uploadDir The base directory where files will be uploaded (e.g., 'public/uploads/images/').
     * @throws RuntimeException If the upload directory cannot be created or is not writable.
     */
    public function __construct(string $uploadDir)
    {
        $this->uploadDir = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0775, true)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created or is not writable.', $this->uploadDir));
        }
    }

    /**
     * Uploads a file and returns its relative path.
     * Handles validation for common upload errors, file size, and MIME type.
     *
     * @param array $fileInfo Array from $_FILES for the specific file input (e.g., $_FILES['image']).
     * @param array $allowedMimeTypes Allowed MIME types (e.g., ['image/jpeg', 'image/png']).
     * @param int $maxFileSize Maximum file size in bytes.
     * @return string The relative path of the saved file (e.g., 'uploads/images/unique_filename.jpg').
     * @throws RuntimeException In case of upload, validation, or file system errors.
     */
    public function upload(array $fileInfo, array $allowedMimeTypes, int $maxFileSize): string
    {
        // Check for common PHP upload errors
        if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
            switch ($fileInfo['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException('Uploaded file exceeds maximum file size allowed by server.');
                case UPLOAD_ERR_PARTIAL:
                    throw new RuntimeException('File was only partially uploaded.');
                case UPLOAD_ERR_NO_FILE:
                    throw new RuntimeException('No file was uploaded.');
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new RuntimeException('Missing a temporary folder for uploads.');
                case UPLOAD_ERR_CANT_WRITE:
                    throw new RuntimeException('Failed to write file to disk.');
                case UPLOAD_ERR_EXTENSION:
                    throw new RuntimeException('A PHP extension stopped the file upload.');
                default:
                    throw new RuntimeException('Unknown upload error occurred.');
            }
        }

        // Validate file size
        if ($fileInfo['size'] > $maxFileSize) {
            throw new RuntimeException('File is too large. Maximum allowed size: ' . round($maxFileSize / 1024 / 1024, 2) . 'MB.');
        }

        // Validate MIME type 
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($fileInfo['tmp_name']);
        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new RuntimeException("Invalid file type: {$mimeType}. Allowed types are: " . implode(', ', $allowedMimeTypes) . '.');
        }

        $extension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        // Basic sanitization/validation of file extension to prevent issues
        $extension = strtolower($extension);
        if (!preg_match('/^[a-z0-9]+$/i', $extension)) {
            throw new RuntimeException("Invalid file extension.");
        }

        // Generate a unique filename to prevent collisions and overwrite existing files
        $fileName = uniqid('img_', true) . '.' . $extension;
        $destinationPath = $this->uploadDir . $fileName;

        // Move the uploaded file from its temporary location to the permanent destination
        if (!move_uploaded_file($fileInfo['tmp_name'], $destinationPath)) {
            throw new RuntimeException('Failed to move uploaded file to destination.');
        }

        // Relative to 'public/' (e.g., 'public/uploads/images/').
        return 'uploads/images/' . $fileName;
    }
}
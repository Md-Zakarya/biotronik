<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class S3StorageService
{
    protected $disk;
    protected $baseImagePath;

    public function __construct()
    {
        $this->disk = Storage::disk('s3');
        $this->baseImagePath = 'backend-images/biotronik';
    }

    /**
     * Upload a file to S3
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @param string|null $userId
     * @return array
     */
    public function uploadFile($file, $directory, $userId = null)
    {
        try {
            $filename = time() . '_' . ($userId ? $userId . '_' : '') . $file->getClientOriginalName();
            $s3Path = "{$this->baseImagePath}/{$directory}/{$filename}";
            $dbPath = "biotronik/{$directory}/{$filename}";

            $success = $this->disk->put($s3Path, file_get_contents($file));

            if (!$success) {
                throw new \Exception('Failed to upload file to S3');
            }

            return [
                's3_path' => $s3Path,
                'db_path' => $dbPath,
                'success' => true
            ];
        } catch (\Exception $e) {
            Log::error('S3 Upload Error', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'directory' => $directory
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get a file's URL from S3
     *
     * @param string $path
     * @return string|null
     */
    public function getFileUrl($path)
    {
        try {
            if (!$path) return null;
            
            return $this->disk->url($path);
        } catch (\Exception $e) {
            Log::error('S3 URL Generation Error', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            return null;
        }
    }

    /**
     * Delete a file from S3
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile($path)
    {
        try {
            if (!$path) return true;
            
            return $this->disk->delete($path);
        } catch (\Exception $e) {
            Log::error('S3 Deletion Error', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            return false;
        }
    }
}
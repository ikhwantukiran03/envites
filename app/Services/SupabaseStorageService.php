<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class SupabaseStorageService
{
    protected $client;
    protected $bucket;
    protected $supabaseUrl;
    protected $serviceKey;

    public function __construct($bucketName = null)
    {
        $this->client = new Client();
        $this->supabaseUrl = config('services.supabase.url');
        $this->serviceKey = config('services.supabase.service_key');
        $this->bucket = $bucketName ?? config('services.supabase.bucket');
    }

    /**
     * Upload file from Laravel UploadedFile
     */
    public function uploadFile($uploadedFile, $directory = 'uploads')
    {
        try {
            // Generate unique filename
            $fileName = time() . '_' . uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
            $filePath = $directory . '/' . $fileName;
            
            // Prepare file content
            $fileContent = file_get_contents($uploadedFile->path());
            
            // Upload to Supabase Storage via REST API
            $url = $this->supabaseUrl . '/storage/v1/object/' . $this->bucket . '/' . $filePath;
            
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->serviceKey,
                    'Content-Type' => $uploadedFile->getMimeType(),
                ],
                'body' => $fileContent
            ]);
            
            Log::info('File uploaded successfully', ['path' => $filePath]);
            
            return [
                'success' => true,
                'path' => $filePath,
                'url' => $this->getPublicUrl($filePath),
                'original_name' => $uploadedFile->getClientOriginalName(),
                'size' => $uploadedFile->getSize(),
                'mime_type' => $uploadedFile->getMimeType()
            ];

        } catch (RequestException $e) {
            $errorMessage = 'Upload failed: ' . $e->getMessage();
            if ($e->hasResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                $errorMessage .= ' - Response: ' . $errorBody;
            }
            Log::error('File upload failed', ['error' => $errorMessage]);
            throw new \Exception($errorMessage);
        } catch (\Exception $e) {
            Log::error('File upload failed', ['error' => $e->getMessage()]);
            throw new \Exception('Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete a file from storage
     */
    public function deleteFile($filePath)
    {
        try {
            $url = $this->supabaseUrl . '/storage/v1/object/' . $this->bucket . '/' . $filePath;
            
            $response = $this->client->delete($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->serviceKey,
                ]
            ]);
            
            Log::info('File deleted successfully', ['path' => $filePath]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $errorMessage = 'Delete failed: ' . $e->getMessage();
            if ($e->hasResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                $errorMessage .= ' - Response: ' . $errorBody;
            }
            Log::error('File deletion failed', ['error' => $errorMessage, 'path' => $filePath]);
            throw new \Exception($errorMessage);
        }
    }

    /**
     * Get public URL for a file
     */
    public function getPublicUrl($filePath)
    {
        return $this->supabaseUrl . '/storage/v1/object/public/' . $this->bucket . '/' . $filePath;
    }

    /**
     * List files in a directory
     */
    public function listFiles($path = '', $limit = 100)
    {
        try {
            $url = $this->supabaseUrl . '/storage/v1/object/list/' . $this->bucket;
            if ($path) {
                $url .= '?prefix=' . urlencode($path);
            }
            
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->serviceKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'limit' => $limit,
                    'offset' => 0
                ]
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $errorMessage = 'File listing failed: ' . $e->getMessage();
            if ($e->hasResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                $errorMessage .= ' - Response: ' . $errorBody;
            }
            Log::error('File listing failed', ['error' => $errorMessage, 'path' => $path]);
            throw new \Exception($errorMessage);
        }
    }

    /**
     * Download a file
     */
    public function downloadFile($filePath)
    {
        try {
            $url = $this->supabaseUrl . '/storage/v1/object/' . $this->bucket . '/' . $filePath;
            
            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->serviceKey,
                ]
            ]);
            
            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            $errorMessage = 'Download failed: ' . $e->getMessage();
            if ($e->hasResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                $errorMessage .= ' - Response: ' . $errorBody;
            }
            Log::error('File download failed', ['error' => $errorMessage, 'path' => $filePath]);
            throw new \Exception($errorMessage);
        }
    }
}
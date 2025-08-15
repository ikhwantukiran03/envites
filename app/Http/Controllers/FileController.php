<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    protected $storage;

    public function __construct()
    {
        $this->storage = new SupabaseStorageService();
    }

    /**
     * Upload a file
     */
    public function upload(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'directory' => 'sometimes|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get directory or use default
            $directory = $request->input('directory', 'uploads');
            
            // Upload file
            $result = $this->storage->uploadFile($request->file('file'), $directory);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*' => 'file|max:10240',
            'directory' => 'sometimes|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $directory = $request->input('directory', 'uploads');
            $results = [];
            $errors = [];

            foreach ($request->file('files') as $index => $file) {
                try {
                    $result = $this->storage->uploadFile($file, $directory);
                    $results[] = $result;
                } catch (\Exception $e) {
                    $errors[] = "File {$index}: " . $e->getMessage();
                }
            }

            if (empty($results)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files were uploaded successfully',
                    'errors' => $errors
                ], 500);
            }

            $response = [
                'success' => true,
                'message' => count($results) . ' file(s) uploaded successfully',
                'data' => $results
            ];

            if (!empty($errors)) {
                $response['partial_errors'] = $errors;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a file
     */
    public function delete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Path is required',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->storage->deleteFile($request->input('path'));

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List files in a directory
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $path = $request->input('path', '');
            $limit = $request->input('limit', 100);

            $files = $this->storage->listFiles($path, $limit);

            return response()->json([
                'success' => true,
                'data' => $files
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file URL
     */
    public function getUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $url = $this->storage->getPublicUrl($request->input('path'));

            return response()->json([
                'success' => true,
                'url' => $url
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
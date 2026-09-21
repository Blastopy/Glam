<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Talks to Cloudinary's HTTP upload API directly with a computed signature,
 * rather than pulling in the official SDK — keeps this consistent with
 * MpesaService's approach elsewhere in the app: one dependency-free HTTP
 * integration pattern instead of two different styles.
 */
class CloudinaryService
{
    private const TIMEOUT_SECONDS = 30;

    /**
     * Uploads an image and returns its public URL + Cloudinary asset ID
     * (the latter is needed later to delete or replace it).
     *
     * @return array{url: string, public_id: string}
     */
    public function upload(UploadedFile $file, ?string $folder = null): array
    {
        $folder ??= config('cloudinary.services_folder');
        $timestamp = time();
        $signature = $this->sign(['folder' => $folder, 'timestamp' => $timestamp]);

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($this->endpoint('image/upload'), [
                    'api_key' => config('cloudinary.api_key'),
                    'timestamp' => $timestamp,
                    'folder' => $folder,
                    'signature' => $signature,
                ]);
        } catch (ConnectionException $e) {
            Log::error('Cloudinary upload connection failed', ['error' => $e->getMessage()]);
            throw new RuntimeException('Could not reach the image host right now. Please try again.');
        }

        if (! $response->successful()) {
            Log::error('Cloudinary upload failed', ['response' => $response->body()]);
            throw new RuntimeException($response->json('error.message') ?? 'Could not upload this image. Please try again.');
        }

        $data = $response->json();

        return ['url' => $data['secure_url'], 'public_id' => $data['public_id']];
    }

    /** Removes an image from Cloudinary — called when a service's photo is replaced or the service is deleted. */
    public function destroy(string $publicId): void
    {
        $timestamp = time();
        $signature = $this->sign(['public_id' => $publicId, 'timestamp' => $timestamp]);

        try {
            $response = Http::timeout(15)->post($this->endpoint('image/destroy'), [
                'public_id' => $publicId,
                'api_key' => config('cloudinary.api_key'),
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]);

            if (! $response->successful()) {
                // Not fatal to the request that triggered this — an orphaned Cloudinary
                // asset costs a little storage, not a broken user-facing action.
                Log::warning('Cloudinary delete failed', ['public_id' => $publicId, 'response' => $response->body()]);
            }
        } catch (ConnectionException $e) {
            Log::warning('Cloudinary delete connection failed', ['public_id' => $publicId, 'error' => $e->getMessage()]);
        }
    }

    private function endpoint(string $path): string
    {
        return 'https://api.cloudinary.com/v1_1/' . config('cloudinary.cloud_name') . '/' . $path;
    }

    /**
     * Cloudinary's documented signing algorithm: sort params alphabetically by
     * key, join as key=value pairs with &, append the API secret, then SHA-1.
     */
    private function sign(array $params): string
    {
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = "{$key}={$value}";
        }

        return sha1(implode('&', $pairs) . config('cloudinary.api_secret'));
    }
}

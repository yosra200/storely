<?php

namespace App\Services\Facebook;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FacebookLiveService
{
    private string $graphUrl;

    public function __construct()
    {
        $this->graphUrl = config('services.facebook.graph_url');
    }

    /**
     * Create Facebook Live Video
     */
    public function createLive(
        string $pageId,
        string $pageAccessToken,
        string $title,
        ?string $description = null
    ): array {

        $response = Http::post(
            "{$this->graphUrl}/{$pageId}/live_videos",
            [
                'access_token' => $pageAccessToken,
                'title' => $title,
                'description' => $description,
                'status' => 'LIVE_NOW',
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Facebook create live failed: ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Get Facebook Live information
     */
    public function getLive(
        string $liveId,
        string $pageAccessToken
    ): array {

        $response = Http::get(
            "{$this->graphUrl}/{$liveId}",
            [
                'access_token' => $pageAccessToken,
                'fields' => 'id,status,title,description',
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Facebook get live failed: ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * End Facebook Live
     */
    public function endLive(
        string $liveId,
        string $pageAccessToken
    ): bool {

        $response = Http::post(
            "{$this->graphUrl}/{$liveId}",
            [
                'access_token' => $pageAccessToken,
                'end_live_video' => true,
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Facebook end live failed: ' . $response->body()
            );
        }

        return true;
    }
}

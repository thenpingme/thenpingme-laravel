<?php

declare(strict_types=1);

namespace Thenpingme;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class ThenpingmePingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $url;

    public array $headers = [];

    public array $payload = [];

    public $queue;

    public int $tries = 1;

    public function handle()
    {
        try {
            Http::timeout(5)
                ->retry(3, 250)
                ->withHeaders($this->headers)
                ->acceptJson()
                ->asJson()
                ->post($this->url, $this->payload);
        } catch (Throwable $e) {
            if ($e instanceof RequestException) {
                logger('Could not reach '.parse_url($this->url, PHP_URL_HOST), [
                    'status' => $e->response->status() ?? null,
                    'response' => $e->response->json('message') ?? null,
                ]);
            }
        }
    }
}

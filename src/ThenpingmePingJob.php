<?php

namespace Thenpingme;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Thenpingme\Exceptions\ThenpingmePingException;

class ThenpingmePingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $url;

    public $headers = [];

    public $payload = [];

    public $queue;

    public $response;

    public $tries = 1;

    public function handle()
    {
        $response = Http::timeout(5)
            ->retry(3, 250)
            ->withHeaders($this->headers)
            ->asJson()
            ->post($this->url, $this->payload);

        if (! $response->successful()) {
            throw ThenpingmePingException::couldNotPing($response->status(), $response->json());
        }
    }
}

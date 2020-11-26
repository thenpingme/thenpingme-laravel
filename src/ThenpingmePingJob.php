<?php

namespace Thenpingme;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Zttp\Zttp;

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
        $response = Zttp::withHeaders($this->headers)
            ->accept('application/json')
            ->asJson()
            ->post($this->url, $this->payload);

        if (! $response->isSuccess()) {
            logger('Could not reach '.parse_url($this->url, PHP_URL_HOST), [
                'status' => $response->status() ?? null,
                'response' => data_get($response->json(), 'message'),
            ]);
        }
    }
}

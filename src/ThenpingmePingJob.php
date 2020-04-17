<?php

namespace Thenpingme;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Thenpingme\Exceptions\ThenpingmePingException;

class ThenpingmePingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $url;

    public $headers = [];

    public $payload = [];

    public $queue;

    public $response;

    public function handle()
    {
        $response = Http::withHeaders($this->headers)
            ->asJson()
            ->post($this->url, $this->payload);

        if (! Str::startsWith($response->status(), '2')) {
            throw ThenpingmePingException::couldNotPing($response->status());
        }
    }
}

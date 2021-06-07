<?php

namespace Thenpingme\Client;

use Illuminate\Support\Facades\Config;
use Thenpingme\Exceptions\CouldNotSendPing;
use Thenpingme\Signer\Signer;
use Thenpingme\Signer\ThenpingmeSigner;
use Thenpingme\ThenpingmePingJob;

class ThenpingmeClient implements Client
{
    protected array $payload = [];

    protected ThenpingmePingJob $pingJob;

    protected ?string $secret = null;

    protected Signer $signer;

    protected ?string $url = null;

    public function __construct()
    {
        $this->pingJob = app(ThenpingmePingJob::class);
        $this->signer = app(ThenpingmeSigner::class);

        $this->secret = Config::get('thenpingme.signing_key');
    }

    public static function setup(): Client
    {
        return (new ThenpingmeClient)
            ->endpoint(sprintf('/projects/%s/setup', Config::get('thenpingme.project_id')))
            ->useSecret(Config::get('thenpingme.project_id'));
    }

    public static function ping(): Client
    {
        return (new ThenpingmeClient)
            ->endpoint(sprintf('/projects/%s/ping', Config::get('thenpingme.project_id')))
            ->useSecret(Config::get('thenpingme.signing_key'));
    }

    public static function sync(): Client
    {
        return (new ThenpingmeClient)
            ->endpoint(sprintf('/projects/%s/sync', Config::get('thenpingme.project_id')))
            ->useSecret(Config::get('thenpingme.signing_key'));
    }

    public function baseUrl(): ?string
    {
        return config('thenpingme.api_url');
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function dispatch(): void
    {
        if (! config('thenpingme.enabled')) {
            return;
        }

        if (blank($this->url)) {
            throw CouldNotSendPing::missingUrl();
        }

        if (! $this->secret) {
            throw CouldNotSendPing::missingSigningSecret();
        }

        $this->pingJob->headers = $this->headers();

        config('thenpingme.queue_ping')
            ? dispatch($this->pingJob)
                ->onConnection(config('thenpingme.queue_connection'))
                ->onQueue(config('thenpingme.queue_name'))
            : dispatch_now($this->pingJob);
    }

    public function endpoint(string $url): self
    {
        if (is_null($baseUrl = $this->baseUrl())) {
            throw CouldNotSendPing::missingBaseUrl();
        }

        $this->url = $this->pingJob->url = vsprintf('%s/%s', [
            rtrim($baseUrl, '/'),
            ltrim($url, '/'),
        ]);

        return $this;
    }

    public function headers(): array
    {
        if (is_null($this->secret)) {
            throw CouldNotSendPing::missingSigningSecret();
        }

        return [
            'Signature' => $this->signer->calculateSignature($this->payload, $this->secret),
        ];
    }

    public function payload(array $payload): Client
    {
        $this->payload = $this->pingJob->payload = $payload;

        return $this;
    }

    public function useSecret(?string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }
}

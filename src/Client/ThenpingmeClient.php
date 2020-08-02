<?php

namespace Thenpingme\Client;

use Illuminate\Support\Facades\Config;
use Thenpingme\Exceptions\CouldNotSendPing;
use Thenpingme\Signer\ThenpingmeSigner;
use Thenpingme\ThenpingmePingJob;

class ThenpingmeClient implements Client
{
    /** @var array */
    protected $payload = [];

    /** @var \Thenpingme\ThenpingmePingJob */
    protected $pingJob;

    /** @var string */
    protected $secret;

    /** @var \Thenpingme\Signer\Signer */
    protected $signer;

    /** @var string */
    public $url;

    public function __construct()
    {
        $this->pingJob = app(ThenpingmePingJob::class);
        $this->signer = app(ThenpingmeSigner::class);

        $this->secret = Config::get('thenpingme.signing_key');
    }

    public static function setup(): Client
    {
        return (new static())
            ->endpoint(sprintf('/projects/%s/setup', Config::get('thenpingme.project_id')))
            ->useSecret(Config::get('thenpingme.project_id'));
    }

    public static function ping(): Client
    {
        return (new static())
            ->endpoint(sprintf('/projects/%s/ping', Config::get('thenpingme.project_id')))
            ->useSecret(Config::get('thenpingme.signing_key'));
    }

    public static function sync(): Client
    {
        return (new static)
            ->endpoint(sprintf('/projects/%s/sync', Config::get('thenpingme.project_id')))
            ->useSecret(Config::get('thenpingme.signing_key'));
    }

    public function baseUrl(): ?string
    {
        return config('thenpingme.api_url');
    }

    public function dispatch(): void
    {
        if (! $this->url) {
            throw CouldNotSendPing::missingUrl();
        }

        if (! $this->secret) {
            throw CouldNotSendPing::missingSigningSecret();
        }

        $this->pingJob->headers = $this->headers();

        dispatch($this->pingJob);
    }

    public function endpoint($url): self
    {
        if (! $this->baseUrl()) {
            throw CouldNotSendPing::missingBaseUrl();
        }

        $this->url = $this->pingJob->url = vsprintf('%s/%s', [
            rtrim($this->baseUrl(), '/'),
            ltrim($url, '/'),
        ]);

        return $this;
    }

    public function headers()
    {
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

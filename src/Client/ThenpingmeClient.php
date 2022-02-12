<?php

declare(strict_types=1);

namespace Thenpingme\Client;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Thenpingme\Exceptions\CouldNotSendPing;
use Thenpingme\Signer\Signer;
use Thenpingme\Signer\ThenpingmeSigner;
use Thenpingme\ThenpingmePingJob;

final class ThenpingmeClient implements Client
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
        return (new self)
            ->endpoint(sprintf('/projects/%s/setup', Config::get('thenpingme.project_id')))
            ->useSecret(Config::get('thenpingme.project_id'));
    }

    public static function ping(): Client
    {
        return (new self)
            ->endpoint(sprintf('/projects/%s/ping', Config::get('thenpingme.project_id')))
            ->useSecret(Config::get('thenpingme.signing_key'));
    }

    public static function sync(): Client
    {
        return (new self)
            ->endpoint(sprintf('/projects/%s/sync', Config::get('thenpingme.project_id')))
            ->useSecret(Config::get('thenpingme.signing_key'));
    }

    public function baseUrl(): ?string
    {
        return Config::get('thenpingme.api_url');
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function dispatch(): void
    {
        if (! Config::get('thenpingme.enabled')) {
            Log::warning(__('thenpingme::translations.disabled'));

            return;
        }

        if (blank($this->url)) {
            throw CouldNotSendPing::missingUrl();
        }

        if (! $this->secret) {
            throw CouldNotSendPing::missingSigningSecret();
        }

        $this->pingJob->headers = $this->headers();

        Config::get('thenpingme.queue_ping')
            ? dispatch($this->pingJob)
                ->onConnection(Config::get('thenpingme.queue_connection'))
                ->onQueue(Config::get('thenpingme.queue_name'))
            : dispatch_sync($this->pingJob);
    }

    public function endpoint(string $url): static
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

    public function payload(array $payload): static
    {
        $this->payload = $this->pingJob->payload = $payload;

        return $this;
    }

    public function useSecret(?string $secret): static
    {
        $this->secret = $secret;

        return $this;
    }
}

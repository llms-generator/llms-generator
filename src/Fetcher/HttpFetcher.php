<?php

namespace LlmsGenerator\Fetcher;

use GuzzleHttp\Client as GuzzleClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use RuntimeException;

class HttpFetcher implements FetcherInterface
{
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;

    public function __construct(?ClientInterface $client = null, ?RequestFactoryInterface $requestFactory = null, int $timeout = 30)
    {
        if ($client === null) {
            $client = new GuzzleClient([
                'timeout' => $timeout,
                'connect_timeout' => $timeout,
            ]);
        }

        $this->client = $client;
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
    }

    public function fetch(string $url): string
    {
        $request = $this->requestFactory->createRequest('GET', $url);

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new RuntimeException("HTTP request failed for {$url}: {$e->getMessage()}", 0, $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException("HTTP request failed for {$url} with status {$statusCode}");
        }

        return (string) $response->getBody();
    }
}

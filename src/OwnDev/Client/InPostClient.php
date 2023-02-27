<?php

namespace OwnDev\Client;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InPostClient
{
    /**
     * @var HttpClientInterface $httpClient
     */
    private HttpClientInterface $httpClient;

    /**
     * @var string $bearerToken
     */
    private string $bearerToken;

    private const BASE_ENDPOINT = 'https://api.inpost.pl/v1';
    private const GET_POINT_INFO = '/points/:point';

    public function __construct(
        HttpClientInterface $httpClient
    ) {
        $this->httpClient = $httpClient;
    }

    /**
     * @return string
     */
    public function getBearerToken(): string
    {
        return $this->bearerToken;
    }

    /**
     * @param string $bearerToken
     * @return InPostClient
     */
    public function setBearerToken(string $bearerToken): InPostClient
    {
        $this->bearerToken = $bearerToken;

        return $this;
    }

    /**
     * @param string $code
     * @return JsonResponse
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getPointInfo(string $code): JsonResponse
    {
        $client = null;
        $content = null;
        $endpoint = str_replace(':point', $code, self::BASE_ENDPOINT . self::GET_POINT_INFO);

        try {
            $client = $this->httpClient->request(
                Request::METHOD_GET,
                $endpoint,
                [
                    'base_uri' => self::BASE_ENDPOINT,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->bearerToken
                    ],
                    'verify_host' => false,
                    'verify_peer' => false,
                    'timeout' => 30
                ]
            );

            if ($client->getStatusCode() != 200) {
                return new JsonResponse([
                    'STATUS' => $this->getResponseStatus($client->getStatusCode()),
                    'MESSAGE' => $content
                ], $client->getStatusCode());
            }

            $content = json_decode($client->getContent(), true);

            if (is_null($content)) {
                return new JsonResponse([
                    'STATUS' => 'ERROR',
                    'MESSAGE' => null
                ], $client->getStatusCode());
            }
        } catch (Exception $e) {
            return new JsonResponse([
                'STATUS' => 'ERROR',
                'MESSAGE' => $content
            ], $client->getStatusCode());
        }

        return new JsonResponse([
            'STATUS' => 'OK',
            'MESSAGE' => $content
        ], $client->getStatusCode());
    }

    /**
     * @param $statusCode
     * @return string
     */
    protected function getResponseStatus($statusCode): string
    {
        switch ($statusCode) {
            case Response::HTTP_BAD_REQUEST:
            case Response::HTTP_BAD_GATEWAY:
            case Response::HTTP_FORBIDDEN:
            case Response::HTTP_UNAUTHORIZED:
            default:
                $message = "ERROR";
                break;
            case Response::HTTP_OK:
                $message = "OK";
                break;
        }

        return $message;
    }
}

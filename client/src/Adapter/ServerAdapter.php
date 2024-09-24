<?php

declare(strict_types=1);
/**
 * @by ProfStep, inc. 24.09.2024
 * @website: https://profstep.com
 */

namespace App\Adapter;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

/**
 * Class ServerAdapter
 */
class ServerAdapter
{
    public const ENDPOINT = 'https://localhost';

    public function __construct(
        protected HttpClientInterface $httpClient,
        protected EncoderInterface $encoder,
        protected DecoderInterface $decoder,
    ) {
    }

    /**
     * @param array $data
     * @param string $entityType
     * @return array
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function save(array $data, string $entityType): array
    {
        $options = [
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'body' => $this->encoder->encode($data, 'json'),
            'verify_host' => 0,
            'verify_peer' => 0
        ];

        $isEntityExists = isset($data['id']);

        $request = $this->httpClient
            ->request(
                $isEntityExists ? 'PUT' : 'POST',
                static::ENDPOINT . '/api/' . $entityType . ($isEntityExists ? '/' . $data['id'] : ''),
                $options
            );

        return $this->decoder->decode($request->getContent(), 'json');
    }

    /**
     * @param int $id
     * @param string $entityType
     * @return array
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getById(int $id, string $entityType): array
    {
        $options = [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
            'verify_host' => 0,
            'verify_peer' => 0
        ];

        $request = $this->httpClient
            ->request(
                'GET',
                static::ENDPOINT . "/api/$entityType/" . $id,
                $options
            );

        return $this->decoder->decode($request->getContent(), 'json');
    }

    /**
     * @param int $id
     * @param string $entityType
     * @return true
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function delete(int $id, string $entityType): true
    {
        $options = [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
            'verify_host' => 0,
            'verify_peer' => 0
        ];

        $request = $this->httpClient
            ->request(
                'DELETE',
                static::ENDPOINT . "/api/$entityType/" . $id,
                $options
            );

        return true;
    }

    /**
     * @param string $entityType
     * @return array
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getAll(string $entityType): array
    {
        $request = $this->httpClient
            ->request(
                'GET',
                static::ENDPOINT . "/api/$entityType?pagination=false",
                [
                    'verify_host' => 0,
                    'verify_peer' => 0
                ]
            );

        return $this->decoder->decode($request->getContent(), 'json');
    }
}
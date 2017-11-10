<?php
declare(strict_types=1);

namespace rpkamp\Mailhog;

use Generator;
use Http\Client\HttpClient;
use Http\Message\RequestFactory;
use rpkamp\Mailhog\Message\Message;
use rpkamp\Mailhog\Message\MessageFactory;

class MailhogClient
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var string
     */
    private $baseUri;

    public function __construct(HttpClient $client, RequestFactory $requestFactory, string $baseUri)
    {
        $this->httpClient = $client;
        $this->requestFactory = $requestFactory;
        $this->baseUri = $baseUri;
    }

    public function getAllMessages(int $limit = 50): Generator
    {
        $start = 0;
        while (true) {
            $request = $this->requestFactory->createRequest(
                'GET',
                sprintf(
                    '%s/api/v2/messages?limit=%d&start=%d',
                    $this->baseUri,
                    $limit,
                    ++$start
                )
            );

            $response = $this->httpClient->sendRequest($request);

            $allMessageData = json_decode($response->getBody()->getContents(), true);

            if (0 === $allMessageData['count']) {
                return; // all done!
            }

            foreach ($allMessageData['items'] as $messageData) {
                yield MessageFactory::fromMailhogResponse($messageData);
            }
        }
    }

    public function getNumberOfMessages(): int
    {
        $request = $this->requestFactory->createRequest('GET', sprintf('%s/api/v2/messages?limit=1', $this->baseUri));

        $response = $this->httpClient->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true)['total'];
    }

    public function purgeMessages(): void
    {
        $request = $this->requestFactory->createRequest('DELETE', sprintf('%s/api/v1/messages', $this->baseUri));

        $this->httpClient->sendRequest($request);
    }

    public function releaseMessage(string $messageId, string $host, int $port, string $emailAddress): void
    {
        $body = json_encode([
            'Host' => $host,
            'Port' => (string) $port,
            'Email' => $emailAddress,
        ]);

        $request = $this->requestFactory->createRequest(
            'POST',
            sprintf('%s/api/v1/messages/%s/release', $this->baseUri, $messageId),
            [],
            $body
        );

        $this->httpClient->sendRequest($request);
    }

    public function getMessageById(string $messageId): Message
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf(
                '%s/api/v1/messages/%s',
                $this->baseUri,
                $messageId
            )
        );

        $response = $this->httpClient->sendRequest($request);

        $messageData = json_decode($response->getBody()->getContents(), true);

        if (null === $messageData) {
            throw NoSuchMessageException::forMessageId($messageId);
        }

        return MessageFactory::fromMailhogResponse($messageData);
    }
}

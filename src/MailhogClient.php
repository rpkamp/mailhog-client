<?php
declare(strict_types=1);

namespace rpkamp\Mailhog;

use Generator;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use rpkamp\Mailhog\Message\Message;
use rpkamp\Mailhog\Message\MessageFactory;
use rpkamp\Mailhog\Specification\Specification;
use RuntimeException;

use function array_filter;
use function assert;
use function count;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function rtrim;
use function sprintf;

class MailhogClient
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $baseUri
    ) {
        $this->baseUri = rtrim($baseUri, '/');
    }

    /**
     * @return Generator|Message[]
     */
    public function findAllMessages(int $limit = 50): Generator
    {
        $start = 0;
        while (true) {
            $request = $this->requestFactory->createRequest(
                'GET',
                sprintf(
                    '%s/api/v2/messages?limit=%d&start=%d',
                    $this->baseUri,
                    $limit,
                    $start
                )
            );

            $response = $this->httpClient->sendRequest($request);

            $allMessageData = json_decode($response->getBody()->getContents(), true);

            foreach ($allMessageData['items'] as $messageData) {
                yield MessageFactory::fromMailhogResponse($messageData);
            }

            $start += $limit;

            if ($start >= $allMessageData['total']) {
                return;
            }
        }
    }

    /**
     * @return Message[]
     */
    public function findLatestMessages(int $numberOfMessages): array
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf(
                '%s/api/v2/messages?limit=%d',
                $this->baseUri,
                $numberOfMessages
            )
        );

        $response = $this->httpClient->sendRequest($request);

        $allMessageData = json_decode($response->getBody()->getContents(), true);

        $messages = [];
        foreach ($allMessageData['items'] as $messageData) {
            $messages[] = MessageFactory::fromMailhogResponse($messageData);
        }

        return $messages;
    }

    /**
     * @return Message[]
     */
    public function findMessagesSatisfying(Specification $specification): array
    {
        return array_filter(
            iterator_to_array($this->findAllMessages()),
            static function (Message $message) use ($specification) {
                return $specification->isSatisfiedBy($message);
            }
        );
    }

    public function getLastMessage(): Message
    {
        $messages = $this->findLatestMessages(1);

        if (count($messages) === 0) {
            throw new NoSuchMessageException('No last message found. Inbox empty?');
        }

        return $messages[0];
    }

    public function getNumberOfMessages(): int
    {
        $request = $this->requestFactory->createRequest('GET', sprintf('%s/api/v2/messages?limit=1', $this->baseUri));

        $response = $this->httpClient->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true)['total'];
    }

    public function deleteMessage(string $messageId): void
    {
        $request = $this->requestFactory->createRequest('DELETE', sprintf('%s/api/v1/messages/%s', $this->baseUri, $messageId));

        $this->httpClient->sendRequest($request);
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

        if (false === $body) {
            throw new RuntimeException(
                sprintf('Unable to JSON encode data to release message %s', $messageId)
            );
        }

        $request = $this->requestFactory->createRequest(
            'POST',
            sprintf('%s/api/v1/messages/%s/release', $this->baseUri, $messageId)
        )->withBody($this->streamFactory->createStream($body));

        /**
         * Help PHPStan see that this is actually a RequestInterface. withBody is a method
         * on MessageInterface and has return type MessageInterface in version 1.0 of psr/http-message.
         * This has been fixed in version 2.0 of psr/http-message where the return type is `static`.
         */
        assert($request instanceof RequestInterface);

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

<?php
declare(strict_types=1);

namespace rpkamp\Mailhog;

use Http\Client\HttpClient;
use Http\Message\RequestFactory;

class MailhogApiV1Client
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

    /**
     * @return Message[]
     */
    public function getAllMessages(): array
    {
        $request = $this->requestFactory->createRequest('GET', sprintf('%s/api/v1/messages', $this->baseUri));

        $response = $this->httpClient->sendRequest($request);

        $allMessageData = json_decode($response->getBody()->getContents(), true);

        $messages = [];
        foreach ($allMessageData as $messageData) {
            $recipients = [];
            foreach ($messageData['To'] as $recipient) {
                $recipients[] = sprintf('%s@%s', $recipient['Mailbox'], $recipient['Domain']);
            }

            $sender = sprintf('%s@%s', $messageData['From']['Mailbox'], $messageData['From']['Domain']);

            $messages[] = new Message(
                $messageData['ID'],
                $sender,
                $recipients,
                $messageData['Content']['Headers']['Subject'][0],
                $messageData['Content']['Body']
            );
        }

        return $messages;
    }

    public function getNumberOfMessages(): int
    {
        return count($this->getAllMessages());
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

        $recipients = [];
        foreach ($messageData['To'] as $recipient) {
            $recipients[] = sprintf('%s@%s', $recipient['Mailbox'], $recipient['Domain']);
        }

        $sender = sprintf('%s@%s', $messageData['From']['Mailbox'], $messageData['From']['Domain']);

        $message = new Message(
            $messageData['ID'],
            $sender,
            $recipients,
            $messageData['Content']['Headers']['Subject'][0],
            $messageData['Content']['Body']
        );
        return $message;
    }
}

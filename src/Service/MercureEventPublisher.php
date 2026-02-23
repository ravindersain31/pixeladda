<?php

namespace App\Service;

use Symfony\Component\Mercure\Update;
use Symfony\Component\Mercure\HubInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MercureEventPublisher
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function publishEvent(string $topic, array $data, bool $isPrivate = false, ?string $domain = null, array $options = []): void
    {
        $domain = $domain ?? $this->parameterBag->get('APP_ADMIN_HOST');
        $fullTopic = sprintf("%s_%s", $domain, $topic);

        $triggeredBySession = $options['triggeredBySession'] ?? null;

        try {
            $updatePayload = [
                'topic' => $fullTopic,
                'data' => $data,
            ];

            if ($triggeredBySession) {
                $updatePayload['triggeredBySession'] = $triggeredBySession;
            }

            $update = new Update(
                topics: $fullTopic,
                data: json_encode($updatePayload, JSON_THROW_ON_ERROR),
                private: $isPrivate
            );

            $this->hub->publish($update);
        } catch (\JsonException $jsonException) {
            $this->logger->error("JSON encoding error while publishing event", [
                'error' => $jsonException->getMessage(),
                'topic' => $fullTopic,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            $errorDetails = [
                'error' => $e->getMessage(),
                'topic' => $fullTopic,
                'data' => $data,
                'isPrivate' => $isPrivate,
            ];

            $this->logger->error("Error publishing event to Mercure", $errorDetails);
        }
    }


    public function publishWithTopicPrefix(string $baseTopic, string $subTopic, array $data, bool $isPrivate = false): void
    {
        $topic = sprintf("%s/%s", $baseTopic, $subTopic);
        $this->publishEvent($topic, $data, $isPrivate);
    }

    public function publishToMultipleTopics(array $topics, array $data, bool $isPrivate = false): void
    {
        foreach ($topics as $topic) {
            $this->publishEvent($topic, $data, $isPrivate);
        }
    }

    public function publishHeartbeat(string $topic, array $data = [], bool $isPrivate = false, ?string $domain = null): void
    {
        $domain = $domain ?? $this->parameterBag->get('APP_ADMIN_HOST');
        $fullTopic = sprintf("%s_%s", $domain, $topic);

        $heartbeatData = [
            'type' => 'heartbeat',
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        try {
            $update = new Update(
                topics: $fullTopic,
                data: json_encode([
                    'topic' => $fullTopic,
                    'data' => $heartbeatData,
                ], JSON_THROW_ON_ERROR),
                private: false // Heartbeats are typically public
            );

            $this->hub->publish($update);
        } catch (\JsonException $jsonException) {
            $this->logger->error("JSON encoding error while publishing heartbeat", [
                'error' => $jsonException->getMessage(),
                'topic' => $fullTopic,
                'data' => $heartbeatData,
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Error publishing heartbeat to Mercure", [
                'error' => $e->getMessage(),
                'topic' => $fullTopic,
                'data' => $heartbeatData,
            ]);
        }
    }
}

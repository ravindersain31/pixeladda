<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SlackManager
{
    const SALES = "SALES";

    const DESIGNER = "DESIGNERS";

    const ADWORDS = "ADWORDS";

    const CSR_DECLINES = "CSR_DECLINES";

    const ERROR_LOG = "ERROR_LOG";

    const WAREHOUSE_PRINTER_ALERT = "WAREHOUSE_PRINTER_ALERT";

    const SHIPPING_EASY = "SHIPPING_EASY";

    const ADDRESS_CHANGE = "ADDRESS_CHANGE";

    const ORDER_APPROVED = "ORDER_APPROVED";

    public function __construct(private readonly ParameterBagInterface $parameterBag, private readonly LoggerInterface $logger)
    {
    }

    public function send($channel, $message): bool
    {
        try {
            // $url = $this->getWebHookUrl($channel);
            // $client = HttpClient::create();
            // $response = $client->request('POST', $url, [
            //     'body' => $message,
            // ]);

            // $statusCode = $response->getStatusCode();
            // $content    = $response->getContent(false); 

            // if ($statusCode === 200 && $content === 'ok') {
            //     return true;
            // }

            // $this->logger->error("Slack error", [
            //     'status'   => $statusCode,
            //     'channel'   => $channel,
            //     'response' => $content,
            // ]);
            return false;

        } catch (TransportExceptionInterface | \Throwable $e) {
            // $this->logger->error("Slack send failed", [
            //     'exception' => $e->getMessage(),
            //     'channel'   => $channel,
            // ]);
            return false;
        }

    }

    private function getWebHookUrl($channel): string
    {
        return $this->parameterBag->get("SLACK_HOOK_" . $channel);
    }


}

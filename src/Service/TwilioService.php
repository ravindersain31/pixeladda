<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twilio\Rest\Client;
use Twilio\Rest\Api\V2010\Account\CallInstance;

class TwilioService
{
    private ?Client $client;

    private string $twilioNumber = '+18885658840';

    public function __construct(ParameterBagInterface $parameterBag, private readonly OrderLogger $logger)
    {
        $sid = $parameterBag->get('TWILIO_SID');
        $token = $parameterBag->get('TWILIO_TOKEN');
        $this->client = new Client($sid, $token);
    }

    public function sendSms(string $to, string $message, ?Order $order = null)
    {
        try{
            return $this->client->messages->create(
            $this->formatPhoneNo($to),
            [
                'from' => $this->twilioNumber,
                'body' => $message,
                ]
            );
        }catch(\Exception $e){
            if ($order) {
                $this->logger->setOrder($order);
                $this->logger->log('That message was not delivered due to ' . $e->getMessage());
            }
        }
    }

    public function sendVoiceCall(string $to, string $message, ?Order $order = null)
    {
        try{
            return $this->client->calls->create(
            $this->formatPhoneNo($to),
            $this->twilioNumber,
            [
                'twiml' => "<Response><Say>" . $message . "</Say><Pause length='2'/></Response>"
                ]
            );
        }catch(\Exception $e){
            if($order){
                $this->logger->setOrder($order);
                $this->logger->log('That call was not delivered due to ' . $e->getMessage());
            }
        }
    }

    private function formatPhoneNo($no) {
        if (str_starts_with($no, '0') || str_starts_with($no, '00') || str_starts_with($no, '+')) {
            return $no;
        }
        return preg_replace('/^(?:\+?1|0)?/','+1', $no);
    }

}
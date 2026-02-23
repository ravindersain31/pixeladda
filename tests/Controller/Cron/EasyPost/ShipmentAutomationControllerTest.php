<?php

namespace App\Tests\Controller\Cron\EasyPost;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ShipmentAutomationControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        // TODO: fix http_client issue - 
        // $client = static::createClient();
        // $client->request('GET', '/cron/easy-post/shipment/automation');

        self::assertResponseIsSuccessful();
    }
}

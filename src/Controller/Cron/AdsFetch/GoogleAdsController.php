<?php

namespace App\Controller\Cron\AdsFetch;

use App\Service\CogsHandlerService;
use App\Service\SlackManager;
use App\SlackSchema\ErrorLogSchema;
use App\Entity\Store;
use Doctrine\ORM\EntityManagerInterface;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsException;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest;
use Google\ApiCore\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleAdsController extends AbstractController
{
    private array $storeMapping = [
        '6764248986' => 'YSP'
    ];

    private ?GoogleAdsClient $googleAdsClient = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
        private readonly CogsHandlerService $cogs,
        private readonly SlackManager $slackManager,
    ) {}

    #[Route('/fetch-google-ads-data', name: 'cron_fetch_ads_data')]
    public function fetchYesterdayData(Request $request): Response
    {
        $date = $this->getDateFromRequest($request);
        return $this->fetchGoogleAdsData($date);
    }

    #[Route('/fetch-google-ads-data-today', name: 'cron_fetch_ads_data_today')]
    public function fetchTodayData(): Response
    {
        $date = new \DateTimeImmutable((new \DateTime())->format('Y-m-d'));
        return $this->fetchGoogleAdsData($date);
    }

    private function getDateFromRequest(Request $request): \DateTimeImmutable
    {
        $date = new \DateTime();
        if ($request->get('date')) {
            $date = \DateTime::createFromFormat('Y-m-d', $request->get('date')) ?: $date;
        }
        return new \DateTimeImmutable($date->format('Y-m-d'));
    }

    private function fetchGoogleAdsData(\DateTimeImmutable $date): Response
    {
        $synced = false;
        try {
            $this->authenticate();
            $stores = $this->entityManager->getRepository(Store::class)->findAllByShortName(array_values($this->storeMapping));
            foreach ($stores as $store) {
                $customerId = array_flip($this->storeMapping)[$store->getShortName()] ?? null;
                if (!$customerId) {
                    throw new \Exception("Customer ID not found for store: " . $store->getShortName());
                }
                $data = $this->getCampaigns($date, $customerId);
                $this->cogs->saveGoogleAdsSpent($date, $store, $data);
            }
            $synced = true;
        } catch (\Throwable $exception) {
            $this->handleException($exception);
        } finally {
            $this->entityManager->close();
        }

        return $this->json([
            'status' => $synced ? 'ok' : 'failed',
            'date' => $date->format('Y-m-d'),
        ]);
    }

    private function authenticate(): void
    {
        $projectDir = realpath($this->parameterBag->get('kernel.project_dir') . '/public/ini/google_ads_php.ini');
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile($projectDir)->build();
        $this->googleAdsClient = (new GoogleAdsClientBuilder())
            ->fromFile($projectDir)
            ->withOAuth2Credential($oAuth2Credential)
            ->build();
    }

    private function getCampaigns(\DateTimeImmutable $date, string $customerId): array
    {
        if (!$this->googleAdsClient) {
            throw new \RuntimeException('Google Ads Client not authenticated.');
        }

        $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
        $query = $this->buildCampaignQuery($date);
        $stream = $googleAdsServiceClient->searchStream(SearchGoogleAdsStreamRequest::build($customerId, $query));

        return $this->processCampaignData($stream, $customerId, $date);
    }

    private function buildCampaignQuery(\DateTimeImmutable $date): string
    {
        $formattedDate = $date->format('Y-m-d');
        return <<<SQL
SELECT campaign.id, campaign.name, metrics.cost_micros, metrics.clicks, 
       metrics.impressions, metrics.revenue_micros, metrics.conversions
FROM campaign
WHERE segments.date BETWEEN '{$formattedDate}' AND '{$formattedDate}'
  AND metrics.cost_micros > 0
SQL;
    }

    private function processCampaignData($stream, string $customerId, \DateTimeImmutable $date): array
    {
        $data = [
            'AccountId' => $customerId,
            'AccountName' => $this->storeMapping[$customerId],
            'TimePeriod' => $date->format('Y-m-d'),
            'Clicks' => 0,
            'Impressions' => 0,
            'Revenue' => 0,
            'Conversions' => 0,
            'ConversionRate' => 0,
            'Spend' => 0,
            'ReturnOnAdSpend' => 0,
            'CampaignsNames' => '',
        ];

        foreach ($stream->iterateAllElements() as $row) {
            $metrics = $row->getMetrics();
            $data['Clicks'] += $metrics->getClicks();
            $data['Impressions'] += $metrics->getImpressions();
            $data['Spend'] += round($metrics->getCostMicros() / 1_000_000, 2);
            $data['Revenue'] += round($metrics->getRevenueMicros() / 1_000_000, 2);
            $data['Conversions'] += $metrics->getConversions();
        }

        $data['ConversionRate'] = $data['Clicks'] > 0
            ? round(($data['Conversions'] / $data['Clicks']) * 100, 2)
            : 0;
        $data['ReturnOnAdSpend'] = $data['Spend'] > 0
            ? round(($data['Revenue'] / $data['Spend']) * 100, 2)
            : 0;

        return $data;
    }

    private function handleException(\Throwable $exception): void
    {
        if ($exception instanceof GoogleAdsException) {
            $errors = array_map(
                fn($e) => $e->getMessage(),
                iterator_to_array($exception->getGoogleAdsFailure()->getErrors())
            );
            $message = "*GoogleAdsException* \n *Messages:* ```" . implode("\n", $errors) . "```";
        } elseif ($exception instanceof ApiException) {
            $message = "*Google Ads API Exception* \n *Message:* ```" . $exception->getMessage() . "```";
        } else {
            $message = "*General Exception* \n *Message:* ```" . $exception->getMessage() . "```";
        }

        $this->slackManager->send(SlackManager::ERROR_LOG, ErrorLogSchema::get($message));
    }
}
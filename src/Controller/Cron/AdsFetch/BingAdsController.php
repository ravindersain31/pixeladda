<?php

namespace App\Controller\Cron\AdsFetch;

use App\Entity\Store;
use App\Service\CogsHandlerService;
use App\Service\ThirdPartyTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Microsoft\BingAds\Auth\ApiEnvironment;
use Microsoft\BingAds\Auth\AuthorizationData;
use Microsoft\BingAds\Auth\OAuthDesktopMobileAuthCodeGrant;
use Microsoft\BingAds\Auth\OAuthScope;
use Microsoft\BingAds\Auth\ServiceClient;
use Microsoft\BingAds\Auth\ServiceClientType;
use Microsoft\BingAds\V13\Reporting\Date;
use Microsoft\BingAds\V13\Reporting\AccountPerformanceReportColumn;
use Microsoft\BingAds\V13\Reporting\AccountPerformanceReportRequest;
use Microsoft\BingAds\V13\Reporting\AccountReportScope;
use Microsoft\BingAds\V13\Reporting\PollGenerateReportRequest;
use Microsoft\BingAds\V13\Reporting\ReportAggregation;
use Microsoft\BingAds\V13\Reporting\ReportFormat;
use Microsoft\BingAds\V13\Reporting\ReportRequestStatusType;
use Microsoft\BingAds\V13\Reporting\ReportTime;
use Microsoft\BingAds\V13\Reporting\ReportTimeZone;
use Microsoft\BingAds\V13\Reporting\SubmitGenerateReportRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class BingAdsController extends AbstractController
{
    private array $storeMapping = [
        '138662928' => 'YSP'
    ];

    private string $developerToken;
    private string $clientId;
    private string $clientSecret;
    private string $customerId;
    private string $accountId;
    private string $environment = ApiEnvironment::Production;
    private string $scope = OAuthScope::MSADS_MANAGE;
    private bool $hasCompletedData = true;

    private ?ServiceClient $serviceClient = null;

    public function __construct(
        ParameterBagInterface                   $parameterBag,
        private readonly EntityManagerInterface $entityManager,
        private readonly CogsHandlerService     $cogs,
        private readonly ThirdPartyTokenService $thirdPartyTokenService,
    )
    {
        $this->developerToken = $parameterBag->get('BING_ADS_DEVELOPER_TOKEN');
        $this->clientId = $parameterBag->get('BING_ADS_CLIENT_ID');
        $this->clientSecret = $parameterBag->get('BING_ADS_CLIENT_SECRET');
        $this->customerId = $parameterBag->get('BING_ADS_CUSTOMER_ID');
        $this->accountId = $parameterBag->get('BING_ADS_ACCOUNT_ID');
    }

    #[Route('/fetch-bing-ads-data', name: 'cron_fetch_bing_ads_data')]
    public function index(Request $request): Response
    {
        $date = new \DateTimeImmutable();
        $date = $date->modify('-1 day');
        if ($request->get('date')) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        }

        if ($request->get('force-token-refresh')) {
            $authCode = $request->get('auth_code');
            $this->getBingAdsRefreshToken($authCode);
        }

        return $this->fetchBingAdsData($date);
    }

    #[Route('/save-refresh-token', name: 'cron_save_refresh_token')]
    public function saveRefreshToken(Request $request): Response
    {
        $refreshToken = $request->get('refresh_token');
        $this->thirdPartyTokenService->saveBingAdsRefreshToken($refreshToken);

        return $this->json(['status' => 'ok']);
    }

    #[Route('/fetch-bing-ads-data-today', name: 'cron_fetch_bing_ads_data_today')]
    public function fetchTodayData(Request $request): Response
    {
        $date = new \DateTimeImmutable();
        $this->hasCompletedData = false;
        return $this->fetchBingAdsData($date);
    }

    private function fetchBingAdsData(\DateTimeImmutable $date): Response
    {
        $synced = false;
        try {
            $this->authenticate();
            if($this->serviceClient) {
                $reportRequest = $this->createBingAdsReportRequest($date);

                $submitRequest = new SubmitGenerateReportRequest();
                $submitRequest->ReportRequest = $reportRequest;

                $submitResponse = $this->serviceClient->GetService()->SubmitGenerateReport($submitRequest);
                $this->downloadReportAndParse($submitResponse->ReportRequestId);
                $synced = true;
            }
        } catch (\SoapFault $fault) {
            if (isset($fault->detail->AdApiFaultDetail->Errors)) {
                $errorMessage = $fault->detail->AdApiFaultDetail->Errors->AdApiError->Message;
            } elseif (isset($fault->detail->ApiFaultDetail->OperationErrors)) {
                $errorMessage = $fault->detail->ApiFaultDetail->OperationErrors->OperationError->Message;
            } else {
                $errorMessage = $fault->getMessage();
            }
        }

        $this->entityManager->close();
        return $this->json([
            'status' => $synced ? 'ok' : 'failed',
            'date' => $date->format('Y-m-d'),
        ]);
    }

    private function downloadReportAndParse(string $reportRequestId): void
    {
        do {
            $reportPollRequest = new PollGenerateReportRequest();
            $reportPollRequest->ReportRequestId = $reportRequestId;
            $reportRequest = $this->serviceClient->GetService()->PollGenerateReport($reportPollRequest);

            if ($reportRequest->ReportRequestStatus->Status === ReportRequestStatusType::Pending) {
                sleep(10); // Wait 10 seconds before polling again if the report is still pending.
            }
        } while ($reportRequest->ReportRequestStatus->Status === ReportRequestStatusType::Pending);

        if ($reportRequest->ReportRequestStatus->Status === ReportRequestStatusType::Success) {
            $downloadUrl = $reportRequest->ReportRequestStatus->ReportDownloadUrl;

            // Download the report.
            $reportContents = file_get_contents($downloadUrl);

            $head = unpack("Vsig/vver/vflag/vmeth/vmodt/vmodd/Vcrc/Vcsize/Vsize/vnamelen/vexlen", substr($reportContents, 0, 30));
            $reportString = gzinflate(substr($reportContents, 30 + $head['namelen'] + $head['exlen'], $head['csize']));
            // Remove the BOM if present
            if (str_starts_with($reportString, "\u{FEFF}")) {
                $reportString = substr($reportString, 3);
            }
            // Parse the CSV report.
            $lines = explode(PHP_EOL, $reportString);
            $headerLine = array_shift($lines);
            $header = str_getcsv($headerLine, ',', '"', '\\');

            $header = array_map(function($h) {
                return trim($h, "\"\u{FEFF} "); // remove double quotes, BOM, spaces
            }, $header);

            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $data = str_getcsv($line, ',', '"', '\\');
                    $data = array_map(fn($v) => trim($v, "\" "), $data);
                    $row = array_combine($header, $data);
                    $this->saveData($row);
                }
            }
        } else {
        }
    }

    private function saveData(array $data): void
    {
        $storeShortName = $this->storeMapping[$data['AccountId'] ?? ''];
        $store = $this->entityManager->getRepository(Store::class)->findOneBy(['shortName' => $storeShortName]);
        $date = new \DateTimeImmutable($data['TimePeriod']);
        $this->cogs->saveBingAdsSpent($date, $store, $data);
    }

    private function createBingAdsReportRequest(\DateTimeImmutable $date): \SoapVar
    {
        $report = new AccountPerformanceReportRequest();
        $report->Format = ReportFormat::Csv;
        $report->ReturnOnlyCompleteData = $this->hasCompletedData;
        $report->ExcludeReportFooter = true;
        $report->ExcludeReportHeader = true;
        $report->Aggregation = ReportAggregation::Daily;

        $report->Scope = new AccountReportScope();
        $report->Scope->AccountIds = [];
        $report->Scope->AccountIds[] = $this->accountId;

        $report->Time = new ReportTime();

        $start = new Date();
        $start->Day   = (int) $date->format('d');
        $start->Month = (int) $date->format('m');
        $start->Year  = (int) $date->format('Y');

        $end = new Date();
        $end->Day   = (int) $date->format('d');
        $end->Month = (int) $date->format('m');
        $end->Year  = (int) $date->format('Y');

        $report->Time->CustomDateRangeStart = $start;
        $report->Time->CustomDateRangeEnd   = $end;
        $report->Time->ReportTimeZone = ReportTimeZone::CentralAmerica;

        $report->Columns = [
            AccountPerformanceReportColumn::AccountId,
            AccountPerformanceReportColumn::AccountName,
            AccountPerformanceReportColumn::TimePeriod,
            AccountPerformanceReportColumn::Clicks,
            AccountPerformanceReportColumn::Impressions,
            AccountPerformanceReportColumn::Revenue,
            AccountPerformanceReportColumn::Conversions,
            AccountPerformanceReportColumn::ConversionRate,
            AccountPerformanceReportColumn::Spend,
            AccountPerformanceReportColumn::ReturnOnAdSpend,
        ];
        return new \SoapVar($report, SOAP_ENC_OBJECT, 'AccountPerformanceReportRequest', $this->serviceClient->GetNamespace());
    }

    private function authenticate(): void
    {
        $refreshToken = $this->thirdPartyTokenService->getBingAdsRefreshToken();
        if (!$refreshToken) {
        } else {
            $authentication = (new OAuthDesktopMobileAuthCodeGrant())
                ->withClientId($this->clientId)
                ->withClientSecret($this->clientSecret)
                ->withEnvironment($this->environment)
                ->withOAuthScope($this->scope);

            $authorizationData = (new AuthorizationData())
                ->withAuthentication($authentication)
                ->withCustomerId($this->customerId)
                ->withAccountId($this->accountId)
                ->withDeveloperToken($this->developerToken);


            $authorizationData->Authentication->RequestOAuthTokensByRefreshToken($refreshToken);
            $this->thirdPartyTokenService->saveBingAdsRefreshToken($authorizationData->Authentication->OAuthTokens->RefreshToken);
            try {
                $this->serviceClient = new ServiceClient(ServiceClientType::ReportingVersion13, $authorizationData, $this->environment);
            } catch (\Exception $e) {
            }
        }
    }


    // https://login.microsoftonline.com/common/oauth2/v2.0/authorize?
    // client_id=2cda1a01-5b3f-4917-bcf3-2a3d206eb167&
    // response_type=code&
    // redirect_uri=https://www.yardsignplus.com/&
    // response_mode=query&
    // scope=https://ads.microsoft.com/msads.manage%20offline_access&
    // state=12345

    /**
     * @return void
     * This method is not used anywhere in codebase,
     * but can be useful when the refresh token was expired,
     * and we need to generate the new refresh token with new authorisation code.
     */
    private function getBingAdsRefreshToken(?string $authCode = null): void
    {
        $redirectUri = 'https://www.yardsignplus.com/';
        $authorizationCode = $authCode ?? 'M.C531_BAY.2.U.d0e2d8c3-5d87-24ca-52d1-a503f1d892a4';

        $url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'https://ads.microsoft.com/msads.manage offline_access',
            'redirect_uri' => $redirectUri,
            'code' => $authorizationCode,
            'grant_type' => 'authorization_code',
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
        ));

        $response = curl_exec($ch);
        if ($response === false) {
            dd('Curl error: ' . curl_error($ch));
        }
        $data = json_decode($response, true);
        dd($data);
    }

}

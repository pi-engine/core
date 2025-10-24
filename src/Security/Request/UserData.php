<?php

declare(strict_types=1);

namespace Pi\Core\Security\Request;

use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Jenssegers\Agent\Agent;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\Utility\Ip as IpUtility;
use Pi\Core\Service\UtilityService;
use Psr\Http\Message\ServerRequestInterface;

class UserData implements RequestSecurityInterface
{
    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'userData';

    public function __construct(
        CacheService   $cacheService,
        UtilityService $utilityService,
                       $config
    ) {
        $this->cacheService   = $cacheService;
        $this->utilityService = $utilityService;
        $this->config         = $config;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $securityStream
     *
     * @return array
     */
    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        // Check if the IP is in the whitelist
        if (
            (bool)$this->config['userData']['ignore_whitelist'] === true
            && isset($securityStream['ip']['data']['in_whitelist'])
            && (bool)$securityStream['ip']['data']['in_whitelist'] === true
        ) {
            return [
                'result' => true,
                'name'   => $this->name,
                'status' => 'ignore',
                'data'   => [],
            ];
        }

        // Get request params
        $serverParams = $request->getServerParams();
        $headers      = $request->getHeaders();

        // Set ip class
        $ipUtility = new IpUtility($this->config['ip'], $this->cacheService);
        $clientIp  = $ipUtility->getClientIp();

        // Set user agent
        $userAgent = $serverParams['HTTP_USER_AGENT'] ?? '';

        // Set device data
        $deviceData = $this->getDeviceData($userAgent);
        if (!$deviceData['result']) {
            return [
                'result' => false,
                'name'   => $this->name,
                'status' => 'unsuccessful',
                'data'   => $deviceData['error'],
            ];
        }

        // Set geo data
        if (
            isset($this->config['userData']['geo_location_path'])
            && !empty($this->config['userData']['geo_location_path'])
            && file_exists($this->config['userData']['geo_location_path'])
        ) {
            $geoData = $ipUtility->getGeoIpData($clientIp, $this->config['userData']['geo_location_path']);
            if (!$geoData['result']) {
                return [
                    'result' => false,
                    'name'   => $this->name,
                    'status' => 'unsuccessful',
                    'data'   => $geoData['error'],
                ];
            }
        } else {
            $geoData = [
                'data' => [
                    'ip'           => $clientIp,
                    'country'      => 'Unknown',
                    'country_code' => 'XX',
                    'city'         => 'Unknown',
                    'region'       => 'Unknown',
                    'region_code'  => 'Unknown',
                    'latitude'     => 0,
                    'longitude'    => 0,
                    'timezone'     => 'Unknown',
                ],
            ];
        }

        // Collect User Headers
        $clientHeaders = [
            'user_agent'  => $userAgent,
            'referer'     => $headers['Referer'][0] ?? 'Unknown',
            'accept_lang' => $headers['Accept-Language'][0] ?? 'Unknown',
        ];

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [
                'geo'    => $geoData['data'],
                'device' => $deviceData['data'],
                'client' => $clientHeaders,
            ],
        ];
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: User data for this request not found !';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }

    private function getDeviceData($userAgent): array
    {
        try {
            $agent = new Agent();
            $agent->setUserAgent($userAgent);

            return [
                'result' => true,
                'data'   => [
                    'browser'     => $agent->browser(),
                    'browser_ver' => $agent->version($agent->browser()) ?? 'Unknown',
                    'os'          => $agent->platform(),
                    'os_ver'      => $agent->version($agent->platform()) ?? 'Unknown',
                    'device'      => $agent->device() ?: 'Unknown',
                    'is_mobile'   => $agent->isMobile(),
                    'is_tablet'   => $agent->isTablet(),
                    'is_desktop'  => $agent->isDesktop(),
                    'is_robot'    => $agent->isRobot(),
                ],
                'error'  => [],
            ];
        } catch (Exception $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Device Data lookup failed: ' . $e->getMessage(),
                    'key'     => 'device-data-lookup-failed',
                ],
            ];
        }
    }
}
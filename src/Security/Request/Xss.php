<?php

namespace Pi\Core\Security\Request;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

class Xss implements RequestSecurityInterface
{
    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'xss';

    public function __construct($config)
    {
        $this->config = $config;
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
            (bool)$this->config['xss']['ignore_whitelist'] === true
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

        // Get request and query body
        $requestParams = $request->getParsedBody();
        $QueryParams   = $request->getQueryParams();
        $params        = array_merge($requestParams, $QueryParams);

        // Do check
        if (!empty($params)) {
            if ($this->detectXSS($params)) {
                return [
                    'result' => false,
                    'name'   => $this->name,
                    'status' => 'unsuccessful',
                    'data'   => [],
                ];
            }
        }

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [],
        ];
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: XSS attack detected';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }

    private function detectXSS($input): bool
    {
        // Common XSS patterns
        $xssPatterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is', // Detect <script> tags
            '/<[^>]+(?:on\w+)\s*=\s*[\'"]?[^\'" >]+[\'"]?/i', // Event handlers like onload, onclick
            '/(?:javascript|data)\s*:\s*(?![^\'"]*(?:base64|utf-7))/i', // javascript: and data: URIs
            '/vbscript\s*:\s*/i', // vbscript URIs
            '/<iframe\b[^>]*srcdoc\s*=\s*[\'"]?.*?[\'"]?\s*>/is', // <iframe> with srcdoc attribute
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is', // <iframe> tags
            '/<object\b[^>]*>(.*?)<\/object>/is', // <object> tags
            '/<embed\b[^>]*>(.*?)<\/embed>/is', // <embed> tags
            '/<applet\b[^>]*>(.*?)<\/applet>/is', // <applet> tags
            '/<meta\b[^>]*http-equiv\s*=\s*[\'"]?refresh[\'"]?.*?>/is', // <meta> refresh tags
            '/<img\b[^>]*src\s*=\s*[\'"]?\s*javascript:[^\'" >]+[\'"]?/is', // <img> tags with javascript in src
            '/<img\b[^>]*src\s*=\s*[\'"]?data:\s*[^\'" >]+;base64,[^\'"]+/is', // <img> tags with data URIs
            '/<[^>]+style\s*=\s*[\'"][^\'"]*(expression|url)\s*\(.*\)[^\'"]*[\'"]/is', // expression() or url() in style
            '/<link\b[^>]*href\s*=\s*[\'"]?\s*(?:javascript|data):[^\'" >]+[\'"]?/is', // <link> tags with javascript or data
            '/(&#x[a-fA-F0-9]{1,6};?|&#\d{1,7};?|&[a-zA-Z]{1,10};?)/i', // Encoded characters for obfuscation
            '/<form\b[^>]*action\s*=\s*[\'"]?\s*(?:javascript|data):[^\'" >]+[\'"]?/is', // <form> tags with javascript URIs
            '/<[^>]+formaction\s*=\s*[\'"]?\s*(?:javascript|data):[^\'" >]+[\'"]?/is', // formaction with javascript URIs
            '/innerHTML\s*=\s*["\'].*<object.*["\']/i', // innerHTML use of object
            '/<base\b[^>]*href\s*=\s*[\'"]?\s*(?:javascript|data):[^\'" >]+[\'"]?/is', // <base> tag manipulation
            '/<svg\b[^>]*>(.*?)<\/svg>/is', // <svg> tags
            '/<math\b[^>]*>(.*?)<\/math>/is', // <math> tags
            '/src\s*=\s*[\'"]?data:\s*[^\'" >]+;base64,[^\'"]+/is', // base64 encoded data URIs
            '/innerHTML\s*=\s*["\'].*<script.*["\']/i', // innerHTML with script
            '/outerHTML\s*=\s*["\'].*<script.*["\']/i', // outerHTML with script
            '/textContent\s*=\s*["\'].*<script.*["\']/i', // textContent with script
            '/document\.write\s*\(\s*["\'].*<script.*["\']\s*\)/i', // document.write with script
            '/document\.createElement\s*\(\s*["\']script["\']\s*\)/i', // document.createElement for script
        ];

        // If input is an array, recursively check each item
        if (is_array($input)) {
            foreach ($input as $value) {
                if ($this->detectXSS($value)) {
                    return true; // XSS detected in one of the array items
                }
            }
            return false; // No XSS detected in any array items
        }

        // If input is a string, check for XSS patterns
        if (!empty($input)) {
            foreach ($xssPatterns as $pattern) {
                if (preg_match($pattern, $input)) {
                    return true; // XSS detected
                }
            }
        }

        return false; // No XSS detected
    }
}
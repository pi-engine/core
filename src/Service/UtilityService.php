<?php

declare(strict_types=1);

namespace Pi\Core\Service;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTime;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use Laminas\Escaper\Escaper;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Client\Adapter\Exception\InvalidArgumentException;
use Laminas\Http\Client\Adapter\Exception\RuntimeException;
use Laminas\Http\Client\Adapter\Exception\TimeoutException;
use NumberFormatter;
use Pi\Core\Service\Utility\Ip;
use Symfony\Component\Uid\Uuid;
use function class_exists;
use function method_exists;
use function preg_replace;
use function str_replace;
use function strip_tags;
use function ucfirst;

class UtilityService implements ServiceInterface
{
    /* @var array */
    protected array $config;

    protected array $slugOptions
        = [
            // Force lower case
            'force_lower'     => true,
            // Force normalize chars
            'normalize_chars' => true,
            // Force add uuid suffix
            'uuid_suffix'     => false,
            // Optional prefix, empty by default
            'prefix'          => '',
        ];

    protected array $slugPattern
        = [
            'Š' => 'S',
            'š' => 's',
            'Ð' => 'Dj',
            'Ž' => 'Z',
            'ž' => 'z',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'Æ' => 'A',
            'Ç' => 'C',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ñ' => 'N',
            'Ń' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ø' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Þ' => 'B',
            'ß' => 'Ss',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'æ' => 'a',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'o',
            'ñ' => 'n',
            'ń' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'ý' => 'y',
            'þ' => 'b',
            'ÿ' => 'y',
            'ƒ' => 'f',
            'ă' => 'a',
            'î' => 'i',
            'â' => 'a',
            'ș' => 's',
            'ț' => 't',
            'Ă' => 'A',
            'Î' => 'I',
            'Â' => 'A',
            'Ș' => 'S',
            'Ț' => 'T',
        ];

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     */
    public function getTime($params = []): string
    {
        $timezone = $params['timezone'] ?? $this->config['timezone'] ?? 'UTC';
        $format   = $params['format'] ?? $this->config['date_format'] ?? 'Y-m-d H:i:s';

        $date = new DateTime('now', new DateTimeZone($timezone));
        return $date->format($format);
    }

    /**
     * Load date formatter
     *
     * @param string|int $date
     * @param array      $params
     *
     * @return string
     * @see IntlDateFormatter
     * Valid values: 'NULL', 'FULL', 'LONG', 'MEDIUM', 'SHORT'
     */
    public function date(string|int $date = '', array $params = []): string
    {
        $date = empty($date) ? time() : $date;

        if (!class_exists('IntlDateFormatter')) {
            return date('Y-m-d H:i:s', $date);
        }

        // Set params
        $local    = $params['local'] ?? $this->config['date_local'];
        $datetype = $params['datetype'] ?? $this->config['date_type'];
        $timetype = $params['timetype'] ?? $this->config['time_type'];
        $timezone = $params['timezone'] ?? $this->config['timezone'];
        $calendar = $params['calendar'] ?? $this->config['date_calendar'];
        $pattern  = $params['pattern'] ?? $this->config['date_pattern'];

        $formatter = new IntlDateFormatter($local, $datetype, $timetype, $timezone, $calendar, $pattern);
        return $formatter->format($date);
    }

    /**
     * Escape a string for corresponding context
     *
     * @param string|array $data
     * @param string       $context
     *      String context, valid value: html, htmlAttr, js, url, css
     *
     * @return string|array
     * @see \Laminas\Escaper\Escaper
     */
    public function escape(string|array $data, string $context = 'html'): string|array
    {
        $context = $context ? ucfirst($context) : 'Html';
        $method  = 'escape' . $context;
        $escaper = new Escaper('utf-8');

        if (is_array($data)) {
            $data = $this->escapeArray($escaper, $data, $method);
        } elseif (method_exists($escaper, $method)) {
            $data = $escaper->{$method}($data);
        }

        return $data;
    }

    /**
     * Escape a string for corresponding context
     *
     * @param Escaper $escaper
     * @param array   $data
     * @param string  $method
     *
     * @return array
     * @see \Laminas\Escaper\Escaper
     */
    public function escapeArray(Escaper $escaper, array $data, string $method): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->escapeArray($escaper, $value, $method);
            } elseif (is_string($data)) {
                $data = $escaper->{$method}($data);
            }
        }

        return $data;
    }

    /**
     * Clean a string by stripping HTML tags and removing unrecognizable characters
     *
     * @param string      $text        Text to be cleaned
     * @param string|null $replacement Replacement for stripped characters
     * @param array       $pattern     Custom pattern array
     *
     * @return string
     */
    public function strip(string $text, string|null $replacement = null, array $pattern = []): string
    {
        if (empty($pattern)) {
            $pattern = [
                "\t",
                "\r\n",
                "\r",
                "\n",
                "'",
                "\\",
                '&nbsp;',
                ',',
                '.',
                ';',
                ':',
                ')',
                '(',
                '"',
                '?',
                '!',
                '{',
                '}',
                '[',
                ']',
                '<',
                '>',
                '/',
                '+',
                '-',
                '_',
                '*',
                '=',
                '@',
                '#',
                '$',
                '%',
                '^',
                '&',
            ];
        }
        $replacement = (null === $replacement) ? ' ' : $replacement;

        // Strip HTML tags
        $text = $text ? strip_tags($text) : '';
        // Sanitize
        $text = $text ? $this->escape($text) : '';

        // Clean up
        $text = $text ? preg_replace('`\[.*\]`U', '', $text) : '';
        $text = $text ? preg_replace('`&(amp;)?#?[a-z0-9]+;`i', '', $text) : '';
        $text = $text
            ? preg_replace(
                '/&([a-z])'
                . '(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig);/i',
                '\\1',
                $text
            )
            : '';
        return $text ? str_replace($pattern, $replacement, $text) : '';
    }

    /**
     * @param string|null $text    Text to be cleaned
     * @param array       $options
     * @param array       $pattern Custom pattern array
     *
     * @return string
     */
    public function slug(string|null $text = '', array $options = [], array $pattern = []): string
    {
        $uuid = (string) Uuid::v7();

        // Merge default slug options with user-defined ones (user overrides default)
        $options = array_merge($this->slugOptions, $options);
        $pattern = $pattern ?: $this->slugPattern;

        // Prepare prefix if provided
        $prefix = '';
        if (!empty($options['prefix']) && is_string($options['prefix'])) {
            $prefix = rtrim($options['prefix'], '-') . '-';
        }

        // Determine base text, optionally appending a UUID
        if (!is_string($text) || trim($text) === '') {
            $text = $uuid;
        } elseif (!empty($options['uuid_suffix'])) {
            $text = rtrim($text, '-') . '-' . $uuid;
        }

        // Strip HTML and unwanted characters
        $text = trim($this->strip($text));

        // Replace slashes and multiple spaces with a single dash
        $text = preg_replace('#[\/\s]+#', '-', $text);
        if ($text === null) {
            $text = $uuid; // fallback in case preg_replace fails
        }

        // Normalize special characters if requested
        if (!empty($options['normalize_chars']) && is_array($pattern)) {
            $text = strtr($text, $pattern);
        }

        // Convert to lowercase if requested
        if (!empty($options['force_lower'])) {
            $text = mb_strtolower($text, 'UTF-8');
        }

        // Prepend prefix if available
        return $prefix . $text;
    }

    /**
     * Locale-dependent formatting/parsing of number
     * using pattern strings and/or canned patterns
     *
     * @param float|int   $value
     * @param string|null $currency
     * @param string|null $locale
     *
     * @return float|int|string
     */
    public function setCurrency(float|int $value, string $currency = null, string $locale = null): float|int|string
    {
        $result   = $value;
        $currency = (null === $currency) ? $this->config['currency'] : $currency;
        if ($currency) {
            $style     = 'CURRENCY';
            $formatter = $this->getNumberFormatter($style, $locale);
            $result    = $formatter->formatCurrency((int)$value, $currency);
        }

        return $result;
    }

    /**
     * Load number formatter
     *
     * @param string|null $style
     * @param string|null $pattern
     * @param string|null $locale
     *
     * @return NumberFormatter|null
     * @see NumberFormatter
     *
     */
    public function getNumberFormatter(string $style = null, string $pattern = null, string $locale = null): ?NumberFormatter
    {
        if (!class_exists('NumberFormatter')) {
            return null;
        }

        $locale    = $locale ?: $this->config['local'];
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        if ($pattern) {
            $formatter->setPattern($pattern);
        }

        return $formatter;
    }

    /**
     * Get the real client IP address, preferring IPv4 over IPv6 if both exist.
     *
     * This function checks various headers used by proxies, cloud services, and load balancers.
     * It ensures the returned IP is not private, reserved, or spoofed.
     *
     * @return string The real client IP or '0.0.0.0' if none found.
     */
    public function getClientIp(): string
    {
        $ip = new Ip();
        return $ip->getClientIp();
    }

    /**
     * Check if an IP is in the allowed list (supports CIDR ranges)
     *
     * @param string $ip         The IP to check.
     * @param array  $allowedIps List of allowed IPs and CIDR subnets.
     *
     * @return bool True if allowed, otherwise false.
     */
    public function isIpAllowed(string $ip, array $allowedIps): bool
    {
        foreach ($allowedIps as $allowedIp) {
            if (str_contains($allowedIp, '/')) {
                // CIDR range check
                if ($this->isIpInRange($ip, $allowedIp)) {
                    return true;
                }
            } elseif ($ip === $allowedIp) {
                // Exact match
                return true;
            }
        }
        return false;
    }

    /**
     * Check if an IP falls within a CIDR subnet.
     *
     * @param string $ip   The IP to check.
     * @param string $cidr The CIDR subnet (e.g., "192.168.1.0/24").
     *
     * @return bool True if IP is within the subnet, otherwise false.
     */
    private function isIpInRange(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $ipBinary     = ip2long($ip);
        $subnetBinary = ip2long($subnet);
        $maskBinary   = -1 << (32 - $mask);

        return ($ipBinary & $maskBinary) === ($subnetBinary & $maskBinary);
    }

    /**
     * Check password is strong
     *
     * @param int|string $password
     * @param string     $pattern
     *
     * @return bool
     */
    public function isPasswordStrong(int|string $password, string $pattern = ''): bool
    {
        // Check and set a pattern
        $pattern = empty($pattern) ? '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^a-zA-Z0-9\s]).+$/' : $pattern;

        // Check strong
        return preg_match($pattern, $password) === 1;
    }

    /**
     * Recursively builds a hierarchical tree from a flat array of items based on parent-child relationships.
     *
     * This function assembles a full tree starting from the specified parent item,
     * recursively adding child items as subtrees.
     *
     * @param array $elements The flat array of items with parent-child relationships.
     * @param int   $parentId The parent ID to start building the tree from.
     *
     * @return array The hierarchical tree, including all children and subtrees.
     */

    public function buildTree(array &$elements, int $parentId = 0): array
    {
        $result = [];
        foreach ($elements as &$element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $result[] = $element;
                unset($element);
            }
        }

        return $result;
    }

    /**
     * Retrieves all IDs starting from the given parentId and includes all descendant IDs
     * as a flat list.
     *
     * @param array $elements     The flat array of items with parent-child relationships.
     * @param int   $parentId     The parent ID to start from.
     * @param array $processedIds Keeps track of processed IDs to avoid duplicates.
     *
     * @return array A flat list of IDs.
     */
    public function buildSubTree(array $elements, int $parentId, array &$processedIds = []): array
    {
        $result = [];

        foreach ($elements as $element) {
            if (!in_array($element['id'], $processedIds) && ($element['id'] === $parentId || $element['parent_id'] === $parentId)) {
                $processedIds[] = $element['id']; // Mark ID as processed
                $result[]       = $element['id']; // Add current ID
                $result         = array_merge($result, $this->buildSubTree($elements, $element['id'], $processedIds)); // Add children IDs
            }
        }

        return $result;
    }

    /**
     * Filters an input array, removing keys that are not in the specified field list.
     *
     * @param array $params    The input array to be filtered. Keys represent field names, and values are their corresponding data.
     * @param array $fieldList The list of allowed field names. Only keys from this list will be retained in the filtered array.
     *
     * @return array The filtered array containing only keys that are present in the field list.
     *
     * Example:
     * ```php
     * $params = [
     *     'first_name' => 'John',
     *     'last_name' => 'Doe',
     *     'age' => 30,
     *     'email' => 'john.doe@example.com'
     * ];
     * $fieldList = ['first_name', 'last_name'];
     *
     * $filteredParams = $this->filterParams($params, $fieldList);
     * // Result: [
     * //     'first_name' => 'John',
     * //     'last_name' => 'Doe'
     * // ]
     * ```
     */
    public function filterParams(array $params, array $fieldList): array
    {
        return array_filter(
            $params,
            fn($key) => in_array($key, $fieldList, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Ensures the input array contains all fields from the allowed list.
     * Any missing fields will be added with a null value.
     * If the field list is empty, all input fields are accepted without modification.
     *
     * @param array $params    The input array to be validated.
     * @param array $fieldList The list of fields to ensure in the input array.
     *
     * @return array The modified array containing all fields from the list.
     *
     * Example:
     * ```php
     * $params = ['first_name' => 'John'];
     * $fieldList = ['first_name', 'last_name', 'email'];
     *
     * $result = $this->ensureFields($params, $fieldList);
     * // Result: [
     * // 'first_name' => 'John',
     * // 'last_name' => null,
     * // 'email' => null
     * // ]
     * ```
     */
    // ToDo: use this function in project
    public function ensureFields(array $params, array $fieldList): array
    {
        // If no specific field list is provided, return the input as-is
        if (empty($fieldList)) {
            return $params;
        }

        foreach ($fieldList as $field) {
            if (!array_key_exists($field, $params)) {
                $params[$field] = null;
            }
        }

        return $params;
    }

    /**
     * Updates an existing array with new values while ensuring all required fields are present.
     * If the field list is empty, all input fields are accepted.
     *
     * @param array $params    The input data containing updated values.
     * @param array $existing  The existing record to be updated.
     * @param array $fieldList The list of allowed fields.
     *
     * @return array The updated record with all required fields.
     *
     * Example:
     * ```php
     * $existing = ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com'];
     * $params = ['first_name' => 'Jane'];
     * $fieldList = ['first_name', 'last_name', 'email', 'phone'];
     *
     * $result = $this->updateFields($params, $existing, $fieldList);
     * // Result: [
     * // 'first_name' => 'Jane',
     * // 'last_name' => 'Doe',
     * // 'email' => 'john@example.com',
     * // 'phone' => null
     * // ]
     * ```
     */
    // ToDo: use this function in project
    public function updateFields(array $params, array $existing, array $fieldList): array
    {
        // If no specific field list is provided, merge all input fields into the existing data
        if (empty($fieldList)) {
            return array_merge($existing, $params);
        }

        // Ensure all fields exist in the existing array
        $existing = $this->ensureFields($existing, $fieldList);

        // Update existing values with new data
        foreach ($params as $key => $value) {
            if (in_array($key, $fieldList)) {
                $existing[$key] = $value;
            }
        }

        return $existing;
    }

    /**
     * Sorts an associative array by a custom field list.
     *
     * This function ensures the output contains only the fields defined in $fieldList,
     * sorted in the specified order. If a key is missing in $params, it will be added
     * with a null value. Extra keys in $params that are not in $fieldList will be removed.
     *
     * @param array $params     The associative array to sort and filter.
     * @param array $fieldList  The ordered list of allowed keys.
     *
     * @return array The sorted and filtered associative array.
     */
    function sortFields(array $params, array $fieldList): array
    {
        $filtered = [];

        foreach ($fieldList as $key) {
            $filtered[$key] = $params[$key] ?? null;
        }

        return $filtered;
    }

    /**
     * Makes an HTTP request using Laminas HTTP Client and returns the response.
     *
     * @param string     $url     The endpoint URL.
     * @param string     $method  The HTTP method (GET, POST, PUT, DELETE, etc.).
     * @param array      $headers The HTTP headers to include in the request.
     * @param array|null $body    The request body (optional).
     *
     * @return array Returns an associative array with the keys:
     *               - 'result' (bool): Indicates if the call was successful.
     *               - 'data' (array): The decoded response data if successful, or an empty array on failure.
     *               - 'error' (array|null): Contains 'message' (string) and optional 'response' (string) if there was an error.
     */
    public function callService(string $url, string $method, array $headers = [], ?array $body = null): array
    {
        // Check HTTP method
        $validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
        if (!in_array(strtoupper($method), $validMethods, true)) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => ['message' => "Invalid HTTP method: {$method}"],
            ];
        }

        // Check url
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => ['message' => "Invalid URL: {$url}"],
            ];
        }

        // Set up the HTTP client configuration
        $config = [
            'adapter'     => Curl::class,
            'curloptions' => [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 180,
                CURLOPT_CONNECTTIMEOUT => 10,
            ],
        ];

        // Set HTTP client
        $client = new Client($url, $config);
        $client->setMethod($method);
        $client->setHeaders($headers);
        if (!empty($body)) {
            $client->setRawBody(json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        try {
            $response   = $client->send();
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $responseBody = $response->getBody();
                $decodedBody  = json_decode($responseBody, true);

                // Check if the response contains valid JSON and is an array
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedBody)) {
                    // Check if the response contains 'result' or 'data'
                    if (isset($decodedBody['result']) || isset($decodedBody['data'])) {
                        return $decodedBody;
                    } else {
                        // Return the decoded body as 'data' with 'result' => true and no error
                        return [
                            'result' => true,
                            'data'   => $decodedBody,
                            'error'  => null,
                        ];
                    }
                }

                return [
                    'result' => false,
                    'data'   => [],
                    'error'  => [
                        'message'  => 'Invalid or empty JSON response',
                        'response' => $responseBody,
                    ],
                ];
            }

            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message'  => "HTTP error with status code {$statusCode}",
                    'response' => $response->getBody(),
                ],
            ];
        } catch (TimeoutException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message'  => 'Request timed out',
                    'response' => $e->getMessage(),
                ],
            ];
        } catch (RuntimeException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message'  => 'Runtime error occurred',
                    'response' => $e->getMessage(),
                ],
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message'  => 'Invalid argument passed to the HTTP client',
                    'response' => $e->getMessage(),
                ],
            ];
        } catch (Exception $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message'  => 'An error occurred: ' . $e->getMessage(),
                    'response' => $e->getMessage(),
                ],
            ];
        }
    }
}
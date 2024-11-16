<?php

namespace Pi\Core\Service;

use IntlDateFormatter;
use Laminas\Escaper\Escaper;
use NumberFormatter;
use Pi\User\Service\ServiceInterface;
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
        ];

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Load date formatter
     *
     * @param string $date
     * @param array  $params
     *
     * @return string
     * @see IntlDateFormatter
     * Valid values: 'NULL', 'FULL', 'LONG', 'MEDIUM', 'SHORT'
     *
     */
    public function date(string $date = '', array $params = []): string
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
     * @param string|array $value
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
     * @param string $text    Text to be cleaned
     * @param array  $options
     * @param array  $pattern Custom pattern array
     *
     * @return string
     */
    public function slug(string $text, array $options = [], array $pattern = []): string
    {
        $options = empty($options) ? $this->slugOptions : $options;

        // List of normalize chars
        if (empty($pattern)) {
            $pattern = [
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
        }

        // Strip HTML tags and remove unrecognizable characters
        $text = trim($this->strip($text));

        // Normalize chars
        if (!empty($options['normalize_chars'])) {
            $text = strtr($text, $pattern);
        }

        // Transform to lower case
        if (!empty($options['force_lower'])) {
            $text = strtolower($text);
        }

        // Transform multi-spaces to slash
        return preg_replace('/[\s]+/', '-', $text);
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
     * Check password is strong
     *
     * @param $password
     *
     * @return bool
     */
    public function isPasswordStrong($password): bool
    {
        $pattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^a-zA-Z0-9\s]).+$/';

        return preg_match($pattern, $password) === 1;
    }
}
<?php

declare(strict_types=1);

namespace Pi\Core\Response;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Escaper\Escaper;

class EscapingJsonResponse extends JsonResponse
{
    public function __construct(array $data, $status = 200, array $headers = [], $encodingOptions = self::DEFAULT_JSON_FLAGS)
    {
        $escapedData = $this->escapeData($data);
        parent::__construct($escapedData, $status, $headers, $encodingOptions);
    }

    private function escapeData(array $data): array
    {
        $escaper = new Escaper('utf-8');
        $entityMap = [
            '&#039;' => "'",
            '&quot;' => '"',
            '&amp;'  => '&',
            //'&lt;'   => '<',
            //'&gt;'   => '>',
        ];

        array_walk_recursive($data, function (&$item) use ($escaper, $entityMap) {
            if (is_string($item)) {
                $escaped = $escaper->escapeHtml($item);
                $item = strtr($escaped, $entityMap); // Use strtr for efficiency
            }
        });

        return $data;
    }
}

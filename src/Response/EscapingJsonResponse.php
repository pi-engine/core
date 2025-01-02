<?php

declare(strict_types=1);

namespace Pi\Core\Response;

use Laminas\Diactoros\Response\JsonResponse;
use Pi\Core\Security\Action\EscaperAction;

class EscapingJsonResponse extends JsonResponse
{
    public function __construct(array $data, $status = 200, array $headers = [], $encodingOptions = self::DEFAULT_JSON_FLAGS)
    {
        $escape      = new EscaperAction();
        $escapedData = $escape->process($data);

        parent::__construct($escapedData, $status, $headers, $encodingOptions);
    }
}

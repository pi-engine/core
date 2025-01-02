<?php

declare(strict_types=1);

namespace Pi\Core\Security\Action;

use Laminas\Escaper\Escaper;

class EscaperAction implements ActionSecurityInterface
{
    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'escape';

    public function __construct($config = [])
    {
        $this->config = $config;
    }

    public function process(array $data): array
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
                $item = strtr($escaped, $entityMap);
            }
        });

        return $data;
    }
}
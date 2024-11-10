<?php

namespace Pi\Core\Security\Request;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Filter\HtmlEntities;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\I18n\Validator\IsFloat;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Digits;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Ip;
use Laminas\Validator\IsJsonString;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;
use Laminas\Validator\Uri;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class InputValidation implements RequestSecurityInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var InputFilter */
    protected InputFilter $inputFilter;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'inputValidation';

    protected string $message = 'Access denied: Input data not valid, ';

    public function __construct($config)
    {
        $this->config      = $config;
        $this->inputFilter = new InputFilter();
    }

    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        // Check if the IP is in the whitelist
        if (
            (bool)$this->config['inputValidation']['ignore_whitelist'] === true
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
            $this->processData($params);
            $this->inputFilter->setData($params);
            if (!$this->inputFilter->isValid()) {
                // Set error message
                $this->setErrorMessage($this->inputFilter->getMessages());

                return [
                    'result' => false,
                    'name'   => $this->name,
                    'status' => 'unsuccessful',
                    'data'   => [
                        'message' => $this->inputFilter->getMessages(),
                    ],
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

    public function setErrorMessage($messages): void
    {
        if (is_string($messages)) {
            $this->message = $this->message . ' ' . $messages;
        } else {
            $errorMessage = [];
            foreach ($messages as $field => $filedMessage) {
                if (is_array($filedMessage)) {
                    foreach ($filedMessage as $subField => $subFiledMessage) {
                        $errorMessage[$subField] = $field . ': ' . $subFiledMessage;
                    }
                } else {
                    $errorMessage[$field] = $field . ': ' . $filedMessage;
                }
            }

            $this->message = $this->message . implode(', ', $errorMessage);
        }
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }

    private function processData(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                continue;
            }

            // Initialize the input
            $input = new Input($key);
            $input->getFilterChain()->attach(new StringTrim())->attach(new StripTags())->attach(new HtmlEntities());

            // Handle different types of data
            switch (gettype($value)) {
                case 'integer':
                    $input->getValidatorChain()->attach(new Digits());
                    break;

                case 'double': // Floats in PHP are of type 'double'
                    $input->getValidatorChain()->attach(new IsFloat());
                    break;

                case 'string':
                    // Allow empty strings as valid inputs
                    $input->getValidatorChain()->attach(new StringLength(['min' => 0, 'max' => 65535]));
                    // Apply specific validators if applicable
                    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $input->getValidatorChain()->attach(new EmailAddress());
                    } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                        $input->getValidatorChain()->attach(new Uri());
                    } elseif (filter_var($value, FILTER_VALIDATE_IP)) {
                        $input->getValidatorChain()->attach(new Ip());
                    } elseif ($this->isJson($value)) {
                        $input->getValidatorChain()->attach(new IsJsonString());
                    }
                    break;

                case 'array':
                    $this->processData((array)$value);
                    continue 2; // Continue outer loop

                default:
                    if ($value !== null && $value !== '') {
                        $input->getValidatorChain()->attach(new NotEmpty());
                        $this->inputFilter->setData([$key => $value]);
                        $this->inputFilter->getMessages()[$key][] = "Key '{$key}' has an unrecognized type and cannot be empty.";
                    }
                    break;
            }

            $this->inputFilter->add($input);
        }
    }

    private function isJson($string): bool
    {
        return is_string($string) && json_last_error() === JSON_ERROR_NONE && json_decode($string) !== null;
    }
}
<?php

declare(strict_types=1);

namespace Pi\Core\Service;

use Laminas\I18n\Translator\Translator;
use Pi\User\Service\ServiceInterface;

/**
 * Usage example in services
 * $originalText = 'test_message';
 * $translatedText = $this->translatorService->translator()->translate($originalText);
 *
 * Example of php format for translation file
 * return [
 * 'test_message' => 'Welcome to our project!',
 * ];
 */
class TranslatorService implements ServiceInterface
{
    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function translator(): Translator
    {
        $translator = new Translator();
        $translator->setLocale($this->config['locale']);

        foreach ($this->config['translation_file_patterns'] as $pattern) {
            $translator->addTranslationFilePattern(
                $pattern['type'],
                $pattern['base_dir'],
                $pattern['pattern']
            );
        }

        return $translator;
    }

    public function translate($message): string
    {
        $translator = $this->translator();
        return $translator->translate($message);
    }
}
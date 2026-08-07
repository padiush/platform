<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Keeps the three locales in step.
 *
 * Portuguese silently fell back to English for a long time simply because
 * nobody noticed a file was missing — a missing translation looks like working
 * software until a Brazilian user reads an English validation error. These
 * assertions turn that into a failing build instead.
 */
class TranslationParityTest extends TestCase
{
    private const LOCALES = ['es', 'en', 'pt'];

    /** Spanish is the project's primary language, so it defines the shape. */
    private const REFERENCE = 'es';

    private function langPath(string $path): string
    {
        return dirname(__DIR__, 2)."/lang/{$path}";
    }

    private function load(string $locale, string $file): array
    {
        return require $this->langPath("{$locale}/{$file}.php");
    }

    /**
     * Every key path in the array, ignoring values.
     *
     * @return array<int, string>
     */
    private function keyPaths(array $translations, string $prefix = ''): array
    {
        $paths = [];

        foreach ($translations as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $paths = [...$paths, ...$this->keyPaths($value, $path)];

                continue;
            }

            $paths[] = $path;
        }

        sort($paths);

        return $paths;
    }

    public function test_every_locale_has_the_same_translation_files()
    {
        $reference = $this->filesFor(self::REFERENCE);

        $this->assertNotEmpty($reference);

        foreach (self::LOCALES as $locale) {
            $this->assertSame(
                $reference,
                $this->filesFor($locale),
                "lang/{$locale} does not carry the same files as the reference locale"
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function filesFor(string $locale): array
    {
        $files = array_map(
            fn (string $path) => basename($path, '.php'),
            glob($this->langPath("{$locale}/*.php")) ?: []
        );

        sort($files);

        return $files;
    }

    public function test_message_keys_match_across_locales()
    {
        foreach ($this->filesFor(self::REFERENCE) as $file) {
            $reference = $this->messageKeys($this->load(self::REFERENCE, $file));

            foreach (self::LOCALES as $locale) {
                $this->assertSame(
                    $reference,
                    $this->messageKeys($this->load($locale, $file)),
                    "lang/{$locale}/{$file}.php has drifted from the reference locale"
                );
            }
        }
    }

    /**
     * Message keys only. `attributes` holds human field names, which English
     * deliberately leaves empty because there the key is already the name, and
     * `custom` is framework scaffolding nobody has filled in.
     *
     * @return array<int, string>
     */
    private function messageKeys(array $translations): array
    {
        unset($translations['attributes'], $translations['custom']);

        return $this->keyPaths($translations);
    }

    public function test_translated_locales_name_the_same_attributes()
    {
        $reference = $this->load(self::REFERENCE, 'validation')['attributes'] ?? [];

        $this->assertNotEmpty($reference);
        $this->assertSame(
            $this->keyPaths($reference),
            $this->keyPaths($this->load('pt', 'validation')['attributes'] ?? []),
            'lang/pt/validation.php names different attributes than the reference locale'
        );
    }

    public function test_json_catalogues_match()
    {
        // These carry the framework's own strings, including the ones Laravel
        // uses to build notification emails.
        $reference = $this->jsonKeys(self::REFERENCE);

        $this->assertNotEmpty($reference);
        $this->assertSame($reference, $this->jsonKeys('pt'));
    }

    /**
     * @return array<int, string>
     */
    private function jsonKeys(string $locale): array
    {
        $keys = array_keys(
            json_decode(file_get_contents($this->langPath("{$locale}.json")), true)
        );

        sort($keys);

        return $keys;
    }

    public function test_no_translation_is_left_empty()
    {
        foreach (self::LOCALES as $locale) {
            foreach ($this->filesFor($locale) as $file) {
                $translations = $this->load($locale, $file);

                foreach ($this->keyPaths($translations) as $path) {
                    $this->assertNotSame(
                        '',
                        data_get($translations, $path),
                        "lang/{$locale}/{$file}.php leaves {$path} empty"
                    );
                }
            }
        }
    }
}

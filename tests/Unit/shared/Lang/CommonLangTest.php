<?php

namespace Quantum\Tests\Unit\shared\Lang;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CommonLangTest extends TestCase
{
    /**
     * The keys the framework reads by name: the mail subjects, the pagination links
     * and the field labels the validator reads as `common.<field>`.
     */
    private const COMMON_KEYS = [
        'activate_account',
        'reset_password',
        'otp',
        'pagination.next',
        'pagination.prev',
        'captcha',
        'confirm_password',
        'content',
        'current_password',
        'email',
        'firstname',
        'image',
        'lastname',
        'new_password',
        'password',
        'repeat_password',
        'title',
        'token',
    ];

    #[DataProvider('languageProvider')]
    public function testEveryLanguageHasTheStarTexts(string $language): void
    {
        $star = $this->texts($language, 'pages')['star'] ?? null;

        $this->assertIsArray($star);

        foreach (['message', 'button', 'badge_alt'] as $key) {
            $this->assertArrayHasKey($key, $star);
            $this->assertIsString($star[$key]);
            $this->assertNotSame('', trim($star[$key]));
        }
    }

    public function testStarGroupHasTheSameKeysInEveryLanguage(): void
    {
        $this->assertSame(
            array_keys($this->texts('en', 'pages')['star'] ?? []),
            array_keys($this->texts('es', 'pages')['star'] ?? [])
        );
    }

    public function testEnglishTextAsksForAStarOnGitHub(): void
    {
        $star = $this->texts('en', 'pages')['star'] ?? [];

        $this->assertStringContainsString('starring Quantum on GitHub', (string) ($star['message'] ?? ''));
        $this->assertSame('Star on GitHub', $star['button'] ?? null);
        $this->assertSame('GitHub stars', $star['badge_alt'] ?? null);
    }

    #[DataProvider('languageProvider')]
    public function testCommonKeepsTheKeysTheFrameworkReadsByName(string $language): void
    {
        $keys = $this->flatten($this->texts($language, 'common'));

        foreach (self::COMMON_KEYS as $key) {
            $this->assertArrayHasKey($key, $keys, "common.$key is missing in $language");
            $this->assertNotSame('', trim((string) $keys[$key]));
        }

        $this->assertSame([], array_values(array_diff(array_keys($keys), self::COMMON_KEYS)));
    }

    #[DataProvider('languageProvider')]
    public function testCommentMessagesAreTranslated(string $language): void
    {
        $comments = $this->texts($language, 'comments');

        foreach (['added', 'deleted'] as $key) {
            $this->assertArrayHasKey($key, $comments);
            $this->assertNotSame('', trim($comments[$key]));
        }
    }

    public function testEveryLanguageHasTheSameFilesAndKeys(): void
    {
        $english = $this->files('en');

        $this->assertSame($english, $this->files('es'));

        foreach ($english as $file) {
            $this->assertSame(
                array_keys($this->flatten($this->texts('en', $file))),
                array_keys($this->flatten($this->texts('es', $file))),
                "The keys of $file differ between en and es"
            );
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function languageProvider(): array
    {
        return [
            'english' => ['en'],
            'spanish' => ['es'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function texts(string $language, string $file): array
    {
        return require $this->langDir($language) . '/' . $file . '.php';
    }

    /**
     * @return array<int, string>
     */
    private function files(string $language): array
    {
        $files = array_map(
            fn (string $path): string => basename($path, '.php'),
            glob($this->langDir($language) . '/*.php') ?: []
        );

        sort($files);

        return $files;
    }

    private function langDir(string $language): string
    {
        return dirname(__DIR__, 4) . '/shared/resources/lang/' . $language;
    }

    /**
     * @param array<string, mixed> $texts
     * @return array<string, mixed>
     */
    private function flatten(array $texts, string $prefix = ''): array
    {
        $flat = [];

        foreach ($texts as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $flat += $this->flatten($value, $path);
            } else {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }
}

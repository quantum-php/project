<?php

namespace Quantum\Tests\Unit\shared\Lang;

use PHPUnit\Framework\TestCase;

class CommonLangTest extends TestCase
{
    /**
     * @dataProvider languageProvider
     */
    public function testEveryLanguageHasTheStarTexts(string $language): void
    {
        $star = $this->common($language)['star'] ?? null;

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
            array_keys($this->common('en')['star'] ?? []),
            array_keys($this->common('es')['star'] ?? [])
        );
    }

    public function testEnglishTextAsksForAStarOnGitHub(): void
    {
        $star = $this->common('en')['star'] ?? [];

        $this->assertStringContainsString('starring Quantum on GitHub', (string) ($star['message'] ?? ''));
        $this->assertSame('Star on GitHub', $star['button'] ?? null);
        $this->assertSame('GitHub stars', $star['badge_alt'] ?? null);
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
    private function common(string $language): array
    {
        return require dirname(__DIR__, 4) . '/shared/resources/lang/' . $language . '/common.php';
    }
}

<?php

namespace Quantum\Tests\Feature;

use Quantum\App\Factories\AppFactory;
use Quantum\Tracer\ErrorHandler;
use PHPUnit\Framework\TestCase;
use Quantum\App\Enums\AppType;
use Quantum\Http\Response;
use ReflectionFunction;
use RuntimeException;
use Quantum\App\App;
use Closure;

class AppTestCase extends TestCase
{
    protected string $defaultEmail = 'default@quantumphp.io';

    protected string $defaultPassword = 'password';

    protected string $firstname = 'John';

    protected string $lastname = 'Doe';

    protected static App $app;

    public function setUp(): void
    {
        parent::setUp();
        ob_start();

        $this->ensureRateLimitStorageExists();
        self::$app = createApp(AppType::WEB, PROJECT_ROOT);
        $this->clearRateLimitStorage();
    }

    public function tearDown(): void
    {
        $this->clearRateLimitStorage();
        AppFactory::destroy(AppType::WEB);
        $this->removeFrameworkErrorHandlers();
        parent::tearDown();
        ob_end_clean();
    }

    public function request(
        string $method,
        string $url,
        array  $params = [],
        array  $headers = [],
        array  $files = []
    ): Response {
        request()->create($method, $url, $params, $headers, $files);
        self::$app->start();
        return response();
    }

    protected function signInAndGetTokens(): array
    {
        $response = $this->request('post', '/api/signin', [
            'email' => $this->defaultEmail,
            'password' => $this->defaultPassword,
        ]);

        return $response->get('tokens');
    }

    /**
     * Removes the error and exception handlers that the framework's ErrorHandler registered
     * when setUp() booted the app; the handlers of the console app that tests/bootstrap.php
     * boots once stay in place
     */
    private function removeFrameworkErrorHandlers(): void
    {
        $isFrameworkHandler = $this->isFrameworkHandler(set_error_handler(static fn (): bool => false));
        restore_error_handler();

        if ($isFrameworkHandler) {
            restore_error_handler();
        }

        $isFrameworkHandler = $this->isFrameworkHandler(set_exception_handler(static function (): void {
        }));
        restore_exception_handler();

        if ($isFrameworkHandler) {
            restore_exception_handler();
        }
    }

    private function isFrameworkHandler(mixed $handler): bool
    {
        return $handler instanceof Closure && (new ReflectionFunction($handler))->getClosureThis() instanceof ErrorHandler;
    }

    private function clearRateLimitStorage(): void
    {
        $rateLimitDir = PROJECT_ROOT . DS . 'cache' . DS . 'data';

        if (!is_dir($rateLimitDir)) {
            return;
        }

        foreach (glob($rateLimitDir . DS . '*.rate') ?: [] as $file) {
            if (!unlink($file)) {
                throw new RuntimeException('Failed to remove rate limit file: ' . $file);
            }
        }

        foreach (glob($rateLimitDir . DS . '*.lock') ?: [] as $file) {
            if (!unlink($file)) {
                throw new RuntimeException('Failed to remove rate limit file: ' . $file);
            }
        }
    }

    private function ensureRateLimitStorageExists(): void
    {
        $rateLimitDir = PROJECT_ROOT . DS . 'cache' . DS . 'data';

        if (is_dir($rateLimitDir)) {
            return;
        }

        $created = mkdir($rateLimitDir, 0777, true);

        if (!$created && !is_dir($rateLimitDir)) {
            throw new RuntimeException('Failed to create rate limit storage directory: ' . $rateLimitDir);
        }
    }
}

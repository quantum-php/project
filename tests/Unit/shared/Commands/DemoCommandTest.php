<?php

namespace Quantum\Tests\Unit\shared\Commands;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Application;
use Shared\Commands\DemoCommand;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Runs the command with the given steps instead of the real demo installation
 */
class DemoCommandWithSteps extends DemoCommand
{
    /**
     * @var array<int, array{message: string, action: callable}>
     */
    public array $steps = [];

    protected function getSteps(): array
    {
        return $this->steps;
    }
}

class DemoCommandTest extends TestCase
{
    private const REPOSITORY_URL = 'https://github.com/quantum-php/framework';

    public function testStarMessageIsShownAfterTheSuccessMessage(): void
    {
        $output = $this->runCommand(new DemoCommandWithSteps(), ['--yes' => true]);

        $this->assertStringContainsString('Demo project created successfully', $output);
        $this->assertStringContainsString(self::REPOSITORY_URL, $output);
        $this->assertStringContainsString('starring Quantum on GitHub', $output);
        $this->assertGreaterThan(
            strpos($output, 'Demo project created successfully'),
            strpos($output, self::REPOSITORY_URL)
        );
    }

    public function testStarMessageIsNotShownWhenTheOperationIsCancelled(): void
    {
        $output = $this->runCommand(new DemoCommandWithSteps(), [], ['no']);

        $this->assertStringContainsString('Operation was canceled!', $output);
        $this->assertStringNotContainsString(self::REPOSITORY_URL, $output);
    }

    public function testStarMessageIsNotShownWhenAStepFails(): void
    {
        $command = new DemoCommandWithSteps();
        $command->steps = [
            [
                'message' => 'Failing step',
                'action' => function (): void {
                    throw new RuntimeException('step failed');
                },
            ],
        ];

        $output = new BufferedOutput();

        try {
            $this->application($command)->run(new ArrayInput(['command' => 'install:demo', '--yes' => true]), $output);
            $this->fail('The failing step did not stop the command');
        } catch (RuntimeException $e) {
            $this->assertSame('step failed', $e->getMessage());
        }

        $this->assertStringNotContainsString('Demo project created successfully', $output->fetch());
    }

    public function testStarMessageDoesNotUseTheOldRepositoryName(): void
    {
        $output = $this->runCommand(new DemoCommandWithSteps(), ['--yes' => true]);

        $this->assertStringNotContainsString('softberg', $output);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<int, string> $answers
     */
    private function runCommand(DemoCommand $command, array $input, array $answers = []): string
    {
        $output = new BufferedOutput();

        $arrayInput = new ArrayInput(['command' => 'install:demo'] + $input);
        $arrayInput->setInteractive($answers !== []);

        if ($answers !== []) {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, implode(PHP_EOL, $answers) . PHP_EOL);
            rewind($stream);
            $arrayInput->setStream($stream);
        }

        $this->application($command)->run($arrayInput, $output);

        return $output->fetch();
    }

    private function application(DemoCommand $command): Application
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $application->add($command);

        return $application;
    }
}

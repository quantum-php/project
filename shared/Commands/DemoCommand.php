<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Shared\Commands;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Quantum\HttpClient\Exceptions\HttpClientException;
use Symfony\Component\Console\Helper\ProgressBar;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Service\Exceptions\ServiceException;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Bluemmb\Faker\PicsumPhotosProvider;
use Quantum\Di\Exceptions\DiException;
use Symfony\Component\Process\Process;
use Quantum\Console\CliCommand;
use Ottaviano\Faker\Gravatar;
use ReflectionException;
use Shared\Enums\Role;
use RuntimeException;
use Faker\Generator;
use ErrorException;
use Faker\Factory;

/**
 * Class DemoCommand
 * @package Shared\Commands
 */
class DemoCommand extends CliCommand
{
    /**
     * Default password for generated users
     */
    public const DEFAULT_PASSWORD = 'password';

    /**
     * Repository the visitors are asked to star
     */
    public const REPOSITORY_URL = 'https://github.com/quantum-php/framework';

    /**
     * Message shown after the demo is created successfully, %1$s is the repository link
     */
    public const STAR_MESSAGE = PHP_EOL
        . '<fg=yellow;options=bold>★</> Would you like to show some love by starring Quantum on GitHub?' . PHP_EOL
        . '  <href=%1$s;fg=cyan;options=underscore>%1$s</>';

    /**
     * Command name
     * @var string|null
     */
    protected ?string $name = 'install:demo';

    /**
     * Command description
     * @var string|null
     */
    protected ?string $description = 'Generates demo users and posts';

    /**
     * Command help text
     * @var string|null
     */
    protected ?string $help = 'The command will create demo users and posts for your project';

    /**
     * The default action for all confirmations
     * @var array<int, array<int|string, mixed>>
     */
    protected array $options = [
        ['yes', 'y', 'none', 'Acceptance of the confirmations'],
    ];

    /**
     * @var Generator
     */
    protected Generator $faker;

    /**
     * @var array
     */
    private array $generatedUsers = [];

    /**
     * @var array
     */
    private array $generatedPosts = [];

    /**
     * Static values for user and post counts.
     */
    private const COUNTS = [
        'users' => 3,
        'posts_per_user' => 8,
        'comments_per_post' => 3,
    ];

    /**
     * Predefined command mappings.
     */
    private const COMMANDS = [
        'migrate' => 'migration:migrate',
        'user_create' => 'user:create',
        'post_create' => 'post:create',
        'comment_create' => 'comment:create',
        'user_delete' => 'user:delete',
        'post_delete' => 'post:delete',
        'comment_delete' => 'comment:delete',
        'module_generate' => 'module:generate',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->faker = Factory::create();
        $this->faker->addProvider(new PicsumPhotosProvider($this->faker));
        $this->faker->addProvider(new Gravatar($this->faker));
    }

    /**
     * Executes the command
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ErrorException
     * @throws ExceptionInterface
     * @throws HttpClientException
     * @throws ReflectionException
     * @throws ServiceException
     */
    public function exec(): void
    {
        if (!$this->getOption('yes')) {
            if (!$this->confirm('The operation will remove all previously created data and will create new dataset. Continue?')) {
                $this->info('Operation was canceled!');
                return;
            }
        }

        $steps = $this->getSteps();
        $progress = $this->initProgressBar(count($steps));

        foreach ($steps as $step) {
            $this->updateProgress($progress, $step['message']);
            $step['action']();
            $progress->advance();
        }

        $progress->finish();
        $this->info(PHP_EOL . 'Demo project created successfully');
        $this->output(sprintf(self::STAR_MESSAGE, self::REPOSITORY_URL));
    }

    /**
     * Provides the sequence of defined tasks to be executed.
     * @return array[]
     */
    protected function getSteps(): array
    {
        return [
            [
                'message' => 'Creating API module',
                'action' => function (): void {
                    $this->createModule('Api', 'DemoApi', false);
                },
            ],
            [
                'message' => 'Creating Web module',
                'action' => function (): void {
                    $this->createModule('Web', 'DemoWeb', true);
                },
            ],
            [
                'message' => 'Cleaning up uploads & rebuilding database',
                'action' => function (): void {
                    $this->resetDatabase();
                },
            ],
            [
                'message' => 'Generating users',
                'action' => function (): void {
                    $this->generatedUsers = $this->generateUsers();
                },
            ],
            [
                'message' => 'Generating posts',
                'action' => function (): void {
                    $this->generatedPosts = $this->generatePosts($this->generatedUsers);
                },
            ],
            [
                'message' => 'Generating comments',
                'action' => function (): void {
                    $this->generateComments($this->generatedUsers, $this->generatedPosts);
                },
            ],
        ];
    }

    /**
     * Initializes a progress bar.
     */
    private function initProgressBar(int $steps): ProgressBar
    {
        $progress = new ProgressBar($this->output, $steps);
        $progress->setFormat(sprintf('%s <info>%%item%%</info>', $progress->getFormatDefinition('verbose')));
        $progress->setBarCharacter('■');
        $progress->setEmptyBarCharacter('-');
        $progress->setProgressCharacter('►');
        $progress->start();
        return $progress;
    }

    /**
     * Creates module
     */
    private function createModule(string $moduleName, string $template, bool $withAssets): void
    {
        try {
            $this->runCommandExternally(self::COMMANDS['module_generate'], [
                'module' => $moduleName,
                '--yes' => true,
                '--template' => $template,
                '--with-assets' => $withAssets,
            ]);
        } catch (ProcessFailedException $e) {
            throw new RuntimeException(
                trim((string) $e->getProcess()->getOutput()) ?: $e->getMessage()
            );
        }
    }

    /**
     * Generate demo users.
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ErrorException
     * @throws ExceptionInterface
     * @throws HttpClientException
     * @throws ReflectionException
     */
    private function generateUsers(): array
    {
        $users = [];
        $images = [];

        for ($i = 0; $i < self::COUNTS['users']; $i++) {
            $generated = $this->generateUserData();
            $users[] = $generated['data'];
            $images[] = $generated['image'];
        }

        $imageNames = save_remote_images($images);

        foreach ($users as $index => $user) {
            $user['image'] = $imageNames[$index];
            $this->runCommandInternally(self::COMMANDS['user_create'], $user);
            $users[$index] = $user;
        }

        return $users;
    }

    /**
     * Generate demo posts for each user.
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ErrorException
     * @throws ExceptionInterface
     * @throws HttpClientException
     * @throws ReflectionException
     */
    private function generatePosts(array $users): array
    {
        $posts = [];
        $images = [];

        foreach ($users as $user) {
            for ($i = 0; $i < self::COUNTS['posts_per_user']; $i++) {
                $generated = $this->generatePostData($user);
                $posts[] = $generated['data'];
                $images[] = $generated['image'];
            }
        }

        $imageNames = save_remote_images($images);

        foreach ($posts as $index => $post) {
            $post['image'] = $imageNames[$index];
            $this->runCommandInternally(self::COMMANDS['post_create'], $post);
            $posts[$index] = $post;
        }

        return $posts;
    }

    /**
     * Generate demo comments for each post.
     * @throws ExceptionInterface
     */
    private function generateComments(array $users, array $posts): void
    {
        foreach ($posts as $post) {
            for ($i = 0; $i < self::COUNTS['comments_per_post']; $i++) {
                $commentUser = $this->getRandomUserExcept($users, $post['user_uuid']);

                $this->runCommandInternally(self::COMMANDS['comment_create'], [
                    'post_uuid' => $post['uuid'],
                    'user_uuid' => $commentUser['uuid'],
                    'content' => textCleanUp($this->faker->realText(random_int(20, 100))),
                ]);
            }
        }
    }

    /**
     * Generates data for user
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ErrorException
     * @throws HttpClientException
     * @throws ReflectionException
     */
    private function generateUserData(): array
    {
        $userUuid = $this->faker->uuid();
        $email = $this->faker->safeEmail();

        create_user_directory($userUuid);

        return [
            'data' => [
                'email' => $email,
                'password' => self::DEFAULT_PASSWORD,
                'firstname' => $this->faker->name(),
                'lastname' => $this->faker->lastName(),
                'uuid' => $userUuid,
                'role' => Role::EDITOR,
                'image' => '',
            ],
            'image' => [
                'url' => $this->faker->gravatarUrl(),
                'directory' => $userUuid,
                'name' => $email,
            ],
        ];
    }

    /**
     * Generates data for post
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ErrorException
     * @throws HttpClientException
     * @throws ReflectionException
     */
    private function generatePostData(array $user): array
    {
        $postUuid = $this->faker->uuid();
        $title = textCleanUp($this->faker->realText(50));

        return [
            'data' => [
                'title' => $title,
                'description' => textCleanUp($this->faker->realText(1000)),
                'user_uuid' => $user['uuid'],
                'uuid' => $postUuid,
                'image' => '',
            ],
            'image' => [
                'url' => $this->faker->imageUrl(640, 480, true, 0),
                'directory' => $user['uuid'],
                'name' => $title,
            ],
        ];
    }

    /**
     * Get random user for comment except post owner
     */
    private function getRandomUserExcept(array $users, string $excludeUuid): array
    {
        $candidates = array_filter($users, fn (array $u): bool => $u['uuid'] !== $excludeUuid);

        $candidates = array_values($candidates);
        return $candidates[array_rand($candidates)];
    }

    /**
     * Generates demo users and posts.
     * @param ProgressBar $progress
     * @param string $message
     */
    private function updateProgress(ProgressBar $progress, string $message): void
    {
        $progress->setMessage($message, 'item');
        $progress->display();
    }

    /**
     * Rebuilds the database
     * @throws ExceptionInterface
     */
    private function rebuildDatabase(): void
    {
        switch (db()->getConfigs()['driver']) {
            case 'mysql':
                $this->runCommandInternally(self::COMMANDS['migrate'], ['direction' => 'down']);
                $this->runCommandInternally(self::COMMANDS['migrate'], ['direction' => 'up']);
                break;

            case 'sleekdb':
                $this->runCommandInternally(self::COMMANDS['comment_delete'], ['--yes' => true]);
                $this->runCommandInternally(self::COMMANDS['post_delete'], ['--yes' => true]);
                $this->runCommandInternally(self::COMMANDS['user_delete'], ['--yes' => true]);
                break;
        }
    }

    /**
     * Removes uploaded folders and files.
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    private function removeUploads(): void
    {
        $fs = FileSystemFactory::get();

        foreach ($fs->glob(uploads_dir() . DS . '*') as $folder) {
            foreach ($fs->glob($folder . DS . '*') as $file) {
                $fs->remove($file);
            }

            $fs->removeDirectory($folder);
        }
    }

    /**
     * Cleans the uploads and resets the database.
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ExceptionInterface
     * @throws ReflectionException
     */
    private function resetDatabase(): void
    {
        $this->removeUploads();
        $this->rebuildDatabase();
    }

    /**
     * @throws ExceptionInterface
     */
    private function runCommandInternally(string $commandName, array $arguments = []): void
    {
        $command = $this->getApplication()->find($commandName);
        $command->run(new ArrayInput($arguments), new NullOutput());
    }

    /**
     * Runs command externally
     */
    private function runCommandExternally(string $commandName, array $arguments = []): void
    {
        $command = $this->buildCommand($commandName, $arguments);
        $this->runExternalProcess($command);
    }

    /**
     * Runs separate process
     */
    private function runExternalProcess(array $command): void
    {
        $process = new Process($command, base_dir());
        $process->setTimeout(null);

        $process->mustRun();
    }

    /**
     * Builds command string
     * @return string[]
     */
    private function buildCommand(string $commandName, array $arguments): array
    {
        $command = ['php', 'qt', $commandName];

        foreach ($arguments as $key => $value) {
            if (!is_int($key) && !str_starts_with($key, '--')) {
                $command[] = (string) $value;
                continue;
            }

            if (is_bool($value)) {
                if ($value) {
                    $command[] = $key;
                }
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    $command[] = $key;
                    $command[] = (string) $item;
                }
                continue;
            }

            $command[] = $key;

            if ($value === null) {
                continue;
            }

            $command[] = (string) $value;
        }

        return $command;
    }
}

<?php

namespace AsamoahBoateng\FrankenPhpDeploy\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'frankenphp:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'frankenphp:install
                            {--domain= : The application domain (e.g., myapp.test)}
                            {--project= : The compose project name (e.g., myapp)}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install FrankenPHP Docker deployment scaffolding';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Installing FrankenPHP Docker deployment scaffolding...');

        // 0. Ensure Laravel Octane with FrankenPHP is installed
        if (! $this->ensureOctaneIsInstalled()) {
            return self::FAILURE;
        }

        // 1. Gather configuration interactively or from options
        $domain = $this->option('domain')
            ?? $this->ask('Application domain', config('frankenphp.app_domain', 'myapp.test'));

        $project = $this->option('project')
            ?? $this->ask('Compose project name', config('frankenphp.project_name', 'myapp'));

        // Derive related values
        $replacements = $this->buildReplacements($domain, $project);

        // 2. Publish config if not already present
        $this->publishConfig();

        // 3. Create target directory
        $targetDir = $this->laravel->basePath('frankenphp_server');
        File::ensureDirectoryExists($targetDir);
        File::ensureDirectoryExists($targetDir . '/certs/local');
        File::ensureDirectoryExists($targetDir . '/certs/prod');

        // 4. Process and publish each stub
        $this->publishStub('Dockerfile.frankenphp.stub', $targetDir . '/Dockerfile.frankenphp', $replacements);
        $this->publishStub('docker-compose-traefik.yml.stub', $targetDir . '/docker-compose-traefik.yml', $replacements);
        $this->publishStub('docker-compose-standalone.yml.stub', $targetDir . '/docker-compose-standalone.yml', $replacements);
        $this->publishStub('docker-compose-traefik-master.yml.stub', $targetDir . '/docker-compose-traefik-master.yml', $replacements);
        $this->publishStub('Caddyfile.stub', $targetDir . '/Caddyfile', $replacements);
        $this->publishStub('entrypoint.sh.stub', $targetDir . '/entrypoint.sh', $replacements);
        $this->publishStub('setup-ssl.sh.stub', $targetDir . '/setup-ssl.sh', $replacements);
        $this->publishStub('dynamic_conf.yml.stub', $targetDir . '/dynamic_conf.yml', $replacements);
        $this->publishStub('env.example.stub', $targetDir . '/.env.example', $replacements);
        $this->publishStub('gitignore.stub', $targetDir . '/.gitignore', $replacements);

        // 5. Publish pha script to project root
        $this->publishStub('pha.stub', $this->laravel->basePath('pha'), $replacements);

        // 6. Make scripts executable
        chmod($this->laravel->basePath('pha'), 0755);
        chmod($targetDir . '/entrypoint.sh', 0755);
        chmod($targetDir . '/setup-ssl.sh', 0755);

        // 7. Copy .env.example to .env in the docker directory if it doesn't exist
        if (!File::exists($targetDir . '/.env')) {
            File::copy($targetDir . '/.env.example', $targetDir . '/.env');
        }

        $this->newLine();
        $this->components->info('FrankenPHP deployment scaffolding installed successfully!');
        $this->newLine();
        $this->line('  <fg=gray>Next steps:</>');
        $this->line('  <options=bold>1.</> cd frankenphp_server && ./setup-ssl.sh');
        $this->line('  <options=bold>2.</> Edit frankenphp_server/.env with your settings');
        $this->line('  <options=bold>3.</> ./pha start');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Ensure Laravel Octane with FrankenPHP is installed.
     */
    protected function ensureOctaneIsInstalled(): bool
    {
        if (class_exists(\Laravel\Octane\OctaneServiceProvider::class)) {
            $this->components->info('Laravel Octane is already installed.');
            return true;
        }

        if (! $this->confirm('Laravel Octane is not installed. Would you like to install it now?', true)) {
            $this->components->error('Laravel Octane is required for FrankenPHP deployment.');
            return false;
        }

        $this->components->info('Installing Laravel Octane...');

        $command = ['composer', 'require', 'laravel/octane'];
        $process = new Process($command, base_path());
        $process->setTimeout(300);
        $process->run(fn ($type, $output) => $this->output->write($output));

        if (! $process->isSuccessful()) {
            $this->components->error('Failed to install Laravel Octane via Composer.');
            return false;
        }

        $this->components->info('Configuring Octane with FrankenPHP...');
        $this->call('octane:install', ['--server' => 'frankenphp']);

        return true;
    }

    /**
     * Build the replacement map for stub processing.
     */
    protected function buildReplacements(string $domain, string $project): array
    {
        return [
            '{{APP_DOMAIN}}' => $domain,
            '{{PROJECT_NAME}}' => $project,
            '{{TRAEFIK_NETWORK}}' => config('frankenphp.traefik_network', 'km_traefik-public'),
            '{{TRAEFIK_CONTAINER}}' => config('frankenphp.traefik_container', 'km_traefik'),
            '{{TRAEFIK_HOST}}' => 'traefik.' . $domain,
            '{{ADMINER_DOMAIN}}' => 'adminer.' . $domain,
            '{{SERVICE_NAME}}' => config('frankenphp.service_name', 'frankenphp'),
            '{{WORKER_CONTAINER}}' => $project . '_worker_franken',
            '{{DB_CONTAINER}}' => $project . '_db_franken',
            '{{REDIS_CONTAINER}}' => $project . '_redis_franken',
            '{{ADMINER_CONTAINER}}' => $project . '_adminer_franken',
            '{{TYPESENSE_CONTAINER}}' => $project . '_typesense_franken',
            '{{NETWORK_NAME}}' => $project . '_net',
            '{{ROUTER_PREFIX}}' => $project . '_app',
        ];
    }

    /**
     * Process and publish a stub file.
     */
    protected function publishStub(string $stubName, string $targetPath, array $replacements): void
    {
        if (File::exists($targetPath) && !$this->option('force')) {
            if (!$this->confirm("File {$targetPath} already exists. Overwrite?", false)) {
                return;
            }
        }

        $stubPath = __DIR__ . '/../../stubs/' . $stubName;

        if (!File::exists($stubPath)) {
            $this->components->error("Stub file not found: {$stubName}");
            return;
        }

        $stub = File::get($stubPath);
        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        File::put($targetPath, $content);
        $this->components->task("Published {$stubName}");
    }

    /**
     * Publish the config file if it doesn't exist.
     */
    protected function publishConfig(): void
    {
        if (!File::exists(config_path('frankenphp.php'))) {
            $this->call('vendor:publish', [
                '--tag' => 'frankenphp-config',
                '--quiet' => true,
            ]);
        }
    }
}

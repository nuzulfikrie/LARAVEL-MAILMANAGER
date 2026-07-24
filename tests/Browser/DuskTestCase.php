<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Tests\Browser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Laravel\Dusk\Browser;
use NuzulFikrieCoder\LaravelMailmanager\LaravelMailmanagerServiceProvider;
use Orchestra\Sidekick\Env;
use Orchestra\Testbench\Dusk\Options as DuskOptions;
use Orchestra\Testbench\Dusk\TestCase as Orchestra;
use OwenIt\Auditing\AuditingServiceProvider;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

abstract class DuskTestCase extends Orchestra
{
    protected ?User $duskUser = null;

    public static function setUpBeforeClass(): void
    {
        static::ensureSqliteDatabaseExists();

        parent::setUpBeforeClass();
    }

    /**
     * Shared sqlite file so the Dusk server process and the test process use the same DB.
     */
    protected static function ensureSqliteDatabaseExists(): void
    {
        $path = static::sqliteDatabasePath();
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (! is_file($path)) {
            touch($path);
        }
    }

    protected static function sqliteDatabasePath(): string
    {
        return dirname(__DIR__, 2).'/vendor/orchestra/testbench-dusk/laravel/database/database.sqlite';
    }

    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            AuditingServiceProvider::class,
            LaravelMailmanagerServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        static::ensureSqliteDatabaseExists();

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.debug', true);
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => static::sqliteDatabasePath(),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.mailers.array', ['transport' => 'array']);
        $app['config']->set('audit.enabled', true);
        $app['config']->set('audit.console', true);
        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('laravel-mailmanager.ui.enabled', true);
        $app['config']->set('laravel-mailmanager.route.middleware', ['web']);
        $app['config']->set('laravel-mailmanager.unlayer.project_id', null);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Apply UI + open gates inside the Dusk PHP server process too.
        $this->beforeServingApplication(function ($app): void {
            $app['config']->set('laravel-mailmanager.ui.enabled', true);
            $app['config']->set('laravel-mailmanager.route.middleware', ['web']);
            $app['config']->set('laravel-mailmanager.unlayer.project_id', null);
            $app['config']->set('mail.default', 'array');
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections.sqlite.database', static::sqliteDatabasePath());
            $app['config']->set('auth.providers.users.model', User::class);

            Gate::before(static fn (): true => true);
        });

        Gate::before(static fn (): true => true);

        $this->duskUser = UserFactory::new()->create([
            'email' => 'dusk-admin@example.com',
            'name' => 'Dusk Admin',
        ]);

        $this->publishPackageAssets();
    }

    /**
     * @param  callable(Browser):void  $callback
     */
    protected function browseAsAdmin(callable $callback): void
    {
        $this->browse(function (Browser $browser) use ($callback): void {
            $browser->loginAs($this->duskUser);
            $callback($browser);
        });
    }

    protected function publishPackageAssets(): void
    {
        $public = public_path('vendor/laravel-mailmanager');
        File::ensureDirectoryExists($public.'/css');
        File::ensureDirectoryExists($public.'/js');

        File::copy(
            __DIR__.'/../../public/css/mailmanager.css',
            $public.'/css/mailmanager.css',
        );
        File::copy(
            __DIR__.'/../../public/js/unlayer-bridge.js',
            $public.'/js/unlayer-bridge.js',
        );
        File::copy(
            __DIR__.'/../../public/js/parameter-insert.js',
            $public.'/js/parameter-insert.js',
        );
    }

    protected function driver(): RemoteWebDriver
    {
        DuskOptions::withoutUI();
        DuskOptions::noSandbox();
        DuskOptions::disableGpu();
        DuskOptions::windowSize(1440, 900);

        $options = DuskOptions::getChromeOptions();
        $chromeBinary = $this->resolveChromeBinary();

        if ($chromeBinary !== null) {
            $options->setBinary($chromeBinary);
        }

        return RemoteWebDriver::create(
            Env::get('DUSK_DRIVER_URL') ?? sprintf('http://127.0.0.1:%d', static::$chromeDriverPort),
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options,
            ),
        );
    }

    protected function resolveChromeBinary(): ?string
    {
        $candidates = array_filter([
            Env::get('DUSK_CHROME_PATH'),
            Env::get('CHROME_PATH'),
            dirname(__DIR__, 2).'/chrome/linux-151.0.7922.47/chrome-linux64/chrome',
        ]);

        foreach ($candidates as $path) {
            if (is_string($path) && is_file($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function user()
    {
        return $this->duskUser;
    }
}

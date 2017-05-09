<?php

namespace Statamic\Providers;

use Carbon\Carbon;
use Statamic\API\File;
use Statamic\DataStore;
use Statamic\CP\Router;
use Statamic\API\Config;
use Illuminate\Support\Collection;
use Statamic\Extensions\FileStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Statamic\Extend\Management\Loader;
use Illuminate\Support\ServiceProvider;
use Statamic\Extensions\FileUserProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Set the site's locale
        site_locale(LOCALE);

        // Laravel needs an application key to function securely. We'll allow our
        // users to set it either by using APP_KEY in their .env file (Laravel
        // style) or by setting app_key in their system.yaml (Statamic style).
        $this->ensureAppKey();

        // Any time Carbon is cast to a string (ie. in templates), it uses a default
        // format. We want to override it with whatever the user has specified.
        Carbon::setToStringFormat(Config::get('system.date_format'));

        // Carbon handles i10n for diffForHumans and formatLocalized.
        // Let's enable it.
        try {
            setlocale(LC_TIME, Config::getFullLocale());
            Carbon::setLocale(site_locale());
        } catch(\Exception $e) {
            \Log::error("Your locale does not match any available language translations.");
        }

        // We have our own extension of Laravel's file-based cache driver.
        Cache::extend('statamic', function() {
            return Cache::repository(new FileStore(
                $this->app['files'],
                $this->app['config']["cache.stores.file"]['path']
            ));
        });

        Auth::extend('file', function () {
            return new FileUserProvider();
        });

        // Enable the Debugbar, if necessary
        if (Config::get('debug.debug') && Config::get('debug.debug_bar')) {
            config(['debugbar.enabled' => true]);
        }

        // View composers
        view()->composer('publish', 'Statamic\Http\ViewComposers\PublisherComposer');
        view()->composer(['layout', 'outside', 'installer'], 'Statamic\Http\ViewComposers\TranslationComposer');
        view()->composer('layout', 'Statamic\Http\ViewComposers\PermissionComposer');
        view()->composer('partials.head', 'Statamic\Http\ViewComposers\LayoutComposer');
        view()->composer('partials.scripts', 'Statamic\Http\ViewComposers\FieldtypeJsComposer');
        view()->composer('partials.scripts', 'Statamic\Http\ViewComposers\JavascriptComposer');
        view()->composer('partials.nav-main', 'Statamic\Http\ViewComposers\NavigationComposer');
        view()->composer('settings.edit', 'Statamic\Http\ViewComposers\SettingsComposer');

        // Some servers don't send the appropriate headers to flag the request as https. We can force it.
        if (Config::get('system.use_https')) {
            $this->app['url']->forceSchema('https');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Statamic\DataStore', function() {
            return new DataStore;
        });

        $this->app->singleton('Statamic\Extend\Management\Loader', function() {
            return new Loader;
        });

        $this->app->singleton('Statamic\CP\Router', function ($app) {
            return new Router($app['router'], addon_repo());
        });

        $this->registerPublishers();
    }

    /**
     * Register the Publisher's dependencies.
     *
     * @note This can just be refactored on it's own Service Provider.
     *
     * @return void
     */
    private function registerPublishers()
    {
        $this->app->when(\Statamic\Http\Controllers\PublishPageController::class)
                  ->needs(\Statamic\CP\Publish\Publisher::class)
                  ->give(\Statamic\CP\Publish\PagePublisher::class);

        $this->app->when(\Statamic\Http\Controllers\PublishEntryController::class)
                  ->needs(\Statamic\CP\Publish\Publisher::class)
                  ->give(\Statamic\CP\Publish\EntryPublisher::class);

        $this->app->when(\Statamic\Http\Controllers\PublishGlobalController::class)
                  ->needs(\Statamic\CP\Publish\Publisher::class)
                  ->give(\Statamic\CP\Publish\GlobalsPublisher::class);

        $this->app->when(\Statamic\Http\Controllers\PublishTaxonomyController::class)
                  ->needs(\Statamic\CP\Publish\Publisher::class)
                  ->give(\Statamic\CP\Publish\TaxonomyPublisher::class);

        $this->app->when(\Statamic\Http\Controllers\PublishUserController::class)
                  ->needs(\Statamic\CP\Publish\Publisher::class)
                  ->give(\Statamic\CP\Publish\UserPublisher::class);
    }

    /**
     * Ensure there is an application key set, and if not, generate/set it.
     */
    private function ensureAppKey()
    {
        if (! $this->app['config']['app.key']) {
            if (! $key = Config::getAppKey()) {
                $this->createAndSaveKey();
            }

            $this->app['config']['app.key'] = $key;
        }
    }

    /**
     * If an application key hasn't been set, this will make one and prepend it
     * to the settings file.
     */
    private function createAndSaveKey()
    {
        $key = str_random(32);

        $file = settings_path('system.yaml');

        $contents = File::get($file);

        $contents = "# The following key has been automatically generated by Statamic.\napp_key: {$key}\n\n{$contents}";

        File::put($file, $contents);
    }
}

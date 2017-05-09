<?php

namespace Statamic\Providers;

use Validator;
use Statamic\API\Str;
use Statamic\API\URL;
use Statamic\API\Page;
use Statamic\API\Entry;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Validator::extend('handle_exists', function ($attribute, $value, $parameters, $validator) {
            return ! $this->assetContainerExists($value);
        });

        $this->entrySlugExists();
        $this->pageUriExists();
    }

    /**
     * Check if the AssetContainer exists.
     *
     * @param  string  $value
     * @return boolean
     */
    private function assetContainerExists($value)
    {
        return (bool) \Statamic\API\AssetContainer::find($value);
    }

    private function entrySlugExists()
    {
        Validator::extend('entry_slug_exists', function ($attribute, $value, $parameters, $validator) {
            // Get the ID of the current entry (the one being edited).
            // If an entry is being created this will either be an empty string or just not provided.
            $except = (isset($parameters[1]) && $parameters[1] !== '') ? $parameters[1] : null;

            if (! $existing = Entry::whereSlug($value, $parameters[0])) {
                return true;
            }

            return $except === $existing->id();
        });
    }

    private function pageUriExists()
    {
        Validator::extend('page_uri_exists', function ($attribute, $value, $parameters, $validator) {
            // Get the ID of the current page (the one being edited).
            // If a page is being created this will either be an empty string or just not provided.
            $except = (isset($parameters[1]) && $parameters[1] !== '') ? $parameters[1] : null;

            $uri = URL::assemble($parameters[0], $value);

            if (! $existing = Page::whereUri(Str::ensureLeft($uri, '/'))) {
                return true;
            }

            return $except === $existing->id();
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}

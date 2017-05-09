<?php

namespace Statamic\Providers;

use Statamic\API\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class CollectionsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->getOrPut();
        $this->keyByWithKey();
        $this->filterWithKey();
        $this->l10n();
    }

    /**
     * Get a key from a collection if it exists,
     * otherwise put a value in there and return it.
     *
     * @return void
     */
    private function getOrPut()
    {
        Collection::macro('getOrPut', function ($key, $put) {
            if ($this->has($key)) {
                return $this->get($key);
            }

            $this->put($key, $put);

            return $put;
        });
    }

    /**
     * The Laravel 5.3 way of doing keyBy
     *
     * 5.1 doesn't give us access to the keys.
     *
     * @return void
     */
    private function keyByWithKey()
    {
        Collection::macro('keyByWithKey', function ($keyBy) {
            $keyBy = $this->valueRetriever($keyBy);

            $results = [];

            foreach ($this->items as $key => $item) {
                $resolvedKey = $keyBy($item, $key);

                if (is_object($resolvedKey)) {
                    $resolvedKey = (string) $resolvedKey;
                }

                $results[$resolvedKey] = $item;
            }

            return new static($results);
        });
    }

    /**
     * The Laravel 5.3 way of doing filter
     *
     * 5.1 doesn't give us access to the keys
     *
     * @return void
     */
    private function filterWithKey()
    {
        Collection::macro('filterWithKey', function ($callback) {
            if ($callback) {
                return new static(
                    array_filter_use_both($this->items, $callback)
                );
            }

            return new static(array_filter($this->items));
        });
    }

    private function l10n()
    {
        /**
         * Extract the translations from the files and transform them for
         * usage for the javascript helper.
         *
         * @param  string  $locale
         * @param  string  $prefix  This is for prefixing the keys for our addons.
         */
        Collection::macro('localize', function ($prefix = null) {
            return collect($this->items)
                ->filter(function ($item) {
                    return pathinfo($item, PATHINFO_EXTENSION) == 'php';
                })
                ->keyBy(function ($item) use ($prefix){
                    return $prefix . pathinfo($item, PATHINFO_FILENAME);
                })
                ->map(function ($item) {
                    return require root_path($item);
                });
        });
    }
}

<?php

namespace Statamic\API;

use Statamic\Assets\AssetCollection;
use Statamic\Contracts\Assets\AssetFactory;

class Asset
{
    /**
     * Get an asset by a URL or ID
     *
     * @param string $asset
     * @return \Statamic\Contracts\Assets\Asset
     */
    public static function find($asset)
    {
        if (Str::contains($asset, '::')) {
            return self::whereId($asset);
        }

        return self::whereUrl($asset);
    }

    /**
     * Get an asset by ID
     *
     * @param string $id  An asset ID in the form of "container_id::asset_path""
     * @return \Statamic\Contracts\Assets\Asset
     */
    public static function whereId($id)
    {
        list($container_id, $path) = explode('::', $id);

        // If a container can't be found, we'll assume there's no asset.
        if (! $container = AssetContainer::find($container_id)) {
            return null;
        }

        return $container->asset($path);
    }

    /**
     * Get an asset by url
     *
     * @param string $url
     * @return \Statamic\Contracts\Assets\Asset
     */
    private static function whereUrl($url)
    {
        // Reference the full URL when finding the asset since subdirectory installs would
        // have the assets saved without the subdirectory. Root installs would have no
        // change in URL here. For asset IDs (eg. S3) we'll just leave them alone.
        $fullUrl = (Str::contains($url, '::')) ? $url : URL::prependSiteRoot($url);

        // If a container can't be resolved, we'll assume there's no asset.
        if (! $container = self::resolveContainerFromUrl($fullUrl)) {
            return null;
        }

        $path = trim(Str::removeLeft($fullUrl, URL::makeRelative($container->url())), '/');

        return $container->asset($path);
    }

    /**
     * Find an asset container given an asset URL
     *
     * @param string $url
     * @return \Statamic\Contracts\Assets\AssetContainer
     */
    private static function resolveContainerFromUrl($url)
    {
        return AssetContainer::all()->sortBy(function ($container) {
            return strlen($container->url());
        })->first(function ($id, $container) use ($url) {
            return Str::startsWith($url, URL::makeRelative($container->url()));
        });
    }

    /**
     * Get all assets
     *
     * @return AssetCollection
     */
    public static function all()
    {
        return collect_assets(AssetContainer::all()->flatMap(function ($container) {
            return $container->assets();
        }));
    }

    /**
     * Get all assets in a folder
     *
     * @param string $folder
     * @param string $container
     * @return AssetCollection
     */
    public static function whereFolder($folder, $container)
    {
        return AssetContainer::find($container)->assets($folder);
    }

    /**
     * Get all assets in a container
     *
     * @param string $container
     * @return AssetCollection
     */
    public static function whereContainer($container)
    {
        return AssetContainer::find($container)->assets();
    }

    /**
     * Get an asset by its path
     *
     * @param string      $path
     * @return Asset
     */
    public static function wherePath($path)
    {
        return self::all()->filter(function ($asset) use ($path) {
            return $asset->resolvedPath() === $path;
        })->first();
    }

    /**
     * @param string|null $path
     * @return \Statamic\Contracts\Assets\AssetFactory
     */
    public static function create($path = null)
    {
        return app(AssetFactory::class)->create($path);
    }

    /**
     * Get a raw asset by its UUID
     *
     * @param string      $uuid
     * @return \Statamic\Contracts\Assets\Asset
     * @deprecated since 2.1
     */
    public static function uuidRaw($uuid)
    {
        \Log::notice('Asset::uuidRaw() is deprecated. Use Asset::find()');

        return self::find($uuid);
    }

    /**
     * Get an asset by its UUID
     *
     * @param string      $uuid
     * @return array
     * @deprecated since 2.1
     */
    public static function uuid($uuid)
    {
        \Log::notice('Asset::uuid() is deprecated. Use Asset::find()->toArray()');

        return self::find($uuid)->toArray();
    }

    /**
     * Get an asset by its path
     *
     * @param string      $path
     * @return Asset
     * @deprecated since 2.1
     */
    public static function path($path)
    {
        \Log::notice('Asset::path() is deprecated. Use Asset::wherePath()');

        return self::wherePath($path);
    }
}

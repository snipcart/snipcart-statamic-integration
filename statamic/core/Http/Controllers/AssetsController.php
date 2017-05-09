<?php

namespace Statamic\Http\Controllers;

use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Statamic\API\Arr;
use Statamic\API\Asset;
use Statamic\API\AssetContainer;
use Statamic\API\Config;
use Statamic\API\File;
use Statamic\API\Helper;
use Statamic\API\Path;
use Statamic\API\Stache;
use Statamic\API\Str;
use Statamic\Assets\AssetCollection;
use Statamic\Imaging\ImageGenerator;
use Statamic\Imaging\ThumbnailUrlBuilder;
use Statamic\Contracts\Imaging\UrlBuilder;
use Statamic\Presenters\PaginationPresenter;

class AssetsController extends CpController
{
    private static $thumb_builder;

    /**
     * The main assets routes, which redirects to the browse the first container.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $this->access('assets:*:edit');

        $containers = AssetContainer::all();

        return redirect()->route('assets.browse', $containers->first()->uuid());
    }

    /**
     * List the contents of a particular folder
     *
     * @param string $container  UUID of the container
     * @param string $folder     Path of the folder
     * @return \Illuminate\View\View
     */
    public function browse($container, $folder = '/')
    {
        $this->access('assets:'.$container.':edit');

        $title = translate('cp.browsing_assets');

        return view('assets.browse', compact('title', 'container', 'folder'));
    }

    /**
     * Get asset data for a particular page in the asset browser Vue component
     *
     * @return array
     */
    public function json()
    {
        // Get the path from the request, and normalize it.
        $path = $this->request->path;
        $path = ($path === '') ? '/' : $path;

        // Find the requested container.
        $container = AssetContainer::find($this->request->container);

        // Grab all the assets from the container.
        $assets = $container->assets($path);

        // Set up the paginator, since we don't want to display all the assets.
        $totalAssetCount = $assets->count();
        $perPage = Config::get('cp.pagination_size');
        $currentPage = (int) $this->request->page ?: 1;
        $offset = ($currentPage - 1) * $perPage;
        $assets = $assets->slice($offset, $perPage);
        $paginator = new LengthAwarePaginator($assets, $totalAssetCount, $perPage, $currentPage);

        foreach ($assets as &$asset) {
            // Add thumbnails to all image assets.
            if ($asset->isImage()) {
                $asset->set('thumbnail', $this->thumbnail($asset, 'small'));
                $asset->set('toenail', $this->thumbnail($asset, 'large'));
            }

            // Set some values for better listing formatting.
            $asset->set('size_formatted', Str::fileSizeForHumans($asset->size(), 0));
            $asset->set('last_modified_formatted', $asset->lastModified()->format(Config::get('cp.date_format')));
        }

        // Get data about the subfolders in the requested asset folder.
        $folders = [];
        foreach ($container->assetFolders($path) as $f) {
            $folders[] = [
                'path' => $f->path(),
                'title' => $f->title()
            ];
        }

        return [
            'container' => Arr::except($container->toArray(), 'assets'),
            'containers' => AssetContainer::all()->toArray(),
            'assets' => $assets->toArray(),
            'folders' => $folders,
            'folder' => $container->assetFolder($path)->toArray(),
            'pagination' => [
                'totalItems' => $totalAssetCount,
                'itemsPerPage' => $perPage,
                'totalPages'    => $paginator->lastPage(),
                'currentPage'   => $paginator->currentPage(),
                'prevPage'      => $paginator->previousPageUrl(),
                'nextPage'      => $paginator->nextPageUrl(),
                'segments'      => array_get($paginator->render(new PaginationPresenter($paginator)), 'segments')
            ]
        ];
    }

    /**
     * Get asset data for specifically requested assets, for use in a fieldtype.
     *
     * @return AssetCollection
     */
    public function get()
    {
        $assets = new AssetCollection;

        foreach ($this->request->assets as $url) {
            if (! $asset = Asset::find($url)) {
                continue;
            }

            if ($asset->isImage()) {
                $asset->set('thumbnail', $this->thumbnail($asset, 'small'));
                $asset->set('toenail', $this->thumbnail($asset, 'large'));
            }

            $assets->put($url, $asset);
        }

        return $assets;
    }

    public function store()
    {
        if (! $this->request->hasFile('file')) {
            return response()->json($this->request->file('file')->getErrorMessage(), 400);
        }

        try {
            $container = AssetContainer::find($this->request->input('container'));

            $file = $this->request->file('file');

            $asset = $container->createAsset(
                Path::tidy($this->request->input('folder') . '/' . $file->getClientOriginalName())
            );

            $asset->upload($file);

            if ($asset->isImage()) {
                $asset->set('thumbnail', $this->thumbnail($asset, 'small'));
                $asset->set('toenail', $this->thumbnail($asset, 'large'));
            }

        } catch (Exception $e) {
            \Log::error($e);
            return response()->json($e->getMessage(), 400);
        }

        return response()->json([
            'success' => true,
            'asset' => $asset->toArray()
        ]);
    }

    public function edit($container_id, $path)
    {
        $container = AssetContainer::find($container_id);

        $asset = $this->supplementAssetForEditing($container->asset($path));

        $this->authorize("assets:{$container_id}:edit");

        $fields = $this->populateWithBlanks($asset);

        return ['asset' => $asset->toArray(), 'fields' => $fields];
    }

    public function update($container_id, $path)
    {
        $container = AssetContainer::find($container_id);

        $asset = $container->asset($path);

        $this->authorize("assets:{$container_id}:edit");

        $fields = $this->processFields($asset, $this->request->all());

        $asset->data($fields);

        $asset->save();

        if ($asset->isImage()) {
            $asset->set('thumbnail', $this->thumbnail($asset, 'small'));
            $asset->set('toenail', $this->thumbnail($asset, 'large'));
        }

        return ['success' => true, 'message' => 'Asset updated', 'asset' => $asset->toArray()];
    }

    private function populateWithBlanks($arg)
    {
        // Get a fieldset and data
        $fieldset = $arg->fieldset();
        $data = $arg->processedData();

        // Get the fieldtypes
        $fieldtypes = collect($fieldset->fieldtypes())->keyBy(function($ft) {
            return $ft->getName();
        });

        // Build up the blanks
        $blanks = [];
        foreach ($fieldset->fields() as $name => $config) {
            if (! $default = array_get($config, 'default')) {
                $default = $fieldtypes->get($name)->blank();
            }

            $blanks[$name] = $default;
            if ($fieldtype = $fieldtypes->get($name)) {
                $blanks[$name] = $fieldtype->preProcess($default);
            }
        }

        return array_merge($blanks, $data);
    }

    protected function processFields($asset, $fields)
    {
        foreach ($asset->fieldset()->fieldtypes() as $field) {
            if (! in_array($field->getName(), array_keys($fields))) {
                continue;
            }

            $fields[$field->getName()] = $field->process($fields[$field->getName()]);
        }

        // Get rid of null fields
        $fields = array_filter($fields);

        return $fields;
    }

    public function delete()
    {
        $ids = Helper::ensureArray($this->request->input('ids'));

        foreach ($ids as $id) {
            list($container_id, $path) = explode('::', $id, 2);

            $container = AssetContainer::find($container_id);

            $this->authorize("assets:{$container_id}:delete");

            $container->asset($path)->delete();
        }

        return ['success' => true];
    }

    private function thumbnail($asset, $preset = null)
    {
        $params = ($preset) ? ['p' => "cp_thumbnail_$preset"] : [];

        return $this->thumbnailBuilder()->build($asset, $params);
    }

    private function thumbnailBuilder()
    {
        if (self::$thumb_builder) {
            return self::$thumb_builder;
        }

        self::$thumb_builder = (Config::get('assets.image_manipulation_cached'))
            ? app(UrlBuilder::class)
            : new ThumbnailUrlBuilder(app(ImageGenerator::class));

        return self::$thumb_builder;
    }

    public function replaceEditedImage()
    {
        $asset = Asset::find($this->request->id);

        $handle = fopen($this->request->new_url, 'rb');
        $contents = stream_get_contents($handle);
        fclose($handle);

        $asset->replace($contents);

        return [
            'success' => true,
            'thumbnail' => $this->thumbnail($asset, 'large')
        ];
    }

    public function editorAuth()
    {
        $apiKey = '';
        $apiSecret = '';
        $salt = rand(0, 1000);
        $time = time();
        $sig = sha1($apiKey . $apiSecret . $time . $salt);

        return [
            'timestamp' => $time,
            'salt' => $salt,
            'encryptionMethod' => 'sha1',
            'signature' => $sig
        ];
    }

    public function download($container_id, $path)
    {
        $container = AssetContainer::find($container_id);
        $asset = $container->asset($path);

        $file = $asset->path();

        $filesystem = $asset->disk()->filesystem()->getDriver();
        $stream = $filesystem->readStream($file);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            "Content-Type" => $filesystem->getMimetype($file),
            "Content-Length" => $filesystem->getSize($file),
            "Content-disposition" => "attachment; filename=\"" . basename($file) . "\"",
        ]);
    }

    /**
     * Rename the file associated with an asset
     *
     * @param string $container_id
     * @param string $path
     * @return array
     */
    public function rename($container_id, $path)
    {
        $this->validate($this->request, [
            'filename' => 'required|alpha_dash'
        ]);

        $container = AssetContainer::find($container_id);
        $asset = $this->supplementAssetForEditing($container->asset($path));

        $asset->rename($this->request->filename);

        return $asset->toArray();
    }

    /**
     * An asset requested to be used within the editor should have some additional data.
     *
     * @param \Statamic\Contracts\Assets\Asset $asset
     * @return \Statamic\Contracts\Assets\Asset
     */
    private function supplementAssetForEditing($asset)
    {
        if ($asset->isImage()) {
            $asset->setSupplement('preview', $this->thumbnail($asset));
            $asset->setSupplement('width', $asset->width());
            $asset->setSupplement('height', $asset->height());
        }

        $asset->setSupplement('last_modified_relative', $asset->lastModified()->diffForHumans());
        $asset->setSupplement('download_url', route('asset.download', [$asset->containerId(), $asset->path()]));

        return $asset;
    }

    /**
     * Move one or more assets to another folder
     *
     * Accepts a payload with a folder path and an array of asset IDs.
     * For example:
     * [
     *   folder: 'foo/bar',
     *   assets: ['main::one.jpg', 'main::two.jpg']
     * ]
     *
     * @return mixed
     */
    public function move()
    {
        $container = AssetContainer::find($this->request->container);
        $folder = $this->request->folder;

        return collect($this->request->assets)->map(function ($asset) use ($container, $folder) {
            $path = explode('::', $asset)[1];

            $asset = $container->asset($path);

            $asset->move($folder);

            return $asset;
        });
    }
}

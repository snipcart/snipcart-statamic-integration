<?php

namespace Statamic\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\API\Fieldset;
use Statamic\API\Config;
use Statamic\API\Content;
use Statamic\API\Taxonomy;
use Statamic\CP\Publish\Publisher;
use Statamic\Contracts\CP\Fieldset as FieldsetContract;
use Statamic\Exceptions\PublishException;

abstract class PublishController extends CpController
{
    use GetsTaxonomiesFromFieldsets;

    /**
     * Abstract publisher.
     *
     * @var Publisher
     */
    protected $publisher;

    /**
     * In the parent controller, the locale is being set up, which might be
     * possible to just refactor out into a middleware to keep the constructor
     * just about the dependencies (will have to look into that.)
     *
     * @param  Publisher  $publisher
     * @return PublishController
     */
    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;

        parent::__construct(app('request'));
    }

    /**
     * Build the unique redirect for the specific controller.
     *
     * @param  Request  $request
     * @param  \Statamic\Contracts\Data\Content\Content  $content
     * @return string
     */
    abstract protected function redirect(Request $request, $content);

    /**
     * Save the content.
     *
     * This can also be implemented to the child components so an event can be
     * triggered specific to that content.
     *
     * @todo We can refactor this out to `update()` and `post()` later on but
     *       we'll need to refactor `publish.js` as well since it only targets
     *       POST requests.
     *
     *       We'll end up doing something like `<publish method="put">` or
     *       something to make everything just a tad bit more RESTful.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        try {
            /**
             * Maybe refactor to take in the fields so we don't have to depend
             * on the construct?
             *
             *      $publisher->publish($request->fields);
             *
             * Also try to find a way not to depend on a try catch. Maybe a
             * return false or null.
             *
             *      if (! $publisher->publish($request->fields)) {
             *          return ['success' => false];
             *      }
             *
             *      return ['success' => true];
             *
             * Validation idea can be:
             *
             *      $publisher->validates();
             *
             * Although, accessing the error messages would be a pickle.
             */
            $content = $this->publisher->publish();

        } catch (PublishException $e) {
            return [
                'success' => false,
                'errors'  => $e->getErrors()
            ];
        }

        $this->success(translate('cp.thing_saved', ['thing' => ucwords($request->type)]));

        return [
            'success'  => true,
            'redirect' => $this->buildRedirect($request, $content),
        ];
    }

    /**
     * Do some post processing with the redirect.
     *
     * Include here the previous query parameters that were added and the
     * localization might be included here as well.
     *
     * @param  Request  $request
     * @param  \Statamic\Contracts\Data\Content\Content  $content
     * @return string
     */
    private function buildRedirect(Request $request, $content)
    {
        if (! $query = parse_url($request->header('referer'), PHP_URL_QUERY)) {
            return $this->redirect($request, $content);
        }

        if (! $query = $this->buildQueryString($query)) {
            return $this->redirect($request, $content);
        }

        return $this->redirect($request, $content) . '?' . $this->buildQueryString($query);
    }

    /**
     * Build the http query.
     *
     * @param  string  $query
     * @return string
     */
    private function buildQueryString($query)
    {
        parse_str($query, $query);

        if (array_get($query, 'locale') === default_locale()) {
            unset($query['locale']);
        }

        return http_build_query(
            collect($query)->except('fieldset', 'slug')->all()
        );
    }

    /**
     * Get locales and their links
     *
     * @param string|null $uuid
     * @return array
     */
    protected function getLocales($uuid = null)
    {
        $locales = [];

        foreach (Config::getLocales() as $locale) {
            $url = app('request')->url();

            if ($locale !== Config::getDefaultLocale()) {
                $url .= '?locale=' . $locale;
            }

            $has_content = false;
            if ($uuid) {
                $has_content = Content::find($uuid)->hasLocale($locale);
            }

            $locales[] = [
                'name'        => $locale,
                'label'       => Config::getLocaleName($locale),
                'url'         => $url,
                'is_active'   => $locale === app('request')->query('locale', Config::getDefaultLocale()),
                'has_content' => $has_content
            ];
        }

        return $locales;
    }

    /**
     * Create the data array, populating it with blank values for all fields in
     * the fieldset, then overriding with the actual data where applicable.
     *
     * @param string|\Statamic\Data\Content $arg Either a content object, or the name of a fieldset.
     * @return array
     */
    protected function populateWithBlanks($arg)
    {
        // Get a fieldset and data
        if ($arg instanceof \Statamic\Contracts\Data\Content\Content) {
            $fieldset = $arg->fieldset();
            $data = $arg->processedData();
        } else {
            $fieldset = Fieldset::get($arg);
            $data = [];
        }

        // This will be the "merged" fieldset, built up from any partials.
        $merged_fieldset = [];

        // Get the fieldtypes
        $fieldtypes = collect($fieldset->fieldtypes());

        // Merge any fields from nested fieldsets (only a single level - @todo: recursion)
        $partials = collect();
        $fieldtypes->each(function ($ft) use ($partials, &$merged_fieldset) {
            if ($ft->getAddonClassName() === 'Partial') {
                $pfs = Fieldset::get($ft->getFieldConfig('fieldset'));

                $merged_fieldset = array_merge($pfs->fields(), $merged_fieldset);

                foreach ($pfs->fieldtypes() as $f) {
                    $partials->push($f);
                }
            }
        });

        // Merge the partials and key everything by field name.
        $fieldtypes = $fieldtypes->merge($partials)->keyBy(function($ft) {
            return $ft->getName();
        });
        $merged_fieldset = array_merge($fieldset->fields(), $merged_fieldset);

        // Build up the blanks
        $blanks = [];
        foreach ($merged_fieldset as $name => $config) {
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
}

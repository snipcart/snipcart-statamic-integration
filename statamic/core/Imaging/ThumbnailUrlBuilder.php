<?php

namespace Statamic\Imaging;

use Statamic\API\URL;
use Statamic\API\Path;
use Statamic\API\Config;

class ThumbnailUrlBuilder extends StaticUrlBuilder
{
    /**
     * Build the URL
     *
     * @param \Statamic\Contracts\Assets\Asset|string $item
     * @param array                                   $params
     * @param string|null                             $filename
     * @return string
     */
    public function build($item, $params, $filename = null)
    {
        $this->item = $item;
        $this->params = $params;

        $url = URL::assemble(
            RESOURCES_ROUTE,
            'thumbs',
            base64_encode($this->generatePath()),
            $item->basename()
        );

        return URL::prependSiteRoot($url);
    }
}

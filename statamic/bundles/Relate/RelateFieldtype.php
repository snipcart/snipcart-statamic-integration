<?php

namespace Statamic\Addons\Relate;

use Statamic\Addons\Suggest\SuggestFieldtype;

class RelateFieldtype extends SuggestFieldtype
{
    public function preProcess($data)
    {
        $max_items = (int) $this->getFieldConfig('max_items');

        $data = (array) $data;

        if ($max_items > 1) {
            return array_slice($data, 0, $max_items);
        }

        return $data;
    }

    public function process($data)
    {
        $max_items = (int) $this->getFieldConfig('max_items');

        if ($max_items === 1 && is_array($data)) {
            return $data[0];
        }

        return $data;
    }
}

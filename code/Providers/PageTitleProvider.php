<?php

namespace Seeder\Providers;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FormField;

/**
 * Class PageTitleProvider
 * @package Seeder
 */
class PageTitleProvider extends Provider
{
    /**
     * @var string
     */
    public static $shorthand = 'PageTitle';

    /**
     * @param $field
     * @param $state
     * @return mixed|string
     */
    protected function generateField($field, $state)
    {
        if (!$state->object()) {
            return 'Page Title';
        }

        $page = $state->object();

        $name = str_replace(array('Page', 'Holder'), '', ClassInfo::shortName($page->ClassName));
        $name = ucwords(FormField::name_to_label($name));

        return $name;
    }
}

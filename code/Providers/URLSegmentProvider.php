<?php

namespace Seeder\Providers;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FormField;
use SilverStripe\CMS\Model\SiteTree;

/**
 * Class URLSegmentProvider
 * @package Seeder
 */
class URLSegmentProvider extends Provider
{
    /**
     * @var string
     */
    public static $shorthand = 'URLSegment';

    /**
     * @param $field
     * @param $state
     * @return string
     */
    protected function generateField($field, $state)
    {
        if (!$state->object()) {
            return uniqid('url');
        }

        $page = $state->object();

        if ($field->totalCount > 1) {
            return Convert::raw2url(uniqid($page->Title));
        }

        $name = str_replace(array('Page', 'Holder'), '', ClassInfo::shortName($page->ClassName));
        $name = FormField::name_to_label($name);

        // check if already exists
        $url = Convert::raw2url($name);
        if (SiteTree::get()->filter('URLSegment', $url)->Count()) {
            return uniqid($url);
        }

        return $url;
    }
}

<?php

namespace Seeder\Providers;

use SilverStripe\ORM\DataObject;

/**
 * Class SortProvider
 * @package Seeder
 */
class SortProvider extends Provider
{
    /**
     * @var string
     */
    public static $shorthand = 'sort';

    /**
     * @var array
     */
    private static $classCache = array();

    /**
     * @var array
     */
    private static $sortCache = array();

    /**
     * @param $field
     * @param $state
     * @return int
     */
    protected function generateField($field, $state)
    {
        if (!$state->object()) {
            return 0;
        }

        $obj = $state->object();
        $className = $obj->ClassName;
        if (!isset(self::$classCache[$className])) {
            $ancestry = singleton($className)->getClassAncestry();
            foreach ($ancestry as $ancestor) {
                $fields = DataObject::getSchema()->databaseFields($ancestor, false);
                if (isset($fields[$field->name])) {
                    self::$classCache[$className] = $ancestor;
                    break;
                }
            }
        }

        $sortClass = self::$classCache[$className];

        if (!isset(self::$sortCache[$sortClass])) {
            self::$sortCache[$sortClass] = $sortClass::get()->max($field->name);
        }

        $sort = self::$sortCache[$sortClass] + 1;
        self::$sortCache[$sortClass] = $sort;
        return $sort;
    }
}

<?php

namespace Seeder\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Class Treat
 * @package Seeder\Tests
 */
class Treat extends DataObject implements TestOnly
{
    /**
     * @var array
     */
    private static $db = array(
        'Brand' => 'Varchar',
        'Flavour' => 'Varchar',
    );

    private static $table_name = 'Treat';

    /**
     * @var array
     */
    private static $has_one = array(
        'Pet' => Pet::class,
    );
}

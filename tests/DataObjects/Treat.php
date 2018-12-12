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
    public static $db = array(
        'Brand' => 'Varchar',
        'Flavour' => 'Varchar',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'Pet' => 'Seeder\Tests\Pet',
    );
}

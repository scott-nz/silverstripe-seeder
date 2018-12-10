<?php

namespace Seeder\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Class House
 * @package Seeder\Tests
 */
class House extends DataObject implements TestOnly
{
    /**
     * @var array
     */
    public static $db = array(
        'Address' => 'Varchar(255)',
    );

    /**
     * @var array
     */
    private static $many_many = array(
        'Occupants' => 'Seeder\Tests\Human',
    );
}

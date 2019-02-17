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
    private static $db = array(
        'Address' => 'Varchar(255)',
    );

    private static $table_name = 'House';

    /**
     * @var array
     */
    private static $many_many = array(
        'Occupants' => Human::class,
    );
}

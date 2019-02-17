<?php

namespace Seeder\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Class Pet
 * @package Seeder\Tests
 */
class Pet extends DataObject implements TestOnly
{
    /**
     * @var array
     */
    private static $db = array(
        'Name' => 'Varchar(60)',
        'Age' => 'Int',
    );

    private static $table_name = 'Pet';

    /**
     * @var array
     */
    private static $has_many = array(
        'Treats' => Treat::class,
    );

    /**
     * @var array
     */
    private static $belongs_many_many = array(
        'BelongsHuman' => Human::class,
    );
}

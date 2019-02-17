<?php

namespace Seeder\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Class Human
 * @package Seeder\Tests
 */
class Human extends DataObject implements TestOnly
{
    /**
     * @var array
     */
    private static $db = array(
        'Name' => 'Varchar(60)',
        'Age' => 'Int',
    );

    private static $table_name = 'Human';

    /**
     * @var array
     */
    private static $has_one = array(
        'Parent' => Human::class,
        'House' => House::class,
    );

    /**
     * @var array
     */
    private static $many_many = array(
        'Pets' => Pet::class,
        'Children' => Human::class,
    );

    /**
     * @var array
     */
    private static $belongs_many_many = array(
        'Parents' => Human::class,
    );
}

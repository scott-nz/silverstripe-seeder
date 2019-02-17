<?php

namespace Seeder\Tests;

use SilverStripe\Dev\TestOnly;


/**
 * Class Dog
 * @package Seeder\Tests
 */
class Dog extends Pet implements TestOnly
{
    /**
     * @var array
     */
    private static $db = array(
        'Breed' => 'Varchar',
    );
    
    private static $table_name = 'Dog';
}

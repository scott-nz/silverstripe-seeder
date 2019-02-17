<?php

namespace Seeder\Tests;

use Seeder\Helpers\ConfigParser;
use Seeder\Util\SeederState;
use SilverStripe\CMS\Model\SiteTree;
use Seeder\Providers\SortProvider;
use SilverStripe\Dev\SapphireTest;

/**
 * Class SortProviderTest
 * @package Seeder\Tests
 */
class SortProviderTest extends SapphireTest
{
    /**
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     *
     */
    public function testGenerate_SiteTreeSort_ReturnsIncreasingSort()
    {
        $config = new ConfigParser();
        $field = $config->objectConfig2Field(array(
            'class' => SiteTree::class,
            'fields' => array(
                'Sort' => 'sort()',
            ),
        ));

        $sortField = null;
        foreach ($field->fields as $dbField) {
            if ($dbField->name === 'Sort') {
                $sortField = $dbField;
            }
        }

        $this->assertNotNull($sortField);

        $state = new SeederState($field, new SiteTree());

        $provider = new SortProvider();

        $value1 = $provider->generate($sortField, $state);
        $value2 = $provider->generate($sortField, $state);
        $value3 = $provider->generate($sortField, $state);

        $this->assertTrue($value1[0] < $value2[0]);
        $this->assertTrue($value2[0] < $value3[0]);
    }

//    /**
//     *
//     */
//    public static function tearDownAfterClass()
//    {
//        parent::tearDownAfterClass();
//        \SapphireTest::delete_all_temp_dbs();
//    }
}

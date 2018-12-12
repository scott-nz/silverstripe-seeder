<?php

namespace Seeder\Tests;

use Seeder\Helpers\ConfigParser;
use Seeder\Util\RecordWriter;
use Seeder\Util\SeederState;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;

/**
 * Class ProviderTest
 * @package Seeder\Tests
 */
class ProviderTest extends SapphireTest
{
    /**
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * @var array
     */
    protected $extraDataObjects = array(
        'Seeder\Tests\Dog',
        'Seeder\Tests\House',
        'Seeder\Tests\Human',
        'Seeder\Tests\Pet',
        'Seeder\Tests\Treat',
    );

//    /**
//     *
//     */
//    public function __construct()
//    {
//        parent::__construct();
//        $this->setUpOnce();
//    }

    /**
     *
     */
    public function testGenerate_SimpleFields_GeneratesObjectWithFields()
    {
        $writer = new RecordWriter();
        $configParser = new ConfigParser($writer);

        $field = $configParser->objectConfig2Field(array(
            'class' => 'Seeder\Tests\Dog',
            'provider' => 'Seeder\Tests\TestProvider',
            'fields' => array(
                'Name' => 'test()',
                'Age' => 'test()',
                'Breed' => 'test()',
            ),
        ));

        $provider = $field->provider;

        $dogs = $provider->generate($field, new SeederState());
        $writer->finish();

        $this->assertCount(1, $dogs);
        $this->assertEquals(1, Dog::get()->Count());

        $dog = $dogs[0];
        $this->assertEquals(TestProvider::TEST_STRING, $dog->Name);
        $this->assertEquals(TestProvider::TEST_INT, $dog->Age);
        $this->assertEquals(TestProvider::TEST_STRING, $dog->Breed);
    }

    /**
     *
     */
    public function testGenerate_HasOneField_GeneratesObjectWithHasOneField()
    {
        $writer = new RecordWriter();
        $configParser = new ConfigParser($writer);

        $field = $configParser->objectConfig2Field(array(
            'class' => 'Seeder\Tests\Human',
            'provider' => 'Seeder\Tests\TestProvider',
            'fields' => array(
                'Parent' => array(
                    'provider' => 'Seeder\Tests\TestProvider',
                    'fields' => array(
                        'Name' => 'test()',
                        'Age' => 'test()',
                    ),
                ),
            ),
        ));

        $provider = $field->provider;

        $people = $provider->generate($field, new SeederState());
        $writer->finish();

        $this->assertCount(1, $people);
        $this->assertEquals(2, Human::get()->Count());

        $person = $people[0];
        $parent = $person->Parent();
        $this->assertTrue($parent->exists());
        $this->assertEquals(TestProvider::TEST_STRING, $parent->Name);
        $this->assertEquals(TestProvider::TEST_INT, $parent->Age);
    }

    /**
     *
     */
    public function testGenerate_HasOneDependency_GeneratesObject()
    {
        $writer = new RecordWriter();
        $configParser = new ConfigParser($writer);

        $field = $configParser->objectConfig2Field(array(
            'class' => 'Seeder\Tests\Human',
            'provider' => 'Seeder\Tests\TestProvider',
            'fields' => array(
                'Parent' => array(
                    'provider' => 'Seeder\Tests\TestProvider',
                    'fields' => array(
                        'Parent' => array(
                            'provider' => 'Seeder\Tests\TestProvider',
                            'fields' => array(
                                'Parent' => 'value({$Up.Up})',
                            ),
                        ),
                    ),
                ),
            ),
        ));

        $provider = $field->provider;

        $people = $provider->generate($field, new SeederState());
        $writer->finish();

        $this->assertCount(1, $people);
        $this->assertEquals(3, Human::get()->Count());

        $person = $people[0];
        $parent = $person->Parent();

        $this->assertTrue($parent->exists());
        $this->assertEquals($person->ID, $parent->Parent()->ParentID);
    }

    /**
     *
     */
    public function testGenerate_HasManyField_GeneratesObjectWithHasOneManyField()
    {
        $writer = new RecordWriter();
        $configParser = new ConfigParser($writer);

        $field = $configParser->objectConfig2Field(array(
            'class' => 'Seeder\Tests\Dog',
            'provider' => 'Seeder\Tests\TestProvider',
            'fields' => array(
                'Treats' => array(
                    'count' =>  10,
                    'fields' => array(
                        'Brand' => 'test()',
                        'Flavour' => 'test()',
                    ),
                ),
            ),
        ));

        $provider = $field->provider;

        $dogs = $provider->generate($field, new SeederState());
        $writer->finish();

        $this->assertCount(1, $dogs);
        $this->assertEquals(10, Treat::get()->Count());

        $dog = $dogs[0];
        $treats = $dog->Treats();
        $this->assertEquals(10, $treats->Count());
        foreach ($treats as $treat) {
            $this->assertEquals(TestProvider::TEST_STRING, $treat->Brand);
            $this->assertEquals(TestProvider::TEST_STRING, $treat->Flavour);
        }
    }

    /**
     *
     */
    public function testGenerate_ManyManyField_GeneratesObjectWithManyManyField()
    {
        $writer = new RecordWriter();
        $configParser = new ConfigParser($writer);

        $field = $configParser->objectConfig2Field(array(
            'class' => 'Seeder\Tests\Human',
            'provider' => 'Seeder\Tests\TestProvider',
            'fields' => array(
                'Children' => array(
                    'count' => 10,
                    'provider' => 'Seeder\Tests\TestProvider',
                    'fields' => array(
                        'Name' => 'test()',
                        'Age' => 'test()',
                    ),
                ),
                'Pets' => array(
                    'count' => 5,
                    'provider' => 'Seeder\Tests\TestProvider',
                ),
            ),
        ));

        $provider = $field->provider;

        $people = $provider->generate($field, new SeederState());
        $writer->finish();

        $this->assertCount(1, $people);
        $this->assertEquals(11, Human::get()->Count());
        $this->assertEquals(5, Pet::get()->Count());

        $person = $people[0];
        $children = $person->Children();
        $this->assertEquals(10, $children->Count());
        foreach ($children as $child) {
            $this->assertEquals(TestProvider::TEST_STRING, $child->Name);
            $this->assertEquals(TestProvider::TEST_INT, $child->Age);
        }
    }

    /**
     *
     */
    public function testGenerate_UnpublishedPage_GeneratesUnpublishedPage()
    {
        $writer = new RecordWriter();
        $configParser = new ConfigParser($writer);

        $field = $configParser->objectConfig2Field(array(
            'class' => 'SiteTree',
            'provider' => 'Seeder\Tests\TestProvider',
            'publish' => false,
            'fields' => array(
                'Title' => 'test()',
            ),
        ));

        $provider = $field->provider;

        $pages = $provider->generate($field, new SeederState());
        $writer->finish();

        $this->assertCount(1, $pages);
        $this->assertFalse($pages[0]->isPublished());

        $currentStage = \Versioned::current_stage();
        \Versioned::reading_stage('Stage');
        $this->assertEquals(1, \SiteTree::get()->Count());

        \Versioned::reading_stage('Live');
        $this->assertEquals(0, \SiteTree::get()->Count());

        \Versioned::reading_stage($currentStage);
    }

    /**
     *
     */
    public function testGenerate_PublishedPage_GeneratesPublishedPage()
    {
        $writer = new RecordWriter();
        $configParser = new ConfigParser($writer);

        $field = $configParser->objectConfig2Field(array(
            'class' => 'SiteTree',
            'provider' => 'Seeder\Tests\TestProvider',
            'fields' => array(
                'Title' => 'test()',
            ),
        ));

        $provider = $field->provider;

        $pages = $provider->generate($field, new SeederState());
        $writer->finish();

        $this->assertCount(1, $pages);
        $this->assertTrue($pages[0]->isPublished());

        $currentStage = Versioned::current_stage();
        Versioned::reading_stage('Stage');
        $this->assertEquals(1, SiteTree::get()->Count());

        Versioned::reading_stage('Live');
        $this->assertEquals(1, SiteTree::get()->Count());

        Versioned::reading_stage($currentStage);
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

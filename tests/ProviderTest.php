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

//TESTS FAILING
class ProviderTest extends SapphireTest
{
    /**
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        Dog::class,
        House::class,
        Human::class,
        Pet::class,
        Treat::class,
    ];

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    public function testGenerate_SimpleFields_GeneratesObjectWithFields()
    {
        $writer = singleton(RecordWriter::class);
        $configParser = new ConfigParser($writer);

        $field = $configParser->objectConfig2Field(array(
            'class' => Dog::class,
            'provider' => TestProvider::class,
            'fields' => array(
                'Name' => 'test()',
                'Age' => 'test()',
                'Breed' => 'test()',
            ),
        ));

        $provider = $field->provider;
        $state = new SeederState();
        $dogs = $provider->generate($field, $state);
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
            'class' => Human::class,
            'provider' => TestProvider::class,
            'fields' => array(
                'Parent' => array(
                    'provider' => TestProvider::class,
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
            'class' => Human::class,
            'provider' => TestProvider::class,
            'fields' => array(
                'Parent' => array(
                    'provider' => TestProvider::class,
                    'fields' => array(
                        'Parent' => array(
                            'provider' => TestProvider::class,
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
            'class' => Dog::class,
            'provider' => TestProvider::class,
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
            'class' => Human::class,
            'provider' => TestProvider::class,
            'fields' => array(
                'Children' => array(
                    'count' => 10,
                    'provider' => TestProvider::class,
                    'fields' => array(
                        'Name' => 'test()',
                        'Age' => 'test()',
                    ),
                ),
                'Pets' => array(
                    'count' => 5,
                    'provider' => TestProvider::class,
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
            'class' => SiteTree::class,
            'provider' => TestProvider::class,
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

        $currentStage = Versioned::get_stage();
        Versioned::set_stage('Stage');
        $this->assertEquals(1, SiteTree::get()->Count());

        Versioned::set_stage('Live');
        $this->assertEquals(0, SiteTree::get()->Count());

        if ($currentStage != "") {
            Versioned::set_stage($currentStage);
        }
    }

    /**
     *
     */
    public function testGenerate_PublishedPage_GeneratesPublishedPage()
    {
        //TODO: fix test
        $writer = new RecordWriter();
        $configParser = new ConfigParser($writer);

        $field = $configParser->objectConfig2Field(array(
            'class' => SiteTree::class,
            'provider' => TestProvider::class,
            'fields' => array(
                'Title' => 'test()',
            ),
        ));

        $provider = $field->provider;

        $pages = $provider->generate($field, new SeederState());
        $writer->finish();

        $this->assertCount(1, $pages);
        $is = $pages[0]->isPublished();
        $this->assertTrue($pages[0]->isPublished());

        $currentStage = Versioned::current_stage();
        Versioned::get_stage('Stage');
        $this->assertEquals(1, SiteTree::get()->Count());

        Versioned::set_stage('Live');
        $this->assertEquals(1, SiteTree::get()->Count());

        if ($currentStage != "") {
            Versioned::set_stage($currentStage);
        }
    }

}

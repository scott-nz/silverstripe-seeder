<?php

namespace Seeder\Tests;

use Seeder\DataObjects\SeedRecord;
use Seeder\Util\BatchedSeedWriter;
use Seeder\Util\Field;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;
use LittleGiant\BatchWrite\OnAfterExists;

/**
 * Class BatchSeedWriterTest
 * @package Seeder\Tests
 */
class BatchSeedWriterTest extends SapphireTest
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
        $this->setUpBeforeClass();
    }

    /**
     *
     */
    public function testWrite_WriteObject_SeedAndObjectWritten()
    {
        //TODO: fix test

        $batchSizes = array(10, 30, 100, 300);

        foreach ($batchSizes as $batchSize) {

            $writer = new BatchedSeedWriter($batchSize);

            $dog = Injector::inst()->get(Dog::class);
            $dog->Name = 'Bob';
            $dog->Breed = 'Cavvy';
            $writer->write($dog, $this->createField());

            $writer->finish();

            $seed = SeedRecord::get()->first();
            $dog = Dog::get()->first();

            $this->assertEquals(1, Dog::get()->Count());
            $c = SeedRecord::get()->Count();
            $this->assertEquals(1, SeedRecord::get()->Count());

            $this->assertEquals('Seeder\Tests\Dog', $seed->SeedClassName);
            $this->assertEquals($dog->ID, $seed->SeedID);

            if($seed) {
                $seed->delete();
            }
            $dog->delete();
            unset($dog);
            unset($writer);
            unset($seed);
            $a = 1;
        }
    }

    /**
     *
     */
    public function testWrite_WriteManyObjects_SeedsAndObjectsWritten()
    {
        //TODO: fix test
        $batchSizes = array(10, 30, 100, 300);

        foreach ($batchSizes as $batchSize) {
            $writer = new BatchedSeedWriter($batchSize);

            for ($i = 0; $i < 100; $i++) {
                $dog = new Dog();
                $dog->Name = 'Bob' . $i;
                $dog->Breed = 'Cavvy' . $i;

                $owner = new Human();
                $owner->Name = 'Jim' . $i;

                $owner->onAfterExistsCallback(function ($owner) use ($dog, $writer) {
                    $dog->OwnerID = $owner->ID;
                    $writer->write($dog, $this->createField());
                });

                $writer->write($owner, $this->createField());
            }

            $writer->finish();

            $dogSeeds = SeedRecord::get()->filter('SeedClassName', 'Dog');
            $ownerSeeds = SeedRecord::get()->filter('SeedClassName', 'Human');
            $dogs = Dog::get();
            $owners = Human::get();

            $this->assertEquals(100, $dogs->Count());
            $this->assertEquals(100, $owners->Count());
            $this->assertEquals(100, $dogSeeds->Count());
            $this->assertEquals(100, $ownerSeeds->Count());


            for ($i = 0; $i < 100; $i++) {
                $dog = $dogs[$i];
                $owner = $owners[$i];
                $ownerSeed = $ownerSeeds[$i];
                $dogSeed = $dogSeeds[$i];

                $this->assertEquals('Seeder\Tests\Dog', $dogSeed->SeedClassName);
                $this->assertEquals($dog->ID, $dogSeed->SeedID);
                $this->assertEquals('Seeder\Tests\Human', $ownerSeed->SeedClassName);
                $this->assertEquals($owner->ID, $ownerSeed->SeedID);
            }

            $writer->delete($dogs);
            $writer->delete($dogSeeds);
            $writer->delete($owners);
            $writer->delete($ownerSeeds);
            $writer->finish();

            $this->assertEquals(0, Dog::get()->Count());
            $this->assertEquals(0, Human::get()->Count());
            $this->assertEquals(0, SeedRecord::get()->Count());
        }
    }

    /**
     *
     */
    public function testWrite_WriteObjectsTwice_SeedsWrittenOnce()
    {
        //TODO: fix test
        $batchSizes = array(10, 30, 100, 300);

        foreach ($batchSizes as $batchSize) {
            $writer = new BatchedSeedWriter($batchSize);

            for ($i = 0; $i < 100; $i++) {
                $dog = new Dog();
                $dog->Name = 'Shark' . $i;
                $dog->Age = $i;
                $dog->Breed = 'Blue Whale';

                $field = $this->createField();
                $writer->write($dog, $field);
                $writer->write($dog, $field);
            }

            $writer->finish();

            $this->assertEquals(100, Dog::get()->Count());
            $this->assertEquals(100, SeedRecord::get()->Count());

            $dogs = Dog::get();
            $seeds = SeedRecord::get();
            $writer->delete($dogs);
            $writer->delete($seeds);
            $writer->finish();
        }
    }

    /**
     *
     */
    public function testWrite_WriteVersionedObjectsNotPublished_ObjectsWrittenToStage()
    {

        $batchSizes = array(10, 30, 100, 300);

        foreach ($batchSizes as $batchSize) {
            $writer = new BatchedSeedWriter($batchSize);

            for ($i = 0; $i < 100; $i++) {
                $page = new SiteTree();
                $page->Title = 'Magical Unicorn Journeys ' . $i;

                $field = $this->createField();
                $field->options['publish'] = false;
                $writer->write($page, $field);
            }

            $writer->finish();

            $currentStage = Versioned::get_stage();
            Versioned::set_stage('Stage');
            $this->assertEquals(100, SiteTree::get()->Count());

            Versioned::set_stage('Live');
            $this->assertEquals(0, SiteTree::get()->Count());

            Versioned::set_stage('Stage');
            $pages = SiteTree::get();
            $seeds = SeedRecord::get();
            $writer->deleteFromStage($pages, 'Stage', 'Live');
            $writer->delete($seeds);
            $writer->finish();

            if ($currentStage != "") {
                Versioned::set_stage($currentStage);
            }
        }
    }

    /**
     *
     */
    public function testWrite_WriteVersionedObjects_ObjectsWrittenToLive()
    {
        //TODO: fix test
        $batchSizes = array(10, 30, 100, 300);

        foreach ($batchSizes as $batchSize) {
            $writer = new BatchedSeedWriter($batchSize);

            for ($i = 0; $i < 100; $i++) {
                $page = new SiteTree();
                $page->Title = 'Magical Unicorn Journeys ' . $i;

                $field = $this->createField();
                $writer->write($page, $field);
            }

            $writer->finish();

            $currentStage = Versioned::get_stage();
            Versioned::set_stage('Stage');
            $this->assertEquals(100, SiteTree::get()->Count());

            Versioned::set_stage('Live');
            $this->assertEquals(100, SiteTree::get()->Count());

            $pages = SiteTree::get();
            $seeds = SeedRecord::get();
            $writer->deleteFromStage($pages, 'Stage', 'Live');
            $writer->delete($seeds);
            $writer->finish();

            $this->assertEquals(0, SiteTree::get()->Count());
            $this->assertEquals(0, SeedRecord::get()->Count());

            if ($currentStage != "") {
                Versioned::set_stage($currentStage);
            }
        }
    }

    /**
     *
     */
    public function testWriteManyMany_WriteManyManyObjects_ObjectsAccessibleFromManyMany()
    {

        $batchSizes = array(10, 30, 100, 300);

        foreach ($batchSizes as $batchSize) {
            $writer = new BatchedSeedWriter($batchSize);

            for ($i = 0; $i < 10; $i++) {
                $owner = new Human();
                $owner->Name = 'Mr bean ' . $i;

                for ($j = 0; $j < 10; $j++) {
                    $dog = new Dog();
                    $dog->Name = 'Walnut ' . $i;

                    $afterExists = new OnAfterExists(function () use ($owner, $dog, $writer) {
                        $writer->writeManyMany($owner, 'Pets', $dog);
                    });
                    $afterExists->addCondition($owner);
                    $afterExists->addCondition($dog);

                    $writer->write($dog, $this->createField());
                }

                $writer->write($owner, $this->createField());
            }

            $writer->finish();

            $owners = Human::get();
            $dogs = Dog::get();
            $this->assertEquals(10, $owners->Count());
            $this->assertEquals(100, $dogs->Count());

            foreach ($owners as $owner) {
                $this->assertEquals(10, $owner->Pets()->Count());
            }

            $writer->delete($owners);
            $writer->delete($dogs);
            $writer->finish();
        }
    }

    /**
     * @return Field
     */
    private function createField()
    {
        $field = new Field();
        $field->key = 'test';
        return $field;
    }

}

<?php

namespace Seeder\Tests;

use Seeder\Helpers\HeuristicParser;
use Seeder\Providers\URLSegmentProvider;
use Seeder\Providers\ValueProvider;
use Seeder\Util\BatchedSeedWriter;
use Seeder\Util\Field;
use Seeder\Util\RecordWriter;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;

/**
 * Class HeuristicTest
 * @package Seeder\Tests
 */
class HeuristicTest extends SapphireTest
{
    /**
     *
     */
    public function testMatch_IsAMatcher_SiteTreeIsASiteTree()
    {
        $parser = new HeuristicParser();
        $heuristics = $parser->parse(array(
            'URLSegment' => array(
                'conditions' => array(
                    'name' => 'URLSegment',
                    'parent' => 'is_a(' . SiteTree::class . ')',
                ),
                'field' => 'URLSegment()',
            )
        ));

        $heuristic = $heuristics[0];

        $field = new Field();
        $field->name = 'Page';
        $field->dataType = SiteTree::class;

        $urlField = new Field();
        $urlField->name = 'URLSegment';
        $urlField->dataType = 'Varchar';
        $urlField->fieldType = Field::FT_FIELD;
        $urlField->parent = $field;

        $field->fields[] = $urlField;

        $this->assertTrue($heuristic->match($urlField));

        $heuristic->apply($urlField, new RecordWriter());

        $this->assertInstanceOf(URLSegmentProvider::class, $urlField->provider);
    }

    /**
     *
     */
    public function testMatch_ManyConditions_MatchesSuccessfully()
    {
        $parser = new HeuristicParser();
        $heuristics = $parser->parse(array(
            'MenuTitle' => array(
                'conditions' => array(
                    'name' => 'MenuTitle',
                    'fieldType' => 'db',
                    'dataType' => 'like(varchar%)',
                    'parent' => 'is_a(' . SiteTree::class . ')',
                ),
                'field' => '{$Title}',
            )
        ));

        $heuristic = $heuristics[0];

        $field = new Field();
        $field->name = 'Magic';
        $field->dataType = SiteTree::class;

        $titleField = new Field();
        $titleField->name = 'MenuTitle';
        $titleField->dataType = 'Varchar';
        $titleField->fieldType = Field::FT_FIELD;
        $titleField->parent = $field;

        $field->fields[] = $titleField;

        $this->assertTrue($heuristic->match($titleField));

        $heuristic->apply($titleField, new RecordWriter());

        $this->assertInstanceOf(ValueProvider::class, $titleField->provider);
    }
}

<?php

namespace Seeder\Tests;

use Seeder\Heuristics\IsAMatcher;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;

/**
 * Class IsAMatcherTest
 * @package Seeder\Tests
 */
class IsAMatcherTest extends SapphireTest
{
    /**
     *
     */
    public function testMatch_MatchClasses_SubClassesMatch()
    {
        $matcher = new IsAMatcher(DataObject::class);

        $this->assertFalse($matcher->match('Object'));
        $this->assertTrue($matcher->match(DataObject::class));
        $this->assertTrue($matcher->match(SiteTree::class));
    }
}

<?php

namespace Seeder\Tests;

use Seeder\FakerProvider;
use Seeder\Util\Field;
use Seeder\Util\SeederState;
use SilverStripe\Dev\SapphireTest

/**
 * Class FakerProviderTest
 * @package Seeder\Tests
 */
class FakerProviderTest extends SapphireTest
{
    /**
     *
     */
    public function testGenerateField_Sentences_JoinsWithSpaces()
    {
        $provider = new FakerProvider();

        $field = new Field();
        $field->fieldType = Field::FT_FIELD;
        $field->dataType = 'Varchar(255)';
        $field->options = array(
            'type' => 'sentences',
            'arguments' => array(6),
        );

        $values = $provider->generate($field, new SeederState());

        $value = $values[0];
        $this->assertFalse(strpos($value, "\n"));
    }

    /**
     *
     */
    public function testGenerateField_Paragraphs_JoinsWithNewLines()
    {
        $provider = new FakerProvider();

        $field = new Field();
        $field->fieldType = Field::FT_FIELD;
        $field->dataType = 'Text';
        $field->options = array(
            'type' => 'paragraphs',
            'arguments' => array(3),
        );

        $values = $provider->generate($field, new SeederState());

        $value = $values[0];
        $this->assertTrue(strpos($value, "\n") !== false);
    }
}

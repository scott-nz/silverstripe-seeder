<?php

namespace Seeder\Providers;

use Exception;
use Faker\Factory;

/**
 * Class FakerProvider
 * @package Seeder
 */
class FakerProvider extends Provider
{
    /**
     * @var
     */
    private $faker;

    /**
     * @var string
     */
    public static $shorthand = 'Faker';

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->faker = Factory::create();
    }

    /**
     * @param $argumentString
     * @return array
     */
    public static function parseOptions($argumentString)
    {
        $options = array();
        $arguments = array_map(function ($arg) {
            return trim($arg);
        }, explode(',', $argumentString));

        $options['type'] = array_shift($arguments);
        $options['arguments'] = $arguments;

        return $options;
    }

    /**
     * @param $field
     * @param $state
     * @return mixed|string
     * @throws Exception
     */
    protected function generateField($field, $state)
    {
        if (empty($field->options['type'])) {
            var_dump($field);
            throw new Exception('faker provider requires a \'type\'');
        }

        $type = $field->options['type'];
        // todo are there any faker methods without an argument?
        if (!empty($field->options['arguments'])) {
            $value = call_user_func_array(array($this->faker, $type), $field->options['arguments']);
        } else {
            $value = $this->faker->$type;
        }

        if (is_array($value)) {
            $join = ' ';
            if (stripos($field->dataType, 'text') !== false  || stripos($type, 'paragraph') !== false) {
                $join = "\n";
            }
            $value = implode($join, $value);
        }

        return $value;
    }
}

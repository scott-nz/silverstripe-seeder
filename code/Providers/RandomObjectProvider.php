<?php

namespace Seeder\Providers;

use Exception;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;

/**
 * Class RandomObjectProvider
 * @package Seeder
 */
class RandomObjectProvider extends Provider
{
    /**
     * @var string
     */
    public static $shorthand = 'Random';

    /**
     * @param $field
     * @param $state
     * @throws Exception
     * @returns null
     */
    protected function generateField($field, $state)
    {
        throw new Exception('random object provider does not support generating db fields');
    }

    /**
     * @param $field
     * @param $state
     * @return mixed
     * @throws Exception
     */
    protected function generateOne($field, $state)
    {
        $args = $field->options['arguments'];

        $className = $field->dataType;
        if (count($args) && !empty($args[0])) {
            $className = $args[0];
        }

        $object = $className::get()->sort('RAND()')->first();

        if (!$object) {
            Injector::inst()->get(LoggerInterface::class)->warning("random for {$className} not found");
        }

        return $object;
    }

    /**
     * @param $field
     * @param $state
     * @return mixed
     * @throws Exception
     */
    protected function generateMany($field, $state)
    {
        $args = $field->options['arguments'];

        $className = $field->dataType;
        if (count($args) && !empty($args[0])) {
            $className = $args[0];
        }

        $count = 1;
        if (count($args) > 1 && !empty($args[1])) {
            $count = intval($args[1]);
        }

        return $className::get()->sort('RAND()')->limit($count);
    }
}

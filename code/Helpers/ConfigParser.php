<?php

namespace Seeder\Helpers;

use Exception;
use Seeder\Util\Field;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Injector;
use Psr\Log\LoggerInterface;

/**
 * Class ConfigParser
 * @package Seeder\Helpers
 */
class ConfigParser
{
    /**
     * @var \Config_ForClass
     */
    private $config;

    /**
     * @var null
     */
    private $writer;

    /**
     * @param null $writer
     */
    public function __construct($writer = null)
    {
        $config = \Config::inst();
        $this->config = $config->forClass('Seeder');
        $this->writer = $writer;
    }

    /**
     * @param $config
     * @return Field
     */
    public function objectConfig2Field($config)
    {
        if (!isset($config['key'])) {
            $config['key'] = $config['class'];
        }

        $field = $this->createObjectField($config['class'], $config, $config['key']);
        if (isset($field->options['count']) && is_int($field->options['count'])) {
            $field->count = $field->options['count'];
        }
        $field->fieldType = Field::FT_ROOT;

        $this->setTotalCounts($field);

        return $field;
    }

    /**
     * @param $className
     * @param $options
     * @param null $key
     * @return Field
     * @throws Exception
     */
    private function createObjectField($className, $options, $key = null)
    {
        $field = new Field();
        $field->dataType = $className;

        if (!is_array($options)) {
            $options = $this->parseProviderOptions($options);
        }

        $field->key = $key ?: $className;
        $field->options = $options;

        $object = singleton($className);

        $ancestry = array();
        foreach ($object->getClassAncestry() as $className) {
            $classObject = singleton($className);
            $ancestry[] = $classObject;
        }

        $field->ancestry = $ancestry;

        $ignoreFields = $this->getIgnoreFields($field, $options);

        $ignoreLookup = array();
        foreach ($ignoreFields as $ignoreField) {
            $ignoreLookup[$ignoreField] = $ignoreField;
        }

        $properties = isset($options['fields']) ? $options['fields'] : array();

        $fields = array();
        $hasOneFields = array();
        $hasManyFields = array();
        $manyManyFields = array();
        foreach ($field->ancestry as $classObject) {
            foreach (DataObject::custom_database_fields($classObject->ClassName) as $fieldName => $fieldType) {
                $ignored = isset($ignoreLookup[$fieldName]) && !isset($properties[$fieldName]);
                if ($fieldType !== 'ForeignKey' && !$ignored) {
                    $fields[$fieldName] = $fieldType;
                }
            }

            foreach ($classObject->has_one() as $fieldName => $className) {
                $ignored = isset($ignoreLookup[$fieldName]) && !isset($properties[$fieldName]);
                if (!$ignored
                    && isset($options['fields'])
                    && array_key_exists($fieldName, $options['fields'])
                ) {
                    $hasOneFields[$fieldName] = $className;
                }
            }

            // limit to fields that specify use
            foreach ($classObject->has_many() as $fieldName => $className) {
                $ignored = isset($ignoreLookup[$fieldName]) && !isset($properties[$fieldName]);
                if (!$ignored
                    && isset($options['fields'])
                    && array_key_exists($fieldName, $options['fields'])
                ) {
                    $hasManyFields[$fieldName] = $className;
                }
            }

            // limit to fields that specify use
            foreach ($classObject->many_many() as $fieldName => $className) {
                $ignored = isset($ignoreLookup[$fieldName]) && !isset($properties[$fieldName]);
                if (!$ignored
                    && isset($options['fields'])
                    && array_key_exists($fieldName, $options['fields'])
                ) {
                    $manyManyFields[$fieldName] = $className;
                }
            }
        }

        $properties = array_merge($this->getDefaultProperties($field), $properties);

        foreach ($fields as $fieldName => $dataType) {
            $fieldOptions = isset($properties[$fieldName]) ? $properties[$fieldName] : array();
            $fieldObject = $this->createField($dataType, $fieldOptions);
            $fieldObject->fieldName = $fieldName;
            $fieldObject->name = $fieldName;
            $fieldObject->parent = $field;
            $field->fields[] = $fieldObject;
        }

        foreach ($hasOneFields as $fieldName => $className) {
            $fieldOptions = isset($properties[$fieldName]) ? $properties[$fieldName] : array();
            $fieldObject = $this->createObjectField($className, $fieldOptions, $field->key);
            $fieldObject->fieldType = Field::FT_HAS_ONE;
            $fieldObject->name = $fieldName;
            $fieldObject->fieldName = $fieldName . 'ID';
            $fieldObject->methodName = $fieldName;
            $fieldObject->parent = $field;
            $fieldObject->count = 1;
            $field->hasOne[] = $fieldObject;
        }

        foreach ($hasManyFields as $fieldName => $className) {
            $fieldOptions = isset($properties[$fieldName]) ? $properties[$fieldName] : array();
            $fieldObject = $this->createObjectField($className, $fieldOptions, $field->key);
            $fieldObject->fieldType = Field::FT_HAS_MANY;
            $fieldObject->name = $fieldName;
            $fieldObject->methodName = $fieldName;
            $fieldObject->parent = $field;
            if (isset($fieldOptions['count']) && is_int($fieldOptions['count'])) {
                $fieldObject->count = $fieldOptions['count'];
            }
            $field->hasMany[] = $fieldObject;
        }

        foreach ($manyManyFields as $fieldName => $className) {
            $fieldOptions = isset($properties[$fieldName]) ? $properties[$fieldName] : array();
            $fieldObject = $this->createObjectField($className, $fieldOptions, $field->key);
            $fieldObject->fieldType = Field::FT_MANY_MANY;
            $fieldObject->name = $fieldName;
            $fieldObject->methodName = $fieldName;
            $fieldObject->parent = $field;
            if (isset($fieldOptions['count']) && is_int($fieldOptions['count'])) {
                $fieldObject->count = $fieldOptions['count'];
            }
            $field->manyMany[] = $fieldObject;
        }

        $this->setProvider($field, $options);

        return $field;
    }

    /**
     * @param $optionString
     * @return mixed
     * @throws Exception
     */
    public function parseProviderOptions($optionString)
    {
        if (preg_match('/^([a-zA-Z-_0-9]+)\(([^)]+)?\)$/', $optionString, $matches)) {
            $shorthand = strtolower($matches[1]);
            $arguments = isset($matches[2]) ? $matches[2] : '';

            foreach ($this->config->providers as $provider) {
                if (!class_exists($provider) && !class_exists(str_replace('\\', '\\\\', $provider))) {
                    Injector::inst()->get(LoggerInterface::class)
                        ->warning("provider class '{$provider}' cannot be found");
                } elseif (isset($provider::$shorthand)) {
                    if (strtolower($provider::$shorthand) === $shorthand) {
                        $options = $provider::parseOptions($arguments);
                        $options['provider'] = $provider;
                        return $options;
                    }
                }
            }
            throw new Exception("shorthand '$shorthand' does not match any registered providers");
        }

        $provider = $this->config->empty_shorthand_provider;
        $options = $provider::parseOptions($optionString);
        $options['provider'] = $provider;
        return $options;
    }

    /**
     * @param $field
     * @param $options
     * @return array
     */
    private function getIgnoreFields($field, $options)
    {
        $defaultIgnores = $this->config->default_ignores;

        $ignoreFields = array();
        foreach ($field->ancestry as $object) {
            if (isset($defaultIgnores[$object->ClassName])) {
                $ignoreFields = array_merge($ignoreFields, $defaultIgnores[$object->ClassName]);
            }
        }

        if (isset($options['ignore']) && is_array($options['ignore'])) {
            $ignoreFields = array_merge($ignoreFields, $options['ignore']);
        }

        return array_unique($ignoreFields);
    }

    /**
     * @param $field
     * @return array
     */
    private function getDefaultProperties($field)
    {
        $properties = array();

        $defaultValues = $this->config->default_values;
        foreach ($field->ancestry as $object) {
            if (isset($defaultValues[$object->ClassName])) {
                $properties = array_merge($properties, $defaultValues[$object->ClassName]);
            }
        }

        return $properties;
    }

    /**
     * @param $dataType
     * @param $options
     * @return Field
     * @throws Exception
     */
    private function createField($dataType, $options)
    {
        $field = new Field();
        $field->fieldType = Field::FT_FIELD;
        $field->dataType = $dataType;

        if (!is_array($options)) {
            $options = $this->parseProviderOptions($options);
        }

        $field->options = $options;
        $this->setProvider($field, $options);
        return $field;
    }

    /**
     * @param $field
     * @param $options
     */
    private function setProvider($field, $options)
    {
        if (!empty($options['provider'])) {
            $providerClassName = $options['provider'];
            $field->provider = new $providerClassName();
            $field->explicit = true;
        } else {
            $field->provider = $this->getDefaultProvider($field);
            $field->explicit = false;
        }

        $field->provider->setWriter($this->writer);
    }

    /**
     * @param $field
     * @return mixed
     */
    private function getDefaultProvider($field)
    {
        $providerClassName = $this->config->default_provider;

        $defaultProviders = $this->config->default_providers;
        foreach ($field->ancestry as $object) {
            if (isset($defaultProviders[$object->ClassName])) {
                $providerClassName = $defaultProviders[$object->ClassName];
            }
        }

        // check data type since this will let db fields be overwritten
        if (isset($defaultProviders[$field->dataType])) {
            $providerClassName = $defaultProviders[$field->dataType];
        }

        $provider = new $providerClassName();
        return $provider;
    }

    /**
     * @param $field
     * @param int $mul
     */
    private function setTotalCounts($field, $mul = 1)
    {
        $field->totalCount = $field->count * $mul;

        foreach ($field->fields as $db) {
            $this->setTotalCounts($db, $field->totalCount);
        }

        foreach ($field->hasOne as $hasOneField) {
            $this->setTotalCounts($hasOneField, $field->totalCount);
        }

        foreach ($field->hasMany as $hasManyField) {
            $this->setTotalCounts($hasManyField, $field->totalCount);
        }

        foreach ($field->manyMany as $manyManyField) {
            $this->setTotalCounts($manyManyField, $field->totalCount);
        }
    }
}

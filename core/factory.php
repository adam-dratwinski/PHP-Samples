<?php

    namespace MP\Factory;

    class Base {
        private static $sequence_array = array();
        private static $instance_cache = array();
        private static $objects_cache = array();
        private $fields = array();
        private $settings = array();
        private $factory_name;
        private $record_name; 

        static public function create($factory_name, $record_name)
        {
            return self::getInstance($factory_name, $record_name)->getObject(true);
        }

        static public function build($factory_name, $record_name)
        {
            return self::getInstance($factory_name, $record_name)->getObject(false);
        }

        static private function getInstance($factory_name, $record_name)
        {
            if(!isset(static::$instance_cache[$factory_name][$record_name]))
            {
                self::$instance_cache[$factory_name][$record_name] = new Base($factory_name, $record_name);
            }

            return self::$instance_cache[$factory_name][$record_name];
        }

        private function __construct($factory_name, $record_name)
        {
            $this->factory_name = $factory_name;
            $this->record_name = $record_name;
            $this->settings = $this->parseSettings();
        }

        private function parseSettings()
        {
            $array = array();
            $settings = \MP\YAML\Parser::YAMLLoad(APPLICATION_PATH.'/factories/'.$this->factory_name.'.yml');

            $array['class_name'] = $settings['class_name'];

            if (isset($settings['factories'][$this->record_name]))
            {
                $array['fields'] = $settings['factories'][$this->record_name];
            }
            else
            {
                throw new \Exception("Nie istnieje taka nazwa fabryki, podana nazwa: {$this->record_name}, dost¿pne nazwy to ".join(array_keys($settings['factories']), ", "));
            }

            return $array;
        }

        private function parseFields()
        {
            foreach($this->settings['fields'] as $field_key => $field_val)
            {
                if (is_array($field_val))
                {
                    $factory_name = array_keys($field_val);
                    $factory_name = $factory_name[0];
                    $temp_factory = $this->getCachedObject($factory_name, $field_val[$factory_name]);
                    $parsed_fields[$field_key] = $temp_factory->id;
                }
                else if(preg_match('/#\{n\}/', $field_val))
                {
                    $parsed_fields[$field_key] = preg_replace('/(#\{n\})/', self::getSequenceForField($this->factory_name, $this->record_name, $field_key), $field_val);
                }
                else
                {
                    $parsed_fields[$field_key] = $field_val;
                }
            }

            return $parsed_fields;
        }

        private function getObject($create_object)
        {
            $this->fields = $this->parseFields();
            $class_name = "\App\Models\\".$this->settings['class_name'];
            $new_object = new $class_name($this->fields);
            if($create_object)
            {
                if ( ! $new_object->save())
                {
                    throw new \Exception('Nie udalo sie zapisac fabryki '.$this->factory_name.', lista pól zawierajaca bledy walidacyjne: '.join(array_keys($new_object->getErrors()), ', '));
                }
            }
            return $new_object;
        }

        private function getCachedObject($factory_name, $record_name)
        {
            if( ! isset(self::$objects_cache[$this->factory_name][$this->record_name][$factory_name][$record_name]))
            {
                self::$objects_cache[$this->factory_name][$this->record_name][$factory_name][$record_name] = self::create($factory_name, $record_name);
            }

            return self::$objects_cache[$this->factory_name][$this->record_name][$factory_name][$record_name];
        }

        static private function getSequenceForField($factory_name, $record_name, $field)
        {
            if (!isset(self::$sequence_array[$factory_name][$record_name][$field]))
            {
                self::$sequence_array[$factory_name][$record_name][$field] = 0;
            }

            return ++self::$sequence_array[$factory_name][$record_name][$field];
        }

        static public function clearCache()
        {
            self::$sequence_array = array();
            self::$instance_cache = array();
            self::$objects_cache = array();
        }
    }


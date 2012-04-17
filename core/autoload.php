<?php

/**
 * An autoloader class which loads proper files from the proper directories, eg
 *
 * new User() will look in 
 * APPLICATION_PATH/models 
 * FRAMEWORK_PATH/models
 * APPLICATION_PATH/libraries
 * FRAMEWORK_PATH/libraries
 *
 * It will also look for the controllers exception and task class names in proper directories, same for the core classes.
 */

require_once(\FRAMEWORK_PATH.'/exceptions/base_exception.php');
require_once(\FRAMEWORK_PATH.'/exceptions/autoload_exception.php');

function __autoload($class_name)
{
    $folders = array ();
    $file_name = '';
    $session = \SessionHandler::createAccessor(false);
    if (preg_match('/^Core\\\\(.+)/', $class_name, $matches))
    {
        $folders[] = FRAMEWORK_PATH.'/core';
        $file_name = $matches[1];
    }
    else if (preg_match('/^(.+)(Controller|Exception|Task)$/', $class_name, $matches)) {
        $type_folder = \String\pluralize(\String\uncamelize($matches[2]));
        $folders[] = FRAMEWORK_PATH.'/'.$type_folder;
        if (isset($session['modules'])) {
            foreach ($session['modules'] as $module) {
                $folders[] = MODULES_PATH.'/'.$module.'/'.$type_folder;
            }
        }
        $folders[] = APPLICATION_PATH.'/'.$type_folder;
        $file_name = $matches[1].'_'.\String\uncamelize($matches[2]);
    } else {
        if (isset($session['modules'])) {
            foreach ($session['modules'] as $module) {
                $folders[] = MODULES_PATH.'/'.$module.'/models';
            }
        }
        $folders[] = APPLICATION_PATH.'/models';
        $folders[] = FRAMEWORK_PATH.'/models';
        $folders[] = APPLICATION_PATH.'/libraries';
        $folders[] = FRAMEWORK_PATH.'/libraries';
        $file_name = $class_name;
    }
    
    $file_name_array = explode("\\", $file_name);
    $temp_array = array ();

    foreach ($file_name_array as $file_name)
    {
        $temp_array[] = String\uncamelize($file_name);
    }

    $file_name = join('/', $temp_array).'.php';

    foreach ($folders as $folder)
    {
        if (file_exists($folder.'/'.$file_name))
        {
            $file_location = $folder.'/'.$file_name;
            break;
        }
    }

    if ( ! isset($file_location) || ! $file_location)
    {
        throw new \AutoloadException('Nie znaleziono pliku: "'.$file_name.'" w folderach: "'.join(', ', $folders).'" dla wywoÃâania klasy: "'.$class_name.'"');
    }
    else
    {
        require_once($file_location);
    }

    if ( ! class_exists($class_name) && !interface_exists($class_name)  )
    {
        throw new \AutoloadException('Znaleziono plik: "'.$file_location.'", ale klasa: "'.$class_name.'" nie istnieje');
    }


    if (\method_exists($class_name, '__onLoad'))
    {
        $class_name::__onLoad();
    }
}

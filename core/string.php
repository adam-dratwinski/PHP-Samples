<?php

/**
 * Couple of helpers function which helps to deal with strings
 */

namespace String;

$plural = array(
    '/(quiz)$/i'               => "$1zes",
    '/^(ox)$/i'                => "$1en",
    '/([m|l])ouse$/i'          => "$1ice",
    '/(matr|vert|ind)ix|ex$/i' => "$1ices",
    '/(x|ch|ss|sh)$/i'         => "$1es",
    '/([^aeiouy]|qu)y$/i'      => "$1ies",
    '/([^aeiouy]|qu)ies$/i'    => "$1y",
    '/(hive)$/i'               => "$1s",
    '/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
    '/sis$/i'                  => "ses",
    '/([ti])um$/i'             => "$1a",
    '/(buffal|tomat|potat)o$/i'=> "$1oes",
    '/(bu)s$/i'                => "$1ses",
    '/(alias|status)$/i'       => "$1es",
    '/(octop)us$/i'            => "$1i",
    '/(ax|test)is$/i'          => "$1es",
    '/us$/i'                   => "$1es",
    '/s$/i'                    => "s",
    '/$/'                      => "s"
);

$irregular = array(
    array( 'move',   'moves'    ),
    array( 'sex',    'sexes'    ),
    array( 'child',  'children' ),
    array( 'man',    'men'      ),
    array( 'person', 'people'   )
);

$uncountable = array(
    'sheep',
    'fish',
    'series',
    'species',
    'money',
    'rice',
    'information',
    'equipment'
);

static $singular = array(
    '/(n)ews$/i'                => "$1ews",
    '/([ti])a$/i'               => "$1um",
    '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i'  => "$1$2sis",
    '/(^analy)ses$/i'           => "$1sis",
    '/([^f])ves$/i'             => "$1fe",
    '/(hive)s$/i'               => "$1",
    '/(tive)s$/i'               => "$1",
    '/([lr])ves$/i'             => "$1f",
    '/([^aeiouy]|qu)ies$/i'     => "$1y",
    '/(s)eries$/i'              => "$1eries",
    '/(m)ovies$/i'              => "$1ovie",
    '/(x|ch|ss|sh)es$/i'        => "$1",
    '/([m|l])ice$/i'            => "$1ouse",
    '/(bus)es$/i'               => "$1",
    '/(o)es$/i'                 => "$1",
    '/(shoe)s$/i'               => "$1",
    '/(cris|ax|test)es$/i'      => "$1is",
    '/(octop|vir)i$/i'          => "$1us",
    '/(alias|status)es$/i'      => "$1",
    '/^(ox)en$/i'               => "$1",
    '/(vert|ind)ices$/i'        => "$1ex",
    '/(matr)ices$/i'            => "$1ix",
    '/(quiz)zes$/i'             => "$1",
    '/(us)es$/i'                => "$1",
    '/s$/i'                     => ""
);

function camelize ($string)
{
    $array = explode('_', $string);
    $return_array = array ();

    foreach ($array as $key)
    {
        $return_array[] = ucfirst($key);
    }

    return join ('', $return_array);
}

function uncamelize ($string)
{
    $array = array ();
    $buffor = array ();

    for ($i = 0; $i < strlen($string); $i++)
    {
        $letter = $string[$i];
        if ( ! ($letter === strtolower($letter)))
        {
            if ( ! empty ($buffor))
            {
                $array[] = join('',$buffor);
                $buffor = array();
            }
        }
        $buffor[] = $letter;
    }

    $array[] = join('', $buffor);

    return strtolower (join ('_', $array));
}

function pluralize ($string)
{
    global $uncountable, $irregular, $plural;
    if ( in_array( strtolower( $string ), $uncountable ) )
        return $string;

    foreach ( $irregular as $noun )
    {
        if ( strtolower( $string ) == $noun[0] )
        return $noun[1];
    }

    foreach ( $plural as $pattern => $result )
    {
        if ( preg_match( $pattern, $string ) )
            return preg_replace( $pattern, $result, $string );
    }

    return $string;
}

function singularize ($string)
{
    global $uncountable, $irregular, $singular;
    if ( in_array( strtolower( $string ), $uncountable ) )
        return $string;

    foreach ( $irregular as $noun )
    {
        if ( strtolower( $string ) == $noun[1] )
        return $noun[0];
    }

    foreach ( $singular as $pattern => $result )
    {
        if ( preg_match( $pattern, $string ) )
            return preg_replace( $pattern, $result, $string );
    }

    return $string;
}

function to_percent($number, $total){
    if($total == 0){
        return '-';
    }
    return \round(((float)$number / (float)$total)*100).'%';
}

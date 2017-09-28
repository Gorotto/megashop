<?php

/**
    Конфигурация сайта
*/

class Config
{
    static public $site_title;
    static public $tmpPath;
    
    static public $configCache = array();
    
    static function init()
    {
        self::$site_title = 'Новый сайт';
        
#        ini_set( 'session.save_path', "{$_SERVER['DOCUMENT_ROOT']}/../wwwtmp" );
        self::$tmpPath = "{$_SERVER['DOCUMENT_ROOT']}/../wwwtmp";
    }
    
    static function get( $name ) {
        if( ! array_key_exists( $name, self::$configCache ) ) {
            if( ! $setting = BuilderSettings( array( 'name' => $name ) )->first() ) {
                throw new Exception( "Unknown Builder setting '{$name}'." );
            }
            self::$configCache[ $name ] = $setting->value;
        }
        return self::$configCache[ $name ];
    }
}

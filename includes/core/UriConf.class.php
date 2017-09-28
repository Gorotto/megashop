<?php
/**
    Конфигуратор, упрощающий разбор URI страниц в приложениях
    
    Использование:
    $c = new UriConf(
        array( '~^/(?P<sectionName>\w+)?(?:/page\d+)?/?$~', 'section', array( 'foo' => 'bar' ) ),
        array( '~^/(?P<sectionName>\w+)?/(?P<id>\d+)/?$~', 'page' )
    );
    
    var_dump( $c->resolve( '/news/page5/' ) ); // Возвращает section, в in добавляет переменные sectionName => news и foo => bar
    var_dump( Meta::vars() );
    
    var_dump( $c->resolve( '/news/12' ) ); // Возвращает page, в in добавляет переменные sectionName => news и id => 12
    var_dump( Meta::vars() );
*/

class UriConfAction {
    protected $pattern;
    protected $name;
    protected $matches;
    protected $extra;
    
    function __construct( $args ) {
        if( ! ( is_array( $args ) && count( $args ) >= 2 && count( $args ) <= 3 ) ) {
            throw new Exception( "Некорректное определение действия UriConf: " . print_r( $args, true ) );
        }
        $this->pattern = array_shift( $args );
        $this->name = array_shift( $args );
        $this->extra = $args ? array_shift( $args ) : array();
    }
    
    public function test( $uri ) {
        $this->matches = array();
        if( preg_match( $this->pattern, $uri, $matches ) ) {
            foreach( $matches as $matchedKey => $matchedValue ) {
                if( is_string( $matchedKey ) ) {
                    $this->matches{ $matchedKey } = $matchedValue;
                }
            }
            return true;
        }
        return false;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getMatches() {
        return $this->matches;
    }
    
    public function getExtra() {
        return $this->extra;
    }
}

class UriConfVars {
    function __construct($properties) {
        foreach ($properties as $name => $value) {
            $this->$name = $value;
        }
    }

    function __get($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            return NULL;
        }
    }
}

class UriConf {
    protected $actions = array();
    protected $vars;
    protected $uri;
    public $updateMetaVars = false; // Сделать true, если нужно автоматически обновлять переменные Meta::vars
    
    /**
        Поддерживает неограниченное количество агрументов, каждый — массив вида
        array( '/uriRegExp/', 'action', array( 'extraKey' => 'extraVal' ) )
        Так же все аргументы можно передать массивом массивов :D
    */
    //TODO: передалать все единообразно (массив массивов) и убрать изменение updateMetaVars в отдельный параметр конструктора
    public function __construct( $uri1 ) {
        $actions = func_get_args();

        // плохой хак, смотри TODO выше
        $args_in_array =  is_array($uri1)
                       && array_key_exists(0, $uri1)
                       && is_array($uri1[0]);
        if ($args_in_array) { 
            $actions = $uri1;
        }

        foreach($actions as $actionDefinition) {
            if( is_bool( $actionDefinition ) ) {
                $this->updateMetaVars = true;
            } else {
                $this->actions[] = new UriConfAction( $actionDefinition );
            }
        }
    }

    /**
        Разбор URI
        При отсутствии URI берется Meta::getUriPath().
        Возвращает действие или null, если ничего не найдено
    */    
    public function resolve( $uri = null ) {
        if( is_null( $uri ) ) {
            $uri = Meta::getUriPath();
        }
        
        $this->uri = $uri;
        
        foreach( $this->actions as $action ) {
            if( $action->test( $uri ) ) {
                $vars = array_merge( $action->getMatches(), $action->getExtra() );
                $this->vars = new UriConfVars($vars);

                if( $this->updateMetaVars ) {
                    $in = & Meta::vars();
                    foreach( $vars as $k => $v ) {
                        $in{ $k } = $v;
                        $vars{ $k } = $v;
                    }
                }

                return $action->getName();
            }
        }
        return null;
    }
    
    public function __get( $name ) {
        if( in_array( $name, array( 'vars', 'uri' ) ) ) {
            return $this->$name;
        }
    }
}

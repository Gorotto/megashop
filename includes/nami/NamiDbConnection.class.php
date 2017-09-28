<?
/**
    Абстрактное соединение с БД
*/
abstract class NamiDbConnection
{
    // Переменные подключения к БД
    protected $host;
    protected $port;
    protected $name;
    protected $user;
    protected $password;
    protected $charset;
    
    protected $log = array();   // Тут будут лежать логи запросов

    // Простенький конструктор, копирующий параметры подключения из массива, переданного ему
    function __construct( array $options )
    {
        foreach( array( 'host', 'port', 'name', 'user', 'password', 'charset' ) as $k )
        {
            $this->$k = array_key_exists( $k, $options ) ? $options[ $k ] : NULL;
        }
        
        register_shutdown_function(array($this, 'write_log'));
    }

    // Подключение/ототключение от БД
    abstract function open();
    abstract function close();

    // Закавычивание строк, обычное и для имен объектов
    abstract function escape( $string );
    abstract function escapeName( $string );

    // Получение хэндлера соединения (может требоваться курсору или кому-то еще
    abstract function getHandler();

    // Получение курсора соединения с БД
    abstract function getCursor();

    // Получение значения автоинкрементного поля, установенное базой
    abstract function getLastInsertId( NamiDbCursor $cursor = NULL, $table='', $pkname='' );

    // Сделаем псевдополя handler и cursor
    function __get( $name )
    {
        switch( $name )
        {
            case 'handler': return $this->getHandler();
            case 'cursor': return $this->getCursor();
        }
    }
    
    /**
    *   Запись в лог запросов
    */
    function log($query, $duration) {
        $this->log[] = compact('query', 'duration');
    }
    
    function write_log() {
        if (count($this->log)) {
            /*  Посчитаем среднее время выполнения запроса, и все запросы,
                которые в 2 раза больше среднего выполнялись, подсветим */
            $total_duration = 0;
            foreach ($this->log as $i) {
                $total_duration += $i['duration'];
            }
            $average_duration = $total_duration / count($this->log);

            ?>
            <style>
            .nami-query-log {border-collapse:collapse;}
            .nami-query-log, .nami-query-log tr, .nami-query-log th, .nami-query-log td {background:#fff;color:#222;}
            .nami-query-log td, .nami-query-log th {border:1px solid #555; padding:6px 8px;}
            .nami-query-log .slow {color:red; font-weight:bold;}
            .nami-query-log th {white-space:nowrap;}
            </style>
            <table class="nami-query-log">
            <tr><th colspan="2">Nami query log</th></tr><tr><th>elapsed, sec</th><th>query</th></tr>
            <?
            foreach ($this->log as $i) {
                printf('<tr><td class="%s">%.6f</td><td>%s</td></tr>',
                    $i['duration'] / $average_duration > 2 ? 'slow' : '',
                    $i['duration'],
                    $i['query']
                ); 
            }
            ?>
            <tr><td><b><?= printf('%.6f', $total_duration) ?></b></td><td><?= count($this->log) ?> queries</td></tr>
            </table>
            <?
        }
    }
}


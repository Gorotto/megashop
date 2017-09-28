<?
class GeoIpException extends Exception {}

class GeoIp extends GeoIpExtend {
    /**
     * свойства с параметрами для кук
     */
    public 
        $cookie__city = 'delta__city',
        $cookie__setcity = 'delta__setcity',
        $cookie__show_confirm = 'delta__show_confirm';

    /**
     * хранение значения, как был определен город, автоматически или пользователем
     * @var string
     */
    private $_setcity_type = '';

    /**
     * определять ли город
     * @var boolean
     */
    private $_detect_city = true;

    /**
     * уведомление об успешности или неудаче определения города
     * @var string
     */
    private $_message = '';

    /**
     * город, который уже определился. Результат выборки из бд
     * @var array
     */
    private $_current_city = false;

    /**
     * ip юзера, который к нам пришел
     * @var string
     */
    private $_user_ip = '';

    /**
     * queryset, в котором содержится список городов (это может быть категория филиалов, к каждой из которых привязывается город из geoip)
     * Задается в конструкторе
     */
    public $_cities_qs = '';

    /**
     * хранение id поределившегося города из geoip
     * @var integer
     */
    public $_gi_city_id = 0;

    /**
     * конструктор, чтобы при вызовах много раз не порождать запросы, принимает агрумент $detect,
     * по которому определяет, нужно ли определять город
     * @param boolean $detect [description]
     */
    function __construct($detect = null) {
        if (is_null($detect)) {
            $this->_detect_city;
            if ($this->check_city_cookie()) {
                $detect = false;
            } else {
                $detect = true;
            }
        }

        $this->_detect_city = $detect;

        $this->_cities_qs = ContactItems();


        // вроде как хорошо, что город хранится в куке и что при смене IP он не изменяется
        // именнто так сейчас и происходит
        $this->set_user_ip();
        // $this->set_user_ip('95.167.111.170'); // кемерово
        // $this->set_user_ip('87.103.133.0'); // иркутск
        // $this->set_user_ip('91.189.162.28'); // братск
        // $this->set_user_ip('217.28.73.98'); // саяногорск
        $ip = $this->get_user_ip();
        $long_ip = $this->long_ip($ip);
        $this->_gi_city_id = $this->get_city_id($long_ip);


        if (!$this->be_detect_city()) {
            return false;
        }

        if ($this->_gi_city_id) {
            $this->set_current_city($this->get_city($this->_gi_city_id));
            $this->set_city_cookie($this->_gi_city_id);

        } elseif ($default_city = $this->default_city()) { // если не удалось определить город, то пропишем город по умолчанию
            $this->set_city_cookie($default_city->geo_city);
        }
    }


    /**
     * запись данных о городе, который автоопределился
     * @param  integer $id - id филиала
     * @param  integer $geo_city_id - id гео-города, который привязан к текущему филиалу
     */
    public function write_city_cookie($id = NULL, $geo_city_id = NULL) {
        if (is_null($id) || is_null($geo_city_id)) {
            throw new GeoIpException(__method__ . ' need all argument');
        }

        $city_data = json_encode(
            array(
                    'city_id' => $id,
                    'geo_ip_id' => $geo_city_id
                    )
            );

        $cookie_ttl = time() + (3600 * 24 * 30); // месяц

        setcookie($this->cookie__city, urlencode($city_data), $cookie_ttl, "/", '.' . $_SERVER['HTTP_HOST']);
    }

    /**
     * Удаление куки с городом, который был определен или выбран
     * @return void
     */
    function remove_city_cookie() {
        setcookie ($this->cookie__city, '', time() - 3600 * 24 * 30 * 70);
    }

    /**
     * получение кук с данными о городе
     * @return string
     */
    public function get_cookies() {
        $cookie = isset($_COOKIE[$this->cookie__city]) ? $_COOKIE[$this->cookie__city] : false;
        return $cookie;
    }

    /**
     * установка данных с городом в куку
     * @param integer $geo_city_id идентификатор города geoip, который привязан к какому-то городу из филиалов
     */
    public function set_city_cookie($geo_city_id) {
        if (is_null($geo_city_id)) {
            throw new Exception('GeoIp cookie should have city id');
        }

        $writen = false;
        $city = $this->_cities_qs
                ->get(array('enabled' => true, 'geo_city' => $geo_city_id));
        if ($city) {
            $this->write_city_cookie($city->id, $city->geo_city);
        }

        return $writen;
    }

    /**
     * проверка валидности куки
     * @return boolean
     */
    public function check_city_cookie() {
        $cookie = isset($_COOKIE[$this->cookie__city]) ? $_COOKIE[$this->cookie__city] : false;
        
        if ($cookie) {
            $data = (array)json_decode(urldecode($cookie));
            if (!$data['city_id'] || !$data['geo_ip_id']) {
                return false;
            }

        }

        return !!$cookie;
    }

    /**
     * получение id города по ip
     */
    private function get_city_id($long_ip = NULL) {
        $city_id = false;

        $query_city_id = "SELECT * FROM (SELECT * FROM net_ru WHERE `begin_ip`<={$long_ip} ORDER BY `begin_ip` DESC LIMIT 1) AS t WHERE `end_ip`>={$long_ip}";
        $cursor_city_id = NamiCore::getBackend()->cursor;
        $cursor_city_id->execute($query_city_id); 
        $city_info = $cursor_city_id->fetchOne();
        $city_id = isset($city_info['city_id']) ? $city_info['city_id'] : false;
        return $city_id;
    }

    /**
     * получение города по Ip
     */
    public function get_city($city_id) {
        if (is_null($city_id)) {
            throw new Exception('$city_id var will not be null');
        }
        $city = false;
        $city_id = $city_id ? abs($city_id) : false;
        if ($city_id && !is_null($city_id)) {
            $query_city = "SELECT country_id, name_ru, name_en, net_city.id as city_id FROM `net_city` LEFT JOIN `net_t_city` ON net_city.name_ru=net_t_city.name WHERE net_city.id='{$city_id}'";
            $cursor_city = NamiCore::getBackend()->cursor;
            $cursor_city->execute($query_city); 
            $city = $cursor_city->fetchAll();
        }

        return $city;
    }

    public function default_city() {
        return $this->_cities_qs->get(array('enabled' => true, 'title__icontains' => 'Красноярск'));
    }

    /**
     * конвертирование ip в число
     */
    public function long_ip($ip) {
        if (is_null($ip)) {
            throw new Exception('$ip var will not be null');
        }
        return sprintf("%u\n", ip2long($ip));
    }

    /**
     * ip пользователя
     * @return string
     */
    public function get_user_ip() {
        return $this->_user_ip;
    }

    /**
     * установка ip пользователя
     */
    function set_user_ip($ip = null) {
        if ($ip) {
            $this->_user_ip = $ip;
        } else {
            $this->_user_ip = $_SERVER['REMOTE_ADDR'];
        }
        return $this->_user_ip;
    }

    /**
     * получение типа определения города
     * @return string строка с латинским названием типа опрделения города
     */
    public function get_setcity_type() {
        return $this->_setcity_type;
    }

    /**
     * установка значения типа определения города
     * @param  string $type строка с латинским названием типа опрделения города
     * @return string $type
     */
    public function set_setcity_type($type) {
        if ($type) {
            $this->_setcity_type = $type;
        }
        return $this->_setcity_type;
    }

    /**
     * просмотр переменной с городом, который должен бы определиться
     * @return string
     */
    public function get_current_city() {
        return $this->_current_city;
    }

    /**
     * установка текущего города
     * @param string $city
     */
    public function set_current_city($city) {
        if ($city) {
            $this->_current_city = $city;
        }
        return $this->_current_city;
    }

    /**
     * определять ли город?
     * @return bool
     */
    public function be_detect_city() {
        return $this->_detect_city;
    }

    /**
     * установка сообщения
     * @param string $message строка с сообщением
     * @return string $message
     */
    public function set_message($message) {
        if ($message) {
            $this->_message = (string) $message;
        }
        return $this->_message;
    }

    /**
     * получение сообщения о статусе определения
     * @return string
     */
    public function get_message() {
        return $this->_message;
    }
}
?>
<?

Class UserCity {

    private static $instance = null;
    private $current_city = null;
    private $cookie_name = 'user_city';
    private $cookie_expire = 0;
    public $need_detect = true;

    private function __clone() {

    }

    private function __construct() {

    }

    public function getCurrentCity() {
        return $this->current_city;
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new UserCity();

            self::$instance->loadData();
        }
        return self::$instance;
    }

    private function loadData() {
        $this->cookie_expire = time() + 60 * 60 * 24 * 30;
        if (!$this->setCurrentCity()) {
            throw new Exception("default city was not detected");
        }
    }

    private function setCurrentCity() {
        if (Meta::vars("city")) {
            $city = ContactCities()
                ->get(array(
                "enabled" => true,
                "ymap_name" => mb_strtolower(Meta::vars("city")),
            ));

            if ($city) {
                $this->current_city = $city;
                setcookie($this->cookie_name, $city->ymap_name, $this->cookie_expire, "/");
                $this->need_detect = false;
                return true;
            }
        }

        if (array_key_exists($this->cookie_name, $_COOKIE) && $_COOKIE['user_city']) {
            $city = ContactCities()
                ->get(array(
                "enabled" => true,
                "ymap_name" => mb_strtolower($_COOKIE['user_city']),
            ));

            if ($city) {
                $this->current_city = $city;
                setcookie($this->cookie_name, $city->ymap_name, $this->cookie_expire, "/");
                $this->need_detect = false;
                return true;
            }
        }

        if (!$this->current_city) {
            $city = ContactCities()
                ->get(array(
                "enabled" => true,
                "is_default" => true,
            ));

            if ($city) {
                $this->current_city = $city;
                setcookie($this->cookie_name, $city->ymap_name, $this->cookie_expire, "/");
                return true;
            }
        }

        return false;
    }

}

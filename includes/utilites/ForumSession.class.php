<?

class ForumSessionException extends Exception {
    protected $name;
    function __construct( $name, $message ) {
        $this->name = $name;
        $this->message = $message;
    }
    function getName() {
        return $this->name;
    }
}

class ForumSession {
    private $session = null; // Ссылка на PHP-сессию
    private $user = null;    // текущий пользователь, объект User
    
    private static $instance = null; // Единственный экземпляр класса, который позволительно создать, фишки для singletone
    
    public static function getInstance() {
        if( ! self::$instance ) {
            self::$instance = new ForumSession();
        }
        return self::$instance;
    }
    
    protected function __construct() {
		session_start();

		// Проверим наличие в сессии наших данных, создадим, если нету
		if( ! array_key_exists( 'builderforum', $_SESSION ) ) {
			$_SESSION['builderforum'] = array();
		}
        
        // Сохраняем ссылку на элемент глобального массива, чтобы работать уже со своим полем, и при этом модифицировать сессию PHP
        $this->session = & $_SESSION['builderforum'];

        // Проверяем сессию
        $this->checkSession();
    }
    
    private function checkSession() {
        // Читаем пользователя, если он есть
        $this->user = ( array_key_exists( 'user', $this->session ) && $this->session['user'] ) ? unserialize( $this->session['user'] ) : null;
        if( $this->user && ! ( @$this->session['permanent'] || @$_COOKIE['builder_forum_session'] ) ) {
            $this->logout();
        }
    }
    
    public function login( $email, $password, $permanent = true ) {
        // Сбрасываем текущую сессию
        $this->session['user'] = $this->user = null;
    
        // Выберем пользователя по email
        $user = ForumUsers( array( 'email' => $email ) )->first();
        
        if( ! $user ) {
            throw new ForumSessionException( 'notfound', "Учетная запись {$email} не найдена." );
        }
        
        if( $user->password !== $password ) {
            throw new ForumSessionException( 'wrongpassword', "Введен неправильный пароль." );
        }
    
        if( ! $user->active ) {
            throw new ForumSessionException( 'inactive', "Учетная запись {$email} не активирована." );
        }
    
        if( $user->banned ) {
            throw new ForumSessionException( 'banned', "Учетная запись {$email} заблокирована администрацией." );
        }
        
        $this->user = $user;
        $this->session['user'] = serialize( $user );
        
        if( $permanent ) {
            $this->session['permanent'] = true;       
        } else {
            $this->session['permanent'] = false;
            setcookie( 'builder_forum_session', 1, 0, '/' );
        }

        return true;
    }
    
    public function logout() {
        $this->session['user'] = $this->user = null;
        return true;
    }

    public function getUser() {
        return $this->user;
    }
    
    public function isAuthorized() {
        return (boolean) $this->user;
    }
    
    public function reloadData() {
        $this->user = ForumUsers( array( 'id' => $this->user->id ) )->first();
        $this->session['user'] = $this->user ? serialize( $this->user ) : null;
    }
    
    public function __set( $name, $value ) {
		$this->session[ $name ] = $value;    
    }
    
    public function __get( $name ) {
    	return array_key_exists( $name, $this->session ) ? $this->session[$name] : null;
    }    
}


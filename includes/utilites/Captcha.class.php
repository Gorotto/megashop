<?
/**
	Капча!
	
	Пример использования — капча с формой и js-обновлением капчи по клику на картинку.
	Вставить код можно в любой view.
	---------------------------------
	<p><?= Captcha::getImageHTML( array( 'class' => 'captcha', 'style' => 'cursor:pointer' ) ) ?></p>
	<form><?= Captcha::getInputHTML() ?><button type="submit">Отправить</button></form>
	<p><? if( Captcha::check() ): ?>Капча введена верно.<? endif ?></p>

	<script src="/static/js/jquery-1.3.2.min.js"></script>
	<script>
	$( function() {
		$( '.captcha' ).click( function( event ) {
			var src = $( this ).attr( 'osrc' ) || $( this ).attr( 'src' );
			$( this ).attr( 'osrc', src ).attr( 'src', src + '?' + new Date() );
		} );
	} );
    </script>
	--------------------------------
*/

class Captcha {
    static protected $settings = array();

    static protected $length = 4;
    static protected $storage;
    static protected $key;
    static protected $uri = '/includes/captcha.php';

	static public function init() {
        // Проверим доступность нужных расширений для работы с графикой
		if( ! @extension_loaded( 'gd' ) ) {
			throw new Exception( 'Отсутствует GD, расширение php для создания изображений. Работа Captcha невозможна.' );
		}
        if( ! imagetypes() & IMG_PNG ) {
            throw new Exception( 'В данной сборке php отсутствует поддержка PNG. Работа Captcha невозможна.' );
        }
        // Поскольку нет способа проверить наличие сессии, а session_start выдает notice, приходится использовать собаку :\ Чёртов php!
		if( ! @session_start() ) {
			throw new Exception( 'Не удалось начать сессию php, работа Captcha невозможна.' );
		}
		
		// Сохраним ссылку на хранилище данных
        self::$storage = & $_SESSION[ __CLASS__ ];
        
        // Ключ по умолчанию
        self::setKey( strtolower( __CLASS__ ) );
        
        // Настройки изображения по умолчанию
        self::$settings = array(
            'width'     => 75,
            'height'    => 19,
            'bgcolor'   => 'e7dfc5',
            'fgcolor'   => 'bf2c53',
            'fgopacity' => 10,
            'digits'    => "{$_SERVER['DOCUMENT_ROOT']}/static/i/captchadigits.png",
            'strength'  => 0,
        );
        
        // Проверим наличие файла с цифрами
        if( ! is_readable( self::$settings['digits'] ) ) {
            throw new Exception( "Не удалось открыть изображение ".self::$settings['digits']."." );
        }
	}
	
	static public function setKey( $newkey ) {
	   self::$key = $newkey;
	}
	
	static public function getKey() {
	   return self::$key;
	}
	
	static public function getImage() {
        // Генерируем новый код
        $code = self::createCode( self::$length );
        
        // Сохраняем его в хранилище
		self::$storage = md5( $code );
		
		// Генерируем картинку с кодом
        return self::createImage( $code );
	}
	
    static public function createImage( $code ) {
        extract( self::$settings );
    
        $img = imagecreatetruecolor( $width, $height );
        imagealphablending( $img, true );
        imageantialias( $img, true );
        
        list( $r, $g, $b ) = self::parseColor( $bgcolor );
        $bgcolor = imagecolorallocate( $img, $r, $g, $b );
        
        list( $r, $g, $b ) = self::parseColor( $fgcolor );
        $fgcolor = imagecolorallocatealpha( $img, $r, $g, $b, ceil( $fgopacity * 12.7 ) );
        
        imagefilledrectangle( $img, 0, 0, $width, $height, $bgcolor );
        
        list( $dwidth, $dheight ) = getimagesize( $digits );
        $dwidth = floor( $dwidth / 10.0 );

        $digits = imagecreatefrompng( $digits );
        
        $codesize = strlen( $code );
        
        $htolerance = floor( ( $width - $dwidth * $codesize ) / (float) $codesize );
        $vtolerance = floor( $height - $dheight );
        
        $x = 0;
        $step = floor( (float) $width / $codesize );
        foreach( str_split( $code ) as $char ) {
            $digit  = (int)$char % 10;
            $dstx = $x + rand( 0, $htolerance );
            $dsty = rand( 0, $vtolerance );
            $srcx = $dwidth * $digit;
            $srcy = 0;
            imagecopy( $img, $digits, $dstx, $dsty, $srcx, $srcy, $dwidth, $dheight );
            $x += $step;
        }
        
        $linecount = $strength;

        for( $i = 0; $i < $linecount; $i++ ) {
            $x1 = rand( 0, $height - 1 );
            $y1 = rand( 0, $height - 1 );
            $x2 = rand( $width - $height, $width - 1 );
            $y2 = rand( 0, $height - 1 );
            imageline( $img, $x1, $y1, $x2, $y2, $fgcolor );
        }

        ob_start();
        imagepng( $img );
        $data = ob_get_clean();

        imagedestroy( $img );
        
        return $data;
	}
	
	static protected function parseColor( $color ) {
        if( preg_match( '/^#?([0-9a-f]{1,2})([0-9a-f]{1,2})([0-9a-f]{1,2})$/i', $color, $m ) ) {
            array_shift( $m );
            foreach( $m as & $v ) {
                if( strlen( $v ) < 2 ) {
                    $v = str_repeat( $v, 2 );
                }
                $v = hexdec( $v );
            }
            return $m;
        }
        throw new Exception( "Некорректное определение цвета: {$color}." );
	}
	
	static private function createCode( $length ) {
		$code = '';
		$chars = array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 );
		for( $i=0; $i< $length; $i++ ) {
			$code .= $chars[ mt_rand( 0, count( $chars )-1 ) ];
		}
		return $code;
	}

	static public function getImageHTML( $attributes = array() ) {
		$attr = "";
		foreach( array_merge( $attributes, array( 
            'src'       => self::$uri."?".time(),
            'width'     => self::$settings['width'],
            'height'    => self::$settings['height'],
            'alt'       => 'Защитный код',
            ) ) as $k => $v ) {
			$attr .= " {$k}" . "='" . htmlspecialchars( $v, ENT_QUOTES, 'UTF-8', false ) . "'";
		}
		return "<img{$attr}/>";
	}

	public function getInputHTML( $attributes = array() ) {
		$attr = "";
		foreach( array_merge( $attributes, array( 'type' => 'text', 'name' => self::$key ) ) as $k => $v ) {
			$attr .= " {$k}" . '="' . htmlspecialchars( $v, ENT_QUOTES, 'UTF-8', false ) . '"';
		}
		return "<input{$attr}/>";
	}
	
	function check( $code = null ) {
		foreach( array( $code, @$_POST[ self::$key ], @$_GET[ self::$key ] ) as $v ) {
			if( $v ) {
				$code = $v;
				break;
			}
		}
		$md5 = self::$storage;
		self::$storage = '';

		return md5( strtoupper( $code ) ) == $md5 ? true : false;
	}
	
	static public function settings() {
        $args = func_get_args();
        if( count( $args ) == 2 && array_key_exists( $args[0], self::$settings ) ) {
            self::$settings[ $args[0] ] = $args[ 1 ];
        } else if( count( $args ) == 1 && is_array( $args[0] ) ) {
            foreach( $args[0] as $k => $v ) {
                if( array_key_exists( $k, self::$settings ) ) {
                    self::$settings[ $k ] = $v;
                }
            }
        }
	}
}


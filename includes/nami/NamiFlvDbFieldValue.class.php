<?

class NamiFlvDbFieldValue extends NamiFileDbFieldValue {
    protected $ffmpeg	= null;	// Путь к программе ffmpeg для преобразования видео и генерации скриншотов
	public $width	= 320;	// Ширина видео
	public $height	= 240;	// Высота видео
	public $preview;
	public $priview_uri;
    public $force_convert = true; // Конвертировать все файлы, даже готовые flv, которые можно не конвертировать
	
	function __construct( array $params = array() ) {
		parent::__construct( $params );

		foreach( array( 'ffmpeg', 'width', 'height', 'force_convert' ) as $property ) {
            if( array_key_exists( $property, $params ) ) {
    			$this->$property = $params[ $property ];
            }
		}
	}
	
    protected function getFfmpegPath() {
        if( $this->ffmpeg ) {
            return $this->ffmpeg;
        }
        
        // Путь не задан, попробуем догадаться
        foreach( array( '/usr/local/bin/ffmpeg', '/opt/local/bin/ffmpeg' ) as $fname ) {
            if( is_executable( $fname ) ) {
                $this->ffmpeg = $fname;
                return $this->ffmpeg;
            }
        }
        
        throw new NamiException( "Программа ffmpeg не установлена на сервере, работа с видеофайлами невозможна." );
    }

	protected function loadValue( $value ) {
		// Прочитаем значение словны мы — FileDbField
		$result = parent::loadValue( $value );

		// Если есть источник нового файла — подменим его на сконвертированный
        if( $this->source && ( $this->force_convert || ! preg_match( '/\.flv$/', $this->source['name'] ) ) ) {
			// Временный файл
			$tmpFname = tempnam( null, 'mvf' );
			
			$convertCmd = "%{ffmpeg} -i %{source} -y -ar 22050 -ab 56k -b 200k -r 12 -f flv -s %{width}x%{height} -ac 1 %{destination} 1>/dev/null 2>&1";
			
			$options = array(
				'ffmpeg'		=> $this->getFfmpegPath(),
				'width'			=> $this->width,
				'height'		=> $this->height,
				'source'		=> $this->source['path'],
				'destination'	=> $tmpFname,
			);
			
			exec( NamiUtilities::array_printf( $convertCmd, $options ), $output, $retcode );
			
			// 1 - ошибка, 0 - все в порядке
			if( $retcode ) {
				throw new Exception( 'Ошибка преобразования видеофайла. Видимо, файл имеет неподдерживаемый формат. Используйте другой файл.' );
			}
			// Подменим источник на наш файл, а расширение переправим на flv
			$this->source['path'] = $tmpFname;
			$this->source['name'] = preg_replace( '/[^.]+$/', 'flv', $this->source['name'] );
		}

		return $result;
    }
	
	protected function checkValue() {
		parent::checkValue();
		if( ! is_null( $this->value ) ) {
			if( property_exists( $this, 'preview' ) ) {
				$this->preview_uri = "{$this->path}/{$this->preview}";
			}
		}
	}

	protected function prepareSimplifiedValue() {
		$value = parent::prepareSimplifiedValue();
		if( $this->value ) {
			foreach( array( 'width', 'height', 'preview', 'preview_uri' ) as $property ) {
				if( property_exists( $this->value, $property ) ) {
					$value->$property = $this->value->$property;
				}
			}
		}
		return $value;
	}

	protected function removeFiles( $simplified ) {
		if( is_object( $simplified ) && property_exists( $simplified, 'preview' ) && $simplified->preview ) {
			$file = "{$_SERVER['DOCUMENT_ROOT']}/{$this->path}/{$simplified->preview}";
			if( file_exists( $file ) && is_writeable( $file ) ) {
				unlink( $file );
			}
		}
		return parent::removeFiles( $simplified );
	}
	
	function beforeSave() {
		$return = parent::beforeSave();
		
		if( ! is_null( $this->value ) ) {
			// Сгенерим скриншот
			$screenshotCmd = "%{ffmpeg} -i %{source} -y -f mjpeg -ss 0 -sameq -t 0.001 -s %{width}*%{height} %{destination} 1>/dev/null 2>&1";
			
			$options = array(
				'ffmpeg'		=> $this->getFfmpegPath(),
                'width'			=> $this->width,
				'height'		=> $this->height,
				'source'		=> "{$_SERVER['DOCUMENT_ROOT']}/{$this->path}/" . $this->value->filename,
				'destination'	=> preg_replace( '/[^.]+$/', 'jpg', "{$_SERVER['DOCUMENT_ROOT']}/{$this->path}/{$this->value->filename}" ),
			);
			
			exec( NamiUtilities::array_printf( $screenshotCmd, $options ), $output, $retcode );
			
			// 1 - ошибка, 0 - все в порядке
			if( $retcode ) {
				throw new Exception( 'Ошибка преобразования видеофайла. Видимо, файл имеет неподдерживаемый формат. Используйте другой файл.' );
			}
			
			$this->value->preview = preg_replace( '/[^.]+$/', 'jpg', $this->value->filename );

			$this->checkValue();
		}
		
		return $return;
	}

	protected function prepareDatabaseValue() {
		$value = parent::prepareDatabaseValue();
		if( $value ) {
			$value->preview = $this->preview;
		}
		return $value;
	}
}


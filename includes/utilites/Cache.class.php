<?
/**
    Кеш на файлах
    
    $cache = new Cache();
    $cache->set("foo", $items, "+ 1 day");
    $cache->get("foo");
*/
class Cache {

    static $default_timeout = 86400;
    
    /**
        Достаем кеш
    */
    public function get($key) {
        $key_compressed = $this->make_key($key);
        $path = $this->key_to_file($key_compressed);
    
        if (!file_exists($path)) {
            return false;
        }

        if (!$fp = @fopen($path, 'r')) {
            return false;
        }
        if (!flock($fp, LOCK_SH | LOCK_NB)) {
            return null;
        }
        
        $filesize = filesize($path);
        if ($filesize == 0) {
            return null;
        }
        $filedata = fread($fp, $filesize);
        flock($fp, LOCK_UN);
        fclose($fp);
        
        $separator = strpos($filedata, "\n");
        $expiration = substr($filedata, 0, $separator);
        $value = substr($filedata, $separator + 1);
        
        if ($expiration < time()) {
            $this->delete($key);
            return null;
        }
        
        return unserialize($value);
    }
    
    /**
        Устанавливаем кеш
        
        $timeout 
        - может быть не задан, тогда кеш устанавливается на сутки
        - может быть задан в виде целого числа (+ n секунд)
        - может быть задан в виде строки:
            "10 September 2000"
            "+1 day"
            "+1 week"
            "+1 week 2 days 4 hours 2 seconds"
            "next Thursday"                 
    */
    public function set($key, $value, $timeout = null) {
        $key_compressed = $this->make_key($key);
        $path = $this->key_to_file($key_compressed);

        $dirname = dirname($path);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
        if (!is_writable($dirname)) {
            return false;
        }

        if (!$fp = fopen($path, 'w+')) {
            return false;
        }
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            $expiration = $this->get_expiration($timeout);
            fwrite($fp, "{$expiration}\n");
            fwrite($fp, serialize($value));
            flock($fp, LOCK_UN);
        } else {
            return false;
        }
        fclose($fp);
        @chmod($path, 0666);
        return true;
    }
    
    public function delete($key) {
        $key_compressed = $this->make_key($key);
        $path = $this->key_to_file($key_compressed);
        @unlink($path);
        $dirname = dirname($path);
        @rmdir($dirname);
        return true;
    }

    private function get_expiration($timeout) {
        $time = time();
        if (!$timeout) {
            return $time + self::$default_timeout;
        }
        if (is_numeric($timeout)) {
            return $time + $timeout;
        }
        if (is_string($timeout)) {
            $expiration = strtotime($timeout);
            if ($expiration) {
                return $expiration;
            }
        }
        return $time + self::$default_timeout;
    }
    
    private function make_key($key) {
		$new_key = md5(serialize($key));
		return $new_key;
	}

	private function key_to_file($key) {
		return "{$_SERVER['DOCUMENT_ROOT']}/cache/" . 
		         substr($key, 0, 2) . "/" . 
		         substr($key, 2);
	}
	
	/* clear */
	
	/* cache limit */

}
<?php
     
 class Memcached extends Memcache {
    public $CacheHits = array();
    public $MemcacheDBArray = array();
    public $MemcacheDBKey = '';
    public $Time = 0;
	protected $InTransaction = false;
    protected $Page = array();
    protected $Row = 1;
    protected $Part = 0;
       
    function __construct() {
        $this->connect('127.0.0.1', '11211'); // Host...
    }

    public function cache_value($Key, $Value, $Duration=2592000) {
        $StartTime=microtime(true);
        if (empty($Key)) {
            trigger_error("Cache insert failed for empty key");
        }
        if (!$this->set($Key, $Value, 0, $Duration)) {
            trigger_error("Cache insert failed for key $Key", E_USER_ERROR);
        }
        $this->Time+=(microtime(true)-$StartTime)*1000;
    }

    public function add_value($Key, $Value, $Duration=2592000) {
        $StartTime=microtime(true);
        if (empty($Key)) {
            trigger_error("Cache insert failed for empty key");
        }
        $add = $this->add($Key, $Value, 0, $Duration);
        $this->Time+=(microtime(true)-$StartTime)*1000;
        return $add;
    }

    public function get_value($Key, $NoCache=false) {
        $StartTime=microtime(true);
        if (empty($Key)) {
            trigger_error("Cache retrieval failed for empty key");
        }
        $Return = $this->get($Key);
        $this->Time+=(microtime(true)-$StartTime)*1000;
        return $Return;
    }

    public function replace_value($Key, $Value, $Duration=2592000) {
        $StartTime=microtime(true);
        $this->replace($Key, $Value, false, $Duration);
        $this->Time+=(microtime(true)-$StartTime)*1000;
    }

    public function delete_value($Key) {
        $StartTime=microtime(true);
        if (empty($Key)) {
            trigger_error("Cache retrieval failed for empty key !");
        }
        if (!$this->delete($Key,0)) {
        }
        $this->Time+=(microtime(true)-$StartTime)*1000;
    }

    public function begin_transaction($Key) {
        $Value = $this->get($Key);
        if (!is_array($Value)) {
            $this->InTransaction = false;
            $this->MemcacheDBKey = array();
            $this->MemcacheDBKey = '';
        return false;
        }
        $this->MemcacheDBArray = $Value;
        $this->MemcacheDBKey = $Key;
        $this->InTransaction = true;
        return true;
    }
     
    public function cancel_transaction() {
        $this->InTransaction = false;
        $this->MemcacheDBKey = array();
        $this->MemcacheDBKey = '';
    }
     
    public function commit_transaction($Time=2592000) {
        if (!$this->InTransaction) {
            return false;
        }
        $this->cache_value($this->MemcacheDBKey, $this->MemcacheDBArray, $Time);
        $this->InTransaction = false;
    }

    public function update_transaction($Rows, $Values) {
        if (!$this->InTransaction) {
            return false;
        }
        $Array = $this->MemcacheDBArray;
        if (is_array($Rows)) {
            $i = 0;
            $Keys = $Rows[0];
            $Property = $Rows[1];
        foreach ($Keys as $Row) {
            $Array[$Row][$Property] = $Values[$i];
            $i++;
            }
        } else {
        $Array[$Rows] = $Values;
        }
        $this->MemcacheDBArray = $Array;
    }

    public function update_row($Row, $Values) {
        if (!$this->InTransaction) {
            return false;
        }
        if ($Row === false) {
            $UpdateArray = $this->MemcacheDBArray;
        } else {
            $UpdateArray = $this->MemcacheDBArray[$Row];
        }
        foreach ($Values as $Key => $Value) {
        if (!array_key_exists($Key, $UpdateArray)) {
            trigger_error('Bad transaction key ('.$Key.') for cache '.$this->MemcacheDBKey);
        }
        if ($Value === '+1') {
        if (!is_number($UpdateArray[$Key])) {
            trigger_error('Tried to increment non-number ('.$Key.') for cache '.$this->MemcacheDBKey);
        }
            ++$UpdateArray[$Key];
        } elseif ($Value === '-1') {
        if (!is_number($UpdateArray[$Key])) {
            trigger_error('Tried to decrement non-number ('.$Key.') for cache '.$this->MemcacheDBKey);
        }
            --$UpdateArray[$Key];
        } else {
            $UpdateArray[$Key] = $Value; // Otherwise, just alter value
            }
        }
        if ($Row === false) {
            $this->MemcacheDBArray = $UpdateArray;
        } else {
            $this->MemcacheDBArray[$Row] = $UpdateArray;
        }
    }

    public static function clean() {
        if (!self::$this) {
            trigger_error('Not connected to Memcache server in '.__METHOD__, E_USER_WARNING);
        return false;
        }
        self::$count++;
        $time = microtime(true);
        $clean = self::$link->flush();
        self::$time += (microtime(true) - $time);
        self::set_error(__METHOD__);
    return $clean;
    }

}

?>

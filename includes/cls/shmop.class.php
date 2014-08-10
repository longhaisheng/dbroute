<?php
/**
 * php.ini 需开启php_shmop.dll
 * @author longhaisheng
 * SimpleSHM
 */
class cls_shmop {

    private static $static_int_num = 1000000;

    public static function strToIntKey($str) {
        $len = strlen($str);
        $total = 0;
        for ($i = 0; $i < $len; $i++) {
            $c = ord(substr($str, $i, 1));
            $total = $total + $c;
        }
        return self::$static_int_num - $total;
    }

    /**
     * 向cache中写入 字符串、数字、boolean,数组和类实例除外
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public static function write($key, $value) {
        if (empty($value)) {
            return false;
        }
        $key = self::strToIntKey($key);
        $connect = shmop_open($key, 'c', 0644, strlen($value));
        if ($connect === false) {
            return false;
        }
        //shmop_delete($connect);
        $length = shmop_write($connect, $value, 0);
        shmop_close($connect);
        if ($length === false) {
            return false;
        }
        return true;
    }

    /**
     * 从cache中读数据 包括字符串、数字、boolean 的内容，数组和类实例除外
     * @param string $key
     */
    public static function read($key) {
        $key = self::strToIntKey($key);
        $connect = @shmop_open($key, 'a', 0644, 0);
        if ($connect) {
            $value = shmop_read($connect, 0, shmop_size($connect));
            shmop_close($connect);
            return $value;
        }
        return false;
    }

    /**
     * 将数组写入cache
     * @param string $key
     * @param array $arrayList
     * @return boolean
     */
    public static function writeArray($key, $arrayList = array()) {
        //if (!is_array($arrayList) || empty($arrayList)) {
        //	return false;
        //}
        $key = self::strToIntKey($key);
        $value = serialize($arrayList);
        $connect = shmop_open($key, 'c', 0644, strlen($value));
        if ($connect === false) {
            return false;
        }
        //shmop_delete($connect);
        $length = shmop_write($connect, $value, 0);
        shmop_close($connect);
        if ($length === false) {
            return false;
        }
        return true;
    }

    /**
     * 从cache中读取数组
     * @param string $key
     * @return array
     */
    public static function readArray($key) {
        $key = self::strToIntKey($key);
        $connect = @shmop_open($key, 'a', 0644, 0);
        if ($connect) {
            $arrayList = shmop_read($connect, 0, shmop_size($connect));
            shmop_close($connect);
            return unserialize($arrayList);
        }
        return false;
    }

}
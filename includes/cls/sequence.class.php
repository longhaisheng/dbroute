<?php
class cls_sequence {

    private $mysql;

    private $default_step = 100;

    private $primary_name = 'id';

    private $retry_time = 100;

    public function __construct() {
        global $default_config_array;
        $this->mysql = new cls_mysql($default_config_array);
        if (defined('SEQUENCE_DEFAULT_STEP')) { //序列递增步长
            $this->default_step = SEQUENCE_DEFAULT_STEP;
        }
    }

    public function setDefaultStep($default_step) {
        $this->default_step = $default_step;
    }

    public function getDefaultStep() {
        return $this->default_step;
    }

    public function setPrimaryName($primary_name) {
        $this->primary_name = $primary_name;
    }

    public function getPrimaryName() {
        return $this->primary_name;
    }

    private function getLastSeq($table_name) {
        $range = new SeqRange();
        for ($i = 0; $i < $this->retry_time; $i++) {
            $sql = "select last_seq from sequence where table_name='$table_name' for update";
            $this->mysql->begin();
            $row = $this->mysql->getRow($sql);
            $update_result = 0;
            if ($row) {
                $new_value = $row['last_seq'] + $this->getDefaultStep();
                $range->setMin($row['last_seq'] + 1);
                $range->setMax($new_value);
                $update_sql = "update sequence set last_seq=$new_value,modify_date=now() where table_name='$table_name'  ";
                $update_result = $this->mysql->execute($update_sql);
            } else {
                $last_seq = $this->getDefaultStep();
                $primary_name= $this->getPrimaryName();
                $insert_sql = "insert sequence(table_name,primary_name,last_seq,modify_date) value('$table_name','$primary_name',$last_seq,now())  ";
                $update_result = $this->mysql->insert($insert_sql);
                $range->setMin(1);
                $range->setMax(0 + $this->getDefaultStep());
            }
            $this->mysql->commit();
            if ($update_result) {
                return $range;
            }
        }

        return null;

    }

    public function nextValue($logic_table) {
        $file = fopen(ROOT_PATH . 'includes/cls/'.$logic_table.'_seq.txt', "w+");
        if (flock($file, LOCK_EX)) { //独占锁
            $value = 0;
            $is_write = false;
            for (; ;) {
                $new_array = cls_shmop::readArray($logic_table);
                if ($new_array) {
                    $value = array_shift($new_array);
                    $is_write = cls_shmop::writeArray($logic_table, $new_array);
                } else {
                    $range = $this->getLastSeq($logic_table);
                    if ($range) {
                        $min = $range->getMin();
                        $max = $range->getMax();
                        $array = array();
                        for ($i = $min; $i < $max + 1; $i++) {
                            $array[] = $i;
                        }
                        $value = array_shift($array);
                        $is_write = cls_shmop::writeArray($logic_table, $array);
                    }
                }
                if ($is_write && $value) {
                    break;
                }
            }
            flock($file, LOCK_UN);
        }
        fclose($file);
        return $value;
    }

}

class SeqRange {

    private $min;

    private $max;

    public function setMax($max) {
        $this->max = $max;
    }

    public function getMax() {
        return $this->max;
    }

    public function setMin($min) {
        $this->min = $min;
    }

    public function getMin() {
        return $this->min;
    }
}


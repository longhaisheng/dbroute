<?php
class RefundAO{

    private $refundModel;

    private $refundInfoModel;


    public function __construct(){
        $this->refundModel=new RefundModel();//分库分表类
        $this->refundInfoModel=new RefundInfoModel();//分库分表类
    }

    /**逻辑表(refund、refund_info)分表规则相同,且是多库多表，都根據user_id切分，事务生效*/
    public function testTransaction($user_id){
        $tx_params=array('user_id'=>$user_id);
        try{
            $this->refundModel->getDbroute()->begin($tx_params);

            $refund_id=$this->refundModel->insert($user_id);
            $refund_info_id=$this->refundInfoModel->insert($user_id,$refund_id);

            $this->refundModel->getDbroute()->commit($tx_params);
            echo "refund_id id is ".$refund_id." "."refund_info_id is ".$refund_info_id;
            return true;
        }catch (Exception $e){
            $this->refundModel->getDbroute()->rollBack($tx_params);
            echo "exception for ".$e->getMessage();
            return false;
        }
    }

}
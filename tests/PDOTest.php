<?php
error_reporting('E_ALL & ~E_NOTICE');
require(dirname(__DIR__ ). '/includes/init.php');

class PDOTest extends PHPUnit_Framework_TestCase
{
    public $model;

    public function __construct()
    {
        $this->model = new OrderModel();
    }

    public function testGetAll()
    {
        $r = $this->model->getAll();
        $this->assertGreaterThan(0, count($r));
    }

    public function testGetRow()
    {
        $r = $this->model->getRow(2002);
        $this->assertEquals('2002', $r['id']);
    }

    public function testInsert()
    {
        $id = $this->model->insert();
        $this->assertGreaterThan(0, $id);

        return $id;
    }

    /**
     * @depends testInsert
     */
    public function testUpdate($id)
    {
        $r = $this->model->update($id, 10);
        $this->assertEquals(1, $r);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testDelete($id)
    {
        $r = $this->model->delete($id, 10);
        $this->assertEquals(1, $r);
    }
}
 
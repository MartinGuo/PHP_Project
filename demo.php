<?php
/**
 * Created by PhpStorm.
 * User: guoshaomin
 * Date: 16/1/7
 * Time: 下午5:34
 */
class Car {
    public $speed = 10; //汽车的起始速度是0
    
    public function __construct(){
    	$this->speed = 30;
    }
    public function speedUp() {
        $this->speed += 10;
        return $this->speed;
    }
}
//定义继承于Car的Truck类

class Truck extends Car{
    public function speedUp(){
        $this->speed += 50;
        return $this->speed;
    }
}

$car = new Truck();

echo $car->speed;
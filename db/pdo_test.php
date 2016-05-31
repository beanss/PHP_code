<?php
/**
 * Created by PhpStorm.
 * User: beans
 * Date: 16-5-30
 * Time: 下午4:58
 */
require 'pdo_drive.php';

$pdo = new db(
        array(
            'dsn' => "mysql:host=127.0.0.1;port=3306;dbname=test;",
            'user' => "yanxi",
            'pass' => "123456",
            'char' => "utf8",
            'fileSize' => '10',
        )
);

$sql = "select id,name,age from test1";

$h = 0;
while ($h < 50000) {
    $result = $pdo->query($sql);
    $h++;
}
print_r($result);
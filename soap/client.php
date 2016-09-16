<?php
/**
 * Project: imooc-1
 * User: xialeistudio
 * Date: 2016/9/16 0016
 * Time: 22:29
 */
$client = new SoapClient(null, [
    'location' => 'http://localhost/imooc-1/soap/server.php',
    'uri' => 'post',
    'login' => 'xialei',
    'password' => '111111'
]);
try {

    echo $client->deleteArticle(1);
} catch (SoapFault $e) {
    echo $e->getMessage();
}
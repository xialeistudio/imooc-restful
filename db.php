<?php
$pdo = new PDO('mysql:host=localhost;dbname=restful','root','root',[PDO::ATTR_EMULATE_PREPARES=>false]);
return $pdo;

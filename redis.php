<?php
include 'inc.php';


// $DOCKER_COMPOSE_ROOT

$upd = getRequest("upd");


htmlHeader("redis");
menu();
echo str150("redis ") .strSilver("redis管理") . BR;

if ($upd){
    for ($i =0; $i < 10; $i++){
        $rand = rand(1,999999);
        $shell = "redis-cli -h localhost SET hoge" .$rand. " bar" . $rand;
        $rt = runShell($shell);
        echo strPre($rt);
    }


}

?>
<a href="?upd=add">add</a><br/>
<?php

$shell = "redis-cli -h localhost GET hoge";
$rt = runShell($shell);
echo strPre($rt);

$shell = 'redis-cli -h localhost --scan --pattern "*"';
$rt = runShell($shell);
echo strPre($rt);

echo debugFooter();
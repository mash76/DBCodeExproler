<?php
include 'inc.php';

// $DOCKER_COMPOSE_ROOT

$view = getRequest("view");
$action = getRequest("action");
$id = getRequest("id");

htmlHeader("docker");
menu();
echo str150("Docker ") .strSilver("dockerのコンテナ管理") . BR;

if ($action == "stop"){
    runShell("docker stop " . $id);
    echo BR;
}
if ($action == "start"){
    runShell("docker start " . $id );
    echo BR;
}

$version_str =  trim(runShell("docker version")); // docker version -f json
echo SPC . link2detail('version', '<pre style="background:#f8f8f8;">' . $version_str . '</pre>' );

$contexts =  trim(runShell("docker context ls")); // context - kubernates swarm containerをまとめたもの
echo SPC . link2detail('contexts', '<pre style="background:#f8f8f8;">' . $contexts . '</pre>' );



echo BR.BR;

// contaners
$containers = retJson2Ary(runShell("docker ps -a --no-trunc --format='{{json .}}'"),true);
echo SPC . link2detail('raw',asc2html($containers));
$containers = assocColOrder($containers,["Names","Image","State","Action","Ports","Networks","Size","Status","Mounts","ID"]);
foreach ($containers as &$row){
    $row['Ports'] = preg_replace("/(:\d+)/" , strBlueBG('$1'),$row['Ports']);
    if ($row['State'] == "exited") $row['State'] = strRed($row['State']);

    $row['Action'] = '<a href="?action=stop&id=' .$row['ID']. '">stop</a>';
    $row['Action'] .= '<a href="?action=start&id=' .$row['ID']. '">start</a>';
}
echo asc2html($containers,false,false) . BR;

// images
$images = retJson2Ary( runShell("docker images --format='{{json .}}'"),true);
echo SPC . link2detail('raw',asc2html($images));
$images = assocColOrder($images,["Repository","Tag","ID","Size","CreatedSince"]);
echo asc2html($images) . BR;

$ret = runShell("ls " . $DOCKER_COMPOSE_ROOT . " | grep -i docker");
echo BR . $ret;


if ($id) {
    echo "logs " . BR;
    $log = runShell("docker logs " . $id . " --tail 3");
    echo BR . nl2br($log);
}

function retJson2Ary($json_str){
    $ret =[];
    $lines = explode("\n", trim($json_str));
    foreach ($lines as $line){
        $ret[] = json_decode($line,true);
    }
    return $ret;
}


// recordsの指定列だけを指定した順で切り出した新recordsを返す
function assocColOrder($records,$colnameAry){
    $ret =[];
    foreach ($records as $row){
        $ret_row = [];
        foreach ($colnameAry as $colname){
            if (isset($row[$colname])) $ret_row[$colname] = $row[$colname];
            else $ret_row[$colname] ="";

        }
        $ret[] = $ret_row;
    }
    return $ret;
}


echo debugFooter();
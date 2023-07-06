<?php
include 'inc.php';

$action = getRequest("action");

htmlHeader("caches");
menu();
?>
<a href="?action=del_all">del_all</a>

<br/>
<?php

if ($action=="del_all"){
    runShell("rm cache/*");
}


$json_str = runShell("ls -l cache/* | jc --ls");
$records = json_decode($json_str,true);

echo asc2html($records);

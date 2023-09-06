<?php
include 'inc.php';

$sql = getRequest("sql");

htmlHeader($_SESSION['current_env_name'] . " sql");
menu();

?>

<form id="f1">
	<textarea id="sql" name="sql" cols="80" rows="10" ><?=$sql ?></textarea>
	<br/>
	<input type="submit">
</form>


<?php


$sqls = explode(';',$sql);
foreach ($sqls as $key => $sql1){
	if (trim($sql1) == "") continue;
	echo ($key +1) . ". ";
	$recs = sql2asc($sql1,$pdo);
	echo asc2html($recs);
}
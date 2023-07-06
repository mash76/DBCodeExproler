<?php
include 'inc.php';
$pdo = new PDO($datasource,$current_env['user'],$current_env['pass']);

$TABLE_NAME = getRequest("TABLE_NAME",true);
$count = getRequest("count",false,10);
$mode = getRequest("mode",false,10);

$gen_ct = getRequest("gen_ct",false,10);

htmlHeader($TABLE_NAME);
?>
<?=menu(); ?>
<?php
echo str150($TABLE_NAME) .' ';
$sql_count = "select count(*) ct from `". $TABLE_NAME ."`";
$ct_recs = sql2asc($sql_count,$pdo,false);
echo str150($ct_recs[0]['ct']);
?>
&nbsp;
gen
<a href="?TABLE_NAME=<?=$TABLE_NAME?>&mode=generate&gen_ct=1">1</a>
<a href="?TABLE_NAME=<?=$TABLE_NAME?>&mode=generate&gen_ct=10">10</a>
<a href="?TABLE_NAME=<?=$TABLE_NAME?>&mode=generate&gen_ct=100">100</a>
<a href="?TABLE_NAME=<?=$TABLE_NAME?>&mode=generate&gen_ct=1000">1000</a>
del
<a href="?TABLE_NAME=<?=$TABLE_NAME?>&mode=delete">all</a>
ddl
<a href="?TABLE_NAME=<?=$TABLE_NAME?>&mode=create_statement">create_statement</a>
<br/>
<?php


// 表示
$sql_columns = "select ORDINAL_POSITION ID,COLUMN_NAME NAME,COLUMN_KEY ISKEY,COLUMN_TYPE TYPE,
		IS_NULLABLE ISNULL,COLUMN_DEFAULT DEF,EXTRA,COLUMN_COMMENT
	from information_schema.COLUMNS where TABLE_SCHEMA='" . $current_env['schema'] . "' and TABLE_NAME = '". $TABLE_NAME ."' order by ID asc";
$columns_raw = sql2asc($sql_columns,$pdo);
$columns = [];
foreach ($columns_raw as $row) $columns[$row['NAME']] = $row;
echo asc2html($columns,false,false);

if ($mode=="create_statement"){
	$ret = sql2asc("show create table " . $TABLE_NAME,$pdo);
	echo pre($ret[0]['Create Table']);
}

if ($mode=="delete"){

	sqlExec("delete from " . $TABLE_NAME,$pdo);
}
if ($mode=="generate"){

	for($i=1;$i<= $gen_ct; $i++){
		$cols =[]; // autoでないcolのみ
		$vals =[];
		foreach ($columns as $row){
			if ($row['EXTRA'] == "auto_increment") continue;
			$cols[] = $row['NAME'];

			if (strpos($row['TYPE'],"varchar") !== false) $vals[]= "'aaaaaaa" . rand(10000,50000) . "'";
			if (strpos($row['TYPE'],"int") !== false) $vals[]= rand(1,5);
			if (strpos($row['TYPE'],"date") !== false) $vals[]= 'now()';
		}
		$stmt = "insert into " . $TABLE_NAME . " (" .implode(',',$cols). ") values(" .implode(',',$vals). ")";
		sqlExec($stmt,$pdo);
	}
}

function genInsertCols($columns){

}
function genInsertVals($columns){

}




foreach([10,100,500] as $val){
	?><a href="?TABLE_NAME=<?=$TABLE_NAME ?>&count=<?=$val?>"><?=$val ?></a> <?php
}



$sql_sample_data = "select * from `". $TABLE_NAME ."` limit " . $count;
$ret = sql2asc($sql_sample_data,$pdo);
echo asc2html($ret);


foreach ($columns as $row){
	echo $TABLE_NAME . '.' . $row['NAME'] . "\t" . $row['COLUMN_COMMENT'] . BR;
}
?>
<script>

			$(document).keydown((event)=>{
				if (event.key == "Control" ) {
					location.href = "tables.php"
				}
			})
</script>
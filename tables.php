<?php
include 'inc.php';


$filter_tables =[
	'TABLE' => ["card","log","user"],
];
$filter_cols =[
	'COL_TYPE' => ["varchar","int","date","enum","decimal","float","unsigned","auto_increment"],
	'COL_NAME' => ["create","_id","_at","code","_name","_flag","is_","able","ed"]
];


$filter = getRequest("filter");
$upd = getRequest("upd");
$view = getRequest("view",false,"search");
$days = getRequest("days",false,5);

htmlHeader($_SESSION['current_env_name'] . " TABLES");
menu();



$CACHE_FILE_NAME = 'cache/table_CACHE_ARY-' . $_SESSION['current_env_name'] . '.txt';

if (!file_exists($CACHE_FILE_NAME)){
	createMySQLSchemaStatCache($filter_tables,$filter_cols,$CACHE_FILE_NAME,$pdo);
}
$cache_str = file_get_contents($CACHE_FILE_NAME );
$CACHE_ARY = unserialize($cache_str);

echo str120($CACHE_ARY['__TABLE_ROW_COUNTS']['____table_counts'] . strSilver(' Tables ') );
echo str120($CACHE_ARY['__TABLE_ROW_COUNTS']['____column_counts'] . strSilver(' Columns ') );

?>
<a href="?view=recent_data" >最近のデータ</a>
<a href="?view=schema_stat" >統計</a>
<a href="?view=create_tables" >CreateTables</a>
<a href="?view=table_def_list" >テーブル定義書</a>
<br/>
<?php

////
if ($view == "create_tables"){
	$tbls = searchTables('',false);
	foreach ($tbls as $row) {
		//var_dump($row);
		echo strBlueBG($row['TABLE_NAME']) . BR;
		$ct = sql2asc("show create table ". $row['TABLE_NAME'] , $pdo,false);
		echo nl2br($ct[0]['Create Table']) . BR;
	}
}

if ($view == "table_def_list"){
	$tbls = searchTables('',false);
	foreach ($tbls as $row) {
		echo strBlueBG($row['TABLE_NAME']) . BR;

		$sql_columns = "select ORDINAL_POSITION ID,COLUMN_NAME NAME,COLUMN_KEY ISKEY,COLUMN_TYPE TYPE,
		IS_NULLABLE ISNULL,COLUMN_DEFAULT DEF,EXTRA,COLUMN_COMMENT
		from information_schema.COLUMNS where TABLE_SCHEMA='" . $current_env['schema'] . "' and TABLE_NAME = '". $row['TABLE_NAME'] ."' order by ID asc";
		$columns_raw = sql2asc($sql_columns,$pdo,false);
		echo asc2html($columns_raw,false,false);
	}
}

// スキーマの統計
if ($view == "schema_stat"){

	// 容量 table index
	$sql ="select
	round(sum(DATA_LENGTH) /1024/1024,2) TABLE_MB,
	round(sum(INDEX_LENGTH)/1024/1024 ,2) INDEX_MB
	from information_schema.TABLES where TABLE_SCHEMA= '" . $current_env['schema']. "'";
	$ret = sql2asc($sql,$pdo);
	echo asc2html($ret);

	$sql ="select ENGINE,count(*) from information_schema.TABLES where TABLE_SCHEMA= '" . $current_env['schema']. "' GROUP BY ENGINE";
	$ret = sql2asc($sql,$pdo);
	echo asc2html($ret);

	//テーブル数など
	$sql ="
	select
		(select count(*) from information_schema.TABLES where TABLE_SCHEMA= '" . $current_env['schema']. "') TABLES,
		(select count(*) from information_schema.COLUMNS where TABLE_SCHEMA= '" . $current_env['schema']. "') COLUMNS
		";
	$ret = sql2asc($sql,$pdo);
	echo asc2html($ret);

	//col type
	$sql ="select DATA_TYPE,count(*) from information_schema.COLUMNS where TABLE_SCHEMA= '" . $current_env['schema']. "' GROUP BY DATA_TYPE";
	$ret = sql2asc($sql,$pdo);
	foreach ($ret as &$row){
		$row['DATA_TYPE'] = '<a href="?filter=' .$row['DATA_TYPE']. '">' . $row['DATA_TYPE'] . '</a>';
	}
	echo asc2html($ret);

	$sql ="select COLUMN_TYPE,count(*) from information_schema.COLUMNS where TABLE_SCHEMA= '" . $current_env['schema']. "' GROUP BY COLUMN_TYPE";
	$ret = sql2asc($sql,$pdo);
	foreach ($ret as &$row){
		$row['COLUMN_TYPE'] = '<a href="?filter=' .$row['COLUMN_TYPE']. '">' . $row['COLUMN_TYPE'] . '</a>';
	}
	echo asc2html($ret);

	exit();
}

// 最近データ
if ($view == "recent_data"){

	foreach (["0.01","0.03","0.1","0.5","1","2","3","4","5","10","50"] as $day){
		$disp = $day;
		if ($disp == $days) $disp = strBold($days);
		?><a href="?view=recent_data&days=<?=$day?>" ><?=$disp ?></a> <?php
	}

	$start = microtime(true);
	$date_str = date('Y-m-d H:i:s',time() - 86400 * $days);
	$mins = round($days*3600);
	echo BR. " " .$mins. strsilver("分 ") . $days.strsilver("日 ") . $date_str . '<hr/>' ;

	$wheres =[];
	foreach ($DATE_COLS as $colname){
		$wheres[] = " COLUMN_NAME = '" . $colname . "' ";
	}

	$sql = "select TABLE_NAME,COLUMN_NAME from information_schema.COLUMNS
			where
			TABLE_SCHEMA='" . $current_env['schema'] . "' and
			( " . implode(' or ',$wheres). " ) ";
	$ret = sql2asc($sql,$pdo);
	$table_date_cols = [];
	foreach ($ret as $row){
		if (!isset($table_date_cols[$row['TABLE_NAME']])) $table_date_cols[$row['TABLE_NAME']] = [];
		$table_date_cols[$row['TABLE_NAME']][] = $row['COLUMN_NAME'];
	}

	// テーブル一覧
	foreach ($table_date_cols as $table_name => $columns){
		echo strBlueBG($table_name) . ' ';
		$where = [];
		foreach ($columns as $colname){
			$where[] = $colname . " > '" . $date_str . "'";
		}

		$recs = sql2asc("select * from " . $table_name . " where " .  implode(' or ' ,$where),$pdo);

		foreach ($recs as &$row2){
			$row2 = bringColFront($row2,'updated');
			$row2 = bringColFront($row2,'created');
			$row2 = bringColFront($row2,'created_at');
			$row2 = recentRedCol($row2,'updated',$date_str);
			$row2 = recentRedCol($row2,'created',$date_str);
			$row2 = recentRedCol($row2,'created_at',$date_str);
		}
		echo asc2html($recs,false,false);
	}
	echo "<hr/>";
	echo date('h:i:s',intVal($start)) . '-' . date('h:i:s') . BR;
	echo round(microtime(true) - $start,2) .strSilver(' sec');
	exit();
}


?>

<form id="f1">
	<?php

	// 検索ワードと件数 tables columns
	foreach ($filter_tables as $k_name => $row) {
		echo $k_name . ' ' ;
		foreach ($row as $word) {
			$disp_name = $word;
			$ct_disp = 0;
			if (isset($CACHE_ARY[$word])) {
				$ct_disp = strZeroSilver($CACHE_ARY[$word]);
				if ($CACHE_ARY[$word] == 0) $disp_name = strGray($disp_name);
			}
			if ($filter == $word) $disp_name = strBold($disp_name);
			?><a href="?filter=<?=$word ?>"><?=$disp_name ?></a><?php
			echo strZeroSilver($ct_disp) . ' ';
		}
		echo BR;
	}
	foreach ($filter_cols as $k_name => $row) {
		echo $k_name . ' ' ;
		foreach ($row as $word) {
			$disp_name = $word;
			$ct_disp = 0;
			if (isset($CACHE_ARY[$word])) {
				$ct_disp = strZeroSilver($CACHE_ARY[$word]);
				if ($CACHE_ARY[$word] == 0) $disp_name = strGray($disp_name);
			}
			if ($filter == $word) $disp_name = strBold($disp_name);
			?><a href="?filter=<?=$word ?>"><?=$disp_name ?></a><?php
			echo strZeroSilver($ct_disp) . ' ';

		}
		echo BR;
	}
	?>
	<input type="text" id="filter" name="filter" value="<?=$filter ?>">
	<input type="submit" style="display:none;" >
</form>
<script>
    <?=commonJS() ?>
</script>

<?php
if ($view == "search"){

	$ret = searchTables($filter);
	foreach ($ret as &$row){
		$table_name = $row['TABLE_NAME'];
		$row = rowMarkRed($row,$filter);

		$row['TABLE_NAME'] = '<a href="table.php?TABLE_NAME=' . $table_name . '">' . $row['TABLE_NAME'] . '</a>';
		//$row['ROWS_NOW'] = $CACHE_ARY['__TABLE_ROW_COUNTS'][$table_name];

		$row['DATA_MB'] = colorDBMegabyte($row['DATA_MB'] );
		$row['INDEX_MB'] = colorDBMegabyte($row['INDEX_MB'] );
		$row['TABLE_ROWS'] = colorDBColcount($row['TABLE_ROWS'] );
	}
	echo str150('Tables ' . count($ret) . ' <br/>') . asc2html($ret,false,false);

	$ret = searchColumns($filter);
	foreach ($ret as &$row){
		$table_name = $row['TABLE_NAME'];
		$row['COLUMN_COMMENT'] = strTrim($row['COLUMN_COMMENT'],70,true);
		$row = rowMarkRed($row,$filter);
		$row['TABLE_NAME'] = '<a href="table.php?TABLE_NAME=' .$table_name . '">' . $row['TABLE_NAME'] . '</a>';
	}
	echo str150('Columns ' . count($ret) . ' <br/>') . asc2html($ret,false,false);
}



function createMySQLSchemaStatCache($filter_tables,$filter_cols,$CACHE_FILE_NAME,$pdo){

	echo "create " . strCache($CACHE_FILE_NAME) . BR;
	$start = microtime(true);
	$CACHE_ARY = [];
	foreach ($filter_tables as $row){
		foreach($row as $val){
			$ret1 = searchTables($val,false);
			$CACHE_ARY[$val] = count($ret1) ;
		}
	}
	foreach ($filter_cols as $row){
		foreach($row as $val){
			$ret2 = searchColumns($val,false);
			$CACHE_ARY[$val] = count($ret2);
		}
	}

	$CACHE_ARY['__TABLE_ROW_COUNTS'] = [];
	$all_tables = searchTables('',false);
	foreach ($all_tables as $row){
		$ct = RDBtableRowCt($row['TABLE_NAME'],$pdo);
		$CACHE_ARY['__TABLE_ROW_COUNTS'][$row['TABLE_NAME']] = $ct;
	}
	$CACHE_ARY['__TABLE_ROW_COUNTS']['____table_counts'] = count($all_tables);
	$all_columns = searchColumns('',false);
	$CACHE_ARY['__TABLE_ROW_COUNTS']['____column_counts'] = count($all_columns);

	echo assocDump($CACHE_ARY['__TABLE_ROW_COUNTS']);

	file_put_contents($CACHE_FILE_NAME, serialize($CACHE_ARY));
	echo "<hr/>";
	echo date('h:i:s',intVal($start)) . '-' . date('h:i:s') . BR;
	echo round(microtime(true) - $start,2) .strSilver(' sec');
}

function searchTables($filter,$showSQL=true){
	global $pdo,$current_env;
	$filter = str_replace("_","\_",$filter);
	$sql1 = "select TABLE_NAME,'' SP_COMMENT,TABLE_COMMENT,TABLE_TYPE,ENGINE,'' ROWS_NOW,TABLE_ROWS,ROUND(DATA_LENGTH /1024/1024,2) DATA_MB ,ROUND(INDEX_LENGTH/1024/1024,2) INDEX_MB
	from information_schema.TABLES where TABLE_SCHEMA='" . $current_env['schema'] . "' ";
	if ($filter ) $sql1 .= " and (TABLE_NAME like '%". $filter ."%' or TABLE_COMMENT like '%". $filter ."%' or ENGINE like '%". $filter ."%'  )";
	$ret = sql2asc($sql1,$pdo,$showSQL);
	return $ret;
}
function searchColumns($filter,$showSQL=true){
	global $pdo,$current_env;
	$filter = str_replace("_","\_",$filter);
	$sql2 = "select TABLE_NAME,COLUMN_NAME,COLUMN_KEY CKEY,COLUMN_TYPE ,COLUMN_DEFAULT CDEF,EXTRA,COLUMN_COMMENT
		from information_schema.COLUMNS where TABLE_SCHEMA='" . $current_env['schema'] . "' ";
	if ($filter ) $sql2 .= " and (COLUMN_NAME like '%". $filter ."%' or EXTRA like '%". $filter ."%' or COLUMN_COMMENT like '%". $filter ."%' or COLUMN_TYPE like '%". $filter ."%'  )";
	$sql2 .= " order by COLUMN_NAME asc";
	$ret = sql2asc($sql2,$pdo,$showSQL);
	return $ret;
}
function colorDBMegabyte($val){
	if ($val < 1) return  strSilver($val);
	if ($val > 5) return  strBlue($val);
}
function colorDBColcount($val){
	if ($val < 1000) return strSilver($val);
	if ($val > 100000) return  strBlue($val);

}

<?php
include "_env.php";

$MENUS = ["links","tables","diff","sql","users","cards","grep","git","gdrive","docker","k8s","cache","shell","aws","aws2"];

$SHELL_HISTORY =[];
$SQL_HISTORY =[];

session_start();

date_default_timezone_set("Asia/Tokyo");

define('SPC','&nbsp;');
define('BR','<br/>');
define('BRBR','<br/><br/>');
define('LF',"\n");

// docker関連
if ($USE_DOCKER){
	$docker_shell = "ps aux | grep -i docker | grep -i serve | grep -v ps";
	$ret = trim(runShell($docker_shell,false) . "");
	if (!$ret ) exit( strBG($docker_shell) . BR . strRed("docker daemon not running"));
}


// env関連
if (! isset($_SESSION['current_env_name'])) $_SESSION['current_env_name'] = $ENV_DEFAULT;
$param_current_env_name = getRequest('current_env_name',false);
if ($param_current_env_name) {
	$_SESSION['current_env_name'] = $param_current_env_name;
}
if (!$_SESSION['current_env_name']) exit("no envname");
$current_env = $ENVS[$_SESSION['current_env_name']];
$datasource = "mysql:dbname=" . $current_env['schema'] . ";host=127.0.0.1;port=" . $current_env['port'] . ";";
$ADMIN_URL_BASE = $current_env['admin_url'];

// DB接続
$pdo = new PDO($datasource,$current_env['user'],$current_env['pass']);

// ajax vscodeで開く
$ajaxfinder = getRequest('ajaxfinder');
if ($ajaxfinder){
    $shell = 'open -a "Visual Studio Code" "' . rawurldecode($ajaxfinder) . '"' ;
    runShell($shell);
    exit();
}

// ----- 名前idセット キャッシュファイル  ---------------------------
$DATA = [];
$SCHEMA_CACHE_FILE_NAME = 'cache/id_name-' . $_SESSION['current_env_name'] . '.txt';
$expire_sec = 5000;

if (file_exists($SCHEMA_CACHE_FILE_NAME)){
	$DATA = unserialize(file_get_contents($SCHEMA_CACHE_FILE_NAME));
}else{

	$DATA['user_id'] = RDBgetColStatAry('users',$pdo,'id','name',10);
	$DATA['card_id'] = RDBgetColStatAry('cards',$pdo,'id','name',10);
	$DATA['expired_type'] = [1 => "期限日",2=>'付与日'] ;
	$DATA['rich_pattern'] = [0 => 'なし', 1 => 'クーポン',2 => '予約フォーム' , 3=> 'URL'];
	$DATA['role'] = [0 => 'ADMIN' ,1 => 'ADVISER' ,2 => 'SHOP' ];

	$sql1 = "select count(*) ct from information_schema.TABLES where TABLE_SCHEMA='" .$current_env['schema'] . "' ";
	$ret = sql2asc($sql1,$pdo,false);
	$DATA['table_ct']['tables'] = $ret[0]['ct'];

	$sql1 = "select count(*) ct from information_schema.COLUMNS where TABLE_SCHEMA='" .$current_env['schema'] . "' ";
	$ret = sql2asc($sql1,$pdo,false);
	$DATA['table_ct']['columns'] = $ret[0]['ct'];
	$DATA['table_ct']['users'] = RDBtableRowCt('users',$pdo);
	$DATA['table_ct']['cards'] = RDBtableRowCt('cards',$pdo);

	file_put_contents($SCHEMA_CACHE_FILE_NAME,serialize($DATA));
}

function setNames2Rec($recs, $table_name = "" ){
    global $DATA;
    foreach ($recs as &$row){
        foreach ($row as $colname => &$colvalue){
			// colnameだけでマッチ
            if (isset($DATA[$colname]) ){
				if ($colvalue != null or $colvalue != ""){
					if (isset($DATA[$colname][$colvalue])){
						$colvalue .= ' ' . strSilver($DATA[$colname][$colvalue]);
					}else{
						$colvalue .= ' ' . strRevRed('no master');
					}
				}
            }
			//table名 + colnameでマッチ
            if (isset($DATA[$table_name . '.'.  $colname]) ){
				if ($colvalue){
					if (isset($DATA[$table_name . '.'.  $colname][$colvalue])){
						$colvalue .= ' ' . strSilver($DATA[$table_name . '.'.  $colname][$colvalue]);
					}else{
						$colvalue .= ' ' . strRevRed('no master');
					}
				}
            }
		}
    }
    return $recs;
}

function menu(){
	global $DATA,$ENVS,$current_env,$MENUS;

	foreach ($ENVS as $envname => $row){
		$disp = $envname;
		if ($envname == $_SESSION['current_env_name']) $disp = strRed($disp);
		echo '<a href="?current_env_name=' . $envname . '">' . $disp . '</a> ';
	}
	echo " " . strGray($current_env['schema']);
	echo BR;

	// メニューと件数
	foreach ($MENUS as $val){
		$disp = $val;
		if (strpos($_SERVER['PHP_SELF'],$val) !== false) $disp = strBold($val);
		?><a href="<?=$val ?>.php"><?=$disp ?></a><?php
		if (isset($DATA['table_ct'][$val])) echo strGray($DATA['table_ct'][$val]) . ' ';
	}

    ?>
    <hr/>
    <?php
}

function RDBtableRowCt($table_name,$pdo){
	$sql3 = "select count(*) ct from `" .$table_name. "`";
	$ret = sql2asc($sql3,$pdo,false);
	return $ret[0]['ct'];
}

function RDBcountPerCol($table,$col,$pdo){
	$sql1 = "select ". $col .",count(*) ct from " . $table . " group by " . $col;
	$ret = sql2asc($sql1,$pdo,false);
	$ret = setNames2Rec($ret,$table);
	return $ret;
}
function RDBcountPerColRaw($table,$col,$pdo){
	$sql1 = "select ". $col .",count(*) ct from " . $table . " group by " . $col;
	$ret = sql2asc($sql1,$pdo,false);
	return $ret;
}

function RDBgetColStatAry($table_name, $pdo, $id_col = "id", $name_col = "name" ,$trim_ct = null){
	$sql3 = "select " . $id_col . "," . $name_col . " from `" . $table_name . "`";
	$ret = sql2asc($sql3,$pdo,false);
	$idnames = [];
	foreach ($ret as $row) {
		$idnames[$row[$id_col]] = $row[$name_col];
		if ($trim_ct) $idnames[$row[$id_col]] = strTrim($idnames[$row[$id_col]],$trim_ct);

	}
	return $idnames;
}


// 取得結果の配列を
function statColOfRecord($records ,$colname){
	$ret = [];
	foreach ($records as $row){
		if (!isset($ret[$row[$colname]])) $ret[$row[$colname]] = 0;
		$ret[$row[$colname]]++;
	}
	return $ret;
}

function pre($str){
	return '<pre>' . $str . '</pre>';
}

//一項目をキーワード（ひとつ）で赤に
function markRed($val,$keyword,$colorName="crimson"){

	if (!$keyword) return $val;
	if (!$val) return $val;
	return preg_replace("@(".preg_quote($keyword).")@i","<span style='color:".$colorName."'>$1</span>",$val);
}

//配列in配列のvalueをキーワード(ひとつ)で赤に
function rowMarkRed($row,$keyword,$colorName="crimson",$ignore_cols=[]){ //ignore..は 配列で来る
	$ignores = [];
	//valueできたら配列に、配列はキーに
	$ignore_cols = (array)$ignore_cols;
	foreach($ignore_cols as $colname) $ignores[$colname]='ignore';

	if (!$keyword) return $row;

	foreach ($row as $key => &$val){
		if (!isset($ignores[$key])) $val = markRed($val,$keyword,$colorName);
	}

	return $row;
}

//配列in配列のvalueをキーワード(ひとつ)で赤に
function assocMarkRed($array,$keyword,$colorName="crimson",$ignore_cols=[]){ //ignore..は 配列で来る
	$ignores = [];
	//valueできたら配列に、配列はキーに
	$ignore_cols=(array)$ignore_cols;
	foreach($ignore_cols as $colname) $ignores[$colname]='ignore';

	if (!$keyword) return $array;

	foreach ($array as &$row){
		foreach ($row as $key => &$val){
			if (!isset($ignores[$key])) $val = markRed($val,$keyword,$colorName);
		}
	}
	return $array;
}

function statExt($filePathList){

    $ext_stat = [];
    foreach ($filePathList as $line){
        $info = pathinfo($line);
		if ($info && isset($info['extension'])){
			if (!isset($ext_stat[$info['extension']])) $ext_stat[$info['extension']]=0;
			$ext_stat[$info['extension']]++;
		}

    }
    return $ext_stat;
}

function sqliteConnect($path,$show_debug = false){

	if (file_exists($path) ){
		$str = strBlue('SQLite Exist<br/>');
	}else{
		$str = strRed('SQLite Generate<br/>');
	}
	if ($show_debug) echo $str;
	$pdo = new PDO('sqlite:' . $path);
	return $pdo;
}

function assocDump($assoc){  //連想配列をHTMLテーブルでダンプ1階層のみ
	$ret = "\n<table>";
	foreach ($assoc as $key=>$value)  {
		if (is_array($value)){
			$ret.= "<tr><td style='border-bottom: 1px solid silver;'>" . $key. "</td><td style='border-bottom: 1px solid silver;'>";
			$ret .= print_r($value,true);
			$ret.= "</td></tr>";
		}else{
			if (!$value or !strlen((string)$value)) {
				$value="<span style='color:silver; font-style:italic;'>null</span>";
			}
			$ret.= "<tr><td style='border-bottom: 1px solid silver; white-space:nowrap;'>" . $key . "</td><td style='border-bottom: 1px solid silver; white-space:nowrap;'>" . $value . "</td></tr>";
		}
	}
	$ret.= "</table>";
	return $ret;
}

function assocDumpNoKey($assoc){  //連想配列をHTMLテーブルでダンプ1階層のみ
	$ret = "\n<table>";
	foreach ($assoc as $key=>$value)  {
		if (is_array($value)){
			$ret.= "<tr><td style='border-bottom: 1px solid silver;'>";
			$ret .= print_r($value,true);
			$ret.= "</td></tr>";
		}else{
			if (!$value or !strlen((string)$value)) {
				$value="<span style='color:silver; font-style:italic;'>null</span>";
			}
			$ret.= "<tr><td style='border-bottom: 1px solid silver; white-space:nowrap;'>" . $value . "</td></tr>";
		}
	}
	$ret.= "</table>";
	return $ret;
}

function runShell($shell,$showDump=true){ //shell実行、コマンド出力
	global $SHELL_HISTORY,$STDERR_PATH ;
	$SHELL_HISTORY[] = $shell;

	if ($showDump) echo strGrayBG(nl2br($shell));
	$shell = $shell . " 2>" . $STDERR_PATH;
	$ret = [];
	$result_code = exec($shell,$ret). "";
	//$ret = `$shell` . "";
	$return = trim(implode("\n",$ret)) ;
	$err = file_get_contents($STDERR_PATH);
	if ($err) $return = strRed(nl2br(file_get_contents($STDERR_PATH)). "") . $return;
	return $return;
}
function runShellAry($shell,$showDump=true){ //shell実行、コマンド出力
	global $SHELL_HISTORY;
	$SHELL_HISTORY[] = $shell;

	$str = trim(`$shell` . "");
	$ary = explode("\n",$str);
	if (!$ary) $ary =[];
	if ($showDump) echo strGray(nl2br($shell)) . ' ' . strPink(count($ary)) . "<br/>";
	return $ary;
}

function htmlHeader($title){
    ?>
    <head>
        <meta http-equiv="content-language" content="ja" charset="UTF-8">
        <title><?=$title ?></title>
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
		<link rel="icon" href="favicon.ico">
        <style>
            body{color:#666; font-family:sans-serif,helvetica; }
            a:link{color:dodgerblue; text-decoration:none; padding:2px; }
            a:visited{color:dodgerblue; text-decoration:none; }
            a:hover{color:dodgerblue; text-decoration:underline; opacity:0.6; }

			th {font-weight:normal; color:#bbb; border-bottom: 1px solid gray; color:gray; padding-right:10px; }
			td {border-bottom: 1px solid silver ; padding-right:5px; text-align:left; vertical-align:top; white-space:nowrap; }
            .flex-item {margin-left:15px; margin-bottom:20px; }
            .flex-container {display: flex; flex-wrap:wrap; }
			a.history-link { color:orange !important; background:#fee; padding:3px; }
			a.cache-link { color:deeppink !important; background:#fee; padding:3px; } /* キャッシュや一時集計ファイルの更新 */
			a.admin-link { color:green !important; background:#efe; padding:3px; }
			a.upd-link { color:orange !important; }

			.linename {  background:#f4f4f4; padding:3px;}
			.tablename { color:blue !important; background:#eef;padding:2px; }
			.table-def { color:darkslateblue !important; background:#eef;padding:2px; }

			.td1 { white-space:nowrap;}

			.alert { color:red; !important; background:#fee;padding:2px; }
        </style>
    </head>
    <?php
}

function getRequest($index,$isRequire=false,$default=""){
	if ($isRequire && (!isset($_REQUEST[$index]) || $_REQUEST[$index]=="" )) exit ('request param need ' . $index);
	return isset($_REQUEST[$index]) ? $_REQUEST[$index] : $default;
}

//sqlを改行消してトリム クリックすると全文表示
function jsTrim($str,$count=140,$encode='utf-8'){

    //return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    if (isset($GLOBALS['trim_count'])) $count = $GLOBALS['trim_count'];

	$trimed=mb_strimwidth($str,0,$count) . "..";
	$trimedEnc=htmlentities($trimed,ENT_QUOTES,$encode);

	if ($trimed!=$str) $trimedEnc="<span style='color:Plum;'>trim </span>".$trimedEnc."...";
	//クリックしたら改行を反映した元ソースを表示
	return "<span title='".htmlentities($str,ENT_QUOTES,$encode)."' onClick='".
	'if ($(this).attr("clicked")!="true") {
		$(this).text($(this).attr("title"));
		$(this).html($(this).html());} '.
		'$(this).attr("clicked","true");'.
	"'>".$trimedEnc."</span>";

}

function strTrim($str,$count=20,$show_trim_ct = false){
	if (!$str) return $str;
    $str2 = $str;
	if (mb_strwidth($str2) > $count) {
		$str2=mb_strimwidth($str,0,$count) . "..";

		// 半角文字が多ければ削らないようにしたい
		if ($show_trim_ct ) $str2 .= strOrange(mb_strwidth($str) - $count);
	}
	return $str2;
}

// 更新系sqlExec
function sqlExec($sql, $pdo, $flgEcho=true){ //SQL,DB接続,SQL表示フラグ

	assertPDO($pdo);
    try{
		$statement = $pdo->prepare($sql);

		// sqlエラーをキャッチ
		if ($pdo->errorInfo()[1] != null){
			echo strSilver($sql) . '<br/>';
			echo " " .strRed($pdo->errorInfo()[2]) . '<br/>';
			return;
		}
		if ($statement === false) return;
		$result = $statement->execute();

		// sqlエラーをキャッチ
		if ($pdo->errorInfo()[1] != null){
			echo strSilver($sql) . '<br/>';
			echo " " .strRed($pdo->errorInfo()[2]) . '<br/>';
			return;
		}

    	if ($flgEcho) echo jsTrim($sql)." &nbsp; <span style='color:Plum;'>" .
    					   $statement->rowCount()."&nbsp;" ."&nbsp; </span><br/>";

    	if (!$result) {
    		$info = $statement->errorInfo();
    		echo strRed($info[0] . ' ' . $info[1] . ' ' . $info[2]) . '<br/>';
    	}
   		// 件数表示
   		if ($flgEcho) echo "affected rows " . $statement->rowCount() . '<br/>';

   		return $statement->rowCount();

    }catch(PDOException $e) {
    	echo "error ";
        echo strGray(jsTrim($sql)) . "<br/>" . strRed($e->getMessage()) . "<br/>";
    }
}

//SQL発行して配列で取得。selectのみ
function sql2asc($sql,$pdo,$flgEcho = true, $limitCount=false){ //SQL,DB接続,SQL表示フラグ

	assertPDO($pdo);
    $GLOBALS['sql_his'][]=$sql;

	$timeStart = microtime(true);
	//SQL表示
	if ($flgEcho) echo strSilver(jsTrim($sql));

	try{
		$result = $pdo->query($sql) ;
		$timeUsed=round(microtime(true)-$timeStart,3);
	//SQLエラー処理
  	}catch(PDOException $e) {
        echo strRed($e->getMessage())."<br/>";
		return ;
	}

	//select結果
    $assoc= [];
    if ($result){
		foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$assoc[]=$row;
			//規定limit件数で配列化終了
			if ($limitCount==true and count($assoc)>=$limitCount){
				$msgLimitrow= strRed("limit");
				break;
			}
		}
    }

    if ($flgEcho) echo " &nbsp; <span style='color:Plum;'>".count($assoc) . " &nbsp; " .$timeUsed."</span><br/>";

	// sqlエラーをキャッチ
	if ($pdo->errorInfo()[1] != null){
		echo strSilver($sql) . '<br/>';
		echo " " .strRed($pdo->errorInfo()[2]) . '<br/>';
	}

    return $assoc;
}

function ascHTMLEnc($records){
	foreach($records as &$row){
		foreach($row as &$val){
			if ($val) $val = htmlentities($val);
		}
	}
	return $records;
}

//変換：連想配列 > HTMLテーブル  //htmlencode するとtext attrはセットしない
function asc2html($assoc){ //クエリ結果連想配列

	$return="";
	if (count($assoc)==0) return;

	$return.= "\n<table >\n ";

	//ヘッダ表示
	$return.= " <tr >";
	foreach ($assoc as $row) {
		foreach ($row as $key => $value){
			$disp = $key;
			if (substr($key,0,1) == "_") $disp = strOrange($disp);
			$return.= "  <th align='left' style=''>" . $disp . "</th>\n";
		}
		break;
	}
	$return.= "</tr>";
	foreach ($assoc as $row){
		$return.= "    <tr>\n";
		foreach ($row as $key => &$value){
			if ($value === null) { //nullと空白を区別
				$value = "<span style='color:silver; font-style:italic; '>null</span>";
   			}
			$return.= '   <td class="td1">'.$value."</td>\n";
		}
		$return.= "    </tr>\n";
	}
	$return.= "</table>\n";
	return $return;
}

// assert
function assertPDO($pdo){
	if (!is_object($pdo)) {
		echo strRed('"'.__FUNCTION__.'" MySQL pdo link erorr<br/>');
		debug_print_backtrace();
		exit();
	}
}
// 0件だったら終了
function assertZero($msg,$array){
	if (!$array) {
		echo strRed($msg . BR . ' count 0 fail<br/>');
		debug_print_backtrace();
		exit ();
	}
	return $array;
}


function bringColFront($array,$colname){
	if (!array_key_exists($colname,$array)) return $array;
	$val = $array[$colname];
	unset ($array[$colname]);
	$array = array_merge([$colname => $val],$array);

	return $array;
}

function recentRedCol($array,$colname,$date_str_border){
	if (!array_key_exists($colname,$array)) return $array;
	if (!$array[$colname]) return $array;

	if (strtotime($array[$colname]) > strtotime($date_str_border)){
		$array[$colname] = strRed($array[$colname]);
	}else{
		$array[$colname] = strSilver($array[$colname]);
	}
	return $array;
}

// リンク押したら詳細テキストが開く
function link2detail($name,$html){

    $id = rand(10000,50000);
    $ret = '<a href="javascript:$(\'#' . $id . '\').slideToggle(60)" >' . strOrangeBG($name) . '</a>';
    $ret .= '<pre id="' . $id . '" style="background:#f8f8f8; padding:3px; border-radius:5px; display:none; ">' . $html . '</pre>';
    return $ret;
}

?>
function

<?php

// ペアで使う
function l2dLink($name){
	// 開いてるdetailで自分以外全部閉じ、自分をtoggle
    $ret = '<a href="javascript:$(\'pre[id!=' .$name. ']\').hide(0);
				$(\'#' . $name . '\').slideToggle(50);" >' . strOrangeBG($name) . '</a>';
    return $ret;
}
function l2dDetail($name,$html){
    $ret = '<pre id="' . $name . '" group="details" style="background:#f8f8f8; padding:3px; border-radius:5px; display:none; ">' . $html . '</pre>';
    return $ret;
}



function debugFooter(){
	echo '<hr/>';
	echo link2detail("params","GET" . AssocDump($_GET) . "POST" . assocDump($_POST));
}

// js
function commonJS(){
	?>
	function setVal(name,val){
        $('#' + name).val(val)
        $('#f1').submit()
    }
    $('input:checkbox').click(()=>{
        $('#f1').submit()
    })
    $('input').dblclick((obj) =>{
        $(obj.target).val('')
        $('#f1').submit()
    })
	$('#filter').focus()
	<?php

}

//色 /背景
function strBG($str) { return "<span style='color:#444444; background:#eee; padding:1px;'>".$str."</span>";}
function strWhite($str) { return "<span style='color:#FFFFFF;'>".$str."</span>";}
function strPink($str) { return "<span style='color:deeppink;'>".$str."</span>";}
function strPinkBG($str) { return "<span style='color:deeppink; background:#fee;'>".$str."</span>";}
function strBlack($str) { return "<span style='color:#444444;'>".$str."</span>";}
function strBlackBG($str) { return "<span style='color:#444444; background:#eee; padding:1px;'>".$str."</span>";}
function strBlue($str) { return "<span style='color:#4444DD;'>".$str."</span>";}
function strBlueBG($str) { return "<span style='color:#4444DD; background:#eef; padding:1px;'>".$str."</span>";}
function strDodger($str) { return "<span style='color:dodgerblue;'>".$str."</span>";}
function strDodgerBG($str) { return "<span style='color:dodgerblue; background:#eef; padding:1px;'>".$str."</span>";}

function strYellow($str) { return "<span style='color:yellow;'>".$str."</span>";}
function strRed($str)  { return "<span style='color:red;'>".$str."</span>";}
function strGreen($str) { return "<span style='color:DarkGreen;'>".$str."</span>";}
function strGreenBG($str) { return "<span style='color:DarkGreen; background:#efe;'>".$str."</span>";}
function strDarkred($str) { return "<span style='color:crimson;'>".$str."</span>";}
function strGray($str) { return "<span style='color:gray;'>".$str."</span>";}
function strGrayBG($str) { return "<span style='color:gray; background:#eee; padding:1px;'>".$str."</span>";}
function strSilver($str) { return "<span style='color:silver;'>".$str."</span>";}
function strOrange($str) { return "<span style='color:darkOrange;'>".$str."</span>";}
function strOrangeBG($str) { return "<span style='color:darkOrange; background:#fed; padding:1px;'>".$str."</span>";}

// 色 反転
function strRevBlue($str)   { return "<span style='color:white; background-color:blue;'>".$str."</span>";}
function strRevYellow($str) { return "<span style='color:white; background-color:yellow;padding:3px;'>".$str."</span>";}
function strRevRed($str)    { return "<span style='color:white; background-color:red;padding:3px;'>".$str."</span>";}

// 色 背景のみ
function bgBlue($str) { return "<span style='background-color:#4444DD;'>".$str."</span>";}
function bgYellow($str) { return "<span style='background-color:yellow;'>".$str."</span>";}
function bgRed($str)  { return "<span style='background-color:red;'>".$str."</span>";}

//サイズ
function str80($str) { return "<span style='font-size:80%;'>".$str."</span>";}
function str120($str) { return "<span style='font-size:120%;'>".$str."</span>";}
function str150($str) { return "<span style='font-size:150%;'>".$str."</span>";}
// 装飾 インデント
function strBold($str) { return "<span style='font-weight:bold;'>".$str."</span>";}
function strCenter($str){ return "<div style='text-align:center;'>".$str."</div>";}

function strCode($str){ return "<div style='font-family:Courier New; background:#f8f8f8;'>". $str."</div>"; }

// 目的別
function strZeroSilver($val){
    if ($val ==0) return strSilver($val);
    return $val;
}
function assocZeroSilver($records){
	foreach ($records as &$row){
		foreach ($row as $key => &$val){
			$val = strZeroSilver($val);
		}
	}
	return $records;
}

// このプログラムでの設定
function strCache($str){ return strOrangeBG($str); }
function strTableName($str){ return "<span class='tablename'>".$str."</span>";}
function strTitle($str) { return "<span style='font-family:arial,helvetica,sans-serif;'>".$str."</span>";}


?>
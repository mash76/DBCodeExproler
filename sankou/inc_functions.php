<?php
date_default_timezone_set("Asia/Tokyo"); 

/*
定数

time系
sql系
assert系
dump系
文字列系
通信系
escape系
os系
*/

//ver 20161228

// $GLOBALS['sql_his']      sqlToAssoc
// $GLOBALS['trim_count']   jsTrim

define('BR','<br/>');
define('BRBR','<br/><br/>');
define('LF',"\n");

function getRequest($index,$isRequire=false,$default=""){
	if ($isRequire && (!isset($_REQUEST[$index]) || $_REQUEST[$index]=="" )) exit ('request param need ' . $index);

	return isset($_REQUEST[$index]) ? $_REQUEST[$index] : $default;
}
function setSession($index,$value,$default){
	if (!isset($_SESSION[$index] )) $_SESSION[$index]  = $default;
	if ($value) $_SESSION[$index] = $value;
}

//ダンプ出力系処理 csv,tsv,insert sqlの束を受け取る
function sqlDump($arySQL,$link,$outType){

    $arySQL=(Array)$arySQL;//配列でなければ配列化
    $sql_order=0;
    //検索結果
    foreach ($arySQL as $key=>$value){

        if (!trim($value)) continue;

        $sql_order++;
        //通常SQL実行
        if ($outType=='execute' or $outType=='author' or !$outType) {
        	echo strBold($sql_order.". ");
            $array=assocTrimChar(sql2asc(trim($value),$link,true,$_SESSION['mysql_limit']),$_SESSION['mysql_trim']);
            echo asc2html($array,"SQL");
            echo "<br/>";
        }
        if ($outType=='indent') {
            //echo "indent";
        }
        //distinct
        if ($outType=='distinct') echo asc2html(sql2asc($value, $link,true,$_SESSION['mysql_limit']),"DISTINCT");
        //CSV出力
        if ($outType=='csv')    echo assocToText(addHeaderToAssoc(sql2asc($value, $link,false,false)),",");//*task
        if ($outType=='csvquote') {//*task 禁止文字来たら警告
            $assocCSV=sql2asc($value, $link,false,false);
            foreach($assocCSV as $key=>$value){
                $rowQuoted=null;
                foreach($value as $key2=>$value2)    $rowQuoted[$key2]='"'.$value2.'"';
                $assocCSVQuoted[$key]=$rowQuoted;
            }
            echo assocToText(addHeaderToAssoc($assocCSVQuoted,","));
        }
		
		//TSV INSERT EXPLAIN PROFILE
        if ($outType=='tsv')         echo assocToText(addHeaderToAssoc(sql2asc($value, $link,false,false)),"\t");//*task
        if ($outType=='insertout')   echo assocToInsert(sql2asc($value,$link,false,false),$_REQUEST['TABLE_NAME']);
        if ($outType=='hexdump')     echo asc2html(HexDumpAssoc(sql2asc($value, $link,true,$limit)),"SQL");
        if ($outType=='tabcontain')  echo asc2html(checkCTRLContain(sql2asc($value, $link,true,$limit)),"SQL");
        if ($outType=='explain')     echo asc2html(sql2asc('explain '.$value, $link),'EXPLAIN');
        if ($outType=='viewer')     {
			$records=sql2asc($value, $link);
			if ($_REQUEST['mark']) $records=assocMarkRed($records,$_REQUEST['mark']);
			echo asc2html($records,'viewer',false)."<br/>";
		}
		if ($outType=='profile') {
            sql2asc('set profiling=1', $link);
            sql2asc($value, $link);
            echo asc2html(sql2asc("show profile;", $link),'PROFILE');
        }
    }
}


// -----  assert系  ------------------------------------------------------------------------------------------------

// assert
function assertPDO($pdo){
	if (!is_object($pdo)) {
		echo strRed('"'.__FUNCTION__.'" MySQL pdo link erorr<br/>');
		debug_print_backtrace();
		exit();
	}
}
// 0件だったら終了
function assertZero($array){
	if (!$array) {
		echo strRed('count 0 fail<br/>');
		debug_print_backtrace();
		exit ();
	}
}

function assert1( $exp , $msg ){
	if ($exp) exit($msg);	
}

// 時間計測系
function timeHakaru($tag)
{
    //初回
    if (!isset($GLOBALS['timer'])) {
        $GLOBALS['timer_prev_static'] = microtime(true);
        $GLOBALS['timer']=[];
        $GLOBALS['timer'][] = array( 'tag' => $tag , 'time' => 'novalue');
    } else {
        //毎回
        $GLOBALS['timer'][ count($GLOBALS['timer'])-1 ]['time'] = round(microtime(true) - $GLOBALS['timer_prev_static'], 3);
        $GLOBALS['timer'][] = array( 'tag' => $tag , 'time' => 0);
        $GLOBALS['timer_prev_static'] = microtime(true);
    }
}

function timeDump()
{
    $total = 0;
    foreach ($GLOBALS['timer'] as $row) {
        $total += $row['time'];
    }
    $GLOBALS['timer'][] = array( 'tag' => 'total' , 'value' => $total);
    foreach ($GLOBALS['timer'] as &$row) {
        $row['percent'] = round($row['time'] / $total, 2);
    }
    
    echo '<h3>Time</h3>' . asc2html($GLOBALS['timer']);
}




function minusRed($var){
	$ret = '';
	if ($var < 0 ) $ret = strRed($var);
	if ($var >= 0 ) $ret = strBlue($var);
	return $ret;
}


// 時間計測globalへんすうに溜め込んでいく
function timeSet($word){
	global $time_stat;
	if (!$time_stat) {
		$time_stat[]= [ 'name' => 'start','microtime' =>microtime(true) , 'pass' => 0];
	}
	$prev = $time_stat[count($time_stat) -1];
	$time_stat[count($time_stat) -1]['pass'] = round( microtime(true) - $prev['microtime'],2);
	$time_stat[] = [ 'name' => $word,'microtime' =>microtime(true) , 'pass' => round(microtime(true) - $prev['microtime'] ,2) ];
}

// 日付を [あとn日] の表示に     
function timeAto($target_time,$now = null){

    if (!$now) $now=time();
    //文字列で来たらunixtimeに置き換え
    if (preg_match("/(:|-)/",$target_time)) $target_time=strtotime($target_time);

    if ($target_time > $now) { //未来
      $seconds=$target_time-$now;
      $ret="あと";
    }else{ 
      $seconds=abs($target_time-$now);
      $ret="経過";
    }

    if ($seconds>86400) $ret.=floor($seconds/86400)."日 ";
    if ($seconds>3600) $ret.=floor($seconds % 86400 /3600)."時間 ";
    if ($seconds>60) $ret.=floor($seconds % 3600 /60)."分 ";
    return $ret;
}

//画像検索
function schImages($path,$keyword,$filetype_re="",$showcount=50){

    if (!$filetype_re) $filetype_re='(gif$|jpg$|jpeg$|png$)';

    //imageファイルがなければ停止 
    $viewer_php=dirname(__FILE__)."/image.php";
    if (!file_exists($viewer_php)) exit(strRed("no ".$viewer_php));

    $counttmp=0;
    $shell="find ".$path." -type f | grep -E '".$filetype_re."'"; 
    //$shell="find ".$path." -type f"; 

    if (isset($keyword)) $shell.=" | grep '".$keyword."'";
    echo "<font color=gray>".$shell."</font><br/><br/>";
    $strImages=trim(`$shell`);
    $aryImages=explode("\n",$strImages);
    $count=count($aryImages);

    echo $count."件<br/>";

    //検索した画像一覧
    echo "<table border=0><tr>";
    foreach($aryImages as $path){
        echo "<td valign=top><hr/>";
        echo strGray($path)."<br/>";
        echo "<img src='image.php?path=" . urlencode($path) . "' /><br/>";
        echo "</td>";
        
        if ($counttmp % 5==0) echo "</tr><tr>";
        $counttmp++;
    }
    echo "</tr></table>";
}

function encolorLimitDate($strDate){ //2012-01-01 23:59:59 など日付受け取り、近いもの色付け 1時間以内=赤太字   本日中=赤

	$timeSecondPast=strtotime($strDate)-time();
	if ($timeSecondPast < 10)	return "<span style='background-color:red; color:white; font-weight:bold;'>".$strDate."</span>";
	if ($timeSecondPast < 3600)	return "<span style='color:red; font-weight:bold;'>".$strDate."</span>";
	if (date("Y-m-d",$timeSecondPast) == date("Y-m-d")) return "<span style='font-weight:bold;'>".$strDate."</span>";
	if ($timeSecondPast < 86400) return $strDate;
	return "<span style='color:dimgray;'>".$strDate."</span>";
}

function encolorRecentDate($strDate, $option="short"){ //2012-01-01 23:59:59 など日付受け取り、近いもの色付け 10分内=赤太字 60分内=赤 24H内=黄色

	$timeSecondPast=time()-strtotime($strDate);
	$ret_str = $strDate;
	if ($option == "short") {
		$ret_str =date('m-d',strtotime($strDate));
		if (date('d') == date('d',strtotime($strDate)))  $ret_str =date('H:i',strtotime($strDate));		
	}
	if ($timeSecondPast < 300)	return "<span style='color:red; font-weight:bold; '>".$ret_str."</span>";
	elseif ($timeSecondPast < 500)	return "<span style='color:red; font-weight:bold;'>".$ret_str."</span>";
	elseif ($timeSecondPast < 1800)	return "<span style='color:red;'>".$ret_str."</span>";
	elseif ($timeSecondPast < 3600*2)	return "<span style='font-weight:bold;'>".$ret_str."</span>";
	elseif ($timeSecondPast < 86400)	return $ret_str;
	return "<span style='color:dimgray;'>".$ret_str."</span>";
}

function assocHTMLEnc($assoc,$encode='utf-8'){   //配列in配列をhtmlEncode
	foreach ($assoc as &$row) {
		foreach ($row as &$value) $value=htmlentities($value,ENT_QUOTES,$encode);
	}
	return $assoc;
}

function checkCTRLContain($assoc){ //ctrlコードが入っていたら表示,**task 文字列型の列だけ確認したい。日付文字列などが入っていると面倒。スキーマ渡すか。注意書きで対処
  $return=[];
	foreach ($assoc as &$row){
    $return[]=$row;//旧行
		foreach ($row as $key => &$value){
      $contains="";
      if (strpos($value," ")  !== false) $contains.="SPC ";   //SPC
      if (strpos($value,'\\') !== false) $contains.="5C=YEN ";//CR
      if (strpos($value,"'")  !== false) $contains.="['] ";   //CR
      if (strpos($value,'"')  !== false) $contains.='["] ';   //CR
      if (strpos($value,"\r") !== false) $contains.="CR ";    //CR
      if (strpos($value,"\n") !== false) $contains.="LF ";    //LF
      if (strpos($value,"\t") !== false) $contains.="TAB ";   //Tab
      $value=$contains;
    }
    $return[]=$row;//新行 チェック結果
  }
  return $return;
}


function HexDumpAssoc($assoc,$encode='utf-8'){   //16進ダンプ
  $return=array();
	foreach ($assoc as &$row){
    $return[]=$row;//旧行
		foreach ($row as $key => &$value){
      $value=bin2hex($value)." (Hex ".strlen($value)." byte  ".mb_strlen($value,$encode)." char)";
    }
    $return[]=$row;//新行
  }
  return $return;
}

function sqlSeikei($strSql){   //SQL整形 SQLテキストをselect update insert fromで見やすく改行

	//複数のスペースやタブならスペース1に変換してしまうか？
	$strSql=str_ireplace("SELECT ","\nSELECT ",$strSql);
	$strSql=str_ireplace("UPDATE ","\nUPDATE ",$strSql);
	$strSql=str_ireplace("INSERT ","\nINSERT ",$strSql);

	$strSql=preg_replace("/\s+WHERE\s+/i","\nWHERE ",$strSql);
	$strSql=preg_replace("/\s+AND\s+/i","\nAND ",$strSql);
	$strSql=preg_replace("/\s+FROM\s+/i","\nFROM ",$strSql);
	$strSql=preg_replace("/\s+ORDER\s+/i","\nORDER ",$strSql);
	$strSql=preg_replace("/\s+GROUP\s+/i","\nGROUP ",$strSql);
	$strSql=preg_replace("/,/",",\n  ",$strSql);
	return $strSql;
}

//SQLから配列:mysql
function sql2asc($sql,$pdo,$flgEcho = true, $limitCount=false){ //SQL,DB接続,SQL表示フラグ

	assertPDO($pdo);
    $GLOBALS['sql_his'][]=$sql;

	$timeStart=microtime(true); 
	//SQL表示
	if ($flgEcho) echo strGray(jsTrim($sql));

	try{
		$result = $pdo->query($sql) ;
		$timeUsed=round(microtime(true)-$timeStart,3);
	//SQLエラー処理
  	}catch(PDOException $e) {
        echo strRed($e->getMessage())."<br/>";
	}
	//insertUpdateDelete 成功
	if ($result === true) {
    	if ($flgEcho) echo " &nbsp; <span style='color:Plum;'>" . 
    					   mysql_affected_rows($link) . " &nbsp; " . $timeUsed."&nbsp; </span><br/>";
		return $result;
 	}

	//$result insertならtrue/false  select ならオブジェクト返す
    
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

// 更新系sql
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
    	echo "error";
        echo strGray(jsTrim($sql)) . "<br/>" . strRed($e->getMessage()) . "<br/>";
    }
}

//SQLから配列
function sqlLiteToAssoc($sql,$link,$flgEcho=true,$limitCount=false){ //SQL,DB接続,SQL表示フラグ

    $assoc=[];
    $msgLimitrow = "";
	$timeStart=microtime(true);

	$stmt = $link->prepare($sql);
	$result = $stmt->execute();

	$timeUsed=round(microtime(true)-$timeStart,3);

	//SQL表示
	if ($flgEcho) echo strGray(jsTrim($sql)) . " &nbsp;".count($assoc) . "$msgLimitrow &nbsp;" . $timeUsed . "<br/>";


	//select結果
	$assoc = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $assoc;
}


function statColOfRecords($records,$col_name){

    $ext_stat = [];
    foreach ($records as $row){
		if (!isset($ext_stat[$row[$col_name]])) $ext_stat[$row[$col_name]]=0;
		$ext_stat[$row[$col_name]]++;
    }
    return $ext_stat;
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

//sqlを改行消してトリム クリックすると全文表示
function jsTrim($str,$count=140,$encode='utf-8'){
	
    //return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    if (isset($GLOBALS['trim_count'])) $count=$GLOBALS['trim_count'];
    
	$trimed=substr($str,0,$count);
	$trimedEnc=htmlentities($trimed,ENT_QUOTES,$encode);
	
	if ($trimed!=$str) $trimedEnc="<span style='color:Plum;'>trim </span>".$trimedEnc."......";
	//クリックしたら改行を反映した元ソースを表示
	return "<span title='".htmlentities($str,ENT_QUOTES,$encode)."' onClick='".
	'if ($(this).attr("clicked")!="true") {
		$(this).text($(this).attr("title"));
		$(this).html($(this).html());} '.
		'$(this).attr("clicked","true");'.
	"'>".$trimedEnc."</span>";

}

function jsTrimNoEnc($str,$count=140,$encode='utf-8'){
	
    //return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    if (isset($GLOBALS['trim_count'])) $count=$GLOBALS['trim_count'];
    
	$trimed=substr($str,0,$count);
	$trimedEnc=htmlentities($trimed,ENT_QUOTES,$encode);
	
	if ($trimed != $str) $trimedEnc="<span style='color:Plum;'>trim </span>".$trimedEnc."......";
	//クリックしたら改行を反映した元ソースを表示
	return "<span title='".$str."' onClick='".
	'if ($(this).attr("clicked")!="true") {
		$(this).html($(this).attr("title"));
		} '.
		'$(this).attr("clicked","true");'.
	"'>".$trimedEnc."</span>";

}

//タイトル表示、クリックすると横に詳細
function jsDetail($title,$detail,$encode='utf-8'){
	
    //return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
	$titleEnc=htmlentities($title,ENT_QUOTES,$encode);
	
	//クリックしたら改行を反映した元ソースを表示
	return "<span title='".htmlentities($detail,ENT_QUOTES,$encode)."' onClick='".
	'if ($(this).attr("clicked")!="true") {
		$(this).html($(this).html()+" <span style='."color:gray;".'>"+$(this).attr("title")+"</span>");
		} '.
		'$(this).attr("clicked","true");'.
	"'>".$titleEnc."</span>";

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


//arrayの空白行を除去
function trimArray($in_array){
    $keywords=$in_array;
    foreach ($keywords as $key=>$value) {if (trim($value)=="") unset($in_array[$key]);}
    return $in_array;
}

//一項目をキーワード（ひとつ）で赤に
function markRed($val,$keyword,$colorName="crimson"){
	
	if (!$keyword) return $val;
	return preg_replace("/(".preg_quote($keyword).")/i","<span style='color:".$colorName."'>$1</span>",$val);
}

//配列in配列のvalueを文字数で切り詰める   切り詰めはmbで byte数表示はcharで
function assocTrimChar($array, $trimcount, $option="")
{
    if (!$trimcount) {
        return $array;
    }

    foreach ($array as &$row) {
        foreach ($row as &$str) {
            $str = trimChar($str, $trimcount, $option);
        }
    }
    return $array;
}
function trimChar($str, $trimcount, $option ="")
{
    if (mb_strlen($str, 'UTF-8') > $trimcount) {
        $str = mb_substr($str, 0, $trimcount, 'UTF-8') . '...';
        if (preg_match('/byte/i', $option)) {
            $str .= strRed(strlen($str) . "byte ");
        }
    }
    return $str;
}

//変換：連想配列 > HTMLテーブル  //htmlencode するとtext attrはセットしない
function asc2html($assoc,$htmlIdTag=null,$doHtmlEncode=true,$option=""){ //クエリ結果連想配列

	$return="";
	$nullAttr="";
	if (count($assoc)==0) return;

	$return.= "\n<table name='${htmlIdTag}' id='${htmlIdTag}' >\n ";

	//ヘッダ表示
	if (!preg_match("/notitle/" ,$option)){
		$return.= " <tr id='header' name='header' >";		
		foreach ($assoc as $row) {
			foreach ($row as $key => $value){//*task
				$return.= "  <th id='${key}' align='left' style='border-bottom: 2px solid silver;padding-right:10px;'>${key}</th>\n";
			}
			break;
		}
		$return.= "</tr>";
	}
	
	$count=0;
	foreach ($assoc as $row){
		$return.= "    <tr id='header".$count++."'>\n";
		foreach ($row as $key => &$value){
			$nullAttr="isnull=false";
			if ($value===null) { //nullと空白を区別
				$value="<span style='color:gray;font-style:italic;'>null</span>";
				$nullAttr="isnull=true";
				$tdValue=$value;
			}else{
				$nowrap="";
    	        if ($key!="ACTION" and $doHtmlEncode) $value=htmlentities($value,ENT_COMPAT,'utf-8');
        	    //if ($mbchar_count>$colCharMax) $value.="... <font color=red>".$mbchar_count." char ".$char_count."byte</font>";
				$tdValue=htmlentities($value,ENT_COMPAT,'utf-8');
   			}
			
			if ($key=="ACTION") $tdValue="";
			$return.= '        <td id="'.$htmlIdTag.'_'.$key.'_'.$count.'" name="'.$key.'" text="'.$tdValue.'" '.
					 ' style="border-bottom: 1px solid silver ; padding-right:5px;" '.$nullAttr.' align="left" valign="top" nowrap >'.$value."</td>\n";

		}
		$return.= "    </tr>\n";
	}
	$return.= "</table>\n";
	return $return;
}

// 2次元配列にヘッダ行を追加
function addHeaderToAssoc($in_assoc=[]){
    //0件から取る。
    if (!$in_assoc)             return $in_assoc;
    if (count($in_assoc)==0) return $in_assoc;
    
    foreach ($in_assoc as $key =>$row){
        $headerRow['header']=$row;
        foreach($row as $key2=>$row2)  $headerRow['header'][$key2]=$key2;
        break;
    }
    return $headerRow+$in_assoc;
}

//連想配列をCSVやTSVテキスト化
function assocToText($in_assoc,$separater="\t"){  
	$returnStr="";
	if (count($in_assoc)==0) {return "";}
	foreach($in_assoc as $row){
		//foreach($row as $key=>$value){
			//要素にタブなどあれば確認
		//}
		$returnStr.=join($separater,$row)."\n";
	}
	return $returnStr."\n";
}

//連想配列をinsert文にレコードで取得
function assocToInsertStmt($in_Records,$in_Tablename,$dbtype='mysql'){
  
    $returnStr=[];
    if (count($in_Records)==0) return "";

    foreach($in_Records[0] as $key=>$value) {
		if ($dbtype=='mysql') $aryCols[]="`".$key."`";
    	elseif ($dbtype=='sqlite') $aryCols[]=$key;
	}
	$strCols=join(',',$aryCols);
    foreach($in_Records as $row){
        foreach($row as $key=>$value){
            if ($value===null)    {
            	$aryVals[$key]='null';
            }else {
				if ($dbtype=='mysql') $aryVals[$key]="'".addslashes($value)."'";
				if ($dbtype=='sqlite') $aryVals[$key]="'".SQLite3::escapeString($value)."'";
			}
        }
        $returnStr[] = 'insert into '.$in_Tablename.' ('.$strCols.') values('.join(',',$aryVals).');';
    }
    return $returnStr;
}

function arrayToInsertStmt($row,$table_name,$dbtype='mysql'){
  
    if (count($row)==0) return "";

    foreach($row as $key=>$value) {
		if ($dbtype=='mysql') $aryCols[]="`".$key."`";
    	elseif ($dbtype=='sqlite') $aryCols[]=$key;
	}
	$strCols=join(',',$aryCols);
	foreach($row as $key=>$value){
		if ($value===null)    {
			$aryVals[$key]='null';
		}else {
			if ($dbtype=='mysql') $aryVals[$key]="'".addslashes($value)."'";
			if ($dbtype=='sqlite') $aryVals[$key]="'".SQLite3::escapeString($value)."'";
		}
	}
	$returnStr = 'insert into '.$table_name.' ('.$strCols.') values('.join(',',$aryVals).');';
    return $returnStr;
}

function createInsert($record,$table_name){ //assocからinsert文ひとつ生成
	//colname
	foreach ($record as $colname =>$roc) $cols[]="`".$colname."`";
	//value
	$vals=[];
	foreach ($record as $value) {
		if (is_null($value)) $vals[]='null';
		else $vals[]="'".addslashes($value)."'";	
	}
	$return="insert into `".$table_name."` (".implode(",",$cols).") values (".implode(",",$vals).");";
	return $return;
}
function createBigInsert($records,$table_name){ // assoc in assoc からinsert文の束を生成

	//colname
	foreach ($records[0] as $colname =>$roc) $cols[]="`".$colname."`";
	//value
	foreach ($records as $record) {
		$vals=[];

		foreach ($record as $value) {
			if (is_null($value)) $vals[]='null';
			else $vals[]="'".addslashes($value)."'";	
		}		
		$return.="insert into ".$table_name."(".implode(",",$cols).") values (".implode(",",$vals).");";
	}
	return $return;
}

function createInsertForm($table,$link){

	//tabledesc
	echo strBold("INSERT into ".str150($table))."<br/>";
	$a= sql2asc("select * from information_schema.columns where TABLE_SCHEMA=database() and TABLE_NAME='" . $table . "'",$link,true);
	echo "<form name=f1 method=post>";
	echo '<input type="hidden" >';
	echo "<table>";
	foreach ($a as $row){
		echo "<tr><td>";
		if ($row['IS_NULLABLE']=='NO') echo strRed(strbold($row['COLUMN_COMMENT']))." ";
		else echo $row['COLUMN_COMMENT']." " ;	

		echo strGray($row['COLUMN_NAME']);
		echo "</td><td>";
		if ($row['CHARACTER_MAXIMUM_LENGTH']>200) echo '<textarea name="'.$row['COLUMN_NAME'].'" cols=80 rows=4></textarea>';
		else echo '<input type="text" name="'.$row['COLUMN_NAME'].'">';
		echo "</td></tr>";	
	}
	echo "</table>";
	echo '<input type="submit" >';
	echo "</form>";
}

function createUpdate($row,$tablename,$link){
	
	//pK列取得  Key=PRI のもの
	$select ="describe ".$tablename;
	$desc=sql2asc($select,$link);
	foreach($desc as $descrow) {
		if ($descrow['Key']=='PRI') $pks[$descrow['Field']]=$descrow['Field'];
	}

	//値の更新部分
	foreach ($row as $key=>$val) {
		if (is_null($val)) $upd[]="`".$key."`=null";
		else $upd[]="`".$key."`='".$val."'";
	}	

	//foreach ($row as $key=>$val)	$upd[]="`".$key."`='".$val."'";
	$update_str=implode(' , ',$upd );
	
	//where部分
	foreach ($pks as $key=>$val)	$where[]="`".$key."`='".$row[$key]."'";
	$where_str=implode(' and ',$where );

	//sql 
	$sql="update ".$tablename." set ".$update_str.' where '.$where_str;
	return $sql;
}
function createDelete($row,$tablename,$link){
	
	//pK列取得  Key=PRI のもの
	$select ="describe ".$tablename;
	$desc=sql2asc($select,$link,false,false);
	foreach($desc as $descrow) {
		if ($descrow['Key']=='PRI') $pks[$descrow['Field']]=$descrow['Field'];
	}

	//where部分
	foreach ($pks as $key=>$val)	$where[]=$key."='".$row[$key]."'";
	$where_str=implode(' and ',$where );

	//sql
	$sql="delete from ".$tablename.' where '.$where_str;
	return $sql;	
}





// -----  dump系  ------------------------------------------------------------------------------------------------


function assocDump($assoc){  //連想配列をHTMLテーブルでダンプ1階層のみ
	$ret = "\n<table>";
	foreach ($assoc as $key=>$value)  {
		if (is_array($value)){
			$ret.= "<tr><td style='border-bottom: 1px solid silver;'>" . $key. "</td><td style='border-bottom: 1px solid silver;'>";
			$ret .= print_r($value,true);
			$ret.= "</td></tr>";
		}else{
			if (!$value or !strlen((string)$value)) {
				$value="<span style='color:gray;font-style:italic;'>null</span>";
			}
			$ret.= "<tr><td style='border-bottom: 1px solid silver; white-space:nowrap;'>" . $key . "</td><td style='border-bottom: 1px solid silver; white-space:nowrap;'>" . $value . "</td></tr>";
		}
	}
	$ret.= "</table>";
	return $ret;
}


//横棒グラフを追加 全行分の%を表示  引数 records配列 値の列名
function addChartCol($records,$countColName="count"){
	$sum=0;
	foreach ($records as &$row) $sum+=$row[$countColName];
	foreach ($records as &$row) {
		$percent=round($row[$countColName]/$sum,2);
		$pixel=$percent*100;
		$row['chart']="<img src='1dot_crimson.jpg' width=".$pixel." height=5 /><img src='1dot_gray.jpg' width=".(100-$pixel)." height=5 /> ".strGray($percent."%");
	}
	return $records;
}

//プラス・マイナスのグラフを追加   $records  priceColName=価格の元になる列名  maxval=100%の値
function add_chart_col_plus_minus($records,$priceColName,$max_val){
	$max=0;
	//maxの値のサイズ感を得る
	foreach ($records as &$row) if (abs($row[$priceColName]) > $max) $max=abs($row[$priceColName]);
	foreach ($records as &$row) {
		$pixel=round(abs($row[$priceColName])/$max,2)*100;
		//プラスとマイナスで描き分ける。プラス100px + minus 100px
		if ($row[$priceColName] < 0 ){
			
			$row['chart']="<img src='1dot_gray.jpg' width=".(100-$pixel)." height=5 /><img src='1dot_crimson.jpg' width=".($pixel)." height=5 /><img src='1dot_gray.jpg' width=100 height=5 />".strGray($pixel."%");
		}else{
			//グレーで100px ,値+残りグレー
			$row['chart']="<img src='1dot_gray.jpg' width=100 height=5 /><img src='1dot_crimson.jpg' width=".$pixel." height=5 /><img src='1dot_gray.jpg' width=".(100-$pixel)." height=5 /> ".strGray($pixel."% ".$max);
		}
	}
	return $records;
}

//配列の項目内のTABCrLf除去：参照渡し
function stripTabCrLf(&$in_assoc){
      foreach ($in_assoc as $key=>$row){
          foreach ($row as $key2=>$value){
              $value2=preg_replace("/(\t|\r|\n)/","",$value);
              $in_assoc[$key][$key2]=$value2;
          }
      }
      return $in_assoc;
}


function curl_request_simple($url,$showURL=false,$showResult=false){  //urlを受け通信してHTML返す

	if ($showURL) echo strRed("curl")." ".strGray($url)." ";
	$ch = curl_init($url);	//ページ 初期メソッドget
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);	//戻り値 0=画面 true=変数に出力
	curl_setopt($ch, CURLOPT_HEADER, true);	//1=戻り値でhttpヘッダも返す 0=返さない
	
	//証明書のないSSL用：一時設定
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

	$start=microtime(true);
	$result = curl_exec($ch);

	$res_ary=explode("\n",trim($result));
	if ($showURL) echo round(microtime(true)-$start,2)." ".array_shift($res_ary)."  "." ".strRed(strlen($result)).strGray("byte<br/>");
	//error
	if ($result===false) {
		echo strRed(curl_error($ch)."<br/>");
		return false;
	}
	if ($showResult) {
		echo strBold("RequestHeader<br/>");
		echo assocDump(curl_getinfo($ch));
		echo strBold("Response<br/>");
		echo nl2br(htmlentities($result));
	}
	curl_close($ch);  
	return $result;   
}


function runShell($shell,$showDump=true){ //shell実行、コマンド出力
	if ($showDump) echo strGray(nl2br($shell))."<br/>";
	return `$shell`;
}
function runShellAry($shell,$showDump=true){ //shell実行、コマンド出力

	$str = trim(`$shell` . "");
	$ary = explode("\n",$str);
	if (!$ary) $ary =[];
	if ($showDump) echo strGray(nl2br($shell)) . ' ' . strPink(count($ary)) . "<br/>";
	return $ary;
}


// excape系 -------------------


//自動クオートでなければクオート
function stripslashMQuote($str,$dbname="mysql"){  
	if (get_magic_quotes_gpc()) return stripslashes($str);
	return $str;
}
function addslashesMQuote($str,$dbname="mysql"){
	if ($dbname=="sqlite"){
		if (get_magic_quotes_gpc()) return sqlite_escape_string(stripslashes($str));
		return sqlite_escape_string($str);
	}
  	//mysql
	if (get_magic_quotes_gpc()) return $str;
	return addslashes($str);
}
function mysqlEsacpeMQuote($str){
  if (get_magic_quotes_gpc()) $str=stripslashes($str);
  return addslashes($str);
}




function strTitle($str) { return "<span style='font-family:arial,helvetica,sans-serif;'>".$str."</span>";}

function str50($str) { return "<span style='font-size:50%;'>".$str."</span>";}
function str80($str) { return "<span style='font-size:80%;'>".$str."</span>";}
function str120($str) { return "<span style='font-size:120%;'>".$str."</span>";}
function str150($str) { return "<span style='font-size:150%;'>".$str."</span>";}
function str200($str) { return "<span style='font-size:200%;'>".$str."</span>";}
function str300($str) { return "<span style='font-size:300%;'>".$str."</span>";}

function strWhite($str) { return "<span style='color:#FFFFFF;'>".$str."</span>";}
function strPink($str) { return "<span style='color:pink;'>".$str."</span>";}
function strBlack($str) { return "<span style='color:#444444;'>".$str."</span>";}
function strBlackBG($str) { return "<span style='color:#444444; background:#eee; padding:1px;'>".$str."</span>";}
function strBlue($str) { return "<span style='color:#4444DD;'>".$str."</span>";}
function strYellow($str) { return "<span style='color:yellow;'>".$str."</span>";}
function strRed($str)  { return "<span style='color:red;'>".$str."</span>";}
function strGreen($str) { return "<span style='color:DarkGreen;'>".$str."</span>";}
function strDarkred($str) { return "<span style='color:crimson;'>".$str."</span>";}
function strGray($str) { return "<span style='color:gray;'>".$str."</span>";}
function strSilver($str) { return "<span style='color:silver;'>".$str."</span>";}
function strZeroSilver($val){
    if ($val ==0) return strSilver($val);
    return $val;
}
function strOrange($str) { return "<span style='color:darkOrange;'>".$str."</span>";}
function strOrangeBG($str) { return "<span style='color:darkOrange; background:#fed; padding:1px;'>".$str."</span>";}

function strRevBlue($str)   { return "<span style='color:white; background-color:blue;'>".$str."</span>";}
function strRevYellow($str) { return "<span style='color:white; background-color:yellow;padding:2px;'>".$str."</span>";}
function strRevRed($str)    { return "<span style='color:white; background-color:red;padding:2px;'>".$str."</span>";}

function strBold($str) { return "<span style='font-weight:bold;'>".$str."</span>";}

function strCenter($str){ return "<div style='text-align:center;'>".$str."</div>";}
function strBlink($str){ return "<span style='text-decoration:blink;'>".$str."</span>";}

function bgBlue($str) { return "<span style='background-color:#4444DD;'>".$str."</span>";}
function bgYellow($str) { return "<span style='background-color:yellow;'>".$str."</span>";}
function bgRed($str)  { return "<span style='background-color:red;'>".$str."</span>";}

function strHTML($str,$decos){
	//短縮ワードとcssの対応リスト
	$deco_list=array("red"=>"color:red;","blue"=>"color:#4444DD;","crimson"=>"color:crimson;",
			"gray"=>"color:gray;","pink"=>"color:pink;",
			"orange"=>"color:orange;","green"=>"color:green;","purple"=>"color:purple;",
			"blink"=>"text-decoration:blink;","underline"=>"text-decoration:underline;",
			"left"=>"text-align:left;","center"=>"text-align:center;","right"=>"text-align:right;",
			"bold"=>"font-weight:bold;",
			"x-small"=>"font-size:x-small;","medium"=>"font-size:medium;",
			"large"=>"font-size:large;","x-large"=>"font-size:x-large;",
			"arial" =>"font-family:arial,helvetica,sans-serif;"
			);

	$deco_ary_in=explode(" ",$decos);
	$decoStr="";
	$tag='div';
	//css文字列作成  span指定なければdiv  #が交じる文字ならcolor:#nnnnnn
	foreach ($deco_ary_in as $deco) {
		if (trim($deco)!="" and isset($deco_list[$deco])) $decoStr.=$deco_list[$deco];
		if (trim($deco)=="span") $tag="span";
		//色
		if (strpos($deco,"#")!==false) $decoStr.="color:".trim($deco).";";//#つきなら色名
		//文字サイズ
		if (strpos($deco,"%")!==false) $decoStr.="font-size:".trim($deco).";";// % フォントサイズ
		if (strpos($deco,"px")!==false) $decoStr.="font-size:".trim($deco).";";// px フォントサイズ
	}
	return "<".$tag." style='".$decoStr."'>".$str."</".$tag.">";
}

function indent($str){ return "<blockquote>".$str."</blockquote>";}

function htmlHeader($title = ""){
	echo '<?xml version="1.0" encoding="utf-8" ?>'; 
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"> 
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja"> 
	<head> 
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="robots" content="none"> 
	<title><?=$title?></title>
	<link href='style.css?a=<?=rand(1,100)?>' rel='stylesheet' type='text/css' />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script> 

	</head>
<?php
}
?>
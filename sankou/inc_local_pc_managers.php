<?php

// ajax  ファイル実行 finderでselect 
$open = getRequest("open");
$shell ='open "' . rawurldecode($open) . '" ';
if ($open) {
    system($shell);
    exit();
}

// ガームemulatorで開く
$openemu = getRequest("openemu");
$shell ='open -a "OpenEmu" "' . rawurldecode($openemu) . '" ';
if ($openemu) {
	echo $shell;
    system($shell);
    exit();
}

$openhandbrake = getRequest("openhandbrake");
$shell ='open -a handbrake "' . rawurldecode($openhandbrake) . '" ';
if ($openhandbrake) {
	echo $shell;
    system($shell);
    exit();
}



$runapp = getRequest("runapp");
$shell ='open -a "' . rawurldecode($runapp) . '" ';
if ($runapp) {
    system($shell);
    exit();
}

$finderselect = getRequest("finderselect");
if ($finderselect) {
    $shell ='open -R "' . $finderselect . '"';
    system($shell);
    exit($shell);
}


//  sqlite  ---------------------------------------

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

function getPCName(){
	$pcname = trim(`scutil --get ComputerName`);
	$pcname = str_replace(' ','',$pcname);
	return $pcname;
}


function getWifiSSID(){
	$net = `networksetup -listallhardwareports | grep -1 Wi-Fi`;
	$net = str_replace(LF,'',$net);
	$EN = trim(explode(':',$net)[2]);
	$shell11 = "networksetup -getairportnetwork " . $EN;
	$ssid = trim(`$shell11`);
	$ssid = trim(explode(":",$ssid)[1]);

	return $ssid;
}


function getPdfInfos($path){
	$path = str_replace('"','\"',$path);
    $shell1 = 'qpdf --json  "'. $path . '"';
    $json_str = `$shell1`;
    $json_raw = json_decode($json_str,true);
	return $json_raw;
}

function get_ffprobeStreams($path){
	$path = str_replace('"','\"',$path);
    $shell1 = 'ffprobe -v error -print_format json -show_streams "'. $path . '"';
    $json_str = `$shell1`;
    $json_raw = json_decode($json_str,true);
	return $json_raw;
}

function get_ffprobeChapters($path){
	$path = str_replace('"','\"',$path);
    $shell1 = 'ffprobe -v error -print_format json -show_chapters "'. $path . '"';
    $json_str = `$shell1`;
    $json_raw = json_decode($json_str,true);
	return $json_raw;
}


// ファイルの場合とrtspの場合
function getMovieInfo($path){

	$json_raw = get_ffprobeStreams($path);
    $json = $json_raw['streams'][0];
    $stat = stat($path);
	// echo "<hr/>".$path . "<br/>";
	// echo "<br/>";
    $ret = [
        "codec" => $json['codec_name'] , 
        "f_width" => $json['width'],
        "f_height" => $json['height'] ,
        "fps"=>str_replace("/1","",$json['r_frame_rate'])  , 
		"nb_frames" => $json['nb_frames'],
        "duration_s" => round((float)$json['duration'],1) , 
        "bit_r" => intval($json['bit_rate'] / 1024),
        "path" =>  $path  ,
        "file_s" => round($stat['size']/1024/1024,1) ,
        "created" => round((time() - $stat['mtime'])/86400,1),
        "mtime" => $stat['mtime']
    ];
	return $ret;
}
function getVideoStreamInfo($path){

	$json_raw = get_ffprobeStreams($path);
    $json = $json_raw['streams'][0];
    $ret = [
        "codec" => $json['codec_name'] , 
        "f_width" => $json['width'],
        "f_height" => $json['height'] ,
        "fps"=>str_replace("/1","",$json['r_frame_rate'])  , 
        "path" =>  $path  ,
    ];
	return $ret;
}



// 1次元配列
function filterAry($files_ary1,$filters){

	// ファイル一覧
	$filtered= [];
	foreach ($files_ary1 as $fpath){
		$ismatch = true;
		$fpath2 = iconv("UTF-8-MAC", "UTF-8", $fpath); // 濁点を吸収
		foreach ($filters as $f1){
			if (!preg_match("@" . $f1 . "@i", $fpath2)) $ismatch = false;
		}
		if ($ismatch) $filtered[] = $fpath;
	}

	return $filtered;
}

// ファイル一覧の配列から 2段前のフォルダの一覧を出す
function getRootPathList($files_ary){
    $rootpaths=[];
    foreach ($files_ary as $fpath){
        $pathinfo = pathinfo($fpath);
        $dir = $pathinfo['dirname'];
        $dir = str_replace("./","",$dir);
    
        $rootpath = preg_replace("/\/.*/u","",$dir);
        if (!isset($rootpaths[$rootpath] )) $rootpaths[$rootpath] = 0;
        $rootpaths[$rootpath]++;
    }
    ksort($rootpaths);
    return $rootpaths;
}

//ファイル一覧の配列から 
function getDirAndCount($files_ary){

    $paths =[];
    foreach ($files_ary as $fpath){
        $pathinfo = pathinfo($fpath);
        $dir = $pathinfo['dirname'];
        $dir = str_replace("./","",$dir);
    
        if (!isset($paths[$dir] )) $paths[$dir] = 0;
        $paths[$dir]++;
    }
    return $paths;
} 

// 左のファイルリスト
function showFilterWordList($words,$word_counts =[]){
	$html ="";
	foreach ($words as $key =>$words_ary ){
		$html .= strBold($key . '<table> ');
		foreach ($words_ary as $key =>$words2 ){
			$html .=  ' <tr><td style="white-space:nowrap;"> ' . $key .' </td><td style="white-space:nowrap;" >';
			foreach ($words2 as $word ){  
				if (!$word) continue;      
				$ct = 0;
				if (isset($word_counts[$word])) $ct = $word_counts[$word];
				if ($ct < 5) $ct = strSilver($ct);
				if ($ct < 15) $ct = strBold($ct);
				$html .=  '<a style="background:#eff;" href="?filter=' . $word . '">' . 
						$word . '</a>' . $ct . ' &nbsp;';
			}
			$html .=  '</td></td style="white-space:nowrap;">';
		}
		$html .=  '</table> <br/>';
	}
	return $html;
}


class StatFiles{
	static function flatArray($dim3_array){
		$word_list = [];
		foreach ($dim3_array as $key => $wds){
			foreach ($wds as $vals){
				foreach ($vals as $value){
					$word_list[]=$value;
				}
			}
		}
		return $word_list;
	}
	// ファイルパス配列と単語配列からファイル名に含む単語の統計取得
	static function countWordsInFilename($files_ary,$word_list){	

		$word_counts =[];
		foreach ($word_list as $value){
			$word_counts[$value] = 0;
			$filters = preg_split("/\s+/u",trim($value));
			foreach ($files_ary as $fpath){
				if (!$fpath) continue; 
				$ismatch = true;
				$fpath = iconv("UTF-8-MAC", "UTF-8", $fpath); // 濁点を吸収
				foreach ($filters as $f1){
					if (preg_match("@" . preg_quote($f1) . "@iu", $fpath) === 0) $ismatch = false;
				}
				if ($ismatch) {
					$word_counts[$value]++;
				}
			}                    
		}
		return $word_counts;

	}
	// ファイルパス配列と単語配列から
	//ファイル名と中身に含む単語の統計取得
	static function countWordsInFiletext($records,$word_list){	

		$word_counts =[];
		foreach ($word_list as $value){
			$word_counts[$value] = 0;
			$filters = preg_split("/\s+/u",trim($value));
			foreach ($records as $row){
				//if (!$fpath) continue; 
				$fname = 'pdf_text/' . $row['inode'] . '.txt';
				$ismatch = true;
				$text1 = file_get_contents($fname);
				foreach ($filters as $f1){
					if (preg_match("@" . preg_quote($f1) . "@iu", $text1) == 0) $ismatch = false;
				}
				if ($ismatch) {
					$word_counts[$value]++;
				}
			}                    
		}
		return $word_counts;

	}

}


// 一覧表示  右側
// filter = 更新   filters
class FileResults{

	// 未使用
	static function fileSearch(){

	}

	// 未使用
	static function pathAry2records($filepath_ary){
		$ret_records = [];
		foreach ($filepath_ary as $path) {
			$pathinfo = pathinfo($fpath);
			$ret_records[] = ['path' => $path,"info" =>$pathinfo] ;
		}
		return $ret_records;
	}


	// path配列をキーワードで絞り込み
	static function filterPathAry($filepath_ary,$filter_words){

		if (!$filter_words) {
			return $filepath_ary;
		}
		$filtered = [];

        foreach ($filepath_ary as $ct => $fpath){
			if (!$fpath) exit(strRed('no pass ') . $fpath);

			$ismatch = true;
			//echo $fpath . BR;
            $fpath_conved = iconv("UTF-8-MAC", "UTF-8", $fpath); // 濁点を吸収 文字により失敗
			if (!$fpath_conved) {
				echo "iconv fail " . $ct . ' ' . strRed($fpath) . BR;
				continue;
			}
            foreach ($filter_words as $f1){
                if (preg_match("@" . preg_quote($f1) . "@iu", $fpath_conved) == 0) $ismatch = false;
            }
            if ($ismatch) {
                $filtered[] = $fpath_conved;
            }
        }
        sort($filtered);
		return $filtered;
	}

	static function limit($filepath_ary, $limit = 400){
		$ret_ary = [];
		$ct = 0;
		foreach ($filepath_ary as $path){
			$ct++;
			$ret_ary[] = $path;
			if ($ct >= $limit ) break;
		}
		return $ret_ary;
	}
}

/*



*/

// 要素切り出し
// リンク生成


//変換：連想配列 > HTMLテーブル  //htmlencode するとtext attrはセットしない
function asc2html4filer($assoc){ //クエリ結果連想配列

	$return="";
	if (count($assoc)==0) return;

	$return.= "\n<table class='filelist_table' >\n ";
	//ヘッダ表示
	$return.= " <tr id='header' name='header' >";		
	foreach ($assoc as $row) {
		foreach ($row as $key => $value){//*task
			$return.= "  <th id='${key}' align='left' style='border-bottom: 2px solid silver;padding-right:10px;'>${key}</th>\n";
		}
		break;
	}
	$return.= "</tr>";	
	// 内容表示
	foreach ($assoc as $row){
		$return.= "    <tr group='clickable' path='". $row['fullpath'] ."' >\n";
		foreach ($row as $key => &$value){			
			$return.= '        <td id="'.$key.'" name="'.$key.'"'.
					 ' style="border-bottom: 1px solid silver ; padding-right:5px;"  align="left" valign="top" nowrap >'.$value."</td>\n";
		}
		$return.= "    </tr>\n";
	}
	$return.= "</table>\n";

$return .= <<<EOF
	<script>
		$('tr[group=clickable]').click(function(obj){
			console.log("click")
			setOpen($(this).attr('path'))
		})
		$('tr[group=clickable]').dblclick(function(obj){
			console.log("dbl")
			setFinderSelect($(this).attr('path'))
		})

		// $(".filelist_table tr").each( function (ind,obj){ 
		// 	if (ind % 2==0){ $(obj).css("background-color","#F0F0F0") }
		// })

		$(".filelist_table tr").mouseover(function () { 
			$(this).css("background-color","#F0F0F0");
		})
		$(".filelist_table tr").mouseout(function () { 
			$(this).css("background-color","#FFF");
		});	
	</script>
EOF;
	


	return $return;
}

// レコードに  wiki rate year filesize movie_length create select fullpath dir filename
function ary2records($filepath_ary,$attr = ["wiki","filename","rate","year","playtime","fsize","fullpath"],$filters = []){

	$records = [];
	foreach ($filepath_ary as $path){

		$row = [];
		$pathinfo = pathinfo($path);
		foreach ($attr as $val){
			if ($val == "open"){
				$fname = $pathinfo['filename'];
				foreach($filters as $f1){
					if ($f1) $fname = preg_replace("/(" . preg_quote($f1) . ")/im",strRed('$1'),$fname);
				}
				$row[$val] = "<a href='javascript:setOpen(\"" . rawurlencode($path) . "\")'>" . $fname . "</a> ";  
			}

			if ($val == "openemu"){
				$fname = $pathinfo['filename'];
				foreach($filters as $f1){
					if ($f1) $fname = preg_replace("/(" . preg_quote($f1) . ")/im",strRed('$1'),$fname);
				}
				$row[$val] = "<a href='javascript:setOpenEmu(\"" . rawurlencode($path) . "\")'>" . $fname . "</a> "; 
			}

			if ($val == "romtype"){
				$row[$val] = "";
				$ext_types= ["gb" => 'GameBoy',"smd" => 'MegaDrive',"snes" => "SuperFamicom", "nes" => "famicom","pce" => "PCEngine"];
				foreach($ext_types as $key => $type1){
					if (strpos(strtolower($path),"." . $key)) $row[$val] = $ext_types[$key];
				}
			}

			if ($val == "fullpath"){
				$row[$val] = $path; 
			}
			if ($val == "sel"){
				$row[$val] =  "<a href='javascript:setFinderSelect(\"" . rawurlencode($path) . "\")'>" . strSilver("sel") . "</a> ";
			}
			if ($val == "dir"){
				$row[$val] = $pathinfo['dirname'];
				
			}
			if ($val == "filename"){
				$row[$val] = $pathinfo['filename'];
			}
			if ($val == "year"){
				$year ="";
				$matches =[];
				if (preg_match("/ \d{4} /u",$path,$matches)){
					if (count($matches) > 1)  echo strRed('year dup ' . $path);
					$year = $matches[0];
					if ($year > 2030) $year ="";
				}				
				$row[$val] = $year; 
			}
			if ($val == "rate"){
				$rate = "";
				$matches =[];
				if (preg_match("/[★]+/u",$path,$matches)){
					$rate = $matches[0];
				}

				$row[$val] = $rate; 
			}
			if ($val == "wiki"){
				$row[$val] =  '<a href="https://www.google.com/search?q=wikipedia+' . $pathinfo['filename'] . '">' . strSilver('wiki') . '</a>'; 
			}
			if ($val == "fsize"){
				$fsize = filesize($path);
				$fsize_mb = round($fsize /1024 / 1024);
				$row["fsize"] = $fsize_mb . strSilver("mb");
			}
			if ($val == "movie_length"){
				$ret = runShell("");
				$fsize = filesize($path);
				$fsize_mb = round($fsize /1024 / 1024);
				$row[$val] = $fsize_mb . strSilver("mb");
			}
			
		}
		$records[] = $row;
	}
	return $records;
}


function showFileList($filepath_ary,$filters,$is_ext=false){

	$ret = "";
	$ret .= "files " . count($filepath_ary) . "<hr/>";
	$ret .= "<table>";

	// ファイル一覧 表示
	$prev_dir ="";
	$ct =0;
	foreach ($filepath_ary as $ct => $path){

		if (!is_string($path)) exit(strRed('no string ') . $ct . ' ' . print_r($path,true));
		if (!$path) continue;

		$ct++;
		$pathinfo = pathinfo($path);
		$dir = $pathinfo['dirname'];
		$filename = $pathinfo['filename'];
		if ($is_ext) $filename .= "." . $pathinfo['extension'];

		$dir2 = $dir;
		$dir2 = str_replace( "../movie","",$dir2);

		

		// 検索ワードヒット部分色づけ
		$filename_colored = $filename;
		if ($filters) {
			foreach ($filters as $f1){
				if ($f1=="") continue; 
				$dir2 = preg_replace("@(" . preg_quote($f1) . ")@iu",strRed('$1'),$dir2);
				$filename_colored = preg_replace("@(" . preg_quote($f1) . ")@iu",strRed('$1'),$filename_colored);
			}
		}

		// dir表示
		if ($prev_dir != $dir){
			$ret .= "<tr><td colspan=7 >" . $dir2 . " <a href='javascript:setOpen(\"" . rawurlencode($dir) . "\")'>" . 
			strSilver("open")."</a> </td></tr>" ;
		}

		// ファイル情報など
		$fsize = filesize($path);
		$fsize_mb = round($fsize /1024 / 1024);

		// ファイル名から★★★や時間120mを取得

		// 4桁数字 = 年号
		$year ="";
		$matches =[];
		if (preg_match("/ \d{4} /u",$path,$matches)){
			if (count($matches) > 1)  echo strRed('year dup ' . $path);
			$year = $matches[0];
		}
		// ★の数
		$rate = "";
		$matches =[];
		if (preg_match("/[★]+/u",$path,$matches)){
			$rate = $matches[0];
		}

		// ファイルパス表示  ファイルサイズと更新日 
		$ret .=  "<tr><td> ";
		// game wikipedia 

		$ret .=  '<a href="https://www.google.com/search?q=wikipedia+' . $filename . '">' . strSilver('wiki') . '</a>';
		
		$ret .=  "</td><td>";
		$ret .=  $year;
		$ret .=  "</td><td>";
		$ret .=  $rate;
		$ret .=  "</td><td>";
		$ret .=  $fsize_mb . strSilver("mb");
		$ret .=  "</td><td>";
		$ret .=  "<a href='javascript:setFinderSelect(\"" . rawurlencode($path) . "\")'>" . strSilver("sel") . "</a> ";    
		$ret .=  "</td><td>";
		$ret .=  "<a href='pdf_sandbox.php?filepath=" . rawurlencode($path) . "'>" . strSilver("sand") . "</a> ";    
		$ret .=  "</td><td>";
		$ret .=  "<a href='javascript:setOpen(\"" . rawurlencode($path) . "\")'>" . $filename_colored . "</a> "; 
			
		$ret .=  "</td></tr>";
		$prev_dir = $dir;
	}
	$ret .="</table>";
	return $ret;
}

function showJS(){
?>

	// jqueryにセットする用にエスケープ id="val[1]" の項目を選択するときなど
	function escapeJquerySelector(val){
		return val.replace(/[ !"#$%&'()*+,.\/:;<=>?@\[\\\]^`{|}~]/g, "\\$&");
	}

	// 項目に値をセットしてform submit 
	function setVal(key,val,is_submit = "submit"){
        $('#' + escapeJquerySelector(key)).val(val)
        if (is_submit == "submit" ) $('#f1').submit()
    }

	// ファイルをopen 
	function setOpen(path){
		let url = '?open=' + encodeURIComponent(path)
		$.get(url).done( function(data) { console.log(data) } )
	}

	function setOpenEmu(path){
		console.log(path)
		let url = '?openemu=' + encodeURIComponent(path)
		$.get(url).done( function(data) { console.log(data) } )
	}	

	function setHandBrakeOpen(path){
		console.log(path)
		let url = '?openhandbrake=' + encodeURIComponent(path)
		$.get(url).done( function(data) { console.log(data) } )
	}	

	// finder選択
	function setFinderSelect(path){
		let url = '?finderselect=' + encodeURIComponent(path)
		$.get(url).done( function(data) { console.log(data) } )
	}

	// inputダブルクリックで値をクリア
    $("form input").dblclick(function(e){
        $(this).val("")
        $('#f1').submit()
    })

    $("form input").keyup(function(event){ 
		if ($(this).val()){
			$(this).css("background","#eef")
			$(this).css("border","2px solid #88f")
		}
    })	
    $("form input:text").each(function(event){ 
		if ($(this).val()){
			$(this).css("background","#eef")
			$(this).css("border","2px solid #88f")
		}
    })	


	// escape でinputを全部クリア
    $(document).keydown(function(event){ 
             if (event.which == 27) {
                $('input').val("")
                $('#f1').submit()
             }
    })	

	// 初期フォーカス
	$('#filter').focus()

<?php
}
?>
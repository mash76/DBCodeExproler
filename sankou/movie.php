<?php
// 書籍と映像マネジメント
include 'inc_functions.php';
include 'inc_local_pc_managers.php';

$QUERY_LIMIT = 1000;
$STAT_FILE_NAME = "movie_counts.txt";
$SQLITE_FILE_NAME = "movie_sqlite.db";
$pdo = sqliteConnect($SQLITE_FILE_NAME);

// 画面制御
$mode = getRequest("mode");
$go = getRequest("go");

// 検索パラメータ
$filter = trim(getRequest("filter"));
$codec = trim(getRequest("codec"));
$exclude = trim(getRequest("exclude"));
$rate = trim(getRequest("rate"));
$mtime = trim(getRequest("mtime"));
$mb_min = trim(getRequest("mb_min"));
$mb_max = trim(getRequest("mb_max"));
$chapter_min = trim(getRequest("chapter_min"));
$chapter_max = trim(getRequest("chapter_max"));
$minutes_min = trim(getRequest("minutes_min"));
$minutes_max = trim(getRequest("minutes_max"));


$framesize = trim(getRequest("framesize"));

$sort_col = trim(getRequest("sort_col",false,''));
$sort_dir = trim(getRequest("sort_dir",false,'desc'));
include 'inc_menu.php'; 

$word_counts = unserialize(file_get_contents ($STAT_FILE_NAME));

// ファイル名特定部分リネーム
if ($mode=="rename"){

    // 置き換えキーワード
    //$fil1 ='\d+abr\d+'; // 1abr400 など 
    //$fil1 ='chapter'; 
    //$fil1 ='H26\d{1}\-\d+'; // h265-400  など
    //$fil1 =' \d+m '; //  60m
    $fil1 =' \d+p '; // 480p など 

    $shell = 'find ../movie -type f | grep -v DS_Store' ;
    $files = runShellAry($shell,true);  

    $fltered = [];
    foreach ($files as $line){
        if (preg_match('/' . $fil1. '/mi',$line)){
            echo preg_replace('/('. $fil1.')/mi',strRed('$1'),$line) . "<br/>"; 
            $fltered[] = $line;
        }
    }
    echo count($fltered);

    // 置換
    foreach ($fltered as $line){
        $to_name = preg_replace('/('. $fil1.')/mi','',$line) ; 
        $shell1 =  'mv "'.str_replace('"','\"',$line).'" "'.str_replace('"','\"',$to_name).'"';
        echo $shell1 . "<br/>";        
        #runShell($shell1);
    }
    exit();
}

if ($mode=="sqlite_recreate"){
    sqlExec("drop table movies",$pdo);
    sqlExec('
    CREATE TABLE "movies" (
        "filepath"	TEXT,
        "streams"	INTEGER,
        "chapters"	INTEGER,
        "v_codec"	TEXT,
        "v_tag"	INTEGER,
        "width"	INTEGER,
        "height"	INTEGER,
        "fps"	TEXT,
        "duration"	REAL,
        "minutes"	INTEGER,
        "v_bit_rate"	INTEGER,
        "a_codec"	TEXT,
        "a_tag"	TEXT,
        "sample_rate"	INTEGER,
        "channels"	INTEGER,
        "channel_layout"	TEXT,
        "a_bit_rate"	INTEGER,
        "filesize"	INTEGER,
        "filesize_mb"	INTEGER,
        "ctime"	INTEGER,
        "mtime"	INTEGER,
        "atime"	INTEGER,
        "created"	TEXT
    )
    ',$pdo);

    sqlExec('CREATE UNIQUE INDEX "movies_filename_unique" ON "movies" ( "filepath"	ASC)',$pdo);
    sqlExec("delete from movies",$pdo);
}
// consoleで  
if ($mode=="sqlite_import"){

    if (!$go) echo strRed("preview<br/>");

    // ゼロから全件インポート

    // ファイル一覧
    $shell = "find ../movie -type f | grep -v DS_Store" ;
    $files = runShellAry($shell,true);  
    $real_filenames = [];
    foreach ($files  as $line) $real_filenames[$line] = "";
    echo count($files) . " files <br/>"; 

    // bd行一覧
    $records = sql2asc("select filepath,mtime,ctime from movies ",$pdo);
    $db_filenames = [];
    foreach ($records as $row) $db_filenames[$row['filepath']] = $row;

    echo strBlue('<hr/>実ファイル-Sqlite 不存在〜ファイル名違いの差分<br/>');

    // sqliteにないファイル
    $nodb_files = [];
    $mtime_change_files = [];
    foreach ($real_filenames as $line => $val){
        if (!isset($db_filenames[$line])) {
            $nodb_files[$line] = "";
        }else{
            //sqliteに存在するが更新日が違う(登録後に更新された)
            if (stat($line)['mtime'] != $db_filenames[$line]['mtime']){
                $mtime_change_files[$line] = "";
            }
        }
    }
    $dbonly_files = [];
    foreach ($db_filenames as $line => $val){
        if (!isset($real_filenames[$line])) $dbonly_files[$line] = "";
    }
    echo "no-db-files " . strRevRed(count($nodb_files)) . "<br/>"; 
    if ($nodb_files) echo jsTrimNoEnc(implode('<br/>', array_keys($nodb_files))) . "<br/>";
    echo "db-only-files " . strRevRed(count($dbonly_files)) . "<br/>"; 
    if ($dbonly_files) echo jsTrimNoEnc(implode('<br/>', array_keys($dbonly_files))) . "<br/>";
    echo '更新日違 '  . strRevRed(count($mtime_change_files)) . "<br/>";
    if ($mtime_change_files) echo jsTrimNoEnc(implode('<br/>', array_keys($mtime_change_files))) . "<br/>";
    ?>
    <hr/>
    <a href="?mode=sqlite_import&go=true">以上を更新</a><br/>
    <?php

    if ($go){
        foreach ($nodb_files as $line => $val){
            $row = getMovieInfoRecord($line);
            insertMovie($row);
        }
        foreach ($dbonly_files as $line => $val){        
            sqlExec("delete from movies where filepath='".SQLite3::escapeString($line)."'",$pdo);
        }
    }
exit();
}

if ($mode=="sqlite_show"){

    $ret = sql2asc("select v_codec,count(*) ct from movies group by v_codec",$pdo,false);
    echo asc2html($ret);

    $ret = sql2asc("select height,count(*) ct from movies group by height",$pdo,false);
    echo asc2html($ret);

    exit();
}
?>
SQLite 
<a href="?mode=sqlite_recreate">recreate</a>
<a href="?mode=sqlite_import">import</a>
<a href="?mode=sqlite_show">show</a>


filename
<a href="?mode=rename">rename(テスト用)</a>

&nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp; 
<a href="?mode=keyword_stat">キーワード件数更新</a>
&nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp;  &nbsp; 
<a href="movie_replace.php">chapter_replace</a>
<a href="movie_file_rename.php">mkv_rename</a>


    <table><tr><td class="top_nowrap">
        <?php
            //キーワード一覧
            $words = getFilterWordList();
            echo showFilterWordList($words,$word_counts);
        ?>

    </td><td class="top_nowrap" >
        <!-- form -->
        <form id="f1">

            <table>
                <tr><td >filter</td><td>
                    <input id="filter" type="text" name="filter" placeholder="filter" value="<?=$filter ?>" >
                </td></tr>
                <tr><td>rate</td><td>
                    <input id="rate" type="text" name="rate" placeholder="★★" value="<?=$rate ?>" > 
                    <?php
                    $rates = ["★" => "★","★★" => "★★","★★★" => "★★★","★★★★" => "★★★★","★★★★★" => "★★★★★","xxx" => "xxx"];
                    foreach ($rates as $key =>$val){
                        ?>
                        <a href="javascript:$('#exclude').val('<?=$val . "★" ?>'); setVal('rate','<?=$val ?>')"><?=$key ?></a>
                        <?php
                    }
                    ?>
                </td></tr>
                <tr><td>除外</td><td>
                    <input id="exclude" type="text" name="exclude" placeholder="除外" value="<?=$exclude ?>" >
                    <?php
                    foreach (["★" => "★"] as $key =>$val){
                        echo '<a href="javascript:setVal(\'exclude\',\'' . $val . '\')">' . $key . '</a> ';
                    }
                    ?>
                </td></tr>
                <tr><td >最近更新</td><td>
                    <input id="mtime" type="text" name="mtime" placeholder="更新時刻" value="<?=$mtime ?>" >
                    <?php
                    
                    $sizes = [
                        "6H" =>  0.25,
                        "12H" =>  0.5,
                        "1d" => 1,
                        "3d" => 3,
                        "10d" => 10,
                        "30d" => 30,
                        "50d" => 50,
                        "100d" => 100,
                    ];
                    foreach ($sizes as $key =>$val){
                        echo '<a href="javascript:setVal(\'mtime\',\'' . $val . '\')">' . $key . '</a> ';
                    }
                    ?>
                </td></tr>
                <tr><td nowrap>filesize</td><td>
                    <input type="text" id="mb_min" name="mb_min" size="6" value="<?=$mb_min ?>" placeholder="mb">
                    〜
                    <input type="text" id="mb_max" name="mb_max" size="6" value="<?=$mb_max ?>" placeholder="mb">

                    <?php
                    $sizes = ["10m" =>  (10) ,"30" =>  (30) ,"50" =>  (50) ,
                            "100" =>  (100) ,"500" =>  (500) ,
                            "1G" =>  (1024) ,"2G" =>  (2*1024) ,"3G" =>  (3*1024) ,
                            ];
                    foreach ($sizes as $key =>$val){
                        ?><a href="javascript:setVal('mb_min','<?=$val ?>')"><?=$key ?></a> <?php
                    }
                    ?>
                </td></tr>
                <tr><td nowrap>長さ（分）</td><td> 
                    <input type="text" id="minutes_min" name="minutes_min" size="6" value="<?=$minutes_min ?>" placeholder="minutes">
                    〜
                    <input type="text" id="minutes_max" name="minutes_max" size="6" value="<?=$minutes_max ?>" placeholder="minutes">
        
                    <?php
                    $sizes = ["-10" => [0,10] ,
                                "-30" => [10,30] ,
                                "-50" => [30,50],
                                "-100" =>[50,100],
                                "-200" =>[100,200],
                                "-400" =>[200,400],
                                "-1000" =>[400,1000]
                            ];
                    foreach ($sizes as $key =>$vals){
                        ?><a href="javascript:setVal('minutes_min','<?=$vals[0] ?>',false);setVal('minutes_max','<?=$vals[1] ?>')"><?=$key ?></a> <?php
                    }
        ?>
                </td></tr>
                <tr><td nowrap>chapter数</td><td> 
                    <input type="text" id="chapter_min" name="chapter_min" size="6" value="<?=$chapter_min ?>" placeholder="mb">
                    〜
                    <input type="text" id="chapter_max" name="chapter_max" size="6" value="<?=$chapter_max ?>" placeholder="mb">
                    
                    <?php
                    $sizes = ["0-10" => [0,10] ,
                                "10-30" => [10,30] ,
                                "30-50" => [30,50],
                                "50-100" =>[50,100],
                                "100-200" =>[100,200],
                                "200-400" =>[200,400],
                                "400-1000" =>[400,1000]
                            ];
                    foreach ($sizes as $key =>$vals){
                        ?><a href="javascript:setVal('chapter_min','<?=$vals[0] ?>',false);setVal('chapter_max','<?=$vals[1] ?>')"><?=$key ?></a> <?php
                    }
                    ?>
                </td></tr>
                <tr><td nowrap>codec</td><td> 
                    <input type="text" id="codec" name="codec" size="6" value="<?=$codec ?>" placeholder="codec">
                    <?php
                    $codecs = sql2asc("select v_codec,count(*) ct from movies GROUP BY v_codec",$pdo,false);
                    foreach ($codecs as $row){
                        $disp = $row['v_codec'];
                        if (!$disp) $disp = '取得不可';
                        ?><a href="javascript:setVal('codec','<?=$row['v_codec'] ?>')"><?=$disp . ' ' .  strGray($row['ct']) ?></a> &nbsp;<?php
                    }
                    ?>   

                    <input type="hidden" id="sort_col" name="sort_col" size="6" value="<?=$sort_col ?>" placeholder="sortcol">
                    <input type="hidden" id="sort_dir" name="sort_dir" size="6" value="<?=$sort_dir ?>" placeholder="sortdir">

                </td></tr>
                <tr><td >---</td><td> 

                    <input type="text" id="framesize" name="framesize" size="6" value="<?=$framesize ?>" placeholder="framesize" >
                    <a href="javascript:setVal('framesize',1920);">HD</a>
                    <a href="javascript:setVal('framesize',3840);">4K</a>
                </td></tr>
            </table>

            <input type="submit" style="display:none;">
        </form>
        <?php

        // sql生成
        $sql = "SELECT v_codec codec,width || 'x' || height fr_size ,minutes min,filesize_mb mb, v_bit_rate /1024 v_rate,chapters chap,
                '' sel,'' sand,'' brake,filepath,fps,
                a_codec,
                ctime,mtime,atime 
                FROM movies
                 ";
        $where = [];

        $filter = iconv("UTF-8","UTF-8-MAC", $filter); // web文字コードからmacファイル名の文字コードに変換
        $filters = preg_split("/\s+/",$filter);
        foreach ($filters as $f1){
            if ($f1) $where[] = " filepath like '%" .$f1 . "%'";
        }

        if ($mtime) $where[] = ' mtime > ' . (time() - ($mtime * 86400));
        if ($exclude) $where[] = " filepath not like '%" . $exclude . "%' ";        
        if ($rate) $where[] = " filepath like '%" . $rate . "%' ";    
        if ($codec) $where[] = " v_codec = '" . $codec . "'";

        if ($mb_min) $where[] = " filesize_mb > " . $mb_min;
        if ($mb_max) $where[] = " filesize_mb < " . $mb_max;
        if ($chapter_min) $where[] = " chapters > " . $chapter_min;
        if ($chapter_max) $where[] = " chapters < " . $chapter_max;
        if ($minutes_min) $where[] = " min > " . $minutes_min;
        if ($minutes_max) $where[] = " min < " . $minutes_max;

        if ($framesize) $where[] = " width= " . $framesize;

        if ($where ) $sql .= " where " . implode(" and ",$where);
        if ($sort_col) $sql .= " ORDER BY " . $sort_col . " " . $sort_dir . " ";
        $sql .= " LIMIT " . $QUERY_LIMIT;
        $files_ary = sql2asc($sql,$pdo);
        echo str150(count($files_ary)) . BR;

        ///// 検索ワード全部に件数を取得
        $no_file_ct = 0;
        if ($mode == "keyword_stat"){

            echo strRed("キーワード統計更新") . BR;
            echo strBlue('SQLite全件取得 ');
            $filepaths = sql2asc("select filepath from movies",$pdo);
            $paths = [];
            foreach ($filepaths as $row) $paths[] = $row['filepath'];

            $word_list = StatFiles::flatArray($words); // wordを1次元配列に
            $word_counts = StatFiles::countWordsInFilename($paths,$word_list); // カウント
            file_put_contents ($STAT_FILE_NAME,serialize($word_counts)); // 保存
            echo strBlue('filepath word match ');
            echo count($word_list) . strSilver(" word ") . array_sum($word_counts) . strSilver(" hit ") . BR;
            echo strBLue("save ") . strSilver(__DIR__ . '/') . $STAT_FILE_NAME . BR; 
            exit(strRed('finish'));
        }    

        // 表示用に加工
        foreach ($files_ary as &$row){
            $path = $row['filepath'];
            $path_disp = $row['filepath'];
            $path_disp = str_replace("../movie/","",$path_disp);
            $path_disp = preg_replace("@(.*/)@",strOrange('$1'),$path_disp); // スラッシュまで色変え
            $strlen = 100;
            if (mb_strlen($path_disp) >= $strlen) $path_disp = mb_substr($path_disp,0,$strlen) . "...";
            if ( $filter ) $path_disp = preg_replace("/(" .preg_quote($filter) . ")/mi",strRed('$1'),$path_disp);

            
            $row['sel'] = "<a href='javascript:setFinderSelect(\"" . rawurlencode($path) . "\")'>" . strSilver("sel") . "</a> ";
            $row['sand'] = "<a href='ffprobe.php?filepath=" . rawurlencode($path) . "'>" . strSilver("sand") . "</a> ";

            $row['filepath'] = "<a href='javascript:setOpen(\"" . rawurlencode($path) . "\")'>" . $path_disp . "</a> "; 
			//実験 
			$row['brake'] = "<a href='javascript:setHandBrakeOpen(\"" . rawurlencode($path) . "\")'>".strSilver('brake')."</a> ";

            if (!file_exists($path)){
                $row['filepath'] = strRevRed('no file') . ' ' . $row['filepath']; 
                $no_file_ct++;
            }
            
            $atime = $row['atime'];
            $ctime = $row['ctime'];
            $mtime = $row['mtime'];        
            $row['atime'] = date('Y-m-d',$atime);
            $row['ctime'] = date('Y-m-d H:i:s',$ctime);
            $row['mtime'] = date('Y-m-d',$mtime);
            if (time() - $atime < 86400 ) $row['atime'] = strBlue($row['atime']);
            if (time() - $ctime < 86400 ) $row['ctime'] = strBlue($row['ctime']);
            if (time() - $mtime < 86400 ) $row['mtime'] = strBlue($row['mtime']);
        }

        // 表示
        if ($no_file_ct) echo 'ファイル名変更 ' . strRevRed( $no_file_ct ) . BR;

        echo asc2htmlMovie($files_ary,$sort_col,$sort_dir)
        //echo asc2html($files_ary,false,false);

        ?>
    </td></tr></table>

<script>
    <?=showJS() ?>
</script>

<?php

function insertMovie($row){
    global $pdo;
    $cols =implode(",",array_keys($row));
    foreach ($row as $k => &$v) $v = SQLite3::escapeString($v);
    $vals="'" . implode("','",$row). "'";
    $sql = "insert into movies (".$cols.") values(". $vals .")";
    sqlExec($sql,$pdo,true);
}

function getMovieInfoRecord($line){

    $stat = stat($line); 
    $rec1 = ["filepath" => $line, "streams" => "", "chapters"=>"" ,"v_codec" =>"","v_tag"=>"","width"=>"","height"=>"","fps"=>"",
    "duration"=>"","minutes"=>"","v_bit_rate"=>"",
    "a_codec"=>"","a_tag"=>"","sample_rate"=>"","channels"=>"","channel_layout"=>"","a_bit_rate" => "",
    "filesize"=>$stat['size'],"filesize_mb"=>round($stat["size"] /1024/1024),
    "ctime"=>$stat["ctime"], "mtime"=>$stat["mtime"], "atime"=>$stat["atime"] ];

    if ($stat['size'] > 0){

        $info = get_ffprobeStreams($line);
        if (!$info) {
            echo strRed( 'ffprobe 失敗 ') . "<br/>";
            return $rec1;
        }
        $chapters = get_ffprobeChapters($line);
        $rec1['chapters'] = count($chapters['chapters']);

        $rec1['streams'] = count($info['streams']);
        echo  " " . count($info['streams']) . "<br/>";

        foreach ($info['streams'] as $row){
            if (!isset($codecs[$row['codec_type']])) $codecs[$row['codec_type']] = [];
            if (!isset($codecs[$row['codec_type']][$row['codec_name']])) $codecs[$row['codec_type']][$row['codec_name']] = 0;            
            $codecs[$row['codec_type']][$row['codec_name']]++;

            if ($row['codec_type'] == "video") {
                $rec1['v_codec'] = $row['codec_name'];
                $rec1['v_tag'] = $row['codec_tag_string'];
                $rec1['width'] = $row['width']; 
                $rec1['height'] = $row['height'];   
                $rec1['fps'] = $row['r_frame_rate'];   
                $rec1['duration'] = $row['duration'];  
                $rec1['minutes'] = round($row['duration'] / 60);  
                $rec1['v_bit_rate'] = $row['bit_rate'];                   
            }
            if ($row['codec_type'] == "audio") {
                $rec1['a_codec'] = $row['codec_name'];
                $rec1['channels'] = $row['channels'];
                $rec1['channel_layout'] = $row['channel_layout'];
                $rec1['sample_rate'] = $row['sample_rate'];
                $rec1['a_tag'] = $row['codec_tag_string'];
                $rec1['a_bit_rate'] = $row['bit_rate'];
            }
        }
    }else{
        echo strRed(" size 0 ");
    }
    echo "<br/>";
    return $rec1;
}


function asc2htmlMovie($assoc,$sort_col,$sort_order){ //クエリ結果連想配列

	$return="";
	$nullAttr="";
	if (count($assoc)==0) return;

	$return.= "\n<table  >\n ";

	//ヘッダ表示
    $return.= " <tr>";		
    foreach ($assoc[0] as $key => $value){



        // ソート指定してれば太字、太字をクリックなら順序をascに ソートは初期値desc 
        $disp = $key;
        $s_order = 'desc';  
        if ($key == $sort_col) {
            $disp = strBold($disp);
            if ($sort_order == 'asc' ){
                $disp .= ' △';
            }else{
                $s_order = 'asc';  
                $disp .= ' ▽';                
            } 
        }



        $return .= "  <th align='left' style='border-bottom: 2px solid silver;padding-right:10px;' nowrap >";
        if ($key=='sel' or $key == 'sand' ) {
            $return .= $key;
        }else{
            $return .= '  <a href="javascript:setVal(\'sort_col\',\'' . $key . '\',false); setVal(\'sort_dir\',\'' . $s_order . '\') ">' . $disp . "</a>";
        }

        $return .= "  </th>\n";
    }
    $return.= "</tr>";
	
	foreach ($assoc as $row){
		$return.= "    <tr >\n";
		foreach ($row as $key => &$value){
			$nullAttr="isnull=false";
			if ($value===null) { //nullと空白を区別
				$value="<span style='color:gray;font-style:italic;'>null</span>";
   			}
			$return.= '        <td '.
					 ' style="border-bottom: 1px solid silver ; padding-right:5px;" align="left" valign="top" nowrap >'.$value."</td>\n";
		}
		$return.= "    </tr>\n";
	}
	$return.= "</table>\n";
	return $return;
}

// 検索ワードリスト
function getFilterWordList(){

    $words['特筆'] = [
        "1" => ["トラック","男は","伊丹","あまちゃん","リーガル",]
    ];

    $words['鉄道'] = [
        "地域" => ["首都圏","鉄道 西","鉄道 北"],
            "鉄道会社" => ["メトロ","都営","西武","東急","JR"],
            "出版会社" => ["vicom","anec"],
            "電車タイプ" => ["特急","新幹線","普通","急行","バス","モノレール"],
            "タイプ" => ["道路","飛行機","鉄道"],
        ];    

    $words['日本映画'] = [
        "タグ" => ["やり直し","切れ","字幕なし"],
        "シリーズ" => ["渡り鳥","社長","若大将","サラリーマン"],
        "シリーズ2" => ["男はつらいよ","トラック","釣り"],
        "シリーズ3" => ["仁義なき","悪名","不良番長"],
    
        "監6070" => ["山本晋也","岡本喜八","今村","鈴木則文","市川崑","神代","黒澤"],
        "監8090 " => ["伊丹十三","滝田","根岸"],
    ];
    $words['海外映画'] = [
        "シリーズ" => ["スタローン","シュワ","ジャッキー"],
        "監督1" => ["ウディ アレン","バーホーベン","コーエン","ジョン ウー"],
        "監督2" => ["スピルバーグ","ルーカス"],
    ];


    $words['音楽'] = [
            "音楽1" => ["PSY"],
            "音楽" => ["reggae","motown","パンク","carpenters","woodstock",],
            "個人" => ["sting","santana","queen","jimi hendrix"],
            "音楽R&B" => ["rihanna","R Kelly","janet","michael","madonna"],
            "日本" => ["鈴木雅之","aska","竹内まりや","萩原健一","松田聖子"],    
            "日本2" => ["もんた","ゴールデンカップス","fishmans"],               
        ];

    $words['ほか'] = [
            "CM" => ["ジェミニ","CM","タンスにゴン","カップヌードル","ネスカフェ"],
            "ドキュメント" => ["document"],   
            "フィットネス" => ["BILLY"],       
        ];
    
    
    $words['アニメ'] = [
            "アニメ" => ["粘土","snoopy","moomin","anime"],
            "Pixar" => ["toy","finding","incredible","レミー","カール","walle"],
            "Illumination" => ["Illumination","sing","怪盗","ミニオン"],
            "アードマン" => ["Aardman","chicken run","ウォレス","ショーン"],
            "cartoon" => ["トムとジェリー","",""],
            "日本" => ["eva","gundam","cowboy bebop","lupin"],
        ];
        
    $words['ドラマ日本'] = [
            "刑事探偵" => ["あぶない刑事","警視K","踊る大捜査線"],
            "ホーム" => ["おしん","渡る世間"],
            "60-70" => ["ガードマン","大都会","傷だらけ"],
            "クドカン" => ["マンハッタン","リーガル","タイガー","あまちゃん"],
            "ドラマ " => ["ふぞろい","岸辺","派遣","最高の離婚","リーガルV","結婚できない男"],
            "君塚良一" => ["ナニワ金融道","踊る大捜査線"],
            "時代劇" => ["仕事人","暴れん坊将軍"],
        ];
    
    $words['ドラマ海外'] = [
            "女子" => ["satc","奥様は魔女","mylove"],
            "コメディ" => ["friends","Dharma Greg","Different Strokes","monty python"],
            "男" => [ "Knight Rider","Miami Vice","west wing"],
            "刑事" => ["コロンボ","ポアロ",""],
            "スパイ" => ["スパイ大作戦",""],
            "SF" => [ "ビジター","DarkAngel","star gate","twilight zone"],
            "SF2" => [ "レッドドワーフ","宇宙大作戦"],
        ];
    return $words;

}
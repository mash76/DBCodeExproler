<?php
$CACHE_FILE_NAME = 'cache/gdrive_stat.txt';

$words['kinou'] = ["基本設計","詳細設計","開発","保守","資料","定義","抽出","error","依頼","クリエイティブ"];
$words['test'] = ["テスト","負荷","結合","便利","キャプチャ","upload","test"];
$words['aws'] = ["AWS","CloudWatch","Lambda","フロント","アカウント","ダンプ","cron","バックアップ","マイグレーション"];
$words['mtg'] = ["MTG","定例","システム定例","アジェンダ"];

$words['year'] = ["2021","2022","2023"];
$words['ext'] = ['xlsx$','docx$','pptx$',"pdf$",'gsheet$','gdoc$','txt$','ai$','xd$','png$','mp4$'];

include 'inc.php';


// param
$filter = getRequest('filter');
$filter_utf8mac = iconv( "UTF-8","UTF-8-MAC", $filter);

$year = getRequest('year');
$fsize_min = getRequest('fsize_min');
$ext = getRequest('ext');
$mode = getRequest('mode');
$mtime = getRequest('mtime');

$ajaxfinder = getRequest('ajaxfinder');
if ($ajaxfinder){
    $shell ='open -R "' . rawurldecode($ajaxfinder) . '"';
   // $shell = 'open "' . rawurldecode($ajaxfinder) . '"' ;
    runShell($shell);
    exit();
}
$ajaxopen = getRequest('ajaxopen');
if ($ajaxopen){
    $shell = 'open "' . rawurldecode($ajaxopen) . '"' ;
    runShell($shell);
    exit();
}

if (file_exists($CACHE_FILE_NAME)){
    $cache_str = file_get_contents($CACHE_FILE_NAME );
    $stat = unserialize($cache_str);
}

htmlHeader("google drive" );
menu();

echo str150('GoogleDrive') .' ' ;
echo '<a href="?mode=setstat" class="cache-link">stat更新</a>';
echo strSilver($GOOGLE_DRIVE_PATH);

?>

<form id="f1">
    n日以内更新<input type="text" id="mtime" name="mtime" value="<?=$mtime ?>">
    <?php
	$ary = [1,"2","5","10","20" ,"30","40","50","100","200"];
	foreach ($ary as $name) {
		?><a href="javascript:setVal('mtime','<?=$name ?>')"><?=$name ?>日</a> <?php
	}
    ?>
    <br/>
	業務<input type="text" id="filter" name="filter" value="<?=$filter ?>">
    <br/>
	<?php
    foreach ($words as $cate_name => $row) {
        echo strBold($cate_name . ' ');
        foreach ($row as $name) {
            $name_utf8mac = iconv( "UTF-8","UTF-8-MAC", $name);
            $disp = $name_utf8mac;

            if ($filter_utf8mac == $name_utf8mac) $disp = strBold($name_utf8mac);
            ?><a href="javascript:setVal('filter','<?=$name_utf8mac ?>')"><?=$disp ?></a> <?php
            if (isset($stat[$name_utf8mac])) echo strGray($stat[$name_utf8mac] . ' ');
        }
        echo BR;
    }
	?>
	year<input type="text" id="year" name="year" value="<?=$year ?>">
    <?php
	foreach ($words['year'] as $name) {
		?><a href="javascript:setVal('year','<?=$name ?>')"><?=$name ?></a> <?php
        if (isset($stat[$name])) echo strGray($stat[$name] . ' ');
	}
	?>
    <br/>

    ext<input type="text" id="ext" name="ext" value="<?=$ext ?>">
	<?php

	foreach ($words['ext'] as $name) {
		?><a href="javascript:setVal('ext','<?=$name ?>')"><?=$name ?></a> <?php
        if (isset($stat[$name])) echo strGray($stat[$name] . ' ');
	}
	?>
    <br/>
    filesizeが**k以上<input type="text" id="fsize_min" name="fsize_min" value="<?=$fsize_min ?>">
    <?php
	$ary = ["2","5","10","100","1000","10000"];
	foreach ($ary as $name) {
		?><a href="javascript:setVal('fsize_min','<?=$name ?>')"><?=$name ?>k</a> <?php
	}
    ?>


    <input type="submit" style="display:none;" >
</form>
<script>
    function setVal(name,val){
        $('#' + name).val(val)
        $('#f1').submit()
    }
    function openFinder(path){
        $.get("?ajaxfinder=" + path)
    }
    function open(path){
        $.get("?ajaxopen=" + path)
    }
    $('input').dblclick((obj) =>{
        $(obj.target).val("")
        $('#f1').submit()
    })
</script>

<?php



// stat更新
if ($mode== "setstat"){
    $stat = [];
    foreach ($words as $key => $vals){
        echo strBlue($key. ' ');
        foreach ($vals as $val){
            $val_utf8mac = iconv( "UTF-8","UTF-8-MAC", $val);
            $shell_ct = 'find "' . $GOOGLE_DRIVE_PATH . '" -type f ';
            $shell_ct .= ' | grep -i "' . $val_utf8mac . '" | grep -iv /vendor/ | wc -l';
            $count = runShell($shell_ct,false);
            echo $val_utf8mac . ' ' . $count . ' ';
            $stat[$val_utf8mac] =$count;
        }
        echo BR;
    }
    file_put_contents($CACHE_FILE_NAME, serialize($stat));
    exit();
}

// find 作成
$shell = 'find "' . $GOOGLE_DRIVE_PATH . '" -type f ';
if ($fsize_min) $shell .= " -size +" . $fsize_min . "k  " ;
if ($mtime) $shell .= " -mtime -" . $mtime;
if ($filter) $shell .= ' | grep -i "' . $filter_utf8mac . '"';
if ($year) $shell .= ' | grep -i "' . $year . '"';
if ($ext) $shell .= ' | grep -i "' . $ext . '"';
$shell .= " | sort ";


$files = runShellAry($shell);
echo str150(count($files)) . BR;

$ext_ary = statExt($files);
foreach ($ext_ary as $extname => $count){
    echo strBlue($extname) . ' ' . strSilver($count) . ' ';
}
echo "<br/>";

foreach ($files as $line){

    $fsize_num = filesize($line);
    $line1 = str_replace($GOOGLE_DRIVE_PATH,'',$line);
    if ($filter) $line1 = markRed($line1,$filter_utf8mac);
    if ($year) $line1 = markRed($line1,$year);
    if ($ext) $line1 = markRed($line1,$ext);

    $fsize_num = round($fsize_num/1024/1024,3);
    $fsize_disp = $fsize_num;
    if ($fsize_num < 1) $fsize_disp = strGray($fsize_disp);
    if ($fsize_num > 5) $fsize_disp = strOrange($fsize_disp);
    if ($fsize_num > 50) $fsize_disp = strBold($fsize_disp);

    echo '<a href="javascript:open(\'' . $line . '\')">' . $line1 . '</a>' ;
    echo  ' <a style="color:silver;" href="javascript:openFinder(\'' . $line . '\')" >finder</a> ' . " ";
    echo ' <a style="color:silver;" href="javascript:open(\'' . $line . '\')" >open</a> ' . $fsize_disp . strSilver('mb') . BR;
}

exit();
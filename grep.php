<?php
include 'inc.php';

$SOURCE_PATH = trim(`pwd`);
$CACHE_FILE_NAME = 'cache/grep_stat.txt';

// param
$filter = getRequest('filter');
$view = getRequest('view');
$batch = getRequest('batch');
$cate0 = getRequest('cate0');
$notin = getRequest('notin',false,"/vendor/");
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

htmlHeader("customers " );
menu();
echo str150('grep') . SPC . strSilver($SOURCE_PATH) . SPC;

$words = [];
?>
<a href="?mode=setstat" class="cache-link">stat更新</a>

<form id="f1">

    n日以内更新<input type="text" id="mtime" name="mtime" value="<?=$mtime ?>">

    <?php
	$ary = ["2","5","10","20" ,"30","40","50","100","200"];
	foreach ($ary as $name) {
		?><a href="javascript:setVal('mtime','<?=$name ?>')"><?=$name ?>日</a> <?php
	}
    ?>
    <br/>
	業務<input type="text" id="filter" name="filter" value="<?=$filter ?>">
	<?php
	$words['kinou'] = ["Corporate","Coupon","Reservation"];
	foreach ($words['kinou'] as $name) {
		?><a href="javascript:setVal('filter','<?=$name ?>')"><?=$name ?></a> <?php
        if (isset($stat[$name])) echo strSilver($stat[$name] . ' ');
	}
	?>
    <br/>

	カテ<input type="text" id="cate0" name="cate0" value="<?=$cate0 ?>">
	<?php
	$words['module']  = ["/api/tmp/cache/", "/Config/","/src/","/webroot/","/tests/", "composer"];
	foreach ($words['module'] as $name) {
		?><a href="javascript:setVal('cate0','<?=$name ?>')"><?=$name ?></a> <?php
        if (isset($stat[$name])) echo strSilver($stat[$name] . ' ');
	}
	?>
    <br/>

	バッチ<input type="text" id="batch" name="batch" value="<?=$batch ?>">
	<?php
	$words['batch']  = ['*Batch' ,'Command','*Batch' ,'Command',
            '*DB', 'Model',"Migrations",
            '*TEST',"Tests","TestCase","Fixture"
        ];
	foreach ($words['batch'] as $name) {
        if (strpos($name,"*") !==false) {
            echo str_replace("*","",$name). " ";
            continue;
        }
		?><a href="javascript:setVal('batch','<?=$name ?>')"><?=$name ?></a> <?php
        if (isset($stat[$name])) echo strSilver($stat[$name] . ' ');
	}
	?>
    <br/>

	view<input type="text" id="view" name="view" value="<?=$view ?>">
	<?php
	$words['view']  = [
                "*Routes" , "routes","htaccess","apache","conf",
                "*View","Template","Layout","Element","Error","/css/",
                "*Controller","Controller","Component"
                ];
	foreach ($words['view'] as $name) {
        if (strpos($name,"*") !==false) {
            echo str_replace("*","",$name). " ";
            continue;
        }
		?><a href="javascript:setVal('view','<?=$name ?>')"><?=$name ?></a> <?php
        if (isset($stat[$name])) echo strSilver($stat[$name] . ' ');
	}
	?>
    <br/>

    ext<input type="text" id="ext" name="ext" value="<?=$ext ?>">
	<?php
	$words['ext'] = ["sh$","ctp$",'php$','yml$','json$','js$','ts$','md$','html$','xml$','png$','jpg$','cfg$'];
	foreach ($words['ext'] as $name) {
		?><a href="javascript:setVal('ext','<?=$name ?>')"><?=$name ?></a> <?php
        if (isset($stat[$name])) echo strSilver($stat[$name] . ' ');
	}
	?>
    <br/>


	除外<input type="text" id="notin" name="notin" value="<?=$notin ?>">
	<?php
	$words['exclude'] = ["/vendor/","/cache/",'/plugins/'];
	foreach ($words['exclude'] as $name) {
		?><a href="javascript:setVal('notin','<?=$name ?>')"><?=$name ?></a> <?php
        if (isset($stat[$name])) echo strSilver($stat[$name] . ' ');
	}
	?>
    <br/>
    filesize<input type="text" id="fsize_min" name="fsize_min" value="<?=$fsize_min ?>">〜
    <?php
	$ary = ["2","5","10"];
	foreach ($ary as $name) {
		?><a href="javascript:setVal('fsize_min','<?=$name ?>')"><?=$name ?>k</a> <?php
	}
    ?>
    grep<input type="text" id="grep" name="grep" value="<?=$fsize_min ?>">
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
            $shell_ct = 'find "' . $SOURCE_PATH . '" -type f ';
            $shell_ct .= ' | grep -i "' . $val . '" | grep -iv /vendor/ | wc -l';
            $count = runShell($shell_ct,false);
            echo $val . ' ' . $count . ' ';
            $stat[$val] =$count;
        }
        echo BR;
    }
    file_put_contents($CACHE_FILE_NAME, serialize($stat));
    exit();
}

// find 作成
$shell = 'find "' . $SOURCE_PATH . '" -type f ';
if ($fsize_min) $shell .= " -size +" . $fsize_min . "k  " ;
if ($mtime) $shell .= " -mtime -" . $mtime;
if ($filter) $shell .= ' | grep -i "' . $filter . '"';
if ($view) $shell .= ' | grep -i "' . $view . '"';
if ($batch) $shell .= ' | grep -i "' . $batch . '"';
if ($cate0) $shell .= ' | grep -i "' . $cate0 . '"';
if ($notin) $shell .= ' | grep -iv "' . $notin . '"';
$shell .= ' | grep -iv "DS_Store"';
if ($batch) $shell .= ' | grep -i "' . $batch . '"';
if ($ext) $shell .= ' | grep -i "' . $ext . '"';

$files = runShellAry($shell);
echo str150(count($files)) . BR;

$ext_ary = statExt($files);
foreach ($ext_ary as $extname => $count){
    echo strBlue($extname) . ' ' . strSilver($count) . ' ';
}
echo "<br/>";

foreach ($files as $line){

    $fsize_num = filesize($line);
    $line1 = str_replace($SOURCE_PATH,'',$line);
    if ($filter) $line1 = markRed($line1,$filter);
    if ($view) $line1 = markRed($line1,$view);
    if ($cate0) $line1 = markRed($line1,$cate0);
    if ($batch) $line1 = markRed($line1,$batch);
    if ($ext) $line1 = markRed($line1,$ext);

    $fsize_num = round($fsize_num/1024,1);
    $fsize_disp = $fsize_num;
    if ($fsize_num < 2) $fsize_disp = strSilver($fsize_disp);
    if ($fsize_num > 5) $fsize_disp = strOrange($fsize_disp);
    if ($fsize_num > 10) $fsize_disp = strBold($fsize_disp);

    echo ' <a style="color:silver;" href="javascript:openFinder(\'' . $line . '\')" >finder</a> ' . " ";
    echo ' <a style="color:silver;" href="javascript:open(\'' . $line . '\')" >open</a> ' . $fsize_disp . " " ;
    echo $line1 . BR;
}

exit();
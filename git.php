<?php  /* gitを便利に扱う 複数リポジトリ */
include 'inc.php';

$SQLITE_PATH = "cache/git.sqlite";
$sqlite = sqliteConnect($SQLITE_PATH); // incで接続したmysqlをsqliteで上書き

/*
cd devtools/docker/php
git clone https://github.com/twigphp/Twig.git
git clone https://github.com/ruby/erb.git

*/



if (false ){
    sqlExec('
    CREATE TABLE "commit_files" (
        "repo"	TEXT NOT NULL,
        "hash"	TEXT NOT NULL,
        "filename"	TEXT NOT NULL,
        "author"	TEXT,
        "date"	TEXT,
        "adds"	INTEGER,
        "dels"	INTEGER,
        PRIMARY KEY("repo","hash","filename"))
    ',$sqlite);

    sqlExec('
    CREATE TABLE "commits" (
        "repo"	TEXT NOT NULL,
        "hash"	TEXT NOT NULL,
        "author"	TEXT,
        "message"	TEXT,
        "date"	TEXT,
        PRIMARY KEY("repo","hash"))
    ',$sqlite);


}

$repo_ary = ["twig" => "/Twig","erb"=>"erb"];

$view = getRequest('view',false,"commit");
$upd = getRequest('upd');

$hash = getRequest('hash');
$repo = getRequest('repo');
$add_rows = getRequest('add_rows');
$filter = getRequest('filter');
$filename = getRequest('filename');
$is_show_files = getRequest('is_show_files');

htmlHeader("git ") ;
menu();
echo str150('git ') .strSilver($GIT_ROOT);
?>

<a href="?upd=sqlite_import" class="cache-link" >gitlogをsqlite取り込み</a>
<br/>

<?php
$view_ary = ["commit","author","mon"];
foreach ($view_ary as $viewname) {
    $disp = $viewname;
    if ($disp == $view) $disp = strBold($disp);
    ?><a href="?repo=<?=$repo ?>&view=<?=$viewname ?>"><?=$disp ?></a> <?php
}

?>

<form id="f1">
	リポジトリ<input type="text" id="repo" name="repo" size="6" value="<?=$repo ?>" placeholder="repo">
	<?php
	foreach ($repo_ary as $name=>$path) {
		?><a href="javascript:setVal('repo','<?=$name ?>')"><?=$name ?></a> <?php
	}
	?>
    <br/>
	変更行数<input type="text" id="add_rows" name="add_rows" size="15" value="<?=$add_rows ?>" placeholder="変更行数">
	<?php
	foreach ([2,5,10,20,30,50,100] as $name) {
		?><a href="javascript:setVal('add_rows','<?=$name ?>')"><?=$name ?></a> <?php
	}
	?>
    <br/>
	commitメッセージ<input type="text" id="filter" name="filter" size="15" value="<?=$filter ?>" placeholder="commit文">
    <br/>
	ファイル名<input type="text" id="filename" name="filename" size="15" value="<?=$filename ?>" placeholder="ファイル名">
    <?php
	foreach (["Migrations","Command","Customers","Coupon"] as $name) {
		?><a href="javascript:setVal('filename','<?=$name ?>')"><?=$name ?></a> <?php
	}
	?>

    <br/>
	<input type="checkbox" id="is_show_files" name="is_show_files" size="15" value="1" <?=$is_show_files ? " checked" : "" ?> > ファイル名も表示(遅い)
    <br/>

	<input type="submit" style="display:none;" >
</form>
<script>
    function setVal(name,val){
        $('#' + name).val(val)
        $('#f1').submit()
    }
    $('input').dblclick((obj) =>{
        $(obj.target).val("")
        $('#f1').submit()
    })

	$('#filter').focus()
    $('input:checkbox').click(()=>{
        $('#f1').submit()
    })

</script>

<?php
// sqlite import
if ($upd=="sqlite_import"){

    sqlExec("delete from commits ",$sqlite);
    sqlExec("delete from commit_files ",$sqlite);

    foreach ($repo_ary as $repo_name => $path) {
        $repo_path = $GIT_ROOT . $path ;

        $shell_log = 'git -C '. $repo_path . " log --numstat --pretty='hash\t%h\t%an\t%cd\t%s' --date=format:'%Y/%m/%d %H:%M:%S' --no-merges";
        $ret_str = runShell($shell_log);
        $ret_ary = explode("\n", $ret_str);

        $hash = "";
        $author = "";
        $date = "";
        foreach ($ret_ary as $line){
            if (substr($line,0,4) == "hash"){
                echo BR;
                list ($no,$hash,$author,$date,$message) = explode("\t",$line);
                $current_hash = $hash;
                //echo $line . BR;
                echo $current_hash . ' ' . strOrange($date. ' ') . $author . ' '. strOrange($message) . BR;
                $sql_insert = "insert into commits (repo,hash,author,message,date)
                    values('". SQLite3::escapeString($repo_name) ."',
                    '". SQLite3::escapeString($hash) ."',
                    '". SQLite3::escapeString($author) ."',
                    '". SQLite3::escapeString($message) ."',
                    '". SQLite3::escapeString($date) ."')";
                sqlExec($sql_insert,$sqlite);
            }

            if (is_numeric(substr($line,0,1))){
                echo strRed($line) . BR;
                list($adds,$dels,$filename ) = preg_split("/\t+/",$line);
                $sql_insert = "insert into commit_files (repo,hash,filename,author,date,adds,dels)
                    values('". SQLite3::escapeString($repo_name) ."',
                    '". SQLite3::escapeString($hash) ."',
                    '". SQLite3::escapeString($filename) ."',
                    '". SQLite3::escapeString($author) ."',
                    '". SQLite3::escapeString($date) ."',
                    '". SQLite3::escapeString($adds) ."',
                    '". SQLite3::escapeString($dels) ."')";
                sqlExec($sql_insert,$sqlite);
            }
        }
    }
    sqlExec("update commits set dels = (select sum(dels) from commit_files where hash = commits.hash )",$sqlite);
    sqlExec("update commits set adds = (select sum(adds) from commit_files where hash = commits.hash )",$sqlite);
    sqlExec("update commits set files = (select count(*) from commit_files where hash = commits.hash )",$sqlite);
    exit();
}

// mon 月単位分析
if ($view=="mon"){
    $shell_gitlog_mon = 'git -C '.$GIT_ROOT ."/" . $repo . " log --date=format:'%y.%m' --pretty=format:'%an\t%cd'";
    $ret_ary = runShellAry($shell_gitlog_mon);
    $monstat = [];
    $author_list = [];
    $mon_list = [];
    // 集計
    foreach ($ret_ary as $line) {
        list($author,$mon) = explode("\t",trim($line));
        if (!isset($monstat[$author])) $monstat[$author] = [];
        if (!isset($monstat[$author][$mon])) $monstat[$author][$mon] = 0;
        $monstat[$author][$mon]++;
        if (!isset($mon_list[$mon])) $mon_list[$mon] = 0;
        $mon_list[$mon]++;
        if (!isset($author_list[$author])) $author_list[$author] = 0;
        $author_list[$author]++;
    }
    ksort($mon_list);
    $recs = [];
    // 集計と取り出し
    foreach ($author_list as $author => $author_sum){
        $recs[$author] = ['author' => $author];
        $commit_sum =0;
        foreach($mon_list as $mon => $mon_sum){
            $recs[$author][$mon] = 0;
            if (isset($monstat[$author][$mon]) ) $recs[$author][$mon] = $monstat[$author][$mon];
            $commit_sum += $recs[$author][$mon];

          //  $recs[$author][$mon] = strZeroSilver($recs[$author][$mon]);
        }
        $recs[$author]['sum'] = $commit_sum;
    }
    $recs['sum'] = ['author' => 'sum'];
    foreach ($mon_list as $mon => $mon_sum){
        $recs['sum'][$mon] = $mon_sum;
    }
    echo asc2htmlGit($recs,false,false);
    exit();
}

// $shell_gitlog_simple = 'git -C '.$GIT_ROOT ."/" . $repo . " log --date=format:'%y/%m/%d' --pretty=format:'%h\t%an\t%cd\t%s'";


// author 人単位集計
if ($view == "author"){
    $stat_author = sql2asc("select author ,count(*) commit_ct, min(date) start,max(date) end
        from commits group by author order by end desc",$sqlite);
    echo asc2html($stat_author);
}

// hash ハッシュ単位詳細チェック
if ($view == "hash"){

    $sql = "select filename, adds, dels,author,date
         from commit_files where hash ='". $hash ."'";
    $rets = sql2asc($sql,$sqlite);
    echo asc2html($rets);

    $repo_path = $GIT_ROOT . $repo_ary[$repo];
    $shell_diff = 'git -C '.$repo_path . ' diff ' . $hash . '~..' . $hash;
    $ret_str = trim(runShell($shell_diff));
    $ret_ary = explode("\n",$ret_str);
    $ret_ary = colorGitDiff($ret_ary);

    echo '<hr/>';
    echo '<pre>' . implode("\n",$ret_ary) . '</pre>';
}


// commit一覧
if ($view=="commit"){

    // sql作成
    $sql = "select * from commits ";
    $where =[];
    if ($repo) $where[] = " repo='" . $repo . "'";
    if ($add_rows) $where[] .= " (adds >=" . $add_rows . " or dels >=". $add_rows ." ) ";
    if ($filter) {
        $where[] = " (message like '%" . SQLite3::escapeString( $filter) . "%'
        or author like '%" . SQLite3::escapeString( $filter) . "%'
        or hash like '%" . SQLite3::escapeString( $filter) . "%' )
        ";
    }
    if ($filename) {
        $where[] = " exists (
        select * from commit_files where hash=commits.hash and filename like '%" . SQLite3::escapeString( $filename) . "%' )
        ";
    }

    if ($where) $sql .= " where " . implode(' and ', $where);
    $sql .= " order by date desc";
    $records = sql2asc($sql ,$sqlite);
    foreach ($records as &$row) {
        $row['author'] = strTrim($row['author'],16);
        $row['message'] = strTrim($row['message'],90,true);
    }
    foreach ($records as &$row){
        $hash = $row['hash'];
        $hash_disp = $hash;
        if ($filter){
            $row['message'] = markRed($row['message'],$filter);
            $row['author'] = markRed($row['author'],$filter);
            $hash_disp = markRed($hash_disp,$filter);
        }
        $row['hash'] = '<a href="?repo=' . $row['repo'] . '&view=hash&hash=' .$hash. '">' . $hash_disp .'</a>';

        if ($is_show_files){
            $sql = "select filename,adds,dels from commit_files where hash='" . $hash . "'";
            $ret = sql2asc($sql,$sqlite,false);
            foreach ($ret as $row2){
                $row['message'] .= BR . ' &nbsp; ' .  strBlue(markRed($row2['filename'],$filename)). ' ' . $row2['adds'] . ' ' . $row2['dels'];
            }

        }
    }
    echo count($records). BR . asc2html($records,false,false);
}

// commit 結果表示
function asc2htmlGit($assoc){
    $return="";
    $return.= "\n<table  >\n ";
    //ヘッダ表示
    $return.= " <tr id='header' name='header' >";
    foreach ($assoc as $row) {
        foreach ($row as $key => $value){//*task
            $return.= "  <th align='left' style='border-bottom: 2px solid silver;padding-right:10px;'{$key}</th>\n";
        }
        break;
    }
    $return.= "</tr>";
    foreach ($assoc as $row){
        $return.= "    <tr >\n";
        foreach ($row as $colname => $value){

            $bg="";
            if ($colname != "author" and $colname != "sum" ){


                if ($value >= 30 ) $value = strBold($value);
            }

            if ($value == 0 ) {
                $value = strSilver($value);
                $bg = "#eee";
            }

            $return.= '        <td style="border-bottom: 1px solid silver ; padding-right:5px; background:' . $bg. ';" align="left" valign="top" nowrap >'.$value."</td>\n";
        }
        $return.= "    </tr>\n";
    }
    $return.= "</table>\n";
    return $return;
}

function colorGitDiff($lines){

    $ret_lines = [];
    foreach($lines as &$line){
        $ret_lines[] = $line;
        if (strpos($line,'+++') === 0 ) {
            $ret_lines[] = '-----------------------------------------------------------------------------------------------------------------------';
        }
    }

    foreach($ret_lines as &$line){
        $line = htmlentities($line);
        if (strpos($line,'-') === 0 ) $line = strRed($line);
        if (strpos($line,'+') === 0 ) $line = strGreen($line);
        if (strpos($line,'@@') === 0 ) $line = strOrange($line);
        if (strpos($line,'diff') === 0 ) {
            $ret_lines[] = '';
        }

    }


    return $ret_lines;

}

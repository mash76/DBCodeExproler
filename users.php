<?php

include 'inc.php';

$filter = getRequest('filter');
$user_id = getRequest('user_id');
$view = getRequest('view' ,false ,'tables' );

htmlHeader($_SESSION['current_env_name']  . ' user ' . $user_id);
menu();

echo str150('<a href="?">Users</a> ユーザー ');
echo str150(RDBtableRowCt('users',$pdo)) ;

echo ' <a class="admin-link" href="' . $ADMIN_URL_BASE . 'aaa">一覧</a>' ;
echo "add ";
echo ' <a class="upd-link" href="?upd=add_card_all">cards</a>' ;
echo ' <a class="upd-link" href="?upd=add_talk_all">talks</a>' ;
echo ' <a class="upd-link" href="?upd=add_log_all">logs</a>' ;

// sessionで履歴
$sess_name = $_SESSION['current_env_name'] . ' users ';
if (!isset($_SESSION[$sess_name])) $_SESSION[$sess_name] = [];
foreach ($_SESSION[$sess_name] as $key => $val){
    ?> <a class="history-link" href="?user_id=<?=$key ?>"><?=$val?></a> <?php
}
echo BR;

$upd = getRequest('upd');

if ($upd=="add_card_all"){
    $user_ids = array_keys($DATA['user_id']);
    $card_ids = array_keys($DATA['card_id']);
    for ($i =0; $i<=100; $i++){
        shuffle($user_ids);
        shuffle($card_ids);
        $sql = "insert into user_cards (user_id,card_id,created) values(".$user_ids[0].",".$card_ids[0].",now())";
        sqlExec($sql, $pdo);
    }
}
if ($upd=="add_talk_all"){
    $user_ids = array_keys($DATA['user_id']);
    for ($i =0; $i<=100; $i++){
        shuffle($user_ids);
        $sql = "insert into user_talks (user_id,message) values(".$user_ids[0].",'sahdasghdgh" . $i . "')";
        sqlExec($sql, $pdo);
    }
}
if ($upd=="add_log_all"){
    $user_ids = array_keys($DATA['user_id']);
    for ($i =0; $i<=100; $i++){
        shuffle($user_ids);
        $sql = "insert into user_logs (user_id,body,created) values(".$user_ids[0].",'log_aaaa" . $i . "',now())";
        sqlExec($sql, $pdo);
    }
}

?>
<form id="f1">
	<input type="text" id="filter" name="filter" value="<?=$filter ?>">
	<?php
	$ary = ["aaaa","aaa","bbb"];
	foreach ($ary as $name) {
		?><a href="?filter=<?=$name ?>"><?=$name ?></a> <?php
	}
	?>
    <br/>
	<input type="submit" style="display:none;" >
</form>
<script>
    <?=commonJS() ?>
</script>

<?php
if ($user_id){

    $sql_cus = "select * from users where id=" . $user_id;
    $cus = assertZero('customer_id ',sql2asc($sql_cus,$pdo,false));
    $cus_row = $cus[0];
    echo asc2html($cus);

    // ユーザー内メニュー
    $menus = ["tables","detail"];
    foreach ($menus as $name){
        $disp = $name;
        if ($view == $name) $disp = strBold($name);

        echo '<a href="?user_id=' . $user_id . '&view=' . $name . '">' . $disp . '</a> ';
    }
    echo '<br/>';

    // tables
    if ($view == "tables"){

        $sql3 = "select TABLE_NAME from information_schema.COLUMNS
            where TABLE_SCHEMA='" .$current_env['schema']. "' and COLUMN_NAME = 'user_id' ";
        $records = sql2asc($sql3,$pdo,false);
        foreach ($records as $row){
            $sql3 = "select * from `" . $row['TABLE_NAME'] . "`
                where user_id=" . $user_id . " limit 10";
            echo strTableName($row['TABLE_NAME']) . ' ';
            $recs1 = sql2asc($sql3,$pdo);
            echo asc2html($recs1);
        }
    }
    exit();
}

// 一覧
$sql1 = "select * from users " ;
$sql1 = 'select id ,name,
    (select count(*) from user_cards where user_id = users.id) _cards,
    (select count(*) from user_talks where user_id = users.id) _talks,
    (select count(*) from user_logs where user_id = users.id) _logs,
    created,updated from users ' ;
$where =[];
if ($filter){
    $where[] = " ( id like '%" . $filter . "%' or name like '%" . $filter . "%' ) ";
}
//$sql1 .= " order by created desc";
$sql1 .= " limit 400";

$ret = sql2asc($sql1,$pdo);
foreach ($ret as &$row){
    $id = $row['id'];
    $row['name'] = strTrim($row['name'] . "",8);
    $row['name'] = '<a href="?user_id='. $row['id'] . '">' . $row['name'] . "</a>";
    $row = rowMarkRed($row,$filter);
}
$ret = setNames2Rec($ret,'customers');

echo str150(count($ret));
echo asc2html($ret,false,false);

echo debugFooter();
exit();

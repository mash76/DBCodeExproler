<?php
include 'inc.php';

$filter = getRequest('filter');
$card_id = getRequest('card_id');
$view = getRequest('view' ,false ,'tables' );


htmlHeader($_SESSION['current_env_name']  . ' card ' . $card_id);
menu();
echo str150('<a href="?">Cards</a> カード ');

echo str150(RDBtableRowCt('cards',$pdo)) ;

echo ' <a class="admin-link" href="' . $ADMIN_URL_BASE . 'customer-list">一覧</a>' ;


$sess_name = $_SESSION['current_env_name'] . ' cards ';
if (!isset($_SESSION[$sess_name])) $_SESSION[$sess_name] = [];
foreach ($_SESSION[$sess_name] as $key => $val){
    ?> <a class="history-link" href="?user_id=<?=$key ?>"><?=$val?></a> <?php
}
echo BR;
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
if ($card_id){

    $upd = getRequest('upd');



    $sql_cus = "select * from cards where id=" . $customer_id;
    $cus = assertZero('customer_id ',sql2asc($sql_cus,$pdo,false));
    $cus_row = $cus[0];

    $_SESSION[$sess_name][$customer_id] = $cus_row['line_display_name'];

    $sql_cars = "select * from cars where customer_id=" . $customer_id;
    $cars = sql2asc($sql_cars,$pdo,false);
    $sql_user = "select * from users where id=" . $cus_row['user_id'];
    $users = sql2asc($sql_user,$pdo,false);
    $user_row = $users[0];
    $sql_corpo = "select * from corporates where id=" . $user_row['corporate_id'];
    $corpo = sql2asc($sql_corpo,$pdo,false);
    $corpo_row = $corpo[0];

    // 会社名 店舗名
    echo strSilver(' user ' . $user_row['id']). ' ' . $user_row['name'];
    echo BR;

    // 顧客名など $cus_row['name']
    echo str150(
        $customer_id . ' <span class="linename">' . $cus_row['name'] . '</span> ' . $cus_row['line_display_name']);
    // echo ' <img height="50" src="' . $cus_row['line_image'] . '"/>';

    // disable
    if (!$cus_row['enabled'] ) echo ' ' . strRevRed('&nbsp;論理削除 ') . ' &nbsp; ';

    echo ' <a class="upd-link" href="?customer_id=' . $customer_id . '&upd=enable_user">顧客有効</a>';
    echo ' <a class="upd-link" href="?customer_id=' . $customer_id . '&upd=disable_user">無効</a>';

    echo '<a class="admin-link" href="' . $ADMIN_URL_BASE . 'customer-detail/' . $customer_id . '?tab=kihon">詳細</a> ';
    echo '<a class="admin-link" href="' . $ADMIN_URL_BASE . 'customer-edit/' . $customer_id . '?tab=kihon">編集</a> ';

    echo strSilver(' car ');

    echo ' <a class="upd-link" href="?customer_id=' . $customer_id . '&upd=add_car">car追加</a>';
    echo ' <a class="upd-link" href="?customer_id=' . $customer_id . '&upd=del_car">削除</a>';

   // echo ' <img height="50" src="' . $corpo_row['follow_image'] . '"/>';

    echo BR;
    echo 'customers '. asc2html($cus);
    echo 'cars ' . asc2html($cars);

    if (!$corpo_row['management_user_id'] ) echo ('<span class="alert">会社の management_user_id なし</span><br/>');


    // ユーザー内メニュー
    $menus = ["tables","detail","coupon_tags","coupon_deli","coupons","last_use_logs"];
    foreach ($menus as $name){
        $disp = $name;
        if ($view == $name) $disp = strBold($name);

        echo '<a href="?customer_id=' . $customer_id . '&view=' . $name . '">' . $disp . '</a> ';
    }
    echo '<br/>';

    // tables
    if ($view == "tables"){

        $sql3 = "select TABLE_NAME from information_schema.COLUMNS
            where TABLE_SCHEMA='choku_dev' and COLUMN_NAME = 'customer_id' ";
        $records = sql2asc($sql3,$pdo,false);
        foreach ($records as $row){
            $sql3 = "select * from `" . $row['TABLE_NAME'] . "`
                where customer_id=" . $customer_id . " limit 10";
            echo strTableName($row['TABLE_NAME']) . ' ';
            $recs1 = sql2asc($sql3,$pdo);
            echo asc2html($recs1);
        }
    }

    // tables
    if ($view == "detail"){

        $upd = getRequest('upd');

        if ($upd == 'add_names'){
            sqlExec("update customers set
            name = 'name". rand(1,2) ."',
            kana = 'kana" . rand(20,70) . "'
            email = 'person" .  rand(1,100) . '@company-gon' . rand(01,9999) . ".com'
            where id = " . $customer_id . "
            ",$pdo);
            // 年齢 20-60才
        }

        if ($upd == 'del_address'){
            sqlExec("update customers set
            sex = null,
            age = null,
            phone_number = null,
            mobile_phone_number = null,
            postcode = null,
            prefecture_id = null,
            city = null,
            town = null,
            address = null,
            building_room_number = null,
            birthday = null,
            memo= null
            where id = " . $customer_id . "
            ",$pdo);
        }

        // 再取得
        $cus = assertZero('customer_id ',sql2asc($sql_cus,$pdo,false));
        $cus_row = $cus[0];
        $sql_cars = "select * from cars where customer_id=" . $customer_id;
        $cars = sql2asc($sql_cars,$pdo,false);

        echo '<table><tr><td style="vertical-align:top;" >';
        echo strbold('customer ' ) . BR;

        echo ' 基本タブ <a class="admin-link" href="' . $ADMIN_URL_BASE . 'customer-detail/' .$customer_id . '?tab=kihon">detail</a>' ;
        echo ' <a class="admin-link" href="' . $ADMIN_URL_BASE . 'customer-edit/' .$customer_id . '?tab=kihon">edit</a> ' ;
        echo '<a class="table-def" target="_blank" href="table.php?TABLE_NAME=customers">customers</a> ';
        echo BR;

        echo ' 住所 ';
        echo ' <a class="upd-link" href="?customer_id=' . $customer_id . '&view=detail&upd=add_address">add</a> ';
        echo ' <a class="upd-link" href="?customer_id=' . $customer_id . '&view=detail&upd=del_address">del</a> ';
        echo ' 名前カナ ';
        echo ' <a class="upd-link" href="?customer_id=' . $customer_id . '&view=detail&upd=add_names">add</a>';
        echo ' <a class="upd-link" href="?customer_id=' . $customer_id . '&view=detail&upd=del_names">del</a>';
        foreach ($cus_row as $key => &$value) {
            if (strpos($key, 'image') !== false ) $value = strTrim($value);
        }
        echo assocDump($cus_row);
        echo '</td>';

        // 車
        echo '<td>' . strBold('car ') . BR;

        echo '車両タブ <a class="admin-link" href="' . $ADMIN_URL_BASE . 'customer-detail/' .$customer_id . '?tab=syaryou">detail</a> ' ;
        echo ' <a class="admin-link" href="' . $ADMIN_URL_BASE . 'customer-edit/' .$customer_id . '?tab=syaryou">edit</a> ' ;
        echo '<a class="table-def" target="_blank" href="table.php?TABLE_NAME=cars">cars</a> ';
        echo BR;
        echo ' 数字系 ';
        echo ' <a class="upd-link" href="?customer_id=' . $customer_id . '&view=detail&upd=add_car_infos">add</a>';
        echo ' <a class="upd-link" href="?customer_id=' . $customer_id . '&view=detail&upd=del_car_infos">del</a>';
        foreach ($cars as $num => &$row){
            $row['image1'] = strTrim($row['image1'],20);
            $row['image2'] = strTrim($row['image2'],20);
            if ($num == 0 ) echo assocDump($row) . '</td>';
            if ($num != 0) echo '<td>' . assocDumpNoKey($row) . '</td>';
        }
        echo '</tr></table>';
    }

    // last_use_logs
    if ($view == "last_use_logs"){

        // 最終購買日の転記情報(last use logs )をセット
        echo '<a class="upd-link" href="?customer_id=' .$customer_id . '&view=last_use_logs&upd=gen_last_use_logs">use_logs追加</a> ';
        echo '<a class="upd-link" href="?customer_id=' .$customer_id . '&view=last_use_logs&upd=del_last_use_logs">削除</a> ';
        echo BR;

        $upd = getRequest("upd");

        if ($upd =="del_last_use_logs"){
            sqlExec("delete from last_use_logs where customer_id = " . $customer_id,$pdo);

        }
        if ($upd =="gen_last_use_logs"){

            $tags = sql2asc("select * from coupon_tags",$pdo);
            foreach ($tags as $tag_row){
                if (rand(1,2) == 1) continue;
                $time = time() -  rand(1,86400 * 40);
                $sql = "insert into last_use_logs (coupontag_id	,customer_id,`used`)
                values(" . $tag_row['id'] . "," .$customer_id. ",'". date('Y-m-d H:i:s', $time) . "')";
                sqlExec($sql,$pdo);
            }
        }
        $sql_last_use_logs = "select * from last_use_logs where customer_id=" . $customer_id;
        $rets = sql2asc($sql_last_use_logs,$pdo);
        $rets = setNames2Rec($rets);
        echo asc2html($rets,false,false);
    }

    // coupon_tags
    if ($view == "coupon_tags"){

        echo "クーポンタグごと<b>最終</b>購買日 " .strTableName("coupon_logs ");
        $sql_cus = "select max(created) ,
            (select tag from coupons where id=cl.coupon_id ) tag
            from coupon_logs cl where customer_id =" . $customer_id . " group by tag";
        $cus = sql2asc($sql_cus,$pdo);
        echo asc2html($cus);

        echo "クーポンタグごと購買日 " . strOrange("coupon_logs ");
        $sql_cus = "select *,
            (select tag from coupons where id=cl.coupon_id) tag
            from coupon_logs cl where customer_id =" . $customer_id . " order by created desc";
        $cus = sql2asc($sql_cus,$pdo);
        echo asc2html($cus);

        echo "最終配信日";
        $sql_cus = "select max(date) from delivery_logs where customer_id =" . $customer_id;
        $cus = sql2asc($sql_cus,$pdo);
        echo asc2html($cus);
    }

    // coupons
    if ($view == "coupons"){

        echo '<a class="upd-link" href="?customer_id=' .$customer_id . '&view=coupons&upd=gen_coupon_logs">利用履歴追加</a> ';
        echo '<a class="upd-link" href="?customer_id=' .$customer_id . '&view=coupons&upd=del_coupon_logs">削除</a> ';
        echo BR;

        $upd = getRequest("upd");

        if ($upd =="del_coupon_logs"){
            sqlExec("delete from coupon_logs where customer_id = " . $customer_id,$pdo);
        }
        // 利用ログ。付与された範囲から
        if ($upd =="gen_coupon_logs"){

            $my_deliv_coupons = sql2asc("select * from delivery_logs where customer_id =" .$customer_id ,$pdo);
            foreach ($my_deliv_coupons as $deliver_row){

                // 利用履歴
                if (rand(1,2) == 1) continue;
                $time = time() -  rand(86400 * 1,86400 * 10);
                $sql_ins_coupon_log = "insert into coupon_logs (coupon_id,	customer_id	,created)
                values(" . $deliver_row['coupon_id'] . "," .$customer_id. ",'" . date('Y-m-d H:i:s', $time) . "')";
                sqlExec($sql_ins_coupon_log,$pdo);
            }
        }


        echo "クーポンタグごと購買日(couponlogの実データから) " . strTableName("coupon_logs ");
        $sql_customer = "select *,
            (select tag from coupons where id=cl.coupon_id) tag
            from coupon_logs cl where customer_id =" . $customer_id .
            " order by created desc";
        $cus = sql2asc($sql_customer,$pdo);
        echo asc2html($cus);
    }
    exit();
}

$sql1 = "select    *  from cards " ;
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

    $row = rowMarkRed($row,$filter);
}
$ret = setNames2Rec($ret,'customers');

echo str150(count($ret));
echo asc2html($ret,false,false);

echo debugFooter();
exit();

<?php

include 'inc.php';

htmlHeader("links " );
menu();

$openapp = isset($_REQUEST["openapp"]) ? $_REQUEST["openapp"] : "";
if ($openapp) {
    $shell ='open -a "' . rawurldecode($openapp) . '" ';
    system($shell);
    exit();
}
// フォルダとアプリをopen
$open = isset($_REQUEST["open"]) ? $_REQUEST["open"] : "";
if ($open) {
    $home_cmd = 'echo $HOME';
    $home = trim(`$home_cmd`);
    $shell ='open "' . $home . '/' . rawurldecode($open) . '"';
    $ret = system($shell);
    exit();
}
?>
<html>
<head>
    <meta http-equiv="content-language" content="ja" charset="UTF-8">
    <title>daily bookmarks</title>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<style>
		body{color:#666; font-family:sans-serif,helvetica; }
		a:link{color:#1e90ff; text-decoration:none; }
		a:visited{color:#1e90ff; text-decoration:none; }
		a:hover{color:#1e90ff; text-decoration:underline;}

        .top_nowrap {vertical-align:top; white-space:nowrap;}
        silver {color:silver; }

		.flex-item {margin-left:15px; margin-bottom:20px; width:300px; }
        .flex-container {display: flex; flex-wrap:wrap; }
	</style>
</head>
<body>
    <div class="flex-container">
        <div class="flex-item" >
            <b>Macアプリ</b> <hr/>
            <a sc="vscode" href="?openapp=<?=urlencode("Visual Studio Code") ?>">VSCode</a>
            <br/>
            <a sc="cw" href="?openapp=<?=urlencode("Chatwork") ?>">Chatwork</a>
            <br/>
            <a sc="sourcetree" href="?openapp=<?=urlencode("Sourcetree") ?>">Sourcetree</a>
            <br/>
            <a sc="excel" href="?openapp=<?=urlencode("Microsoft Excel") ?>">Excel</a>
            <br/>

            <br/>
            <br/>
            <a sc="syspre" href="?openapp=<?=urlencode("System Settings") ?>">システム環境設定</a>
            <br/>
            <a sc="activity" href="?openapp=<?=urlencode("Activity Monitor") ?>">アクティビティモニタ</a>
            <br/>
            <a sc="diskutility" href="?openapp=<?=urlencode("Disk Utility") ?>">Diskユーティリティ</a>
            <br/>


        </div>
        <div class="flex-item" >
            <b>フォルダ</b> <hr/>
            <a sc="desktop" href="?open=<?=urlencode("Desktop")?>">Desktop</a>
        </div>
        <div class="flex-item" >

            <b>googleツール</b> <hr/>
            <a sc="gmail" href="https://mail.google.com/mail/u/2/">GMail</a>
            <a sc="calender" href="https://calendar.google.com/calendar/u/2/r/month">Calender</a>

            <br/>
            devツール<br/>
            <a sc="table" href="http://0.0.0.0:8010/tables.php">table</a>
            <a sc="grep" href="http://0.0.0.0:8010/grep.php">grep</a>
            <a sc="git" href="http://0.0.0.0:8010/git.php">git</a>
            <a sc="coupon" href="http://0.0.0.0:8010/coupons.php">coupon</a>
            <a sc="customers" href="http://0.0.0.0:8010/customers.php">customers</a>

            <br/>
            ツール<br/>
            <a sc="backlog" href="https://pipecs.backlog.jp/find/CARCON_CHOKU?projectId=142330&statusId=1&statusId=2&statusId=15750&statusId=3&statusId=16761&sort=UPDATED&order=false&simpleSearch=true&allOver=false&offset=0">Backlog</a>
            <br/>
            <a sc="bnew" href="https://pipecs.backlog.jp/projects/CARCON_CHOKU">New</a>
            <br/>

            <a sc="ghub" href="https://github.com/carcon-team">github</a>
            <br/>

            マイツール<br/>
            <a target="_blank" sc="timer" href="http://0.0.0.0:8000/managers/timer.php">timer</a>
            <br/>

        </div>

        <div class="flex-item" >

            管理画面<hr/>
            dev <a sc="devadmin" href="****">admin</a>
            local <a sc="localadmin" href="***/***">admin</a>

        </div>

        <div class="flex-item" >
            GoogleDrive
            <a sc="drive" href="http://0.0.0.0:8010/gdrive.php?mtime=20&filter=%2F90_MTG&year=&ext=&fsize_min=">Drive</a>






        </div>


    </div> <!-- flex container -->

    <!-- 入力表示ウィンドウ -->
    <div id="enter" style="position:absolute; border-radius:20px; width:600px;
                            opacity:0.8; top:200px; left:400px;
                            padding-left:20px; padding-right:20px; padding-bottom:20px;">
        <div id="input_words" style="font-size:600%; "></div>
        <table><tr>
            <td id="kouho_shortcuts" style="font-size:150%; color:gray; "></td>
            <td id="kouho_lists" style="font-size:150%; "></td>
        </tr></table>
    </div>
    <script>

        let input_chars = ""
        $(document).ready(function() {
            // ショートカットキー
            $(document).keydown(function(event){
                if (event.key === 'Enter' ) input_chars = "" //enterで入力リセット
                if (event.key.length == 1 ) input_chars += event.key.toLowerCase() //一文字なら追記していく metaやshift enterはx
                // 一つだけ該当すれば遷移
                $('a').css('border','').css('background','') // 該当aタグの赤枠をまず全除去
                let matchs = $('a[sc^=' + escapeJquerySelector(input_chars) + ']')
                // 1件絞れたらなら開く
                if (matchs.length == 1) {

                    // ?openで始まるならajax
                    let url = $(matchs[0]).attr('href')
                    if (url.startsWith('?open')){
                        $.get(url)
                    }else{
                        window.open(url)
                    }
                    // 戻ってきた時すぐ使えるよう初期化
                    matchs =[]
                    input_chars = ""
                }
                console.log(matchs)
                showKouhos(matchs)
            })
        })

        // 入力中文字列と候補リストを表示/非表示
        function showKouhos(matchs){

            if (input_chars.length == 0 ) {
                $('#enter').css('display','none')
            }else{
                $('#enter').css('display','block')
            }
            $('#input_words').html(input_chars );
            if (matchs.length == 0 ) {
                $('#enter').css('background','red').css('color','white')
            }else{
                $('#enter').css('background','#f0f0ff').css('color','')
            }

            let kouho_names = []
            let kouho_shortcuts =[]
            matchs.each(function(ind,obj){
                let shortcut_str = $(obj).attr('sc').toLowerCase()
                kouho_shortcuts.push( (kouho_names.length + 1) + ' ' + shortcut_str.replace(input_chars,'<b>' + input_chars + '</b>')) //マッチ分太字
                kouho_names.push($(obj).text())
                $(obj).css('border','1px solid red').css('background','#fff8f8') // 該当Aタグに赤枠つける
            })
            $('#kouho_lists').html(kouho_names.join('<br/>'))
            $('#kouho_shortcuts').html(kouho_shortcuts.join('<br/>'))
        }
        // jqueryにセットする用にエスケープ id="val[1]" の項目を選択するときなど
        function escapeJquerySelector(val){
            return val.replace(/[ !"#$%&'()*+,.\/:;<=>?@\[\\\]^`{|}~]/g, "\\$&");
        }
    </script>
</body>
</html>

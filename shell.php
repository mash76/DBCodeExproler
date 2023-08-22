<?php
include 'inc.php';

htmlHeader("shell");
echo menu();
echo str150("Shell ") .strSilver("よく使うシェルスクリプトの確認用サンドボックス") . BR;

$shell = getRequest("shell");
?>
<form id="f1">

	<?php
    $list = [
        "linux" => ["ls ","ls -l","ps","ps aux",'ps aux | grep -i docker | grep -i serve | grep -v ps ',"launchctl list","launchctl --help"],
        "git"=> ["git status ","git log","git log --oneline","git log --graph --oneline ","git branch","git branch -r","git ls-files","whereis git"],
        "docker" => ["docker ps ","docker images", "docker version","docker swarm --help","docker service --help","docker  --help","whereis docker"],
        "docker-compose" => ["docker compose ","docker-compose ls","docker-compose version","whereis docker-compose"],
        "kubernates" => ["kubectl --help","kubectl config get-contexts","kubectl config current-context","kubectl get pods --all-namespaces","kubectl config use-context docker-desktop"],
    ];
    foreach ($list as $cate_name => $vals){
        echo $cate_name . " ";
        foreach ($vals as $name) {
            ?><a href="?shell=<?=$name ?>"><?=strDodgerBG($name) ?></a> <?php
        }
        echo BR;
    }
	?>
    <input type="text" id="shell" name="shell" value="<?=$shell ?>">
    <br/>
	<input type="submit" style="display:none;" >
</form>
<?php

if (!$shell) exit();
$ret = runShell($shell);
echo BR .nl2br($ret);

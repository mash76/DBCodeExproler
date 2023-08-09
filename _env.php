<?php
// プロジェクトごと設定項目
$GIT_ROOT = '/Users/***/devtools/docker/php/';
$DOCKER_COMPOSE_ROOT = "/Users/***/***/devtools/docker";
$GOOGLE_DRIVE_PATH = "/Users/****/Library/CloudStorage/GoogleDrive-*****";

$MENUS = ["links","tables","diff","sql","users","cards","grep","git","gdrive","docker","cache"];

// mysql
$DATE_COLS = ['created','updated']; // 直近行進行の判断に利用
$ENVS = [
"local" => ["admin_url" => "http://aaaaaa/",
	"port" => 3306, "user"=>"root", "pass"=>"root" , "schema" => "tree-local"],
"dev" => ["admin_url" => "https://bbbbb/",
	"port" => 3306, "user"=>"root", "pass"=>"root", "schema" => "tree-dev"],
];
$ENV_DEFAULT='local';
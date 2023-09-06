<?php

// プロジェクトごと設定項目  github push時モザイクに
$GIT_ROOT = '/Users/******/Dropbox/managers/devtools/docker/php/';
$DOCKER_COMPOSE_ROOT = "/Users/******/Dropbox/managers/devtools/docker";
$GOOGLE_DRIVE_PATH = "/Users/******/Library/CloudStorage/GoogleDrive-******76@gmail.com";

$USE_DOCKER = true; // docker 関連の起動チェックなど行うか、
$USE_DOCKER_K8S= false;
$USE_MYSQL= false;

$STDERR_PATH = "cache/stderr.log"; // 一時的なstderr出力場所

// mysql
$DATE_COLS = ['created','updated']; // 直近行進行の判断に利用
$ENVS = [
"local" => ["admin_url" => "http://aaaaaa/",
	"port" => 3306, "user"=>"root", "pass"=>"root" , "schema" => "tree-local"],
"dev" => ["admin_url" => "https://bbbbb/",
	"port" => 3306, "user"=>"root", "pass"=>"root", "schema" => "tree-dev"],
];
$ENV_DEFAULT='local';
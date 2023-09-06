<?php
include 'inc.php';


// $DOCKER_COMPOSE_ROOT

$view = getRequest("view");
$action = getRequest("action");
$id = getRequest("id");

htmlHeader("S3");
menu();
echo str150("S3 ") .strSilver("S3") . BR;


echo debugFooter();
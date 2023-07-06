<?php
include 'inc.php';
$pdo = new PDO($datasource,$current_env['user'],$current_env['pass']);

htmlHeader("schema diff");
?>
<?=menu(); ?>
<?php

echo str150("Schema Diff ") . BR;

$diff_envs = array_keys($ENVS);

$diffs = [];
foreach ($diff_envs as $env_name){

    //connection
    $dest_env = $ENVS[$env_name];
    $datasource = "mysql:dbname=" . $dest_env['schema'] . ";host=127.0.0.1;port=" . $dest_env['port'] . ";";
    $pdo1 = new PDO($datasource,$dest_env['user'],$dest_env['pass']);

    $sql_version = "select version() ver";
    $recs =sql2asc($sql_version,$pdo1,false);
    $diffs[$env_name]['version'] = $recs[0]['ver'];

    // table一覧
    $sql = "select TABLE_NAME FROM information_schema.TABLES where TABLE_SCHEMA='" . $dest_env['schema']. "'";
    $recs =sql2asc($sql,$pdo1,false);
    $diffs[$env_name]['tables'] = [];
    foreach ($recs as $row) $diffs[$env_name]['tables'][$row['TABLE_NAME']] = $row['TABLE_NAME'];


    // column一覧とtype
    $sql = "select TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,COLUMN_DEFAULT,IS_NULLABLE,EXTRA FROM information_schema.COLUMNS where TABLE_SCHEMA='" . $dest_env['schema']. "'";
    $recs =sql2asc($sql,$pdo1,false);
    $diffs[$env_name]['columns'] = [];
    foreach ($recs as $row) $diffs[$env_name]['columns'][$row['TABLE_NAME'] . "." . $row['COLUMN_NAME']] = $row['COLUMN_TYPE'] . $row['COLUMN_DEFAULT'] . $row['IS_NULLABLE'] . $row['EXTRA'];

}

$diff_str_ary = [];
foreach ($diff_envs as $ind => $env_name){

    //$diff_str_recs['name'][$env_name] = strBlueBG($env_name);

    $other_env_name = $diff_envs[0];
    if ($ind ==0 ) $other_env_name = $diff_envs[1];

    $diff_str_recs['version'][$env_name] = $diffs[$env_name]['version'];

    $diff_str_recs['tables'][$env_name] = count($diffs[$env_name]['tables']) . strSilver(" tables") . BR;

    $str ="";
    $diffs[$env_name]['table_diff_ct'] = 0;
    foreach ($diffs[$env_name]['tables'] as $key => $val){
        if (!isset($diffs[$other_env_name]['tables'][$key])) {
            $str .= strBlue($key) . BR;
            $diffs[$env_name]['table_diff_ct']++;
        }
    }

    $table_same_ct = count($diffs[$env_name]['tables']) - $diffs[$env_name]['table_diff_ct'];
    $diff_str_recs['tables'][$env_name] .= $table_same_ct . strSilver(' same') . BR;
    $diff_str_recs['tables'][$env_name] .= $diffs[$env_name]['table_diff_ct'] . strSilver(' diff') . BR;
    $diff_str_recs['tables'][$env_name] .= $str;


    $diffs[$env_name]['column_diff_ct']=0;
    $diff_str_recs['columns'][$env_name] = count($diffs[$env_name]['columns']) . strSilver(" columns") . BR;
    $str ="";
    foreach ($diffs[$env_name]['columns'] as $key => $val){
        if (!isset($diffs[$other_env_name]['columns'][$key])) {
            $str .= strBlue($key) . BR;
            $diffs[$env_name]['column_diff_ct']++;
        }
    }
    $column_same_ct = count($diffs[$env_name]['columns']) - $diffs[$env_name]['column_diff_ct'];
    $diff_str_recs['columns'][$env_name] .= $column_same_ct . strSilver(' same') . BR;
    $diff_str_recs['columns'][$env_name] .= $diffs[$env_name]['column_diff_ct'] . strSilver(' diff') . BR;
    $diff_str_recs['columns'][$env_name] .= $str;

}
echo asc2html($diff_str_recs);
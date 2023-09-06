<?php

function showMenus(){
    ?>
    <a href="?view=region">region-az</a>
    <br/>
    regionあり
    <a href="?view=tree">tree</a>
    <a href="?view=ssm">ssm</a>
    <a href="?view=vpc">vpc-ni-sg-key</a>
    <a href="?view=user">iam-user-group</a>
    <a href="?view=ec2">ec2-rds-cache</a>
    <a href="?view=dynamo">dynamo</a>
    <a href="?view=opensearch">openSearch</a>
    <a href="?view=container">container</a>
    <a href="?view=lightsail">lightsail</a>
    <a href="?view=s3">s3</a>
    <a href="?view=cloudfront">cloudfront</a>
    <a href="?view=ses">ses</a>
    | regionなし
    <a href="?view=lambda">lambda-gateway</a>


    <a href="?view=watch">cloudwatch</a>
    <a href="?view=cost">cost</a>
    |

    <a href="?view=sqlite">sqlite</a>
    <a href="?view=conf">conf</a>

    <hr/>
    <?php
}

function showAWSLinks($key){
    $console_urls = [
        "lightsail" => [
            "lightsail"=>"https://lightsail.aws.amazon.com/ls/webapp/home?#"
        ],
        "s3" => [
            "S3" => "https://s3.console.aws.amazon.com/s3/home?region=ap-northeast-1#",
            "API-s3api" => "https://docs.aws.amazon.com/cli/latest/reference/s3api/index.html",
            "API-s3" => "https://docs.aws.amazon.com/cli/latest/reference/s3/index.html#s3",
        ],
        "vpc"=>[
            "VPC" => "https://ap-northeast-1.console.aws.amazon.com/vpc/home?region=ap-northeast-1#Home:",
            "Subnet" =>"https://ap-northeast-1.console.aws.amazon.com/vpc/home?region=ap-northeast-1#subnets:",
            "SecurityGroup" => "https://ap-northeast-1.console.aws.amazon.com/vpc/home?region=ap-northeast-1#securityGroups:",
        ],
        "user"=>[
            "iam" => "https://us-east-1.console.aws.amazon.com/iamv2/home?region=ap-northeast-1#/home",
            "group" => "https://us-east-1.console.aws.amazon.com/iamv2/home?region=ap-northeast-1#/groups",
            "role" => "https://us-east-1.console.aws.amazon.com/iamv2/home?region=ap-northeast-1#/roles",
            "API-iam" => "https://docs.aws.amazon.com/cli/latest/reference/iam/#available-commands",
        ],
        "ec2"=>[
            "EC2" => "https://ap-northeast-1.console.aws.amazon.com/ec2/home?region=ap-northeast-1#Home:",
            "RDS" => "https://ap-northeast-1.console.aws.amazon.com/rds/home?region=ap-northeast-1#",
            "ElastiCache" => "https://ap-northeast-1.console.aws.amazon.com/elasticache/home?region=ap-northeast-1#",
            "API-elastiCache" => "https://docs.aws.amazon.com/cli/latest/reference/elasticache/#available-commands",
            "API-EC2" => "https://docs.aws.amazon.com/cli/latest/reference/ec2/#available-commands",
            "API-RDS" => "https://docs.aws.amazon.com/cli/latest/reference/rds/#available-commands",
        ],
        "dynamo"=>[
            "Dynamo" => "https://ap-northeast-1.console.aws.amazon.com/dynamodbv2/home?region=ap-northeast-1#",
            "API-Dynamo" => "https://docs.aws.amazon.com/cli/latest/reference/dynamodb/#available-commands",
        ],
        "opensearch"=>[
            "OpenSearch" => "https://ap-northeast-1.console.aws.amazon.com/aos/home?region=ap-northeast-1#opensearch/dashboard",
        ],
        "lambda"=>[
            "lambda" => "https://ap-northeast-1.console.aws.amazon.com/lambda/home?region=ap-northeast-1#/functions",
            "APIGateway" => "https://ap-northeast-1.console.aws.amazon.com/apigateway/home?region=ap-northeast-1",
            "API-lambda" => "https://awscli.amazonaws.com/v2/documentation/api/latest/reference/lambda/index.html#available-commands",
            "API-APIGateway" => "https://awscli.amazonaws.com/v2/documentation/api/latest/reference/apigatewayv2/index.html#available-commands",
        ],
        "cloudfront" => [
            "CloudFront" => "https://us-east-1.console.aws.amazon.com/cloudfront/v3/home?region=ap-northeast-1#",
            "API-Cloudfront" => "https://docs.aws.amazon.com/cli/latest/reference/cloudfront/index.html#available-commands",
        ],
        "container" => [
            "ECR(Registory)" => "https://ap-northeast-1.console.aws.amazon.com/ecr/home?region=ap-northeast-1#",
            "ECS(fargate)" => "https://ap-northeast-1.console.aws.amazon.com/ecs/v2/home?region=ap-northeast-1#",
            "EKS(kubernates)" => "https://ap-northeast-1.console.aws.amazon.com/eks/home?region=ap-northeast-1#",
            "API-ecs" => "https://docs.aws.amazon.com/cli/latest/reference/ecs/#available-commands",
            "API-ecr" => "https://docs.aws.amazon.com/cli/latest/reference/ecr/#available-commands",
            "API-eks" => "https://docs.aws.amazon.com/cli/latest/reference/eks/#available-commands",
        ],
        "ssm" => [
            "SSM" => "https://ap-northeast-1.console.aws.amazon.com/systems-manager/home?region=ap-northeast-1",
            "CloudFormation" => "https://ap-northeast-1.console.aws.amazon.com/cloudformation/home?region=ap-northeast-1#",
            "API-SSM" => "https://docs.aws.amazon.com/cli/latest/reference/ssm/#available-commands",
            "API-CFprmation" => "https://awscli.amazonaws.com/v2/documentation/api/latest/reference/cloudformation/index.html#available-commands",
        ],
        "watch" => [
            "CloudWatch" => "https://ap-northeast-1.console.aws.amazon.com/cloudwatch/home?region=ap-northeast-1#home:",
            "Alarms" => "https://ap-northeast-1.console.aws.amazon.com/cloudwatch/home?region=ap-northeast-1#alarmsV2:",
            "LogGroup" => "https://ap-northeast-1.console.aws.amazon.com/cloudwatch/home?region=ap-northeast-1#logsV2:log-groups",
            "API-cw" => "https://docs.aws.amazon.com/cli/latest/reference/cloudwatch/#available-commands",
        ],
        "cost" => [
            "Cost" => "https://us-east-1.console.aws.amazon.com/billing/home?region=ap-northeast-1#/",
        ],
        "ses" => [
            "ses" => "https://ap-northeast-1.console.aws.amazon.com/ses/home?region=ap-northeast-1#/account",
            "API-sesv2" => "https://docs.aws.amazon.com/cli/latest/reference/sesv2/index.html#available-commands",
        ],
    ];
    if (isset($console_urls[$key])){
        foreach ($console_urls[$key] as $name => $url1){
            echo '<a target="_blank" href="' . $url1 . '">' . strPink($name) . '</a> ';
        }
        echo BR;
    }
}



// shell コマンド結果をsqliteで保持
function shellCache($sqlite,$command,$is_disp=true){
    $ret = sql2asc("select * from shell_results where command ='" . sqlite3::escapeString($command) . "'",$sqlite,false);
    if (count($ret) == 1){
        if ($is_disp) echo strBG($command) . SPC;
        $return = $ret[0]['result_text'];
    }else{
        $return = runShell($command,$is_disp);
        $sql1 = "insert into shell_results (command ,result_text,created) values('" . sqlite3::escapeString( $command) . "','".$return."','".time()."') ";
        sqlExec($sql1,$sqlite,false);

    }
    return $return;
}
function filterRecords($records,$colname,$value){
    $return = [];
    foreach ($records as $row){
        if (isset($row[$colname])){
            if ($row[$colname] == $value){
                $return[] = $row;
            }
        }else{
            echo strRed('no key '. $colname . " " . var_export($row,true));
        }

    }
    return $return;
}

// リストを受け取りリンクとして表示
function instances2records($instances,$cols){
    $rets = [];
    foreach ($instances as $node){
        $ret =[];
        $ret = instance2row($node,$cols);
        $rets[] = $ret;
    }
    return $rets;
}
function instance2row($node,$cols){
        foreach ($cols as $colname) {

            if (strpos($colname,":") !== false){
                list($name,$new_name) = explode(":",$colname);
            }else{
                $name = $colname;
                $new_name=$name;
            }
            $name = trim($name);
            $new_name = trim($new_name);

            $colnameary = explode(" ",$name);
            $node1 =$node;
            foreach ($colnameary as $col1){
                if (isset($node1[$col1])) {
                    $node1 = $node1[$col1];
                }else{
                    $node1 = strSilver("no key");
                }
            }
            $ret[$new_name] = $node1;
        }
        return $ret;
}

// コストの一覧を縦横の表に
function getCostMatrixTable($records,$date_trim="month"){
    // 縦がkey 横が Keys, Start,Start,Start
    $cols1 = ["Keys"=> "","Sum"=>0];

    // 日付文字列 yyyy-mm-dd を mmか dd に加工
    foreach ($records as &$row) {
        $row['Keys'] = preg_replace("/(AWS|Amazon|Service|Simple)/i","",$row["Keys"]);
        if ($date_trim == "month") $row["Start"] = substr($row["Start"],5,2);
        if ($date_trim == "day") $row["Start"] = substr($row["Start"],8,2);
    }
    // 日付文字列
    foreach ($records as $row) {
        $cols1[$row["Start"]] =0;
    }
    $recs1 = [];
    $recsum = ["Keys"=>strRed("Sum"),"Sum"=>0];
    $sumsum = 0;
    //各行
    foreach ($records as $row) {
        if (!isset($recs1[$row['Keys']])) {
            $recs1[$row['Keys']] = $cols1;
        }
        $recs1[$row['Keys']]["Keys"] = $row['Keys'];// awsアイテム名 何度も上書きされるが問題なし
        $recs1[$row['Keys']][$row['Start']] = $row['Amount']; //個別の金額

        $recs1[$row['Keys']]["Sum"] += $row['Amount']; // 項目ごとの合計

        if (!isset($recsum[$row['Start']])) $recsum[$row['Start']] =0;
        $recsum[$row['Start']] += $row['Amount']; // 合計行

        $sumsum += $row['Amount'];
    }
    // tax行をつけ直し
    $taxrow = $recs1['Tax'];
    unset($recs1['Tax']);

    $recsum["Sum"] = $sumsum;
    array_unshift($recs1 , $taxrow);
    array_unshift($recs1 , $recsum);
    return $recs1;
}

// costのapi結果をjsonから二重配列に戻す
function json2tableCost($json_str){
    $costs = json_decode($json_str,true);
    $items = $costs["ResultsByTime"];
    $rets = [];
    foreach ($items as $period){
        foreach ($period["Groups"] as $node){
            $ret =[];
            $ret["Start"] = $period["TimePeriod"]["Start"];
            $ret["End"] = $period["TimePeriod"]["End"];
            $ret["Keys"] = $node["Keys"][0];
            $amount = $node["Metrics"]["UnblendedCost"]["Amount"];
            //$ret["AmountRaw"] = $amount;
            $amount = ceil($amount * 1000)/ 1000;
            $ret["Amount"] = $amount;
            $ret["Unit"] = $node["Metrics"]["UnblendedCost"]["Unit"];
            $rets[] = $ret;
        }
    }
    return $rets;
}



//
function setlink2Records($records,$colname,$url_base){
    foreach ($records as &$row){
        $row[$colname] = '<a href="' .str_replace("***",$row[$colname],$url_base). '">'. $row[$colname] .'</a>';
    }
    return $records;
}
function setlink2Row($row,$colname,$url_base){
    $row[$colname] = '<a href="' .str_replace("***",$row[$colname],$url_base). '">'. $row[$colname] .'</a>';
    return $row;
}

// 日付列をわかりやすく加工
function setTime2Records($records){
    foreach ($records as &$row){
        foreach ($row as $key => &$val){
            if (strpos($val,":") && strpos($val,"-") !== false && strtotime($val) !== false) $val = date('Y-m-d',strtotime($val));
        }
    }
    return $records;
}
function setTime2Row($row){
        foreach ($row as $key => &$val){
            if (strpos($val,":") && strpos($val,"-") !== false  && strtotime($val) !== false) $val = date('Y-m-d',strtotime($val));
        }
    return $row;
}

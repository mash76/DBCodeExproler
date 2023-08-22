<?php
include 'inc.php';

$SQLITE_PATH = "aws.sqlite";
$sqlite = sqliteConnect($SQLITE_PATH); // incで接続したmysqlをsqliteで上書き

$delid = getRequest("delid");
$view = getRequest("view",false,"ec2");

htmlHeader("AWS");
menu();
echo str150("AWS ") .strSilver("aws cliを使ったaws管理") . BR;

showMenus();
showAWSLinks($view);

if ($view=="conf"){  echo nl2br(`cat ~/.aws/credentials`);  }
if ($view=="cloudfront"){

    $ec2list = shellCache($sqlite,"aws cloudfront list-distributions");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["DistributionList"]['Items'];
    $cols = ["Status","Enabled","Id","ARN","DomainName"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);

    $ec2list = shellCache($sqlite,"aws cloudfront list-functions");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["FunctionList"]["Items"];
   // $cols = ["MaxItems","InstanceType","PlatformDetails","LaunchTime","PrivateDnsName",
    // "KeyName","SubnetId","VpcId"];
    // $rets = instances2records($instances,$cols);
  //  echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    //echo asc2html($rets);
}
if ($view=="watch"){

    $ec2list = shellCache($sqlite,"aws cloudwatch describe-alarms");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["MetricAlarms"];
    $cols = ["Namespace","MetricName","StateValue","AlarmName","ActionsEnabled","AlarmArn"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);

    $ec2list = shellCache($sqlite,"aws cloudwatch list-dashboards");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["DashboardEntries"];
    $cols = ["DashboardName","DashboardArn","LastModified","Size"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);

    $ec2list = shellCache($sqlite,"aws cloudwatch get-dashboard --dashboard-name rds");
    // $ec2s = json_decode($ec2list,true);
    // $instances = $ec2s["DashboardEntries"];
    // $cols = ["DashboardName","DashboardArn","LastModified","Size"];
    // $rets = instances2records($instances,$cols);
    // echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    //decho asc2html($rets);

    // $ec2list = shellCache($sqlite,"aws cloudwatch list-metrics");
    // $ec2s = json_decode($ec2list,true);
    // $instances = $ec2s["Metrics"];
    // $cols = ["Namespace","MetricName"];
    // $rets = instances2records($instances,$cols);
    // echo SPC . count($instances) .SPC;
    // echo link2detail("raw",$ec2list) . BR;
    // echo asc2html($rets);



}



if ($view=="ec2"){

    //ec2
    $ec2list = shellCache($sqlite,"aws ec2 describe-instances");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["Reservations"][0]["Instances"];
    $cols = ["PlatformDetails","InstanceType","KeyName","InstanceId","LaunchTime","PrivateDnsName",
    "SubnetId","VpcId"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);

    // rds
    $rdslist = shellCache($sqlite,"aws rds describe-db-instances");
    $rdss = json_decode($rdslist,true);
    $instances = $rdss["DBInstances"];
    $cols = ["Engine","EngineVersion","DBInstanceClass","DBInstanceIdentifier","StorageType",
            "DBSubnetGroup VpcId" ,"MasterUsername","AllocatedStorage","BackupRetentionPeriod","AvailabilityZone",
            "DBInstanceArn"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$rdslist) . BR;
    echo asc2html($rets);

    //cache
    $vpclist = shellCache($sqlite,"aws elasticache describe-cache-clusters");
    $vpcs = json_decode($vpclist,true);
    $items = $vpcs["CacheClusters"];
    $cols = ["Engine","EngineVersion","CacheNodeType","CacheClusterId",
    "CacheClusterStatus",
    "NumCacheNodes","PreferredAvailabilityZone","ARN"];
    $rets = instances2records($items,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$vpclist) . BR;
    echo asc2html($rets);

    // 大量 全履歴か？
    // $vpclist = shellCache($sqlite,"aws ec2 describe-snapshots");
    // $vpcs = json_decode($vpclist,true);
    // $items = $vpcs["Snapshots"];
    // $cols = ["SnapshotId",
    // "Progress","StartTime","State","Description"];
    // $rets = instances2records($items,$cols);
    // echo SPC . count($rets) .SPC;
    // echo link2detail("raw",$vpclist) . BR;
    // echo asc2html($rets);


    // dynamo
    $dynamolist = shellCache($sqlite,"aws dynamodb list-tables");
    $dynamoname_ary = json_decode($dynamolist,true);
    $items = $dynamoname_ary["TableNames"];
    $dynamos =[];
    foreach ($items as $tablename) $dynamos[] = ["TableName" => $tablename];
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$dynamoname_ary) . BR;
    echo asc2html($dynamos);

    foreach ($dynamos as $row){
        $dynatable_list = shellCache($sqlite,"aws dynamodb describe-table --table-name " . $row['TableName']);
        $dynamo_ary = json_decode($dynatable_list,true);
        $items = $dynamo_ary["Table"];
       $cols = ["TableName","TableStatus","TableSizeBytes","ItemCount","TableId",
       "ProvisionedThroughput NumberOfDecreasesToday",
       "ProvisionedThroughput ReadCapacityUnits",
       "ProvisionedThroughput WriteCapacityUnits",
        ];
       $rets = instances2records([$items],$cols);
        echo SPC . count($rets) .SPC;
        echo link2detail("raw",$dynatable_list) . BR;
        echo asc2html($rets);
    }
}

if ($view=="sqlite"){

    if ($delid) sqlExec("delete from shell_results where id=". $del,$sqlite);

    $ret = sql2asc("select '' action,* from shell_results order by command asc",$sqlite);
    $stat = [];
    echo '<a href="?view=sqlite&delid=all">delAll</a>';
    foreach ($ret as &$row){
        $suncommand = explode(" ",$row['command'])[1];
        if (!isset($stat[$suncommand])) $stat[$suncommand] = 0;
        $stat[$suncommand]++;
        $row['action'] = '<a href="?view=sqlite&delid=' .$row['id']. '">del</a>';
    }
    echo asc2html($ret);
    exit();
}

if ($view=="container"){


}

if ($view=="region"){

    $regionlist = shellCache($sqlite,"aws ec2 describe-regions");
    $regions_raw = json_decode($regionlist,true);
    $rows = $regions_raw["Regions"];
    $cols = ["RegionName","OptInStatus","Endpoint"];
    $regions = instances2records($rows,$cols);
    echo SPC . count($regions) .SPC;
    echo link2detail("raw",$regionlist) . BR;
    echo asc2html($regions);

    $zones=[];
    foreach ($regions as $row){
        $vpclist = shellCache($sqlite,"aws ec2 describe-availability-zones --region " . $row['RegionName'],false);
        $vpcs = json_decode($vpclist,true);
        $cols = ["RegionName","ZoneName","ZoneId","GroupName","NetworkBorderGroup","State"];
        $rets = instances2records($vpcs["AvailabilityZones"],$cols);
      //  echo SPC . count($rets) .SPC;
      //  echo link2detail("raw",$vpclist) . BR;
        $zones = array_merge($zones,$rets);
    }
    echo BR .strBG("AvailabilityZones") . SPC . count($zones);
    echo asc2html($zones);
}

if ($view=="vpc"){
    $vpclist = shellCache($sqlite,"aws ec2 describe-vpcs");
    $vpcs = json_decode($vpclist,true);
    $cols = ["VpcId","OwnerId","State","IsDefault","CidrBlock","DhcpOptionsId"];
    $rets = instances2records($vpcs["Vpcs"],$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$vpclist) . BR;
    echo asc2html($rets);

    // $subnetlist = shellCache($sqlite,"aws ec2 describe-subnets");
    // $subnets = json_decode($subnetlist,true);
    // $cols = ["SubnetId","VpcId","AvailabilityZoneId","AvailabilityZone","CidrBlock","AvailableIpAddressCount","State","SubnetArn"];
    // $rets = instances2records($subnets["Subnets"],$cols);
    // echo SPC . count($rets) .SPC;
    // echo link2detail("raw",$subnetlist) . BR;
    // echo asc2html($rets);

    $subnetlist = shellCache($sqlite,"aws ec2 describe-network-interfaces");
    $subnets = json_decode($subnetlist,true);
    $items = $subnets["NetworkInterfaces"];
    $cols = ["RequesterId","Description","Attachment AttachTime","Status","SubnetId","VpcId"];
    $rets = instances2records($items,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$subnetlist) . BR;
    echo asc2html($rets);



    $securitylist = shellCache($sqlite,"aws ec2 describe-security-groups");
    $securities = json_decode($securitylist,true);
    $items = $securities["SecurityGroups"];
    $rets = [];
    foreach ($items as $node){
        $ret =[];

        $ret["GroupId"] = $node["GroupId"];
        $ret["GroupName"] = $node["GroupName"];
        $ret["Description"] = $node["Description"];
        $ret["VpcId"] = $node["VpcId"];
        $ret["ips_in"] = "";
        foreach ($node["IpPermissions"] as $node1){
            $ret["ips_in"] .= $node1["IpProtocol"] . SPC ;
            if (isset($node1["FromPort"])) $ret["ips_in"].=$node1["FromPort"] . SPC;
            $ret["ips_in"].=" | " ;
        }
        $ret["ips_out"] = "";
        foreach ($node["IpPermissionsEgress"] as $node1){
            $ret["ips_out"] .= $node1["IpProtocol"] . SPC ;
            if (isset($node1["FromPort"])) $ret["ips_out"].=$node1["FromPort"] . SPC;
            $ret["ips_out"].=" | " ;
        }
        $rets[] = $ret;
    }

    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$securitylist) . BR;
    echo asc2html($rets);
}

// $securitylist = shellCache($sqlite,"aws ec2 describe-security-group-rules");
// $securities = json_decode($securitylist,true);
// $items = $securities["SecurityGroupRules"];
// $cols = ["SecurityGroupRuleId","GroupId","GroupOwnerId","IsEgress","IpProtocol","FromPort","ToPort"];
// $rets = instances2records($items,$cols);
// echo SPC . count($rets) .SPC;
// echo link2detail("raw",$securitylist) . BR;
// echo asc2html($rets);


if ($view=="cost"){

    //cost
    echo BR .strBold("今年Monthly " ). BR;
    $costlist = shellCache($sqlite,"aws ce get-cost-and-usage --time-period Start=2023-01-01,End=2023-08-01 --granularity MONTHLY --metrics UnblendedCost --group-by Type=DIMENSION,Key=SERVICE");
    $rets = json2tableCost($costlist);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$costlist) . BR;
    //  echo asc2html($rets);
    $recs1 = getCostMatrixTable($rets);
    echo asc2html(assocZeroSilver($recs1));

    echo BR .strBold("今月Daily " ). BR;
    $costlist = shellCache($sqlite,"aws ce get-cost-and-usage --time-period Start=" .date("Y-m-01"). ",End=" . date("Y-m-d") . " --granularity DAILY --metrics UnblendedCost --group-by Type=DIMENSION,Key=SERVICE");
    $rets = json2tableCost($costlist);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$costlist) . BR;
    // echo asc2html($rets);
    $recs1 = getCostMatrixTable($rets);
    echo asc2html(assocZeroSilver($recs1));
}

if ($view=="lambda"){

    $lambdalist = shellCache($sqlite,"aws lambda list-functions");
    $lambdas = json_decode($lambdalist,true);
    $instances = $lambdas["Functions"];
    $cols = ["FunctionName","Description","Runtime","CodeSize","Timeout","MemorySize","LastModified","EphemeralStorage Size"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$lambdalist) . BR;
    echo asc2html($rets);

    // HTTP API は低価格で提供できるように最小限
    // APIキー、クライアントごとのスロットリング、リクエストの検証、AWS WAF の統合、プライベート API エンドポイントなどの機能は、REST API
    $apilist = shellCache($sqlite,"aws apigateway get-rest-apis ");
    $apis = json_decode($apilist,true);
    $items = $apis["items"];
    $cols = ["id","name","description"];
    $rets = instances2records($items,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$apilist) . BR;
    echo asc2html($rets);
}

if ($view=="user"){

    $grouplist = shellCache($sqlite,"aws iam list-groups");
    $groups = json_decode($grouplist,true);
    $instances = $groups["Groups"];
    $cols = ["Path","GroupName","GroupId","CreateDate","Arn"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$grouplist) . BR;
    echo asc2html($rets);

    $userlist = shellCache($sqlite,"aws iam list-users");
    $users = json_decode($userlist,true);
    $instances = $users["Users"];
    $cols = ["UserName","UserId","CreateDate","Arn"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$userlist) . BR;
    echo asc2html($rets);

    $userlist = shellCache($sqlite,"aws iam list-roles");
    $users = json_decode($userlist,true);
    $instances = $users["Roles"];
    $cols = ["RoleName","RoleId","Arn","CreateDate","Description"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$userlist) . BR;
    echo asc2html($rets);

}

if ($view=="s3"){

    $s3list = shellCache($sqlite,"aws s3 ls");
    $s3s = json_decode($s3list,true);
    echo link2detail("raw",$s3list) . BR;
    echo nl2br($s3list);
}

if ($view=="lightsail"){
    //lightsail
    $saillist = shellCache($sqlite,"aws lightsail get-instances");
    $sails = json_decode($saillist,true);
    $items = $sails["instances"];
    $cols = ["name","blueprintId","blueprintName",
    "hardware cpuCount","hardware ramSizeInGb",
    "location availabilityZone","location regionName",
    "publicIpAddress","isStaticIp",
    "bundleId","username","sshKeyName","createdAt","arn"];
    $rets = instances2records($items,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$saillist) . BR;
    echo asc2html($rets);
}

if ($view=="tree"){

    $ec2list = shellCache($sqlite,"aws ec2 describe-instances");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["Reservations"][0]["Instances"];
    $cols = ["InstanceId","InstanceType","PlatformDetails","LaunchTime","PrivateDnsName",
    "KeyName","SubnetId","VpcId"];
    $ecs_tables = instances2records($instances,$cols);

    $subnetlist = shellCache($sqlite,"aws ec2 describe-subnets");
    $subnets = json_decode($subnetlist,true);
    $items = $subnets["Subnets"];
    $cols = ["SubnetId","VpcId","AvailabilityZoneId","AvailabilityZone","CidrBlock","AvailableIpAddressCount","State","SubnetArn"];
    $subnet_tables = instances2records($items,$cols);

    $securitylist = shellCache($sqlite,"aws ec2 describe-security-groups");
    $securities = json_decode($securitylist,true);
    $items = $securities["SecurityGroups"];
    $sg_tables = [];
    foreach ($items as $node){
        $ret =[];

        $ret["GroupId"] = $node["GroupId"];
        $ret["GroupName"] = $node["GroupName"];
        $ret["Description"] = $node["Description"];
        $ret["VpcId"] = $node["VpcId"];
        $ret["ips_in"] = "";
        foreach ($node["IpPermissions"] as $node1){
            $ret["ips_in"] .= $node1["IpProtocol"] . SPC ;
            if (isset($node1["FromPort"])) $ret["ips_in"].=$node1["FromPort"] . SPC;
            $ret["ips_in"].=" | " ;
        }
        $ret["ips_out"] = "";
        foreach ($node["IpPermissionsEgress"] as $node1){
            $ret["ips_out"] .= $node1["IpProtocol"] . SPC ;
            if (isset($node1["FromPort"])) $ret["ips_out"].=$node1["FromPort"] . SPC;
            $ret["ips_out"].=" | " ;
        }
        $sg_tables[] = $ret;
    }

    $rdslist = shellCache($sqlite,"aws rds describe-db-instances");
    $rdss = json_decode($rdslist,true);
    $rds_cols = $rdss["DBInstances"];
    $cols = ["DBInstanceIdentifier","DBInstanceClass","Engine","EngineVersion","StorageType",
            "DBSubnetGroup VpcId" ,"MasterUsername","AllocatedStorage","BackupRetentionPeriod","AvailabilityZone",
            "DBInstanceArn"];
    $rds_tables = instances2records($rds_cols,$cols);


    $vpclist = shellCache($sqlite,"aws ec2 describe-vpcs");
    $vpcs = json_decode($vpclist,true);
    $items = $vpcs["Vpcs"];
    $cols = ["VpcId","OwnerId","State","IsDefault","CidrBlock","DhcpOptionsId"];
    $rets = instances2records($items,$cols);
    echo SPC . count($items) .SPC;
    echo link2detail("raw",$vpclist) . BR;
    foreach ($rets as $row){
        echo asc2html([$row]);
        echo '<blockquote>';

        echo strOrange("subnet ") . BR . asc2html(filterRecords($subnet_tables,"VpcId",$row['VpcId']));
        echo strOrange("securitygroup ") . asc2html(filterRecords($sg_tables,"VpcId",$row['VpcId']));
        echo strOrange("rds ") . BR. asc2html(filterRecords($rds_tables,"VpcId",$row['VpcId']));
        echo strOrange("ec2 ") . BR. asc2html(filterRecords($ecs_tables,"VpcId",$row['VpcId']));


        echo '</blockquote>';

    }

}


// shell コマンド結果をsqliteで保持
function shellCache($sqlite,$command,$is_disp=true){
    $ret = sql2asc("select * from shell_results where command ='" . $command . "'",$sqlite,false);
    if (count($ret) == 1){
        if ($is_disp) echo strBG($command) . SPC;
        $return = $ret[0]['result_text'];
    }else{
        $return = runShell($command,$is_disp);
        sqlExec("insert into shell_results (command ,result_text,created) values('" . $command . "','".$return."','".time()."') ",$sqlite,false);

    }
    return $return;
}
function filterRecords($records,$colname,$value){
    $return = [];
    foreach ($records as $row){
        if ($row[$colname] == $value){
            $return[] = $row;
        }
    }
    return $return;
}

// リストを受け取りリンクとして表示
function instances2records($instances,$cols){
    $rets = [];
    foreach ($instances as $node){
        $ret =[];
        foreach ($cols as $colname) {

            $colary = explode(" ",$colname);
            $node1 =$node;
            foreach ($colary as $col1){
                if (isset($node1[$col1])) {
                    $node1 = $node1[$col1];
                }else{
                    $node1 = null;
                }
            }
            $ret[$col1] = $node1;
        }
        $rets[] = $ret;
    }
    return $rets;
}

// コストの一覧を縦横の表に
function getCostMatrixTable($records){
    // 縦がkey 横が Keys, Start,Start,Start
    $cols1 = ["Keys"=> "","Sum"=>0];
    $sum = 0;
    foreach ($records as $row) {
        $cols1[$row["Start"]] =0;
        $sum += $row['Amount'];
    }
    $recs1 = [];
    foreach ($records as $row) {
        if (!isset($recs1[$row['Keys']])) {
            $recs1[$row['Keys']] = $cols1;
        }
        $recs1[$row['Keys']]["Keys"] = $row['Keys'];// awsアイテム名
        $recs1[$row['Keys']][$row['Start']] = $row['Amount'];
        $recs1[$row['Keys']]["Sum"] += $row['Amount'];
    }
    return $recs1;
}

// costのapi結果をjsonから戻す
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

function showMenus(){
    ?>
    <a href="?view=region">region-az</a>
    <br/>
    regionあり
    <a href="?view=vpc">vpc-ni-sgroup</a>
    <a href="?view=user">iam-user-group</a>
    <a href="?view=ec2">ec2-rds-cache</a>
    <a href="?view=s3">s3</a>
    | regionなし
    <a href="?view=lambda">lambda-gateway</a>
    <a href="?view=lightsail">lightsail</a>
    <a href="?view=cloudfront">cloudfront</a>
    <a href="?view=watch">cloudwatch</a>
    <a href="?view=cost">cost</a>
    |
    <a href="?view=container">container</a>
    <a href="?view=tree">tree</a>
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
        "vpc"=>[
            "VPC" => "https://ap-northeast-1.console.aws.amazon.com/vpc/home?region=ap-northeast-1#Home:",
            "SecurityGroup" => "https://ap-northeast-1.console.aws.amazon.com/vpc/home?region=ap-northeast-1#securityGroups:",
        ],
        "user"=>[
            "iam" => "https://us-east-1.console.aws.amazon.com/iamv2/home?region=ap-northeast-1#/home",
            "group" => "https://us-east-1.console.aws.amazon.com/iamv2/home?region=ap-northeast-1#/groups",
            "role" => "https://us-east-1.console.aws.amazon.com/iamv2/home?region=ap-northeast-1#/roles",
        ],
        "ec2"=>[
            "EC2" => "https://ap-northeast-1.console.aws.amazon.com/ec2/home?region=ap-northeast-1#Home:",
            "RDS" => "https://ap-northeast-1.console.aws.amazon.com/rds/home?region=ap-northeast-1#",
            "ElastiCache" => "https://ap-northeast-1.console.aws.amazon.com/elasticache/home?region=ap-northeast-1#",
            "DynamoDB" => "https://ap-northeast-1.console.aws.amazon.com/dynamodbv2/home?region=ap-northeast-1#",
        ],
        "lambda"=>[
            "lambda" => "https://ap-northeast-1.console.aws.amazon.com/lambda/home?region=ap-northeast-1#/functions",
            "APIGateway" => "https://ap-northeast-1.console.aws.amazon.com/apigateway/home?region=ap-northeast-1",
        ],
        "cloudfront" => [
            "CloudFront" => "https://us-east-1.console.aws.amazon.com/cloudfront/v3/home?region=ap-northeast-1#",
            ],
        "container" => [
            "ECS(fargate)" => "https://ap-northeast-1.console.aws.amazon.com/ecs/v2/home?region=ap-northeast-1#",
            "EKS(kubernates)" => "https://ap-northeast-1.console.aws.amazon.com/eks/home?region=ap-northeast-1#",
        ],
        "watch" => [
            "CloudWatch" => "https://ap-northeast-1.console.aws.amazon.com/cloudwatch/home?region=ap-northeast-1#home:",
        ],
        "cost" => [
            "Cost" => "https://us-east-1.console.aws.amazon.com/billing/home?region=ap-northeast-1#/",
        ],
    ];
    if (isset($console_urls[$key])){
        foreach ($console_urls[$key] as $name => $url1){
            echo '<a target="_blank" href="' . $url1 . '">' . strPink($name) . '</a> ';
        }
        echo BR;
    }
}

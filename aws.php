<?php
include 'inc.php';
include 'aws_inc.php';


$SQLITE_PATH = "aws.sqlite";
$sqlite = sqliteConnect($SQLITE_PATH); // incで接続したmysqlをsqliteで上書き

$view = getRequest("view",false,"ec2");

//sqlite
$filter = getRequest("filter");
$delid = getRequest("delid");
$delspan = getRequest("delspan");

htmlHeader("AWS " . $view);
menu();
echo str150("AWS ") .strSilver("aws cliを使ったaws管理") . BR;

showMenus();
showAWSLinks($view);

if ($view=="conf"){  echo nl2br(`cat ~/.aws/credentials`);  }
if ($view=="cloudfront"){

    $ecs_subcom = [
      //  "list-cache-policies",
        "list-cloud-front-origin-access-identities",
      //  "list-conflicting-aliases",
        "list-continuous-deployment-policies",
       // "list-distributions",
     //   "list-distributions-by-cache-policy-id",
     //   "list-distributions-by-key-group",
     //   "list-distributions-by-origin-request-policy-id",
     //   "list-distributions-by-realtime-log-config",
     //   "list-distributions-by-response-headers-policy-id",
      //  "list-distributions-by-web-acl-id",
        "list-field-level-encryption-configs",
        "list-field-level-encryption-profiles",
        "list-functions",
     //   "list-invalidations",
        "list-key-groups",
        "list-origin-access-controls",
        "list-origin-request-policies",
        "list-public-keys",
        "list-realtime-log-configs",
        "list-response-headers-policies",
        "list-streaming-distributions",
   //     "list-tags-for-resource",
    ];
    foreach ($ecs_subcom as $com1){
        echo link2detail("raw",shellCache($sqlite,"aws cloudfront " .$com1 )) . BR;
    }


    $ec2list = shellCache($sqlite,"aws cloudfront list-cache-policies");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["CachePolicyList"]["Items"];
    $cols = ["Type",
        "CachePolicy CachePolicyConfig Name:Name",
        "CachePolicy CachePolicyConfig Comment:Comment",
        "CachePolicy CachePolicyConfig DefaultTTL:DefaultTTL",
        "CachePolicy CachePolicyConfig MaxTTL:MaxTTL",
        "CachePolicy CachePolicyConfig MinTTL:MinTTL",
    ];
    $rets = instances2records($instances,$cols);
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);



    $ec2list = shellCache($sqlite,"aws cloudfront list-distributions");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["DistributionList"]['Items'];
    $cols = ["Status","Enabled","Id","LastModifiedTime:Modified","DomainName",
    "Origins Quantity:Origins",
   // "ARN",
    ];
////
    $rets =[];
    foreach ($instances as &$node1){
        $ret = instance2row($node1,$cols);
        //$ret["Replicas"] = 0;
        $ret['OriginUrls']="";
        foreach ($node1["Origins"]["Items"] as &$row2){
            $ret['OriginUrls'] .= $row2["DomainName"] . SPC;
        }
        $rets[] = $ret;
    }
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);

    foreach ($instances as $node){
        $id = $node["Id"];
        $ec2list = shellCache($sqlite,"aws cloudfront get-distribution --id " . $id);
        echo link2detail("raw",$ec2list) . BR;
        $ec2list = shellCache($sqlite,"aws cloudfront get-distribution-config --id " . $id);
        echo link2detail("raw",$ec2list) . BR;
    }

    $ec2list = shellCache($sqlite,"aws cloudfront list-functions");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["FunctionList"]["Items"];
    // $cols = ["MaxItems","InstanceType","PlatformDetails","LaunchTime","PrivateDnsName",
    // "KeyName","SubnetId","VpcId"];
    // $rets = instances2records($instances,$cols);
    // echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    //echo asc2html($rets);



}
if ($view=="container"){

    $ecs_subcom = [
        "list-clusters",
     //   "list-services",
    //    "list-tasks",
    ];
    foreach ($ecs_subcom as $com1){
        echo link2detail("raw",shellCache($sqlite,"aws ecs " .$com1 )) . BR;
    }



    $ec2list = shellCache($sqlite,"aws ecr describe-repositories");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["repositories"];
    $cols = ["repositoryName","createdAt","imageTagMutability"];
    $rets = instances2records($instances,$cols);

    $rets = setlink2Records($rets,"repositoryName","https://ap-northeast-1.console.aws.amazon.com/ecr/repositories/private/086548466210/***?region=ap-northeast-1");

    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);



}

if ($view=="ssm"){

    $ecs_subcom = [
        "list-stacks",
        "list-exports",
        // "list-stack-instance-resource-drifts",
        // "list-stack-instances",
        // "list-stack-resources",
        // "list-stack-set-operation-results",
        // "list-stack-set-operations",
        // "list-stack-sets",
        // "list-type-registrations",
        // "list-type-versions",
        "list-types",
    ];

    foreach ($ecs_subcom as $com1){
        echo link2detail("raw",shellCache($sqlite,"aws cloudformation " .$com1 )) . BR;
    }

    $ecs_subcom = [
        "list-commands",
     //   "list-types",
    ];

    foreach ($ecs_subcom as $com1){
        echo link2detail("raw",shellCache($sqlite,"aws ssm " .$com1 )) . BR;
    }

    $ec2list = shellCache($sqlite,"aws ssm list-documents");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["DocumentIdentifiers"];
    $cols = ["Owner","DocumentType",
    "DocumentFormat","TargetType",
    "Name","CreatedDate",];
    $rets = instances2records($instances,$cols);
    $rets = setTime2Records($rets);
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);
}

if ($view=="watch"){

    $ecs_subcom = [
      //  "list-dashboards",
      //  "list-managed-insight-rules",
        "list-metric-streams",
     //   "list-metrics",
   //     "list-tags-for-resource",
        "describe-alarm-history",
    ];
    foreach ($ecs_subcom as $com1){
        echo link2detail("raw",shellCache($sqlite,"aws cloudwatch " .$com1 )) . BR;
    }



    $ec2list = shellCache($sqlite,"aws cloudwatch describe-alarms");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["MetricAlarms"];
    $cols = ["Namespace","MetricName","Statistic",
    "Period","StateValue","Threshold",
            "AlarmConfigurationUpdatedTimestamp:ConfigUpdated",
            "AlarmName",
            "ActionsEnabled",
            //"AlarmArn"
        ];
    $rets = instances2records($instances,$cols);
    $rets = setTime2Records($rets);
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);

    $ec2list = shellCache($sqlite,"aws cloudwatch list-dashboards");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["DashboardEntries"];
    $cols = ["DashboardName","DashboardArn","LastModified","Size"];
    $rets = instances2records($instances,$cols);
    $rets = setTime2Records($rets);
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);

    $ec2list = shellCache($sqlite,"aws cloudwatch get-dashboard --dashboard-name rds");
    echo link2detail("raw",$ec2list) . BR;

    // 既存の選べるメトリクス全部
    $ec2list = shellCache($sqlite,"aws cloudwatch list-metrics");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["Metrics"];
    $cols = ["Namespace","MetricName"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
   // echo asc2html($rets);



}


if ($view=="ec2"){

    //ec2
    echo strOrange("EC2") . SPC;
    $ec2list = shellCache($sqlite,"aws ec2 describe-instances");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["Reservations"][0]["Instances"];
    $cols = ["State Name:State","PlatformDetails","InstanceType","LaunchTime","Monitoring State:Monitor","KeyName","InstanceId","PrivateDnsName",
    "SubnetId","VpcId"];
    $rets = instances2records($instances,$cols);
    $rets = setTime2Records($rets);
    $rets = setlink2Records($rets,"InstanceId","https://ap-northeast-1.console.aws.amazon.com/ec2/home?region=ap-northeast-1#InstanceDetails:instanceId=***");
    foreach ($rets as &$row){
        if ($row["State"] == "stopped") $row["State"] = strRed($row["State"]);
    }

    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);



    // rds
    echo strOrange("RDS-Cluster") . SPC;
    $rdslist = shellCache($sqlite,"aws rds describe-db-clusters");
    $rdss = json_decode($rdslist,true);
    $instances = $rdss["DBClusters"];
    $cols = ["Status","DBClusterIdentifier:Cluster","MultiAZ",
    "Engine","EngineVersion:Ver","Port"];
    $rets = instances2records($instances,$cols);
    $rets = setlink2Records($rets,"Cluster","https://ap-northeast-1.console.aws.amazon.com/rds/home?region=ap-northeast-1#database:id=***;is-cluster=true");
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$rdslist) . BR;
    foreach ($rets as &$row){
        if ($row["Status"] == "stopping") $row["Status"] = strRed($row["Status"]);
    }
    echo asc2html($rets);

    echo strOrange("RDS-Instance") . SPC;
    $rdslist = shellCache($sqlite,"aws rds describe-db-instances");
    $rdss = json_decode($rdslist,true);
    $instances = $rdss["DBInstances"];
    $cols = ["DBInstanceStatus:Status","DBClusterIdentifier:Cluster","AvailabilityZone:AZ","Engine","EngineVersion:Ver",
        "DBInstanceClass","DBInstanceIdentifier","StorageType",
            "DBSubnetGroup VpcId:VpcId" ,"MasterUsername:MasterUser","AllocatedStorage","BackupRetentionPeriod:BKCycleDays",
            "DBInstanceArn:Arn"];
    $rets = instances2records($instances,$cols);
    $rets = setlink2Records($rets,"DBInstanceIdentifier","https://ap-northeast-1.console.aws.amazon.com/rds/home?region=ap-northeast-1#database:id=***;is-cluster=false");
    foreach ($rets as &$row){
        if ($row["Status"] == "stopping") $row["Status"] = strRed($row["Status"]);
    }
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$rdslist) . BR;
    echo asc2html($rets);

    //cache
    echo strOrange("Elasticache-Cluster") . SPC;
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



}
if ($view=="opensearch"){

    echo strOrange("OpenSearch") . SPC;
    $vpclist = shellCache($sqlite,"aws opensearch list-domain-names");
    $vpcs = json_decode($vpclist,true);
    $items = $vpcs["DomainNames"];
    $cols = ["DomainName","EngineType"];
    $rets = instances2records($items,$cols);
    $rets = setlink2Records($rets,"DomainName","https://ap-northeast-1.console.aws.amazon.com/aos/home?region=ap-northeast-1#opensearch/domains/***" );
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$vpclist) . BR;
    echo asc2html($rets);

    $vpclist = shellCache($sqlite,"aws opensearch describe-domain --domain-name opensearch1");
    $vpcs = json_decode($vpclist,true);
    $item = $vpcs;
    $cols = ["DomainStatus DomainName:Domain",
    "DomainStatus EngineVersion:Engine",
    "DomainStatus VPCOptions VPCId:VPC",
    "DomainStatus ARN:ARN",
];
    $ret = instance2row($item,$cols);
    echo SPC . count($ret) .SPC;
    echo link2detail("raw",$vpclist) . BR;
    echo asc2html([$ret]);


}




if ($view=="dynamo"){

    // dynamo
    echo BR. strOrange("DynamoDB") . BR;
    $dynamolist = shellCache($sqlite,"aws dynamodb list-tables");
    $dynamoname_ary = json_decode($dynamolist,true);
    $items = $dynamoname_ary["TableNames"];
    $dynamos =[];
    foreach ($items as $tablename) $dynamos[] = ["TableName" => $tablename];
    echo SPC . count($items) .SPC;
    echo link2detail("raw",$dynamolist) . BR;

    // $dynamolist = shellCache($sqlite,"aws dynamodb list-backups");
    // echo link2detail("raw",$dynamolist) . BR;
    // $dynamolist = shellCache($sqlite,"aws dynamodb describe-continuous-backups --table-name dynamotable1");
    // echo link2detail("raw",$dynamolist) . BR;

    // $dynamolist = shellCache($sqlite,"aws dynamodb list-contributor-insights");
    // echo link2detail("raw",$dynamolist) . BR;
    // $dynamolist = shellCache($sqlite,"aws dynamodb list-exports");
    // echo link2detail("raw",$dynamolist) . BR;
    // $dynamolist = shellCache($sqlite,"aws dynamodb list-global-tables");
    // echo link2detail("raw",$dynamolist) . BR;
    // $dynamolist = shellCache($sqlite,"aws dynamodb list-imports");
    // echo link2detail("raw",$dynamolist) . BR;


    $rets =[];
    foreach ($dynamos as $row){
        $dynatable_list = shellCache($sqlite,"aws dynamodb describe-table --table-name " . $row['TableName']);
        $dynamo_ary = json_decode($dynatable_list,true);
        $item = $dynamo_ary["Table"];
        $cols = ["TableName","TableStatus:Status","TableSizeBytes:Bytes",
            "ItemCount:Items",
            "Keys", // ダミー 後から入れる
            "CreationDateTime:Created",
            "ProvisionedThroughput NumberOfDecreasesToday : DecreasesToday",
            "ProvisionedThroughput ReadCapacityUnits : RCapa",
            "ProvisionedThroughput WriteCapacityUnits : WCapa",
            "BillingModeSummary BillingMode:BillingMode",
            "Replicas","ReplicaList",// あとから埋める用のダミー
            "TableId",
            ];
        $ret = instance2row($item,$cols);
        $ret = setlink2Row($ret,"TableName","https://ap-northeast-1.console.aws.amazon.com/dynamodbv2/home?region=ap-northeast-1#table?name=***" );



        $ret["Replicas"] = 0;
        $ret["ReplicaList"] = "";
        if (isset($item["Replicas"])){
            $ret["Replicas"] = count($item["Replicas"]) ;
            foreach ($item["Replicas"] as $ary1) {
                $ret["ReplicaList"] .= $ary1['RegionName'] . SPC;
            }
        }
        $ret['Keys'] ="";
        foreach ($item["AttributeDefinitions"] as $node2){
            $ret['Keys'] .= $node2['AttributeName'] . SPC;
            $ret['Keys'] .= strOrange($node2['AttributeType']) . SPC;
        }
        echo link2detail("raw",$dynatable_list) . BR;
        $ret = setTime2Row($ret);
        $rets[] = $ret;
    }
    echo asc2html($rets);



    // $time = time();
    // for($i = 0; $i < 5;$i++){
    //     $shell = 'aws dynamodb put-item --table-name dynamotable1 --item \'{"pk1": {"S": "key' .$time . $i . '"}, "SongTitle": {"S": "Howdy"}}\'';
    //     runShell($shell);
    //     echo BR;
    //     $shell = 'aws dynamodb put-item --table-name gdfsdfggfd --item \'{"key1": {"S": "key1' .$time . $i . '"}, "key2": {"N":"' .$time . $i . '"},"name": {"S": "Howdy"}}\'';
    //     runShell($shell);
    //     echo BR;
    // }



}


if ($view=="sqlite"){

    if ($delid) {
        if ($delid == "all") sqlExec("delete from shell_results ",$sqlite);
        if ($delid == "old") sqlExec("delete from shell_results where created < " . (time() - 86400 * $delspan),$sqlite);
        if (is_numeric($delid)) sqlExec("delete from shell_results where id=". $delid,$sqlite);
    }



    $sql = "select '' action,* from shell_results ";
    $retall = sql2asc($sql,$sqlite);
    $stat = [];

    foreach ($retall as &$row){
        $suncommand = explode(" ",$row['command'])[1];
        if (!isset($stat[$suncommand])) $stat[$suncommand] = 0;
        $stat[$suncommand]++;
        $row['action'] = '<a href="?view=sqlite&delid=' .$row['id']. '">del</a>';
    }
    echo "DEL ";
    echo '<a href="?view=sqlite&delid=all">All</a>' . SPC;
    echo '<a href="?view=sqlite&delid=old&delspan=1">1Day</a>' . SPC;
    echo '<a href="?view=sqlite&delid=old&delspan=2">2Day</a>' . BR;

    foreach ($stat as $key => $val){
        echo '<a href="?view=sqlite&filter=' .$key. '">' . $key . SPC . strGray($val) . '</a>' . SPC. SPC;
    }
    ?>
    <form id="f1">
        <input type="hidden" name="view" id="view" value="sqlite">
        <input type="text" name="filter" id="filter" value="<?=$filter?>">
    </form>
    <?php
    if ($filter) $sql .= "where command like '%" .$filter. "%' ";
    $sql .= " order by command asc";
    $ret_filter = sql2asc($sql,$sqlite);
    foreach ($ret_filter as &$row){
        $row['action'] = '<a href="?view=sqlite&filter=' .$filter. '&delid=' .$row['id']. '">del</a>';
        $row["command"] = jstrim($row["command"],65);
        $row["result_text"] = jstrim($row["result_text"],65);
    }
    echo asc2html($ret_filter);
    exit();
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
    $rets = setlink2Records($rets,"VpcId",'https://ap-northeast-1.console.aws.amazon.com/vpc/home?region=ap-northeast-1#VpcDetails:VpcId=***');
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$vpclist) . BR;
    echo asc2html($rets);

    $vpclist = shellCache($sqlite,"aws ec2 describe-route-tables");
    $vpcs = json_decode($vpclist,true);
    $cols = ["RouteTableId","VpcId","OwnerId"];
    $rets = instances2records($vpcs["RouteTables"],$cols);
    $rets = setlink2Records($rets,"RouteTableId",'https://ap-northeast-1.console.aws.amazon.com/vpc/home?region=ap-northeast-1#RouteTableDetails:RouteTableId=***');
    foreach ($vpcs as $node){

    }

    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$vpclist) . BR;
    echo asc2html($rets);

    $vpclist = shellCache($sqlite,"aws ec2 describe-transit-gateways");
    echo link2detail("raw",$vpclist) . BR;



    $subnetlist = shellCache($sqlite,"aws ec2 describe-subnets");
    $subnets = json_decode($subnetlist,true);
    $cols = ["SubnetId","VpcId","AvailabilityZoneId","AvailabilityZone","CidrBlock","AvailableIpAddressCount","State","SubnetArn"];
    $rets = instances2records($subnets["Subnets"],$cols);
    $rets = setlink2Records($rets,"SubnetId",'https://ap-northeast-1.console.aws.amazon.com/vpc/home?region=ap-northeast-1#SubnetDetails:subnetId=***');
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$subnetlist) . BR;
    echo asc2html($rets);


    $subnetlist = shellCache($sqlite,"aws ec2 describe-network-interfaces");
    $subnets = json_decode($subnetlist,true);
    $items = $subnets["NetworkInterfaces"];
    $cols = ["RequesterId","Description","Status","SubnetId","VpcId","Attachment AttachTime:AttachTime"];
    $rets = instances2records($items,$cols);
    $rets = setTime2Records($rets);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$subnetlist) . BR;
    echo asc2html($rets);


    $ec2list = shellCache($sqlite,"aws ec2 describe-key-pairs");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["KeyPairs"];
    $cols = ["KeyName","KeyType","KeyPairId","CreateTime"];
    $rets = instances2records($instances,$cols);
    $rets = setTime2Records($rets);
    echo SPC . count($instances) .SPC;
    echo link2detail("raw",$ec2list) . BR;
    echo asc2html($rets);



    $securitylist = shellCache($sqlite,"aws ec2 describe-security-groups");
    $securities = json_decode($securitylist,true);
    $items = $securities["SecurityGroups"];
    $rets = [];
    foreach ($items as $node){
        $ret = instance2row($node,["GroupId","GroupName","Description","VpcId"]);
        $ret["ips_in"] = "";
        foreach ($node["IpPermissions"] as $node1){
            $ret["ips_in"] .= $node1["IpProtocol"] . SPC ;
            if (isset($node1["FromPort"])) $ret["ips_in"].=$node1["FromPort"] . SPC;
            $ret["ips_in"].=" | " ;
        }
        $ret["ips_out"] = "";
        foreach ($node["IpPermissionsEgress"] as $node1){
            $ret["ips_out"] .= $node1["IpProtocol"] . SPC ;
            if (isset($node1["FromPort"])) $ret["ips_out"] .= $node1["FromPort"] . SPC;
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
    $recs1 = getCostMatrixTable($rets,"month");
    echo asc2html(assocZeroSilver($recs1));

    echo BR .strBold("今月Daily " ). BR;
    $costlist = shellCache($sqlite,"aws ce get-cost-and-usage --time-period Start=" .date("Y-m-01"). ",End=" . date("Y-m-d") . " --granularity DAILY --metrics UnblendedCost --group-by Type=DIMENSION,Key=SERVICE");
    $rets = json2tableCost($costlist);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$costlist) . BR;
    // echo asc2html($rets);
    $recs1 = getCostMatrixTable($rets,"day");
    echo asc2html(assocZeroSilver($recs1));
}

if ($view=="lambda"){


    $ecs_subcom = [
       // "list-aliases",
      //  "list-function-url-configs",
      "get-function-configuration --function-name CFfunction1",
      "get-function-url-config --function-name CFfunction1",
      "get-policy --function-name CFfunction1",
    ];
    foreach ($ecs_subcom as $com1){
        echo link2detail("raw",shellCache($sqlite,"aws lambda " .$com1 )) . BR;
    }



    $lambdalist = shellCache($sqlite,"aws lambda list-functions");
    $lambdas = json_decode($lambdalist,true);
    $instances = $lambdas["Functions"];
    $cols = ["FunctionName","Description","Runtime","CodeSize","Timeout","MemorySize","LastModified","EphemeralStorage Size"];
    $rets = instances2records($instances,$cols);
    $rets = setlink2Records($rets,"FunctionName",'https://ap-northeast-1.console.aws.amazon.com/lambda/home?region=ap-northeast-1#/functions/***?tab=code');

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

    $ecs_subcom = [
        "get-group --group-name ec2_group",
         "get-user ",
      //   "list-access-keys",
        // "describe-tasks",
    ];
    foreach ($ecs_subcom as $com1){
        echo link2detail("raw",shellCache($sqlite,"aws iam " .$com1 )) . BR;
    }






    $grouplist = shellCache($sqlite,"aws iam list-access-keys");
    $groups = json_decode($grouplist,true);
    $instances = $groups["AccessKeyMetadata"];
    $cols = ["UserName","AccessKeyId","Status","CreateDate"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$grouplist) . BR;
    echo asc2html($rets);


    $grouplist = shellCache($sqlite,"aws iam list-groups");
    $groups = json_decode($grouplist,true);
    $instances = $groups["Groups"];
    $cols = ["Path","GroupName","Users","Policies","GroupId","CreateDate","Arn"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$grouplist) . BR;
    foreach ($rets as &$row){
        $grouplist = shellCache($sqlite,"aws iam get-group --group-name " . $row["GroupName"]);
        echo link2detail("raw",$grouplist) . BR;
        $groups = json_decode($grouplist,true);
        $row["Users"] = count($groups["Users"]);

        $grouplist = shellCache($sqlite,"aws iam list-attached-group-policies --group-name " . $row["GroupName"]);
        echo link2detail("raw",$grouplist) . BR;
        $groups = json_decode($grouplist,true);
        $row["Policies"] = count($groups["AttachedPolicies"]);

    }

    $rets = setTime2Records($rets);
    echo asc2html($rets);

    $userlist = shellCache($sqlite,"aws iam list-users");
    $users = json_decode($userlist,true);
    $instances = $users["Users"];
    $cols = ["UserName","UserId","CreateDate","Arn"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$userlist) . BR;
    foreach ($rets as &$row){
        $grouplist = shellCache($sqlite,"aws iam get-user --user-name " . $row["UserName"]);
        echo link2detail("raw",$grouplist) . BR;
    }
    $rets = setTime2Records($rets);
    echo asc2html($rets);

    $userlist = shellCache($sqlite,"aws iam list-roles");
    $users = json_decode($userlist,true);
    $instances = $users["Roles"];
    $cols = ["RoleName","RoleId","Arn","CreateDate","Description"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$userlist) ;
    echo link2detail("result",asc2html($rets)) . BR;


}

if ($view=="s3"){

    $s3list = shellCache($sqlite,"aws s3api list-buckets");
    $s3s = json_decode($s3list,true);
    $instances = $s3s["Buckets"];
    $cols = ["Name","CreationDate"];
    $rets = instances2records($instances,$cols);


    foreach ($rets as &$row ){
        $name = $row["Name"];
        $s3list = shellCache($sqlite,"aws s3api get-bucket-acl --bucket " . $name);
        echo link2detail("raw",$s3list) . BR;
        $s3list = shellCache($sqlite,"aws s3api get-bucket-encryption --bucket " . $name);
        echo link2detail("raw",$s3list) . BR;

        $s3list = shellCache($sqlite,"aws s3api get-bucket-acl --bucket " . $name);
        echo link2detail("raw",$s3list) . BR;


        $s3list = shellCache($sqlite,"aws s3api get-bucket-location --bucket " . $name);
        echo link2detail("raw",$s3list) . BR;
        $row['Region'] = json_decode($s3list,true)["LocationConstraint"];

        $s3list = shellCache($sqlite,"aws s3api get-bucket-versioning --bucket " . $name);
        echo link2detail("raw",$s3list) . BR;

        $s3list = shellCache($sqlite,'aws s3api list-objects --bucket ' . $name);
        echo link2detail("raw",$s3list) . BR;

        $s3list = shellCache($sqlite,'aws s3api list-objects --query "Contents[] | length(@)" --bucket ' . $name);
        echo link2detail("raw",$s3list) . BR;
        $row['Objects'] = $s3list;

        $s3list = shellCache($sqlite,'aws s3api list-objects --query "Contents[].Size | sum(@)"  --bucket ' . $name);
        echo link2detail("raw",$s3list) . BR;
        $row['TotalSize'] = $s3list;

        $day30pre = date("Y-m-d",time() - (86400 * 30));
        $shell1 = 'aws s3api list-objects --query "Contents[?LastModified > \'' .$day30pre. 'T00:00:00+00:00\'].Size | length(@)" --bucket ' . $name;
        $s3list = shellCache($sqlite,$shell1);
        echo link2detail("raw",$s3list) . BR;
        $row['30日内登録'] = $s3list;

        $day30pre = date("Y-m-d",time() - (86400 * 5));
        $shell1 = 'aws s3api list-objects --query "Contents[?LastModified > \'' .$day30pre. 'T00:00:00+00:00\'].Size | length(@)" --bucket ' . $name;
        $s3list = shellCache($sqlite,$shell1);
        echo link2detail("raw",$s3list) . BR;
        $row['5日内'] = $s3list;


        $s3list = shellCache($sqlite,"aws s3 ls s3://" .$name. " --sum");



    }
    $rets = setlink2Records($rets,"Name",'https://s3.console.aws.amazon.com/s3/buckets/***?region=us-east-1&tab=objects');
    $rets = setTime2Records($rets);
    echo link2detail("raw",$s3list) . BR;
    echo asc2html($rets);
}

if ($view=="ses"){

    $ecs_subcom = [
        "list-configuration-sets",
        "get-configuration-set --configuration-set-name email-conf-set1 ",
        "list-contact-lists",
     //   "list-contacts",
     //   "list-email-identities",
    ];
    foreach ($ecs_subcom as $com1){
        echo link2detail("raw",shellCache($sqlite,"aws sesv2 " .$com1 )) . BR;
    }

    $userlist = shellCache($sqlite,"aws sesv2 list-email-identities");
    $users = json_decode($userlist,true);
    $instances = $users["EmailIdentities"];
    $cols = ["IdentityType","IdentityName",
    "SendingEnabled","VerificationStatus"];
    $rets = instances2records($instances,$cols);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$userlist) . BR;
    echo asc2html($rets);


}

if ($view=="lightsail"){
    //lightsail
    $saillist = shellCache($sqlite,"aws lightsail get-instances");
    $sails = json_decode($saillist,true);
    $items = $sails["instances"];
    $cols = ["state name:state",
    "name",
   // "blueprintId",
    "blueprintName",
    "bundleId",
    "hardware cpuCount:cpus",
    "hardware ramSizeInGb:ram",
    "createdAt",

    "location availabilityZone:AZ",
    //"location regionName:region",
    "publicIpAddress:PublicIp",
    "isStaticIp",
    "username","sshKeyName",

    "arn"];
    $rets = instances2records($items,$cols);
    $rets = setTime2Records($rets);
    echo SPC . count($rets) .SPC;
    echo link2detail("raw",$saillist) . BR;
    echo asc2html($rets);
}

if ($view=="tree"){

    $ec2list = shellCache($sqlite,"aws ec2 describe-instances");
    $ec2s = json_decode($ec2list,true);
    $instances = $ec2s["Reservations"][0]["Instances"];
    $cols = ["PlatformDetails","InstanceType","KeyName","InstanceId","LaunchTime","PrivateDnsName",
    "SubnetId","VpcId"];
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

    $rds_cluster_list = shellCache($sqlite,"aws rds describe-db-clusters");
    $rdss = json_decode($rds_cluster_list,true);
    $rds_cols = $rdss["DBClusters"];
    $cols = ["DBClusterIdentifier","Engine","EngineVersion",
    // "DBInstanceClass","DBClusterIdentifier:Cluster","DBInstanceIdentifier","StorageType",
    //          "DBSubnetGroup VpcId:VpcId" ,"MasterUsername","AllocatedStorage","BackupRetentionPeriod","AvailabilityZone",
    //          "DBInstanceArn"
            ];
    $rds_clusters = instances2records($rds_cols,$cols);

    $rdslist = shellCache($sqlite,"aws rds describe-db-instances");
    $rdss = json_decode($rdslist,true);
    $rds_cols = $rdss["DBInstances"];
    $cols = ["Engine","EngineVersion","DBInstanceClass","DBClusterIdentifier:Cluster","DBInstanceIdentifier","StorageType",
            "DBSubnetGroup VpcId:VpcId" ,"MasterUsername","AllocatedStorage","BackupRetentionPeriod","AvailabilityZone",
            "DBInstanceArn"];
    $rds_tables = instances2records($rds_cols,$cols);


    $subnetlist = shellCache($sqlite,"aws ec2 describe-network-interfaces");
    $subnets = json_decode($subnetlist,true);
    $items = $subnets["NetworkInterfaces"];
    $cols = ["RequesterId","Description","Attachment AttachTime","Status","SubnetId","VpcId"];
    $ni_tables = instances2records($items,$cols);


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
        $subnets = filterRecords($subnet_tables,"VpcId",$row['VpcId']);

        foreach ($subnets as $sn){
            echo strOrangeBG("subnet ") . asc2html([$sn]);
            $nis = asc2html(filterRecords($ni_tables,"SubnetId",$sn['SubnetId']));
            if ($nis){
                echo '<blockquote>';
                echo strOrange("network interface ") . BR . $nis;
                echo '</blockquote>';
            }

        }

       // echo strOrange("subnet ") . BR . asc2html(filterRecords($subnet_tables,"VpcId",$row['VpcId']));

        echo strOrange("securitygroup ") . asc2html(filterRecords($sg_tables,"VpcId",$row['VpcId']));
        echo strOrange("rds clusters") . BR. asc2html(filterRecords($rds_clusters,"VpcId",$row['VpcId']));
        echo strOrange("rds instances") . BR. asc2html(filterRecords($rds_tables,"VpcId",$row['VpcId']));
        echo strOrange("ec2 ") . BR. asc2html(filterRecords($ecs_tables,"VpcId",$row['VpcId']));


        echo '</blockquote>';

    }

}

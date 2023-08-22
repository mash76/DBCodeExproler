<?php
include 'inc.php';

$shell = getRequest("shell");

$del = getRequest("del");
$log = getRequest("log");
$desc = getRequest("desc");
$top = getRequest("top");
$replicas = getRequest("replicas");

htmlHeader("k8s");
menu();
echo str150("Kubernates ") .strSilver("dockerのコンテナ管理") . BR;

if ($del) {
    runShell("kubectl delete pod " . $del); // docker version -f json
    echo BR.nl2br($ret ). BR;
}

if ($top) {
    runShell("kubectl top pod " . $top); // docker version -f json
    echo BR.nl2br($ret ). BR;
}

if ($log) {
    $ret = runShell("kubectl logs -n " . $log); // docker version -f json
    echo BR.nl2br($ret ). BR;
}


if ($replicas) {
    $ret = runShell("kubectl scale --replicas=" .$replicas. " -f nginx.yml "); // docker version -f json
    echo BR.nl2br($ret ). BR;
}

/*
Replicas:               10 desired | 10 updated | 10 total | 10 available | 0 unavailable
StrategyType:           RollingUpdate
MinReadySeconds:        0
RollingUpdateStrategy:  25% max unavailable, 25% max surge
*/
$ret = `kubectl describe deploy nginx-test | egrep -i "(replicas:|strategytype|rolling)"`;
echo strSilver(nl2br($ret));


/*
dashboard関連

ダッシュボードのコンテナをセット
kubectl apply -f https://raw.githubusercontent.com/kubernetes/dashboard/v2.0.0/aio/deploy/recommended.yaml

開始
kubectl proxy
アクセス
http://localhost:8001/api/v1/namespaces/kubernetes-dashboard/services/https:kubernetes-dashboard:/proxy/#/login

ログインにtokenか
kubectl -n kubernetes-dashboard get secret
kubectl -n kube-system describe secret kubernetes-dashboard-certs
*/


// $rep_sets =  runShell("kubectl describe HorizontalPodAutoscaler nginx-test"); // context - kubernates swarm containerをまとめたもの
// echo SPC . link2detail('rep_sets', strCode(nl2br($rep_sets ) ));

//kubectl autoscale deployment
//kubectl get hpa
// リソースメトリクスはcpuかメモリ
// job cronjob


$shells = [
    "version"=>"kubectl version --output=json",
    "confs" => "cat ~/.kube/config",
    'contexts' => "kubectl config get-contexts",
    "services" => "kubectl get services",
    "nodes" => "kubectl get nodes",
    "repsets" => "kubectl get rs -o wide --output=json",
    "deploy" => "kubectl describe deploy nginx-test",
];

foreach ($shells as $name => $sh1){
    echo l2dLink($name) . SPC;
}
foreach ($shells as $name => $sh1){
    echo SPC . l2dDetail($name, strBG($sh1) . BR. strCode(runShell($sh1,false)));
}

echo BR;

?>
replicas
<a href="?replicas=1">1</a>
<a href="?replicas=5">5</a>
<a href="?replicas=10">10</a>
<a href="?replicas=15">15</a>
<br/>

<form id="f1">
    <input type="text" id="shell" name="shell" value="<?=$shell ?>" placeholder="shell cmd">
    <br/>
	<input type="submit" style="display:none;" >
</form>
<?php
if ($shell){
    echo strBG($shell) . BR;
    echo '<pre style="background:#f8f8f8; padding:3px; border-radius:5px;  ">' . strCode(runShell($shell,false)) . '</pre>';
}


$pods_str = runShell("kubectl get pods --all-namespaces --output=json"); // context - kubernates swarm containerをまとめたもの
$pods_ary = json_decode($pods_str,true);

//echo $pods_str;
//echo "<pre>". var_export($pods_ary )."</pre>";
echo BR;
$pods = [];
foreach ($pods_ary['items'] as $key => $node){
    $pod = [];
    $pod['num'] = $key+1;
    $pod['kind'] = $node['kind'] ;
    $pod['namespace'] = $node['metadata']['namespace'];




    $pod['name'] = $node['metadata']['name'];
    $pod['action'] = '<a href="?log='.$pod['namespace'] . " " . $pod['name'] .'">log</a>';
    if ($pod['namespace'] != "kube-system") {
        $pod['action'] .= '<a href="?del=' .$pod['name'] .'">del</a>';
        $pod['action'] .= '<a href="?top=' .$pod['name'] .'">top</a>';
    }else{
        $pod['namespace'] = strGreenBG($pod['namespace']);
    }

    $pod['containers'] = count($node['spec']['containers']);
    $pod['hostIP'] = $node['status']['hostIP'];
    $pod['podIP'] = "";
    if (isset($node['status']['podIP'])) $pod['podIP'] = $node['status']['podIP'];

    $pod['phase'] = $node['status']['phase'];
    if ($pod['phase'] == "Pending") $pod['phase'] = strRed($pod['phase']);
    $pod['time'] = pastSecMin($node['status']['startTime']);

    $pod['cRestarts'] = $node['status']['containerStatuses'][0]['restartCount'];
    $pod['cImage'] = $node['spec']['containers'][0]['image'];

    $str1 = [];
    foreach ($node['metadata']['labels'] as $key =>$val){
        $str1[] .= strSilver($key ) . SPC . $val ;
    }
    $pod['label'] = implode(strBlue(" | "),$str1);

//echo $node['spec']['containers'][0]['ports'][0]['protocol'] . BR;
//echo $node['spec']['containers'][0]['ports'][0]['containerPort'] . BR;
    $pods[] = $pod;
}
echo asc2html($pods);
echo BR. BR;

echo debugFooter();

function pastSecMin($strdate){
    $sec  = time() - strtotime($strdate);
    $ret = "";
    if ($sec < 60) $ret = $sec . "s";
    if (60 <= $sec and $sec < 3600) $ret = floor($sec /60). "m";
    if (3600 <= $sec ) $ret = floor($sec /3600). "h";
    return $ret;
}


/*
{
    "apiVersion": "v1",
    "kind": "Pod",
    "metadata": {
        "creationTimestamp": "2023-08-13T09:49:09Z",
        "generateName": "nginx-test-57d84f57dc-",
        "labels": {
            "app": "nginx",
            "pod-template-hash": "57d84f57dc"
        },
        "name": "nginx-test-57d84f57dc-7cwc7",
        "namespace": "default",
        "ownerReferences": [
            {
                "apiVersion": "apps/v1",
                "blockOwnerDeletion": true,
                "controller": true,
                "kind": "ReplicaSet",
                "name": "nginx-test-57d84f57dc",
                "uid": "e3d4b0f1-5cb4-45b2-8046-4b8d11add96e"
            }
        ],
        "resourceVersion": "1430",
        "uid": "dd3ff207-1c87-48bc-8d95-1a7f8e9e3832"
    },
    "spec": {
        "containers": [
            {
                "image": "nginx:latest",
                "imagePullPolicy": "Always",
                "name": "nginx",
                "ports": [
                    {
                        "containerPort": 80,
                        "protocol": "TCP"
                    }
                ],
                "resources": {},
                "terminationMessagePath": "/dev/termination-log",
                "terminationMessagePolicy": "File",
                "volumeMounts": [
                    {
                        "mountPath": "/var/run/secrets/kubernetes.io/serviceaccount",
                        "name": "kube-api-access-84wbm",
                        "readOnly": true
                    }
                ]
            }
        ],
        "dnsPolicy": "ClusterFirst",
        "enableServiceLinks": true,
        "nodeName": "docker-desktop",
        "preemptionPolicy": "PreemptLowerPriority",
        "priority": 0,
        "restartPolicy": "Always",
        "schedulerName": "default-scheduler",
        "securityContext": {},
        "serviceAccount": "default",
        "serviceAccountName": "default",
        "terminationGracePeriodSeconds": 30,
        "tolerations": [
            {
                "effect": "NoExecute",
                "key": "node.kubernetes.io/not-ready",
                "operator": "Exists",
                "tolerationSeconds": 300
            },
            {
                "effect": "NoExecute",
                "key": "node.kubernetes.io/unreachable",
                "operator": "Exists",
                "tolerationSeconds": 300
            }
        ],
        "volumes": [
            {
                "name": "kube-api-access-84wbm",
                "projected": {
                    "defaultMode": 420,
                    "sources": [
                        {
                            "serviceAccountToken": {
                                "expirationSeconds": 3607,
                                "path": "token"
                            }
                        },
                        {
                            "configMap": {
                                "items": [
                                    {
                                        "key": "ca.crt",
                                        "path": "ca.crt"
                                    }
                                ],
                                "name": "kube-root-ca.crt"
                            }
                        },
                        {
                            "downwardAPI": {
                                "items": [
                                    {
                                        "fieldRef": {
                                            "apiVersion": "v1",
                                            "fieldPath": "metadata.namespace"
                                        },
                                        "path": "namespace"
                                    }
                                ]
                            }
                        }
                    ]
                }
            }
        ]
    },
    "status": {
        "conditions": [
            {
                "lastProbeTime": null,
                "lastTransitionTime": "2023-08-13T09:49:09Z",
                "status": "True",
                "type": "Initialized"
            },
            {
                "lastProbeTime": null,
                "lastTransitionTime": "2023-08-13T09:49:12Z",
                "status": "True",
                "type": "Ready"
            },
            {
                "lastProbeTime": null,
                "lastTransitionTime": "2023-08-13T09:49:12Z",
                "status": "True",
                "type": "ContainersReady"
            },
            {
                "lastProbeTime": null,
                "lastTransitionTime": "2023-08-13T09:49:09Z",
                "status": "True",
                "type": "PodScheduled"
            }
        ],
        "containerStatuses": [
            {
                "containerID": "docker://947a28b46f2769aa9bb1adbb4bb482a749149fb2357c38e9c431f7703172dc9f",
                "image": "nginx:latest",
                "imageID": "docker-pullable://nginx@sha256:67f9a4f10d147a6e04629340e6493c9703300ca23a2f7f3aa56fe615d75d31ca",
                "lastState": {},
                "name": "nginx",
                "ready": true,
                "restartCount": 0,
                "started": true,
                "state": {
                    "running": {
                        "startedAt": "2023-08-13T09:49:12Z"
                    }
                }
            }
        ],
        "hostIP": "192.168.65.4",
        "phase": "Running",
        "podIP": "10.1.0.9",
        "podIPs": [
            {
                "ip": "10.1.0.9"
            }
        ],
        "qosClass": "BestEffort",
        "startTime": "2023-08-13T09:49:09Z"
    }
},

*/

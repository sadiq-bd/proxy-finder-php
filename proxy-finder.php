<?php
foreach ($argv as $arg) {
    $e=explode("=",$arg);
    if(count($e)==2)
        $_GET[$e[0]]=$e[1];
    else   
        $_GET[$e[0]]=0;
}

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

$cli_green = "\033[1;92m";
$cli_red = "\033[1;91m";

function fetch(string $url, array $post = [], array $headers = [], string $proxy = '') {
    $curl = curl_init($url);
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => array_merge(array(
            'Host: ' . parse_url($url)['host'],
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language: en-GB,en-US;q=0.9,en;q=0.8,bn;q=0.7',
            'Connection: keep-alive',
            'Cache-Control: max-age=0',
            'User-Agent: Mozilla/5.0 (Linux; Android 11; RMX2103 Build/RKQ1.201217.002; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/'.rand(110, 114).'.0.5672.131 Mobile Safari/537.36',
            'Upgrade-Insecure-Requests: 1'
        ), $headers),
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 3
    ));

    if (strlen($proxy) > 1) {
        curl_setopt($curl, CURLOPT_PROXY, $proxy);
    }


    if (count($post) > 0) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }

    $resp = curl_exec($curl);


    $error = curl_errno($curl) ? curl_error($curl) : '';
    

    curl_close($curl);

    $obj = new stdClass;

    $obj->response = $resp;
    $obj->error = $error;

    return $obj;
}


$proxyList = file_get_contents('https://proxylist.geonode.com/api/proxy-list?limit='.$limit.'&page=1&sort_by=lastChecked&sort_type=desc');

$proxyList = json_decode($proxyList, true)['data'];

$proxyOkList = [];

foreach ($proxyList as $proxy) {

    $proxyUrl = $proxy['protocols'][0] . '://' . $proxy['ip'] . ':' . $proxy['port'];
    if ($fetch = fetch(
        'https://www.google.com/',
        [],
        [],
        $proxyUrl)) {
        if (strlen($fetch->error) < 1) {
            $proxyOkList[] = $proxyUrl;
            echo $cli_green . "\nProxy OK " . $proxyUrl . "\n";
        } else {
            echo $cli_red . "\nProxy Error [".$proxyUrl."]: " . $fetch->error . "\n";
        }
    }

}

if (count($proxyOkList) > 0) {
    $proxyOkListJson = json_encode($proxyOkList);

    $file = fopen($fname = __DIR__ . '/proxy_list_' . uniqid() . '.json', 'w+');
    fwrite($file, $proxyOkListJson);
    fclose($file);
    echo $cli_green . "\n\n".count($proxyOkList)." workable proxy found \n\n";
    echo $cli_green . "\n\nProxy List File: " . $fname . "\n\n";
} else {
    echo $cli_red . "\n\nNo workable proxy found \n\n";
}


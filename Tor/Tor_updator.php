<?php

if (isset($argv[1]) && $argv[1] == '-help') {
    exit('https://metrics.torproject.org/collector/recent/exit-lists/' . PHP_EOL);
}

$fileName = input("Enter File name :");
$db = input("Enter Database name :");
$createStatement = input("Create table? Y/n :");
$text = file_get_contents('https://collector.torproject.org/recent/exit-lists/' . $fileName);

$exitNode = '';
$lastStatus = '';
$nodes  = [];
foreach(preg_split("/((\r?\n)|(\r\n?))/", $text) as $line){
    if (strpos($line, "ExitNode") !== false) {
        $ext = explode(' ', $line);
        $exitNode = $ext[1];
    }
    if (strpos($line, "LastStatus") !== false) {
        $ext = explode(' ', $line);
        $lastStatus = $ext[1];
    }
    if (strpos($line, "ExitAddress ") !== false) {
        $ext = explode(' ', $line);
        $ip = $ext[1];
        $nodes[$exitNode] = [
            'last_checked' => $lastStatus,
            'ip'	=> $ip
        ];
    }
}

$sql = '';
if ($db != '') {
    $sql .= 'USE ' . $db . ';' . PHP_EOL;
}

if ($createStatement == 'Y' || $createStatement == 'y') {
    $sql = 'CREATE TABLE IF NOT EXISTS `exit_nodes` (
      `ip` varchar(100) NOT NULL,
      `last_checked` varchar(45) DEFAULT NULL,
      `node_id` varchar(150) DEFAULT NULL,
      PRIMARY KEY (`ip`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
';
}

foreach ($nodes as $key => $value) {
    $sql .= 'INSERT INTO exit_nodes (ip, last_checked, node_id) VALUES ';

    $sql .= '("' . $value['ip'] . '","' . $value['last_checked'] . '", "' . $key . '")';
    $sql .= 'ON DUPLICATE KEY UPDATE last_checked = "' . $value['last_checked'] . '", node_id="' . $key . '";' . PHP_EOL;
}

file_put_contents('tor_update_script.sql',$sql);

function input(string $prompt = null): string
{
    echo $prompt;
    $handle = fopen ("php://stdin","r");
    $output = fgets ($handle);
    return trim ($output);
}

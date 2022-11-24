<?php
echo "Script started!\r\n";

$db = new SQLite3(__DIR__.'/cars.db');
$res = $db->query("SELECT * FROM cars where name like 'Mercedes A 180%' and first_registration_date like '%/2019' and mileage between 60000 and 120000");
while ($car = $res->fetchArray()) {
    echo "-----------------------------\r\n";
    echo "ID: ".$car['id']."\r\n";
    echo "Name: ".$car['name']."\r\n";
    echo "URL: ".$car['url']."\r\n";
    echo "Photos: \r\n";
    // Getting exist car photos;
    $car_hash = md5($car['id']);

    // Getting "blocks";
    $path = __DIR__.'/photos';
    for($i = 0 ; $i < 32 / 2 ; $i++){
        $path .= '/'.substr($car_hash, $i * 2, 2);
    }

    if(!file_exists($path)){
        echo "ERROR! Photos for this cars does not exist.\r\n";
        continue;
    }

    $photos = scandir($path.'/original');
    foreach($photos as $photo){
        if(in_array($photo, ['.', '..'])) continue;

        echo $path.'/'.$photo."\r\n";
    }

    echo "-----------------------------\r\n";
}
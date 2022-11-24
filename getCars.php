<?php
require __DIR__.'/vendor/autoload.php';

$source_url = 'https://ecarstrade.com';

echo "Script started!\r\n";
echo "Getting pages count...\r\n";

$html = file_get_contents($source_url."/auctions/stock");
$dom = \voku\helper\HtmlDomParser::str_get_html($html);

// Get total block;
$total_block = $dom->findOne('.motorTable_fromToPagebar')->innerText();
$total_block_temp = explode('/', $total_block);

// Total cars;
$total_cars = 0;
if(count($total_block_temp) > 1) $total_cars = trim($total_block_temp[1]);

// Small loop for every page;
$pages_count = ceil($total_cars / 20);

echo "Total pages count: $pages_count\r\n";

$cars_data = [];
$images_counter = 0;
for($i = 1 ; $i <= $pages_count; $i++){
    echo "Getting data from page: $i / $pages_count...\r\n";

    $page_html = file_get_contents($source_url.'/auctions/stock/page'.$i.'?sort=mark_model.asc');
    $cars_dom = \voku\helper\HtmlDomParser::str_get_html($page_html);
    $cars = $cars_dom->find('.items-list .car-item');

    foreach($cars as $car){
        echo "Scrapping data for car item...\r\n";

        $car_item = [
            'id' => null,
            'url' => null,
            'name' => null,
            'price' =>rand(111111, 999999),
            'features' => [],
            'photos' => [
                'original' => [],
                'thumbnail' => []
            ]
        ];

        // Car ID;
        $car_item['id'] = $car->getAttribute('data-itemid');

        // Car URL;
        $car_item['url'] = '/cars/'.$car_item['id'];

        // Car name;
        $name = $cars_dom->findOne('a[href="'.$car_item['url'].'"] div')->innerText();
        $name = str_replace('#'.$car_item['id'].' - ', '', $name);
        $car_item['name'] = $name;

        // Photos;
        $photos = $cars_dom->find('.item-photos a[href="'.$car_item['url'].'"] .hover-photos .hover-photo');
        foreach($photos as $photo){
            $src = $photo->getAttribute('data-src');

            // Thumbnail;
            $car_item['photos']['thumbnail'][] = $source_url.$src;
            $images_counter++;

            // Original;
            $car_item['photos']['original'][] = $source_url.str_replace(['/thumbnails', '/260x0__r'], '', $src);
            $images_counter++;

            // We need to get only first photo. Remove this break, if you want to get all photos;
            break;
        }

        // Features;
        $car_html = \voku\helper\HtmlDomParser::str_get_html($car->innerHtml());
        $features = $car_html->find('.item-feature');
        $accept_features_array = [
            'first_registration_date',
            'mileage',
            'gearbox',
            'fuel',
            'engine_size',
            'power',
            'emission_class',
            'co2',
            'car_location',
        ];
        foreach($features as $feature){
            $feature_key = $feature->getAttribute('data-original-title');
            $feature_key = str_replace(' ', '_', mb_strtolower($feature_key));
            $feature_key = str_replace(['<sub>', '</sub>'], '', $feature_key);

            if(!in_array($feature_key, $accept_features_array)) continue;

            $value = $feature->text();

            switch($feature_key){
                case 'mileage':
                    $value = str_replace([' ', 'KM'], '', $value);
                    break;

                case 'co2':
                    $value = str_replace(' CO2', '', $value);
                    break;
            }

            $car_item['features'][$feature_key] = $value;
        }

        // Attache source URL ofr main;
        $car_item['url'] = $source_url.$car_item['url'];

        echo "Car ID: ".$car_item['id']." / ".$car_item['name']." - Successfully scrapped.\r\n";
        echo "---------------------\r\n";

        $cars_data[] = $car_item;
    }
}

/*
 * Addition data in DB;
 * */
echo "Addition data in DB...\r\n";
$db = new SQLite3(__DIR__.'/cars.db');
foreach($cars_data as $key => $car_item){
    // Check car on exist;
    $exist_car = $db->querySingle("SELECT * FROM cars WHERE id = ".$car_item['id']);

    if($exist_car != null){
        echo "Car with ID ".$car_item['id']." already exist in DB\r\n";
        unset($cars_data[$key]);
        continue;
    }

    // If car does not exist - add new item in DB;
    $db->exec("INSERT INTO cars(id, url, name, price, first_registration_date, mileage, gearbox, fuel, engine_size, power, emission_class, co2, car_location)                         
                        VALUES
                        (
                               ".$car_item['id'].", 
                               '".$car_item['url']."', 
                               '".$car_item['name']."',
                               '".$car_item['price']."',
                               '".$car_item['features']['first_registration_date']."',
                               '".$car_item['features']['mileage']."',
                               '".$car_item['features']['gearbox']."',
                               '".$car_item['features']['fuel']."',
                               '".$car_item['features']['engine_size']."',
                               '".$car_item['features']['power']."',
                               '".$car_item['features']['emission_class']."',
                               '".$car_item['features']['co2']."',
                               '".$car_item['features']['car_location']."'
                        )");

    echo "Car #".$car_item['id']." / ".$car_item['name']." - successfully added in DB\r\n";
}

/*
 * Downloading photos;
 * */
echo "Downloading images...\r\n";
foreach($cars_data as $car_item){
    // Photo saving;
    $car_hash = md5($car_item['id']);

    // Getting "blocks";
    $path = __DIR__.'/photos';
    if(!file_exists($path)) mkdir($path);

    for($i = 0 ; $i < 32 / 2 ; $i++){
        // Calc path with block;
        $path .= '/'.substr($car_hash, $i * 2, 2);

        // Check folder for exist;
        if(!file_exists($path)) mkdir($path);
    }

    // Original and Thumbnail folder;
    if(!file_exists($path.'/original')) mkdir($path.'/original');
    if(!file_exists($path.'/thumbnail')) mkdir($path.'/thumbnail');

    // Save photos;
    // Original;
    foreach($car_item['photos']['original'] as $image){
        $filename = basename($image);
        file_put_contents($path.'/original/'.$filename, file_get_contents($image));
        $images_counter--;
    }

    // Thumbnails;
    foreach($car_item['photos']['thumbnail'] as $key => $image){
        $filename = basename($car_item['photos']['original'][$key]);
        file_put_contents($path.'/thumbnail/'.$filename, file_get_contents($image));
        $images_counter--;
    }

    echo "\rImages left: $images_counter";
}

echo "\r\n--- Script successfully finished ---\r\n";

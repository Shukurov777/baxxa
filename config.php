<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$token = defined('BOT_TOKEN') ? BOT_TOKEN : "6966200328:AAGJL3R6JPIJFJ_0WzOrQSuaBudULOe9fPc";



function bot($method,$datas=[]){
global $token;
    $url = "https://api.telegram.org/bot".$token."/".$method;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
    $res = curl_exec($ch);
    if(curl_error($ch)){
        var_dump(curl_error($ch));
    }else{
        return json_decode($res);
    }
}

function sot($method, $datas = []) {
    global $token;
    $url = "https://api.telegram.org/bot".$token."/".$method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res);
}

$directories = ['step', 'data', 'audio', 'lang', 'admin',  'lang/users'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

$admin_file = 'admin/admins.json';

if (file_exists($admin_file)) {
    $admin = json_decode(file_get_contents($admin_file), true);
} else {
    $admin = ["6331347023"];
    file_put_contents($admin_file, json_encode($admin));
}


function iget($url = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}



function getInitalData($html, $nodeIndex = 33) {
    // If our regex found the initialData then return
    if (preg_match('/ytInitialData\s*=\s*({.+?})\s*;/i', $html, $matches)) {
        $json = $matches[1];
        return json_decode($json, true);
    }
    // Else  we will load it in dom and get through index
    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $nodes = $doc->getElementsByTagName('script');
    $Var_value = $nodes[$nodeIndex]->nodeValue;
    $res = rtrim(substr($Var_value, 20, strlen($Var_value)), ";");
    $json = json_decode($res, true);
    if ($json != null) {
        return $json;
    }

    foreach ($nodes as $node) {
        // Get the node value
        $nodeValue = $node->nodeValue;

        // Check if the node value contains ytInitialData
        if (strpos($nodeValue, 'ytInitialData') !== false) {

            $res = rtrim(substr($nodeValue, 20, strlen($nodeValue)), ";");
            $json = json_decode($res, true);
            return $json;
        }
    }

    // Return null if ytInitialData is not found
    return null;
}

function parseSearchResult($json) {
    $video_page_response = $json["contents"]["twoColumnSearchResultsRenderer"]["primaryContents"]["sectionListRenderer"]["contents"];
    $size = 0;
    if (is_array($video_page_response)) {
        $size = sizeof($video_page_response);
    }
    $nextToken = $video_page_response[$size - 1]["continuationItemRenderer"]["continuationEndpoint"]["continuationCommand"]["token"];

    $videosJson = $json["contents"]["twoColumnSearchResultsRenderer"]["primaryContents"]["sectionListRenderer"]["contents"][0]["itemSectionRenderer"]["contents"];
    $videos = [];
    foreach (@$videosJson as $value) {
        if (isset($value["videoRenderer"])) {
            $_video = $value["videoRenderer"];
            $video['id'] = $_video["videoId"];
            $video["url"] = "https://youtube.com/watch?v=" . $_video["videoId"];
            $video['title'] = $_video["title"]["runs"][0]["text"];
            $video['author'] = $_video["longBylineText"]["runs"][0]["text"];
            $video['views'] = $_video["viewCountText"]["simpleText"] ?? 'Не указано'; // Количество просмотров

            // Получаем информацию о длительности
            if (isset($_video["lengthText"])) {
                $video['duration'] = $_video["lengthText"]["simpleText"]; // Длительность в текстовом формате
                // Разберем длительность, например: "12:05" в секунды для сравнения
                list($minutes, $seconds) = explode(':', $video['duration']);
                $totalSeconds = ($minutes * 60) + $seconds;

                // Фильтруем видео по длительности (максимум 15 минут = 900 секунд)
                if ($totalSeconds > 900) {
                    continue; // Пропускаем видео дольше 15 минут
                }
            } else {
                continue; // Пропускаем, если длина не указана
            }

            // Добавляем видео в список
            array_push($videos, $video);
        }
    }

    return json_encode(['result' => $videos], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}





function search($query) {
    $query = urlencode($query);
    $html = iget("https://www.youtube.com/results?search_query=$query");
    $json = getInitalData($html, 33);
    return parseSearchResult($json);
}

function json($meth, $value) {
    # json -> decode, encode.
    if ($meth == "e") {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    if ($meth == "d") {
        return json_decode($value, true);
    }
    if ($meth == "df") {
        return json_decode(file_get_contents($value), true);
    }
}


function open ($value) {
    # file_get_contents -> open
    return file_get_contents($value);
}
function save ($value, $content) {
    # file_put_contents -> save
    file_put_contents($value, $content);
}

function shazam($file, $qid, $cid, $mid){
exec("python3 shazam.py $file 2>&1", $execOutput, $execReturnVar);
if ($execReturnVar !== 0) {
    echo json_encode(["status"=> "false", "message" => " script execution failed"], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
$jsonOutput = json_decode(implode("\n", $execOutput), true);
if ($jsonOutput === null) {
    echo json_encode(["status"=> "false", "message" => "Unknown error processing the audio file"], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
$jsonOutput[qid] = $qid;
$jsonOutput[cid] = $cid;
$jsonOutput[mid] = $mid;
return json_encode($jsonOutput, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exec("pip install shazamio");
}



function InstaSave($url, $id){
    @mkdir("music", 0777, true);
    $videoFile = "music/$id.mp4";
    $metaFile  = "music/$id.info.json";

    // Формируем команду
    $commandParts = [
        'yt-dlp',
        // Формат: лучшее видео + лучшее аудио или просто лучшее доступное
        '-f', 'bestvideo+bestaudio/best',
        // Объединять в MP4
        '--merge-output-format', 'mp4',
        // Параллельная закачка фрагментов
        '--concurrent-fragments', '5',
        // (Опционально) можно добавить retries
        // '-R', '10',
        // '--fragment-retries', '10',

        // Записать info.json
        '--write-info-json',

        // Куда сохранять
        '-o', escapeshellarg($videoFile),

        // URL
        escapeshellarg($url)
    ];

    // Склеиваем всё в одну строку
    $command = implode(' ', $commandParts);

    // Выполняем команду
    // Для отладки можно добавить вывод $output
    exec($command . ' 2>&1', $output, $returnCode);

    // Проверяем успешность
    if ($returnCode !== 0) {
        // Здесь можно логировать или выводить ошибку
        // var_dump($output); // для отладки
    }

    // Пытаемся прочитать meta
    $description = "unknown";
    if (file_exists($metaFile)) {
        $metadata = json_decode(file_get_contents($metaFile), true);
        if (isset($metadata['description'])) {
            // Экранируем HTML-сущности
            $description = htmlspecialchars($metadata['description'], ENT_QUOTES, 'UTF-8');
        }
    }

    return [
        'file' => $videoFile,
        'description' => $description
    ];
}

function TikTokSave($url, $id) {
    @mkdir("music", 0777, true);
    $filePath = "music/$id.mp4";
    $metaFile = "music/$id.info.json";

    // Аналогичная логика
    $commandParts = [
        'yt-dlp',
        '-f', 'bestvideo+bestaudio/best',
        '--merge-output-format', 'mp4',
        '--concurrent-fragments', '5',
        '--write-info-json',
        '-o', escapeshellarg($filePath),
        escapeshellarg($url)
    ];

    $command = implode(' ', $commandParts);
    exec($command . ' 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        // Обработка ошибок
    }

    $description = 'unknown';
    if (file_exists($metaFile)) {
        $metadata = json_decode(file_get_contents($metaFile), true);
        if (isset($metadata['description'])) {
            $description = $metadata['description'];
        }
    }
    return [
        'file' => $filePath,
        'description' => $description
    ];
}

function PinterestSave($url, $id) {
    @mkdir("music", 0777, true);
    $filePath = "music/$id.mp4";
    $metaFile = "music/$id.info.json";

    $commandParts = [
        'yt-dlp',
        '-f', 'bestvideo+bestaudio/best',
        '--merge-output-format', 'mp4',
        '--concurrent-fragments', '5',
        '--write-info-json',
        '-o', escapeshellarg($filePath),
        escapeshellarg($url)
    ];

    $command = implode(' ', $commandParts);
    exec($command . ' 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        // Обработка ошибок
    }

    $description = 'unknown';
    if (file_exists($metaFile)) {
        $metadata = json_decode(file_get_contents($metaFile), true);
        if (isset($metadata['description'])) {
            $description = $metadata['description'];
        }
    }

    return [
        'file' => $filePath,
        'description' => $description
    ];
}


function Duration($url){
	$js = exec("yt-dlp -j $url");
    $js = json_decode($js);
	$ret = $js->duration;
	return $ret;
	}

function YouTube($url, $id){
	@mkdir("music");
    $command .= "yt-dlp ";
    $command .= "-o 'music/$id.mp3' ";
    // $command .= "--cookies cookie.txt ";
    $command .= "-f 'bestaudio' ";
    $command .= "-x --audio-format mp3 ";
    $command .= $url;
    exec($command .  " 2>&1");
    rename("music/$id.temp.mp3", "music/$id.mp3");   
    $file = "music/$id.mp3";
    return $file;
}

function delmusic($id){
    @unlink("music/$id.mp3");
    @unlink("music/$id.jpg");
}

require_once("bot.php");




?>
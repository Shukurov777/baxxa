<?php
date_default_timezone_set('Europe/Irkutsk');

$host = "localhost";
$user = "new_mp3_usr";
$password = "12345678";
$dbname = "new_mp3";

$conn = new mysqli($host, $user, $password);
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);
$conn->set_charset("utf8mb4");

$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        user_id BIGINT PRIMARY KEY,
        lang VARCHAR(10),
        is_premium BOOLEAN DEFAULT 0,
        date DATE,
        INDEX (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$conn->query("
    CREATE TABLE IF NOT EXISTS stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        stat_key VARCHAR(50),
        stat_date DATE,
        value INT DEFAULT 0,
        UNIQUE KEY(stat_key, stat_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

function addNewUser($user_id, $lang, $is_premium) {
    global $conn;
    $user_id = (int)$user_id;
    $lang = $conn->real_escape_string($lang);
    $is_premium = (int)$is_premium;
    $date = date("Y-m-d");
    $q = $conn->prepare("SELECT user_id FROM users WHERE user_id=?");
    $q->bind_param("i", $user_id);
    $q->execute();
    $r = $q->get_result();
    if($r->num_rows == 0) {
        $q = $conn->prepare("INSERT INTO users (user_id, lang, is_premium, date) VALUES (?,?,?,?)");
        $q->bind_param("isis", $user_id, $lang, $is_premium, $date);
        $q->execute();
        return true;
    }
    return false;
}

function incrementStat($key) {
    global $conn;
    $date = date("Y-m-d");
    $q = $conn->prepare("SELECT value FROM stats WHERE stat_key=? AND stat_date=?");
    $q->bind_param("ss", $key, $date);
    $q->execute();
    $r = $q->get_result();
    if($r->num_rows == 0) {
        $q = $conn->prepare("INSERT INTO stats (stat_key, stat_date, value) VALUES (?,?,1)");
        $q->bind_param("ss", $key, $date);
        $q->execute();
    } else {
        $row = $r->fetch_assoc();
        $val = $row['value'] + 1;
        $q = $conn->prepare("UPDATE stats SET value=? WHERE stat_key=? AND stat_date=?");
        $q->bind_param("iss", $val, $key, $date);
        $q->execute();
    }
}

function getDailyStat($key, $date) {
    global $conn;
    $q = $conn->prepare("SELECT value FROM stats WHERE stat_key=? AND stat_date=?");
    $q->bind_param("ss", $key, $date);
    $q->execute();
    $r = $q->get_result();
    if($r->num_rows==0) return 0;
    $row = $r->fetch_assoc();
    return (int)$row['value'];
}

function getTotalStat($key) {
    global $conn;
    $q = $conn->prepare("SELECT SUM(value) as total FROM stats WHERE stat_key=?");
    $q->bind_param("s", $key);
    $q->execute();
    $r = $q->get_result();
    $row = $r->fetch_assoc();
    return $row['total'] ? (int)$row['total'] : 0;
}

function getTotalUsers() {
    global $conn;
    $res = $conn->query("SELECT COUNT(*) as c FROM users");
    return (int)$res->fetch_assoc()['c'];
}

function users() { incrementStat('users'); }
function music_success() { incrementStat('music_success'); }
function music_error() { incrementStat('music_error'); }
function youtube_save() { incrementStat('youtube_save'); }
function youtube_errors() { incrementStat('youtube_errors'); }

function sendStat($cid) {
    $today = date("Y-m-d");
    $yesterday = date("Y-m-d", time()-86400);
    $day_before_yesterday = date("Y-m-d", time()-172800);

    $today_users = getDailyStat('users', $today);
    $today_music = getDailyStat('music_success', $today);
    $today_youtube = getDailyStat('youtube_save', $today);
    $today_music_error = getDailyStat('music_error', $today);
    $today_youtube_error = getDailyStat('youtube_errors', $today);

    $yesterday_users = getDailyStat('users', $yesterday);
    $yesterday_music = getDailyStat('music_success', $yesterday);
    $yesterday_youtube = getDailyStat('youtube_save', $yesterday);
    $yesterday_music_error = getDailyStat('music_error', $yesterday);
    $yesterday_youtube_error = getDailyStat('youtube_errors', $yesterday);

    $db_yesterday_users = getDailyStat('users', $day_before_yesterday);
    $db_yesterday_music = getDailyStat('music_success', $day_before_yesterday);
    $db_yesterday_youtube = getDailyStat('youtube_save', $day_before_yesterday);
    $db_yesterday_music_error = getDailyStat('music_error', $day_before_yesterday);
    $db_yesterday_youtube_error = getDailyStat('youtube_errors', $day_before_yesterday);

    $total_users = getTotalUsers();
    $total_music = getTotalStat('music_success');
    $total_youtube = getTotalStat('youtube_save');
    $total_music_error = getTotalStat('music_error');
    $total_youtube_error = getTotalStat('youtube_errors');

    $datetime = date("Y-m-d | H:i:s");

    $message = " ├ — Статистика — — \n";
    $message .= "├ ----- Сегодня:\n";
    $message .= "├   Пользователи: $today_users\n";
    $message .= "├   Музыки: $today_music\n";
    $message .= "├   Youtube: $today_youtube\n";
    $message .= "├   Ошибки музыки: $today_music_error\n";
    $message .= "├   Ошибки Youtube: $today_youtube_error\n";
    $message .= "├\n";
    $message .= "├ ----- Вчера:\n";
    $message .= "├   Пользователи: $yesterday_users\n";
    $message .= "├   Музыки: $yesterday_music\n";
    $message .= "├   Youtube: $yesterday_youtube\n";
    $message .= "├   Ошибки музыки: $yesterday_music_error\n";
    $message .= "├   Ошибки Youtube: $yesterday_youtube_error\n";
    $message .= "├\n";
    $message .= "├ ----- Позавчера:\n";
    $message .= "├   Пользователи: $db_yesterday_users\n";
    $message .= "├   Музыки: $db_yesterday_music\n";
    $message .= "├   Youtube: $db_yesterday_youtube\n";
    $message .= "├   Ошибки музыки: $db_yesterday_music_error\n";
    $message .= "├   Ошибки Youtube: $db_yesterday_youtube_error\n";
    $message .= "├\n";
    $message .= "├ ----- Общее:\n";
    $message .= "├   Пользователи: $total_users\n";
    $message .= "├   Музыки: $total_music\n";
    $message .= "├   Youtube: $total_youtube\n";
    $message .= "├   Ошибки музыки: $total_music_error\n";
    $message .= "├   Ошибки Youtube: $total_youtube_error\n";
    $message .= "├ \n";
    $message .= "└ ----- <i>$datetime</i>";

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $message,
        'parse_mode' => "html"
    ]);
}

if($message){
    $user_id = $message->from->id;
    $lang = $message->from->language_code ?? "ru";
    $is_premium = $message->from->is_premium ? 1 : 0;
    if(addNewUser($user_id, $lang, $is_premium)) {
        users();
    }
}


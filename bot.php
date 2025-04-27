<?php

require_once("config.php");
$toSend = "-1002591128133";
$id_chan = "-1002261177937";
$update = json_decode(file_get_contents('php://input'));
file_put_contents('update.log', print_r($update, true));

if (is_object($update)) {
    $message = $update->message ?? null;
    $callback = $update->callback_query ?? null;
    $callback_query = $update->callback_query ?? null;
    if ($message) {
        $lans = $message->from->language_code ?? "ru";
        $fid = $message->from->id;
        $name_link = "<a href=\"tg://user?id={$fid}\">" . htmlspecialchars($message->from->first_name) . "</a>";
        $username = $message->from->username;
        $cid = $message->chat->id;
        $mmid = $message->message_id;
        $mid = $message->message_id;
        $uid = $message->from->id;
        $text = $message->text;
        $sticker = $message->sticker;
        $reply = $message->reply_to_message;
    } elseif ($callback) {
        $lans = $callback->from->language_code ?? "ru";
        $fid = $callback->from->id;
        $name_link = "<a href=\"tg://user?id={$fid}\">" . htmlspecialchars($callback->from->first_name) . "</a>";
        $username = $callback->from->username;
        $mmid = $callback->message_id;
        $mid = $callback->message->message_id;
        $cid = $callback->message->chat->id;
        $data = $callback->data ?? null;
        $qid = $callback->id;
        $uid = $callback->from->id;
    } else {
        file_put_contents('error.log', "Invalid message or callback received\n", FILE_APPEND);
    }
} else {
    file_put_contents('error.log', "Invalid update received\n", FILE_APPEND);
}

if (isset($update->inline_query)) {
    $iqid = $update->inline_query->id;
    $iquery = $update->inline_query->query;
}

@$m_mid = $message->message_id;
@$chatid = $message->chat->id;
@$userid = $message->from->id;
@$cmid = $update->callback_query->message->message_id;
@$ccid = $update->callback_query->message->chat->id;
@$bmid = $update->callback_query->message->message_id ?? $update->message->message_id;

if (isset($iquery)) {
    $inlineQueryId = $iqid;
    $videos = json_decode(search($iquery), true)["result"];
    $results = [];
    foreach ($videos as $video) {
        $sizeInMb = $video['size'] ?? 'Не указано';
        $views = $video['views'] ?? 'Не указано';
        $duration = $video['duration'] ?? 'Не указано';
        $results[] = [
            'type' => 'article',
            'id' => $video['id'],
            'title' => $video['title'],
            'input_message_content' => [
                'message_text' => $video['url'],
            ],
            'description' => "👤 {$video['author']} | 👁 {$views} | 📹 {$duration}",
            'thumb_url' => "https://img.youtube.com/vi/{$video['id']}/hqdefault.jpg",
        ];
    }
    $data = [
        'inline_query_id' => $inlineQueryId,
        'results' => json_encode($results),
    ];
    bot('answerInlineQuery', $data);
}

require_once("panel.php");
$lang = getUserLang($cid);
$messages = file_exists("lang/$lang.php") ? require_once("lang/$lang.php") : require_once("lang/en.php");
$msg = $messages;

function getUserLang($uid) {
    $file = "lang/users/$uid.txt";
    if (file_exists($file)) {
        $lang = trim(file_get_contents($file));
        // Check if language file exists, return 'en' if not
        return file_exists("lang/$lang.php") ? $lang : 'en';
    }
    return 'en'; // Default to English if no user language file exists
}
function saveUserLang($uid, $lang) {
    file_put_contents("lang/users/$uid.txt", $lang);
}

if ($text == "/lang") {
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['choose_lang'],
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "Русский 🇷🇺", 'callback_data' => 'lang_ru'],
                    ['text' => "English 🇺🇸", 'callback_data' => 'lang_en']
                ],
                [
                    ['text' => "Türkçe 🇹🇷", 'callback_data' => 'lang_tr'],
                    ['text' => "Oʻzbek 🇺🇿", 'callback_data' => 'lang_uz']
                ],
                [
                    ['text' => "فارسی 🇮🇷", 'callback_data' => 'lang_fa'],
                    ['text' => "Українська 🇺🇦", 'callback_data' => 'lang_uk']
                ],
                [
                    ['text' => "Тоҷики 🇹🇯", 'callback_data' => 'lang_tj'],
                    ['text' => "عربي 🇸🇦", 'callback_data' => 'lang_ar']
                ]
            ]
        ])
    ]);
}

if ($data == "lang") {
    bot('editMessageText', [
        'chat_id' => $cid,
        'message_id' => $mid,
        'text' => $msg['choose_lang'],
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "Русский 🇷🇺", 'callback_data' => 'lang_ru'],
                    ['text' => "English 🇺🇸", 'callback_data' => 'lang_en']
                ],
                [
                    ['text' => "Türkçe 🇹🇷", 'callback_data' => 'lang_tr'],
                    ['text' => "Oʻzbek 🇺🇿", 'callback_data' => 'lang_uz']
                ],
                [
                    ['text' => "فارسی 🇮🇷", 'callback_data' => 'lang_fa'],
                    ['text' => "Українська 🇺🇦", 'callback_data' => 'lang_uk']
                ],
                [
                    ['text' => "Тоҷики 🇹🇯", 'callback_data' => 'lang_tj'],
                    ['text' => "عربي 🇸🇦", 'callback_data' => 'lang_ar']
                ],
                [
                    ['text' => $msg['btn_back'], 'callback_data' => 'back_to']
                ]
            ]
        ])
    ]);
}

if (strpos($data, 'lang_') === 0) {
    $selectedLang = str_replace('lang_', '', $data);
    saveUserLang($cid, $selectedLang);
    $messages = require("lang/$selectedLang.php");
    bot('editMessageText', [
        'chat_id' => $cid,
        'message_id' => $mid,
        'text' => "✅",
        'parse_mode' => "HTML"
    ]);
    bot('answerCallbackQuery', [
        'callback_query_id' => $callback_query->id,
        'text' => isset($messages['language_set']) ? $messages['language_set'] : "Язык установлен: " . strtoupper($selectedLang),
        'show_alert' => false
    ]);
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => isset($messages['start']) ? $messages['start'] : "Привет! Ваш язык установлен на " . strtoupper($selectedLang),
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $messages['tags_set'], 'callback_data' => 'new_o']
                ],
                [
                    ['text' => $messages['btn_lang'], 'callback_data' => 'lang'],
                    ['text' => $messages['btn_your_musics'], 'callback_data' => 'musics']
                ],
                [
                    ['text' => $messages['add_me_group'], 'url' => 'https://t.me/mp3_tool_bot?startgroup=on&admin=delete_messages']
                ],
                [
                    ['text' => $messages['search'], 'switch_inline_query_current_chat' => '']
                ]
            ]
        ])
    ]);
}

$main_key = json_encode([
    'inline_keyboard' => [
        [['text' => $msg['tags_set'], 'callback_data' => 'new_o']],
        [
            ['text' => $msg['btn_lang'], 'callback_data' => 'lang'],
            ['text' => $msg['btn_your_musics'], 'callback_data' => 'musics']
        ],
        [['text' => $msg['add_me_group'], 'url' => 'https://t.me/mp3_tool_bot?startgroup=on&admin=delete_messages']],
        [['text' => $msg['search'], 'switch_inline_query_current_chat' => '']]
    ]
]);

require_once("sql.php");

if ($text === "/1310") {
    sendStat($cid);
}

if ($data == "back_to") {
    bot('editMessageText', [
        'chat_id' => $cid,
        'message_id' => $mid,
        'text' => $msg['start'],
        'parse_mode' => "HTML",
        'reply_markup' => $main_key
    ]);
}

$music_folder = "audio/{$uid}_{$cid}";

$key = json_encode([
    'inline_keyboard' => [
        [['text' => $msg['avtosave'], 'callback_data' => 'auto_changes']],
        [
            ['text' => $msg['edit_tags'], 'callback_data' => 'edit_tag'],
            ['text' => $msg['edit_photo'], 'callback_data' => 'edit_photo']
        ],
        [
            ['text' => $msg['shazam'], 'callback_data' => 'shazam'],
            ['text' => $msg['effects'], 'callback_data' => 'effect']
        ],
        [
            ['text' => $msg['cut'], 'callback_data' => 'cut'],
            ['text' => $msg['save'], 'callback_data' => 'save']
        ],
        [['text' => $msg['convert'], 'callback_data' => 'convert']]
    ]
]);

function react($id, $emoji) {
    global $cid;
    bot('setMessageReaction', [
        'chat_id' => $cid,
        'message_id' => $id,
        'reaction' => json_encode([
            ['type' => 'emoji', 'emoji' => $emoji]
        ]),
    ]);
}

if (mb_stripos($text, 'start') !== false) {
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['start'],
        'parse_mode' => "HTML",
        'reply_markup' => $main_key
    ]);
}

if (preg_match('/(?:https?:\/\/)?(?:www\.)?pin\.it\/.+/i', strtolower($text))) {
     $result = bot('copyMessage', [
        'chat_id' => $cid,
        'from_chat_id' => -1002121535082,
        'message_id' => 11
    ]);
    $data = PinterestSave($text, $fid);
    $response = $data['file'];
    $description = $data['description'] ?? 'unknown';
    @mkdir("music");

    $botResponse = bot('sendVideo', [
        'chat_id' => $cid,
        "video" => new CURLFile($response),
        'caption' => "<code>" . htmlspecialchars($description) . "</code>\n\n<a href='https://t.me/mp3_tool_bot?start=new'>@mp3_tool_bot</a>",
        'reply_to_message_id' => $mid,
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            "inline_keyboard" => [
                [["text" => $msg["open_in_editor"], "callback_data" => "open_id"]]
            ]
        ])
    ]);



    youtube_save();
    unlink($response);
    bot('deletemessage', [
        'chat_id' => $cid,
        'message_id' => $result->result->message_id
    ]);
    exit();
}




function gjj($id)
{
    global $cmid, $messages;

    $language = $messages; 
    $joinButton = $language["subscribe"];
    $joinMessage = $language["join_channels_prompt"];
    $notFollowedMessage = $language["join_channels_prompt"];
    $callbackAlert = $language["join_channels_prompt"];

    $json = json_decode(file_get_contents("data/channel.json"), true);

    if ($json === null || !isset($json["result"])) {
        return false;
    }

    $key = [];
    $stat = false;

    foreach ($json["result"] as $channel) {
        $cid = $channel['channel_id'];
        $title = $channel['title'];
        $link = $channel['link'];

        $result = sot('getChatMember', [
            'chat_id' => $cid,
            'user_id' => $id
        ])->result->status ?? null;

        if ($result !== "member" && $result !== "creator" && $result !== "administrator") {
            $key[] = ["text" => "🔐 " . $title, 'url' => $link];
            $stat = true;
        }
    }

    $keyboard2 = array_chunk($key, 2);
    $keyboard2[] = [['text' => $joinButton, "callback_data" => "save"]];

    if ($stat) {
        if (isset($cmid)) {
            sot('deleteMessage', [
                'chat_id' => $id,
                'message_id' => $cmid,
            ]);
        }

        sot('sendMessage', [
            'chat_id' => $id,
            'text' => $joinMessage,
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard2
            ]),
        ]);

        return false;
    } else {
        return true;
    }
}



if (
    preg_match('/(?:https?:\/\/)?(?:www\.)?tiktok\.com\/.+/i', strtolower($text)) ||
    mb_stripos($text, "instagram.com") !== false
) {
       $result = bot('copyMessage', [
        'chat_id' => $cid,
        'from_chat_id' => -1002121535082,
        'message_id' => 11
    ]);
    $data = PinterestSave($text, $fid);
    $response = $data['file'];
    $description = $data['description'] ?? 'unknown';

    @mkdir("music");

    $botResponse = bot('sendVideo', [
        'chat_id' => $cid,
        "video" => new CURLFile($response),
        'caption' => "<code>" . htmlspecialchars($description) . "</code>\n\n<a href='https://t.me/mp3_tool_bot?start=new'>@mp3_tool_bot</a>",
        'reply_to_message_id' => $mid,
        'parse_mode' => "HTML",
        'reply_markup' => json_encode([
            "inline_keyboard" => [
                [["text" => $msg["open_in_editor"], "callback_data" => "open_id"]],
            ]
        ]),
    ]);

   
    
    youtube_save();

    unlink($response);

    bot('deletemessage', [
        'chat_id' => $cid,
        'message_id' => $result->result->message_id
    ]);

    exit();
}






function getYouTubeInfo($url) {
    $json = shell_exec("yt-dlp -j " . escapeshellarg($url));
    $data = json_decode($json, true);
    if (!$data || empty($data['duration']) || empty($data['title']) || empty($data['thumbnail']) || empty($data['uploader'])) {
        return ['error' => 'Не удалось получить информацию о видео.'];
    }
    return [
        'duration' => $data['duration'],
        'title' => $data['title'],
        'thumbnail' => $data['thumbnail'],
        'uploader' => $data['uploader']
    ];
}

function downloadYouTubeAudio($url, $title, $thumbnail, $uploader, $cid) {
    $filePath = "music/{$cid}.mp3";
    $coverPath = "music/{$cid}.jpg";
    $command = "yt-dlp -o " . escapeshellarg($filePath) . " -f 'bestaudio' -x --audio-format mp3 --audio-quality 0 --embed-metadata --no-playlist " . escapeshellarg($url);
    shell_exec($command . " 2>&1");
    if (!file_exists($filePath) || filesize($filePath) < 10000) {
        unlink($filePath);
        return ['error' => 'Ошибка загрузки аудио.'];
    }
    file_put_contents($coverPath, file_get_contents($thumbnail));
    $ffmpegCommand = "ffmpeg -i " . escapeshellarg($filePath) . " -i " . escapeshellarg($coverPath) . " -map 0:a -map 1:v -c:a copy -c:v mjpeg -metadata title=" . escapeshellarg($title) . " -metadata artist=" . escapeshellarg($uploader) . " -id3v2_version 3 -write_id3v1 1 " . escapeshellarg("music/{$cid}_final.mp3") . " -y";
    shell_exec($ffmpegCommand);
    unlink($filePath);
    rename("music/{$cid}_final.mp3", $filePath);
    return [
        'audio' => file_exists($filePath) ? $filePath : null,
        'cover' => file_exists($coverPath) ? $coverPath : null
    ];
}



function saveYData($fid, $title, $messageId) {
    $filePath = "data/{$fid}_y.json"; // исправлено: было $fid_y.json — так нельзя
    if (file_exists($filePath)) {
        $jsonData = json_decode(file_get_contents($filePath), true);
        if (!is_array($jsonData)) {
            $jsonData = [];
        }
    } else {
        $jsonData = [];
    }
    $jsonData[] = [
        'music_name' => $title,
        'date_time' => date('Y-m-d H:i:s'),
        'message_id' => $messageId
    ];
    file_put_contents($filePath, json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

if (preg_match('/youtu/i', strtolower($text))) {
    $result = bot('copyMessage', [
        'chat_id' => $cid,
        'from_chat_id' => -1002121535082,
        'message_id' => 11
    ]);

    $youtubeUrl = $text;
    $info = getYouTubeInfo($youtubeUrl);

    if (isset($info['error'])) {
        bot('sendMessage', ['chat_id' => $cid, 'text' => "Ошибка: {$info['error']}"]);
        exit;
    }

    if ($info['duration'] > 900) {
        bot('sendMessage', ['chat_id' => $cid, 'text' => "Ошибка: Видео длиннее 15 минут!"]);
        exit;
    }

    $download = downloadYouTubeAudio($youtubeUrl, $info['title'], $info['thumbnail'], $info['uploader'], $cid);
    if (!$download['audio']) {
        bot('sendMessage', ['chat_id' => $cid, 'text' => "Ошибка при загрузке аудио."]);
        exit;
    }

    // Отправка пользователю
    bot('sendAudio', [
        'chat_id' => $cid,
        'audio' => new CURLFile($download['audio']),
        'title' => $info['title'],
        'performer' => $info['uploader'],
        'thumb' => new CURLFile($download['cover']),
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text' => $msg['open_in_editor'] ?? 'Open in Editor',
                        'callback_data' => 'open_id'
                    ]
                ]
            ]
        ])
    ]);

    // Переменные $toSend, $audio_to_send, $performer, $thumb_to_send, $name_link, $username должны быть определены заранее!
    $sendResult = bot('sendAudio', [
        'chat_id' => $toSend,
        'audio' => new CURLFile($download['audio']),
        'title' => $info['title'],
        'performer' => $info['uploader'],
        'thumb' => new CURLFile($download['cover']),
        'caption' => "👉🏻 $name_link | @$username",
        'parse_mode' => "html",
        'performer' => $performer
    ]);

    if (isset($sendResult->result->message_id)) {
        $channelMsgId = $sendResult->result->message_id;
        saveYData($fid, $info['title'], $channelMsgId);
    }

    youtube_save(); // убедись, что такая функция существует
    unlink($download['audio']);
    unlink($download['cover']);

    if (isset($result->result->message_id)) {
        bot('deleteMessage', [
            'chat_id' => $cid,
            'message_id' => $result->result->message_id
        ]);
    }

    exit();
}



$audio = $message->audio ?? $message->video ?? $message->voice;
if (isset($audio) || $data == "open_id") {
    if (isset($data)) {
        $audio = $update->callback_query->message->audio ?? $update->callback_query->message->video;
    }
    if ($audio->file_size > 20 * 1024 * 1024) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['file_too_large'],
            'parse_mode' => "HTML"
        ]);
        exit;
    }
    $result = bot('copyMessage', [
        'chat_id' => $cid,
        'from_chat_id' => -1002121535082,
        'message_id' => 11
    ]);
    if (isset($result->result->message_id)) {
        $messageId = $result->result->message_id;
        $file_id = $audio->file_id;
        $file_info = bot('getFile', ['file_id' => $file_id]);
        if (!$file_info || !isset($file_info->result->file_path)) {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $msg['error_processing_audio'],
                'parse_mode' => "HTML"
            ]);
            exit;
        }
        $file_path = $file_info->result->file_path;
        $file_url = "https://api.telegram.org/file/bot$token/$file_path";
        if (!is_dir($music_folder)) {
            mkdir($music_folder, 0755, true);
        } else {
            array_map('unlink', glob("$music_folder/*"));
        }
        $original_audio_file = "$music_folder/original_audio";
        if (@file_put_contents($original_audio_file, file_get_contents($file_url)) === false) {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $msg['download_failed'],
                'parse_mode' => "HTML"
            ]);
            exit;
        }
        $format = getAudioFormat($original_audio_file);
        $supported_formats = ['mp3','aac','flac','wav','ogg','m4a','opus'];
        if (!in_array($format, $supported_formats)) {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $msg['unsupported_format'],
                'parse_mode' => "HTML"
            ]);
            unlink($original_audio_file);
            exit;
        }
        $audio_file_path = "$music_folder/$uid.mp3";
        if ($format !== 'mp3') {
            $conversion_cmd = "ffmpeg -i \"$original_audio_file\" -codec:a libmp3lame -qscale:a 2 \"$audio_file_path\" -y 2>&1";
            exec($conversion_cmd, $output, $return_var);
            unlink($original_audio_file);
            if ($return_var !== 0 || !file_exists($audio_file_path)) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => $msg['error_processing_audio'],
                    'parse_mode' => "HTML"
                ]);
                exit;
            }
        } else {
            rename($original_audio_file, $audio_file_path);
        }
        $cover_image_path = "$music_folder/image.jpg";
        $extract_cover_cmd = "ffmpeg -i \"$audio_file_path\" -an -vcodec copy \"$cover_image_path\" -y 2>&1";
        exec($extract_cover_cmd, $cover_output, $cover_return_var);
        if ($cover_return_var !== 0 || !file_exists($cover_image_path) || filesize($cover_image_path) == 0) {
            copy("data/image.jpg", $cover_image_path);
        }
        react($mid, "👌");
        bot('deleteMessage', [
            'chat_id' => $cid,
            'message_id' => $messageId
        ]);
        $performer = isset($audio->performer) ? $audio->performer : $msg['unknown'];
        $title = isset($audio->title) ? $audio->title : $msg['unknown'];
        $duration = isset($audio->duration) ? gmdate("H:i:s", $audio->duration) : $msg['unknown'];
        $file_size = round($audio->file_size / (1024 * 1024), 2) . " MB";
        $dataLocal = [
            'title' => $title,
            'performer' => $performer,
            'duration' => $duration,
            'file_size' => $file_size,
            'effect'=> ["use"=> "no", "volume"=> 0]
        ];
        file_put_contents("$music_folder/data.json", json_encode($dataLocal));
        $caption =
            "<b>" . $msg['music_name'] . "</b>: <code>" . htmlspecialchars($dataLocal['title']) . "</code>\n" .
            "<b>" . $msg['artist_name'] . "</b>: <code>" . htmlspecialchars($dataLocal['performer']) . "</code>\n" .
            "<b>" . $msg['file_size'] . "</b>: <code>" . $dataLocal['file_size'] . "</code>\n" .
            "<b>" . $msg['duration'] . "</b>: <code>" . $dataLocal['duration'] . "</code>\n" .
            "<b>" . $msg['effects'] . "</b>: " . $msg["no_effect"];
        $photo_to_send = new CURLFile(realpath($cover_image_path));
        $result = bot('sendPhoto', [
            'chat_id' => $cid,
            'photo' => $photo_to_send,
            'caption' => $caption,
            'reply_markup' => $key,
            'parse_mode' => "HTML"
        ]);
        if (isset($result->result->message_id)) {
            $photo_message_id = $result->result->message_id;
            file_put_contents("$music_folder/photo_message_id.txt", $photo_message_id);
        } else {
            $result = bot('sendPhoto', [
                'chat_id' => $cid,
                'photo' => "https://upload.wikimedia.org/wikipedia/commons/1/14/No_Image_Available.jpg",
                'caption' => $caption,
                'reply_markup' => $key,
                'parse_mode' => "HTML"
            ]);
            if (isset($result->result->message_id)) {
                $photo_message_id = $result->result->message_id;
                file_put_contents("$music_folder/photo_message_id.txt", $photo_message_id);
            }
            copy("https://upload.wikimedia.org/wikipedia/commons/1/14/No_Image_Available.jpg", "data/default_cover.jpg");
        }
    }
}

if (mb_stripos($text, '/search') !== false) {
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['search_prompt'],
        'parse_mode' => "HTML",
        'reply_to_message_id' => $mid,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => $msg['search'], 'switch_inline_query_current_chat' => '']]
            ]
        ])
    ]);
}

if($data == "effect"){
    bot('answercallbackquery', [
        'callback_query_id' => $update->callback_query->id,
        'text' => $msg["please_wait"]
    ]);
    $key2 = json_encode([
        "inline_keyboard"=> [
            [
                ["text"=> "🚫", "callback_data"=> "cancel_effect"],
                ["text"=> "50", "callback_data"=> "fff|$mid|50"],
                ["text"=> "100", "callback_data"=> "fff|$mid|100"],
                ["text"=> "150", "callback_data"=> "fff|$mid|150"],
                ["text"=> "200", "callback_data"=> "fff|$mid|200"],
                ["text"=> "300", "callback_data"=> "fff|$mid|300"],
                ["text"=> "350", "callback_data"=> "fff|$mid|350"]
            ]
        ]
    ]);
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['effect_volume'],
        'parse_mode' => "HTML",
        'reply_markup'=> $key2
    ]);
}

if($data == "cancel_effect"){
    bot('answercallbackquery', [
        'callback_query_id' => $update->callback_query->id,
        'text' => $msg["please_wait"]
    ]);
    bot('deleteMessage',[
        'chat_id'=> $cid,
        'message_id' => $mid
    ]);
}

if(strpos($update->callback_query->data, "fff|")!==false){
    $ex = explode("|", $update->callback_query->data);
    $msgIdLocal = $ex[1];
    $lvl = $ex[2];
    $dataLocal = json_decode(file_get_contents("$music_folder/data.json"), true);
    $dataLocal['effect']['use'] = "yes";
    $dataLocal['effect']['volume'] = $lvl;
    file_put_contents("$music_folder/data.json", json_encode($dataLocal));
    $volText = "<b>" . $msg['effects'] . "</b>: " . $msg["no_effect"];
    if ($dataLocal["effect"]["use"] == "yes") {
        $volText = "<b>" . $msg['effects'] . "</b>: " . $msg["effect"] . " - " . $lvl;
    }
    $caption =
        "<b>" . $msg['music_name'] . "</b>: <code>" . htmlspecialchars($dataLocal['title']) . "</code>\n" .
        "<b>" . $msg['artist_name'] . "</b>: <code>" . htmlspecialchars($dataLocal['performer']) . "</code>\n" .
        "<b>" . $msg['file_size'] . "</b>: <code>" . $dataLocal['file_size'] . "</code>\n" .
        "<b>" . $msg['duration'] . "</b>: <code>" . $dataLocal['duration'] . "</code>\n" .
        $volText;
    bot('editMessageCaption',[
        'chat_id' => $cid,
        'message_id'=> $msgIdLocal,
        'caption' => $caption,
        'reply_markup'=> $key,
        'parse_mode'=> "HTML"
    ]);
    bot('deleteMessage',[
        'chat_id'=> $cid,
        'message_id'=> $mid
    ]);
}

if ($data == "shazam") {
    bot('answercallbackquery', [
        'callback_query_id' => $update->callback_query->id,
        'text' => $msg["please_wait"]
    ]);
    $shazam = shazam("$music_folder/$uid.mp3", $update->callback_query->id, $cid, $mid);
    file_put_contents("shazam.txt", $shazam);
    $shazam = json_decode($shazam, true);
    if (isset($shazam["track"]["subtitle"])) {
        bot('answercallbackquery', [
            'callback_query_id' => $shazam["qid"],
            'text' => $shazam["track"]["subtitle"] . " " . $shazam["track"]["title"]
        ]);
        $dataLocal = json_decode(file_get_contents("$music_folder/data.json"), true);
        $volText = "<b>" . $msg['effects'] . "</b>: " . $msg["no_effect"];
        if ($dataLocal["effect"]["use"] == "yes"){
            $lvl = $dataLocal["effect"]["volume"];
            $volText = "<b>" . $msg['effects'] . "</b>: " . $msg["effect"] . " - ". $lvl;
        }
        $caption =
            "<b>" . $msg['music_name'] . "</b>: <code>" . htmlspecialchars($shazam["track"]["subtitle"]) . "</code>\n" .
            "<b>" . $msg['artist_name'] . "</b>: <code>" . htmlspecialchars($shazam['track']['title']) . "</code>\n" .
            "<b>" . $msg['file_size'] . "</b>: <code>" . $dataLocal['file_size'] . "</code>\n" .
            "<b>" . $msg['duration'] . "</b>: <code>" . $dataLocal['duration'] . "</code>\n" .
            $volText;
        bot('editMessageMedia', [
            'chat_id' => $shazam["cid"],
            'message_id' => $shazam["mid"],
            'media' => json_encode([
                'type' => 'photo',
                'media' => $shazam["track"]["images"]["coverart"],
                'caption' => $caption,
                'parse_mode' => "HTML"
            ]),
            'reply_markup' => $key
        ]);
        $dataLocal['title'] = $shazam["track"]["subtitle"];
        $dataLocal['performer'] = $shazam["track"]["title"];
        copy($shazam["track"]["images"]["coverart"], "$music_folder/image.jpg");
        file_put_contents("$music_folder/data.json", json_encode($dataLocal));
    } else {
        bot('answercallbackquery', [
            'callback_query_id' => $update->callback_query->id,
            'text' => $msg["tags_not_found"]
        ]);
    }
}

function getAudioFormat($file) {
    $ffprobe_cmd = "ffprobe -v error -select_streams a:0 -show_entries stream=codec_name -of default=noprint_wrappers=1:nokey=1 \"$file\"";
    exec($ffprobe_cmd, $ffprobe_output, $ffprobe_return);
    if ($ffprobe_return !== 0 || empty($ffprobe_output)) {
        return false;
    }
    return trim($ffprobe_output[0]);
}

function getAudioDuration($file) {
    $ffprobe_cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 \"$file\"";
    exec($ffprobe_cmd, $output, $return_var);
    if ($return_var !== 0 || empty($output)) {
        return false;
    }
    return intval(floatval($output[0]));
}

$step = @file_get_contents("step/{$uid}_{$cid}.txt");
@$sid = @file_get_contents("$music_folder/sid.txt");

if (isset($data)) {
    if ($data == "edit_tag") {
        if (!file_exists("$music_folder/$uid.mp3")) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => $msg['no_music'],
                'show_alert' => true
            ]);
            exit;
        }
        file_put_contents("step/{$uid}_{$cid}.txt", "edit_title");
        $mes_id = bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['send_new_tags'],
            'parse_mode' => "HTML"
        ])->result->message_id;
        file_put_contents("$music_folder/sid.txt", $mes_id);
        file_put_contents("$music_folder/mid.txt", $callback_query->message->message_id);
    } elseif ($data == "edit_photo") {
        if (!file_exists("$music_folder/$uid.mp3")) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => $msg['no_music'],
                'show_alert' => true
            ]);
            exit;
        }
        file_put_contents("step/{$uid}_{$cid}.txt", "edit_photo");
        $mes_id = bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['send_new_photo'],
            'parse_mode' => "HTML"
        ])->result->message_id;
        file_put_contents("$music_folder/sid.txt", $mes_id);
        file_put_contents("$music_folder/mid.txt", $callback_query->message->message_id);
    } elseif ($data == "cut") {
        if (!file_exists("$music_folder/$uid.mp3")) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => $msg['no_audio_to_trim'],
                'show_alert' => true
            ]);
            exit;
        }
        file_put_contents("step/{$uid}_{$cid}.txt", "cut_audio");
        $mes_id = bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['enter_time_interval'],
            'parse_mode' => "HTML"
        ])->result->message_id;
        file_put_contents("$music_folder/sid.txt", $mes_id);
        file_put_contents("$music_folder/mid.txt", $callback_query->message->message_id);
    } elseif ($data == "convert") {
        if (!file_exists("$music_folder/$uid.mp3")) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => $msg['no_audio_to_convert'],
                'show_alert' => true
            ]);
            exit;
        }
        $audio_file_path = "$music_folder/$uid.mp3";
        $duration = getAudioDuration($audio_file_path);
        if ($duration === false) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => $msg['no_audio_duration'],
                'show_alert' => true
            ]);
            exit;
        }
        if ($duration > 240) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => $msg['audio_too_long'],
                'show_alert' => true
            ]);
            exit;
        }
        $messageId = bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['processing'],
            'parse_mode' => "HTML"
        ])->result->message_id;
        $voice_file_path = "$music_folder/voice.ogg";
        $ffmpeg_cmd = "ffmpeg -i \"$audio_file_path\" -c:a libopus -b:a 64k -vbr on -compression_level 10 \"$voice_file_path\" -y 2>&1";
        exec($ffmpeg_cmd, $output, $return_var);
        bot('deleteMessage', [
            'chat_id' => $cid,
            'message_id' => $messageId
        ]);
        if ($return_var !== 0 || !file_exists($voice_file_path)) {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $msg['error_converting_to_voice'],
                'parse_mode' => "HTML"
            ]);
            exit;
        }
        $voice_to_send = new CURLFile(realpath($voice_file_path));
        bot('sendVoice', [
            'chat_id' => $cid,
            'voice' => $voice_to_send,
            'parse_mode' => "HTML"
        ]);
        exit;
    } elseif ($data == "save") {
        if (!file_exists("$music_folder/$uid.mp3")) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => $msg['no_audio_to_save'],
                'show_alert' => true
            ]);
            exit;
        }
        $temp_audio_file = "$music_folder/temp_$uid.mp3";
        $audio_file_path = "$music_folder/$uid.mp3";
        $cover_image_path = "$music_folder/image.jpg";
        $data_file = "$music_folder/data.json";
        if (file_exists($data_file)) {
            $dataLocal = json_decode(file_get_contents($data_file), true);
        } else {
            $dataLocal = [];
        }
        $title = $dataLocal['title'] ?? $msg['unknown'];
        $performer = $dataLocal['performer'] ?? '';
        $album = $dataLocal['album'] ?? '';
        if ($dataLocal["effect"]["use"] == "yes"){
            $level = $dataLocal["effect"]["volume"];
            $hzMapping = [
                "50"  => "0.1",
                "100" => "0.3",
                "150" => "0.5",
                "200" => "0.7",
                "300" => "1.0",
                "350" => "1.5"
            ];
            if (array_key_exists($level, $hzMapping)) {
                $hz = $hzMapping[$level];
                $inputFile = $audio_file_path;
                $outputFile = $temp_audio_file;
                $command = "ffmpeg -i $inputFile -af 'apulsator=hz=$hz, aecho=0.8:0.88:60:0.4' $outputFile";
                exec($command, $output, $return_var);
                rename($temp_audio_file, $audio_file_path);
            }
        }
        if (!file_exists($cover_image_path)) {
            $cover_image_path = "data/default_cover.jpg";
        }
        $safe_title = addslashes($title);
        $safe_performer = addslashes($performer);
        $safe_album = addslashes($album);
        $ffmpeg_cmd = "ffmpeg -i \"$audio_file_path\" -i \"$cover_image_path\" -map 0:a -map 1 -c:a copy -c:v copy -id3v2_version 3 -metadata title=\"$safe_title\" -metadata artist=\"$safe_performer\" -metadata album=\"$safe_album\" -metadata:s:v comment=\"Cover (front)\" \"$temp_audio_file\" -y 2>&1";
        exec($ffmpeg_cmd, $output, $return_var);
        if ($return_var !== 0 || !file_exists($temp_audio_file)) {
            file_put_contents("$music_folder/ffmpeg_error_log.txt", implode("\n", $output));
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $msg['error_saving_audio'],
                'parse_mode' => "HTML"
            ]);
            exit;
        }
        rename($temp_audio_file, $audio_file_path);
        $thumbnail_path = "$music_folder/thumbnail.jpg";
        $ffmpeg_thumbnail_cmd = "ffmpeg -i \"$cover_image_path\" -vf \"scale=90:90\" \"$thumbnail_path\" -y 2>&1";
        exec($ffmpeg_thumbnail_cmd, $thumb_output, $thumb_return_var);
        if ($thumb_return_var !== 0 || !file_exists($thumbnail_path)) {
            $thumbnail_path = $cover_image_path;
        }
        $audio_to_send = new CURLFile(realpath($audio_file_path));
        $thumb_to_send = new CURLFile(realpath($thumbnail_path));

        bot('deleteMessage', [
            'chat_id' => $cid,
            'message_id' => $callback_query->message->message_id
        ]);



        bot('sendAudio', [
            'chat_id' => $cid,
            'audio' => $audio_to_send,
            'title' => $title,
            'performer' => $performer,
            'thumb' => $thumb_to_send
        ]);

        music_success();
        // sendAdd($cid, $msg);
        $sendResult = bot('sendAudio', [
            'chat_id' => $toSend,
            'audio' => $audio_to_send,
            'title' => $title,
            'caption' => "👉🏻 $name_link | @$username",
            'parse_mode' => "html",
            'performer' => $performer,
            'thumb' => $thumb_to_send
        ]);
        

        if (isset($sendResult->result->message_id)) {
            $channelMsgId = $sendResult->result->message_id;
            saveDatas($fid, $title, $channelMsgId);
        }
        $files_to_delete = [
            $audio_file_path,
            $cover_image_path,
            $data_file,
            $thumbnail_path,
            "$music_folder/photo.jpg",
            "$music_folder/cover.jpg",
            "$music_folder/photo_message_id.txt",
            "$music_folder/sid.txt",
            "$music_folder/mid.txt",
            "step/{$uid}_{$cid}.txt"
        ];
        foreach ($files_to_delete as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        if (is_dir($music_folder)) {
            $files = scandir($music_folder);
            if (count($files) == 2) {
                rmdir($music_folder);
            }
        }
        exit;
    }
}

function saveDatas($fid, $title, $messageId) {
    $filePath = "data/$fid.json";
    if (file_exists($filePath)) {
        $jsonData = json_decode(file_get_contents($filePath), true);
        if (!is_array($jsonData)) {
            $jsonData = [];
        }
    } else {
        $jsonData = [];
    }
    $jsonData[] = [
        'music_name' => $title,
        'date_time' => date('Y-m-d H:i:s'),
        'message_id' => $messageId
    ];
    file_put_contents($filePath, json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}


function sendPostToChat($chatId) {
    $url = "https://api.gramads.net/ad/SendPost";
    $data = array('SendToChatId' => $chatId);
    $payload = json_encode($data);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'Authorization: bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIzOTYiLCJqdGkiOiI4ZjdlZDFjZC1hNGI3LTQyMDAtODAyMC04ZGVjOWJiYjJkODgiLCJuYW1lIjoiTXVzaWMgZWRpdG9yIC0g0JzRg9C30YvQutCwINGA0LXQtNCw0LrRgtC-0YAg8J-OtiIsImJvdGlkIjoiMTE2MTMiLCJodHRwOi8vc2NoZW1hcy54bWxzb2FwLm9yZy93cy8yMDA1LzA1L2lkZW50aXR5L2NsYWltcy9uYW1laWRlbnRpZmllciI6IjM5NiIsIm5iZiI6MTc0NTI1ODAxMSwiZXhwIjoxNzQ1NDY2ODExLCJpc3MiOiJTdHVnbm92IiwiYXVkIjoiVXNlcnMifQ.rzQbpQp9_lWwBlVhB1pjoOqCc3J7LPH6VRsaRfTPcvk'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status_code != 200) {
        return;
    }
    echo "Gramads: " . $result;
}

if ($step == "edit_title" && isset($text)) {
    $data_file = "$music_folder/data.json";
    if ($message->message_id) {
        bot('deleteMessage', [
            'chat_id' => $message->chat->id,
            'message_id' => $message->message_id
        ]);
    }
    $sid = @file_get_contents("$music_folder/sid.txt");
    if ($sid) {
        bot('deleteMessage', [
            'chat_id' => $cid,
            'message_id' => $sid
        ]);
    }
    if (file_exists($data_file)) {
        $dataLocal = json_decode(file_get_contents($data_file), true);
    } else {
        $dataLocal = [];
    }
    $lines = explode("\n", $text);
    if (isset($lines[0]) && trim($lines[0]) != "") {
        $new_title = trim($lines[0]);
        $dataLocal['title'] = $new_title;
    }
    if (isset($lines[1]) && trim($lines[1]) != "") {
        $new_performer = trim($lines[1]);
        $dataLocal['performer'] = $new_performer;
    }
    file_put_contents($data_file, json_encode($dataLocal));
    $volText = "<b>" . $msg['effects'] . "</b>: " . $msg["no_effect"];
    if($dataLocal["effect"]["use"] == "yes"){
        $lvl = $dataLocal["effect"]["volume"];
        $volText = "<b>" . $msg['effects'] . "</b>: " . $msg["effect"] . " - ". $lvl;
    }
    $caption =
        "<b>" . $msg['music_name'] . "</b>: <code>" . htmlspecialchars($dataLocal['title']) . "</code>\n" .
        "<b>" . $msg['artist_name'] . "</b>: <code>" . htmlspecialchars($dataLocal['performer'] ?? '') . "</code>\n" .
        "<b>" . $msg['file_size'] . "</b>: <code>" . $dataLocal['file_size'] . "</code>\n" .
        "<b>" . $msg['duration'] . "</b>: <code>" . $dataLocal['duration'] . "</code>\n" .
        $volText;
    $photo_message_id = file_get_contents("$music_folder/photo_message_id.txt");
    bot('editMessageCaption', [
        'chat_id' => $cid,
        'message_id' => $photo_message_id,
        'caption' => $caption,
        'reply_markup' => $key,
        'parse_mode' => "HTML"
    ]);
    file_put_contents("step/{$uid}_{$cid}.txt", "");
    exit;
}

if ($step == "edit_photo" && isset($message->photo)) {
    $sid = @file_get_contents("$music_folder/sid.txt");
    if ($sid) {
        bot('deleteMessage', [
            'chat_id' => $cid,
            'message_id' => $sid
        ]);
    }
    $photos = $message->photo;
    $highest_quality_photo = end($photos);
    $photo_file_id = $highest_quality_photo->file_id;
    $file_info = bot('getFile', ['file_id' => $photo_file_id]);
    if (!$file_info || !isset($file_info->result->file_path)) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['error_processing_photo'],
            'parse_mode' => "HTML"
        ]);
        exit;
    }
    $file_path = $file_info->result->file_path;
    $photo_url = "https://api.telegram.org/file/bot$token/$file_path";
    $photo_file_path = "$music_folder/image.jpg";
    if (@file_put_contents($photo_file_path, file_get_contents($photo_url)) === false) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['error_saving_photo'],
            'parse_mode' => "HTML"
        ]);
        exit;
    }
    if ($message->message_id) {
        bot('deleteMessage', [
            'chat_id' => $message->chat->id,
            'message_id' => $message->message_id
        ]);
    }
    $data_file = "$music_folder/data.json";
    if (file_exists($data_file)) {
        $dataLocal = json_decode(file_get_contents($data_file), true);
    } else {
        $dataLocal = [];
    }
    $volText = "<b>" . $msg['effects'] . "</b>: " . $msg["no_effect"];
    if($dataLocal["effect"]["use"] == "yes"){
        $lvl = $dataLocal["effect"]["volume"];
        $volText = "<b>" . $msg['effects'] . "</b>: " . $msg["effect"] . " - ". $lvl;
    }
    $caption =
        "<b>" . $msg['music_name'] . "</b>: <code>" . htmlspecialchars($dataLocal['title'] ?? $msg['unknown']) . "</code>\n" .
        "<b>" . $msg['artist_name'] . "</b>: <code>" . htmlspecialchars($dataLocal['performer'] ?? $msg['unknown']) . "</code>\n" .
        "<b>" . $msg['file_size'] . "</b>: <code>" . ($dataLocal['file_size'] ?? $msg['unknown']) . "</code>\n" .
        "<b>" . $msg['duration'] . "</b>: <code>" . ($dataLocal['duration'] ?? $msg['unknown']) . "</code>\n" .
        $volText;
    $nid = @file_get_contents("$music_folder/photo_message_id.txt");
    if (!$nid) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['no_message_to_update'],
            'parse_mode' => "HTML"
        ]);
        exit;
    }
    $media = [
        'type' => 'photo',
        'media' => 'attach://photo',
        'caption' => $caption,
        'parse_mode' => "HTML"
    ];
    $datas = [
        'chat_id' => $cid,
        'message_id' => $nid,
        'reply_markup' => $key,
        'media' => json_encode($media),
        'photo' => new CURLFile(realpath($photo_file_path))
    ];
    $result = bot('editMessageMedia', $datas);
    if (!$result || !$result->ok) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['error_updating_photo'],
            'parse_mode' => "HTML"
        ]);
    }
    file_put_contents("step/{$uid}_{$cid}.txt", "");
    exit;
}

if ($step == "cut_audio" && isset($text)) {
    $sid = @file_get_contents("$music_folder/sid.txt");
    if ($sid) {
        bot('deleteMessage', [
            'chat_id' => $cid,
            'message_id' => $sid
        ]);
    }
    if ($message->message_id) {
        bot('deleteMessage', [
            'chat_id' => $message->chat->id,
            'message_id' => $message->message_id
        ]);
    }
    $input = str_replace(' ', '', $text);
    $times = explode('-', $input);
    if (count($times) != 2 || !is_numeric($times[0]) || !is_numeric($times[1])) {
        $me_messages = file_exists("$music_folder/me.txt") ? json_decode(file_get_contents("$music_folder/me.txt"), true) : [];
        $me = bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['incorrect_interval'],
            'parse_mode' => "HTML"
        ])->result->message_id;
        $me_messages[] = $me;
        file_put_contents("$music_folder/me.txt", json_encode($me_messages));
        exit;
    }
    $start_time = intval($times[0]);
    $end_time = intval($times[1]);
    if ($start_time >= $end_time) {
        $me_messages = file_exists("$music_folder/me.txt") ? json_decode(file_get_contents("$music_folder/me.txt"), true) : [];
        $me = bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['start_less_than_end'],
            'parse_mode' => "HTML"
        ])->result->message_id;
        $me_messages[] = $me;
        file_put_contents("$music_folder/me.txt", json_encode($me_messages));
        exit;
    }
    $audio_file_path = "$music_folder/$uid.mp3";
    $total_duration = getAudioDuration($audio_file_path);
    if ($total_duration === false) {
        $me_messages = file_exists("$music_folder/me.txt") ? json_decode(file_get_contents("$music_folder/me.txt"), true) : [];
        $me = bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['no_audio_duration'],
            'parse_mode' => "HTML"
        ])->result->message_id;
        $me_messages[] = $me;
        file_put_contents("$music_folder/me.txt", json_encode($me_messages));
        exit;
    }
    if ($end_time > $total_duration) {
        $me_messages = file_exists("$music_folder/me.txt") ? json_decode(file_get_contents("$music_folder/me.txt"), true) : [];
        $me = bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['end_exceeds_duration'],
            'parse_mode' => "HTML"
        ])->result->message_id;
        $me_messages[] = $me;
        file_put_contents("$music_folder/me.txt", json_encode($me_messages));
        exit;
    }
    $output_audio_path = "$music_folder/trimmed_$uid.mp3";
    $ffmpeg_cmd = "ffmpeg -i \"$audio_file_path\" -ss $start_time -to $end_time -c copy \"$output_audio_path\" -y 2>&1";
    exec($ffmpeg_cmd, $output, $return_var);
    if ($return_var !== 0 || !file_exists($output_audio_path)) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['error_trimming_audio'],
            'parse_mode' => "HTML"
        ]);
        exit;
    }
    $me_messages = file_exists("$music_folder/me.txt") ? json_decode(file_get_contents("$music_folder/me.txt"), true) : [];
    foreach ($me_messages as $me_id) {
        bot('deleteMessage', [
            'chat_id' => $cid,
            'message_id' => $me_id
        ]);
    }
    unlink("$music_folder/me.txt");
    rename($output_audio_path, $audio_file_path);
    $new_duration = $end_time - $start_time;
    $data_file = "$music_folder/data.json";
    if (file_exists($data_file)) {
        $dataLocal = json_decode(file_get_contents($data_file), true);
    } else {
        $dataLocal = [];
    }
    $dataLocal['duration'] = gmdate("H:i:s", $new_duration);
    file_put_contents($data_file, json_encode($dataLocal));
    $volText = "<b>" . $msg['effects'] . "</b>: " . $msg["no_effect"];
    if($dataLocal["effect"]["use"] == "yes"){
        $lvl = $dataLocal["effect"]["volume"];
        $volText = "<b>" . $msg['effects'] . "</b>: " . $msg["effect"] . " - ". $lvl;
    }
    $caption =
        "<b>" . $msg['music_name'] . "</b>: <code>" . htmlspecialchars($dataLocal['title'] ?? $msg['unknown']) . "</code>\n" .
        "<b>" . $msg['artist_name'] . "</b>: <code>" . htmlspecialchars($dataLocal['performer'] ?? $msg['unknown']) . "</code>\n" .
        "<b>" . $msg['file_size'] . "</b>: <code>" . ($dataLocal['file_size'] ?? $msg['unknown']) . "</code>\n" .
        "<b>" . $msg['duration'] . "</b>: <code>" . $dataLocal['duration'] . "</code>\n" .
        $volText;
    $nid = @file_get_contents("$music_folder/photo_message_id.txt");
    if (!$nid) {
        $me = bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['no_message_to_update'],
            'parse_mode' => "HTML"
        ])->result->message_id;
        $me_messages[] = $me;
        file_put_contents("$music_folder/me.txt", json_encode($me_messages));
        exit;
    }
    bot('editMessageCaption', [
        'chat_id' => $cid,
        'message_id' => $nid,
        'caption' => $caption,
        'reply_markup' => $key,
        'parse_mode' => "HTML"
    ]);
    file_put_contents("step/{$uid}_{$cid}.txt", "");
    exit;
}

if ($data == "cancel") {
    bot('deleteMessage', [
        'chat_id' => $cid,
        'message_id' => $callback_query->message->message_id
    ]);
    file_put_contents("step/{$uid}_{$cid}.txt", "");
    exit;
}

function getJoin($id) {
    $url = 'https://api.flyerservice.io/check';
    $data = [
        'key' => 'FL-Wyxftv-DikSMK-FeLiAv-bydCup',
        'user_id' => $id,
        'language_code' => 'ru'
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    if ($httpCode === 200) {
        $resultData = json_decode($response, true);
        if (isset($resultData['skip']) && $resultData['skip'] === true) {
            return true;
        }
    }
    return false;
}

$yes_keyboard = json_encode([
    'inline_keyboard' => [
        [['text' => $msg['setting_photo'], 'callback_data' => "set_photo"]],
        [['text' => $msg['setting_music_name'], 'callback_data' => "set_music_name"]],
        [['text' => $msg['setting_performer'], 'callback_data' => "set_performer_name"]],
        [['text' => $msg['close'], 'callback_data' => "close_autosave"]]
    ]
]);

$no_keyboard = json_encode([
    'inline_keyboard' => [
        [['text' => $msg['add_photo'], 'callback_data' => "set_photo"]],
        [['text' => $msg['add_music_name'], 'callback_data' => "set_music_name"]],
        [['text' => $msg['add_performer'], 'callback_data' => "set_performer_name"]],
        [['text' => $msg['close'], 'callback_data' => "close_autosave"]]
    ]
]);

$setting_music = json_encode([
    'inline_keyboard' => [
        [['text' => $msg['set_music_empty'], 'callback_data' => "set_music_empty"]],
        [['text' => $msg['leave_music_as_is'], 'callback_data' => "set_music_as_is"]],
        [['text' => $msg['enter_new_music_name'], 'callback_data' => "set_new_music_name"]],
        [['text' => $msg['cancel'], 'callback_data' => "cencel"]]
    ]
]);

$setting_performer = json_encode([
    'inline_keyboard' => [
        [['text' => $msg['set_music_empty'], 'callback_data' => "set_performer_empty"]],
        [['text' => $msg['leave_music_as_is'], 'callback_data' => "set_performer_as_is"]],
        [['text' => $msg['enter_new_music_name'], 'callback_data' => "set_new_performer_name"]],
        [['text' => $msg['cancel'], 'callback_data' => "cencel"]]
    ]
]);

if ($data == "cencel" or $data == "close_autosave") {
    file_put_contents("step/{$uid}_{$cid}.txt", "");
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
}

if ($data == "setting") {
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
}

if($data == "new_o"){
    bot('answerCallbackQuery', [
        'callback_query_id' => $callback_query->id,
        'text' => "✅️"
    ]);
}

if ($text == '/setting' or $data == "setting" or $data == "cencel" or $data == "new_o") {
    $autosave_dir = 'autosave/' . $uid;
    if (!file_exists($autosave_dir)) {
        mkdir($autosave_dir, 0755, true);
    }
    $image_path = $autosave_dir . '/image.jpg';
    $json_path = $autosave_dir . '/data.json';
    $image_exists = file_exists($image_path);
    $json_exists = file_exists($json_path);
    if ($image_exists && $json_exists) {
        $dataLocal = json_decode(file_get_contents($json_path), true);
        $music_name = $dataLocal['music_name'] ?? 'Не настроено';
        $performer_name = $dataLocal['performer_name'] ?? 'Не настроено';
        bot('sendPhoto', [
            'chat_id' => $cid,
            'photo' => new CURLFile(realpath($image_path)),
            'caption' => str_replace(['{music_name}', '{performer_name}'], [$music_name, $performer_name], $msg['auto_settings_caption']),
            'reply_markup' => $yes_keyboard
        ]);
    } elseif ($image_exists) {
        bot('sendPhoto', [
            'chat_id' => $cid,
            'photo' => new CURLFile(realpath($image_path)),
            'caption' => $msg['settings_with_no_data'],
            'reply_markup' => $no_keyboard
        ]);
    } elseif ($json_exists) {
        $dataLocal = json_decode(file_get_contents($json_path), true);
        $music_name = $dataLocal['music_name'] ?? 'Не настроено';
        $performer_name = $dataLocal['performer_name'] ?? 'Не настроено';
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => str_replace(['{music_name}', '{performer_name}'], [$music_name, $performer_name], $msg['auto_settings_caption']),
            'reply_markup' => $no_keyboard
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['no_data_available'],
            'reply_markup' => $no_keyboard
        ]);
    }
}

if ($data == "set_photo") {
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
    file_put_contents("step/{$uid}_{$cid}.txt", "set_photo");
    bot('sendMessage', [
        'chat_id' => $ccid,
        'text' => $msg['photo_prompt']
    ]);
}

if ($step == "set_photo" && isset($message->photo)) {
    $photo = end($message->photo);
    $file_id = $photo->file_id;
    $file_info = bot('getFile', ['file_id' => $file_id]);
    $file_path = $file_info->result->file_path;
    $photo_url = "https://api.telegram.org/file/bot$token/$file_path";
    if (!is_dir('autosave/' . $uid)) {
        mkdir('autosave/' . $uid, 0755, true);
    }
    file_put_contents('autosave/' . $uid . '/image.jpg', file_get_contents($photo_url));
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['photo_saved']
    ]);
    bot('deleteMessage', [
        'chat_id' => $cid,
        'message_id' => $message->message_id
    ]);
    sendAutoSaveSettings($cid, $msg, $yes_keyboard);
}

if ($data == "set_music_name") {
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
    file_put_contents("step/{$uid}_{$cid}.txt", "choose_music_name");
    bot('sendMessage', [
        'chat_id' => $ccid,
        'text' => $msg['select_music_name'],
        'reply_markup' => $setting_music
    ]);
}

if ($data == "set_music_empty") {
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
    $dataLocal = json_decode(file_get_contents('autosave/' . $uid . '/data.json'), true);
    $dataLocal['music_name'] = '⠀';
    if (!isset($dataLocal['performer_name'])) {
        $dataLocal['performer_name'] = null;
    }
    file_put_contents('autosave/' . $uid . '/data.json', json_encode($dataLocal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['music_name_empty']
    ]);
    sendAutoSaveSettings($cid, $msg, $yes_keyboard);
}

if ($data == "set_music_as_is") {
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
    $dataLocal = json_decode(file_get_contents('autosave/' . $uid . '/data.json'), true);
    $dataLocal['music_name'] = null;
    if (!isset($dataLocal['performer_name'])) {
        $dataLocal['performer_name'] = null;
    }
    file_put_contents('autosave/' . $uid . '/data.json', json_encode($dataLocal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['music_name_as_is']
    ]);
    sendAutoSaveSettings($cid, $msg, $yes_keyboard);
}

if ($data == "set_new_music_name") {
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
    file_put_contents("step/{$uid}_{$cid}.txt", "set_new_music_name");
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['new_music_name_prompt']
    ]);
}

if ($step == "set_new_music_name" && isset($text)) {
    if (!is_dir('autosave/' . $uid)) {
        mkdir('autosave/' . $uid, 0755, true);
    }
    $data_file = 'autosave/' . $uid . '/data.json';
    $dataLocal = [];
    if (file_exists($data_file)) {
        $dataLocal = json_decode(file_get_contents($data_file), true);
    }
    $dataLocal['music_name'] = $text;
    if (!isset($dataLocal['performer_name'])) {
        $dataLocal['performer_name'] = null;
    }
    file_put_contents($data_file, json_encode($dataLocal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['new_music_name_saved']
    ]);
    file_put_contents("step/{$uid}_{$cid}.txt", "");
    sendAutoSaveSettings($cid, $msg, $yes_keyboard);
}

if ($data == "set_performer_name") {
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
    file_put_contents("step/{$uid}_{$cid}.txt", "choose_performer_name");
    bot('sendMessage', [
        'chat_id' => $ccid,
        'text' => $msg['select_performer_name'],
        'reply_markup' => $setting_performer
    ]);
}

if ($data == "set_performer_empty") {
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
    $dataLocal = json_decode(file_get_contents('autosave/' . $uid . '/data.json'), true);
    $dataLocal['performer_name'] = '⠀';
    if (!isset($dataLocal['music_name'])) {
        $dataLocal['music_name'] = null;
    }
    file_put_contents('autosave/' . $uid . '/data.json', json_encode($dataLocal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['performer_name_empty']
    ]);
    sendAutoSaveSettings($cid, $msg, $yes_keyboard);
}

if ($data == "set_performer_as_is") {
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
    $dataLocal = json_decode(file_get_contents('autosave/' . $uid . '/data.json'), true);
    $dataLocal['performer_name'] = null;
    if (!isset($dataLocal['music_name'])) {
        $dataLocal['music_name'] = null;
    }
    file_put_contents('autosave/' . $uid . '/data.json', json_encode($dataLocal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['performer_name_as_is']
    ]);
    sendAutoSaveSettings($cid, $msg, $yes_keyboard);
}

if ($data == "set_new_performer_name") {
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
    file_put_contents("step/{$uid}_{$cid}.txt", "set_new_performer_name");
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['new_performer_name_prompt']
    ]);
}

if ($step == "set_new_performer_name" && isset($text)) {
    if (!is_dir('autosave/' . $uid)) {
        mkdir('autosave/' . $uid, 0755, true);
    }
    $data_file = 'autosave/' . $uid . '/data.json';
    $dataLocal = [];
    if (file_exists($data_file)) {
        $dataLocal = json_decode(file_get_contents($data_file), true);
    }
    $dataLocal['performer_name'] = $text;
    if (!isset($dataLocal['music_name'])) {
        $dataLocal['music_name'] = null;
    }
    file_put_contents($data_file, json_encode($dataLocal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg['new_performer_name_saved']
    ]);
    file_put_contents("step/{$uid}_{$cid}.txt", "");
    sendAutoSaveSettings($cid, $msg, $yes_keyboard);
}

if ($data == "close_autosave") {
    bot('deleteMessage', [
        'chat_id' => $ccid,
        'message_id' => $cmid
    ]);
}

function sendAutoSaveSettings($cid, $msg, $yes_keyboard) {
    global $uid;
    $autosave_dir = 'autosave/' . $uid;
    if (!file_exists($autosave_dir . '/data.json')) {
        $dataLocal = [];
    } else {
        $dataLocal = json_decode(file_get_contents($autosave_dir . '/data.json'), true);
    }
    $music_name = $dataLocal['music_name'] ?? $msg['unknown'];
    $performer_name = $dataLocal['performer_name'] ?? 'Не настроено';
    $photo_autosave = file_exists($autosave_dir . '/image.jpg');
    $caption = str_replace(['{music_name}', '{performer_name}'], [$music_name, $performer_name], $msg['auto_settings_caption']);
    if ($photo_autosave) {
        bot('sendPhoto', [
            'chat_id' => $cid,
            'photo' => new CURLFile(realpath($autosave_dir . '/image.jpg')),
            'caption' => $caption,
            'reply_markup' => $yes_keyboard,
            'parse_mode' => 'HTML'
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $caption,
            'reply_markup' => $yes_keyboard,
            'parse_mode' => 'HTML'
        ]);
    }
}

if ($data == "auto_changes") {
    global $uid;
    $autosave_dir = 'autosave/' . $uid;
    $audio_dir = 'audio/' . $uid . '_' . $cid;

    // Проверяем, существует ли автосохранение (изображение + JSON)
    if (!file_exists($autosave_dir . '/data.json') && !file_exists($autosave_dir . '/image.jpg')) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $msg['no_autosave_set'],
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => $msg['configure_autosave'], 'callback_data' => "setting"]]
                ]
            ])
        ]);
        exit;
    }

    // Считываем данные из autosave
    $autosave_data = json_decode(file_get_contents($autosave_dir . '/data.json'), true);

    $data_file = $audio_dir . '/data.json';
    if (!file_exists($data_file)) {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => $msg['audio_file_not_found'],
            'show_alert' => true
        ]);
        exit;
    }

    // Считываем данные из файла аудио
    $audio_data = json_decode(file_get_contents($data_file), true);
    $music_name = $autosave_data['music_name'] ?? null;
    $performer_name = $autosave_data['performer_name'] ?? null;

    // Меняем title и performer, если они установлены пользователем
    if ($music_name !== null && $music_name !== "ㅤ") {
        $audio_data['title'] = $music_name;
    }
    if ($performer_name !== null && $performer_name !== "ㅤ") {
        $audio_data['performer'] = $performer_name;
    }

    // Сохраняем обратно
    file_put_contents($data_file, json_encode($audio_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // Обрабатываем картинку
    $new_image_path = $autosave_dir . '/image.jpg';
    $old_image_path = $audio_dir . '/image.jpg';
    $image_updated = false;

    if (file_exists($new_image_path)) {
        if (file_exists($old_image_path)) {
            unlink($old_image_path);
        }
        if (copy($new_image_path, $old_image_path)) {
            $image_updated = true;
        } else {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $msg['image_copy_error']
            ]);
            exit;
        }
    }

    // Проверяем, есть ли у нас сохранённый message_id для фото
    $photo_message_id_path = $audio_dir . '/photo_message_id.txt';
    $the_message = $msg['main_text'];

    if (file_exists($photo_message_id_path)) {
        $photo_message_id = file_get_contents($photo_message_id_path);

        $data = json_decode(file_get_contents("$music_folder/data.json"), true);
        if ($data["effect"]["use"] == "yes") {
            $lvl = $data["effect"]["volume"];
            $add = $msg["effect"] . " - " . $lvl;
        } else {
            $add = $msg["no_effect"] . " - " . $lvl;
        }

        // Функция для «чистки» нежелательных символов
        function sanitize($value) {
            return str_replace(["<", ">", "`", "~"], "", $value);
        }

        // «Чистим» значения и только потом оборачиваем в <code>
        $title_clean     = "<code>" . sanitize($audio_data['title'] ?? $msg['unknown']) . "</code>";
        $performer_clean = "<code>" . sanitize($audio_data['performer'] ?? $msg['unknown']) . "</code>";
        $size_clean      = "<code>" . sanitize($audio_data['file_size'] ?? $msg['unknown']) . "</code>";
        $duration_clean  = "<code>" . sanitize($audio_data['duration'] ?? $msg['unknown']) . "</code>";
        $effect_clean    = "<code>" . sanitize($add) . "</code>";

        // Заменяем плейсхолдеры в строке $the_message
        $caption_2 = str_replace(
            ['{music_name}', '{performer_name}', '{file_size}', '{duration}', '{effect}'],
            [$title_clean, $performer_clean, $size_clean, $duration_clean, $effect_clean],
            $the_message
        );

        // При желании можно ещё раз пройтись по $caption_2, если требуется,
        // но обычно достаточно очистки на входе, а не на выходе.
        // Если же нужно «дочистить» финальную строку, можно раскомментировать:
        // $caption_2 = str_replace(["<", ">", "`", "~"], "", $caption_2);

        if ($image_updated) {
            $photo_path = realpath($old_image_path);
            if ($photo_path) {
                $media = [
                    'type' => 'photo',
                    'media' => 'attach://photo',
                    'caption' => $caption_2,
                    'parse_mode' => "HTML"
                ];

                $datas = [
                    'chat_id' => $cid,
                    'message_id' => $photo_message_id,
                    'media' => json_encode($media),
                    'photo' => new CURLFile($photo_path),
                    'reply_markup' => $key
                ];

                $result = bot('editMessageMedia', $datas);
                if (!$result->ok) {
                    handleEditError($result, $qid, $msg);
                    exit;
                }
            } else {
                bot('answerCallbackQuery', [
                    'callback_query_id' => $qid,
                    'text' => $msg['image_not_found'],
                    'show_alert' => true
                ]);
                exit;
            }
        } else {
            $result = bot('editMessageCaption', [
                'chat_id' => $cid,
                'message_id' => $photo_message_id,
                'caption' => $caption_2,
                'parse_mode' => "HTML",
                'reply_markup' => $key
            ]);
            if (!$result->ok) {
                handleEditError($result, $qid, $msg);
                exit;
            }
        }

        // Завершаем колбэк
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => $msg['settings_saved'],
            'show_alert' => true
        ]);
    } else {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => $msg['settings_already_changed'],
            'show_alert' => true
        ]);
    }
}

function handleEditError($result, $qid, $msg) {
    if ($result->description == 'Bad Request: message is not modified: specified new message content and reply markup are exactly the same as a current content and reply markup of the message') {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => $msg['settings_already_changed'],
            'show_alert' => true
        ]);
    } else {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => $msg['description_update_error'],
            'show_alert' => true
        ]);
    }
}

function showMyMusicCommon($cid, $fid, $page = 1, $edit = false, $messageId = null, $msg, $withMainMenuButton = true, $prefix = "music") {
    $filePath = "data/$fid.json";
    if (!file_exists($filePath)) {
        $params = [
            'chat_id' => $cid,
            'text' => $msg['not_find']
        ];
        if (!$edit) {
            bot('sendMessage', $params);
        } else {
            $params['message_id'] = $messageId;
            bot('editMessageText', $params);
        }
        return;
    }
    $musicList = json_decode(file_get_contents($filePath), true);
    if (!is_array($musicList) || count($musicList) == 0) {
        $params = [
            'chat_id' => $cid,
            'text' => $msg['not_find']
        ];
        if (!$edit) {
            bot('sendMessage', $params);
        } else {
            $params['message_id'] = $messageId;
            bot('editMessageText', $params);
        }
        return;
    }
    usort($musicList, function($a, $b) {
        return strtotime($b['date_time']) <=> strtotime($a['date_time']);
    });
    $limit = 6;
    $total = count($musicList);
    $totalPages = ceil($total / $limit);
    if ($page < 1) {
        $page = 1;
    } elseif ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $limit;
    $itemsForPage = array_slice($musicList, $offset, $limit);
    $inlineKeyboard = [];
    foreach ($itemsForPage as $track) {
        $musicName  = $track['music_name'];
        $trackMsgId = $track['message_id'];
        $inlineKeyboard[] = [[
            'text' => $musicName,
            'callback_data' => $prefix . "_" . $trackMsgId
        ]];
    }
    $navRow = [];
    if ($page > 1) {
        $navRow[] = [
            'text' => $msg['btn_prev'],
            'callback_data' => $prefix . "_page_" . ($page - 1)
        ];
    }
    if ($page < $totalPages) {
        $navRow[] = [
            'text' => $msg['btn_next'],
            'callback_data' => $prefix . "_page_" . ($page + 1)
        ];
    }
    if (!empty($navRow)) {
        $inlineKeyboard[] = $navRow;
    }
    if ($withMainMenuButton) {
        $inlineKeyboard[] = [
            [
                'text' => $msg['btn_main'],
                'callback_data' => "back_to"
            ]
        ];
    }
    $text = str_replace(['{page}', '{totalPages}'], [$page, $totalPages], $msg['your_musics']);
    $params = [
        'chat_id' => $cid,
        'text' => $text,
        'reply_markup' => json_encode(['inline_keyboard' => $inlineKeyboard]),
        'parse_mode' => 'HTML'
    ];
    if (!$edit) {
        bot('sendMessage', $params);
    } else {
        $params['message_id'] = $messageId;
        bot('editMessageText', $params);
    }
}

if (isset($text)) {
    if ($text == "/mymusic") {
        showMyMusicCommon($cid, $fid, 1, false, null, $msg, true, "music");
        exit;
    }
    if ($text == "/myfiles") {
        showMyMusicCommon($cid, $fid, 1, false, null, $msg, false, "files");
        exit;
    }
}

if (isset($callback_query)) {
    $data = $callback_query->data;
    $cid  = $callback_query->message->chat->id;
    $mid  = $callback_query->message->message_id;
    if ($data == "musics") {
        showMyMusicCommon($cid, $fid, 1, true, $mid, $msg, true, "music");
        bot('answerCallbackQuery', ['callback_query_id' => $callback_query->id]);
        exit;
    }
    if ($data == "myfiles") {
        showMyMusicCommon($cid, $fid, 1, true, $mid, $msg, false, "files");
        bot('answerCallbackQuery', ['callback_query_id' => $callback_query->id]);
        exit;
    }
    if (strpos($data, "music_page_") === 0) {
        $page = (int) str_replace("music_page_", "", $data);
        showMyMusicCommon($cid, $fid, $page, true, $mid, $msg, true, "music");
        bot('answerCallbackQuery', ['callback_query_id' => $callback_query->id]);
        exit;
    }
    if (strpos($data, "files_page_") === 0) {
        $page = (int) str_replace("files_page_", "", $data);
        showMyMusicCommon($cid, $fid, $page, true, $mid, $msg, false, "files");
        bot('answerCallbackQuery', ['callback_query_id' => $callback_query->id]);
        exit;
    }
    if ((strpos($data, "music_") === 0) && (strpos($data, "music_page_") !== 0)) {
        $messageIdToCopy = (int) str_replace("music_", "", $data);
        bot('copyMessage', [
            'chat_id' => $cid,
            'from_chat_id' => $toSend,
            'message_id' => $messageIdToCopy,
            'caption' => "<code>" . htmlspecialchars($description) . "</code>\n\n<a href='https://t.me/mp3_tool_bot?start=new'>@mp3_tool_bot</a>",
            'reply_to_message_id' => $mid,
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                "inline_keyboard" => [
                    [["text" => $msg["open_in_editor"], "callback_data" => "open_id"]]
                ]
            ])
        ]);
        bot('answerCallbackQuery', [
            'callback_query_id' => $callback_query->id,
            'text' => $msg['send_track']
        ]);
        exit;
    }
    if ((strpos($data, "files_") === 0) && (strpos($data, "files_page_") !== 0)) {
        $messageIdToCopy = (int) str_replace("files_", "", $data);
        bot('copyMessage', [
            'chat_id' => $cid,
            'from_chat_id' => $toSend,
            'message_id' => $messageIdToCopy,
            'caption' => "<code>" . htmlspecialchars($description) . "</code>\n\n<a href='https://t.me/mp3_tool_bot?start=new'>@mp3_tool_bot</a>",
            'reply_to_message_id' => $mid,
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                "inline_keyboard" => [
                    [["text" => $msg["open_in_editor"], "callback_data" => "open_id"]]
                ]
            ])
        ]);
        bot('answerCallbackQuery', [
            'callback_query_id' => $callback_query->id,
            'text' => $msg['send_track']
        ]);
        exit;
    }
}

function sendAdd($cid, $msg) {
    $ad = [
        'text' => $msg['ad2_text'],
        'keyboard' => [
            [
                [
                    'text' => $msg['ad2_button'],
                    'url' => $msg['ad2_url']
                ]
            ]
        ]
    ];
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $ad['text'],
        'reply_markup' => json_encode(['inline_keyboard' => $ad['keyboard']]),
        'parse_mode' => 'HTML'
    ]);
}




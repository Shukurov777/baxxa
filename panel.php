<?php

// Подключение конфигурации и бота (раскомментируйте, если используете)
// require_once("config.php");
// require_once("bot.php");

// Токен бота (взято из вашего примера)
$BOT_TOKEN = "6966200328:AAGJL3R6JPIJFJ_0WzOrQSuaBudULOe9fPc";

// Проверка необходимых переменных (определите их в bot.php или здесь)
$uid = isset($uid) ? $uid : (isset($_GET['uid']) ? $_GET['uid'] : null); // ID пользователя
$cid = isset($cid) ? $cid : (isset($_GET['cid']) ? $_GET['cid'] : null); // ID чата
$ccid = isset($ccid) ? $ccid : (isset($_GET['ccid']) ? $_GET['ccid'] : null); // ID чата для callback
$cmid = isset($cmid) ? $cmid : (isset($_GET['cmid']) ? $_GET['cmid'] : null); // ID сообщения
$qid = isset($qid) ? $qid : (isset($_GET['qid']) ? $_GET['qid'] : null); // ID callback-запроса
$text = isset($text) ? $text : (isset($_POST['text']) ? $_POST['text'] : null); // Текст сообщения
$data = isset($data) ? $data : (isset($_POST['data']) ? $_POST['data'] : null); // Callback-данные
$message = isset($message) ? $message : null; // Сообщение
$callback = isset($callback) ? $callback : null; // Callback

// Список администраторов (определите в config.php или здесь)
$admin = isset($admin) ? $admin : json_decode(file_get_contents("admin/admins.json"), true) ?? [123456789]; // Замените 123456789 на ваш ID
$admin_file = isset($admin_file) ? $admin_file : "admin/admins.json"; // Файл администраторов

// Функция для отправки запросов к Telegram API
function sendTelegramRequest($method, $data) {
    global $uid, $BOT_TOKEN;

    // Проверка токена
    if (empty($BOT_TOKEN)) {
        error_log("BOT_TOKEN is not defined", 3, "admin/errors.log");
        return ['ok' => false, 'description' => 'BOT_TOKEN is not defined'];
    }

    // Формируем URL
    $url = "https://api.telegram.org/bot" . $BOT_TOKEN . "/" . $method;
    $ch = curl_init($url);

    // Настраиваем cURL
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Выполняем запрос
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Декодируем ответ
    $response = json_decode($result, true) ?? [];

    // Логируем ошибки cURL
    if ($curl_error || $http_code != 200) {
        $error_msg = "cURL Error: $curl_error, HTTP Code: $http_code, Response: " . print_r($response, true);
        error_log($error_msg, 3, "admin/errors.log");
        return ['ok' => false, 'description' => $error_msg];
    }

    // Проверка на ошибку блокировки
    if (isset($response['ok']) && $response['ok'] === false && strpos($response['description'] ?? '', 'Forbidden') !== false) {
        // Пользователь заблокировал бота
        $data_content = json_decode(file_get_contents("admin/link.json"), true) ?? [];
        foreach ($data_content as $link_id => $info) {
            $users_file = "admin/link/$link_id/users.txt";
            $blocked_file = "admin/link/$link_id/blocked.txt";
            $users = file_exists($users_file) ? file($users_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
            if (in_array($uid, $users)) {
                $blocked_users = file_exists($blocked_file) ? file($blocked_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
                if (!in_array($uid, $blocked_users)) {
                    // Добавляем пользователя в blocked.txt
                    if (file_put_contents($blocked_file, $uid . "\n", FILE_APPEND | LOCK_EX) === false) {
                        error_log("Failed to write to $blocked_file for UID $uid", 3, "admin/errors.log");
                    }
                }
                break;
            }
        }
    }

    return $response;
}

// Проверка директорий
if (!is_dir("admin")) {
    mkdir("admin", 0755, true);
}
if (!is_dir("admin/link")) {
    mkdir("admin/link", 0755, true);
}
if (!is_dir("admin/stat")) {
    mkdir("admin/stat", 0755, true);
}
if (!is_dir("data")) {
    mkdir("data", 0755, true);
}

// Админ-панель
if (($text == "/admin" || $data == "admin_panel") && in_array($uid, $admin)) {
    $admin_keyboard = json_encode([
        'inline_keyboard' => [
            [['text' => '📊 Статистика', 'callback_data' => 'stats']],
            [['text' => '🔗 Ссылки', 'callback_data' => 'links']],
            [['text' => '📁 База данных', 'callback_data' => 'database']],
            [['text' => '📣 Каналы', 'callback_data' => 'channels']],
            [['text' => '👥 Управление администраторами', 'callback_data' => 'admin_management']],
            [['text' => '❌ Закрыть', 'callback_data' => 'close_admin']]
        ]
    ]);
    if ($message) {
        sendTelegramRequest('sendMessage', [
            'chat_id' => $cid,
            'text' => "👮‍♂️ Админ-панель",
            'reply_markup' => $admin_keyboard,
            'parse_mode' => "HTML"
        ]);
    } elseif ($callback) {
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "👮‍♂️ Админ-панель",
            'reply_markup' => $admin_keyboard,
            'parse_mode' => "HTML"
        ]);
    }
}

// Обработка callback-запросов для админов
if (isset($data) && in_array($uid, $admin)) {
    if ($data == 'stats') {
        $stats_keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => '📅 Статистика по дате', 'callback_data' => 'stats_by_date']],
                [['text' => '📊 Общая статистика', 'callback_data' => 'overall_stats']],
                [['text' => '⬅️ Назад', 'callback_data' => 'admin_panel']]
            ]
        ]);
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "📊 Выберите тип статистики:",
            'reply_markup' => $stats_keyboard,
            'parse_mode' => "HTML"
        ]);
    } elseif ($data == 'stats_by_date') {
        file_put_contents("admin/{$uid}_state.txt", "waiting_for_stats_date");
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "📅 Пожалуйста, отправьте дату в формате ДД.ММ.ГГГГ. Пример: 10.10.2024",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '⬅️ Назад', 'callback_data' => 'stats']],
                    [['text' => '🚫 Отмена', 'callback_data' => 'cancel_action']]
                ]
            ])
        ]);
    } elseif ($data == 'overall_stats') {
        $stats_text = getStatistics();
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => $stats_text,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '⬅️ Назад', 'callback_data' => 'stats']]
                ]
            ]),
            'parse_mode' => "HTML"
        ]);
    } elseif ($data == 'links') {
        $links_keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => 'Создать ссылку', 'callback_data' => 'create_link']],
                [['text' => 'Статистика ссылок', 'callback_data' => 'link_stats']],
                [['text' => 'Мои ссылки', 'callback_data' => 'my_links']],
                [['text' => '⬅️ Назад', 'callback_data' => 'admin_panel']]
            ]
        ]);
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "🔗 Управление ссылками:",
            'reply_markup' => $links_keyboard,
            'parse_mode' => "HTML"
        ]);
    } elseif ($data == 'create_link') {
        file_put_contents("admin/{$uid}_state.txt", "waiting_for_link_desc");
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "📝 Пожалуйста, отправьте описание для новой ссылки.",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '🚫 Отмена', 'callback_data' => 'cancel_action']]
                ]
            ])
        ]);
    } elseif ($data == 'link_stats') {
        $data_content = json_decode(file_get_contents("admin/link.json"), true) ?? [];
        $stats = "📊 Статистика ссылок:\n";
        if (!empty($data_content)) {
            foreach ($data_content as $key => $info) {
                $users_file = "admin/link/$key/users.txt";
                $blocked_file = "admin/link/$key/blocked.txt";
                $music_file = "admin/link/$key/music.txt";
                $user_count = file_exists($users_file) ? substr_count(file_get_contents($users_file), "\n") : 0;
                $blocked_count = file_exists($blocked_file) ? substr_count(file_get_contents($blocked_file), "\n") : 0;
                $active_count = $user_count - $blocked_count;
                $music_count = file_exists($music_file) ? (int)file_get_contents($music_file) : 0;
                $stats .= "🔗 <b>$key</b> ({$info['description']}): {$active_count} активных, {$blocked_count} заблокировано, {$music_count} треков\n";
            }
        } else {
            $stats .= "Нет созданных ссылок.";
        }
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => $stats,
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '⬅️ Назад', 'callback_data' => 'links']],
                    [['text' => '🔍 Детальная статистика', 'callback_data' => 'detailed_link_stats']]
                ]
            ])
        ]);
    } elseif ($data == 'detailed_link_stats') {
        $data_content = json_decode(file_get_contents("admin/link.json"), true) ?? [];
        if (!empty($data_content)) {
            $link_buttons = [];
            foreach ($data_content as $key => $info) {
                $link_buttons[] = [['text' => "$key ({$info['description']})", 'callback_data' => "link_info:$key"]];
            }
            $link_buttons[] = [['text' => '⬅️ Назад', 'callback_data' => 'links']];
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => "🔍 Выберите ссылку для просмотра детальной статистики:",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode(['inline_keyboard' => $link_buttons])
            ]);
        } else {
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => "Нет созданных ссылок.",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '⬅️ Назад', 'callback_data' => 'links']]
                    ]
                ])
            ]);
        }
    } elseif (strpos($data, 'link_info:') === 0) {
        $link_id = substr($data, strlen('link_info:'));
        $data_content = json_decode(file_get_contents("admin/link.json"), true) ?? [];
        if (isset($data_content[$link_id])) {
            $info = $data_content[$link_id];
            $users_file = "admin/link/$link_id/users.txt";
            $blocked_file = "admin/link/$link_id/blocked.txt";
            $music_file = "admin/link/$link_id/music.txt";
            $user_count = file_exists($users_file) ? substr_count(file_get_contents($users_file), "\n") : 0;
            $blocked_count = file_exists($blocked_file) ? substr_count(file_get_contents($blocked_file), "\n") : 0;
            $active_count = $user_count - $blocked_count;
            $music_count = file_exists($music_file) ? (int)file_get_contents($music_file) : 0;
            $text = "🔗 Информация о ссылке <b>$link_id</b>:\n\n";
            $text .= "Описание: <b>{$info['description']}</b>\n";
            $text .= "Ссылка: https://t.me/mp3_Tool_Bot?start=$link_id\n";
            $text .= "👥 Активных пользователей: <b>$active_count</b>\n";
            $text .= "🚫 Заблокировано пользователей: <b>$blocked_count</b>\n";
            $text .= "🎵 Треков отправлено: <b>$music_count</b>\n";
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => $text,
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '⬅️ Назад', 'callback_data' => 'detailed_link_stats']]
                    ]
                ])
            ]);
        } else {
            sendTelegramRequest('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => '❗ Ссылка не найдена.',
                'show_alert' => true
            ]);
        }
    } elseif ($data == 'my_links') {
        $data_content = json_decode(file_get_contents("admin/link.json"), true) ?? [];
        $links = "🔗 Ваши ссылки:\n";
        if (!empty($data_content)) {
            foreach ($data_content as $key => $info) {
                $links .= "🔗 <b>$key</b> ({$info['description']}): https://t.me/mp3_Tool_Bot?start=$key\n";
            }
        } else {
            $links .= "Нет созданных ссылок.";
        }
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => $links,
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '⬅️ Назад', 'callback_data' => 'links']]
                ]
            ])
        ]);
    } elseif ($data == 'database') {
        if (file_exists("admin/all.txt")) {
            sendTelegramRequest('sendDocument', [
                'chat_id' => $ccid,
                'document' => new CURLFile("admin/all.txt"),
            ]);
        } else {
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => "❗ База данных не найдена.",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '⬅️ Назад', 'callback_data' => 'admin_panel']]
                    ]
                ])
            ]);
        }
    } elseif ($data == 'channels') {
        $channels_keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => 'Добавить канал', 'callback_data' => 'add_channel']],
                [['text' => 'Список каналов', 'callback_data' => 'list_channels']],
                [['text' => 'Удалить канал', 'callback_data' => 'delete_channel']],
                [['text' => '⬅️ Назад', 'callback_data' => 'admin_panel']]
            ]
        ]);
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "📣 Управление каналами:",
            'reply_markup' => $channels_keyboard,
            'parse_mode' => "HTML"
        ]);
    } elseif ($data == 'add_channel') {
        file_put_contents("admin/{$uid}_state.txt", "waiting_for_channel_info");
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "📝 Пожалуйста, отправьте данные в формате:\n<code>Channel ID\nLink\nChannel Name</code>",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '🚫 Отмена', 'callback_data' => 'cancel_action']]
                ]
            ])
        ]);
    } elseif ($data == 'list_channels') {
        $channels = json_decode(file_get_contents("data/channel.json"), true) ?? ['result' => []];
        if (!empty($channels['result'])) {
            $channel_buttons = [];
            foreach ($channels['result'] as $channel) {
                $channel_buttons[] = [['text' => $channel['title'], 'callback_data' => 'channel_info:' . $channel['channel_id']]];
            }
            $channel_buttons[] = [['text' => '⬅️ Назад', 'callback_data' => 'channels']];
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => "📃 Список каналов:",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode(['inline_keyboard' => $channel_buttons])
            ]);
        } else {
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => "Нет добавленных каналов.",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '⬅️ Назад', 'callback_data' => 'channels']]
                    ]
                ])
            ]);
        }
    } elseif (strpos($data, 'channel_info:') === 0) {
        $channel_id = substr($data, strlen('channel_info:'));
        $channels = json_decode(file_get_contents("data/channel.json"), true) ?? ['result' => []];
        $channel_info = null;
        foreach ($channels['result'] as $channel) {
            if ($channel['channel_id'] == $channel_id) {
                $channel_info = $channel;
                break;
            }
        }
        if ($channel_info) {
            $text = "📣 Информация о канале:\n\n";
            $text .= "Название: <b>{$channel_info['title']}</b>\n";
            $text .= "ID: <code>{$channel_info['channel_id']}</code>\n";
            $text .= "Ссылка: {$channel_info['link']}";
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => $text,
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '⬅️ Назад', 'callback_data' => 'list_channels']]
                    ]
                ])
            ]);
        } else {
            sendTelegramRequest('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => '❗ Канал не найден.',
                'show_alert' => true
            ]);
        }
    } elseif ($data == 'delete_channel') {
        $channels = json_decode(file_get_contents("data/channel.json"), true) ?? ['result' => []];
        if (!empty($channels['result'])) {
            $channel_buttons = [];
            foreach ($channels['result'] as $channel) {
                $channel_buttons[] = [['text' => $channel['title'], 'callback_data' => 'confirm_delete_channel:' . $channel['channel_id']]];
            }
            $channel_buttons[] = [['text' => '⬅️ Назад', 'callback_data' => 'channels']];
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => "🗑 Выберите канал для удаления:",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode(['inline_keyboard' => $channel_buttons])
            ]);
        } else {
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => "Нет добавленных каналов.",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '⬅️ Назад', 'callback_data' => 'channels']]
                    ]
                ])
            ]);
        }
    } elseif (strpos($data, 'confirm_delete_channel:') === 0) {
        $channel_id = substr($data, strlen('confirm_delete_channel:'));
        $channels = json_decode(file_get_contents("data/channel.json"), true) ?? ['result' => []];
        $channel_info = null;
        foreach ($channels['result'] as $channel) {
            if ($channel['channel_id'] == $channel_id) {
                $channel_info = $channel;
                break;
            }
        }
        if ($channel_info) {
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => "❓ Вы уверены, что хотите удалить канал <b>{$channel_info['title']}</b>?",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '✅ Да', 'callback_data' => 'delete_channel_confirmed:' . $channel_id]],
                        [['text' => '🚫 Отмена', 'callback_data' => 'delete_channel']]
                    ]
                ])
            ]);
        } else {
            sendTelegramRequest('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => '❗ Канал не найден.',
                'show_alert' => true
            ]);
        }
    } elseif (strpos($data, 'delete_channel_confirmed:') === 0) {
        $channel_id = substr($data, strlen('delete_channel_confirmed:'));
        $channels = json_decode(file_get_contents("data/channel.json"), true) ?? ['result' => []];
        $found = false;
        foreach ($channels['result'] as $key => $channel) {
            if ($channel['channel_id'] == $channel_id) {
                unset($channels['result'][$key]);
                $found = true;
                break;
            }
        }
        if ($found) {
            $channels['result'] = array_values($channels['result']);
            file_put_contents("data/channel.json", json_encode($channels));
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => "✅ Канал удалён успешно.",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '⬅️ Назад', 'callback_data' => 'channels']]
                    ]
                ])
            ]);
        } else {
            sendTelegramRequest('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => '❗ Канал не найден.',
                'show_alert' => true
            ]);
        }
    } elseif ($data == 'admin_management') {
        $admin_mgmt_keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => '➕ Добавить администратора', 'callback_data' => 'add_admin']],
                [['text' => '👥 Список администраторов', 'callback_data' => 'list_admins']],
                [['text' => '➖ Удалить администратора', 'callback_data' => 'remove_admin']],
                [['text' => '⬅️ Назад', 'callback_data' => 'admin_panel']]
            ]
        ]);
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "👥 Управление администраторами:",
            'reply_markup' => $admin_mgmt_keyboard,
            'parse_mode' => "HTML"
        ]);
    } elseif ($data == 'add_admin') {
        file_put_contents("admin/{$uid}_state.txt", "waiting_for_new_admin_id");
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "📝 Пожалуйста, отправьте User ID пользователя, которого хотите добавить в администраторы.",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '🚫 Отмена', 'callback_data' => 'cancel_action']]
                ]
            ])
        ]);
    } elseif ($data == 'list_admins') {
        $admin_list_text = "👥 Список администраторов:\n";
        foreach ($admin as $admin_id) {
            $admin_list_text .= "• <code>$admin_id</code>\n";
        }
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => $admin_list_text,
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '⬅️ Назад', 'callback_data' => 'admin_management']]
                ]
            ])
        ]);
    } elseif ($data == 'remove_admin') {
        $admin_buttons = [];
        foreach ($admin as $admin_id) {
            $admin_buttons[] = [['text' => $admin_id, 'callback_data' => 'confirm_remove_admin:' . $admin_id]];
        }
        $admin_buttons[] = [['text' => '⬅️ Назад', 'callback_data' => 'admin_management']];
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "👥 Выберите администратора для удаления:",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode(['inline_keyboard' => $admin_buttons])
        ]);
    } elseif (strpos($data, 'confirm_remove_admin:') === 0) {
        $remove_admin_id = substr($data, strlen('confirm_remove_admin:'));
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "❓ Вы уверены, что хотите удалить администратора с ID <code>$remove_admin_id</code>?",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '✅ Да', 'callback_data' => 'remove_admin_confirmed:' . $remove_admin_id]],
                    [['text' => '🚫 Отмена', 'callback_data' => 'remove_admin']]
                ]
            ])
        ]);
    } elseif (strpos($data, 'remove_admin_confirmed:') === 0) {
        $remove_admin_id = substr($data, strlen('remove_admin_confirmed:'));
        if (($key = array_search($remove_admin_id, $admin)) !== false) {
            unset($admin[$key]);
            file_put_contents($admin_file, json_encode(array_values($admin)));
            sendTelegramRequest('editMessageText', [
                'chat_id' => $ccid,
                'message_id' => $cmid,
                'text' => "✅ Администратор с ID <code>$remove_admin_id</code> удалён.",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '⬅️ Назад', 'callback_data' => 'admin_management']]
                    ]
                ])
            ]);
        } else {
            sendTelegramRequest('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => '❗ Администратор не найден.',
                'show_alert' => true
            ]);
        }
    } elseif ($data == 'cancel_action') {
        @unlink("admin/{$uid}_state.txt");
        sendTelegramRequest('editMessageText', [
            'chat_id' => $ccid,
            'message_id' => $cmid,
            'text' => "Действие отменено.",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '⬅️ Назад', 'callback_data' => 'admin_panel']]
                ]
            ])
        ]);
    } elseif ($data == 'close_admin') {
        sendTelegramRequest('deleteMessage', [
            'chat_id' => $ccid,
            'message_id' => $cmid
        ]);
    }
}

// Обработка состояний админа
$admin_state = @file_get_contents("admin/{$uid}_state.txt");
if ($admin_state && isset($text) && in_array($uid, $admin)) {
    if ($admin_state == "waiting_for_stats_date") {
        $date_input = trim($text);
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date_input)) {
            $date_parts = explode('.', $date_input);
            $day = $date_parts[0];
            $month = $date_parts[1];
            $year = $date_parts[2];
            $date_filename = "{$year}_{$month}_{$day}";
            $user_file = "admin/$date_filename.txt"; // Исправлено на правильный формат имени файла
            $music_file = "admin/stat/musics_{$date_filename}.txt";

            if (file_exists($user_file) || file_exists($music_file)) {
                $user_count = file_exists($user_file) ? substr_count(file_get_contents($user_file), "\n") : 0;
                $music_count = file_exists($music_file) ? (int)file_get_contents($music_file) : 0;

                $stats_text = "📊 Статистика за $date_input:\n\n";
                $stats_text .= "👥 Пользователей: <b>$user_count</b>\n";
                $stats_text .= "🎵 Музыки обработано: <b>$music_count</b>";

                sendTelegramRequest('sendMessage', [
                    'chat_id' => $cid,
                    'text' => $stats_text,
                    'parse_mode' => "HTML",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => '⬅️ Назад', 'callback_data' => 'stats']]
                        ]
                    ])
                ]);
            } else {
                sendTelegramRequest('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❗ Данные за $date_input не найдены.",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => '⬅️ Назад', 'callback_data' => 'stats']]
                        ]
                    ])
                ]);
            }
            @unlink("admin/{$uid}_state.txt");
        } else {
            sendTelegramRequest('sendMessage', [
                'chat_id' => $cid,
                'text' => "❗ Пожалуйста, введите дату в правильном формате ДД.ММ.ГГГГ",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '🚫 Отмена', 'callback_data' => 'cancel_action']]
                    ]
                ])
            ]);
        }
    } elseif ($admin_state == "waiting_for_link_desc") {
        $description = trim($text);
        // Проверка уникальности link_id
        $data_content = json_decode(file_get_contents("admin/link.json"), true) ?? [];
        do {
            $link_id = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 4);
        } while (isset($data_content[$link_id]));
        $ref_link = "https://t.me/mp3_Tool_Bot?start=$link_id";
        $data_content[$link_id] = ['description' => $description, 'subscribers' => 0];
        if (file_put_contents("admin/link.json", json_encode($data_content)) === false) {
            error_log("Failed to write to admin/link.json", 3, "admin/errors.log");
        }

        // Создание папки для ссылки
        $link_dir = "admin/link/$link_id";
        if (!is_dir($link_dir)) {
            mkdir($link_dir, 0755, true);
        }
        // Создание файлов для пользователей, музыки и заблокированных
        file_put_contents("$link_dir/users.txt", "");
        file_put_contents("$link_dir/music.txt", "0");
        file_put_contents("$link_dir/blocked.txt", "");

        @unlink("admin/{$uid}_state.txt");
        sendTelegramRequest('sendMessage', [
            'chat_id' => $cid,
            'text' => "🔗 Ссылка создана: $ref_link",
            'parse_mode' => "HTML",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '⬅️ Назад', 'callback_data' => 'links']]
                ]
            ])
        ]);
    } elseif ($admin_state == "waiting_for_new_admin_id") {
        $new_admin_id = trim($text);
        if (is_numeric($new_admin_id)) {
            if (!in_array($new_admin_id, $admin)) {
                $admin[] = $new_admin_id;
                file_put_contents($admin_file, json_encode($admin));
                @unlink("admin/{$uid}_state.txt");
                sendTelegramRequest('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ Пользователь с ID <code>$new_admin_id</code> добавлен в администраторы.",
                    'parse_mode' => "HTML",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => '⬅️ Назад', 'callback_data' => 'admin_management']]
                        ]
                    ])
                ]);
            } else {
                sendTelegramRequest('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❗ Этот пользователь уже является администратором.",
                    'parse_mode' => "HTML",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => '🚫 Отмена', 'callback_data' => 'cancel_action']]
                        ]
                    ])
                ]);
            }
        } else {
            sendTelegramRequest('sendMessage', [
                'chat_id' => $cid,
                'text' => "❗ Пожалуйста, введите корректный User ID (число).",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '🚫 Отмена', 'callback_data' => 'cancel_action']]
                    ]
                ])
            ]);
        }
    } elseif ($admin_state == "waiting_for_channel_info") {
        $lines = explode("\n", $text);
        if (count($lines) == 3) {
            $channel_id = trim($lines[0]);
            $link = trim($lines[1]);
            $channel_name = trim($lines[2]);

            $channels = json_decode(file_get_contents("data/channel.json"), true) ?? ['result' => []];

            $exists = false;
            foreach ($channels['result'] as $channel) {
                if ($channel['channel_id'] == $channel_id) {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                sendTelegramRequest('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❗ Канал с ID $channel_id уже существует.",
                    'parse_mode' => "HTML",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => '🚫 Отмена', 'callback_data' => 'cancel_action']]
                        ]
                    ])
                ]);
            } else {
                $channels['result'][] = [
                    'channel_id' => $channel_id,
                    'title' => $channel_name,
                    'link' => $link
                ];
                file_put_contents("data/channel.json", json_encode($channels));
                @unlink("admin/{$uid}_state.txt");
                sendTelegramRequest('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "📣 Канал добавлен успешно!",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => '⬅️ Назад', 'callback_data' => 'channels']]
                        ]
                    ])
                ]);
            }
        } else {
            sendTelegramRequest('sendMessage', [
                'chat_id' => $cid,
                'text' => "❗ Пожалуйста, используйте правильный формат:\nChannel ID\nLink\nChannel Name",
                'parse_mode' => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '🚫 Отмена', 'callback_data' => 'cancel_action']]
                    ]
                ])
            ]);
        }
    }
}

// Обработка перехода по реферальной ссылке
if (isset($text) && preg_match('/^\/start (.+)$/', $text, $matches)) {
    $link_id = $matches[1];
    $data_content = json_decode(file_get_contents("admin/link.json"), true) ?? [];
    if (isset($data_content[$link_id])) {
        $users_file = "admin/link/$link_id/users.txt";
        $blocked_file = "admin/link/$link_id/blocked.txt";
        $users = file_exists($users_file) ? file($users_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $blocked_users = file_exists($blocked_file) ? file($blocked_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        if (!in_array($uid, $users) && !in_array($uid, $blocked_users)) {
            // Добавляем пользователя в users.txt
            file_put_contents($users_file, $uid . "\n", FILE_APPEND | LOCK_EX);
            // Увеличиваем счётчик подписчиков
            $data_content[$link_id]['subscribers']++;
            file_put_contents("admin/link.json", json_encode($data_content));
        }
    } else {
    }
}

// Обработка отправки музыки
function handleMusicUpload($uid, $cid) {
    $data_content = json_decode(file_get_contents("admin/link.json"), true) ?? [];
    foreach ($data_content as $link_id => $info) {
        $users_file = "admin/link/$link_id/users.txt";
        $blocked_file = "admin/link/$link_id/blocked.txt";
        $users = file_exists($users_file) ? file($users_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $blocked_users = file_exists($blocked_file) ? file($blocked_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        if (in_array($uid, $users) && !in_array($uid, $blocked_users)) {
            // Увеличиваем счётчик треков
            $music_file = "admin/link/$link_id/music.txt";
            $music_count = file_exists($music_file) ? (int)file_get_contents($music_file) : 0;
            $music_count++;
            file_put_contents($music_file, $music_count, LOCK_EX);
            break;
        }
    }
}

// Функция для получения общей статистики
function getStatistics() {
    date_default_timezone_set('Europe/Moscow');
    $date = date('Y_m_d');
    $yesterday = date('Y_m_d', strtotime('-1 day'));

    $today_user_file = "admin/$date.txt";
    $all_users_file = "admin/all.txt";
    $yesterday_user_file = "admin/$yesterday.txt";

    $muz_today_file = "admin/stat/musics_{$date}.txt";
    $muz_file = "admin/stat/musics.txt";
    $muz_yesterday_file = "admin/stat/musics_{$yesterday}.txt";

    $today_users = file_exists($today_user_file) ? substr_count(file_get_contents($today_user_file), "\n") : 0;
    $all_users = file_exists($all_users_file) ? substr_count(file_get_contents($all_users_file), "\n") : 0;
    $yesterday_users = file_exists($yesterday_user_file) ? substr_count(file_get_contents($yesterday_user_file), "\n") : 0;

    $muz_today = file_exists($muz_today_file) ? (int)file_get_contents($muz_today_file) : 0;
    $muz_total = file_exists($muz_file) ? (int)file_get_contents($muz_file) : 0;
    $muz_yesterday = file_exists($muz_yesterday_file) ? (int)file_get_contents($muz_yesterday_file) : 0;

    $stats_text = "📊 <b>Общая статистика</b>\n\n";
    $stats_text .= "👥 Всего пользователей: <b>$all_users</b>\n";
    $stats_text .= "📅 Пользователей сегодня: <b>$today_users</b>\n";
    $stats_text .= "📅 Пользователей вчера: <b>$yesterday_users</b>\n\n";
    $stats_text .= "🎵 Всего музыки обработано: <b>$muz_total</b>\n";
    $stats_text .= "🎵 Музыки сегодня: <b>$muz_today</b>\n";
    $stats_text .= "🎵 Музыки вчера: <b>$muz_yesterday</b>\n";

    return $stats_text;
}

?>
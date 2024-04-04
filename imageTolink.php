<?php
define('API_TOKEN', '7030409010:AAFpEYc9685k0WX8CVlqcAtCm_lkQGEwZzM');
define('IMGBB_API_ENDPOINT', 'https://api.imgbb.com/1/upload');
define('IMGBB_API_KEY', 'd58b53fed58cb6da4ef53e8ebe866d63');

$update = json_decode(file_get_contents("php://input"), true);
$message = $update['message']['text'];
$chatId = $update['message']['chat']['id'];

if ($message == '/start') {
    $response = "<b>📸Welcome to upload bot!</b>\n\n<i>✅ You can upload your photo and get URL for your photo</i>\n\nℹ️ Send Photo to upload";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => 'Developer Channel', 'url' => 'https://t.me/yourchannel']],
            [['text' => 'Bots Channel', 'url' => 'https://t.me/yourcahnner']]
        ]
    ];
    $encodedKeyboard = json_encode($keyboard);
    sendMessage($chatId, $response, $encodedKeyboard, 'HTML');
} elseif ($update['message']['photo']) {
    $photoArray = end($update['message']['photo']);
    $photoId = $photoArray['file_id'];
    $photoFile = getPhotoFile($photoId);

    if ($photoFile !== null) {
        $imgbbUrl = uploadToImgBB($photoFile);

        if ($imgbbUrl !== null) {
            sendMessage($chatId, "<b>🎑Image >> $imgbbUrl</b>", null, 'HTML', $update['message']['message_id']);
        } else {
            sendMessage($chatId, "Failed to upload the photo to ImgBB.");
        }
    } else {
        sendMessage($chatId, "Failed to retrieve the photo.");
    }
}

function sendMessage($chatId, $message, $keyboard, $parseMode, $replyToMessageId = null) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'reply_markup' => $keyboard,
        'parse_mode' => $parseMode,
        'reply_to_message_id' => $replyToMessageId
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

function getPhotoFile($photoId) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/getFile?file_id=$photoId";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);

    if (isset($result['ok']) && $result['ok'] === true) {
        if (isset($result['result']['file_path'])) {
            return "https://api.telegram.org/file/bot" . API_TOKEN . "/" . $result['result']['file_path'];
        }
    }

    return null;
}

function uploadToImgBB($photoFile) {
    $post = [
        'key' => IMGBB_API_KEY,
        'image' => base64_encode(file_get_contents($photoFile))
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, IMGBB_API_ENDPOINT);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);

    if (isset($result['data']['url'])) {
        return $result['data']['url'];
    }

    return null;
}
?>
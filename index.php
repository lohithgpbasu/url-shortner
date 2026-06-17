<?php
/*
|--------------------------------------------------------------------------
| Single File URL Shortener (index.php)
|--------------------------------------------------------------------------
| Requirements:
| - PHP hosting (GitHub Pages won't run PHP)
| - JSON storage
| - No database
| - Own short links
|--------------------------------------------------------------------------
*/

$dataFile = __DIR__ . '/urls.json';

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, '{}');
}

$data = json_decode(file_get_contents($dataFile), true) ?: [];

$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') .
    '://' . $_SERVER['HTTP_HOST'] .
    rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

function generateCode($length = 6)
{
    return substr(str_shuffle(
        '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ), 0, $length);
}

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/
if (isset($_GET['u'])) {
    $code = trim($_GET['u']);

    if (isset($data[$code])) {
        header('Location: ' . $data[$code]);
        exit;
    }

    http_response_code(404);
    die('Short URL not found.');
}

/*
|--------------------------------------------------------------------------
| Create Short URL
|--------------------------------------------------------------------------
*/
$shortUrl = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['url'] ?? '');

    if (!$url) {
        $error = 'Please enter a URL.';
    } else {
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $error = 'Invalid URL.';
        } else {
            $code = generateCode();

            while (isset($data[$code])) {
                $code = generateCode();
            }

            $data[$code] = $url;

            file_put_contents(
                $dataFile,
                json_encode($data, JSON_PRETTY_PRINT)
            );

            $shortUrl = $baseUrl . '/?u=' . $code;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>URL Shortener</title>

<style>
*{
    box-sizing:border-box;
    font-family:Arial,sans-serif;
}
body{
    margin:0;
    background:#0f172a;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    color:#fff;
}
.card{
    width:100%;
    max-width:600px;
    background:#1e293b;
    padding:30px;
    border-radius:16px;
}
h1{
    text-align:center;
    margin-top:0;
}
input{
    width:100%;
    padding:15px;
    border:none;
    border-radius:10px;
    margin-bottom:15px;
    font-size:16px;
}
button{
    width:100%;
    padding:15px;
    border:none;
    border-radius:10px;
    background:#3b82f6;
    color:#fff;
    font-size:16px;
    cursor:pointer;
}
.result{
    margin-top:20px;
    background:#0f172a;
    padding:15px;
    border-radius:10px;
    word-break:break-all;
}
.result a{
    color:#60a5fa;
}
.error{
    margin-top:15px;
    color:#f87171;
}
</style>
</head>
<body>

<div class="card">
    <h1>URL Shortener</h1>

    <form method="post">
        <input
            type="text"
            name="url"
            placeholder="Enter long URL..."
            required
        >

        <button type="submit">
            Shorten URL
        </button>
    </form>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($shortUrl): ?>
        <div class="result">
            <strong>Short URL:</strong><br><br>
            <a href="<?= $shortUrl ?>" target="_blank">
                <?= $shortUrl ?>
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

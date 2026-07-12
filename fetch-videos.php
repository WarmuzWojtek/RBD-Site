<?php
/**
 * Pobiera najnowsze filmy z kanału YouTube (RSS, bez klucza API) i zapisuje
 * je do videos.json obok tego pliku. Uruchamiany cyklicznie przez cron
 * (zobacz README-cron.txt).
 */

$channelId = 'UCIcZQZBpdx7_4X5KC0AQm1Q';
$rssUrl = "https://www.youtube.com/feeds/videos.xml?channel_id={$channelId}";
$outputFile = __DIR__ . '/videos.json';
$maxReleases = 12;

// Old Punks Desperate Riot musi być sprawdzone przed Rude Beat Foundation,
// bo "Rude Beat" bywa częścią nazwy label w tytułach innych projektów.
$artistMap = [
    'Old Punks Desperate Riot' => 'Old Punks Desperate Riot',
    'Old Punks' => 'Old Punks Desperate Riot',
    'Morpheia' => 'Morpheia',
    'Rude Beat Foundation' => 'Rude Beat Foundation',
];

function fetchRss(string $url): string|false
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; RBDSiteBot/1.0)',
        ]);
        $body = curl_exec($ch);
        $ok = $body !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        return $ok ? $body : false;
    }

    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (compatible; RBDSiteBot/1.0)\r\n",
            'timeout' => 15,
        ],
    ]);
    return @file_get_contents($url, false, $context);
}

$xmlString = fetchRss($rssUrl);
if ($xmlString === false) {
    fwrite(STDERR, "Nie udało się pobrać RSS z YouTube.\n");
    exit(1);
}

$xml = @simplexml_load_string($xmlString);
if ($xml === false) {
    fwrite(STDERR, "Nie udało się sparsować RSS.\n");
    exit(1);
}

$releases = [];
foreach ($xml->entry as $entry) {
    $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');
    $videoId = (string) $yt->videoId;
    $link = (string) $entry->link->attributes()->href;
    $title = trim((string) $entry->title);

    if ($videoId === '' || strpos($link, '/shorts/') !== false) {
        continue; // pomijamy Shorts - to nie pełne wydania
    }

    $artist = 'Rude Beat Distro';
    foreach ($artistMap as $needle => $name) {
        if (stripos($title, $needle) !== false) {
            $artist = $name;
            break;
        }
    }

    $releases[] = [
        'artist' => $artist,
        'title' => $title,
        'id' => $videoId,
    ];

    if (count($releases) >= $maxReleases) {
        break;
    }
}

$payload = [
    'updated' => gmdate('c'),
    'releases' => $releases,
];

file_put_contents(
    $outputFile,
    json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

echo "OK: zapisano " . count($releases) . " wydań do videos.json\n";

<?php
    
    if(!isset($_GET['file']))
    {
        exit;
    }

    $file = './data/videos/'. $_GET['file'];

    $mime_types = [
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'ogg'  => 'video/ogg',
        'gif'  => 'image/gif',
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/wav',
        'flac' => 'audio/flac'
    ];
    
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    // Check if file exists
    if (!file_exists($file)) 
    {
        header("HTTP/1.1 404 Not Found");
        exit;
    }

    $size = filesize($file); // File size
    $length = $size; // Content length to be sent
    $start = 0; // Start byte
    $end = $size - 1; // End byte

    // Check if there's a range request in the headers
    if (isset($_SERVER['HTTP_RANGE'])) 
    {
        $range = $_SERVER['HTTP_RANGE'];
        $range = str_replace('bytes=', '', $range);
        $range = explode('-', $range);
        
        $start = (int)$range[0];
        if (isset($range[1]) && $range[1] !== '') {
            $end = (int)$range[1];
        }
        
        $length = $end - $start + 1;
        
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$size");
    } 
    else 
    {
        header('HTTP/1.1 200 OK');
    }

    header('Content-Type: '. $mime_types[$extension]);
    header('Content-Length: ' . $length);
    header('Accept-Ranges: bytes');

    // Open the file and seek to the starting byte
    $fp = fopen($file, 'rb');
    fseek($fp, $start);

    // Stream the file in chunks
    $bufferSize = 1024 * 8;
    while (!feof($fp) && ($pos = ftell($fp)) <= $end) 
    {
        if ($pos + $bufferSize > $end) {
            $bufferSize = $end - $pos + 1;
        }
        echo fread($fp, $bufferSize);
        flush();
    }
    fclose($fp);
    exit;
?>
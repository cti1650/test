<?php

function multi_encode($str,$en='utf8'){
    $min_pos = 99999999999999;//十分に大きな数字
    $from_encoding ='UTF-8';//デフォルト
    foreach(array('UTF-8','SJIS','EUC-JP','ASCII','JIS','ISO-2022-JP') as $charcode){
      if($min_pos > stripos($str,$charcode,0) && stripos($str,$charcode,0)>0){
        $min_pos =  stripos($str,$charcode,0);
        $from_encoding = $charcode;
      }
    }
    return mb_convert_encoding($str, $en, $from_encoding);
}

function txt_cal(){
    date_default_timezone_set('Asia/Tokyo');
    $today = date("YmdHis");
    $txt = <<<EOL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
BEGIN:VEVENT
SUMMARY:test
LOCATION:prace
DESCRIPTION:des
DTSTART:20201030100000
DTEND:20201030100000
END:VEVENT
END:VCALENDAR
EOL;
    return $txt;
}

function txt_prof(){
    $txt = <<<EOL
BEGIN:VCARD
VERSION:3.0
PRODID:-//Apple Inc.//iOS 9.0.1//EN
N:田中;太郎;;;
FN:田中太郎
x-PHONETIC-LAST-NAME:たなかたろう
EMAIL;type=INTERNET;type=WORK;type=pref:samp@mail.com
EMAIL;type=INTERNET;type=HOME;type=pref:samp@mail.com
TEL;type=WORK;type=FAX:090-0000-0000
TEL;type=HOME;type=VOICE:090-0000-0000
TEL;type=CELL;type=VOICE:090-0000-0000
ADR;type=WORK:;;;;東京都;;
NOTE:メモ
END:VCARD
EOL;
    return $txt;
}

function txt_mail($address='',$subject='',$body=''){
    $txt = <<<EOL
mailto:{$address}?subject={$subject}&body={$body}
EOL;
    return $txt;
}

function getTitle($link_url){
    $source = @file_get_contents($link_url);
    if (preg_match('/<title>(.*?)<\/title>/i', mb_convert_encoding($source, 'UTF-8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS'), $result)) {
        $title = $result[1];
    } else {
        $title = $link_url;
    }
    return $title;
}

$data = '';
$data = isset($_GET['text']) ? multi_encode(htmlspecialchars($_GET['text'])):'';
$url = multi_encode(htmlspecialchars($_GET['url']));
$data = isset($_GET['url']) ? 'Title|'.getTitle($url).'||URL|'.$url:$data;

switch (strtoupper($data)){
    case 'TESTCAL':
        $data = txt_cal();
        break;
    case 'TESTPROF':
        $data = txt_prof();
        break;
    case 'TESTMAIL':
        $data = txt_mail('samp@mail.com','動作確認','テスト送信|'.$data);
        break;
    case '{{NOW}}':
    case '{{JST}}':
        $data = 'https://www.nict.go.jp/JST/JST6.html';
        break;
    default:
}
$data = str_replace('|',"\n",$data);

$chld = isset($_GET['chld']) ? htmlspecialchars($_GET['chld']):'';
$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']):'';
$f_name = isset($_GET['name']) ? htmlspecialchars($_GET['name']):'qr';

switch (strtoupper($chld)){
    case 'L':
    case 'M':
    case 'Q':
    case 'H':
        break;
    default:
        $chld = 'M';
}

$url = 'http://chart.googleapis.com/chart?cht=qr&chs=250x250&choe=UTF-8&chld=' . $chld . '&chl='.rawurlencode($data);

$context = stream_context_create(array(
    'http' => array('ignore_errors' => true)
));

$img_data=file_get_contents($url,false,$context);
$scheme='data:application/octet-stream;base64,';
$image_size=getimagesize($scheme . base64_encode($img_data));

switch($image_size["mime"]){
    case 'image/png':
        $original_image = imagecreatefrompng($scheme . base64_encode($img_data));
        break;

    default:
        throw new RuntimeException('画像形式がおかしいみたいです',$size["mime"]);
        exit(0);
}

switch (strtoupper($type)){
    case 'PNG':
        header('Content-Type: image/png');
        header('Content-Disposition: '.(isset($_GET["download"])?"attachment":"inline").'; filename="'.$f_name.'.png"');
        imagepng($original_image);
        break;
    case 'JPG':
    case 'JPEG':
        header('Content-Type: image/jpeg');
        header('Content-Disposition: '.(isset($_GET["download"])?"attachment":"inline").'; filename="'.$f_name.'.jpg"');
        imagejpeg($original_image);
        break;
    case 'BMP':
        header('Content-Type: image/bmp');
        header('Content-Disposition: '.(isset($_GET["download"])?"attachment":"inline").'; filename="'.$f_name.'.bmp"');
        imagebmp($original_image);
        break;
    case 'GIF':
        header('Content-Type: image/gif');
        header('Content-Disposition: '.(isset($_GET["download"])?"attachment":"inline").'; filename="'.$f_name.'.gif"');
        imagegif($original_image);
        break;
    default:
        header('Content-Type: image/png');
        header('Content-Disposition: '.(isset($_GET["download"])?"attachment":"inline").'; filename="'.$f_name.'.png"');
        imagepng($original_image);
}
imagedestroy($original_image);

<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
$loid = required_param('loid', PARAM_RAW); 
$curl = curl_init();

curl_setopt_array($curl, array(
 CURLOPT_URL => "https://api.go1.com/v2/learning-objects/".$loid,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"limit\"\r\n\r\n1\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer ".get_config('mod_goone', 'token'),
    "cache-control: no-cache",
    "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} 

function dejank($jank)
{
//Replace unicode for < and > with < or > and Replace back slash escape character and finally Remove unicode characters
   $jank = preg_replace('/\\\\u[0-9A-F]{4}/i', '', str_replace("\u003C", "<", str_replace("\u003E", ">", str_replace("\/", "/", $jank))));
//Convert to HTML to clean up any invalid HTML jank
   $jank = html_entity_decode($jank);
//Remove opening tags and remove leading and trailing spaces
   $jank = preg_replace('(\s*<[a-z A-Z 0-9]*>\\s*)', '', $jank);
//Replace closing tags and leading and trailing spaces with a single space character
   $jank = preg_replace('(\s*<\/[a-z A-Z 0-9]*>\s*)', ' ', $jank);
//Remove URL's
   $regex = "|https?://[a-z\.0-9]+|i";
// this is optional if you want to remove links $jank = preg_replace($regex,'',$jank);
   return $jank;
}

function convertToHoursMins($time, $format = '%02d:%02d') {
    if ($time < 1) {
        return;
    }
    $hours = floor($time / 60);
    $minutes = ($time % 60);
    return sprintf($format, $hours, $minutes);
}



$data = json_decode($response,true);

$data['has_items'] = !empty($data['items']);

foreach ($data['delivery'] as &$obj) {
   $obj = convertToHoursMins($obj);
}

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/goone/browser.php');
$PAGE->set_pagelayout('embedded');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_goone/modal', $data);
echo $OUTPUT->footer();

<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/goone/lib.php');    
require_login();
$config = get_config('mod_goone');

    $pkeyword = optional_param('keyword','', PARAM_RAW); 
    $pprovider = optional_param('provider','', PARAM_RAW); 
    $planguage = optional_param('language','', PARAM_RAW); 
    $ptag = optional_param('tag','', PARAM_RAW); 
    $pprice = optional_param('price','', PARAM_RAW); 
    $ptype = optional_param('type','', PARAM_RAW); 
    $psub = optional_param('subscribed','',PARAM_RAW);
    $ppage = optional_param('page','', PARAM_RAW); 
    $psort = optional_param('sort','', PARAM_RAW);
    $poffset = optional_param('offset','', PARAM_RAW);

    $limit = 20;

    if (!$ppage) {$ppage = 0;}

if ($config->premiumfilter == 1)
  {$psub = "true";}

if ($config->collectionsfilter == 1) {
  $pcoll = "default";
  $psub = "";}

if ($config->showallfilter == 1) {
  $psub = "";
  $pcoll = "";
}

if ($pkeyword || $ptag || $ptype && !$psort) {
  $psort = "relevance";
}

$data = array (
'keyword' => $pkeyword,
'provider%5B%5D' => $pprovider,
'language%5B%5D' => $planguage,
'tags%5B%5D' => $ptag,
'price%5Bmax%5D' => $pprice,
'type' => $ptype,
'subscribed' => $psub,
'offset' => $poffset,
'collection' => $pcoll,
'sort' => $psort,
'limit' => $limit);
// var_dump($data);die;
foreach($data as $key=>$value)
        if(!$value==''){
                $params .= $key.'='.$value.'&';
         }
        $params = trim($params, '&');

if (!goone_tokentest()) {
  echo $OUTPUT->notification(get_string('connectionerror', 'goone'), 'notifyproblem');
}
$curl = curl_init();

curl_setopt_array($curl, array(
 CURLOPT_URL => "https://api.GO1.com/v2/learning-objects?facets=instance,tag,language&marketplace=all&".$params,
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
$jank = preg_replace('/\\\\u[0-9A-F]{4}/i', '', str_replace("\u003C","<",str_replace("\u003E",">",str_replace("\/","/",$jank))));
//Convert to HTML to clean up any invalid HTML jank
$jank = html_entity_decode($jank);
//Remove opening tags and remove leading and trailing spaces
$jank = preg_replace('(\s*<[a-z A-Z 0-9]*>\\s*)', '', $jank);
//Replace closing tags and leading and trailing spaces with a single space character
$jank = preg_replace('(\s*<\/[a-z A-Z 0-9]*>\s*)', ' ', $jank);
//Replace any tags that contain attributes
$jank = preg_replace('(\s*<[^>]*>\s*)', '', $jank);
// $regex = "|https?://[a-z\.0-9]+|i";
// this is optional if you want to remove links $jank = preg_replace($regex,'',$jank);
   return $jank;
}


$response = json_decode($response,true);
foreach ($response['hits'] as &$obj) {
   $obj['description'] = dejank($obj['description']);
   $obj['pricing']['price'] = '$'.$obj['pricing']['price'];
    if (!empty($obj['subscription']) and ($obj['subscription']['licenses'] === -1 or $obj['subscription']['licenses'] > 0)) {
      $obj['pricing']['price'] = "Included";
    }
    if ($obj['pricing']['price'] === "$0")
      { $obj['pricing']['price'] = "Free";
  }
}


$context = context_system::instance();



echo $OUTPUT->render_from_template('mod_goone/hits', $response);


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

    $limit = 20;

    if (!$ppage) {$ppage = 0;}
//premium
if ($config->filtersel == 1)
  {$psub = "true";}
//collections
if ($config->filtersel == 2) {
  $pcoll = "default";
  $psub = "";}
//showall
if ($config->filtersel == 0) {
  $psub = "";
  $pcoll = "";
}


  $psort = "popularity";


//set default language to users language.
if (!$plang) {
  $plang = $USER->lang;
}
$data = array (
'keyword' => $pkeyword,
'provider%5B%5D' => $pprovider,
'language%5B%5D' => $planguage,
'tags%5B%5D' => $ptag,
'price%5Bmax%5D' => $pprice,
'type' => $ptype,
'subscribed' => $psub,
'offset' => ($ppage * $limit),
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
 CURLOPT_URL => "https://api.GO1.com/v2/learning-objects?facets=instance,tag,language&marketplace=all&sort=relevance&".$params,
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

$curl = curl_init();

curl_setopt_array($curl, array(
 CURLOPT_URL => "https://api.GO1.com/v2/learning-objects?facets=instance,tag,language&limit=0",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer ".get_config('mod_goone', 'token'),
    "cache-control: no-cache",
    "content-type: application/x-www-form-urlencoded"
  ),
));

$facets = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);


if ($err) {
  echo "cURL Error #:" . $err;
} 

function dejank($jank) {

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
$facets = json_decode($facets,true);
$response['facets'] = $facets['facets'];
foreach ($response['hits'] as &$obj) {
   $obj['description'] = dejank($obj['description']);
}


class string_manager {

    private $manager; private $languages;

    public function __construct($config = null) { $this->manager =
    get_string_manager(); $this->languages =
    $this->manager->get_list_of_languages(); }

    public function get_language($lang) { if (array_key_exists($lang,
    $this->languages)) { return $this->languages[$lang]; } if (strpos($lang,
    '-') > 0) { list($langcode, $countrycode) = explode('-', $lang, 2); if
    (array_key_exists($langcode, $this->languages)) { $string =
    $this->languages[$langcode]; $countrycode =
    clean_param(strtoupper($countrycode), PARAM_STRINGID); if
    ($this->manager->string_exists($countrycode, 'core_countries')) { return
    $string . " (" . get_string($countrycode, 'core_countries') . ")"; } } }
    if (empty($lang)) { return get_string('unknownlanguage',
    'contentmarketplace_goone'); } return $lang; }

    public function get_region($region) { if (empty($region)) { return ''; }
    $identifier = 'region:' . clean_param($region, PARAM_STRINGID); if
    ($this->manager->string_exists($identifier, 'mod_goone')) {
    return get_string($identifier, 'mod_goone'); } return
    $region; }

}

$stringmanager = new string_manager();
foreach ($response['facets']['language']['buckets'] as &$obj) {
   $obj['name'] = $stringmanager->get_language($obj['key']);
   if ($obj['key'] == $plang) {
   $obj['selected'] = "selected";
  }
}

  $response['msort'] = "selected";



$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/goone/browser.php');
$PAGE->set_pagelayout('embedded');
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/goone/js/browser.js'));
$PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/goone/js/bootstrap-multiselect.js'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/goone/css/bootstrap-multiselect.css'));

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_goone/browser', $response);
echo $OUTPUT->footer(); 
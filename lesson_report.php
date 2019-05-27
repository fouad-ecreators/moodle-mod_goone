<?php 

require_once('../../config.php');
require_once('lib.php');

define('COMPLETION_REPORT_PAGE', 25);

global $CFG, $DB, $OUTPUT, $PAGE;

//Get course
$cmid   = required_param('id', PARAM_INT);  // Course Module ID   
$cm     = get_coursemodule_from_id('goone', $cmid, 0, false, MUST_EXIST); 
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);   
$context = context_course::instance($course->id);

if (!$cm = get_coursemodule_from_id('goone', $cmid)) {
    print_error(get_string('cmidincorrect','goone'));
}
if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
    print_error(get_string('courseincorrect','goone')); 
}
if (!$goone = $DB->get_record('goone', array('id'=> $cm->instance))) {
    print_error(get_string('cmincorrect','goone')); 
}


// Sort (default lastname, optionally firstname)
$sort = optional_param('sort','',PARAM_ALPHA);
$firstnamesort = $sort == 'firstname';

// Paging
$start   = optional_param('start', 0, PARAM_INT);
$sifirst = optional_param('sifirst', 'all', PARAM_NOTAGS);
$silast  = optional_param('silast', 'all', PARAM_NOTAGS);
$start   = optional_param('start', 0, PARAM_INT);

// Whether to show extra user identity information
$extrafields = get_extra_user_fields($context);
$leftcols = 1 + count($extrafields);

$url = new moodle_url('/mod/goone/lesson_report.php', array('id' => $cm->id));
if ($sort !== '') {
    $url->param('sort', $sort);
}
if ($start !== 0) {
    $url->param('start', $start);
}
if ($sifirst !== 'all') {
    $url->param('sifirst', $sifirst);
}
if ($silast !== 'all') {
    $url->param('silast', $silast);
}

$PAGE->set_url($url); 
$PAGE->set_pagelayout('report');

require_login($course, true, $cm);

// Check basic permission
require_capability('report/progress:view',$context);

// Get group mode
$group = groups_get_course_group($course,true); // Supposed to verify group
if ($group===0 && $course->groupmode==SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups',$context);
}

// Get data on activities and progress of all users, and give error if we've
// nothing to display (no users or no activities)
$completion = new completion_info($course);
$activities = $completion->get_activities();
$lessons = getLessonsBygooneID($goone->id);


if ($sifirst !== 'all') {
    set_user_preference('ifirst', $sifirst);
}
if ($silast !== 'all') {
    set_user_preference('ilast', $silast);
}

if (!empty($USER->preference['ifirst'])) {
    $sifirst = $USER->preference['ifirst'];
} else {
    $sifirst = 'all';
}

if (!empty($USER->preference['ilast'])) {
    $silast = $USER->preference['ilast'];
} else {
    $silast = 'all';
}

// Generate where clause
$where = array();
$where_params = array();

if ($sifirst !== 'all') {
    $where[] = $DB->sql_like('u.firstname', ':sifirst', false);
    $where_params['sifirst'] = $sifirst.'%';
}

if ($silast !== 'all') {
    $where[] = $DB->sql_like('u.lastname', ':silast', false);
    $where_params['silast'] = $silast.'%';
}

// Get user match count
$total = $completion->get_num_tracked_users(implode(' AND ', $where), $where_params, $group);

// Total user count
$grandtotal = $completion->get_num_tracked_users('', array(), $group);

// Get user data
$progress = array();

if ($total) {
    $progress = $completion->get_progress_all(
        implode(' AND ', $where),
        $where_params,
        $group,
        $firstnamesort ? 'u.firstname ASC, u.lastname ASC' : 'u.lastname ASC, u.firstname ASC',
        COMPLETION_REPORT_PAGE,
        $start,
        $context
    );
}

// Navigation and header
$PAGE->set_title('Report: '. $goone->name); 
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header(); 
$PAGE->requires->js_call_amd('report_progress/completion_override', 'init', [fullname($USER)]);

// Handle groups (if enabled)
groups_print_course_menu($course,$CFG->wwwroot.'/report/progress/?course='.$course->id);?>


<style>
    .activity-navigation{ display: none; }
</style>

<?php

if (count($lessons)==0) {
    echo $OUTPUT->container(get_string('err_noactivities', 'completion'), 'errorbox errorboxcontent'); //Edit
    echo $OUTPUT->footer();
    exit;
}

// If no users in this course what-so-ever
if (!$grandtotal) {
    echo $OUTPUT->container(get_string('err_nousers', 'completion'), 'errorbox errorboxcontent'); //Edit
    echo $OUTPUT->footer();
    exit;
}

// Build link for paging
$link = $CFG->wwwroot.'/mod/goone/?lesson_report='.$cmid;
if (strlen($sort)) {
    $link .= '&amp;sort='.$sort;
}
$link .= '&amp;start=';

$pagingbar = '';

// Initials bar.
$prefixfirst = 'sifirst';
$prefixlast = 'silast';
$pagingbar .= $OUTPUT->initials_bar($sifirst, 'firstinitial', get_string('firstname'), $prefixfirst, $url);
$pagingbar .= $OUTPUT->initials_bar($silast, 'lastinitial', get_string('lastname'), $prefixlast, $url);

// Do we need a paging bar?
if ($total > COMPLETION_REPORT_PAGE) {

    // Paging bar
    $pagingbar .= '<div class="paging">';
    $pagingbar .= get_string('page').': ';

    $sistrings = array();
    if ($sifirst != 'all') {
        $sistrings[] =  "sifirst={$sifirst}";
    }
    if ($silast != 'all') {
        $sistrings[] =  "silast={$silast}";
    }
    $sistring = !empty($sistrings) ? '&amp;'.implode('&amp;', $sistrings) : '';

    // Display previous link
    if ($start > 0) {
        $pstart = max($start - COMPLETION_REPORT_PAGE, 0);
        $pagingbar .= "(<a class=\"previous\" href=\"{$link}{$pstart}{$sistring}\">".get_string('previous').'</a>)&nbsp;';
    }

    // Create page links
    $curstart = 0;
    $curpage = 0;
    while ($curstart < $total) {
        $curpage++;

        if ($curstart == $start) {
            $pagingbar .= '&nbsp;'.$curpage.'&nbsp;';
        } else {
            $pagingbar .= "&nbsp;<a href=\"{$link}{$curstart}{$sistring}\">$curpage</a>&nbsp;";
        }

        $curstart += COMPLETION_REPORT_PAGE;
    }

    // Display next link
    $nstart = $start + COMPLETION_REPORT_PAGE;
    if ($nstart < $total) {
        $pagingbar .= "&nbsp;(<a class=\"next\" href=\"{$link}{$nstart}{$sistring}\">".get_string('next').'</a>)';
    }

    $pagingbar .= '</div>';
}

// Start of table
print '<br class="clearer"/>'; // ugh

print $pagingbar;

if (!$total) {
    echo $OUTPUT->heading(get_string('nothingtodisplay'));
    echo $OUTPUT->footer();
    exit;
}

print '<div id="lesson-progress-wrapper" class="no-overflow">';
print '<table id="lesson-progress" class="generaltable flexible boxaligncenter" style="text-align:left"><thead><tr style="vertical-align:top">';

// User heading / sort option
print '<th scope="col" class="completion-sortchoice">';

$sistring = "&amp;silast={$silast}&amp;sifirst={$sifirst}";

if ($firstnamesort) {
    print
        get_string('firstname')." / <a href=\"./lesson_report.php?id={$cmid}&course={$course->id}{$sistring}\">".
        get_string('lastname').'</a>';
} else {
    print "<a href=\"./lesson_report.php?id={$cmid}&course={$course->id}&amp;sort=firstname{$sistring}\">".
        get_string('firstname').'</a> / '.
        get_string('lastname');
}
print '</th>';

// Print user identity columns
foreach ($extrafields as $field) {
    echo '<th scope="col" class="completion-identifyfield">' .
            get_user_field_name($field) . '</th>';
}

// Lessons
$formattedactivities = array();
foreach($lessons as $lesson) {
    
    // Some names (labels) come URL-encoded and can be very long, so shorten them
    $displayname = format_string($lesson->lesson_name, true);
    $shortenedname = shorten_text($displayname); //With elipsis
        
        print '<th scope="col" style="font-weight: 400;">'.
        '<a href="'.$CFG->wwwroot.'/mod/'.$cm->modname.'/view.php?id='.$cmid.'" title="' . s($displayname) . '">'.
            '<div><span>'.$OUTPUT->image_icon('icon', get_string('modulename', $cm->modname), $cm->modname) .' '. $displayname.'</span></div>'.
        '</a>';

        print '</th>';

    $formattedactivities[$lesson->lesson_id] = (object)array(
        'displayname' => $displayname,
        'lessonid' => $lesson->lesson_id,
        'gooneid' => $lesson->goone_id
    );
}

//Activity Grade
// print '<th scope="col" style="font-weight: 400;">Activity Grade from Gradebook</th>';

print '</tr></thead><tbody>';

// Row for each user
foreach($progress as $user) {
    // User name
    print '<tr><th scope="row"><a href="'.$CFG->wwwroot.'/user/view.php?id='.
    $user->id.'&amp;course='.$course->id.'">'.fullname($user).'</a></th>';
    foreach ($extrafields as $field) {
        echo '<td>' . s($user->{$field}) . '</td>';
    }

    foreach ($formattedactivities as $a) {

        $user->activity_id = $a->gooneid;
        $user->lesson_id   = $a->lessonid;
        $grade = getLessonGrade($user);

        echo '<td>' . s($grade) . '%</td>';
    }

    // $activityGrade = getUserActivityGradeByUserId($user->activity_id, $user->id);
    // echo '<td>' . $activityGrade . '</td>';
   
    print '</tr>';
}

print '</tbody></table>';
print '</div><hr><br/>';
print $pagingbar;

echo $OUTPUT->footer();

?>


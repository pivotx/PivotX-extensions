<?php
// - Extension: Entry Calendar
// - Version: 0.10
// - Author: PivotX Team/Khevor/Kay Hermann
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A widget to display a calendar with your PivotX entries.
// - Date: 2009-11-17
// - Identifier: calendar
// - Required PivotX version: 2.0.2

global $calendar_config;

$calendar_config = array(
    'calendar_folder'   => "calendar",
    'week_start_day'   => 1
);

/**
 * Adds the hook for calendarAdmin()
 *
 * @see calendarAdmin()
 */
$this->addHook(
    'configuration_add',
    'calendar',
    array("calendarAdmin", "Calendar")
);

/**
 * Adds the hook for the actual widget. We just use the same
 * as the snippet, in this case.
 *
 * @see smarty_calendar()
 */
if (!getDefault($PIVOTX['config']->get('calendar_snippet_only'), false)) {
    $this->addHook(
        'widget',
        'calendar',
        "smarty_calendar"
    );
}

/**
 * Add the calendar.css to the header..
 */
$this->addHook(
    'after_parse',
    'insert_before_close_head',
    "<!-- calendar -->
    <link href='[[pivotx_dir]]extensions/calendar/calendar.css' rel='stylesheet' type='text/css' />
    <script type='text/javascript'>
    // <![CDATA[
    function showCalendarDiv(dday) {
        var divDay = 'day' + dday;
        document.getElementById('dlCalendarEntries').style.display='block';
        document.getElementById('dlCalendarEntries').innerHTML = ' ';
        document.getElementById('dlCalendarEntries').innerHTML = document.getElementById(divDay).innerHTML;
    }
    // ]]>
    </script>"
);

// Register 'calendar' as a smarty tag.
$PIVOTX['template']->register_function('calendar', 'smarty_calendar');

/**
 * Output
 */
function smarty_calendar($params) {
    global $PIVOTX, $calendar_config;

    $params = cleanParams($params);

    $iWeekStart = getDefault($PIVOTX['config']->get('week_start_day'), $calendar_config['week_start_day'], true);
    $iTimeZone = getDefault($PIVOTX['config']->get('timeoffset'),0);
    $iTimeZoneUnit = $PIVOTX['config']->get('timeoffset_unit');

    // Translate TimeZone Unit to full English
    switch ($iTimeZoneUnit) {
        case "i":
            $iTZU = " minutes";
            break;
        case "d":
            $iTZU = " days";
            break;
        case "m":
            $iTZU = " months";
            break;
        case "y":
            $iTZU = " years";
            break;
        default:
            $iTZU = " hours";
    }

    // Get the current date
    $aStoredDate = getdate(strtotime($iTimeZone . $iTZU));

    // Were the month and year passed and if so, change the $aCurrentDate to that month/year
    //   unless it matches the current month and year, then use the date stored in $aStoredDate.
    if (isset($_REQUEST['m'])) {
        $sCalledMonth = $_REQUEST['m'];
        $bMonOk = preg_match("/^[0-9]{1,2}$/", $sCalledMonth) > 0 ? true : false;
        if ($sCalledMonth < 1 || $sCalledMonth > 13) { $bMonOk = false; }
    }

    if (isset($_REQUEST['y'])) {
        $sCalledYear = $_REQUEST['y'];
        $bYearOk = preg_match("/^[0-9]{4}$/", $sCalledYear) > 0 ? true : false;
    }

    if ($bMonOk && $bYearOk) {
        if ( $sCalledMonth == $aStoredDate['mon'] && $sCalledYear == $aStoredDate['year']) {
            $aCurrentDate = $aStoredDate;
        } else {
            $aCurrentDate = getdate(mktime(0, 0, 0, $sCalledMonth, 1, $sCalledYear));
        }
    } else {
        $aCurrentDate = $aStoredDate;
    }

    $thisMonth = $aCurrentDate['mon'];
    $thisYear = $aCurrentDate['year'];

    $iNumDays = getDaysInMonth($thisMonth,$thisYear);
    $iStartDay = date("w", mktime(0, 0, 0, $thisMonth, 1, $thisYear));

    if ($iStartDay == 0) $iStartDay = 7;

    $j = $iStartDay;

    $aMonth = array_fill(1, $iNumDays, 0);

    for($i = 1; $i < $iNumDays + 1; $i++) {
        if ($j > 7) { $j = 1; }
        $aMonth[$i] = $j;
        $j++;
    }

    /******************************
     *  Get Entries For the Month  *
     ******************************/

    $aParams = array ("date" => $thisYear.'-'.$thisMonth);
    $aParams['status'] = getDefault($params['status'], "publish");
    $category = getDefault($params['category'], $PIVOTX['config']->get('calendar_category'));
    if (!empty($category)) {
        $aParams['cats'] = array_map('trim', explode(",", $category));
    }
    if (isset($params['user'])) {
        $aParams['user'] = array_map('trim', explode(",", $params['user']));
    }

    $aListEntries = $PIVOTX['db']->read_entries($aParams);

    /******************************
     * Start building the calendar *
     ******************************/
    // Build variables for previous and next month links
    $prevMonth = $thisMonth - 1;
    $prevYear = $thisYear;
    if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

    $nextMonth = $thisMonth + 1;
    $nextYear = $thisYear;
    if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

    $output = <<<EOM
<a name="dlCName"></a>
<div class="dlCalendarTable">
<table class="dlCalendar">
\t<tbody>
\t<tr>
\t\t<th class="previous"><a href="%prev_month_link%#dlCName" rel="nofollow" title="%prev_month%">&laquo;</a></th>
\t\t<th colspan="5" class="dlCalendarHead">%thisMonthName% $thisYear</th>
\t\t<th class="next"><a href="%next_month_link%#dlCName" rel="nofollow" title="%next_month%">&raquo;</a></th>
\t</tr>
\t<tr class="dlCalendarSubHead">
EOM;
    $output = str_replace("%thisMonthName%", $PIVOTX['locale']->getMonth($thisMonth), $output);
    $output = str_replace("%prev_month%", __("Previous Month"), $output);
    $output = str_replace("%next_month%", __("Next Month"), $output);
    $link = smarty_self();
    // Remove earlier date parameters from link
    $link = preg_replace('/(\?|&amp;)m=\d{1,2}&amp;y=\d{4}/', '', $link);
    if (strpos($link, '?') == false) {
        $link .= '?';
    } else {
        $link .= '&amp;';
    }
    $output = str_replace("%prev_month_link%", $link . "m=$prevMonth&amp;y=$prevYear", $output);
    $output = str_replace("%next_month_link%", $link . "m=$nextMonth&amp;y=$nextYear", $output);

    $j = $iWeekStart;
    for ($i = 0; $i < 7; $i++) {
        $day = $PIVOTX['locale']->getWeekday($j);
        $output .= "\t\t<td>".$PIVOTX['locale']->getWeekdayInitial($day)."</td>\n";
        $j++;
        if ($j==7) { $j = 0; }
    }
    if ($iWeekStart == 0) $iWeekStart = 7;

    /******************************
     *  Build the actual calendar  *
     ******************************/

    // Insert blank days for the first week if necessary.
    if ($iWeekStart <= $iStartDay) {
        $iBlankDays = $iStartDay - $iWeekStart;
    } else {
        $iBlankDays = 7 - ($iWeekStart - $iStartDay);
    }

    if ($iBlankDays > 0) {
        $output .= "\t</tr>\n\t<tr>\n";
        for ($i = 0; $i < $iBlankDays; $i++) {
            $output .= "\t\t<td>&nbsp;</td>\n";
        }
    }

    // Insert links for the published entries during the iteration of days.
    $aDivInfo = array_fill(1, $iNumDays, "");
    for ($i = 1; $i < $iNumDays + 1; $i++) {
        if ($aMonth[$i] == $iWeekStart) {
            $output .= "\t</tr>\n\t<tr>\n";
        }

        $dNow = date("Y-m-d", mktime(23, 59, 59, $aCurrentDate['mon'], $i, $aCurrentDate['year']));
        $dStoredNow = date("Y-m-d", mktime(23, 59, 59, $aStoredDate['mon'], $aStoredDate['mday'], $aStoredDate['year']));

        $bCounter = false;
        $sDivInfo = "";
        foreach ($aListEntries as $a) {
            if ( $dNow == substr($a['publish_date'], 0, 10)) {
                $sDivInfo .= '<li><a href="' . $a['link'] . '" title="' . $a['title'] . '">' . $a['title'] . '</a></li>';
                $bCounter = true;
            }
        }

        if ($bCounter) {
            if ($dNow == $dStoredNow) {
                $output .= "\t\t<td class=\"dlCalendarCurrentDay\"><a class=\"dlCalendarDivLink\" onclick=\"showCalendarDiv(" . $i . ");\">" . $i . "</a></td>\n";
            } else {
                $output .= "\t\t<td><a class=\"dlCalendarDivLink\" onclick=\"showCalendarDiv(" . $i . ");\">" . $i . "</a></td>\n";
            }
        } else {
            if ($dNow == $dStoredNow) {
                $output .= "\t\t<td class=\"dlCalendarCurrentDay\">" . $i . "</td>\n";
            } else {
                $output .= "\t\t<td>" . $i . "</td>\n";
            }
        }

        $aDivInfo[$i] = $sDivInfo;
    }

    // Insert blank days for the last week if necessary.
    $iEndDay = $aMonth[$iNumDays];
    $iWeekEnd = $iWeekStart - 1;
    if ($iWeekEnd < 1 ) { $iWeekEnd = 7; }
    if ($iEndDay <= $iWeekEnd) {
        $iBlankDays = $iWeekEnd - $iEndDay;
    } else {
        $iBlankDays = 7 - ($iEndDay - $iWeekEnd);
    }

    for ($i = 0; $i < $iBlankDays; $i++ ) {
        $output .= "\t\t<td>&nbsp;</td>\n";
    }

    // Close off the calendar construction
    $output .= "\t</tr>\n";
    $output .= "\t</tbody>\n</table>\n";

    // Build the Entry Div (where the entry titles are to be displayed)
    $output .= "<div id=\"dlCalendarEntries\">&nbsp;</div>\n";

    // Build sub Divs where the all entries for that month are hidden
    //   for use with the javascript.
    for ($i = 1; $i < $iNumDays + 1; $i++) {
        if ($aDivInfo[$i] != "" ) {
            $output .= "<div id=\"day" . $i . "\" style=\"display:none;\"><ul>" . $aDivInfo[$i] . "</ul></div>\n";
        }
    }

    // Close the Entry Div.
    $output .= "</div>\n";

    // Output the calendar
    return $output;
}

/**
 * The configuration screen for calendar
 *
 * @param unknown_type $form_html
 */
function calendarAdmin(&$form_html) {
    global $PIVOTX, $calendar_config;

    $form = $PIVOTX['extensions']->getAdminForm('calendar');

    $days = array();
    for ($i = 0; $i < 7; $i++) {
        $days[$i] = $PIVOTX['locale']->getWeekday($i);
    }

    $form->add( array(
        'type' => 'select',
        'name' => 'week_start_day',
        'label' => __("Start day of the week"),
        'error' => __("Error"),
        'text' => makeJtip(__('Start day of the week'), 
            __("Select the day of the week you wish the calendar to start on.")),
        'firstoption' => __('Select'),
        'options' => $days,
        'isrequired' => 1,
        'validation' => 'any'
    ));

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['calendar'] = $PIVOTX['extensions']->getAdminFormHtml($form, $calendar_config);

}

?>

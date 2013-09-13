<?php
// - Extension: Google Calendar
// - Version: 1.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A widget and snippet to display events from one or more Google Calendars.
// - Date: 2013-09-13
// - Identifier: googlecalendar
// - Required PivotX version: 2.1.0

global $googlecalendar_config;

$googlecalendar_config = array(
    'googlecalendar_id' => "",
    'googlecalendar_max_items' => 8,
    'googlecalendar_futureevents' => 0,
    'googlecalendar_orderby' => 'starttime',
    'googlecalendar_sortorder' => 'a',
    'googlecalendar_style' => 'widget-lg',
    'googlecalendar_header' => "<p><strong>" . __('My Calendar') . "</strong></p>\n<ul>",
    'googlecalendar_footer' => "<ul>",
    'googlecalendar_format' => "<li><a href=\"%link%\">%title%</a> - <span class='date'>%date_start%-%date_end%</span></li>",
    'googlecalendar_format_date_start' => "%day%-%month%-'%ye% %hour24%:%minute%",
    'googlecalendar_format_date_end' => "%hour24%:%minute%",
    'googlecalendar_only_snippet' => 0,
);

/**
 * Adds the hook for googlecalendarAdmin()
 *
 * @see googlecalendarAdmin()
 */
$this->addHook(
    'configuration_add',
    'googlecalendar',
    array("googlecalendarAdmin", "Google Calendar")
);

/**
 * Adds the hook for the actual widget. We just use the same
 * as the snippet, in this case.
 *
 * @see smarty_googlecalendar()
 */
$this->addHook(
    'widget',
    'googlecalendar',
    'widget_googlecalendar'
);

// Register 'googlecalendar' as a smarty tag.
$PIVOTX['template']->register_function('googlecalendar', 'smarty_googlecalendar');

/**
 * Outputs the events from a Google Calendar feed as a widget
 *
 * @return string
 */
function widget_googlecalendar($params) {
    global $PIVOTX, $googlecalendar_config;

    $key = 'googlecalendar_only_snippet';
    $disabled = getDefault($PIVOTX['config']->get($key), $googlecalendar_config[$key]);
    if ($disabled) {
        return;
    } else {
        $output = smarty_googlecalendar($params);
        return $output;
    }

}

/**
 * Outputs the events from a Google Calendar feed
 *
 * @param array $params
 * @return string
 */
function smarty_googlecalendar($params) {
    global $googlecalendar_config, $PIVOTX;

    $params = clean_params($params);

    $calendar_vars = array('id', 'max_items', 'futureevents', 'orderby', 'sortorder');
    $query = array();
    foreach ($calendar_vars as $var) {
        if (isset($params[$var])) {
            $query[] = "$var=" . $params[$var];
        }
    }
    $query = implode('&', $query);

    if (isset($params['style'])) {
        $style = $params['style'];
    } else {
        $style = getDefault($PIVOTX['config']->get('googlecalendar_style'), $googlecalendar_config['googlecalendar_style']);
    }

    $output = $PIVOTX['extensions']->getLoadCode('defer_file', "googlecalendar/calendar.php?$query", $style);

    return $output;

}

/**
 * The configuration screen for Google Calendar
 *
 * @param unknown_type $form_html
 */
function googlecalendarAdmin(&$form_html) {
    global $form_titles, $googlecalendar_config, $PIVOTX;

    $form = $PIVOTX['extensions']->getAdminForm('googlecalendar');

    $form->add( array(
        'type' => 'text',
        'name' => 'googlecalendar_id',
        'label' => __('ID'),
        'value' => '',
        'error' => __('That\'s not a proper ID!'),
        'text' => __("The ID of your <a href='http://www.google.com/calendar/'>Google Calendar</a>. This is your Gmail e-mail address or something like ". 
            "<tt>d8a49s3jgekpep0p1eiv7pueo4@group.calendar.google.com</tt>. You'll find the ID in the 'Calendar Address' box on the 'Calendar Details' tab " . 
            "when you have selected a specific calendar on the settings page. If you want to merge multiple calendars, just separate the IDs with a comma."),
        'size' => 60,
        'isrequired' => 1,
        'validation' => 'string|minlen=5|maxlen=80'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'googlecalendar_max_items',
        'label' => __('Maximum items'),
        'value' => '',
        'error' => __('That\'s not a proper value!'),
        'text' => __("The maximum amount of items to show from your Calendar."),
        'size' => 5,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=60'
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'googlecalendar_futureevents',
        'label' => __('Only include future events'),
        'value' => '',
        'options' => array( 0 => __('No'), 1 => __('Yes')),
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'googlecalendar_orderby',
        'label' => __('Sort items by'),
        'value' => '',
        'options' => array( 'starttime' => __('Start time'), 'lastmodified' => __('Updated time')),
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'googlecalendar_sortorder',
        'label' => __('Order items'),
        'value' => '',
        'options' => array( 'a' => __('Ascending'), 'd' => __('Descending')),
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'googlecalendar_style',
        'label' => __('Widget Style'),
        'value' => '',
        'options' => getDefaultWidgetStyles(),
        'error' => __('That\'s not a proper style!'),
        'text' => __('Select the style to use for this widget.'),

    ));


    $form->add( array(
        'type' => 'textarea',
        'name' => 'googlecalendar_header',
        'label' => __('Header format'),
        'error' => __('Error!'),
        'size' => 20,
        'cols' => 70,
        'rows' => 3,
        'validation' => 'ifany|string|minlen=2|maxlen=4000'
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'googlecalendar_format',
        'label' => __('Output format'),
        'error' => __('Error!'),
        'size' => 20,
        'cols' => 70,
        'rows' => 5,
        'validation' => 'string|minlen=2|maxlen=4000'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'googlecalendar_format_date_start',
        'label' => __('Format for start date'),
        'error' => __('Error!'),
        'size' => 60,
        'validation' => 'string|minlen=2|maxlen=60',
        'text' => __('Define the start date format using the standard PivotX ' . 
            '<a href="http://book.pivotx.net/page/app-d">date formatting options</a>.'),
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'googlecalendar_format_date_end',
        'label' => __('Format for end date'),
        'error' => __('Error!'),
        'size' => 60,
        'validation' => 'string|minlen=2|maxlen=60',
        'text' => __('Define the end date format using the standard PivotX ' . 
            '<a href="http://book.pivotx.net/page/app-d">date formatting options</a>.'),
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'googlecalendar_footer',
        'label' => __('Footer format'),
        'error' => __('Error!'),
        'size' => 20,
        'cols' => 70,
        'rows' => 3,
        'validation' => 'ifany|string|minlen=2|maxlen=4000'
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'googlecalendar_only_snippet',
        'label' => __("Use only as snippet"),
        'text' => sprintf(__("Yes, I don't want %s to appear among the widgets."), "Google Calender")
    ));

    $form->use_javascript(true);

    /**
     * Add the form to our (referenced) $form_html. Make sure you use the same key
     * as the first parameter to $PIVOTX['extensions']->getAdminForm
     */
    $form_html['googlecalendar'] = $PIVOTX['extensions']->getAdminFormHtml($form, $googlecalendar_config);


}


?>

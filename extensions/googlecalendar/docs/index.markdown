
Google Calendar
====================

This extension enables you to include items/events from one or more Google 
Calendars. After enabling this extension, you can configure it in the
administration screen at Extensions &raquo; Configure Extensions &raquo;
Google Calendar. The configuration sets the default values and the values 
used by the widget. The output format can use the following formatting tags:

* <tt>%author%</tt>
* <tt>%where%</tt>
* <tt>%title%</tt>
* <tt>%link%</tt>
* <tt>%description%</tt>
* <tt>%date_start%</tt>
* <tt>%date_end%</tt>
 
You can also include a Google Calendar anywhere in a template or in a page or
entry using 

    [[ googlecalendar id="username@gmail.com" ]]

In addition to the id parameter, there are 7 optional parameters:

* <tt>max_items</tt> - the number of items/events to display.
* <tt>futureevents</tt> - set to 1 or 0 to indicate whether the
    calendar should only include future events.
* <tt>orderby</tt> - order the items/events by when they start or when they
    have been updated. Legal values: starttime (default) and lastmodified.
* <tt>sortorder</tt> - whether the items/events are sort in ascending or
    descending order. Legal values: ascending (default) and descending.
* <tt>style</tt> - the style applied to the div with the calendar items.
* <tt>format\_date_start</tt> - format for the start date.
* <tt>format\_date_end</tt> - format for the end date

Some examples
-------------

All coming events listed with first event first:

    [[ googlecalendar id="username@gmail.com" futureevents=1 ]]

All coming events listed with the last updated first:

    [[ googlecalendar id="username@gmail.com" 
            futureevents=1 orderby=lastmodified sortorder=descending ]]

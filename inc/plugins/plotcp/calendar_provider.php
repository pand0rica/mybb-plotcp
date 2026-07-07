<?php
/**
 * plotcp – RPG Calendar provider
 *
 * Handler for the rpgcalendar_collect_events hook. Contract (see rpgcalendar
 * README): one indexed query per month render, titles RAW (the consumer
 * escapes on render), over-delivery outside the month is fine, malformed
 * entries are silently discarded by the consumer. Archived plots stay in the
 * calendar on purpose – they are historical events. Previews (visible = 0)
 * are never delivered.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function plotcp_collect_calendar_events(array &$args): void
{
    global $db;

    $year  = (int)$args['year'];
    $month = (int)$args['month'];

    $month_start = $year * 10000 + $month * 100 + 1;
    $month_end   = $year * 10000 + $month * 100 + 31;

    $query = $db->simple_select(
        'plotcp_plots',
        'pid, title, start_year, start_month, start_day, end_year, end_month, end_day',
        'has_date = 1 AND visible = 1 AND start_key <= ' . $month_end . ' AND end_key >= ' . $month_start
    );

    while ($plot = $db->fetch_array($query)) {
        $args['events'][] = [
            'id'        => 'plot-' . (int)$plot['pid'],
            'source'    => 'plot',
            'title'     => (string)$plot['title'],
            'url'       => 'plotcp.php?action=view&pid=' . (int)$plot['pid'],
            'start'     => [
                'y' => (int)$plot['start_year'],
                'm' => (int)$plot['start_month'],
                'd' => (int)$plot['start_day'],
            ],
            'end'       => [
                'y' => (int)$plot['end_year'],
                'm' => (int)$plot['end_month'],
                'd' => (int)$plot['end_day'],
            ],
            'important' => true,
            'badge'     => 'plot',
        ];
    }
}

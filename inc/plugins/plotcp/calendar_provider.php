<?php
/**
 * plotcp – RPG Calendar provider
 *
 * Handlers for the rpgcalendar hooks. Contract (see rpgcalendar README): one
 * indexed query per month render, titles RAW (the consumer escapes on render),
 * over-delivery outside the month is fine, malformed entries are silently
 * discarded by the consumer. Previews (visible = 0) are never delivered.
 *
 * Each plot category is exposed as one virtual sub-calendar 'plotcat-{cid}',
 * both on the events (colour/grouping) and calendars (My-calendars list) hooks.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Stable default colour per category (users override it per-user). */
function plotcp_category_color(int $cid): string
{
    $palette = ['#a855f7', '#ec4899', '#f97316', '#14b8a6', '#6366f1', '#ef4444', '#84cc16', '#0ea5e9'];

    return $palette[$cid % count($palette)];
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
        'pid, title, cid, start_year, start_month, start_day, end_year, end_month, end_day',
        'has_date = 1 AND visible = 1 AND start_key <= ' . $month_end . ' AND end_key >= ' . $month_start
    );

    while ($plot = $db->fetch_array($query)) {
        $cid = (int)$plot['cid'];
        $args['events'][] = [
            'id'            => 'plot-' . (int)$plot['pid'],
            'source'        => 'plot',
            'title'         => (string)$plot['title'],
            'url'           => 'plotcp.php?action=view&pid=' . (int)$plot['pid'],
            'start'         => [
                'y' => (int)$plot['start_year'],
                'm' => (int)$plot['start_month'],
                'd' => (int)$plot['start_day'],
            ],
            'end'           => [
                'y' => (int)$plot['end_year'],
                'm' => (int)$plot['end_month'],
                'd' => (int)$plot['end_day'],
            ],
            'important'     => true,
            'badge'         => 'plot',
            // One virtual sub-calendar per plot category
            'calendar_key'  => 'plotcat-' . $cid,
            'default_color' => plotcp_category_color($cid),
        ];
    }
}

/**
 * Exposes each plot category as a virtual sub-calendar for the rpgcalendar
 * "My calendars" page (hook 'rpgcalendar_collect_calendars').
 */
function plotcp_collect_calendars(array &$args): void
{
    global $db;

    $query = $db->simple_select('plotcp_categories', 'cid, name', '',
        ['order_by' => 'display_order', 'order_dir' => 'ASC']);

    while ($cat = $db->fetch_array($query)) {
        $cid = (int)$cat['cid'];
        $args['calendars'][] = [
            'key'           => 'plotcat-' . $cid,
            'name'          => (string)$cat['name'],
            'default_color' => plotcp_category_color($cid),
        ];
    }
}

<?php
/**
 * plotcp – CLI self test (test server only).
 *
 * Run from the MyBB root: php tests/cli-selftest.php
 * Covers the calendar math, date validation fixpoints, the group diff logic
 * and the alert recipient dedupe with throwaway SELFTEST fixtures (cleaned up
 * at the end). Not part of a release zip.
 */

if (PHP_SAPI !== 'cli') {
    die('CLI only.');
}

define('IN_MYBB', 1);
chdir(dirname(__DIR__));
require_once './inc/init.php';

require_once MYBB_ROOT . 'inc/plugins/plotcp/core/database.php';

$passed = 0;
$failed = 0;

function check(string $name, bool $ok): void
{
    global $passed, $failed;

    if ($ok) {
        $passed++;
        echo "ok   {$name}\n";
    } else {
        $failed++;
        echo "FAIL {$name}\n";
    }
}

/* ---- calendar math ---------------------------------------------------- */

check('leap year 2000',            Plotcp_Database::isLeapYear(2000) === true);
check('non-leap year 1900',        Plotcp_Database::isLeapYear(1900) === false);
check('leap year 0',               Plotcp_Database::isLeapYear(0) === true);
check('leap year -4',              Plotcp_Database::isLeapYear(-4) === true);
check('non-leap year -1',          Plotcp_Database::isLeapYear(-1) === false);
check('feb has 29 days in year 0', Plotcp_Database::daysInMonth(0, 2) === 29);
check('apr has 30 days',           Plotcp_Database::daysInMonth(1247, 4) === 30);

check('valid 29.2.0',    Plotcp_Database::isValidDate(0, 2, 29) === true);
check('invalid 29.2.1900', Plotcp_Database::isValidDate(1900, 2, 29) === false);
check('valid 29.2.2000', Plotcp_Database::isValidDate(2000, 2, 29) === true);
check('invalid 31.4.',   Plotcp_Database::isValidDate(1247, 4, 31) === false);
check('invalid month 13', Plotcp_Database::isValidDate(1247, 13, 1) === false);
check('valid negative year', Plotcp_Database::isValidDate(-500, 7, 15) === true);

check('key formula', Plotcp_Database::dateKey(1247, 3, 12) === 12470312);
check('key monotonic across year 0',
    Plotcp_Database::dateKey(-1, 12, 31) < Plotcp_Database::dateKey(0, 1, 1));
check('key monotonic negative years',
    Plotcp_Database::dateKey(-500, 6, 1) < Plotcp_Database::dateKey(-499, 1, 1));

/* ---- date validation -------------------------------------------------- */

$r = Plotcp_Database::validateDates(false, []);
check('no date -> defaults', $r['ok'] && $r['fields']['has_date'] === 0 && $r['fields']['start_key'] === 0);

$r = Plotcp_Database::validateDates(true, [
    'start_year' => '1247', 'start_month' => '3', 'start_day' => '12',
    'end_year' => '', 'end_month' => '', 'end_day' => '',
]);
check('end empty -> copied from start', $r['ok']
    && $r['fields']['end_year'] === 1247 && $r['fields']['end_month'] === 3
    && $r['fields']['end_day'] === 12 && $r['fields']['end_key'] === $r['fields']['start_key']);

$r = Plotcp_Database::validateDates(true, [
    'start_year' => '1247', 'start_month' => '3', 'start_day' => '12',
    'end_year' => '1247', 'end_month' => '4', 'end_day' => '2',
]);
check('valid range 12.3.-2.4.1247', $r['ok']
    && $r['fields']['start_key'] === 12470312 && $r['fields']['end_key'] === 12470402);

$r = Plotcp_Database::validateDates(true, [
    'start_year' => '0', 'start_month' => '2', 'start_day' => '29',
    'end_year' => '', 'end_month' => '', 'end_day' => '',
]);
check('29.2. year 0 accepted (year zero exists)', $r['ok'] === true);

$r = Plotcp_Database::validateDates(true, [
    'start_year' => '1247', 'start_month' => '', 'start_day' => '12',
    'end_year' => '', 'end_month' => '', 'end_day' => '',
]);
check('incomplete start rejected', !$r['ok'] && $r['error'] === 'error_start_incomplete');

$r = Plotcp_Database::validateDates(true, [
    'start_year' => '1247', 'start_month' => '2', 'start_day' => '31',
    'end_year' => '', 'end_month' => '', 'end_day' => '',
]);
check('31.2. rejected', !$r['ok'] && $r['error'] === 'error_start_invalid');

$r = Plotcp_Database::validateDates(true, [
    'start_year' => '1247', 'start_month' => '3', 'start_day' => '12',
    'end_year' => '1247', 'end_month' => '4', 'end_day' => '',
]);
check('partial end rejected', !$r['ok'] && $r['error'] === 'error_end_partial');

$r = Plotcp_Database::validateDates(true, [
    'start_year' => '1247', 'start_month' => '3', 'start_day' => '12',
    'end_year' => '1246', 'end_month' => '12', 'end_day' => '31',
]);
check('end before start rejected', !$r['ok'] && $r['error'] === 'error_end_before_start');

$r = Plotcp_Database::validateDates(true, [
    'start_year' => '-4', 'start_month' => '2', 'start_day' => '29',
    'end_year' => '-4', 'end_month' => '3', 'end_day' => '1',
]);
check('leap day in year -4 accepted', $r['ok'] === true);

/* ---- DB roundtrip + dedupe (SELFTEST fixtures) ------------------------ */

global $db;

if (!$db->table_exists('plotcp_plots')) {
    echo "SKIP db roundtrip – plugin not installed\n";
} else {
    // fixtures: two groups, one plot, one uid in both groups
    $gid1 = (int)$db->insert_query('plotcp_groups', [
        'name' => 'SELFTEST group A', 'description' => '', 'approved' => 1, 'dateline' => TIME_NOW,
    ]);
    $gid2 = (int)$db->insert_query('plotcp_groups', [
        'name' => 'SELFTEST group B', 'description' => '', 'approved' => 1, 'dateline' => TIME_NOW,
    ]);
    $gid3 = (int)$db->insert_query('plotcp_groups', [
        'name' => 'SELFTEST group C', 'description' => '', 'approved' => 0, 'dateline' => TIME_NOW,
    ]);
    $pid = (int)$db->insert_query('plotcp_plots', [
        'cid' => 0, 'title' => 'SELFTEST plot', 'description' => '',
        'visible' => 0, 'created_dateline' => TIME_NOW,
    ]);
    $db->insert_query('plotcp_group_members', ['gid' => $gid1, 'uid' => 999999901, 'dateline' => TIME_NOW]);
    $db->insert_query('plotcp_group_members', ['gid' => $gid2, 'uid' => 999999901, 'dateline' => TIME_NOW]);
    $db->insert_query('plotcp_group_members', ['gid' => $gid2, 'uid' => 999999902, 'dateline' => TIME_NOW]);

    $diff = Plotcp_Database::setPlotGroups($pid, [$gid1, $gid2]);
    check('group diff: both added', count($diff['added']) === 2 && !$diff['removed']);

    $diff = Plotcp_Database::setPlotGroups($pid, [$gid2]);
    check('group diff: one removed one kept',
        $diff['removed'] === [$gid1] && $diff['kept'] === [$gid2] && !$diff['added']);

    $uids = Plotcp_Database::getGroupMemberUids([$gid1, $gid2]);
    sort($uids);
    check('member uids deduped across groups', $uids === [999999901, 999999902]);

    $minus = array_values(array_diff(
        Plotcp_Database::getGroupMemberUids([$gid1]),
        Plotcp_Database::getGroupMemberUids([$gid2])
    ));
    check('added-minus-remaining dedupe (no double alert)', $minus === []);

    $approved = Plotcp_Database::filterApprovedGids([$gid1, $gid2, $gid3, 999999]);
    sort($approved);
    check('filterApprovedGids drops unapproved + unknown', $approved === [$gid1, $gid2]);

    check('groupNameExists case-insensitive',
        Plotcp_Database::groupNameExists('selftest GROUP a') === true);

    // cleanup
    Plotcp_Database::deletePlot($pid);
    foreach ([$gid1, $gid2, $gid3] as $gid) {
        Plotcp_Database::deleteGroup($gid);
    }
    $row = $db->fetch_array($db->query('SELECT COUNT(*) AS num FROM ' . TABLE_PREFIX . "plotcp_groups WHERE name LIKE 'SELFTEST%'"));
    check('fixtures cleaned up', (int)$row['num'] === 0);
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);

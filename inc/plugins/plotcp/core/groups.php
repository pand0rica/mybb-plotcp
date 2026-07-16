<?php
/**
 * plotcp – bridge to the optional plottinggroups plugin
 *
 * Plot↔group binding is optional: plotcp keeps its own plotcp_plot_groups link
 * table, but the group definitions and memberships live in the separate
 * plottinggroups plugin. Every group lookup goes through this bridge, which
 * returns empty results when plottinggroups is not installed/active – so the
 * plot side simply hides all group features in that case.
 *
 * function_exists('plottinggroups_load_core') is the "installed AND active"
 * gate (MyBB only loads a plugin's main file while active); load_core() then
 * pulls in Plottinggroups_Api.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Plotcp_Groups
{
    /** True when the plottinggroups plugin is installed, active and exposes its API. */
    public static function available(): bool
    {
        static $checked = null;
        if ($checked !== null) {
            return $checked;
        }
        if (function_exists('plottinggroups_load_core')) {
            plottinggroups_load_core();
        }
        $checked = class_exists('Plottinggroups_Api');

        return $checked;
    }

    /** @return array gid => name of all approved groups (for select/checkbox UIs). */
    public static function approvedGroups(): array
    {
        return self::available() ? Plottinggroups_Api::approvedGroups() : [];
    }

    /** @return array gid => name for the given gids (for plot badges). */
    public static function groupNames(array $gids): array
    {
        return self::available() ? Plottinggroups_Api::groupNames($gids) : [];
    }

    /** @return int[] distinct member uids of the given groups (publish alerts). */
    public static function memberUids(array $gids): array
    {
        return self::available() ? Plottinggroups_Api::memberUids($gids) : [];
    }

    /** @return int[] approved gids among the given ones (validate submitted input). */
    public static function filterApprovedGids(array $gids): array
    {
        return self::available() ? Plottinggroups_Api::filterApprovedGids($gids) : [];
    }

    /** Relative URL of the groups page, or '' when unavailable. */
    public static function groupsUrl(): string
    {
        return self::available() ? Plottinggroups_Api::groupsUrl() : '';
    }
}

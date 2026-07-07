<?php
/**
 * plotcp – Frontend rendering
 *
 * Builds the frontend views (plot list, detail page, groups page, submission
 * forms) and the scene box shown below threads in configured forums.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Plotcp_Frontend
{
    /**
     * Scene box for showthread ({$plotcp_scene_box}). Empty string outside
     * the configured inplay/archive forums or without permission.
     */
    public static function buildSceneBox(): string
    {
        // Implemented with the scene linking work package; safe no-op until then.
        return '';
    }
}

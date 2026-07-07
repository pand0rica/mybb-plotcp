<?php
/**
 * plotcp – MyAlerts formatter
 *
 * One formatter instance per alert type code. Every value taken from
 * ExtraDetails is escaped with htmlspecialchars_uni() before it is placed in
 * the alert message – ExtraDetails are stored raw (stored XSS otherwise).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Plotcp_MyAlerts_Formatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
    /** @var string alert type code this instance formats (plotcp_*) */
    private $plotcp_code = '';

    public function __construct($mybb, $lang, $alertTypeName = '')
    {
        parent::__construct($mybb, $lang, $alertTypeName);
        $this->plotcp_code = (string)$alertTypeName;
    }

    public function init()
    {
        if (!isset($this->lang->myalerts_setting_plotcp_plot_published)) {
            $this->lang->load('plotcp_myalerts');
        }
    }

    /**
     * @param MybbStuff_MyAlerts_Entity_Alert $alert
     * @param array                           $outputAlert
     * @return string formatted alert message
     */
    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
    {
        $details = (array)$alert->getExtraDetails();

        $lang_key = 'myalerts_' . $this->plotcp_code;
        if ($this->plotcp_code === 'plotcp_submission_team') {
            $kind = isset($details['kind']) && $details['kind'] === 'group' ? 'group' : 'plot';
            $lang_key .= '_' . $kind;
        }

        if (!isset($this->lang->{$lang_key}) || $this->lang->{$lang_key} === '') {
            $this->lang->load('plotcp_myalerts');
        }
        if (!isset($this->lang->{$lang_key}) || $this->lang->{$lang_key} === '') {
            return '[' . htmlspecialchars_uni($this->plotcp_code) . ']';
        }

        $message = (string)$this->lang->{$lang_key};

        // {from_user} is pre-formatted by MyAlerts (profile link), no escaping here.
        if (isset($outputAlert['from_user']) && $outputAlert['from_user'] !== '') {
            $message = str_replace('{from_user}', $outputAlert['from_user'], $message);
        } else {
            $message = str_replace('{from_user}', '', $message);
        }

        foreach (['plot_title', 'group_name', 'title', 'link'] as $key) {
            $value = isset($details[$key]) ? (string)$details[$key] : '';
            $message = str_replace('{' . $key . '}', htmlspecialchars_uni($value), $message);
        }

        return $message;
    }

    /**
     * Click target of the alert row (bell dropdown / alerts page).
     *
     * @param MybbStuff_MyAlerts_Entity_Alert $alert
     * @return string
     */
    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
    {
        $details = (array)$alert->getExtraDetails();
        $link = isset($details['link']) ? (string)$details['link'] : 'plotcp.php';

        return $this->mybb->settings['bburl'] . '/' . htmlspecialchars_uni($link);
    }
}

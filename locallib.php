<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * local library class for the Planet eStream Assignment Submission Plugin
 * extends submission plugin base class
 *
 * @package        assignsubmission_estream
 * @copyright        Planet Enterprises Ltd
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die();

if (!function_exists('assignsubmission_estream_get_cachebuster')) {
    /**
     * Build a per-request cache-busting token for iframe URLs.
     *
     * @return string
     */
    function assignsubmission_estream_get_cachebuster()
    {
        return preg_replace('/[^0-9]/', '', sprintf('%.6f', microtime(true)));
    }
}

class assign_submission_estream extends assign_submission_plugin
{
    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name()
    {
        return get_string('shortname', 'assignsubmission_estream');
    }
    /**
     * Save the submission plugin settings
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data)
    {
        return true;
    }
    /**
     * Read the submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function funcgetsubmission($submissionid)
    {
        global $DB;
        return $DB->get_record('assignsubmission_estream', array(
            'submission' => $submissionid
        ));
    }

    /**
     * Determine whether a cdid value is empty.
     *
     * @param mixed $cdid
     * @return bool
     */
    private function is_empty_cdid($cdid)
    {
        return trim((string)$cdid) === '';
    }
    /**
     * Embed the player
     *
     * @return string
     */
    public function funcembedplayer($cdid, $embedcode)
    {
        $url = rtrim(get_config('assignsubmission_estream', 'url'), '/');
        $cachebuster = assignsubmission_estream_get_cachebuster();
        if ($cdid == "") {
            return "<p>" . get_config('assignsubmission_estream', 'emptyoverride') . "</p>";
        } else {

            if (strpos($cdid, '¬') !== false) { // Multiple uploads

                $CDIDs = explode("¬", $cdid);
                $Codes = explode("¬", $embedcode);
                $strReturn = '';

                for ($i = 0; $i < count($CDIDs); $i++) {

                    $strReturn .= "<iframe allowfullscreen height=\"198\" width=\"352\" src=\"" . $url . "/Embed.aspx?id=" . $CDIDs[$i]
                        . "&amp;code=" . $Codes[$i] . "&amp;wmode=opaque&amp;viewonestream=0&amp;cb=" . $cachebuster
                        . "\" frameborder=\"0\"></iframe>&nbsp;";
                }

                return $strReturn;

                return "<iframe allowfullscreen height=\"198\" width=\"352\" src=\"" . $url . "/Embed.aspx?id=123&amp;code=123&amp;wmode=opaque&amp;viewonestream=0\" frameborder=\"0\"></iframe>";
            } else {

                return "<iframe allowfullscreen height=\"198\" width=\"352\" src=\"" . $url . "/Embed.aspx?id=" . $cdid
                    . "&amp;code=" . $embedcode . "&amp;wmode=opaque&amp;viewonestream=0&amp;cb=" . $cachebuster
                    . "\" frameborder=\"0\"></iframe>";
            }
        }
    }
    /**
     * Save data to the database
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data)
    {
        try {
            global $DB;
            $thissubmission = $this->funcgetsubmission($submission->id);

            $incomingcdid = isset($data->cdid) ? trim((string)$data->cdid) : '';
            $incomingembedcode = isset($data->embedcode) ? trim((string)$data->embedcode) : '';
            $hasincomingitem = !$this->is_empty_cdid($incomingcdid);

            if (!$thissubmission) {
                if (!$hasincomingitem && get_config('assignsubmission_estream', 'forcesubmit')) {
                    $this->set_error(get_string('error_force', 'assignsubmission_estream'));
                    return false;
                }

                $thissubmission = new stdClass();
                $thissubmission->submission = $submission->id;
                $thissubmission->assignment = $this->assignment->get_instance()->id;
                $thissubmission->embedcode = $hasincomingitem ? $incomingembedcode : '';
                $thissubmission->cdid = $hasincomingitem ? $incomingcdid : '';

                return $DB->insert_record('assignsubmission_estream', $thissubmission) > 0;
            }

            $storedcdid = trim((string)$thissubmission->cdid);
            $storedembedcode = trim((string)$thissubmission->embedcode);
            $hasstoreditem = !$this->is_empty_cdid($storedcdid);

            if (!$hasincomingitem) {
                if (get_config('assignsubmission_estream', 'forcesubmit')) {
                    $this->set_error(get_string('error_force', 'assignsubmission_estream'));
                    return false;
                }

                if (!$hasstoreditem) {
                    return true;
                }

                if (!get_config('assignsubmission_estream', 'overwriteblank')) {
                    return true;
                }

                $thissubmission->assignment = $this->assignment->get_instance()->id;
                $thissubmission->embedcode = '';
                $thissubmission->cdid = '';
                return $DB->update_record('assignsubmission_estream', $thissubmission);
            }

            if ($incomingcdid === $storedcdid && $incomingembedcode === $storedembedcode) {
                return true;
            }

            $thissubmission->assignment = $this->assignment->get_instance()->id;
            $thissubmission->embedcode = $incomingembedcode;
            $thissubmission->cdid = $incomingcdid;
            return $DB->update_record('assignsubmission_estream', $thissubmission);
        } catch (Exception $e) {
            debugging('assignsubmission_estream save failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission)
    {
        $thissubmission = $this->funcgetsubmission($submission->id);
        if ($thissubmission) {
            return $this->funcembedplayer($thissubmission->cdid, $thissubmission->embedcode);
        } else {
            return "";
        }
    }
    /**
     * Display the list of files in the submission status table
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true if the list of files is long
     * @return string
     */
    public function view_summary(stdClass $submission, &$showviewlink)
    {
        return $this->view($submission);
    }
    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version)
    {
        return false;
    }


    public function atto_planetestream_obfuscate($strx)
    {
        $strbase64chars = '0123456789aAbBcCDdEeFfgGHhiIJjKklLmMNnoOpPQqRrsSTtuUvVwWXxyYZz/+=';
        $strbase64string = base64_encode($strx);
        if ($strbase64string == '') {
            return '';
        }
        $strobfuscated = '';
        for ($i = 0; $i < strlen($strbase64string); $i++) {
            $intpos = strpos($strbase64chars, substr($strbase64string, $i, 1));
            if ($intpos == -1) {
                return '';
            }
            $intpos += strlen($strbase64string) + $i;
            $intpos = $intpos % strlen($strbase64chars);
            $strobfuscated .= substr($strbase64chars, $intpos, 1);
        }
        return urlencode($strobfuscated);
    }


    function atto_planetestream_getchecksum()
    {
        $decchecksum = (float)(date('d') + date('m')) + (date('m') * date('d')) + (date('Y') * date('d'));
        $decchecksum += $decchecksum * (date('d') * 2.27409) * .689274;
        return md5(floor($decchecksum));
    }


    function atto_planetestream_getauthticket($url, $checksum, $delta, $userip, &$params)
    {
        $return = '';

        //$return = $url . "~~~" . $checksum . "~~~~" . $delta . "~~~~" . $userip;
        try {
            $url .= '/VLE/Moodle/Auth/?source=1&assign=1&checksum=' . $checksum . '&delta=' . $delta . '&u=' . $userip;
            if (!$curl = curl_init($url)) {
                return '';
                //return $return;
            }
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($curl, CURLOPT_TIMEOUT, 15);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_MAXREDIRS, 4);
            curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
            if (strpos($response, '{"ticket":') === 0) {
                $jobj = json_decode($response);
                $return = $jobj->ticket;
                $params['estream_height'] = $jobj->height;
                $params['estream_width'] = $jobj->width;
            }
        } catch (Exception $e) {
            // ... non-fatal ...
        }
        return $return;
    }

    public function remove(stdClass $submission)
    {
        global $DB;

        if (empty($submission->id)) {
            return true;
        }

        $DB->delete_records('assignsubmission_estream', array(
            'submission' => $submission->id
        ));

        return true;
    }


    public function delete_instance()
    {
        global $DB;
        $DB->delete_records('assignsubmission_estream', array(
            'assignment' => $this->assignment->get_instance()->id
        ));
        try {
            $cs = (float)(date('d') + date('m')) + (date('m') * date('d')) + (date('Y') * date('d'));
            $cs += $cs * (date('d') * 2.27409) * .689274;
            $url = rtrim(get_config('assignsubmission_estream', 'url'), '/');
            $url = $url . "/UploadSubmissionVLE.aspx?mad=" . $this->assignment->get_instance()->id . "&checksum=" . md5(floor($cs));
            if (!$curl = curl_init($url)) {
                $this->log('curl init failed [187].');
                return false;
            }
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($curl, CURLOPT_TIMEOUT, 60);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $content = curl_exec($curl);
        } catch (Exception $e) {
            // Non-fatal exception!
        }
        return true;
    }
    /**
     * Is a submission present?
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission)
    {
        $thissubmission = $this->funcgetsubmission($submission->id);
        if (!$thissubmission) {
            return true;
        }

        return $this->is_empty_cdid($thissubmission->cdid);
    }

    /**
     * Determine if a new submission is empty before save.
     *
     * @param stdClass $data
     * @return bool
     */
    public function submission_is_empty(stdClass $data)
    {
        $cdid = isset($data->cdid) ? $data->cdid : '';
        return $this->is_empty_cdid($cdid);
    }
    /**
     * Add form elements
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data)
    {
        global $CFG, $USER, $PAGE, $COURSE;
        $cdid = "";
        $embedcode = "";
        $url = new moodle_url('/mod/assign/submission/estream/upload.php', array(
            'id' => $this->assignment->get_course_module()->id,
            'sesskey' => sesskey(),
            'cb' => assignsubmission_estream_get_cachebuster()
        ));
        $itemtitle = "Submission by " . fullname($USER);
        if (strlen($itemtitle) > 120) {
            $itemtitle = substr($itemtitle, 0, 120);
        }
        $itemdesc = "Assignment : " . $this->assignment->get_instance()->name . "\r\n";
        $itemdesc .= "Course : " . $COURSE->fullname . "\r\n";
        $url->param('itemtitle', $itemtitle);
        $url->param('itemdesc', $itemdesc);
        $url->param('itemaid', $this->assignment->get_instance()->id);
        $url->param('itemuid', $USER->id);
        $url->param('itemcid', $COURSE->id);
        if ($submission) {
            $thissubmission = $this->funcgetsubmission($submission->id);
            if ($thissubmission) {
                $cdid = $thissubmission->cdid;
                $embedcode = $thissubmission->embedcode;
                $iframehtml = $this->funcembedplayer($cdid, $embedcode);
                $mform->addElement(
                    'static',
                    'currentsubmission',
                    get_string('currentsubmission', 'assignsubmission_estream'),
                    $iframehtml
                );
                $url->param('itemcdid', $cdid);
            }
        }
        $html = '<script type="text/javascript">';
        $html .= 'document.getElementById("hdn_cdid").value="' . $cdid . '";';
        $html .= 'document.getElementById("hdn_embedcode").value="' . $embedcode . '";';
        $html .= '</script>';

        $html .= '<div style="padding-left: 15px; padding-top: 8px; width: 900px; height: 800px; line-height: 160%;">';
        $html .= get_config('assignsubmission_estream', 'helptext') . '<br />';
        $html .= get_string('upload_help', 'assignsubmission_estream') . '<br />';
        $html .= '<iframe allow="camera;microphone;display-capture;" src="' . $url->out(false) . '" width="90%" height="775px" noresize frameborder="0"></iframe>';
        $html .= '</div>';
        $mform->addElement('hidden', 'cdid', '', array('id' => 'hdn_cdid'));
        $mform->addElement('hidden', 'embedcode', '', array('id' => 'hdn_embedcode'));
        $mform->addElement('static', 'div_estream', '', $html);
        $mform->setType('cdid', PARAM_TEXT);
        $mform->setType('embedcode', PARAM_TEXT);
        return true;
    }
}

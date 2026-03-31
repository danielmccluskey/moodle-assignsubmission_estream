<?php
// �

namespace assignsubmission_estream\privacy;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/submission/estream/locallib.php');

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\contextlist;
use \mod_assign\privacy\assign_plugin_request_data;
use \mod_assign\privacy\useridlist;

class provider implements metadataprovider, \mod_assign\privacy\assignsubmission_provider
{

    public static function get_metadata(collection $collection): collection
    {

        $collection->add_external_location_link('assignsubmission_estream', [
            'userid' => 'privacy:metadata:assignsubmission_estream:userid',
            'fullname' => 'privacy:metadata:assignsubmission_estream:fullname',
            'email' => 'privacy:metadata:assignsubmission_estream:email',
            'userip' => 'privacy:metadata:assignsubmission_estream:userip',
        ], 'privacy:metadata:assignsubmission_estream');

        $collection->add_database_table(
            'assignsubmission_estream',
            [
                'assignment' => 'privacy:metadata:assignsubmission_estream:assignment',
                'submission' => 'privacy:metadata:assignsubmission_estream:submission',
                'cdid' => 'privacy:metadata:assignsubmission_estream:cdid',
                'embedcode' => 'privacy:metadata:assignsubmission_estream:embedcode',

            ],
            'privacy:metadata:assignsubmission_estream'
        );

        return $collection;
    }

    /**
     * This is covered by mod_assign provider and the query on assign_submissions.
     *
     * @param  int $userid The user ID that we are finding contexts for.
     * @param  contextlist $contextlist A context list to add sql and params to for contexts.
     */
    public static function get_context_for_userid_within_submission(int $userid, contextlist $contextlist)
    {
        // This is already fetched from mod_assign.
    }

    /**
     * This is covered by the mod_assign provider and it's queries.
     *
     * @param  \mod_assign\privacy\useridlist $useridlist An object for obtaining user IDs of students.
     */
    public static function get_student_user_ids(\mod_assign\privacy\useridlist $useridlist)
    {
        // No need.
    }

    /**
     * Export all user data for this plugin.
     *
     * @param  assign_plugin_request_data $exportdata Data used to determine which context and user to export and other useful
     * information to help with exporting.
     */
    public static function export_submission_user_data(assign_plugin_request_data $exportdata)
    {
        global $DB;
        if ($exportdata->get_user() != null) {
            return null;
        }
        $context = $exportdata->get_context();
        $currentpath = $exportdata->get_subcontext();
        //$currentpath[] = get_string('privacy:path', 'assignsubmission_estream');
        $submission = $exportdata->get_pluginobject();
        $pessubmission = $DB->get_record('assignsubmission_estream', array('submission' => $submission->id));
        if (!empty($pessubmission)) {
            writer::with_context($context)
                // Add the text to the exporter.
                ->export_data($currentpath, $pessubmission);
        }
    }

    /**
     * Delete all the submission records made for this context.
     *
     * @param  assign_plugin_request_data $requestdata Data to fulfill the deletion request.
     */
    public static function delete_submission_for_context(assign_plugin_request_data $requestdata)
    {
        global $DB;
        $DB->delete_records('assignsubmission_estream', ['assignment' => $requestdata->get_assign()->get_instance()->id]);
    }

    /**
     * A call to this method should delete user data (where practical) using the userid and submission.
     *
     * @param  assign_plugin_request_data $deletedata Details about the user and context to focus the deletion.
     */
    public static function delete_submission_for_userid(assign_plugin_request_data $deletedata)
    {
        global $DB;

        $submissionid = $deletedata->get_pluginobject()->id;

        // Delete the records in the table.
        $DB->delete_records('assignsubmission_estream', [
            'assignment' => $deletedata->get_assign()->get_instance()->id,
            'submission' => $submissionid
        ]);
    }
}

<?php

define('CLI_SCRIPT', true);
require(__DIR__.'/../../../config.php');
defined('MOODLE_INTERNAL') || die();

// plugin classes
require_once($CFG->dirroot . '/blocks/peta/src/Service/Iassign.php');
require_once($CFG->dirroot . '/blocks/peta/src/Service/Utils.php');
require_once($CFG->dirroot . '/blocks/peta/src/Service/Table.php');
require_once($CFG->dirroot . '/blocks/peta/src/Service/PrepareData.php');
use block_peta\Service\Iassign;
use block_peta\Service\Utils;
use block_peta\Service\Table;
use block_peta\Service\PrepareData;

global $DB;

$courses = Iassign::courses();
foreach($courses as $course=>$courseinfo){

    $statements = Iassign::statementsWithSubmissions($course);

    foreach($statements as $statement){

        $metrics = PrepareData::statementAnalysis($statement->id);
        
        $metrics_as_objects = array_map(
            function($metric) use ($statement, $course) {

                echo "Synchronization statementid {$statement->id}, userid {$metric['userid']} and course: {$course}\n";

                $statementtext = Iassign::getStatementName($statement->id);

                $submissionsbyuser = Iassign::numberOfSubmissionsByUser($statement->id, $metric['userid']);

                $submissionsgroupedbyuser = Iassign::numberOfSubmissionsGroupedByUser($statement->id);
                $max = max(array_column($submissionsgroupedbyuser, 'total'));

                $numberofusers = count($submissionsgroupedbyuser);
                $numberofsubmissions = $statement->total;
                $avgsubmissionsbyuser = (float) $numberofsubmissions/$numberofusers;

                $median = Utils::median(array_column($submissionsgroupedbyuser, 'total'));

                $obj = new stdClass;
                $obj->courseid = $course;
                $obj->statementid = $statement->id;
                $obj->statement = $statementtext;
                $obj->numberofusers = $numberofusers;
                $obj->numberofsubmissions = $numberofsubmissions;
                $obj->submissionsbyuser = $submissionsbyuser;
                $obj->avgsubmissionsbyuser = $avgsubmissionsbyuser;
                $obj->max = $max;
                $obj->median = $median;
                $obj->userid = $metric['userid'];
                $obj->mtes = $metric['mtes'];
                $obj->mdes = $metric['mdes'];
                $obj->dex  = $metric['dex'];
                $obj->tms  = $metric['tms'];
                $obj->n  = $metric['n'];
                $obj->mtes_normalized = $metric['mtes_normalized'];
                $obj->mdes_normalized = $metric['mdes_normalized'];
                $obj->dex_normalized  = $metric['dex_normalized'];
                $obj->tms_normalized  = $metric['tms_normalized'];
                $obj->n_normalized  = $metric['n_normalized'];
                $obj->grade_average  = $metric['grade_average'];
                return $obj;
            }, 
            $metrics
        );

        $DB->delete_records('block_peta_statement_metrics',[ 'statementid' => $statement->id, 'courseid' => $course ]);
        $DB->insert_records('block_peta_statement_metrics', $metrics_as_objects);
    }
}






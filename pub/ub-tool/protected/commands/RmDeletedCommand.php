<?php

/**
 * @Todo: This command allows to check and delete the migrated items in M2 which no longer exist in M1 database.
 * RmDeletedCommand class - CLI
 */
class RmDeletedCommand extends CConsoleCommand
{
    public function actionIndex($step = -1, $limit = 100, $tables = "")
    {
        $stepsAllowed = [
            2, //migrate websites/stores/store views
            3, //migrate attributes and attribute's values
            4, //migrate categories
            5, //migrate products
            6, //migrate customers
            7, //migrate sales data
            8 //other data
        ];
        if ($step > 0) {
            if (in_array($step, $stepsAllowed)) {
                $this->_removeDeletedData($step, $limit, $tables);
            } else {
                echo "ATTENTION: This command ready for steps " . implode(", ", $stepsAllowed) . " only.\n";
            }
        } else {
            foreach ($stepsAllowed as $stepIndex) {
                $this->_removeDeletedData($stepIndex, $limit);
            }
            echo "********** Checking and removing migrated objects in all steps successfully **********\n";
        }
    }

    private function _removeDeletedData($stepIndex, $limit, $tables)
    {
        $step = UBMigrate::model()->findByPk($stepIndex);
        if ($step) {
            echo "Checking and removing migrated objects in step #{$stepIndex} which no longer exist in M1:\n";

            //reset mapping states for mapping data records in current step
            UBMigrate::resetMappingStates($stepIndex);

            //checking and removing
            $functionName = "rmDeletedDataStep{$stepIndex}";
            do {
                $status = call_user_func_array(array($step, $functionName), array($limit, $tables));
            } while ($status == 'ok');

            if ($status == 'done') {
                Yii::app()->cache->flush();

                //update step status
                $step->status = UBMigrate::STATUS_FINISHED;
                $step->update();

                $msg = Yii::t('frontend', "Checking and removing in step #%s is complete.", array('%s' => $stepIndex));
                Yii::log($msg, 'info', 'ub_data_migration');
                echo "\n{$msg}\n\n";
            } else {
                echo $status;
            }
        } else {
            $msg = Yii::t('frontend', "Step #%s not found.", array('%s' => $stepIndex));
            echo "{$msg}\n";
        }
    }
}
<?php

/**
 * RunCommand class - CLI
 */
class RunCommand extends CConsoleCommand
{
    protected $stepIndex;
    protected $percent;

    public function actionIndex($step = -1, $limit = false, $mode = 'run', $timezone = null)
    {
        Yii::app()->db->createCommand("SET FOREIGN_KEY_CHECKS=0")->execute();
        $this->percent = UBMigrate::getPercentByStatus(UBMigrate::STATUS_FINISHED, [1]);
        if ($step > 0) { //has specify step
            if ($step >= 2 AND $step <= UBMigrate::MAX_STEP_INDEX) {
                $this->stepIndex = $step;
                $controllerName = "Step{$this->stepIndex}Controller";
                $step = new $controllerName("{step{$this->stepIndex}}");
                $step->activeCLI();
                if ($limit > 0) {
                    $step->setLimit($limit);
                }
                if ($timezone) {
                    $step->setTimezone($timezone);
                }
                if ($mode == 'update' || $mode == 'delta') {
                    //reset offset
                    UBMigrate::resetOffset( $this->stepIndex);
                    //update run mode
                    $step->setRunMode(UBMigrate::RUN_MODE_DELTA);
                }
                $this->_migrateData($step);
            } else {
                echo "ATTENTION: You can run command lines for steps 2, 3, 4, 5, 6, 7, and 8 only.\n";
            }

        } else { //run all steps
            $steps = [2, 3, 4, 5, 6, 7, 8];
            foreach ($steps as $step) {
                $this->stepIndex = $step;
                $controllerName = "Step{$this->stepIndex}Controller";
                $step = new $controllerName("{step{$this->stepIndex}}");
                $step->activeCLI();
                if ($limit > 0) {
                    $step->setLimit($limit);
                }
                if ($timezone) {
                    $step->setTimezone($timezone);
                }
                if ($mode == 'update' || $mode == 'delta') {
                    //reset offset
                    UBMigrate::resetOffset($this->stepIndex);
                    //update run mode
                    $step->setRunMode(UBMigrate::RUN_MODE_DELTA);
                }
                $this->_migrateData($step);
            }
            $outPut = "ATTENTION: Data migration has been completed successfully. You still have a few more steps to complete.\n";
            $outPut .= "Follow instructions in the Readme.html that came with your download package, then you're done.";
            echo strtoupper($outPut)."\n\n";
        }

        Yii::app()->db->createCommand("SET FOREIGN_KEY_CHECKS=1")->execute();
    }

    private function _migrateData($step)
    {
        $count = 1;
        do {
            if ($count ==1) {
                echo "[Processing][{$step->runMode}] in step #{$this->stepIndex}: ";
            }
            $result = $step->actionRun();
            $this->_respond($result);
            $count++;
        } while ($result['status'] == 'ok');

        if ($result['status'] == 'fail') {
            $msg = (isset($result['notice']) AND $result['notice']) ? $result['notice'] : (($result['errors']) ? $result['errors'] : '');
            echo "Status: {$result['status']}\n";
            echo "Message: {$msg}\n";
        } else { //done
            $value = UBMigrate::getPercentByStatus(UBMigrate::STATUS_FINISHED, [1]);
            echo "Total Data Migrated: {$value}%\n\n";
        }
    }

    private function _respond($result)
    {
        echo "{$result['message']}\n";
        //update percent finished
        /*if (isset ($result['percent_up']) AND $result['percent_up']) {
            $this->percent += (float)$result['percent_up'];
        }
        $value = round($this->percent);
        if ($result['status'] == 'ok') {
            echo "Total Data Migrated: {$value}%\n";
        }*/
    }

}
<?php
session_start();
$HRconnect = mysqli_connect("localhost", "root", "", "hrms");
if (isset($_GET['overtime'])) {

    if ($_GET['overtime'] == 'getSession') {
        echo getSession($HRconnect, $_GET['empno']);
        exit;
    } else if ($_GET['overtime'] == 'getTimeInputs') {
        echo getTimeInputs($HRconnect, $_GET['empno'], $_GET['type'], $_GET['date']);
    } else if ($_GET['overtime'] == 'createOvertime') {
        $selectUserInfo = "SELECT userlevel FROM hrms.user_info WHERE empno = '" . $_POST['empno'] . "'";
        $queryUserInfo = $HRconnect->query($selectUserInfo);
        $rowUserInfo = $queryUserInfo->fetch_array();

        echo createOvertime($HRconnect, $_POST['empno'], $_POST['overtime_date'], $_POST['overtime_type'], $_POST['overtime_hours'], $_POST['overtime_reason'], $rowUserInfo['userlevel']);
    }
}

function getSession($HRconnect, $empno)
{
    $selectCutOff = "SELECT datefrom, dateto FROM hrms.sched_info where empno = $empno AND status = 'Pending'";
    $queryCutOff = $HRconnect->query($selectCutOff);
    $rowCutOff = $queryCutOff->fetch_array();
    $cutfrom = $rowCutOff['datefrom'];
    $cutto = $rowCutOff['dateto'];
    $sessionData = [
        'empno' => $empno,
        'cutfrom' => $cutfrom,
        'cutto' => $cutto
    ];

    return json_encode($sessionData);
}

function getTimeInputs($HRconnect, $empno, $type, $date)
{
    if ($type == "0") {
        return json_encode([]);
    }
    $selectSchedTime = "SELECT schedto, M_timein, M_timeout, A_timein, A_timeout, timein4, timeout4 FROM hrms.sched_time where empno = $empno AND datefromto = '$date'";
    $querySchedTime = $HRconnect->query($selectSchedTime);
    $rowSchedTime = $querySchedTime->fetch_array();
    $schedTo = $rowSchedTime['schedto'];
    $data = [];

    $totalOvertimeHours = 0;
    $totalOvertimeMinutes = 0;

    $regularTimeIn = $rowSchedTime['M_timein'] ?? null;
    $regularBreakOut = $rowSchedTime['M_timeout'] ?? null;
    $regularBreakIn = $rowSchedTime['A_timein'] ?? null;
    $regularTimeout = $rowSchedTime['A_timeout'] ?? null;

    $brokenTimeIn = $rowSchedTime['timein4'] ?? null;
    $brokenTimeOut = $rowSchedTime['timeout4'] ?? null;

    $hasCompleteTimeInputs = $regularTimeIn != null && $regularBreakOut != null && $regularBreakIn != null && $regularTimeout != null;

    if ($type == 'broken_schedule') {
        $data['broken_sched_in'] = $rowSchedTime['timein4'] ? date('H:i', strtotime($rowSchedTime['timein4'])) : null;
        $data['broken_sched_out'] = $rowSchedTime['timeout4'] ? date('H:i', strtotime($rowSchedTime['timeout4'])) : null;
        $hasCompleteBrokenSched = $data['broken_sched_in'] != null && $data['broken_sched_out'] != null;
        if ($hasCompleteBrokenSched && $hasCompleteTimeInputs) {
            $undertime = utCalculator($schedTo, $regularTimeout);
            $totalBrokenSchedTime = (strtotime($brokenTimeOut) - strtotime($brokenTimeIn));
            $brokenSchedOvertime = floor(($totalBrokenSchedTime - $undertime) / 3600);

            $coverageTime = min($totalBrokenSchedTime, $undertime);
            $totalOvertimeHours = $brokenSchedOvertime;
            $totalOvertimeMinutes = ((strtotime($brokenTimeOut) - strtotime($brokenTimeIn . ' +' . $totalOvertimeHours . ' hour')) - $undertime) / 60;
            $data['broken_time_coverage'] = $coverageTime / 3600;
        } else {
            $totalOvertimeHours = floor((strtotime($brokenTimeOut) - strtotime($brokenTimeIn)) / 3600);
            $totalOvertimeMinutes = (strtotime($brokenTimeOut) - strtotime($brokenTimeIn . ' +' . $totalOvertimeHours . ' hour')) / 60;
        }
    } else if ($type == 'regular_schedule') {
        $regularTimeOut = $rowSchedTime['A_timeout'] ?? null;

        $data['m_timein'] = $regularTimeIn ? date('H:i', strtotime($rowSchedTime['M_timein'])) : null;
        $data['m_timeout'] = $regularBreakOut ? date('H:i', strtotime($rowSchedTime['M_timeout'])) : null;
        $data['a_timein'] = $regularBreakIn ? date('H:i', strtotime($rowSchedTime['A_timein'])) : null;
        $data['a_timeout'] = $regularTimeout ? date('H:i', strtotime($rowSchedTime['A_timeout'])) : null;
        if ($hasCompleteTimeInputs) {
            $totalOvertimeHours = floor((strtotime($regularTimeOut) - strtotime($schedTo)) / 3600);
            $totalOvertimeMinutes = (strtotime($regularTimeOut) - strtotime($schedTo . ' +' . $totalOvertimeHours . ' hour')) / 60;
        }
    }
    $data['max_overtime_hours'] = $totalOvertimeHours <= 0 ? 0 : $totalOvertimeHours;
    $data['is_half_hour_eligible'] = 0;

    // $data['is_half_hour_eligible'] = ($totalOvertimeMinutes >= 30 && $totalOvertimeHours >= 1) ? 1 : 0;

    return json_encode($data);
}

function utCalculator($schedto, $timeout)
{
    $ut = 0;
    $timeoutStr = strtotime($timeout);
    $schedtoStr = strtotime($schedto);
    $ut = max($schedtoStr - $timeoutStr, 0);
    return $ut;
}

function createOvertime($HRconnect, $empno, $overtimeDate, $overtimeType, $overtimeHours, $overtimeReason, $userLevel)
{
    try {
        $HRconnect->begin_transaction();

        $isOvertimeExisting = "SELECT COUNT(*) as is_existing FROM hrms.overunder WHERE empno = $empno AND otdatefrom = '$overtimeDate' AND ottype = '$overtimeType' AND otstatus IN ('pending', 'pending2', 'approved')";
        $queryIsOvertimeExisting = $HRconnect->query($isOvertimeExisting);
        $rowIsOvertimeExisting = $queryIsOvertimeExisting->fetch_array();
        if ($rowIsOvertimeExisting['is_existing'] > 0) {
            return json_encode(['error' => 'Overtime already filed for this date.']);
        }

        $status = updateOvertimeStatus($empno, $userLevel);
        $queryInsertOvertime = "INSERT INTO hrms.overunder SET empno = ?, otdatefrom = ?, ottype = ?, othours = ?, otreason = ?, otstatus = ?";
        $stmtInsertOvertime = $HRconnect->prepare($queryInsertOvertime);
        $stmtInsertOvertime->bind_param("isssss", $empno, $overtimeDate, $overtimeType, $overtimeHours, $overtimeReason, $status);

        if ($stmtInsertOvertime->execute()) {
            $HRconnect->commit();
            return json_encode(['success' => 'Overtime successfully filed']);

        } else {
            return json_encode(['error' => 'Failed to file overtime']);
        }

    } catch (Exception $exception) {
        $HRconnect->rollback();
        return json_encode([
            'error' => 'Failed to file overtime',
            'error_data' => $exception->getMessage(),
        ]);
    }
}

function updateOvertimeStatus($empno, $userLevel)
{
    $isPartialApproved = ($userLevel == 'admin' || $userLevel == 'mod' || $userLevel == 'ac') || ($empno == 2525
        || $empno == 167 || $empno == 2111 || $empno == 5327 || $empno == 2243 || $empno == 3332 || $empno == 3693
        || $empno == 4000 || $empno == 4814 || $empno == 3780 || $empno == 2485 || $empno == 4890 || $empno == 1844
    );

    // $isPending = $empno == 2008 or $empno == 5182 or $userLevel == 'staff'
    //     and $empno != 167 and $empno != 2111 and $empno != 5327 and $empno != 2243 and $empno != 3332 and $empno != 3693
    //     and $empno != 401 and $Employee != 2243 and $Employee != 3693 and $Employee != 4826 and $Employee != 5327 and $Employee != 5753 and $Employee != 6021 and $Employee != 6082 and $Employee != 6378 and $Employee != 6379 and $Employee != 6724
    //     and $empno != 4000 and $empno != 4814 and $empno != 3780 and $empno != 2485 and $empno != 4890 and $empno != 1844
    //     or $empno == 401;

    $status = 'pending';
    if ($isPartialApproved) {
        $status = 'pending2';
    }
    return $status;
}
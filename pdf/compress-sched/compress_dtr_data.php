<?php
$connect = mysqli_connect("localhost", "root", "", "db");
$HRconnect = mysqli_connect("localhost", "root", "", "hrms");
if (isset($_SESSION["viewPrintSched"])) {
    $empid = $_GET["empno"];
} else {
    if (!isset($_SESSION['user_validate'])) {
        header("Location:../../index.php?&m=2");
    }
    $empid = $_SESSION["user_validate"];
}

// ------------------VALIDATION---------------------------------------------------- //
$isExisting = "SELECT sched_type FROM `hrms`.`sched_time` WHERE empno = $empid";
$isExisting_q = $HRconnect->query($isExisting);
if (!(is_numeric($empid))) {
    header("Location:../../pageNotFound.php");
} else if ($isExisting_q->num_rows <= 0) {
    header("Location:../../pageNotFound.php");
}
// ------------------VALIDATION---------------------------------------------------- //

// Employee Details -------------------------------------------------------------//
$datefrom = $_GET["cutfrom"];
$dateto = $_GET["cutto"];
$select_details = "SELECT name, branch, department,userid, position, picture, is_compressed, workforce_division_id FROM `hrms`.`user_info` WHERE empno = ?";
$stmt = $HRconnect->prepare($select_details);
$stmt->bind_param("i", $empid);
$stmt->execute();
$employee_details = $stmt->get_result()->fetch_array();
$stmt->close();

$name = $employee_details["name"];
$branch = $employee_details["branch"];
$department = $employee_details["department"];
$position = $employee_details["position"];
$isPWD = $employee_details["picture"];
$isCompressed = $employee_details["is_compressed"];
$workforceDivisionid = $employee_details["workforce_division_id"];
#region Employee Schedule --------------------------------------------------------
$select_schedule = "SELECT st.datefromto, st.schedfrom, st.schedto, st.break, st.M_timein, st.M_timeout, st.A_timein, st.A_timeout, st.timein, 
                        st.breakout, st.breakin, st.timeout, st.remarks, st.timein4, st.timeout4, st.work_hours, st.m_in_status, st.m_o_status, st.a_in_status, st.a_o_status,
                        ot.othours, ot.otstatus, ot. ottype, ot.isNWD, wdo.wdostatus, wdo.working_hours,
                        vl.vlstatus, vl.vlhours, vl.vlduration, ho.type, ho.prior1, ho.prior2, ho.prior3
                        FROM `hrms`.`sched_time` st
                        LEFT JOIN `hrms`.`overunder` ot ON ot.otdatefrom = st.datefromto AND st.empno = ot.empno AND ot.otstatus != 'canceled'
                        LEFT JOIN `hrms`.`working_dayoff` wdo ON wdo.datefrom = st.datefromto AND st.empno = wdo.empno AND wdo.wdostatus != 'cancelled'
                        LEFT JOIN `hrms`.`vlform` vl ON vl.vldatefrom = st.datefromto AND vl.empno = st.empno AND vl.vlstatus != 'canceled'
                        LEFT JOIN `hrms`.`holiday` ho ON ho.holiday_day = st.datefromto  
                        WHERE st.empno = ? AND st.datefromto BETWEEN ? AND ? AND st.sched_type = ? ORDER BY st.datefromto ASC";
$stmt = $HRconnect->prepare($select_schedule);
$cmp_sched = "cmp_sched";
$stmt->bind_param("isss", $empid, $datefrom, $dateto, $cmp_sched);
$stmt->execute();
$employee_schedule = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// echo "<pre>".print_r($employee_schedule, true)."</pre>";
$stmt->close();
#endregion
// ------------------------------------------------------------------------------//

// Night Differential -----------------------------------------------------------//
$night_diff_start = "22:00";
$night_diff_end = "06:00";
// Compressed sched calculation -------------------------------------------------//
$total_work_hours = 0;
$late = 0;
$undertime = 0;
$leave = 0;
// Ordinary Day -----------------------------------------------------------------//
$ordinary_ot = 0;
$ordinary_nd = 0; // 22:00 - 06:00 night diffential hours
$ordinary_ndot = 0; // 22:00 - 06:00 
// Special Holiday --------------------------------------------------------------//
$special_hrs = 0;
$special_ot = 0;
$special_nd = 0;
$special_ndot = 0;
// Legal Holiday ----------------------------------------------------------------//
$legal_hrs = 0;
$legal_ot = 0;
$legal_nd = 0;
$legal_ndot = 0;
// Working Dayoff ----------------------------------------------------------------//
$working_off = 0;
// ------------------------------------------------------------------------------//

function nightDiffCalcu($schedfrom, $night_start, $night_end, $timeout, $work_hours, $break, $holiday_type)
{
    $normal_hrs = 0;
    $night_diff = 0;
    if (date("H:i", $schedfrom) < "06:00") {
        $night_start = strtotime(date("Y-m-d", strtotime("-1 day", $schedfrom)) . " " . "22:00");
        $night_end = strtotime(date("Y-m-d", $schedfrom) . " " . "06:00");
    } else {
        $night_start = strtotime(date("Y-m-d", $schedfrom) . " " . "22:00");
        $night_end = strtotime(date("Y-m-d", strtotime("+1 day", $schedfrom)) . " " . "06:00");
    }
    for ($counter = 1; $counter <= ($work_hours + $break) * 60; $counter++) {
        $sched_range = strtotime("+" . $counter . " minutes", $schedfrom);
        if ($holiday_type == 1) {
            if (($sched_range > $night_start && $sched_range <= $night_end) && $sched_range <= $timeout) {
                $night_diff++;
            } else if (($sched_range > /*$timein*/ $schedfrom) && ($sched_range <= $timeout)) {
                $normal_hrs++;
            }
        } else {

            if (($sched_range > $night_start && $sched_range <= $night_end) && $sched_range <= $timeout) {
                $night_diff++;
            } else {
                $normal_hrs++;
            }
        }
    }
    $hours_container = array(
        "normal_hrs" => (($normal_hrs + $night_diff) / 60) - $break,
        "night_diff" => $night_diff / 60
    );
    return $hours_container;
}

function OTcalculator($schedto, $night_start, $night_end, $ot_hours, $schedfrom, $work_sched, $timeout, $ot_type, $timein_broken)
{
    $overtime = 0;
    $nd_overtime = 0;
    $night_start = strtotime(date("Y-m-d", $schedfrom) . " " . "23:00");
    $night_end = strtotime(date("Y-m-d", strtotime("+1 day", $schedfrom)) . " " . "06:00");
    $has_half_hour = $ot_hours - floor($ot_hours);
    if ($ot_type == 0) {
        if ($work_sched == "NWD") {
            for ($counter = 1; $counter <= $ot_hours; $counter++) {
                $sched_range = strtotime("+" . $counter . "hours", $schedfrom);
                if (($sched_range >= $night_start && $sched_range <= $night_end) && $sched_range <= $timeout) {
                    $nd_overtime++;
                } else {
                    $overtime++;
                }
            }

            if ($has_half_hour != 0) {
                $sched_range = strtotime("+" . floor($ot_hours) . "hours", $schedfrom);
                if ($sched_range >= $night_start && $sched_range <= $night_end) {
                    $nd_overtime += 0.5;
                } else {
                    $overtime += 0.5;
                }
            }
        } else {
            for ($counter = 1; $counter <= $ot_hours; $counter++) {
                $sched_range = strtotime("+" . $counter . "hours", $schedto);
                if ($sched_range >= $night_start && $sched_range <= $night_end) {
                    $nd_overtime++;
                } else {
                    $overtime++;
                }
            }

            if ($has_half_hour != 0) {
                $sched_range = strtotime("+" . floor($ot_hours) . "hours", $schedto);
                if ($sched_range >= $night_start && $sched_range <= $night_end) {
                    $nd_overtime += 0.5;
                } else {
                    $overtime += 0.5;
                }
            }
        }
    } else {
        if ($work_sched == "NWD") {
            for ($counter = 1; $counter <= $ot_hours; $counter++) {
                $sched_range = strtotime("+" . $counter . "hours", strtotime($timein_broken));
                if (($sched_range >= $night_start && $sched_range <= $night_end) && $sched_range <= $timeout) {
                    $nd_overtime++;
                } else {
                    $overtime++;
                }
            }

            if ($has_half_hour != 0) {
                $sched_range = strtotime("+" . floor($ot_hours) . "hours", strtotime($timein_broken));
                if ($sched_range >= $night_start && $sched_range <= $night_end) {
                    $nd_overtime += 0.5;
                } else {
                    $overtime += 0.5;
                }
            }
        } else {
            for ($counter = 1; $counter <= $ot_hours; $counter++) {
                $sched_range = strtotime("+" . $counter . "hours", strtotime($timein_broken));
                if ($sched_range >= $night_start && $sched_range <= $night_end) {
                    $nd_overtime++;
                } else {
                    // var_dump(date("Y-m-d H:i",$sched_range));
                    $overtime++;
                }
            }

            if ($has_half_hour != 0) {
                $sched_range = strtotime("+" . floor($ot_hours) . "hours", strtotime($timein_broken));
                if ($sched_range >= $night_start && $sched_range <= $night_end) {
                    $nd_overtime += 0.5;
                } else {
                    $overtime += 0.5;
                }
            }
        }
    }

    if ($ot_hours <= 0) {
        $overtime = 0;
        $nd_overtime = 0;
    }
    $overtime_container = array(
        "normal_ot" => $overtime + $nd_overtime,
        "nd_overtime" => $nd_overtime
    );
    return $overtime_container;
}

function checkPriorDates($holiday_prior_dates, $empid, $isCompressed, $workforceDivisionId, $HRconnect)
{
    $isHolidayEligible = 0;
    $backtrackDate = $holiday_prior_dates;

    for ($minusDay = 0; $minusDay < 3; $minusDay++) {
        $scheduleEligibility = "SELECT st.remarks,
                (CASE WHEN vl.vlstatus = 'approved' THEN 1 ELSE 0 END) AS approved_leave,
                (CASE WHEN st.M_timein != '' AND st.M_timeout != '' AND st.A_timein != '' AND st.A_timeout != '' THEN 1 ELSE 0 END) AS is_complete,
                (CASE WHEN ho.holiday_day != '' OR ho.holiday_day = null THEN 1 ELSE 0 END) AS is_holiday
            FROM 
                `hrms`.`sched_time` st
            LEFT JOIN 
                `hrms`.`vlform` vl ON vl.empno = st.empno AND vl.vldatefrom = st.datefromto AND vl.vlstatus != 'canceled' 
            LEFT JOIN 
                `hrms`.`holiday` ho ON ho.holiday_day = st.datefromto
            WHERE st.datefromto = '" . $backtrackDate . "' AND st.empno = $empid";
        $backtrackResult = $HRconnect->query($scheduleEligibility);
        $backtrackRow = $backtrackResult->fetch_assoc();
        $isApproveLeave = $backtrackRow['approved_leave'];
        $timeInputsComplete = $backtrackRow['is_complete'];
        $remarks = $backtrackRow['remarks'];
        $isHoliday = $backtrackRow['is_holiday'];
        if ($timeInputsComplete) {
            $isHolidayEligible = 1;
            break;
        } else if ($isApproveLeave) {
            $isHolidayEligible = 1;
            break;
        }

        if (!$isCompressed && ($remarks != 'RD' && $remarks != 'NWD' && ($workforceDivisionId != 3 || !$isHoliday))) {
            break;
        } elseif ($remarks != 'RD' && $remarks != 'NWD') {
            break;
        }

        $backtrackDate = date('Y-m-d', strtotime($backtrackDate . '-1 day'));

    }
    $status = false;
    if ($isHolidayEligible > 0) {
        $status = true;
    }
    return $status;
}

// Remarks -------------------------------------------------------------- //
function checkDTRconcern($datefrom, $empid, $HRconnect)
{
    $hasConcern = "SELECT status FROM `hrms`.`dtr_concerns` WHERE empno = $empid AND ConcernDate = '$datefrom' ORDER BY status ASC LIMIT 1";
    $query = $HRconnect->query($hasConcern);
    $result = $query->fetch_array();

    $status = "";
    if ($query->num_rows > 0) {
        if ($result["status"] == "Approved") {
            $status = "<i class='text-uppercase text-success'>" . $result["status"] . " Concern</i>";
        } else if ($result["status"] == "Pending") {
            $status = "<i class='text-uppercase text-danger'>" . $result["status"] . " Concern</i>";
        }
    }

    return $status;
}

function checkOBP($datefrom, $empid, $HRconnect)
{
    $hasConcern = "SELECT status FROM `hrms`.`obp` WHERE empno = $empid AND datefromto = '$datefrom'  ORDER BY timedate DESC LIMIT 1";
    $query = $HRconnect->query($hasConcern);
    $result = $query->fetch_array();

    $status = "";
    if ($query->num_rows > 0) {
        if ($result["status"] == "Approved") {
            $status = "<i class='text-uppercase text-success'>" . $result["status"] . " OBP</i>";
        } else if ($result["status"] == "Pending") {
            $status = "<i class='text-uppercase text-danger'>" . $result["status"] . " OBP</i>";
        } else if ($result["status"] == "Pending2") {
            $status = "<i class='text-uppercase text-danger'>Partially Approved OBP</i>";
        }
    }

    return $status;
}

function checkWDO($datefrom, $empid, $HRconnect)
{
    $hasConcern = "SELECT wdostatus FROM `hrms`.`working_dayoff` WHERE empno = $empid AND datefrom = '$datefrom'  ORDER BY timedate DESC LIMIT 1";
    $query = $HRconnect->query($hasConcern);
    $result = $query->fetch_array();

    $status = "";
    if ($query->num_rows > 0) {
        if ($result["wdostatus"] == "approved") {
            $status = "<i class='text-uppercase text-success'>" . $result["wdostatus"] . " WDO</i>";
        } else if ($result["wdostatus"] == "pending") {
            $status = "<i class='text-uppercase text-danger'>" . $result["wdostatus"] . " WDO</i>";
        } else if ($result["wdostatus"] == "pending2") {
            $status = "<i class='text-uppercase text-danger'>Partially Approved WDO</i>";
        }
    }

    return $status;
}

function checkCS($datefrom, $empid, $HRconnect)
{
    $hasConcern = "SELECT cs_status FROM `hrms`.`change_schedule` WHERE empno = $empid AND datefrom = '$datefrom'  ORDER BY timedate DESC LIMIT 1";
    $query = $HRconnect->query($hasConcern);
    $result = $query->fetch_array();

    $status = "";
    if ($query->num_rows > 0) {
        if ($result["cs_status"] == "approved") {
            $status = "<i class='text-uppercase text-success'>" . $result["cs_status"] . " CS</i>";
        } else if ($result["cs_status"] == "pending") {
            $status = "<i class='text-uppercase text-danger'>" . $result["cs_status"] . " CS</i>";
        } else if ($result["cs_status"] == "pending2") {
            $status = "<i class='text-uppercase text-danger'>Partially Approved CS</i>";
        }
    }

    return $status;
}

function checkVL($datefrom, $empid, $HRconnect)
{
    $hasConcern = "SELECT vlstatus FROM `hrms`.`vlform` WHERE empno = $empid AND vldatefrom = '$datefrom' ORDER BY timedate DESC LIMIT 1";
    $query = $HRconnect->query($hasConcern);
    $result = $query->fetch_array();

    $status = "";
    if ($query->num_rows > 0) {
        if ($result["vlstatus"] == "approved") {
            $status = "<i class='text-uppercase text-success'>" . $result["vlstatus"] . " WL</i>";
        } else if ($result["vlstatus"] == "pending") {
            $status = "<i class='text-uppercase text-danger'>" . $result["vlstatus"] . " WL</i>";
        } else if ($result["vlstatus"] == "pending2") {
            $status = "<i class='text-uppercase text-danger'>Partially Approved WL</i>";
        }
    }

    return $status;
}

function checkBackTrack($empno, $cutfrom, $cutto, $HRconnect)
{
    $is_generated = "SELECT COUNT(empno) AS is_generated FROM generated WHERE empno = ? AND datefrom = ? AND dateto = ?";
    $stmt = $HRconnect->prepare($is_generated);
    $stmt->bind_param("iss", $empno, $cutfrom, $cutto);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    // return false;
    return $result["is_generated"] > 0;
}

function getGenerated($empno, $cutfrom, $cutto, $HRconnect)
{
    $is_generated = "SELECT * FROM generated WHERE empno = ? AND datefrom = ? AND dateto = ? LIMIT 1";
    $stmt = $HRconnect->prepare($is_generated);
    $stmt->bind_param("iss", $empno, $cutfrom, $cutto);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $sched_computation_arr = array(
        "workdays" => $result["dayswork"],
        "late" => $result["lateover"],
        "undertime" => $result["undertime"],
        "leave" => $result["vleave"],
        'ordinary_nd' => $result["nightdiff"],
        'ordinary_ot' => $result["regularot"],
        'ordinary_ndot' => $result["nightdiffot"],
        'special_hrs' => $result["specialday"],
        'special_nd' => $result["specialdaynd"],
        'special_ot' => $result["specialdayot"],
        'special_ndot' => $result["specialdayndot"],
        'legal_hrs' => $result["legalday"],
        'legal_nd' => $result["legaldaynd"],
        'legal_ot' => $result["legaldayot"],
        'legal_ndot' => $result["legaldayndot"],
        'working_off' => $result["workdayoiff"]
    );

    return $sched_computation_arr;
}
// ---------------------------------------------------------------------- //

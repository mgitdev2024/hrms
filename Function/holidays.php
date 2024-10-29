<?php
$HRconnect = mysqli_connect("localhost", "root", "", "hrms");
if (isset($_GET['dates'])) {
    if ($_GET['dates'] == 'getHolidays') {
        $holidayType = $_GET['holiday_type'] ?? 0;
        echo getHoliday($HRconnect, $holidayType);
        exit;
    } else if ($_GET['dates'] == 'create_holiday') {
        if (!boolval($_GET['is_edit'])) {
            echo createHoliday($HRconnect, $_POST['holiday_name'], $_POST['holiday_date'], $_POST['holiday_type']);
        } else {
            echo editHoliday($HRconnect, $_POST['holiday_name'], $_POST['holiday_date'], $_POST['holiday_type'], $_POST['holiday_id']);
        }
        exit;
    } else if ($_GET['dates'] == 'delete_holiday') {
        echo deleteHoliday($HRconnect, $_POST['holiday_id']);
        exit;
    }
}

function getHoliday($HRconnect, $holiday_type)
{
    $select_yearly_leave = "SELECT base_year, current_year FROM `hrms`.`holiday_yearly_leave`";
    $query_yearly_leave = $HRconnect->query($select_yearly_leave);
    $yearly_leave = $query_yearly_leave->fetch_array();
    $base_year = $yearly_leave['base_year'];
    $current_year = $yearly_leave['current_year'];
    $select_holiday_dates = "SELECT idholiday, holiday_day, name, prior1, type FROM `hrms`.`holiday` WHERE holiday_day BETWEEN ? AND ? ";
    if ($holiday_type == 0) {
        $select_holiday_dates .= "AND type = 0 ORDER BY holiday_day DESC";
    } else if ($holiday_type == 1) {
        $select_holiday_dates .= "AND type = 1 ORDER BY holiday_day DESC";
    } else {
        $select_holiday_dates .= "ORDER BY holiday_day DESC";
    }
    $stmt = $HRconnect->prepare($select_holiday_dates);
    $stmt->bind_param('ss', $base_year, $current_year);
    $stmt->execute();
    $result_holiday_dates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $holiday_dates_list = [];
    $holiday_all_details = [];
    foreach ($result_holiday_dates as $holiday_date) {
        $holiday_dates_list[] = $holiday_date['holiday_day'];
        $holiday_all_details[] = [
            'holiday_day' => $holiday_date['holiday_day'],
            'name' => $holiday_date['name'],
            'prior1' => $holiday_date['prior1'],
            'id' => $holiday_date['idholiday'],
            'type' => $holiday_date['type'],
        ];
    }
    $response = [
        'holiday_dates' => $holiday_dates_list,
        'holiday_all_details' => $holiday_all_details,
        'current_year' => $current_year,
        'base_year' => $base_year,
    ];

    return json_encode($response);
}

function createHoliday($HRconnect, $holiday_name, $holiday_date, $holiday_type)
{
    $HRconnect->begin_transaction();

    try {

        $query = "SELECT EXISTS(SELECT 1 FROM hrms.holiday WHERE holiday_day = ?) AS holiday_exists";
        $stmt = $HRconnect->prepare($query);

        if (!$stmt) {
            throw new Exception("Failed to prepare check statement: " . $HRconnect->error);
        }
        $stmt->bind_param("s", $holiday_date);
        $stmt->execute();
        $stmt->bind_result($holiday_exists);
        $stmt->fetch();
        $stmt->close();
        if ($holiday_exists) {
            throw new Exception("The holiday date already exists.");
        }

        $insert_query = "INSERT INTO hrms.holiday (name, holiday_day, type" . ($holiday_type == 0 ? ", prior1" : "") . ") VALUES (?, ?, ?" . ($holiday_type == 0 ? ", ?" : "") . ")";
        $stmt = $HRconnect->prepare($insert_query);

        if (!$stmt) {
            throw new Exception("Failed to prepare insert statement: " . $HRconnect->error);
        }

        // Bind parameters conditionally
        if ($holiday_type == 0) {
            $prior_date = date('Y-m-d', strtotime($holiday_date . ' -1 day'));
            $stmt->bind_param("ssis", $holiday_name, $holiday_date, $holiday_type, $prior_date);
        } else {
            $stmt->bind_param("ssi", $holiday_name, $holiday_date, $holiday_type);
        }

        // Execute statement
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }


        $HRconnect->commit();
        $stmt->close();
        return json_encode([
            'status' => 'success',
            'message' => 'Holiday has been successfully created!'
        ]);
    } catch (Exception $e) {
        $HRconnect->rollback();
        return json_encode([
            'status' => 'error',
            'message' => 'Failed to create holiday: ' . $e->getMessage()
        ]);
    }
}

function deleteHoliday($HRconnect, $holiday_id)
{
    $HRconnect->begin_transaction();
    try {
        $query = "DELETE FROM hrms.holiday WHERE idholiday =?";
        $stmt = $HRconnect->prepare($query);
        if (!$stmt) {
            throw new Exception("Failed to prepare delete statement: " . $HRconnect->error);
        }
        $stmt->bind_param("i", $holiday_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $HRconnect->commit();
        $stmt->close();
        return json_encode([
            'status' => 'success',
            'message' => 'Holiday has been successfully deleted!'
        ]);

    } catch (Exception $e) {
        $HRconnect->rollback();
        return json_encode([
            'status' => 'error',
            'message' => 'Failed to delete holiday: ' . $e->getMessage()
        ]);
    }
}

function editHoliday($HRconnect, $holidayName, $holidayDate, $holidayType, $holidayId)
{
    $HRconnect->begin_transaction();
    try {
        // QUERY HOLIDAY EXIST
        $queryExist = "SELECT EXISTS(SELECT 1 FROM hrms.holiday WHERE holiday_day = ? AND idholiday != ?) AS holiday_exists";
        $stmtExist = $HRconnect->prepare($queryExist);

        if (!$stmtExist) {
            throw new Exception("Failed to prepare check statement: " . $HRconnect->error);
        }
        $stmtExist->bind_param("si", $holidayDate, $holidayId);
        $stmtExist->execute();
        $stmtExist->bind_result($holiday_exists);
        $stmtExist->fetch();
        $stmtExist->close();
        if ($holiday_exists) {
            throw new Exception("The holiday date already exists.");
        }
        // END QUERY EXIST

        // UPDATE CURRENT HOLIDAY 
        $query = "UPDATE hrms.holiday SET name =?, holiday_day =?, type =?, prior1 = ? WHERE idholiday =?";
        $priorDate = null;
        if ($holidayType == 0) {
            $priorDate = date('Y-m-d', strtotime($holidayDate . ' -1 day'));
        }
        $stmt = $HRconnect->prepare($query);
        $stmt->bind_param("ssisi", $holidayName, $holidayDate, $holidayType, $priorDate, $holidayId);
        $stmt->execute();
        $HRconnect->commit();
        // END UPDATE
        $HRconnect->commit();
        $stmt->close();
        return json_encode([
            'status' => 'success',
            'message' => 'Holiday has been successfully updated!'
        ]);
    } catch (Exception $e) {
        $HRconnect->rollback();
        return json_encode([
            'status' => 'error',
            'message' => 'Failed to delete holiday: ' . $e->getMessage()
        ]);
    }
}

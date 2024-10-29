let cutFrom = null;
let cutTo = null;
let overtimeDate = null;
let overtimeType = null;
let totalOvertimeHours = 0;
let empno = null;
$(function () {
    empno = $("#empno").val();
    console.log(empno);
    index();
});

function index() {
    $.ajax({
        url: 'Function/overtime_func.php?overtime=getSession',
        method: 'GET',
        data: { empno: empno },
        success: async function (response) {
            console.log(response);

            let jsonResponse = JSON.parse(response);
            cutFrom = jsonResponse.cutfrom;
            cutTo = jsonResponse.cutto;
            await renderCalendar();
        }
    });
}

async function renderCalendar() {
    $overtimeDateButton = $('#overtimeDate');
    $overtimeDateButton.html(
        `<i class="fa fa-calendar" aria-hidden="true"></i>`
    );
    $overtimeDateButton.removeAttr('disabled');
    $overtimeDateButton.removeClass('btn-secondary');
    $overtimeDateButton.addClass('btn-primary');
    flatpickr("#overtimeDate", {
        dateFormat: "Y-m-d",
        minDate: cutFrom,
        maxDate: cutTo,
        disableMobile: true,
        onChange: function (selectedDates, dateStr, instance) {
            overtimeDate = dateStr;

            showOvertimeDate(dateStr);

            let date = new Date(dateStr);
            const readableDate = date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            $('#selected-date').html(readableDate);
            $('#selected-date').attr('data-overtime-date', overtimeDate);
        }
    });
}

function selectOvertimeType() {
    let type = $("#choose-overtime-type").val();
    overtimeType = type

    if (overtimeDate != null) {
        showOvertimeDate(overtimeDate);
    }
}

function showOvertimeDate(date) {
    if (overtimeType != null) {
        onLoad();

        $.ajax({
            url: 'Function/overtime_func.php?overtime=getTimeInputs',
            method: 'GET',
            data: {
                type: overtimeType,
                date: date,
                empno: empno
            },
            success: function (response) {
                Swal.close();
                console.log(response)
                let jsonResponse = JSON.parse(response);
                let maxOvertime = jsonResponse.max_overtime_hours;
                let isEligibleHalfHour = jsonResponse.is_half_hour_eligible;
                $("#captured-time-inputs").removeClass("d-none");

                if (overtimeType == "regular_schedule") {
                    $("#note-time-coverage").addClass("d-none");
                    $("#broken-time-inputs").addClass("d-none");
                    $("#selected-none").addClass("d-none");
                    $("#regular-time-inputs").removeClass("d-none");
                    let timein = jsonResponse.m_timein ?? "--";
                    let breakout = jsonResponse.m_timeout ?? "--";
                    let breakin = jsonResponse.a_timein ?? "--";
                    let timeout = jsonResponse.a_timeout ?? "--";
                    $("#m_timein").html(timein);
                    $("#m_timeout").html(breakout);
                    $("#a_timein").html(breakin);
                    $("#a_timeout").html(timeout);

                    showRenderedOvertimeHours(maxOvertime, isEligibleHalfHour);
                } else if (overtimeType == "broken_schedule") {
                    $("#regular-time-inputs").addClass("d-none");
                    $("#selected-none").addClass("d-none");
                    $("#broken-time-inputs").removeClass("d-none");
                    let brokenTimein = jsonResponse.broken_sched_in ?? "--";
                    let brokenTimeout = jsonResponse.broken_sched_out ?? "--";
                    $("#broken_timein").html(brokenTimein);
                    $("#broken_timeout").html(brokenTimeout);

                    let timeCoverage = jsonResponse.broken_time_coverage;
                    if (timeCoverage > 0) {
                        $("#note-time-coverage").removeClass("d-none");
                        $("#note-time-coverage").html("Note: Your overtime has been automatically adjusted to compensate for your undertime: " + timeCoverage + " hours.");
                    }
                    showRenderedOvertimeHours(maxOvertime, isEligibleHalfHour);
                } else {
                    $("#note-time-coverage").addClass("d-none");
                    $("#captured-time-inputs").addClass("d-none");
                    $("#rendered-overtime-hours").addClass("d-none");
                    $("#selected-none").removeClass("d-none");
                }
            }
        });
    }
}

function createOvertime() {
    const overtimeDate = $("#selected-date").attr("data-overtime-date");
    let overtimeType = $("#choose-overtime-type").val() == 0 ? null : $("#choose-overtime-type").val();
    const overtimeHours = parseFloat($("#filed-ot-hours").html());
    const isCertified = $("#certification").prop("checked");
    const reason = $("#overtime-reason").val();

    $("#submit-ot").attr("disabled");
    // Validate each field
    if (!validateField(overtimeDate, 'Please select an overtime date.') ||
        !validateField(overtimeType, 'Please select an overtime type.') ||
        !validateField(overtimeHours, 'Please enter valid overtime hours.') ||
        !validateField(reason, 'Please enter a reason.') ||
        !validateField(isCertified, 'Please tick the certification below.')) {
        $("#submit-ot").removeAttr("disabled");
        return;
    }

    if (overtimeType == "broken_schedule") {
        overtimeType = 1;
    } else {
        overtimeType = 0;
    }
    $.ajax({
        url: 'Function/overtime_func.php?overtime=createOvertime',
        method: 'POST',
        data: {
            overtime_date: overtimeDate,
            overtime_type: overtimeType,
            overtime_hours: overtimeHours,
            overtime_reason: reason,
            empno: empno
        },
        success: async function (response) {
            let jsonResponse = JSON.parse(response);

            if (jsonResponse.success) {
                Swal.fire({
                    title: 'Overtime Filed Successfully',
                    icon: 'success',
                    iconHtml: '<i class="fas fa-check-circle fa-sm"></i>',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000,
                }).then((result) => {
                    let empno = $("#empno").val();
                    window.location.href = "pdf/print_ot.php?ot=ot&empno=" + empno + "&cutfrom=" + cutFrom + "&cutto=" + cutTo + "&m=1";
                });
            } else {
                $("#submit-ot").removeAttr("disabled");
                Swal.fire({
                    icon: 'warning',
                    title: 'Overtime Already Filed Error',
                    toast: true,
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    position: 'top-right',
                });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $("#submit-ot").removeAttr("disabled");
            Swal.fire({
                icon: 'warning',
                title: 'Overtime Filed Error',
                toast: true,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                position: 'top-right',
            });
        }
    });
}

function validateField(value, message) {
    if (!value || (typeof value === 'number' && value <= 0)) {
        showValidationError(message);
        return false;
    }
    return true;
}
function showValidationError(message) {
    Swal.fire({
        icon: 'warning',
        title: 'Incomplete fields',
        text: message,
        toast: true,
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
        position: 'top-right',
    });
}

function showRenderedOvertimeHours(maxOvertime, isEligibleHalfHour) {
    totalOvertimeHours = 0;
    $("#rendered-overtime-hours").removeClass("d-none");
    $("#add-overtime-whole").attr("max", maxOvertime);
    $("#add-overtime-whole").attr("value", maxOvertime);
    if (maxOvertime <= 0) {
        $("#add-overtime-whole").attr("min", 0);
    }
    if (isEligibleHalfHour != "0" || isEligibleHalfHour != 0) {
        $("#add-half-hour").removeAttr("disabled");
        $("#add-half-hour").prop("checked", true);
        totalOvertimeHours += .50;
    } else {
        $("#add-half-hour").attr("disabled", true);
        $("#add-half-hour").prop("checked", false);
    }

    if (maxOvertime > 0) {
        totalOvertimeHours += maxOvertime;
    }
    $("#add-overtime-wholes").html(totalOvertimeHours);
    $("#filed-ot-hours").html(totalOvertimeHours);
}

function alterOvertimeWhole() {
    let currentHourValue = $("#add-overtime-whole").val();
    if (totalOvertimeHours.toString().includes('.')) {
        currentHourValue = parseFloat(currentHourValue + ".50");
    }
    totalOvertimeHours = currentHourValue
    $("#filed-ot-hours").html(totalOvertimeHours);
}

function tickHalfHour() {
    let halfHourOvertime = .50;
    if ($("#add-half-hour").prop("checked")) {
        halfHourOvertime = parseFloat(totalOvertimeHours + ".50");
    } else {
        halfHourOvertime = parseInt(totalOvertimeHours);
    }
    totalOvertimeHours = halfHourOvertime
    $("#filed-ot-hours").html(totalOvertimeHours);

}
function onLoad() {
    Swal.fire({
        imageUrl: 'images/fetching-data.gif',
        imageHeight: 300,
        imageWidth: 400,
        title: 'Getting data from Cloud...',
        position: 'center',
        showConfirmButton: false,
        timerProgressBar: true,
        showCancelButton: false,
        didOpen: (toast) => {
            Swal.showLoading();
        },
    });
}
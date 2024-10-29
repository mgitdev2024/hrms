let holidates = [];
let base_year = null;
let current_year = null;
$(function () {
	createHolidayIndex('all');
});
function createHolidayIndex(holiday_type) {
	holidates = [];
	$.ajax({
		url: 'Function/holidays.php?dates=getHolidays&holiday_type=' + holiday_type,
		type: 'GET',
		dataType: 'json',
		success: function (response) {
			$holiday_button = $('#holiday-date');
			$holiday_button.removeClass('bg-secondary');
			$holiday_button.addClass('bg-primary');
			$date_icon = $holiday_button.find('#date-icon');
			$spinner = $holiday_button.find('.spinner-border');
			$spinner.hide();
			$date_icon.html(
				`<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                fill="currentColor" class="bi bi-calendar-event" viewBox="0 0 16 16">
                <path
                    d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z" />
                <path
                    d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" />
                </svg>`
			);
			$('#holiday-date').attr('disabled', false);
			for (let i = 0; i < response.holiday_dates.length; i++) {
				holidates.push(response.holiday_dates[i]);
			}
			renderHolidayList(response.holiday_all_details); // Render Holiday List
			base_year = response.base_year;
			current_year = response.current_year;
		},

		error: function (jqXHR, textStatus, errorThrown) {
			console.log(jqXHR, textStatus, errorThrown);
		}
	});
}

function createHoliday() {
	$('#createHolidayModal').modal('show');
	$('#createHolidayTitle').html('Create New Holiday');
	$('#create-holiday-button').attr('onclick', 'submitHoliday()');
	let selected_date_element = $('#input-selected-date');
	let holiday_name_element = $('#holiday-name');
	let holiday_type_element = $('#choose-holiday-type');
	selected_date_element.removeAttr('data-value');
	selected_date_element.val('');
	holiday_name_element.val('');
	holiday_type_element.val('');
	flatpickr("#holiday-date", {
		dateFormat: "Y-m-d",
		minDate: base_year,
		maxDate: current_year,
		disable: holidates,
		disableMobile: true,
		onChange: function (selectedDates, dateStr, instance) {
			let selected_date = null;
			selectedDates.forEach(function (date, index) {
				let mostRecentDate = date;
				let year = mostRecentDate.getFullYear();
				let month = (mostRecentDate.getMonth() + 1).toString().padStart(2, '0');
				let day = mostRecentDate.getDate().toString().padStart(2, '0');
				let formatted_date = year + '-' + month + '-' + day;

				let readable_date = mostRecentDate.toLocaleDateString('en-US', {
					weekday: 'long',
					year: 'numeric',
					month: 'long',
					day: 'numeric'
				});
				selected_date = {
					readable_date: readable_date,
					formatted_date: formatted_date
				};
			});
			$('#input-selected-date').attr('data-value', selected_date.formatted_date);
			$('#input-selected-date').val(selected_date.readable_date);
		}
	});
}
function submitHoliday(isEdit = 0, holiday_id = null) {
	let create_holiday_button = $('#create-holiday-button');
	create_holiday_button.attr('disabled', 'disabled');
	create_holiday_button.removeClass('btn-primary');
	create_holiday_button.addClass('btn-secondary');
	create_holiday_button.html(
		`<span class="spinner-border spinner-border-sm mr-3" role="status"></span> Creating`
	);

	let selected_date_element = $('#input-selected-date');
	let holiday_name_element = $('#holiday-name');
	let holiday_type_element = $('#choose-holiday-type');
	const selected_date = selected_date_element.attr('data-value');
	const holiday_name = holiday_name_element.val();
	const holiday_type = holiday_type_element.val();

	if (!holiday_type) {
		showErrorAndResetModal('Please choose a holiday type.', create_holiday_button);
		return;
	}
	if (!selected_date) {
		showErrorAndResetModal('Please select a date.', create_holiday_button);
		return;
	}
	if (!holiday_name) {
		showErrorAndResetModal('Please enter the holiday name.', create_holiday_button);
		return;
	}

	$.ajax({
		url: 'Function/holidays.php?dates=create_holiday&is_edit=' + isEdit,
		type: 'POST',
		data: {
			holiday_name: holiday_name,
			holiday_type: holiday_type,
			holiday_date: selected_date,
			holiday_id: holiday_id
		},
		success: function (response) {
			console.log(response)
			let jsonResponse = JSON.parse(response);
			if (jsonResponse.status == 'success') {
				$('#createHolidayModal').modal('hide');
				create_holiday_button.removeAttr('disabled');
				create_holiday_button.addClass('btn-primary');
				create_holiday_button.removeClass('btn-secondary');
				create_holiday_button.html(
					`Create`
				);
				selected_date_element.removeAttr('data-value');
				selected_date_element.val('');
				holiday_name_element.val('');
				holiday_type_element.val('');
				Swal.fire({
					title: 'Success',
					text: jsonResponse.message,
					icon: 'success',
					toast: true,
					timer: 3000,
					position: 'top-end',
					showConfirmButton: false,
					timerProgressBar: true,
					showCancelButton: false,
				}).then(function () {
					createHolidayIndex('all');
				});
			} else {
				$('#createHolidayModal').modal('hide');
				create_holiday_button.removeAttr('disabled');
				create_holiday_button.addClass('btn-primary');
				create_holiday_button.removeClass('btn-secondary');
				create_holiday_button.html(
					`Create`
				);
				selected_date_element.removeAttr('data-value');
				selected_date_element.val('');
				holiday_name_element.val('');
				holiday_type_element.val('');
				Swal.fire({
					title: 'Error',
					text: jsonResponse.message,
					icon: 'error',
					toast: true,
					timer: 3000,
					position: 'top-end',
					showConfirmButton: false,
					timerProgressBar: true,
					showCancelButton: false,
				});
			}
		},
		error: function (jqXHR, textStatus, errorThrown) {
			$('#createHolidayModal').modal('hide');
			create_holiday_button.removeAttr('disabled');
			create_holiday_button.addClass('btn-primary');
			create_holiday_button.removeClass('btn-secondary');
			create_holiday_button.html(
				`Create`
			);
			selected_date_element.removeAttr('data-value');
			selected_date_element.val('');
			holiday_name_element.val('');
			holiday_type_element.val('');
			Swal.fire({
				title: 'Error',
				text: jsonResponse.message,
				icon: 'error',
				toast: true,
				position: 'top-end',
				showConfirmButton: false,
				timerProgressBar: true,
				showCancelButton: false,
			});
		}
	});
}

function renderHolidayList(jsonResponse) {
	let holidayList = $('#holiday-list').DataTable();
	holidayList.clear();
	holidayList.rows.add(jsonResponse.map(holiday => {
		let holidayDate = new Date(holiday.holiday_day);
		let formattedDate = holidayDate.toLocaleDateString('en-US', {
			year: 'numeric',
			month: 'long',
			day: 'numeric'
		});
		let weekday = holidayDate.toLocaleDateString('en-US', { weekday: 'long' });
		let formattedHolidayDate = `${formattedDate} (${weekday})`;
		let sortableDate = holidayDate.toISOString().split('T')[0]; // YYYY-MM-DD

		let holidayType = holiday.type;
		let holidayName = holiday.name ?? '--';
		return [
			holidayName,
			(holidayType == '0' ? 'Legal Holiday' : 'Special Holiday'),
			`<span class="d-none">${sortableDate}</span><span>${formattedHolidayDate}</span>`,
			// holiday.prior1 == '' ? '--' : holiday.prior1,
			`<div class="d-flex justify-content-around align-items-center">
                <button class="btn btn-success" onclick="editHoliday('${holiday.id}',this)">
                    <span class="text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
                        </svg>
                    </span>
                </button>
                <button class="btn btn-danger" onclick="deleteHoliday('${holiday.id}')">
                    <span class="text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                            <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                        </svg>
                    </span>
                </button>
            </div>`

		];
	})).draw();
	holidayList.order([0, 'desc']).draw();
}

function showErrorAndResetModal(message, create_holiday_button) {
	Swal.fire({
		title: 'Error',
		text: message,
		icon: 'warning',
		confirmButtonText: 'Understood',
	}).then(function () {
		create_holiday_button.removeAttr('disabled')
			.addClass('btn-primary')
			.removeClass('btn-secondary')
			.html('Create');
	});
}

function deleteHoliday(holiday_id) {
	Swal.fire({
		title: 'Are you sure?',
		text: "You won't be able to revert this!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#d33',
		cancelButtonColor: '#3085d6',
		confirmButtonText: 'Yes, delete it!'
	}).then((result) => {
		if (result.isConfirmed) {
			$.ajax({
				url: 'Function/holidays.php?dates=delete_holiday',
				type: 'POST',
				data: {
					holiday_id: holiday_id
				},
				success: function (response) {
					let jsonResponse = JSON.parse(response);
					if (jsonResponse.status == 'success') {
						Swal.fire({
							title: 'Deleted',
							text: jsonResponse.message,
							icon: 'success',
							toast: true,
							timer: 1500,
							position: 'top-end',
							showConfirmButton: false,
							timerProgressBar: true,
							showCancelButton: false,
						}).then(function () {
							createHolidayIndex('all');
						});
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					Swal.fire({
						title: 'Error',
						text: jsonResponse.message,
						icon: 'error',
						confirmButtonText: 'Understood'
					});
					console.log(jqXHR, textStatus, errorThrown);
				}
			});
		}
	});
}

function editHoliday(holiday_id, element) {
	const parentElement = $(element).closest('tr');
	$('#createHolidayTitle').html('Edit Holiday');
	$('#createHolidayModal').modal('show');
	$('#create-holiday-button').attr('onclick', 'submitHoliday(1,' + holiday_id + ')');
	let tableData = parentElement.find('td');

	let holidayDate = $(tableData[2]).find('span')[0].textContent;
	let type = $(tableData[1]).text() == 'Special Holiday' ? 1 : 0;
	let name = $(tableData[0]).text();
	let date = new Date(holidayDate);
	let holidayFormattedDate = date.toLocaleDateString('en-US', {
		weekday: 'long', // Friday
		year: 'numeric', // 2024
		month: 'long',   // October
		day: 'numeric'   // 4
	});

	let registeredDates = holidates.slice();
	let index = registeredDates.indexOf(holidayDate);

	if (index !== -1) {
		registeredDates.splice(index, 1);
	}
	let calendar = flatpickr("#holiday-date", {
		dateFormat: "Y-m-d",
		disable: registeredDates,
		onChange: function (selectedDates, dateStr, instance) {
			let selected_date = null;
			selectedDates.forEach(function (date, index) {
				let mostRecentDate = date;
				let year = mostRecentDate.getFullYear();
				let month = (mostRecentDate.getMonth() + 1).toString().padStart(2, '0');
				let day = mostRecentDate.getDate().toString().padStart(2, '0');
				let formatted_date = year + '-' + month + '-' + day;

				let readable_date = mostRecentDate.toLocaleDateString('en-US', {
					weekday: 'long',
					year: 'numeric',
					month: 'long',
					day: 'numeric'
				});
				selected_date = {
					readable_date: readable_date,
					formatted_date: formatted_date
				};
			});
			$('#input-selected-date').attr('data-value', selected_date.formatted_date);
			$('#input-selected-date').val(selected_date.readable_date);
		}
	});
	calendar.setDate(holidayDate);

	$('#choose-holiday-type').val(type);
	$('#holiday-name').val(name);
	$('#input-selected-date').val(holidayFormattedDate);
	$('#input-selected-date').attr('data-value', holidayDate);
}

function filterHolidayType() {
	let holiday_type = $('#holiday-type').val();
	createHolidayIndex(holiday_type);
}
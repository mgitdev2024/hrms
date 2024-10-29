<?php
$HRconnect = mysqli_connect("localhost", "root", "", "hrms");
session_start();

if (!(isset($_SESSION['user_validate']))) {
	header("Location:index.php?&m=2");
}
$empid = $_SESSION['user_validate'];
$select_details = "SELECT name, branch, department,userid, position, vl, datehired FROM `hrms`.`user_info` WHERE empno = ?";
$stmt = $HRconnect->prepare($select_details);
$stmt->bind_param("i", $empid);
$stmt->execute();
$employee_details = $stmt->get_result()->fetch_array();
$stmt->close();

$name = $employee_details["name"];
$position = $employee_details["position"];
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="">
	<meta name="author" content="">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<title>Mary Grace Foods Inc.</title>
	<link rel="icon" href="images/logoo.png">
	<!-- Custom fonts for this template-->
	<link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
	<link
		href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
		rel="stylesheet">
	<!-- Date Picker -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<!-- Custom styles for this template-->
	<link href="css/sb-admin-2.min.css" rel="stylesheet">
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"> -->

	<!-- SWAL -->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<!-- AJAX -->
	<script src="js/ajax-overtime.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
	<!-- JavaScript -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

	<!-- Flat picker CDN -->
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	<style>
		select {
			text-align-last: center;
		}

		input.largerCheckbox {
			width: 25px;
			height: 25px;
		}

		input[type=checkbox]+label {
			color: #8D9099;
			font-style: italic;
		}

		input[type=checkbox]:checked+label {
			color: #0000FF;
			font-style: normal;
		}

		.text-small {
			font-size: 80%;
		}

		.bg-disabled {
			background-color: #f2f2f2;
		}

		@media (max-width: 991px) {
			.border-right {
				border-right: none !important;
			}
		}

		@media (min-width: 991px) {
			.border-top {
				border-top: none !important;
			}
		}
	</style>
</head>

<body class="bg-gradient-muted">
	<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
		<a href="index.php" class="navbar-brand">
			<img src="images/logoo.png" height="35" alt=""> <i
				style="color:#7E0000;font-family:Times New Roman, cursive;font-size:120%;">Mary Grace Café</i>
		</a>

		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup"
			aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="navbarNavAltMarkup">
			<div class="navbar-nav ml-auto text-center">
				<a href="login.php" class="nav-item nav-link"
					style="font-family:Times New Roman, cursive;font-size:120%;">Login</a>
			</div>
		</div>
	</nav>

	<div class="container">
		<div class="card o-hidden border-0 shadow-lg my-2">
			<!-- card body -->
			<div class="card-body">
				<div class="d-flex flex-column align-items-center">
					<h1 class="h5 text-gray-900 mb-3 text-center">
						<small>Human Resource Department</small>
						<p class="m-0">Overtime Request</p>
					</h1>
				</div>

				<div class="row">
					<div class="col-lg-6 col-sm-12 border-right pt-3 mt-3">
						<div class="container">
							<p class="">Employee Details</p>
							<div class="row">
								<div class="col-lg-12">
									<input id="empno" type="text" class="d-none" value="<?php echo $empid; ?>">
									<input id="cutfrom" type="text" class="d-none" value="<?php echo $cutfrom; ?>">
									<input id="cutto" type="text" class="d-none" value="<?php echo $cutto; ?>">
									<input type="text"
										class="form-control form-control-user bg-gray-100 text-small text-center rounded-pill"
										value="<?php echo $name; ?>" readonly>
								</div>
							</div>
							<div class="row">
								<div class="col-lg-12 col-sm-12">
									<input type="text"
										class="form-control bg-gray-100 text-small text-center rounded-pill mt-3"
										value="<?php echo $position; ?>" readonly>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-6 col-sm-12 pt-3 mt-3 border-top">
						<div class="card-body">
							<div class="border mb-4">
								<select class="form-control border-0 text-small rounded bg-light "
									id="choose-overtime-type" onchange="selectOvertimeType()">
									<option value="0" selected>- Select OT Type -</option>
									<option value="regular_schedule">Regular Overtime</option>
									<option value="broken_schedule">Broken Schedule Overtime</option>
								</select>

							</div>
							<div class="d-flex align-items-center p-2 border rounded bg-light mb-4">
								<span class="mr-3  text-muted">Select a date for overtime:</span>
								<button id="overtimeDate" class="btn d-flex align-items-center" disabled>
									<span class="spinner-border spinner-border-sm mr-2" role="status"></span>
								</button>
							</div>


							<span>
								Date:
								<p id="selected-date" class="font-weight-bold"></p>
							</span>
						</div>
					</div>
				</div>
			</div>
			<!-- end card body -->
			<hr>

			<div class="container">
				<div class="card-body">
					<div class="row" id="selected-none">
						<div class="col-lg-6 col-sm-12 mb-4">
							<div class="d-flex justify-content-center align-items-center" style="height: 100%">
								<div class="d-flex flex-column">
									<h5 class="text-muted">Please select an OT Type</h5>
								</div>
							</div>
						</div>
						<div class="col-lg-6 col-sm-12">
							<div class="d-flex justify-content-center align-items-center" style="height: 100%">
								<img src="images/interpersonal-graphic.png" alt="" width="250">
							</div>
						</div>
					</div>

					<div class="font-weight-bold d-none" id="captured-time-inputs">
						<p id="captured-time-inputs-text">Captured Time Inputs: </p>
						<div class="row" id="regular-time-inputs">
							<div class="col-lg-3 col-sm-6">
								<label for="m_timein">Time In</label>
								<p id="m_timein" class="bg-disabled border p-2 rounded text-center"></p>
							</div>
							<div class="col-lg-3 col-sm-6">
								<label for="m_timeout">Break Out</label>
								<p id="m_timeout" class="bg-disabled border p-2 rounded text-center"></p>

							</div>
							<div class="col-lg-3 col-sm-6">
								<label for="a_timein">Break In</label>
								<p id="a_timein" class="bg-disabled border p-2 rounded text-center"></p>

							</div>
							<div class="col-lg-3 col-sm-6">
								<label for="a_timeout">Time Out</label>
								<p id="a_timeout" class="bg-disabled border p-2 rounded text-center"></p>
							</div>
						</div>
						<div class="row" id="broken-time-inputs">
							<div class="col-lg-6 col-sm-6">
								<label for="broken_timein">Broken Sched In</label>
								<p id="broken_timein" class="bg-disabled border p-2 rounded text-center"></p>
							</div>
							<div class="col-lg-6 col-sm-6">
								<label for="broken_timeout">Broken Sched Out</label>
								<p id="broken_timeout" class="bg-disabled border p-2 rounded text-center"></p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<hr>

			<div class="container">
				<div class="card-body">
					<div class="font-weight-bold d-none" id="rendered-overtime-hours">
						<p>Rendered Overtime Hours:</p>
						<i class="font-weight-normal d-none" id="note-time-coverage"></i>
						<div class="d-lg-flex justify-content-between align-items-center">
							<div class="row">
								<!-- Main Hour Input -->
								<div class="ml-3 py-3">
									<input class="form-control text-center w-auto" id="add-overtime-whole" type="number"
										min="1" onchange="alterOvertimeWhole()">
								</div>
								<!-- Compress Toggle Switch -->
								<div class="ml-3 d-flex align-items-center py-3">
									<div class="custom-control custom-switch d-none" id="switch-div">
										<input type="checkbox" class="custom-control-input" id="add-half-hour" disabled
											onchange="tickHalfHour()" disabled>
										<label class="custom-control-label" for="add-half-hour"
											style="user-select:none">Add
											Half Hour</label>
									</div>
								</div>
							</div>
							<div class="d-flex align-items-center border p-3 rounded-lg w-auto">
								<span>Total OT Hour/s: </span>
								<span class="text-success ml-3" id="filed-ot-hours">--</span>
							</div>
						</div>
						<textarea class="form-control mt-3" name="" id="overtime-reason"
							placeholder="Reason for Overtime"></textarea>
					</div>
				</div>
			</div>
			<!-- card footer -->
			<div class="card-footer">
				<div class="container">
					<div class="form-group">
						<div class="custom-control custom-checkbox small">
							<input type="checkbox" class="custom-control-input" id="certification" required>
							<label class="custom-control-label" for="certification" required>
								I hereby Certify that the above infomation provided is correct. Any falsification
								of information in this regar may form ground for disciplinary action up to and including
								dismissal.
							</label>
						</div>
					</div>
					<div class="d-flex justify-content-between border-top py-3">
						<a class="btn btn-secondary bg-gradient-secondary"
							href="index.php?empno=5182&SubmitButton=Submit">Back</a>
						<button id="submit-ot" class="btn btn-primary bg-gradient-primary"
							onclick="createOvertime()">Submit</button>
					</div>
					<div class="d-flex justify-content-end border-top py-2">
						<a class="small float-right" href="pdf/print_ot.php?ot=ot&empno=<?php echo $empid; ?>">View
							Filed Overtime <i class="fa fa-angle-right" aria-hidden="true"></i></a>
					</div>
				</div>
			</div>
			<!-- end card footer -->
		</div>
	</div>
</body>

</html>
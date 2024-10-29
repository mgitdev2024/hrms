<?php
$ORconnect = mysqli_connect("localhost", "root", "", "db");
$HRconnect = mysqli_connect("localhost", "root", "", "hrms");
date_default_timezone_set('Asia/Manila');
session_start();


if (empty($_SESSION['user'])) {
  header('location:login.php');
}
$sql = "SELECT * FROM user_info WHERE empno = '" . $_SESSION['empno'] . "'";
$query = $HRconnect->query($sql);
$row = $query->fetch_array();

$user = $row['name'];
$userlevel = $row['userlevel'];
$department = $row['department'];
$branch = $row['branch'];
$userrid = $row['userid'];
$mothercafe = $row['mothercafe'];
$empno = $row['empno'];

$getDateSQL = "SELECT si.datefrom, si.dateto FROM user_info ui LEFT JOIN sched_info si 
ON si.empno = ui.empno
WHERE si.status = 'Pending' AND ui.empno = $empno;";
$querybuilder = $HRconnect->query($getDateSQL);
$rowCutOff = $querybuilder->fetch_array();

$cutFrom = $rowCutOff['datefrom'];
$cutTo = $rowCutOff['dateto'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <title>Mary Grace Foods Inc.</title>
  <link rel="icon" href="images/logoo.png">
  <!-- Custom fonts for this template -->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link
    href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
    rel="stylesheet">
  <!-- Custom styles for this template -->
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <!-- Date Picker -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <!-- Flat picker CDN -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <!-- Custom styles for this page -->
  <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  <!-- DataTables Buttons CSS -->
  <link rel="stylesheet" type="text/css"
    href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
  <!-- SWAL -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- MOMENT -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
  <!-- Lodash -->
  <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>

  <style>
    .text-small {
      font-size: 0.8rem;
    }

    #dataTable {
      font-size: 0.8rem;
    }

    .dt-buttons,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_paginate .paginate_button,
    .dataTables_wrapper .dataTables_info {
      font-size: 0.8rem;
    }

    .custom-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      /* Semi-transparent black */
    }

    table {
      min-width: 1000px;
    }

    @media print {
      .dt-print-view h1 {
        font-size: 1.5rem;
        font-weight: bold;
      }
    }
  </style>
</head>

<body id="page-top" class="sidebar-toggled">
  <?php
  include("navigation.php");
  include("createHolidayModal.php");
  ?>
  <div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-2">
      <div class="mb-1">
        <h4 class="mb-0 font-weight-bold">Holidays</h4>
        <h6 class="m-0">Cut-off Details (<?php echo "$cutFrom to $cutTo"; ?>)</h6>
      </div>
      <br>
    </div>

    <div class="mt-5 d-sm-flex justify-content-between">

    </div>
    <div class="row">
      <div class="col-xl-12 col-lg-12 mb-4">
        <div class="card border-left-primary shadow">
          <div class="card-header">
            <div class="d-sm-flex justify-content-between align-items-center">
              <div class="">
                <label for="holiday-type" class="text-small">Select Holiday Type:</label>
                <select class="form-control p-2 mb-3 w-auto text-small" id="holiday-type"
                  onchange="filterHolidayType()">
                  <option value="all" selected>ALL</option>
                  <option value="0">LEGAL HOLIDAY</option>
                  <option value="1">SPECIAL HOLIDAY</option>
                </select>
              </div>

              <div>
                <button class="btn btn-primary btn-sm mt-1 mb-3" onclick="createHoliday()">
                  <div class="d-flex justify-content-center align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" height=14 class="text-white"
                      fill="currentColor">
                      <path
                        d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z" />
                    </svg>
                    <span class="ml-2">
                      Create Holiday
                    </span>
                  </div>
                </button>
              </div>
            </div>
          </div>
          <!-- Card Body -->
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-hover table-bordered" width="100%" id="holiday-list">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Holiday Date</th>
                    <!-- <th>Prior Date</th> -->
                    <th>Action</th>
                  </tr>
                </thead>

                <tbody id="holiday-list-body">
                  <!-- Content -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>

  <!-- Footer -->
  <footer class="sticky-footer">
    <div class="container my-auto">
      <div class="copyright text-center my-auto">
        <span>Copyright © Mary Grace Foods Inc. 2019.</span>
      </div>
    </div>
  </footer>
  </div>
  </div>
  <!-- End of Page Wrapper -->

  <!-- Scroll to Top Button-->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <!-- Logout Modal-->
  <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
          <a class="btn btn-primary" href="logout.php">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap core JavaScript-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <!-- Core plugin JavaScript-->
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Custom scripts for all pages-->
  <script src="js/sb-admin-2.min.js"></script>

  <!-- Page level plugins -->
  <script src="vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <!-- Page level custom scripts -->
  <script src="js/demo/datatables-demo.js"></script>


  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
  <!-- Add DataTables JS -->
  <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
  <!-- DataTables Buttons JS -->
  <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <!-- JSZip -->
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <!-- PDFMake -->
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
  <!-- Buttons HTML5 -->
  <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <!-- Buttons Print -->
  <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
  <!-- SheetJS -->
  <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>

  <script src="js/ajax-create-holidays.js"></script>
</body>

</html>
<div class="modal fade" id="createHolidayModal" tabindex="-1" role="dialog" aria-labelledby="createHolidayModal"
    aria-hidden="false">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header d-flex flex-column">
                <div class="d-flex w-100">
                    <h5 class="modal-title" id="createHolidayTitle">-</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <br>
                <div>

                </div>

            </div>
            <div class="modal-body">
                <div>
                    <div class="d-flex flex-column">
                        <div class="mb-3">
                            <label for="">Type:</label><span class="ml-2 text-danger">*</span>
                            <select class="form-control p-2 w-auto text-small" id="choose-holiday-type">
                                <option value="" selected>- SELECT A HOLIDAY TYPE -</option>
                                <option value="0">LEGAL HOLIDAY</option>
                                <option value="1">SPECIAL HOLIDAY</option>

                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="">Name:</label><span class="ml-2 text-danger">*</span>
                            <input type="text" id="holiday-name" class="form-control text-center text-small">
                        </div>


                        <div class="mb-3">
                            <label for="">Date:</label><span class="ml-2 text-danger">*</span>
                            <div class="d-flex">
                                <button type="date" id="holiday-date" disabled
                                    class="form-control w-auto mr-2 text-center text-small text-white bg-secondary">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                    <span id="date-icon">

                                    </span>
                                </button>
                                <input type="text" class="form-control text-center text-small bg-white"
                                    id="input-selected-date" placeholder="- Declare the Holiday Date -" disabled>
                            </div>
                        </div>
                    </div>
                </div>

                <br>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary text-center" id="create-holiday-button">Create</button>
            </div>
        </div>
    </div>
</div>
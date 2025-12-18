<div class="modal fade" id="serial_manager_modal" tabindex="-1" role="dialog" aria-labelledby="serialManagerModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="serialManagerModalLabel">@lang('Serial Manager')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="serial_input">@lang('Serial Number')</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="serial_input" placeholder="@lang('Enter serial number')" maxlength="50">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-primary" id="add_serial_btn">@lang('Add')</button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bulk_serial_input">@lang('Bulk Import')</label>
                            <textarea class="form-control" id="bulk_serial_input" rows="3" placeholder="@lang('Enter multiple serial numbers, one per line')"></textarea>
                            <button type="button" class="btn btn-info btn-sm mt-2" id="import_bulk_serial_btn">@lang('Import')</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('Serial List') (<span id="serial_count">0</span> @lang('items'))</label>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="serial_list_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('Serial Number')</th>
                                                <th width="100">@lang('Status')</th>
                                                <th width="100">@lang('Action')</th>
                                            </tr>
                                        </thead>
                                    <tbody id="serial_list_body">
                                        <!-- Serial rows will be added here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('Cancel')</button>
                <button type="button" class="btn btn-success" id="save_serial_btn">@lang('Save')</button>
            </div>
        </div>
    </div>
</div>
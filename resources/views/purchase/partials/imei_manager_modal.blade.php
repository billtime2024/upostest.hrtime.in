<div class="modal fade" id="imei_manager_modal" tabindex="-1" role="dialog" aria-labelledby="imeiManagerModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="imeiManagerModalLabel">@lang('IMEI Manager')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="imei_input">@lang('IMEI Number')</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="imei_input" placeholder="@lang('Enter IMEI number')" maxlength="15">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-primary" id="add_imei_btn">@lang('Add')</button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bulk_imei_input">@lang('Bulk Import')</label>
                            <textarea class="form-control" id="bulk_imei_input" rows="3" placeholder="@lang('Enter multiple IMEI numbers, one per line')"></textarea>
                            <button type="button" class="btn btn-info btn-sm mt-2" id="import_bulk_imei_btn">@lang('Import')</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('IMEI List') (<span id="imei_count">0</span> @lang('items'))</label>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="imei_list_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('IMEI Number')</th>
                                                <th width="100">@lang('Status')</th>
                                                <th width="100">@lang('Action')</th>
                                            </tr>
                                        </thead>
                                    <tbody id="imei_list_body">
                                        <!-- IMEI rows will be added here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('Cancel')</button>
                <button type="button" class="btn btn-success" id="save_imei_btn">@lang('Save')</button>
            </div>
        </div>
    </div>
</div>
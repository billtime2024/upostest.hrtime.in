<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-6">
            <div class="form-group">
                {!! Form::label('whatsapp_instance_id', __('lang_v1.whatsapp_instance_id') . ':') !!}
                {!! Form::text('whatsapp_settings[instance_id]', !empty($whatsapp_settings['instance_id']) ? '••••••••••••••••' : null, ['class' => 'form-control','placeholder' => __('lang_v1.whatsapp_instance_id'), 'id' => 'whatsapp_instance_id']); !!}
                @if(!empty($whatsapp_settings['instance_id']))
                    <small class="text-muted">@lang('lang_v1.value_hidden_for_security')</small>
                @endif
            </div>
        </div>
        <div class="col-xs-6">
            <div class="form-group">
                {!! Form::label('whatsapp_access_token', __('lang_v1.whatsapp_access_token') . ':') !!}
                {!! Form::text('whatsapp_settings[access_token]', !empty($whatsapp_settings['access_token']) ? '••••••••••••••••' : null, ['class' => 'form-control','placeholder' => __('lang_v1.whatsapp_access_token'), 'id' => 'whatsapp_access_token']); !!}
                @if(!empty($whatsapp_settings['access_token']))
                    <small class="text-muted">@lang('lang_v1.value_hidden_for_security')</small>
                @endif
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('show_whatsapp_values', false, false, ['id' => 'show_whatsapp_values']); !!}
                        @lang('lang_v1.show_hidden_values')
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 col-xs-12">
            <div class="form-group">
                <div class="input-group">
                    {!! Form::text('test_whatsapp_number', null, ['class' => 'form-control','placeholder' => __('lang_v1.test_number'), 'id' => 'test_whatsapp_number']); !!}
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-success pull-right" id="test_whatsapp_btn">@lang('lang_v1.test_whatsapp_configuration')</button>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Store original values
    var originalInstanceId = '{{ !empty($whatsapp_settings['instance_id']) ? $whatsapp_settings['instance_id'] : '' }}';
    var originalAccessToken = '{{ !empty($whatsapp_settings['access_token']) ? $whatsapp_settings['access_token'] : '' }}';

    $('#show_whatsapp_values').change(function() {
        if ($(this).is(':checked')) {
            $('#whatsapp_instance_id').val(originalInstanceId);
            $('#whatsapp_access_token').val(originalAccessToken);
        } else {
            $('#whatsapp_instance_id').val('••••••••••••••••');
            $('#whatsapp_access_token').val('••••••••••••••••');
        }
    });
});
</script>
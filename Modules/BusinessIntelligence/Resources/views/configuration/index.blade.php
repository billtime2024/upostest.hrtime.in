@extends('businessintelligence::layouts.app')

@section('page_title', __('businessintelligence::lang.configuration'))
@section('page_subtitle', __('businessintelligence::lang.settings'))

@section('bi_content')

<div class="row">
    <div class="col-md-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#dashboard_tab" data-toggle="tab">
                    <i class="fa fa-dashboard"></i> @lang('businessintelligence::lang.dashboard_settings')
                </a></li>
                <li><a href="#ai_tab" data-toggle="tab">
                    <i class="fa fa-robot"></i> @lang('businessintelligence::lang.ai_settings')
                </a></li>
                <li><a href="#alerts_tab" data-toggle="tab">
                    <i class="fa fa-bell"></i> @lang('businessintelligence::lang.alert_thresholds')
                </a></li>
                <li><a href="#performance_tab" data-toggle="tab">
                    <i class="fa fa-tachometer"></i> @lang('businessintelligence::lang.performance_settings')
                </a></li>
            </ul>
            <div class="tab-content">
                <!-- Dashboard Settings -->
                <div class="active tab-pane" id="dashboard_tab">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Automatic refresh has been disabled to reduce system load. Use the manual refresh button when needed.
                    </div>
                    <form id="dashboard_settings_form">
                        <div class="form-group">
                            <label>@lang('businessintelligence::lang.cache_ttl')</label>
                            <input type="number"
                                   class="form-control"
                                   name="cache_ttl"
                                   value="600"
                                   min="60"
                                   max="3600">
                            <span class="help-block">Cache time-to-live in seconds</span>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> @lang('businessintelligence::lang.save')
                        </button>
                    </form>
                </div>

                <!-- AI Settings -->
                <div class="tab-pane" id="ai_tab">
                    <form id="ai_settings_form">
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" 
                                           name="enable_ai_insights" 
                                           {{ $configurations->where('config_key', 'enable_ai_insights')->first()?->getTypedValue() ?? true ? 'checked' : '' }}>
                                    @lang('businessintelligence::lang.enable_ai_insights')
                                </label>
                            </div>
                            <span class="help-block">Enable or disable AI-powered insights generation</span>
                        </div>
                        
                        <div class="form-group">
                            <label>AI Provider</label>
                            <select class="form-control" name="ai_provider">
                                <option value="rule-based">Rule-Based (Default - No API Key)</option>
                                <option value="openai">OpenAI (Requires API Key)</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="openai_settings" style="display: none;">
                            <label>OpenAI API Key</label>
                            <input type="password" 
                                   class="form-control" 
                                   name="openai_api_key" 
                                   placeholder="sk-...">
                            <span class="help-block">Your OpenAI API key for advanced AI insights</span>
                        </div>
                        
                        <div class="callout callout-info">
                            <h4><i class="fa fa-info-circle"></i> About AI Providers</h4>
                            <p><strong>Rule-Based:</strong> Uses pattern recognition and threshold analysis. No API key required.</p>
                            <p><strong>OpenAI:</strong> Advanced natural language insights using GPT-4. Requires API key from OpenAI.</p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> @lang('businessintelligence::lang.save')
                        </button>
                    </form>
                </div>

                <!-- Alert Thresholds -->
                <div class="tab-pane" id="alerts_tab">
                    <form id="alert_settings_form">
                        <div class="form-group">
                            <label>@lang('businessintelligence::lang.low_stock_threshold')</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="low_stock_threshold" 
                                   value="{{ $configurations->where('config_key', 'low_stock_threshold')->first()?->getTypedValue() ?? 10 }}"
                                   min="1"
                                   max="100">
                            <span class="help-block">Alert when product quantity falls below this number</span>
                        </div>
                        
                        <div class="form-group">
                            <label>@lang('businessintelligence::lang.overdue_days_threshold')</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="overdue_days_threshold" 
                                   value="{{ $configurations->where('config_key', 'overdue_days_threshold')->first()?->getTypedValue() ?? 30 }}"
                                   min="1"
                                   max="365">
                            <span class="help-block">Number of days before payment is considered overdue</span>
                        </div>
                        
                        <div class="form-group">
                            <label>@lang('businessintelligence::lang.profit_margin_threshold')</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="profit_margin_threshold" 
                                   value="15"
                                   min="0"
                                   max="100">
                            <span class="help-block">Alert when profit margin falls below this percentage</span>
                        </div>
                        
                        <div class="form-group">
                            <label>@lang('businessintelligence::lang.expense_spike_threshold')</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="expense_spike_threshold" 
                                   value="50"
                                   min="10"
                                   max="200">
                            <span class="help-block">Alert when expenses increase by this percentage</span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> @lang('businessintelligence::lang.save')
                        </button>
                    </form>
                </div>

                <!-- Performance Settings -->
                <div class="tab-pane" id="performance_tab">
                    <form id="performance_settings_form">
                        <div class="callout callout-warning">
                            <h4><i class="fa fa-warning"></i> Advanced Settings</h4>
                            <p>Changing these settings may affect module performance. Only modify if you understand the implications.</p>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="enable_cache" checked>
                                    Enable Metrics Caching
                                </label>
                            </div>
                            <span class="help-block">Cache frequently accessed metrics for better performance</span>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="auto_generate_insights">
                                    Auto-Generate Daily Insights
                                </label>
                            </div>
                            <span class="help-block">Automatically generate insights daily (requires cron setup)</span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> @lang('businessintelligence::lang.save')
                        </button>
                        
                        <button type="button" class="btn btn-warning" id="reset_defaults">
                            <i class="fa fa-undo"></i> @lang('businessintelligence::lang.reset')
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('bi_scripts')
<script>
$(document).ready(function() {
    // Show/hide OpenAI settings based on provider
    $('select[name="ai_provider"]').change(function() {
        if ($(this).val() === 'openai') {
            $('#openai_settings').slideDown();
        } else {
            $('#openai_settings').slideUp();
        }
    }).trigger('change');

    // Save dashboard settings
    $('#dashboard_settings_form').submit(function(e) {
        e.preventDefault();
        saveSettings('dashboard', $(this).serialize());
    });

    // Save AI settings
    $('#ai_settings_form').submit(function(e) {
        e.preventDefault();
        saveSettings('ai', $(this).serialize());
    });

    // Save alert settings
    $('#alert_settings_form').submit(function(e) {
        e.preventDefault();
        saveSettings('alerts', $(this).serialize());
    });

    // Save performance settings
    $('#performance_settings_form').submit(function(e) {
        e.preventDefault();
        saveSettings('performance', $(this).serialize());
    });

    // Reset to defaults
    $('#reset_defaults').click(function() {
        swal({
            title: '@lang("businessintelligence::lang.reset_confirm")',
            text: 'All settings will be reset to default values.',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then((confirmed) => {
            if (confirmed) {
                $.post('{{ route("businessintelligence.configuration.reset") }}', {}, function(response) {
                    if (response.success) {
                        toastr.success('@lang("businessintelligence::lang.configuration_updated")');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                });
            }
        });
    });

    function saveSettings(category, data) {
        $.ajax({
            url: '{{ route("businessintelligence.configuration.update-multiple") }}',
            method: 'POST',
            data: data + '&category=' + category,
            success: function(response) {
                if (response.success) {
                    toastr.success('@lang("businessintelligence::lang.configuration_updated")');
                }
            },
            error: function(xhr) {
                toastr.error('@lang("businessintelligence::lang.error")');
            }
        });
    }
});
</script>
@endpush


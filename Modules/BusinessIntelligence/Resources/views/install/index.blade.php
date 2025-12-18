@extends('layouts.app')

@section('title', __('Install Business Intelligence Module'))

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>
        Business Intelligence Module
        <small>Installation</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-download"></i> Install Business Intelligence Module
                    </h3>
                </div>
                <div class="box-body">
                    <div id="installation_status">
                        <div class="callout callout-info">
                            <h4><i class="fa fa-info-circle"></i> About Business Intelligence Module</h4>
                            <p>
                                This module provides AI-powered analytics, automated insights, and comprehensive 
                                business performance monitoring for your POS system.
                            </p>
                            <ul>
                                <li>Real-time KPI Dashboard</li>
                                <li>AI-Generated Insights & Recommendations</li>
                                <li>Interactive Charts & Visualizations</li>
                                <li>Sales, Inventory, Financial Analytics</li>
                                <li>Automated Alerts & Notifications</li>
                                <li>Predictive Analytics</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning" id="installation_warning">
                            <h4><i class="fa fa-warning"></i> Before Installation</h4>
                            <p>Please ensure:</p>
                            <ul>
                                <li>You have admin privileges</li>
                                <li>Your database is backed up</li>
                                <li>You have at least some transaction data for analysis</li>
                            </ul>
                        </div>

                        <div class="alert alert-info" id="reinstall_warning" style="display: none;">
                            <h4><i class="fa fa-info-circle"></i> Reinstallation Detected</h4>
                            <p>Some module tables already exist in the database.</p>
                            <p>Clicking "Install Module" will <strong>drop existing tables and reinstall</strong>.</p>
                            <p class="text-danger"><strong>Warning:</strong> This will delete all existing BI data (insights, reports, alerts).</p>
                        </div>

                        <div id="installation_progress" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped active" 
                                     role="progressbar" 
                                     style="width: 0%"
                                     id="progress_bar">
                                    <span id="progress_text">0%</span>
                                </div>
                            </div>
                            <p class="text-center" id="progress_message">Starting installation...</p>
                        </div>

                        <div id="installation_result" style="display: none;"></div>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="button" 
                            class="btn btn-primary btn-lg" 
                            id="install_btn">
                        <i class="fa fa-download"></i> Install Module
                    </button>
                    <button type="button" 
                            class="btn btn-warning btn-lg" 
                            id="install_direct_btn"
                            title="Use this if standard installation fails">
                        <i class="fa fa-wrench"></i> Alternative Install
                    </button>
                    <a href="{{ url('/') }}" class="btn btn-default btn-lg">
                        <i class="fa fa-arrow-left"></i> Cancel
                    </a>
                    <br><br>
                    <small class="text-muted">
                        <i class="fa fa-info-circle"></i> 
                        If standard installation fails, try the <strong>Alternative Install</strong> method (runs SQL directly)
                    </small>
                </div>
            </div>

            <!-- Installation Steps -->
            <div class="box box-solid" id="installation_steps" style="display: none;">
                <div class="box-header with-border">
                    <h3 class="box-title">Installation Steps</h3>
                </div>
                <div class="box-body">
                    <ul class="list-unstyled">
                        <li id="step_1" class="margin-bottom-10">
                            <i class="fa fa-spinner fa-spin text-muted"></i>
                            <span class="text-muted">Running database migrations...</span>
                        </li>
                        <li id="step_2" class="margin-bottom-10">
                            <i class="fa fa-spinner fa-spin text-muted"></i>
                            <span class="text-muted">Creating default configurations...</span>
                        </li>
                        <li id="step_3" class="margin-bottom-10">
                            <i class="fa fa-spinner fa-spin text-muted"></i>
                            <span class="text-muted">Setting up permissions...</span>
                        </li>
                        <li id="step_4" class="margin-bottom-10">
                            <i class="fa fa-spinner fa-spin text-muted"></i>
                            <span class="text-muted">Finalizing installation...</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Check module status on load
    checkModuleStatus();

    $('#install_btn').click(function() {
        installModule(false);
    });

    $('#install_direct_btn').click(function() {
        swal({
            title: 'Alternative Installation',
            text: 'This method runs SQL directly and bypasses Laravel migrations. Use this if the standard method fails. Continue?',
            icon: 'warning',
            buttons: true,
        }).then((confirmed) => {
            if (confirmed) {
                installModule(true);
            }
        });
    });

    function installModule(useDirect) {
        // Disable install buttons
        $('#install_btn, #install_direct_btn').prop('disabled', true);
        $('#install_btn').html('<i class="fa fa-spinner fa-spin"></i> Installing...');
        
        // Hide warning, show progress
        $('#installation_warning, #reinstall_warning').fadeOut();
        $('#installation_progress').fadeIn();
        $('#installation_steps').fadeIn();
        
        // Simulate progress
        updateProgress(25, 'Running database migrations...');
        updateStep('step_1', 'progress');
        
        // Choose URL based on installation method
        const installUrl = useDirect ? 
            '{{ route("businessintelligence.install.direct") }}' : 
            '{{ route("businessintelligence.install.process") }}';
        
        // Make AJAX call to install
        $.ajax({
            url: installUrl,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Update steps
                    updateStep('step_1', 'success');
                    updateProgress(50, 'Creating configurations...');
                    
                    setTimeout(function() {
                        updateStep('step_2', 'success');
                        updateProgress(75, 'Setting up permissions...');
                    }, 500);
                    
                    setTimeout(function() {
                        updateStep('step_3', 'success');
                        updateProgress(90, 'Finalizing...');
                    }, 1000);
                    
                    setTimeout(function() {
                        updateStep('step_4', 'success');
                        updateProgress(100, 'Installation complete!');
                        showSuccess();
                    }, 1500);
                } else {
                    showError(response.message || 'Installation failed');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Installation failed. Please check the logs.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showError(errorMessage);
            }
        });
    }
    
    function updateProgress(percent, message) {
        $('#progress_bar').css('width', percent + '%');
        $('#progress_text').text(percent + '%');
        $('#progress_message').text(message);
    }
    
    function updateStep(stepId, status) {
        let icon = '';
        let className = '';
        
        if (status === 'progress') {
            icon = '<i class="fa fa-spinner fa-spin text-info"></i>';
            className = 'text-info';
        } else if (status === 'success') {
            icon = '<i class="fa fa-check text-success"></i>';
            className = 'text-success';
        } else if (status === 'error') {
            icon = '<i class="fa fa-times text-danger"></i>';
            className = 'text-danger';
        }
        
        $('#' + stepId).find('i').replaceWith(icon);
        $('#' + stepId).find('span').removeClass('text-muted').addClass(className);
    }
    
    function showSuccess() {
        $('#installation_result').html(`
            <div class="alert alert-success">
                <h4><i class="fa fa-check"></i> Installation Successful!</h4>
                <p>The Business Intelligence module has been installed successfully.</p>
                <p>
                    <a href="{{ url('/business-intelligence/dashboard') }}" class="btn btn-success">
                        <i class="fa fa-dashboard"></i> Go to Dashboard
                    </a>
                    <a href="{{ url('/business-intelligence/insights/generate') }}" class="btn btn-primary">
                        <i class="fa fa-lightbulb-o"></i> Generate First Insights
                    </a>
                </p>
            </div>
        `).fadeIn();
        
        $('#install_btn').html('<i class="fa fa-check"></i> Installed').addClass('btn-success');
    }
    
    function showError(message) {
        updateStep('step_1', 'error');
        updateStep('step_2', 'error');
        updateStep('step_3', 'error');
        updateStep('step_4', 'error');
        
        $('#installation_result').html(`
            <div class="alert alert-danger">
                <h4><i class="fa fa-times"></i> Installation Failed</h4>
                <p>${message}</p>
                <p>Please check the error logs and try again.</p>
                <button type="button" class="btn btn-warning" onclick="location.reload()">
                    <i class="fa fa-refresh"></i> Try Again
                </button>
            </div>
        `).fadeIn();
        
        $('#install_btn, #install_direct_btn').prop('disabled', false);
        $('#install_btn').html('<i class="fa fa-download"></i> Install Module').removeClass('btn-success');
    }

    function checkModuleStatus() {
        $.get('{{ route("businessintelligence.status") }}', function(response) {
            if (response.installed) {
                // Some tables exist - show reinstall warning
                let existingTables = [];
                for (let table in response.tables) {
                    if (response.tables[table]) {
                        existingTables.push(table);
                    }
                }
                
                if (existingTables.length > 0) {
                    $('#reinstall_warning').fadeIn();
                    $('#install_btn').html('<i class="fa fa-refresh"></i> Reinstall Module')
                        .removeClass('btn-primary')
                        .addClass('btn-warning');
                }
            }
        }).fail(function() {
            // Ignore error on status check
            console.log('Status check failed - proceeding with fresh install');
        });
    }
});
</script>
@endsection


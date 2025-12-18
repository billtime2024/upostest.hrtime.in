@extends('businessintelligence::layouts.app')

@section('page_title', __('businessintelligence::lang.insights'))
@section('page_subtitle', __('businessintelligence::lang.ai_generated_insights'))

@section('bi_content')

<style>
.insights-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px;
    border-radius: 10px;
    color: white;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
}

.insights-header h2 {
    margin: 0 0 10px 0;
    color: white;
    font-size: 28px;
    font-weight: 600;
}

.insights-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.stats-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    text-align: center;
    transition: all 0.3s ease;
    border-top: 4px solid;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
}

.stats-card.stats-all { border-top-color: #667eea; }
.stats-card.stats-critical { border-top-color: #dc3545; }
.stats-card.stats-high { border-top-color: #ffc107; }
.stats-card.stats-pending { border-top-color: #17a2b8; }

.stats-card .stats-icon {
    font-size: 40px;
    margin-bottom: 15px;
}

.stats-card.stats-all .stats-icon { color: #667eea; }
.stats-card.stats-critical .stats-icon { color: #dc3545; }
.stats-card.stats-high .stats-icon { color: #ffc107; }
.stats-card.stats-pending .stats-icon { color: #17a2b8; }

.stats-card .stats-number {
    font-size: 36px;
    font-weight: 700;
    margin: 10px 0;
    color: #333;
}

.stats-card .stats-label {
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.filter-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    padding: 25px;
    margin-bottom: 25px;
}

.filter-card .filter-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
}

.insight-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    padding: 25px;
    margin-bottom: 20px;
    border-left: 5px solid #667eea;
    transition: all 0.3s ease;
    opacity: 0;
    animation: fadeInUp 0.5s ease forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.insight-card:hover {
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.insight-card.insight-critical {
    border-left-color: #dc3545;
}

.insight-card.insight-high {
    border-left-color: #ffc107;
}

.insight-card.insight-medium {
    border-left-color: #17a2b8;
}

.insight-card.insight-low {
    border-left-color: #28a745;
}

.insight-icon-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin-bottom: 10px;
}

.insight-critical .insight-icon-wrapper {
    background: linear-gradient(135deg, #ff6b6b 0%, #dc3545 100%);
    color: white;
}

.insight-high .insight-icon-wrapper {
    background: linear-gradient(135deg, #ffd93d 0%, #ffc107 100%);
    color: white;
}

.insight-medium .insight-icon-wrapper {
    background: linear-gradient(135deg, #6bcfff 0%, #17a2b8 100%);
    color: white;
}

.insight-low .insight-icon-wrapper {
    background: linear-gradient(135deg, #6ae792 0%, #28a745 100%);
    color: white;
}

.insight-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
    margin-top: 0;
}

.insight-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.insight-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    background: #f8f9fa;
    border-radius: 20px;
    font-size: 13px;
    color: #666;
}

.insight-meta-item i {
    color: #667eea;
}

.insight-description {
    font-size: 15px;
    line-height: 1.6;
    color: #555;
    margin-bottom: 15px;
}

.insight-actions-box {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.insight-actions-box strong {
    color: #667eea;
    font-size: 15px;
}

.insight-actions-list {
    margin: 10px 0 0 0;
    padding-left: 20px;
}

.insight-actions-list li {
    margin-bottom: 8px;
    color: #555;
    line-height: 1.5;
}

.insight-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.insight-buttons .btn {
    border-radius: 20px;
    padding: 8px 20px;
    font-size: 13px;
    font-weight: 500;
}

.priority-badge {
    display: inline-block;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.priority-badge.badge-critical {
    background: linear-gradient(135deg, #ff6b6b 0%, #dc3545 100%);
    color: white;
}

.priority-badge.badge-high {
    background: linear-gradient(135deg, #ffd93d 0%, #ffc107 100%);
    color: white;
}

.priority-badge.badge-medium {
    background: linear-gradient(135deg, #6bcfff 0%, #17a2b8 100%);
    color: white;
}

.priority-badge.badge-low {
    background: linear-gradient(135deg, #6ae792 0%, #28a745 100%);
    color: white;
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 12px;
    background: #e9ecef;
    color: #666;
    margin-top: 10px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.empty-state i {
    font-size: 64px;
    color: #ccc;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #999;
    font-size: 20px;
    margin-bottom: 10px;
}

.empty-state p {
    color: #aaa;
    font-size: 15px;
}

.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.loading-overlay.active {
    display: flex;
}

.loading-spinner {
    text-align: center;
}

.loading-spinner .spinner {
    width: 60px;
    height: 60px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-spinner p {
    margin-top: 20px;
    font-size: 16px;
    color: #667eea;
    font-weight: 600;
}

.insights-container {
    min-height: 400px;
}
</style>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loading_overlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Loading insights...</p>
    </div>
</div>

<!-- Header -->
<div class="insights-header">
    <div class="row">
        <div class="col-md-8">
            <h2><i class="fas fa-brain"></i> @lang('businessintelligence::lang.ai_insights')</h2>
            <p>@lang('businessintelligence::lang.insights_subtitle')</p>
        </div>
        <div class="col-md-4 text-right">
            <button class="btn btn-light btn-lg" id="generate_insights" style="border-radius: 25px;">
                <i class="fas fa-magic"></i> @lang('businessintelligence::lang.generate')
            </button>
            <button class="btn btn-outline-light btn-lg" id="refresh_insights" style="border-radius: 25px; margin-left: 10px;">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row" id="stats_cards">
    <div class="col-md-3">
        <div class="stats-card stats-all">
            <div class="stats-icon">
                <i class="fas fa-lightbulb"></i>
            </div>
            <div class="stats-number" id="stats_all">0</div>
            <div class="stats-label">@lang('businessintelligence::lang.all_insights')</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card stats-critical">
            <div class="stats-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stats-number" id="stats_critical">0</div>
            <div class="stats-label">@lang('businessintelligence::lang.critical')</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card stats-high">
            <div class="stats-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stats-number" id="stats_high">0</div>
            <div class="stats-label">@lang('businessintelligence::lang.high')</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card stats-pending">
            <div class="stats-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stats-number" id="stats_pending">0</div>
            <div class="stats-label">Pending Action</div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="filter-card">
    <div class="filter-title">
        <i class="fas fa-filter"></i> @lang('businessintelligence::lang.filter_insights')
    </div>
    <div class="row">
        <div class="col-md-3">
            <label>@lang('businessintelligence::lang.type')</label>
            <select class="form-control select2" id="insight_type">
                <option value="">All Types</option>
                <option value="sales">Sales</option>
                <option value="inventory">Inventory</option>
                <option value="financial">Financial</option>
                <option value="customer">Customer</option>
            </select>
        </div>
        <div class="col-md-3">
            <label>@lang('businessintelligence::lang.priority')</label>
            <select class="form-control select2" id="insight_priority">
                <option value="">All Priorities</option>
                <option value="critical">Critical</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>
        </div>
        <div class="col-md-3">
            <label>Status</label>
            <select class="form-control select2" id="insight_status">
                <option value="active">Active</option>
                <option value="acknowledged">Acknowledged</option>
                <option value="resolved">Resolved</option>
                <option value="dismissed">Dismissed</option>
            </select>
        </div>
        <div class="col-md-3">
            <label>&nbsp;</label><br>
            <button class="btn btn-primary btn-block" id="apply_filters" style="border-radius: 20px;">
                <i class="fas fa-search"></i> @lang('businessintelligence::lang.apply_filter')
            </button>
        </div>
    </div>
</div>

<!-- Insights List -->
<div class="insights-container" id="insights_container">
    <div id="insights_list">
        <!-- Insights will be loaded here dynamically -->
    </div>
</div>

@endsection

@push('bi_scripts')
<script>
$(document).ready(function() {
    
    // Load insights on page load
    loadInsights();
    
    // Load statistics
    loadStatistics();
    
    // Apply filters
    $('#apply_filters').click(function() {
        loadInsights();
    });
    
    // Initialize select2 if available
    if ($.fn.select2) {
        $('.select2').select2({
            minimumResultsForSearch: Infinity
        });
    }
    
    // Auto-apply filter on any dropdown change
    $('#insight_type, #insight_priority, #insight_status').change(function() {
        console.log('Filter changed, reloading insights...');
        loadInsights();
    });
    
    // Refresh insights
    $('#refresh_insights').click(function() {
        $(this).find('i').addClass('fa-spin');
        loadInsights();
        setTimeout(() => {
            $('#refresh_insights i').removeClass('fa-spin');
        }, 1000);
    });
    
    /**
     * Load insights via AJAX
     */
    function loadInsights() {
        const type = $('#insight_type').val();
        const priority = $('#insight_priority').val();
        const status = $('#insight_status').val();
        
        console.log('Loading insights with filters:', { type, priority, status });
        
        // Show loading
        $('#loading_overlay').addClass('active');
        
        $.ajax({
            url: '{{ route("businessintelligence.insights.data") }}',
            method: 'GET',
            data: {
                type: type,
                priority: priority,
                status: status
            },
            success: function(response) {
                console.log('Insights loaded successfully:', response);
                console.log('Number of insights:', response.data ? response.data.length : 0);
                
                if (response.success && response.data && response.data.length > 0) {
                    renderInsights(response.data);
                    updateStatistics(response.data);
                } else {
                    console.log('No insights found, showing empty state');
                    showEmptyState();
                }
                
                $('#loading_overlay').removeClass('active');
            },
            error: function(xhr, status, error) {
                console.error('Error loading insights:', error);
                console.error('XHR response:', xhr.responseText);
                toastr.error('Failed to load insights. Please try again.');
                $('#loading_overlay').removeClass('active');
                showEmptyState();
            }
        });
    }
    
    /**
     * Render insights list
     */
    function renderInsights(insights) {
        console.log('Rendering insights:', insights.length);
        
        if (insights.length === 0) {
            showEmptyState();
            return;
        }
        
        let html = '';
        
        insights.forEach(function(insight, index) {
            const icon = insight.icon || 'fas fa-lightbulb';
            const insightDate = new Date(insight.insight_date);
            const formattedDate = insightDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const actionItems = Array.isArray(insight.action_items) ? insight.action_items : 
                                (typeof insight.action_items === 'string' ? JSON.parse(insight.action_items || '[]') : []);
            
            html += `
                <div class="insight-card insight-${insight.priority}" data-id="${insight.id}" style="animation-delay: ${index * 0.1}s">
                    <div class="row">
                        <div class="col-md-1 text-center">
                            <div class="insight-icon-wrapper">
                                <i class="${icon}"></i>
                            </div>
                            <span class="priority-badge badge-${insight.priority}">
                                ${insight.priority.toUpperCase()}
                            </span>
                        </div>
                        <div class="col-md-9">
                            <h3 class="insight-title">${insight.title}</h3>
                            
                            <div class="insight-meta">
                                <span class="insight-meta-item">
                                    <i class="fas fa-tag"></i>
                                    ${insight.insight_type.charAt(0).toUpperCase() + insight.insight_type.slice(1)}
                                </span>
                                <span class="insight-meta-item">
                                    <i class="fas fa-calendar"></i>
                                    ${formattedDate}
                                </span>
                                <span class="insight-meta-item">
                                    <i class="fas fa-chart-line"></i>
                                    Confidence: ${Math.round(insight.confidence_score)}%
                                </span>
                            </div>
                            
                            <p class="insight-description">${insight.description}</p>
                            
                            ${actionItems.length > 0 ? `
                            <div class="insight-actions-box">
                                <strong><i class="fas fa-tasks"></i> @lang('businessintelligence::lang.recommended_actions'):</strong>
                                <ol class="insight-actions-list">
                                    ${actionItems.map(action => `<li>${action}</li>`).join('')}
                                </ol>
                            </div>
                            ` : ''}
                            
                            ${insight.status !== 'active' ? `
                            <div class="status-badge">
                                <i class="fas fa-info-circle"></i> 
                                ${insight.status.charAt(0).toUpperCase() + insight.status.slice(1)}
                                ${insight.acknowledged_at ? ' - ' + new Date(insight.acknowledged_at).toLocaleDateString() : ''}
                            </div>
                            ` : ''}
                        </div>
                        <div class="col-md-2">
                            ${insight.status === 'active' ? `
                            <div class="insight-buttons">
                                <button class="btn btn-success btn-sm acknowledge-insight" data-id="${insight.id}">
                                    <i class="fas fa-check"></i> @lang('businessintelligence::lang.acknowledge')
                                </button>
                                <button class="btn btn-primary btn-sm resolve-insight" data-id="${insight.id}">
                                    <i class="fas fa-check-circle"></i> @lang('businessintelligence::lang.resolve')
                                </button>
                                <button class="btn btn-secondary btn-sm dismiss-insight" data-id="${insight.id}">
                                    <i class="fas fa-times"></i> @lang('businessintelligence::lang.dismiss')
                                </button>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('#insights_list').html(html);
    }
    
    /**
     * Show empty state
     */
    function showEmptyState() {
        const html = `
            <div class="empty-state">
                <i class="fas fa-lightbulb"></i>
                <h3>@lang('businessintelligence::lang.no_insights_found')</h3>
                <p>Click "Generate Insights" to analyze your business data and get AI-powered recommendations.</p>
                <button class="btn btn-primary btn-lg" id="generate_insights_empty" style="border-radius: 25px; margin-top: 20px;">
                    <i class="fas fa-magic"></i> @lang('businessintelligence::lang.generate')
                </button>
            </div>
        `;
        $('#insights_list').html(html);
    }
    
    /**
     * Update statistics cards
     */
    function updateStatistics(insights) {
        const total = insights.length;
        const critical = insights.filter(i => i.priority === 'critical').length;
        const high = insights.filter(i => i.priority === 'high').length;
        const pending = insights.filter(i => i.status === 'active').length;
        
        $('#stats_all').text(total);
        $('#stats_critical').text(critical);
        $('#stats_high').text(high);
        $('#stats_pending').text(pending);
    }
    
    /**
     * Load statistics separately
     */
    function loadStatistics() {
        $.ajax({
            url: '{{ route("businessintelligence.insights.data") }}',
            method: 'GET',
            data: { status: '' }, // Get all statuses
            success: function(response) {
                if (response.success && response.data) {
                    updateStatistics(response.data);
                }
            }
        });
    }
    
    /**
     * Generate new insights
     */
    $(document).on('click', '#generate_insights, #generate_insights_empty', function() {
        swal({
            title: '@lang("businessintelligence::lang.generating")',
            text: '@lang("businessintelligence::lang.please_wait")',
            icon: 'info',
            buttons: false,
            closeOnClickOutside: false,
        });
        
        $.post('{{ route("businessintelligence.insights.generate") }}', {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            swal.close();
            if (response.success) {
                swal({
                    title: 'Success!',
                    text: `Generated ${response.count} new insights!`,
                    icon: 'success',
                    timer: 2000,
                });
                setTimeout(function() {
                    loadInsights();
                    loadStatistics();
                }, 2000);
            } else {
                swal({
                    title: 'Error',
                    text: response.message || 'Failed to generate insights',
                    icon: 'error',
                });
            }
        }).fail(function(xhr) {
            swal.close();
            const error = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to generate insights. Please try again.';
            swal({
                title: 'Error',
                text: error,
                icon: 'error',
            });
        });
    });
    
    /**
     * Acknowledge insight
     */
    $(document).on('click', '.acknowledge-insight', function() {
        const insightId = $(this).data('id');
        const $card = $('[data-id="' + insightId + '"]');
        
        $.post('/business-intelligence/insights/' + insightId + '/acknowledge', {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            if (response.success) {
                toastr.success('Insight acknowledged successfully!');
                $card.fadeOut(300, function() {
                    $(this).remove();
                    loadStatistics();
                });
            }
        }).fail(function() {
            toastr.error('Failed to acknowledge insight');
        });
    });
    
    /**
     * Resolve insight
     */
    $(document).on('click', '.resolve-insight', function() {
        const insightId = $(this).data('id');
        const $card = $('[data-id="' + insightId + '"]');
        
        swal({
            title: '@lang("businessintelligence::lang.resolution_note")',
            content: {
                element: "textarea",
                attributes: {
                    placeholder: "@lang('businessintelligence::lang.add_note')",
                },
            },
            buttons: {
                cancel: 'Cancel',
                confirm: 'Resolve'
            },
        }).then((note) => {
            if (note !== null) {
                $.post('/business-intelligence/insights/' + insightId + '/resolve', {
                    resolution_note: note,
                    _token: '{{ csrf_token() }}'
                }, function(response) {
                    if (response.success) {
                        toastr.success('Insight resolved successfully!');
                        $card.fadeOut(300, function() {
                            $(this).remove();
                            loadStatistics();
                        });
                    }
                }).fail(function() {
                    toastr.error('Failed to resolve insight');
                });
            }
        });
    });
    
    /**
     * Dismiss insight
     */
    $(document).on('click', '.dismiss-insight', function() {
        const insightId = $(this).data('id');
        const $card = $('[data-id="' + insightId + '"]');
        
        swal({
            title: '@lang("businessintelligence::lang.confirm_dismiss")',
            text: '@lang("businessintelligence::lang.dismiss_warning")',
            icon: 'warning',
            buttons: {
                cancel: 'Cancel',
                confirm: {
                    text: 'Dismiss',
                    value: true,
                }
            },
            dangerMode: true,
        }).then((confirmed) => {
            if (confirmed) {
                $.post('/business-intelligence/insights/' + insightId + '/dismiss', {
                    _token: '{{ csrf_token() }}'
                }, function(response) {
                    if (response.success) {
                        toastr.success('Insight dismissed');
                        $card.fadeOut(300, function() {
                            $(this).remove();
                            loadStatistics();
                        });
                    }
                }).fail(function() {
                    toastr.error('Failed to dismiss insight');
                });
            }
        });
    });
});
</script>
@endpush

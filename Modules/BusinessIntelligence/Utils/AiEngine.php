<?php

namespace Modules\BusinessIntelligence\Utils;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AiEngine
{
    protected $config;
    protected $httpClient;

    public function __construct()
    {
        $this->config = config('businessintelligence.ai');
        $this->httpClient = new Client();
    }

    /**
     * Generate AI insights based on data
     * 
     * @param array $data Business data to analyze
     * @param string $analysisType Type of analysis (sales, inventory, financial, etc.)
     * @return array AI-generated insights
     */
    public function generateInsights(array $data, string $analysisType = 'general')
    {
        if (!$this->config['enabled']) {
            return $this->generateRuleBasedInsights($data, $analysisType);
        }

        if ($this->config['provider'] === 'openai' && !empty($this->config['openai']['api_key'])) {
            return $this->generateOpenAIInsights($data, $analysisType);
        }

        return $this->generateRuleBasedInsights($data, $analysisType);
    }

    /**
     * Generate rule-based AI insights (default method)
     */
    protected function generateRuleBasedInsights(array $data, string $analysisType)
    {
        $insights = [];

        switch ($analysisType) {
            case 'sales':
                $insights = $this->analyzeSalesData($data);
                break;
            case 'inventory':
                $insights = $this->analyzeInventoryData($data);
                break;
            case 'financial':
                $insights = $this->analyzeFinancialData($data);
                break;
            case 'customer':
                $insights = $this->analyzeCustomerData($data);
                break;
            default:
                $insights = $this->analyzeGeneralData($data);
        }

        return $insights;
    }

    /**
     * Generate OpenAI-based insights
     */
    protected function generateOpenAIInsights(array $data, string $analysisType)
    {
        try {
            $prompt = $this->buildPrompt($data, $analysisType);

            $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['openai']['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->config['openai']['model'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a business intelligence analyst specializing in retail and POS systems. Analyze the provided data and generate actionable insights.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => $this->config['openai']['max_tokens'],
                    'temperature' => 0.7,
                ],
                'timeout' => 30,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['choices'][0]['message']['content'])) {
                return $this->parseOpenAIResponse($result['choices'][0]['message']['content'], $analysisType);
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
            // Fallback to rule-based insights
            return $this->generateRuleBasedInsights($data, $analysisType);
        }

        return [];
    }

    /**
     * Build prompt for OpenAI
     */
    protected function buildPrompt(array $data, string $analysisType)
    {
        $dataJson = json_encode($data, JSON_PRETTY_PRINT);
        
        return "Analyze the following {$analysisType} data and provide 3-5 key insights with actionable recommendations:\n\n{$dataJson}\n\nFor each insight, provide:\n1. Title (brief summary)\n2. Description (detailed analysis)\n3. Priority (low/medium/high/critical)\n4. Recommended actions\n5. Confidence score (0-100)\n\nFormat as JSON array.";
    }

    /**
     * Parse OpenAI response into structured insights
     */
    protected function parseOpenAIResponse(string $response, string $analysisType)
    {
        // Try to extract JSON from response
        preg_match('/\[.*\]/s', $response, $matches);
        
        if (isset($matches[0])) {
            $insights = json_decode($matches[0], true);
            if (is_array($insights)) {
                return array_map(function($insight) use ($analysisType) {
                    return [
                        'type' => $analysisType,
                        'category' => $insight['priority'] ?? 'medium',
                        'title' => $insight['title'] ?? 'AI Insight',
                        'description' => $insight['description'] ?? '',
                        'confidence_score' => $insight['confidence_score'] ?? 85,
                        'priority' => $insight['priority'] ?? 'medium',
                        'action_items' => $insight['recommended_actions'] ?? [],
                        'icon' => $this->getIconForType($analysisType),
                        'color' => $this->getColorForPriority($insight['priority'] ?? 'medium'),
                    ];
                }, $insights);
            }
        }

        // Fallback: create single insight from text response
        return [[
            'type' => $analysisType,
            'category' => 'recommendation',
            'title' => 'AI Analysis',
            'description' => $response,
            'confidence_score' => 75,
            'priority' => 'medium',
            'action_items' => [],
            'icon' => 'fas fa-robot',
            'color' => 'blue',
        ]];
    }

    /**
     * Analyze sales data using rule-based logic
     */
    protected function analyzeSalesData(array $data)
    {
        $insights = [];

        // Trend analysis
        if (isset($data['sales_trend'])) {
            $recentSales = array_slice($data['sales_trend'], -7);
            $previousSales = array_slice($data['sales_trend'], -14, 7);
            
            // Handle both array and object formats for sales data
            $recentTotal = 0;
            foreach ($recentSales as $sale) {
                $amount = is_object($sale) ? ($sale->total_sales ?? 0) : ($sale['total_sales'] ?? 0);
                $recentTotal += $amount;
            }
            $recentAvg = count($recentSales) > 0 ? $recentTotal / count($recentSales) : 0;
            
            $previousTotal = 0;
            foreach ($previousSales as $sale) {
                $amount = is_object($sale) ? ($sale->total_sales ?? 0) : ($sale['total_sales'] ?? 0);
                $previousTotal += $amount;
            }
            $previousAvg = count($previousSales) > 0 ? $previousTotal / count($previousSales) : 0;
            
            if ($previousAvg > 0) {
                $changePercent = (($recentAvg - $previousAvg) / $previousAvg) * 100;
                
                if ($changePercent > 20) {
                    $insights[] = [
                        'type' => 'sales',
                        'category' => 'opportunity',
                        'title' => 'Strong Sales Growth Detected',
                        'description' => sprintf('Your sales have increased by %.1f%% in the last week. Consider increasing inventory for best-selling items to capitalize on this trend.', $changePercent),
                        'confidence_score' => 90,
                        'priority' => 'high',
                        'action_items' => [
                            'Review top-selling products',
                            'Increase inventory levels',
                            'Plan marketing campaigns'
                        ],
                        'icon' => 'fas fa-arrow-trend-up',
                        'color' => 'green',
                    ];
                } elseif ($changePercent < -15) {
                    $insights[] = [
                        'type' => 'sales',
                        'category' => 'alert',
                        'title' => 'Sales Decline Alert',
                        'description' => sprintf('Sales have decreased by %.1f%% compared to last week. Immediate action recommended to identify and address causes.', abs($changePercent)),
                        'confidence_score' => 95,
                        'priority' => 'critical',
                        'action_items' => [
                            'Analyze competitor pricing',
                            'Review customer feedback',
                            'Consider promotional offers',
                            'Check inventory availability'
                        ],
                        'icon' => 'fas fa-arrow-trend-down',
                        'color' => 'red',
                    ];
                }
            }
        }

        // Peak hours analysis
        if (isset($data['hourly_sales'])) {
            $peakHour = collect($data['hourly_sales'])->sortByDesc('total_sales')->first();
            if ($peakHour) {
                $insights[] = [
                    'type' => 'sales',
                    'category' => 'recommendation',
                    'title' => 'Peak Sales Hour Identified',
                    'description' => sprintf('Your busiest hour is %s with average sales of %s. Ensure adequate staffing during this period.', 
                        $peakHour['hour'], 
                        number_format($peakHour['total_sales'], 2)
                    ),
                    'confidence_score' => 85,
                    'priority' => 'medium',
                    'action_items' => [
                        'Schedule more staff during peak hours',
                        'Stock high-demand items before peak',
                        'Optimize checkout processes'
                    ],
                    'icon' => 'fas fa-clock',
                    'color' => 'blue',
                ];
            }
        }

        return $insights;
    }

    /**
     * Analyze inventory data
     */
    protected function analyzeInventoryData(array $data)
    {
        $insights = [];

        // Low stock alerts
        if (isset($data['low_stock_items'])) {
            $lowStockCount = count($data['low_stock_items']);
            
            if ($lowStockCount > 0) {
                $insights[] = [
                    'type' => 'inventory',
                    'category' => 'alert',
                    'title' => sprintf('%d Products Below Minimum Stock', $lowStockCount),
                    'description' => 'Several products are running low on inventory. Immediate reordering recommended to avoid stock-outs and lost sales.',
                    'confidence_score' => 100,
                    'priority' => $lowStockCount > 10 ? 'critical' : 'high',
                    'action_items' => array_slice(array_map(function($item) {
                        // Handle both array and object formats
                        $name = is_object($item) ? ($item->name ?? 'Unknown Product') : ($item['name'] ?? 'Unknown Product');
                        $qty = is_object($item) ? ($item->qty_available ?? 0) : ($item['qty_available'] ?? 0);
                        return sprintf('Reorder %s (Current: %d units)', $name, $qty);
                    }, $data['low_stock_items']), 0, 5),
                    'icon' => 'fas fa-box-open',
                    'color' => 'orange',
                ];
            }
        }

        // Overstock analysis
        if (isset($data['overstock_items'])) {
            $overstockCount = count($data['overstock_items']);
            
            if ($overstockCount > 0) {
                // Handle both array and object formats
                $totalValue = 0;
                foreach ($data['overstock_items'] as $item) {
                    $value = is_object($item) ? ($item->stock_value ?? 0) : ($item['stock_value'] ?? 0);
                    $totalValue += $value;
                }
                
                $insights[] = [
                    'type' => 'inventory',
                    'category' => 'recommendation',
                    'title' => sprintf('Overstock Detected: %s Tied Up', number_format($totalValue, 2)),
                    'description' => sprintf('%d products have excess inventory. Consider promotional strategies to improve cash flow.', $overstockCount),
                    'confidence_score' => 80,
                    'priority' => 'medium',
                    'action_items' => [
                        'Run clearance sales on slow-moving items',
                        'Bundle overstocked products with popular items',
                        'Reduce future orders for these products'
                    ],
                    'icon' => 'fas fa-warehouse',
                    'color' => 'yellow',
                ];
            }
        }

        // Stock value concentration
        if (isset($data['inventory_value']) && isset($data['total_products'])) {
            $avgValue = $data['inventory_value'] / max($data['total_products'], 1);
            
            $insights[] = [
                'type' => 'inventory',
                'category' => 'insight',
                'title' => 'Inventory Health Overview',
                'description' => sprintf('Your total inventory value is %s across %d products (avg: %s per product). Monitor turnover rate to optimize stock levels.',
                    number_format($data['inventory_value'], 2),
                    $data['total_products'],
                    number_format($avgValue, 2)
                ),
                'confidence_score' => 75,
                'priority' => 'low',
                'action_items' => [
                    'Review inventory turnover metrics',
                    'Identify slow-moving inventory',
                    'Adjust reorder points based on sales velocity'
                ],
                'icon' => 'fas fa-chart-pie',
                'color' => 'purple',
            ];
        }

        return $insights;
    }

    /**
     * Analyze financial data
     */
    protected function analyzeFinancialData(array $data)
    {
        $insights = [];

        // Profit margin analysis
        if (isset($data['profit_margin'])) {
            $margin = $data['profit_margin'];
            $threshold = config('businessintelligence.alerts.profit_margin_threshold', 15);
            
            if ($margin < $threshold) {
                $insights[] = [
                    'type' => 'financial',
                    'category' => 'risk',
                    'title' => 'Low Profit Margin Warning',
                    'description' => sprintf('Your profit margin is %.2f%%, below the recommended threshold of %d%%. Review pricing strategy and cost management.', $margin, $threshold),
                    'confidence_score' => 95,
                    'priority' => 'high',
                    'action_items' => [
                        'Review product pricing',
                        'Negotiate better supplier terms',
                        'Reduce operational expenses',
                        'Focus on high-margin products'
                    ],
                    'icon' => 'fas fa-percentage',
                    'color' => 'red',
                ];
            } elseif ($margin > 30) {
                $insights[] = [
                    'type' => 'financial',
                    'category' => 'opportunity',
                    'title' => 'Excellent Profit Margins',
                    'description' => sprintf('Your profit margin of %.2f%% is excellent. Consider reinvesting in business growth or competitive pricing to capture market share.', $margin),
                    'confidence_score' => 90,
                    'priority' => 'medium',
                    'action_items' => [
                        'Invest in inventory expansion',
                        'Consider opening new locations',
                        'Strategically reduce prices to increase volume'
                    ],
                    'icon' => 'fas fa-chart-line',
                    'color' => 'green',
                ];
            }
        }

        // Cash flow analysis
        if (isset($data['cash_balance']) && isset($data['monthly_expenses'])) {
            $runwayMonths = $data['monthly_expenses'] > 0 ? $data['cash_balance'] / $data['monthly_expenses'] : 99;
            
            if ($runwayMonths < 2) {
                $insights[] = [
                    'type' => 'financial',
                    'category' => 'alert',
                    'title' => 'Critical Cash Flow Warning',
                    'description' => sprintf('Based on current cash balance and expenses, you have approximately %.1f months of runway. Immediate action required.', $runwayMonths),
                    'confidence_score' => 98,
                    'priority' => 'critical',
                    'action_items' => [
                        'Accelerate collection of receivables',
                        'Negotiate extended payment terms with suppliers',
                        'Reduce discretionary spending',
                        'Consider short-term financing options'
                    ],
                    'icon' => 'fas fa-money-bill-wave',
                    'color' => 'red',
                ];
            }
        }

        // Expense analysis
        if (isset($data['expense_growth'])) {
            if ($data['expense_growth'] > config('businessintelligence.alerts.expense_spike_threshold', 50)) {
                $insights[] = [
                    'type' => 'financial',
                    'category' => 'alert',
                    'title' => 'Unusual Expense Spike Detected',
                    'description' => sprintf('Expenses have increased by %.1f%% compared to previous period. Review expense categories for anomalies.', $data['expense_growth']),
                    'confidence_score' => 85,
                    'priority' => 'high',
                    'action_items' => [
                        'Review all expense transactions',
                        'Identify unnecessary expenditures',
                        'Implement expense approval workflow'
                    ],
                    'icon' => 'fas fa-exclamation-triangle',
                    'color' => 'orange',
                ];
            }
        }

        return $insights;
    }

    /**
     * Analyze customer data
     */
    protected function analyzeCustomerData(array $data)
    {
        $insights = [];

        // Customer dues analysis
        if (isset($data['overdue_customers'])) {
            $overdueCount = count($data['overdue_customers']);
            
            if ($overdueCount > 0) {
                // Handle both array and object formats
                $totalOverdue = 0;
                foreach ($data['overdue_customers'] as $customer) {
                    $due = is_object($customer) ? ($customer->total_due ?? 0) : ($customer['total_due'] ?? 0);
                    $totalOverdue += $due;
                }
                
                $insights[] = [
                    'type' => 'customer',
                    'category' => 'alert',
                    'title' => sprintf('%d Customers with Overdue Payments', $overdueCount),
                    'description' => sprintf('Total overdue amount: %s. Follow up with customers to improve cash collection.', number_format($totalOverdue, 2)),
                    'confidence_score' => 100,
                    'priority' => 'high',
                    'action_items' => array_map(function($customer) {
                        // Handle both array and object formats
                        $name = is_object($customer) ? ($customer->name ?? 'Unknown') : ($customer['name'] ?? 'Unknown');
                        $due = is_object($customer) ? ($customer->total_due ?? 0) : ($customer['total_due'] ?? 0);
                        return sprintf('Contact %s - Overdue: %s', $name, number_format($due, 2));
                    }, array_slice($data['overdue_customers'], 0, 5)),
                    'icon' => 'fas fa-users',
                    'color' => 'red',
                ];
            }
        }

        // Customer loyalty
        if (isset($data['repeat_customers']) && isset($data['total_customers'])) {
            $repeatRate = ($data['repeat_customers'] / max($data['total_customers'], 1)) * 100;
            
            if ($repeatRate < 30) {
                $insights[] = [
                    'type' => 'customer',
                    'category' => 'recommendation',
                    'title' => 'Low Customer Retention Rate',
                    'description' => sprintf('Only %.1f%% of customers are repeat buyers. Implement loyalty programs to improve retention.', $repeatRate),
                    'confidence_score' => 80,
                    'priority' => 'medium',
                    'action_items' => [
                        'Launch customer loyalty program',
                        'Send follow-up emails after purchases',
                        'Offer exclusive discounts to returning customers',
                        'Collect customer feedback'
                    ],
                    'icon' => 'fas fa-heart',
                    'color' => 'pink',
                ];
            }
        }

        return $insights;
    }

    /**
     * General data analysis
     */
    protected function analyzeGeneralData(array $data)
    {
        $insights = array_merge(
            $this->analyzeSalesData($data),
            $this->analyzeInventoryData($data),
            $this->analyzeFinancialData($data),
            $this->analyzeCustomerData($data)
        );

        return $insights;
    }

    /**
     * Get icon for insight type
     */
    protected function getIconForType(string $type)
    {
        return match($type) {
            'sales' => 'fas fa-chart-line',
            'inventory' => 'fas fa-boxes',
            'financial' => 'fas fa-dollar-sign',
            'customer' => 'fas fa-users',
            'supplier' => 'fas fa-truck',
            default => 'fas fa-lightbulb',
        };
    }

    /**
     * Get color for priority
     */
    protected function getColorForPriority(string $priority)
    {
        return match($priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'blue',
            'low' => 'gray',
            default => 'blue',
        };
    }
}


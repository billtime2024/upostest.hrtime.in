/**
 * Enhanced Labels Functionality
 * This file adds enhanced features to the existing labels system
 * without modifying the original labels.js file
 */

$(document).ready(function () {
    // Initialize enhanced table event handlers
    initializeEnhancedTableEvents();

    // Initialize enhanced print functionality
    initializeEnhancedPrintLabel();
});

/**
 * Initialize event handlers for enhanced table inputs
 * Uses existing fetchPreview() function from labels.js
 */
function initializeEnhancedTableEvents() {
    // Handle quantity input changes
    $(document).on(
        'change input',
        'table#product_table input[name*="[quantity]"]',
        function () {
            console.log('Enhanced: Quantity changed to:', $(this).val());
            // Use existing fetchPreview function
            if (typeof fetchPreview === 'function') {
                fetchPreview();
            }
        }
    );

    // Handle packing date input changes
    $(document).on(
        'change',
        'table#product_table input[name*="[packing_date]"]',
        function () {
            console.log('Enhanced: Packing date changed to:', $(this).val());
            if (typeof fetchPreview === 'function') {
                fetchPreview();
            }
        }
    );

    // Handle datepicker change event specifically for packing date
    $(document).on(
        'changeDate',
        'table#product_table .label-date-picker[name*="[packing_date]"]',
        function () {
            console.log(
                'Enhanced: Packing date changed via datepicker to:',
                $(this).val()
            );
            if (typeof fetchPreview === 'function') {
                fetchPreview();
            }
        }
    );

    // Handle price group selection changes
    $(document).on(
        'change',
        'table#product_table select[name*="[price_group_id]"]',
        function () {
            console.log('Enhanced: Price group changed to:', $(this).val());
            if (typeof fetchPreview === 'function') {
                fetchPreview();
            }
        }
    );

    // Handle lot number changes (if lot numbers are enabled)
    $(document).on(
        'change',
        'table#product_table input[name*="[lot_number]"]',
        function () {
            console.log('Enhanced: Lot number changed to:', $(this).val());
            if (typeof fetchPreview === 'function') {
                fetchPreview();
            }
        }
    );

    // Handle expiry date changes (if expiry dates are enabled)
    $(document).on(
        'change',
        'table#product_table input[name*="[exp_date]"]',
        function () {
            console.log('Enhanced: Expiry date changed to:', $(this).val());
            if (typeof fetchPreview === 'function') {
                fetchPreview();
            }
        }
    );

    // Handle datepicker change event for expiry date
    $(document).on(
        'changeDate',
        'table#product_table .label-date-picker[name*="[exp_date]"]',
        function () {
            console.log(
                'Enhanced: Expiry date changed via datepicker to:',
                $(this).val()
            );
            if (typeof fetchPreview === 'function') {
                fetchPreview();
            }
        }
    );

    console.log('Enhanced table event handlers initialized');
}

/**
 * BETTER APPROACH: Show label info without scaling
 * Let content display at natural size with horizontal scroll
 */
function adjustPreviewScale() {
    const $previewBox = $('#preview_box');
    const $table = $previewBox.find('table');
    const $infoContainer = $('#label_info_container'); // New separate container

    if ($table.length === 0) {
        $infoContainer.empty(); // Clear info when no table
        return;
    }

    // Remove any previous hints from preview box only
    $previewBox.find('.scroll-hint, .preview-info').remove();

    // DON'T scale - let content display naturally
    $table.css('transform', 'none');
    $table.css('margin-bottom', '0');

    // Wait for content to fully render
    setTimeout(() => {
        // Count actual label cells
        const $labelCells = $table.find('td').filter(function () {
            return (
                $(this).find('div[style*="width:"], div[style*="height:"]')
                    .length > 0 ||
                $(this).text().trim().length > 0 ||
                $(this).find('img').length > 0
            );
        });

        const totalLabels = $labelCells.length;
        const $firstRow = $table.find('tr').first();
        const columnsInFirstRow = $firstRow.find('td').filter(function () {
            return (
                $(this).find('div[style*="width:"], div[style*="height:"]')
                    .length > 0 ||
                $(this).text().trim().length > 0 ||
                $(this).find('img').length > 0
            );
        }).length;

        const actualColumns = Math.max(1, columnsInFirstRow);
        const actualRows = Math.max(1, Math.ceil(totalLabels / actualColumns));

        // Check if content is wider than container
        const containerWidth = $previewBox.width();
        const tableWidth = $table[0].scrollWidth || $table.outerWidth();
        const needsScroll = tableWidth > containerWidth;

        console.log(
            `Natural size: Container ${containerWidth}px, Table ${tableWidth}px, Needs scroll: ${needsScroll}`
        );

        // Show info in SEPARATE container (not in preview box)
        let infoMessage = `<i class="fas fa-info-circle"></i> Showing ${totalLabels} labels (${actualColumns} x ${actualRows})`;

        if (needsScroll) {
            infoMessage += ` - Scroll horizontally to see all labels`;
        }

        // Put info in separate container, NOT in preview_box
        $infoContainer.html(`
            <div style="
                background: rgba(59, 130, 246, 0.1);
                color: #3b82f6;
                padding: 6px 12px;
                border-radius: 4px;
                font-size: 12px;
                text-align: center;
                border: 1px solid rgba(59, 130, 246, 0.2);
            ">
                ${infoMessage}
            </div>
        `);

        console.log(
            `Showing ${totalLabels} labels at natural size - no scaling applied`
        );
    }, 300);
}

/**
 * Enhanced Print Label Functionality - Optimized for Label Printers
 * Uses button#enhanced_print_label instead of #print_label
 * Optimized for small barcode/label printers
 */
function initializeEnhancedPrintLabel() {
    $('button#enhanced_print_label').click(function () {
        var printContents = $('#preview_box').html();

        // Check if preview is still loading
        if (
            printContents.includes('Generating preview') ||
            printContents.includes('loading-spinner')
        ) {
            if (typeof toastr !== 'undefined') {
                toastr.warning(
                    'Please wait for the preview to finish loading before printing.'
                );
            } else {
                alert(
                    'Please wait for the preview to finish loading before printing.'
                );
            }
            return;
        }

        if (printContents.trim().length > 0) {
            // More robust cleaning of print content
            var $tempDiv = $('<div>').html(printContents);

            // Remove all info messages by class and content
            $tempDiv.find('.no-print').remove();
            $tempDiv.find('.scroll-hint').remove();
            $tempDiv.find('.preview-info').remove();
            $tempDiv.find('div:contains("Showing")').remove();
            $tempDiv.find('div:contains("Scaled to")').remove();
            $tempDiv.find('div:contains("labels")').remove();
            $tempDiv.find('div:contains("Scroll horizontally")').remove();

            // Additional cleanup - remove any divs with green/blue backgrounds (our info messages)
            $tempDiv.find('div[style*="rgba(34, 197, 94"]').remove();
            $tempDiv.find('div[style*="rgba(59, 130, 246"]').remove();

            var cleanPrintContents = $tempDiv.html();

            // Additional check for actual table content
            if (
                !cleanPrintContents.includes('<table') &&
                !cleanPrintContents.includes('barcode')
            ) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(
                        'No labels found to print. Please wait for preview to load.'
                    );
                } else {
                    alert(
                        'No labels found to print. Please wait for preview to load.'
                    );
                }
                return;
            }

            // Get dynamic label dimensions
            var dimensions = getLabelDimensions();
            var labelWidth = dimensions.width;
            var labelHeight = dimensions.height;

            console.log(
                `Printing with dimensions: ${labelWidth}" x ${labelHeight}"`
            );

            // Open a new window optimized for label printing
            var printWindow = window.open('', '_blank', 'width=400,height=300');
            printWindow.document.open();

            // Write label printer optimized HTML
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Label Printer - Enhanced Print</title>
                        <style>
                            * {
                                margin: 0;
                                padding: 0;
                                box-sizing: border-box;
                            }
                            
                            body {
                                font-family: 'Arial', sans-serif;
                                background: white;
                                font-size: 8px;
                            }
                            
                            @media print {
                                /* Single label page setup */
                                @page {
                                    size: 3in 2in; /* Larger size to ensure single page */
                                    margin: 0.2in;
                                    -webkit-print-color-adjust: exact;
                                    color-adjust: exact;
                                }

                                body {
                                    margin: 0;
                                    padding: 0;
                                    background: white !important;
                                    font-family: Arial, sans-serif;
                                    font-size: 10px;
                                    line-height: 1.2;
                                }

                                /* Force single page layout */
                                * {
                                    -webkit-print-color-adjust: exact !important;
                                    color-adjust: exact !important;
                                    print-color-adjust: exact !important;
                                    page-break-inside: avoid !important;
                                    break-inside: avoid !important;
                                }

                                /* Compact table layout */
                                table {
                                    width: 100% !important;
                                    border-collapse: collapse !important;
                                    table-layout: fixed !important;
                                    page-break-inside: avoid !important;
                                    margin: 0 !important;
                                    padding: 0 !important;
                                }

                                tr, td {
                                    page-break-inside: avoid !important;
                                    break-inside: avoid !important;
                                    margin: 0 !important;
                                    padding: 2px !important;
                                    border: none !important;
                                    vertical-align: top !important;
                                }

                                /* Compact content container */
                                div[style*="width:"][style*="height:"] {
                                    display: block !important;
                                    width: auto !important;
                                    height: auto !important;
                                    max-width: 100% !important;
                                    padding: 5px !important;
                                    margin: 0 !important;
                                    text-align: center !important;
                                    page-break-inside: avoid !important;
                                }

                                /* Text optimization */
                                span, b, div {
                                    font-size: 8px !important;
                                    line-height: 1.1 !important;
                                    margin: 1px 0 !important;
                                    padding: 0 !important;
                                    display: block !important;
                                    text-align: center !important;
                                }

                                /* Business name larger */
                                b {
                                    font-size: 10px !important;
                                    font-weight: bold !important;
                                }

                                /* Barcode container */
                                div[style*="flex"] {
                                    display: block !important;
                                    text-align: center !important;
                                    margin: 2px 0 !important;
                                    page-break-inside: avoid !important;
                                }

                                /* High-quality image rendering */
                                img {
                                    image-rendering: crisp-edges !important;
                                    image-rendering: -moz-crisp-edges !important;
                                    image-rendering: -webkit-optimize-contrast !important;
                                    -webkit-print-color-adjust: exact !important;
                                    display: block !important;
                                    margin: 2px auto !important;
                                    max-width: 90% !important;
                                }

                                /* Barcode specific */
                                img[src*="barcode"], img[src*="DNS1D"] {
                                    height: 25px !important;
                                    width: auto !important;
                                    max-width: 80% !important;
                                }

                                /* QR Code specific */
                                img[src*="qrcode"], img[src*="DNS2D"] {
                                    width: 30px !important;
                                    height: 30px !important;
                                }

                                /* Remove dotted borders */
                                td[style*="dotted"] {
                                    border: none !important;
                                }
                            }
                            
                            @media screen {
                                body {
                                    padding: 20px;
                                    background: #f5f5f5;
                                }
                                
                                /* Show preview scaled up for screen viewing */
                                table {
                                    transform: scale(1.5);
                                    transform-origin: top left;
                                    margin-bottom: 50px;
                                    background: white;
                                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                }
                            }
                        </style>
                    </head>
                    <body>
                        ${cleanPrintContents}
                    </body>
                </html>
            `);

            printWindow.document.close();

            // Label printer specific print handling
            printWindow.onload = function () {
                // Small delay for label printers to process
                setTimeout(function () {
                    printWindow.print();
                    // Keep window open a bit longer for label printers
                    setTimeout(function () {
                        printWindow.close();
                    }, 1000);
                }, 500);
            };

            console.log('Label printer optimized print dialog opened');
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.error('No label preview available to print.');
            } else {
                alert('No label preview available to print.');
            }
        }
    });

    console.log('Label printer enhanced functionality initialized');
}

/**
 * Get Label Dimensions from Barcode Settings
 * Attempts to read the selected barcode setting dimensions
 */
function getLabelDimensions() {
    // Try to get dimensions from barcode settings dropdown
    var selectedOption = $('select[name="barcode_setting"] option:selected');
    var defaultWidth = 1.1811; // 30mm fallback
    var defaultHeight = 0.5906; // 15mm fallback

    // Use custom size if set via setLabelPrinterSize()
    if (window.labelPrinterWidth && window.labelPrinterHeight) {
        return {
            width: window.labelPrinterWidth,
            height: window.labelPrinterHeight,
        };
    }

    // TODO: Add logic to extract dimensions from barcode settings
    // This would require checking your barcode_settings data structure

    return {
        width: defaultWidth,
        height: defaultHeight,
    };
}

/**
 * Optional: Dynamic Label Size Configuration
 * Call this function to set custom label dimensions
 */
function setLabelPrinterSize(widthInches, heightInches) {
    window.labelPrinterWidth = widthInches || 1.1811; // Default 30mm
    window.labelPrinterHeight = heightInches || 0.5906; // Default 15mm
    console.log(
        `Label printer size set to: ${window.labelPrinterWidth}" x ${window.labelPrinterHeight}"`
    );
}
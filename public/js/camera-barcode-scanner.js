/**
* Universal Camera Barcode Scanner for Ultimate POS
* Version: 2.0
*
* Works with: POS, Sales, Purchase, Stock Adjustment, Transfers, etc.
*/


(function ($) {
   'use strict';


   window.CameraBarcodeScanner = {
       config: {
           cameraConfig: { facingMode: 'environment' },
           scanConfig: {
               fps: 10,
               qrbox: { width: 300, height: 150 },
               supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
           },
       },


       html5QrCode: null,
       isScanning: false,
       currentSearchInputId: 'search_product',
       currentPageType: null,


       init: function () {
           console.log('🎥 Initializing Universal Camera Barcode Scanner...');
           try {
               this.html5QrCode = new Html5Qrcode('reader');
               this.detectPageType();
               this.bindEvents();
           } catch (error) {
               console.error(
                   '❌ Camera scanner initialization failed:',
                   error
               );
           }
       },


       detectPageType: function () {
           const currentUrl = window.location.pathname;


           if (currentUrl.includes('/pos/')) {
               this.currentPageType = 'pos';
           } else if (currentUrl.includes('/sells/')) {
               this.currentPageType = 'sell';
           } else if (
               currentUrl.includes('/purchases/') ||
               currentUrl.includes('/purchase-order/')
           ) {
               this.currentPageType = 'purchase';
           } else if (currentUrl.includes('/stock-adjustments/')) {
               this.currentPageType = 'stock_adjustment';
           } else if (currentUrl.includes('/stock-transfers/')) {
               this.currentPageType = 'transfer';
           } else if (currentUrl.includes('/purchase-return/')) {
               this.currentPageType = 'purchase_return';
           } else {
               this.currentPageType = 'general';
           }
       },


       bindEvents: function () {
           const self = this;


           $(document).on(
               'click',
               '.camera-barcode-scanner-btn, #barcode_scanner_btn',
               function (e) {
                   e.preventDefault();
                   e.stopPropagation();


                   const searchInputId =
                       $(this).data('search-input') || 'search_product';
                   self.currentSearchInputId = searchInputId;
                   self.openCamera();
               }
           );


           $(document).on('keydown', function (e) {
               if (e.ctrlKey && e.key === 'b') {
                   e.preventDefault();
                   $(
                       '.camera-barcode-scanner-btn:visible, #barcode_scanner_btn:visible'
                   )
                       .first()
                       .click();
               }
           });
       },


       openCamera: function () {
           if (!this.html5QrCode) return;
           if (
               location.protocol !== 'https:' &&
               location.hostname !== 'localhost'
           ) {
               alert('❌ Camera requires HTTPS connection');
               return;
           }


           $('#camera_modal').show();
           this.startCamera();
       },


       startCamera: function () {
           if (this.isScanning) return;


           const self = this;
           this.html5QrCode
               .start(
                   this.config.cameraConfig,
                   this.config.scanConfig,
                   function (decodedText) {
                       self.closeCamera();
                       self.processBarcodeForCurrentPage(decodedText);
                       if (typeof toastr !== 'undefined') {
                           toastr.success('Barcode scanned: ' + decodedText);
                       }
                   },
                   function () {
                       $('#status').text(
                           '📷 Scanning... Position barcode in view'
                       );
                   }
               )
               .then(() => {
                   self.isScanning = true;
                   $('#status').text('📷 Camera active');
               })
               .catch((err) => {
                   $('#status').text('❌ Camera error: ' + err);
                   setTimeout(() => self.closeCamera(), 2000);
               });
       },


       processBarcodeForCurrentPage: function (barcode) {
           const searchInput = $('#' + this.currentSearchInputId);
           if (searchInput.length) {
               searchInput.val(barcode).trigger('input').trigger('change');
           }
       },


       closeCamera: function () {
           if (this.isScanning && this.html5QrCode) {
               this.html5QrCode.stop().then(() => {
                   this.isScanning = false;
               });
           }
           $('#camera_modal').hide();
       },


       closeModalOnly: function () {
           this.closeCamera();
           return false;
       },
   };


   $(document).ready(function () {
       if (
           $('.camera-barcode-scanner-btn').length ||
           $('#barcode_scanner_btn').length
       ) {
           CameraBarcodeScanner.init();
       }
   });
})(jQuery);

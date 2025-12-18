@props([
   'searchInputId' => 'search_product',
   'buttonClass' => '',
   'showInGroup' => true,
   'buttonStyle' => 'default',
   'fullWidth' => false,
   'buttonText' => 'Scan Barcode'
])

@if($showInGroup)
   {{-- Camera Barcode Scanner Button for Input Groups --}}
   <button type="button"
           class="btn btn-default bg-white btn-flat camera-barcode-scanner-btn {{ $buttonClass }}"
           data-search-input="{{ $searchInputId }}"
           title="Scan Barcode">
       <i class="fa fa-camera text-primary fa-lg"></i>
   </button>
@else

 {{-- Standalone Camera Button --}}
   <button type="button"
           class="btn btn-{{ $buttonStyle }} camera-barcode-scanner-btn {{ $buttonClass }} @if($fullWidth) btn-block @endif"
           data-search-input="{{ $searchInputId }}"
           title="Scan Barcode">
       <i class="fa fa-camera @if($buttonStyle === 'link') text-primary @endif"></i> {{ $buttonText }}
   </button>
@endif

{{-- Camera Scanner Modal (only include once per page) --}}
@once
<div id="camera_modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; text-align: center;">
   <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 10px; max-width: 90%; max-height: 90%;">
       <div style="margin-bottom: 15px;">
           <h4 style="margin: 0; color: #333;">📷 Barcode Scanner</h4>
          	 <button type="button"
                   onclick="event.preventDefault(); event.stopPropagation(); CameraBarcodeScanner.closeModalOnly(); return false;"
                   style="position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
       </div>


       {{-- Html5Qrcode Scanner --}}
       <div id="reader" style="width: 100%; max-width: 500px; border: 2px solid #007bff; border-radius: 8px;"></div>


       <div id="status" style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; color: #333;">
           Starting camera...
       </div>
   </div>
</div>


<style>
   #barcode_scanner_btn:hover,
   .camera-barcode-scanner-btn:hover {
       background-color: #e3f2fd !important;
       border-color: #2196f3 !important;
   }


   #reader video {
       border-radius: 5px;
   }


   #camera_modal {
       backdrop-filter: blur(3px);
   }


   @media (max-width: 768px) {
       #camera_modal > div {
           width: 95% !important;
           max-width: none !important;
       }
   }
</style>
@endonce

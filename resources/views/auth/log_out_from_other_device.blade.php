@extends('layouts.auth2')
@section('title', __('lang_v1.login'))
@inject('request', 'Illuminate\Http\Request')
@section('content')
<style>
    .container {
        width: 100%;
        height: 100%;
    }
    
    .our-modal-backdrop {
        width: 99.9vw;
        height: 99.9vh;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 10;
        position: absolute;
        top: 0;
        left: 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .our-modal {
        width: 50vw;
        height: 50vh;
        position: relative;
        top: 20vh;
        left: 20vw;
        z-index: 20;
        background-color: #fff;
        text-align: center;
        padding: 1rem;
        box-shadow: 5px 6px 14px 5px #b7b7b7;
        border-radius: 0.75rem;
        color: #000 !important;
    }
     .our-modal > div >  * {
            font-weight: 500;
            font-size: 2rem;
           color: #000 !important;
     }
     .our-modal > .content {
         display: flex;
         justify-content: center;
         align-items: center;
         flex-direction: column;
     }
     .our-modal-footer {
        display: flex;
        justify-content: space-around;
        align-items: center;
        
     }
      .our-modal-footer > .buttons {
          display: flex;
          justify-content: space-around;
          align-items: center;
      }
</style>
<div class="our-modal-backdrop">
<div class="popover tour orphan tour-tour tour-tour-0 fade top in" id="step-0" style="top: 40vh; left: 40vw; display: flex; justify-content: center; align-items: center; flex-direction: column;">
    <div class="arrow" style="left: 50%;"></div>
    <h3 class="popover-title text-bold" style="color: #ff0000 !important;">Account logged in another device</h3>
    <div class="popover-content text-bold" style="color: #28b97b !important; width: 70%; text-align:center;">You want Force logout from another device ?</div>
    <div class="popover-navigation"><button id="yes" style="margin-right: 1rem;" class="btn btn-success btn-sm" data-role="next">Yes</button>
    <button id="no" style="margin-left: 2rem;" class="btn btn-danger btn-sm" data-role="end">No</button>
    </div>
    </div>
    </div>
<!--<div class="container">-->
<!--    <div class="our-modal-backdrop"></div>-->

<!--    <div class="our-modal">-->
<!--        <div class="content">-->
<!--            <h1 style="color: #000 !important;">Account access from another device.</h1>-->
<!--            <h2>You want Force logout from another device ?</h2>-->
<!--        </div>-->
<!--        <div class="our-modal-footer">-->
<!--            <div></div>-->
<!--            <div class="buttons">-->
<!--                <button id="yes" class="btn btn-primary">Yes</button>-->
<!--                <button id="no" class="btn btn-secondary">NO</button>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</div>-->
<script>
        // const modalBackDrop = document.querySelector(".our-modal-backdrop");
        // const modal = document.querySelector(".our-modal");
        const yesBtn = document.getElementById("yes");
        const noBtn = document.getElementById("no");
        
        // modalBackDrop.addEventListener("click", () => {
        //     modal.style.display = "none";
        //     modalBackDrop.style.display = "none";
        // })
        
        yesBtn.addEventListener("click", () => {
            window.location = {!! "'" . route('logout_from_other_device', $found_user[0]->id) . "'" !!} ;
        })
        noBtn.addEventListener("click", () => {
            window.location = {!!  "'". route('login') . "'" !!} ;
        })
            //   const result =  confirm('Account access from another device. You want Force logout from another device ?');
            //   if(result){
            //       window.location = {!! "'" . route('logout_from_other_device', $found_user[0]->id) . "'" !!} ;
            //   }else {
            //         window.location = {!!  "'". route('login') . "'" !!} ;
            //   }
            // setTimeout(function() {
            //      document.getElementById("modal_btn").click();
            // }, 500);
           
            </script>

@endsection
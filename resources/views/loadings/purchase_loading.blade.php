<div class="loader-con">
  <div style="--i: 0;" class="pfile"></div>
  <div style="--i: 1;" class="pfile"></div>
  <div class="pfile" style="--i: 2;"></div>
  <div class="pfile" style="--i: 3;"></div>
  <div class="pfile" style="--i: 4;"></div>
  <div class="pfile" style="--i: 5;"></div>
</div>
<style>
.loader-con {
    display: none;
  position: absolute;
  top:0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  z-index: 9999;
  /*background-color: #000 ;*/
  /*opacity: 0.8;*/
}

.pfile {
  position: absolute;
  top: 50vh;
  width: 75px;
  height: 100px;
  background: linear-gradient(90deg, #4a00ff, #220074);
  border-radius: 4px;
  transform-origin: center;
  animation: flyRight 3s ease-in-out infinite;
  opacity:0;
}

.pfile::before {
  content: "";
  position: absolute;
  top: 6px;
  left: 6px;
  width: 28px;
  height: 4px;
  background-color: #ffffff;
  border-radius: 2px;
}

.pfile::after {
  content: "";
  position: absolute;
  top: 13px;
  left: 6px;
  width: 18px;
  height: 4px;
  background-color: #ffffff;
  border-radius: 2px;
}

@keyframes flyRight {
  0% {
    left: -10%;
    transform: scale(0);
    opacity: 0;
  }
  50% {
    left: 45%;
    transform: scale(1.2);
    opacity: 1;
  }
  100% {
    left: 100%;
    transform: scale(0);
    opacity: 0;
  }
}

.pfile {
  animation-delay: calc(var(--i) * 0.6s);
}

</style>
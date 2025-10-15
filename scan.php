<?php
// scan.php — مرحلتين منفصلتين (Recto ثم Verso) بكاميرا كبيرة وواضحة
ini_set('display_errors',1); error_reporting(E_ALL);

$res   = $_GET['res']   ?? '';
$count = isset($_GET['count']) ? (int)$_GET['count'] : 1;
$i     = isset($_GET['i'])     ? (int)$_GET['i']     : 1;
$ids   = isset($_GET['ids'])   ? trim($_GET['ids'])  : '';
$step  = $_GET['s'] ?? 'recto'; // recto | verso

if(!$res){ die("Numéro de réservation manquant"); }
if($count < 1) $count = 1;
if($count > 5) $count = 5;
if($i < 1) $i = 1;

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
$titleStep = ($step==='verso') ? 'Verso' : 'Recto';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Scanner la Carte Nationale — Réservation <?= h($res) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root{
    --bg:#0f172a; --card:#0b1220; --muted:#8aa0bf;
    --primary:#2dd4bf; --primary-2:#06b6d4; --danger:#ef4444; --ok:#22c55e;
    --w:1100px;
  }
  *{box-sizing:border-box} html,body{height:100%}
  body{margin:0;font-family:Inter,system-ui,Segoe UI,Arial;color:#e5ecff;
       background: radial-gradient(1200px 600px at 10% -10%, #13203a 0%, #0b1220 40%, #0b1220 100%), var(--bg);}
  .wrap{max-width:var(--w);margin:0 auto;padding:18px 14px}
  .hero{background:linear-gradient(135deg, rgba(45,212,191,.08), rgba(6,182,212,.08));
        border-bottom:1px solid rgba(148,163,184,.12)}
  h1{margin:0;font-size:18px}
  .muted{color:var(--muted);font-size:13px;margin-top:6px}

  .card{background:linear-gradient(180deg, rgba(255,255,255,.02), rgba(255,255,255,.01));
        border:1px solid rgba(148,163,184,.15);border-radius:16px;padding:16px;backdrop-filter: blur(6px);
        box-shadow: 0 10px 30px rgba(2,6,23,.35), inset 0 1px 0 rgba(255,255,255,.03)}

  .cam{position:relative;border-radius:16px;overflow:hidden;background:#030712;
       border:1px solid rgba(148,163,184,.15);margin-bottom:14px}

  .camHeader{display:flex;align-items:center;justify-content:space-between;padding:12px;border-bottom:1px solid rgba(148,163,184,.12)}
  .camTitle{font-weight:700;font-size:14px}
  .camBtns{display:flex;gap:8px}

  /* كاميرا كبيرة ومناسبة لكل الأجهزة */
  video, .preview {
    display: block;
    width: 100%;
    height: auto;
    max-height: 82vh; /* يخلي الكاميرا كبيرة ومناسبة للهاتف */
    aspect-ratio: auto;
    object-fit: cover;
    background: #000;
  }

  @media (orientation: portrait) {
    video, .preview {
      max-height: 70vh; /* فالموبايل portrait */
      object-fit: contain;
    }
  }

  /* زر الالتقاط وسط لتحت */
  #bigCap{
    position:absolute; left:50%; bottom:14px; transform:translateX(-50%);
    width:64px; height:64px; border-radius:50%; border:0; cursor:pointer;
    background: radial-gradient(120% 120% at 30% 30%, var(--primary) 0%, var(--primary-2) 90%);
    box-shadow:0 10px 25px rgba(6,182,212,.35),0 0 0 8px rgba(45,212,191,.08);
    transition:.15s transform ease, .15s box-shadow ease, .15s opacity ease;
  }
  #bigCap:after{content:"";display:block;width:12px;height:12px;border-radius:50%;
    background:#001018;margin:auto;transform:translateY(26px);
    box-shadow: inset 0 0 0 4px rgba(255,255,255,.7)}
  #bigCap:active{ transform:translateX(-50%) scale(.96) }

  .btn{appearance:none;border:1px solid rgba(148,163,184,.22);background:#0e1729;color:#e7f7ff;font-weight:600;
       padding:10px 12px;border-radius:12px;cursor:pointer}
  .btn:disabled{opacity:.55;cursor:not-allowed}
  .btn.soft{background:#0a1324}
  .btn.pri{border-color:transparent;background:linear-gradient(135deg,var(--primary),var(--primary-2));color:#001018}

  .panel{border:1px solid rgba(148,163,184,.15);border-radius:14px;padding:12px;background:#0b1426}
  .row{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
  .ok{color:var(--ok);font-size:12px;margin-top:6px}
  .err{color:var(--danger);text-align:center;margin-top:12px;min-height:20px}
  .actions{display:flex;justify-content:space-between;margin-top:16px}
  .link{text-decoration:none;color:#dbeafe;opacity:.85}.link:hover{opacity:1}
</style>
</head>
<body>
<div class="hero">
  <div class="wrap">
    <h1>Scanner la Carte Nationale — Réservation <b><?= h($res) ?></b></h1>
    <div class="muted">التقط — <?= ($step==='verso'?'2/2 <b>Verso</b>':'1/2 <b>Recto</b>'); ?>.</div>
  </div>
</div>

<div class="wrap" style="padding-top:16px;">
  <div class="card">

    <!-- كاميرا واحدة -->
    <section class="cam" id="camBlock">
      <div class="camHeader">
        <div class="camTitle">Caméra — <?= h($titleStep) ?></div>
        <div class="camBtns">
          <button id="btnOpen" class="btn soft">Ouvrir la caméra</button>
          <button id="btnSwitch" class="btn soft" disabled>↺ تبديل</button>
        </div>
      </div>
      <video id="video" autoplay playsinline muted></video>
      <img id="shot" class="preview" alt="Aperçu">
      <button id="bigCap" aria-label="Capturer"></button>
    </section>

    <!-- لوحة التحكم -->
    <section class="panel">
      <h3 style="margin:0 0 8px"><?= h($titleStep) ?></h3>
      <div class="row">
        <button class="btn pri"  id="btnCapture"   disabled>Capturer (<?= h(strtolower($titleStep)) ?>)</button>
        <button class="btn soft" id="btnRetake"    disabled>Reprendre</button>
      </div>
      <div id="okMark" class="ok" style="display:none">✔ Capturé — اضغط Suivant</div>
    </section>

    <div id="camStatus" class="err"></div>

    <div class="actions">
      <a class="link" href="index.php">← Retour</a>
      <button id="btnNext" class="btn pri" disabled>Suivant →</button>
    </div>
  </div>
</div>

<script>
(() => {
  const step = <?= json_encode($step) ?>;
  const params = new URLSearchParams({
    res: <?= json_encode($res) ?>,
    count: <?= json_encode($count) ?>,
    i: <?= json_encode($i) ?><?= $ids!=='' ? ", ids: ".json_encode($ids) : "" ?>
  });

  const vid = document.getElementById('video');
  const shot = document.getElementById('shot');
  const openBtn = document.getElementById('btnOpen');
  const switchBtn = document.getElementById('btnSwitch');
  const capBtn = document.getElementById('btnCapture');
  const retakeBtn = document.getElementById('btnRetake');
  const nextBtn = document.getElementById('btnNext');
  const bigCap = document.getElementById('bigCap');
  const statusEl = document.getElementById('camStatus');

  let stream=null, devices=[], videoIdx=0, currentDeviceId=null;
  let dataUrl=null;
  const canvas=document.createElement('canvas');

  function setStatus(msg, ok=false){ statusEl.textContent=msg||''; statusEl.style.color=ok?'#5ad':'#ef4444'; }
  function stopStream(){ try{ stream?.getTracks()?.forEach(t=>t.stop()); }catch{} stream=null; if(vid) vid.srcObject=null; }

  function snapshot(){
    const w=vid.videoWidth||1280, h=vid.videoHeight||720;
    const s=w>1400?1400/w:1;
    canvas.width=Math.round(w*s); canvas.height=Math.round(h*s);
    canvas.getContext('2d').drawImage(vid,0,0,canvas.width,canvas.height);
    return canvas.toDataURL('image/jpeg',.86);
  }

  async function listVideoInputs(){
    try{
      if(navigator.permissions){
        const p=await navigator.permissions.query({name:'camera'});
        if(p.state==='prompt'){
          const tmp=await navigator.mediaDevices.getUserMedia({video:true});
          tmp.getTracks().forEach(t=>t.stop());
        }
      }
    }catch{}
    const all=await navigator.mediaDevices.enumerateDevices().catch(()=>[]);
    devices=all.filter(d=>d.kind==='videoinput');
  }

  function pickBackIndex(){
    if(!devices.length)return 0;
    const i1=devices.findIndex(d=>/back|rear|environment/i.test(d.label));
    if(i1>=0)return i1;
    if(devices.length>1)return devices.length-1;
    return 0;
  }

  async function startWith(constraints){
    try{
      stopStream();
      stream=await navigator.mediaDevices.getUserMedia(constraints);
      vid.srcObject=stream; vid.setAttribute('playsinline',''); vid.muted=true;
      await vid.play();
      switchBtn.disabled=devices.length<=1;
      capBtn.disabled=false;
      return true;
    }catch(e){console.warn(e);return false;}
  }

  async function openCamera(){
    if(location.protocol!=='https:' && location.hostname!=='localhost'){
      setStatus('خاص HTTPS/localhost باش تخدم الكاميرا.'); return;
    }
    setStatus('جاري فتح الكاميرا…',true);
    let ok=await startWith({video:{facingMode:{exact:'environment'}},audio:false});
    if(!ok) ok=await startWith({video:{facingMode:'environment'},audio:false});
    if(!ok){
      await listVideoInputs();
      if(!devices.length){setStatus('ما لقيتش كاميرا.');return;}
      const saved=localStorage.getItem('lun_cam_deviceId');
      videoIdx=saved?Math.max(0,devices.findIndex(d=>d.deviceId===saved)):pickBackIndex();
      currentDeviceId=devices[videoIdx]?.deviceId;
      ok=await startWith({video:{deviceId:{exact:currentDeviceId}},audio:false});
      if(ok) localStorage.setItem('lun_cam_deviceId',currentDeviceId);
      else setStatus('تعذّر فتح الكاميرا.');
    }else setStatus('');
  }

  async function switchCamera(){
    await listVideoInputs();
    if(!devices.length)return;
    videoIdx=(videoIdx+1)%devices.length;
    currentDeviceId=devices[videoIdx].deviceId;
    localStorage.setItem('lun_cam_deviceId',currentDeviceId);
    await startWith({video:{deviceId:{exact:currentDeviceId}},audio:false});
  }

  function showPreview(url){
    dataUrl=url;
    shot.src=url; shot.style.display='block';
    vid.style.display='none';
    capBtn.disabled=true;
    retakeBtn.disabled=false;
    nextBtn.disabled=false;
    document.getElementById('okMark').style.display='block';
    stopStream();
  }

  function resetCapture(){
    dataUrl=null;
    shot.removeAttribute('src');
    shot.style.display='none';
    vid.style.display='block';
    capBtn.disabled=false;
    retakeBtn.disabled=true;
    nextBtn.disabled=true;
    document.getElementById('okMark').style.display='none';
  }

  openBtn.addEventListener('click', openCamera);
  switchBtn.addEventListener('click', switchCamera);
  capBtn.addEventListener('click', ()=>{
    if(!vid || vid.readyState===0){setStatus('Caméra non prête');return;}
    showPreview(snapshot());
  });
  retakeBtn.addEventListener('click',()=>{resetCapture();openCamera();});
  bigCap.addEventListener('click',()=>capBtn.click());

  nextBtn.addEventListener('click',async()=>{
    if(!dataUrl)return;
    try{
      if(step==='recto'){
        sessionStorage.setItem('scan_front_'+<?= json_encode($res) ?>,dataUrl);
        const q=new URLSearchParams(params); q.set('s','verso');
        location.href='scan.php?'+q.toString();
      }else{
        const front=sessionStorage.getItem('scan_front_'+<?= json_encode($res) ?>);
        if(!front){setStatus('الصورة الأمامية مفقودة.');return;}
        const back=dataUrl;
        nextBtn.disabled=true; nextBtn.textContent='Envoi…';
        const fd=new FormData(); fd.append('front',front); fd.append('back',back);
        const qs=new URLSearchParams(<?= json_encode(['res'=>$res,'count'=>$count,'i'=>$i] + ($ids!==''?['ids'=>$ids]:[])) ?>);
        const r=await fetch('save_scans.php?'+qs.toString(),{method:'POST',body:fd});
        const j=await r.json().catch(()=>null);
        if(j?.ok){
          sessionStorage.removeItem('scan_front_'+<?= json_encode($res) ?>);
          const next=new URLSearchParams(<?= json_encode(['res'=>$res,'count'=>$count,'i'=>$i] + ($ids!==''?['ids'=>$ids]:[])) ?>);
          location.href='arrival-form.php?'+next.toString();
        }else{
          setStatus(j?.error||'Erreur lors de l’envoi.');
          nextBtn.disabled=false; nextBtn.textContent='Suivant →';
        }
      }
    }catch(e){
      setStatus('Erreur réseau: '+e.message);
      nextBtn.disabled=false; nextBtn.textContent='Suivant →';
    }
  });

  document.addEventListener('DOMContentLoaded',()=>{resetCapture();openCamera();});
  window.addEventListener('pagehide',stopStream);
  window.addEventListener('beforeunload',stopStream);
  document.addEventListener('visibilitychange',()=>{if(document.hidden)stopStream();});
})();
</script>
</body>
</html>

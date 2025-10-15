<!-- <?php // index.php — Landing: Logo + N° réservation + Suivant ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Lunja Village</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root{ --bg:#f6f7f9; --card:#ffffff; --border:#e5e7eb; --text:#0f172a; --primary:#1f4d7a }
  *{box-sizing:border-box}
  body{ margin:0; background:var(--bg); color:var(--text);
        font-family:Arial,"Segoe UI",Tahoma,sans-serif; min-height:100dvh; display:grid; place-items:center; }
  .card{ width:min(720px,92vw); background:var(--card); border:1px solid #e5e7eb; border-radius:16px;
         padding:28px; box-shadow:0 10px 30px rgba(0,0,0,.05); text-align:center; }
  .logo{ display:block; margin:0 auto 18px; max-width:360px; height:auto; max-height:120px }
  h1{ margin:0 0 6px; font-size:26px }
  p{ margin:0 0 18px; color:#475569 }
  .row{ display:flex; gap:10px; justify-content:center; align-items:center; margin:8px 0 16px; flex-wrap:wrap }
  input[type=text]{ width:260px; padding:11px 12px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; }
  .btn{ padding:12px 18px; border-radius:12px; text-decoration:none; font-weight:700;
        background:var(--primary); color:#fff; border:0; cursor:pointer; letter-spacing:.2px; }
  .err{ color:#b91c1c; font-size:12px; margin-top:6px; min-height:1em }
  .muted{ display:block; margin-top:10px; font-size:12px; color:#64748b }
  @media print{ body{ display:none } }
</style>
</head>
<body>
  <main class="card">
    <img class="logo" src="assetslogo.png" alt="Lunja Village Logo">
    <h1>Lunja Village</h1>
    <p>HOTEL • RESORT • AQUAPARK — Imi Ouaddar, Agadir</p>

    <div class="row">
      <input id="resno" type="text" placeholder="N° de réservation" inputmode="numeric" required>
      <button class="btn" id="go">Suivant →</button>
    </div>
    <div id="err" class="err"></div>

    <span class="muted">Appuyez sur Entrée aussi</span>
    <div class="muted">© <?=date('Y')?> Lunja Village</div>
  </main>

  <script>
    const res = document.getElementById('resno');
    const go  = document.getElementById('go');
    const err = document.getElementById('err');
    function navigate(){
      const v = (res.value||'').trim();
      if(!v){ err.textContent = "Le numéro de réservation est obligatoire."; res.focus(); return; }
      location.href = 'scan.php?res='+encodeURIComponent(v);


    }
    go.addEventListener('click', navigate);
    addEventListener('keydown', (e)=>{ if(e.key==='Enter') navigate(); });
  </script>
</body>
</html> -->


<?php // index.php — Landing مع اختيار عدد الاستمارات ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Lunja Village</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root{ --bg:#f6f7f9; --card:#ffffff; --border:#e5e7eb; --text:#0f172a; --primary:#1f4d7a }
  *{box-sizing:border-box}
  body{ margin:0; background:var(--bg); color:var(--text);
        font-family:Arial,"Segoe UI",Tahoma,sans-serif; min-height:100dvh; display:grid; place-items:center; }
  .card{ width:min(720px,92vw); background:var(--card); border:1px solid #e5e7eb; border-radius:16px;
         padding:28px; box-shadow:0 10px 30px rgba(0,0,0,.05); text-align:center; }
  .logo{ display:block; margin:0 auto 18px; max-width:360px; height:auto; max-height:120px }
  h1{ margin:0 0 6px; font-size:26px }
  p{ margin:0 0 18px; color:#475569 }
  .row{ display:flex; gap:10px; justify-content:center; align-items:center; margin:8px 0 16px; flex-wrap:wrap }
  input[type=text]{ width:260px; padding:11px 12px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; }
  select{ padding:11px 12px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; }
  .btn{ padding:12px 18px; border-radius:12px; text-decoration:none; font-weight:700;
        background:var(--primary); color:#fff; border:0; cursor:pointer; letter-spacing:.2px; }
  .err{ color:#b91c1c; font-size:12px; margin-top:6px; min-height:1em }
  .muted{ display:block; margin-top:10px; font-size:12px; color:#64748b }
  @media print{ body{ display:none } }
</style>
</head>
<body>
  <main class="card">
    <img class="logo" src="assetslogo.png" alt="Lunja Village Logo">
    <h1>Lunja Village</h1>
    <p>HOTEL • RESORT • AQUAPARK — Imi Ouaddar, Agadir</p>

    <div class="row">
      <input id="resno" type="text" placeholder="N° de réservation" inputmode="numeric" required>
      <select id="count" aria-label="عدد الاستمارات">
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
      </select>
      <button class="btn" id="go">Suivant →</button>
    </div>
    <div id="err" class="err"></div>

    <span class="muted">Appuyez sur Entrée aussi</span>
    <div class="muted">© <?=date('Y')?> Lunja Village</div>
  </main>

  <script>
    const res = document.getElementById('resno');
    const go  = document.getElementById('go');
    const cnt = document.getElementById('count');
    const err = document.getElementById('err');

    function navigate(){
      const v = (res.value||'').trim();
      const n = parseInt(cnt.value,10);
      if(!v){ err.textContent = "Le numéro de réservation est obligatoire."; res.focus(); return; }
      if(!(n>=1 && n<=5)){ err.textContent = "Choisissez un nombre entre 1 et 5."; return; }
      location.href = 'scan.php?res='+encodeURIComponent(v)+'&count='+n+'&i=1';
    }
    go.addEventListener('click', navigate);
    addEventListener('keydown', (e)=>{ if(e.key==='Enter') navigate(); });
  </script>
</body>
</html>

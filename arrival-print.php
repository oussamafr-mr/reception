<?php
ini_set('display_errors',1); error_reporting(E_ALL);

/* SQLite */
$SQLITE_PATH = __DIR__ . '/hotel_app.db';
$pdo = new PDO('sqlite:'.$SQLITE_PATH, null, null, [
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
]);

/* ids=1,2,3 أو id=7 */
$idsParam = isset($_GET['ids']) ? trim($_GET['ids']) : '';
$ids = array_values(array_filter(array_map('intval', explode(',', $idsParam)), fn($v)=>$v>0));
if (!$ids) {
  $single = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($single > 0) $ids = [$single];
}

/* جلب السجلات */
$records = [];
if ($ids) {
  $in  = implode(',', array_fill(0, count($ids), '?'));
  $stm = $pdo->prepare("SELECT * FROM arrivals WHERE id IN ($in) ORDER BY id ASC");
  $stm->execute($ids);
  $records = $stm->fetchAll();
}
if(!$records){ die("Record(s) not found."); }

/* دوال */
function esc($s){ return htmlspecialchars($s??'',ENT_QUOTES,'UTF-8'); }
function ar_nbsp($s){ return str_replace(' ', '&nbsp;', $s); }
function lab($ar,$fr,$en=''){
  $fr_en = $en ? "$fr / $en" : $fr;
  return '<div class="lab"><div class="ar">'.ar_nbsp($ar).'</div><div class="fr">'.$fr_en.'</div></div>';
}

/* صور البطاقة */
function id_images_for_row($row){
  $uploadsDir = __DIR__ . '/uploads';
  $frontWeb = $backWeb = '';
  $cleanRes = '';
  $idx = isset($row['batch_idx']) ? (int)$row['batch_idx'] : 0;

  if (!empty($row['reservation_no'])) {
    $cleanRes = preg_replace('/[^A-Za-z0-9_\-]/','', $row['reservation_no']);
    foreach (['jpg','jpeg','png','webp'] as $ext) {
      $f = $uploadsDir . "/res_{$cleanRes}_{$idx}_front.$ext";
      $b = $uploadsDir . "/res_{$cleanRes}_{$idx}_back.$ext";
      if (!$frontWeb && $idx>0 && is_file($f)) { $frontWeb = "uploads/" . basename($f); }
      if (!$backWeb  && $idx>0 && is_file($b)) { $backWeb  = "uploads/" . basename($b); }
    }
    if (!$frontWeb || !$backWeb) {
      foreach (['jpg','jpeg','png','webp'] as $ext) {
        $f = $uploadsDir . "/res_{$cleanRes}_front.$ext";
        $b = $uploadsDir . "/res_{$cleanRes}_back.$ext";
        if (!$frontWeb && is_file($f)) { $frontWeb = "uploads/" . basename($f); }
        if (!$backWeb  && is_file($b)) { $backWeb  = "uploads/" . basename($b); }
      }
    }
  }
  return [$frontWeb,$backWeb,$cleanRes];
}
?>
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8">
<title>استمارات الوصول</title>
<style>
  :root{ --table-width: 188mm; --fs-table: 10.6px; }
  @page{ size:A4; margin:0 }
  body{ margin:0; background:#f3f4f6; font-family:Arial,"Segoe UI",Tahoma,sans-serif }

  .actions{max-width:900px;margin:10px auto;display:flex;gap:10px;justify-content:center}
  .btn{padding:9px 12px;border-radius:8px;text-decoration:none;cursor:pointer;border:none}
  .primary{background:#1f4d7a;color:#fff} .ghost{background:#475569;color:#fff} .link{background:#e5e7eb;color:#111}

  #printArea{ width:210mm; margin:0 auto; background:#fff; color:#000; box-sizing:border-box; padding: 10mm 7mm 10mm; }
  @media screen{ #printArea{ box-shadow:0 0 0 1px #e5e7eb, 0 8px 24px rgba(0,0,0,.06) } }
  @media print{ .actions{display:none} #printArea{ box-shadow:none } }

  .pb{ page-break-before: always; }

  .header{ display:flex; align-items:flex-start; justify-content:center; margin-bottom:6mm; position:relative; }
  .logo-box{ position:absolute; left:0; top:0; }
  .logo{ display:block; max-height:22mm; width:auto; }
  .title{ text-align:center; flex:1; }
  .title h1{ margin:0; font-size:17px; font-weight:700; line-height:1.5; }

  table{ width:var(--table-width); margin:0 auto; border-collapse:collapse; table-layout:fixed; }
  th,td{ border:1px solid #cfd4da; padding:4px 6px; vertical-align:top; font-size:var(--fs-table); line-height:1.35; width:50%; }
  th{ background:#f1f3f6; text-align:left }
  .lab{display:flex;flex-direction:column}
  .lab .ar{direction:rtl;text-align:right;font-weight:700}
  .lab .fr{font-weight:600;color:#333}

  .footer{ margin-top:4mm }
  .sig{ display:grid;grid-template-columns:1fr 1fr;gap:6px; width:var(--table-width); margin:0 auto 3mm; align-items:end; }
  .sigcol{ display:flex; flex-direction:column; }
  .siglabel{ font-weight:700; margin:0 0 4px; line-height:1.2; }
  .sigbox{height:36px;border:1px solid #999;background:#eef2f7}

  .note{ width:calc(var(--table-width) - 8mm); margin:0 auto }
  .nota-fr,.nota-ar{font-size:9px;line-height:1.32}
  .nota-fr{direction:ltr;text-align:left}
  .nota-ar{direction:rtl;text-align:right;overflow-wrap:anywhere;hyphens:auto}

  .images-page{ padding-top:6mm }
  .img-title{ text-align:center; margin:0 0 6mm; font-size:16px; font-weight:700 }
  .img-grid{ width:var(--table-width); margin:0 auto; display:grid; grid-template-columns:1fr 1fr; gap:8mm; align-items:start }
  .img-cell{ border:1px solid #e5e7eb; border-radius:6px; padding:4mm; text-align:center }
  .img-cell h4{ margin:0 0 4mm; font-size:12.5px }
  .img-cell img{ width:100%; height:auto; max-height: 220mm; object-fit:contain }
</style>
</head>
<body>

<div class="actions">
  <button class="btn primary" onclick="window.print()">Imprimer / Save as PDF</button>
  <button class="btn ghost" id="dlBtn">Télécharger PDF</button>
  <a class="btn link" href="arrival-form.php">⬅︎ Retour</a>
</div>

<div id="printArea">
  <?php foreach($records as $ix=>$row): list($frontWeb,$backWeb,$cleanRes) = id_images_for_row($row); ?>
    <?php if($ix>0): ?><div class="pb"></div><?php endif; ?>

    <!-- الصفحة 1 -->
    <div class="header">
      <div class="logo-box"><img class="logo" src="assetslogo.png" alt="Lunja Village Logo"></div>
      <div class="title"><h1><?= ar_nbsp('ورقة الوصول') ?><br>Bulletin d’Arrivée<br>Arrival Form</h1></div>
    </div>

    <?php
      $hasPassport  = !empty($row['passport']);
      $idKindRaw    = trim((string)($row['id_kind'] ?? ''));
      $isResidence  = stripos($idKindRaw, 'carte de séjour') === 0;
      // رقم بطاقة الإقامة فقط (نحيّدو النص)
      $residenceNum = $isResidence ? trim(preg_replace('/^carte\s+de\s+séjour\s*/i','',$idKindRaw)) : '';

      // نحدّدو شنو نورّيو:
      $showPassportRow   = $hasPassport;                 // باسبور ⇒ سطر خاص
      $showResidenceRow  = !$hasPassport && $isResidence;
      $showNationalIdRow = !$hasPassport && !$isResidence && $idKindRaw !== '';
    ?>

    <table>
      <tr><th><?= lab('رقم الحجز','N° de réservation','Reservation No.') ?></th><td><?= esc($row['reservation_no']) ?></td></tr>
      <tr><th><?= lab('الاسم العائلي','Nom','Name') ?></th><td><?= esc($row['name']) ?></td></tr>
      <tr><th><?= lab('الاسم الشخصي','Prénom','First Name') ?></th><td><?= esc($row['firstname']) ?></td></tr>
      <tr><th><?= lab('تاريخ ومكان الازدياد','Date et lieu de naissance','Date & place of birth') ?></th><td><?= esc($row['dob']) ?></td></tr>
      <tr><th><?= lab('الجنسية','Nationalité','Nationality') ?></th><td><?= esc($row['nationality']) ?></td></tr>
      <tr><th><?= lab('العنوان','Adresse','Address') ?></th><td><?= esc($row['address']) ?></td></tr>
      <tr><th><?= lab('المدينة','Ville','City') ?></th><td><?= esc($row['city']) ?></td></tr>
      <tr><th><?= lab('الرمز البريدي','Code Postal','Zip code') ?></th><td><?= esc($row['zip']) ?></td></tr>
      <tr><th><?= lab('البلد','Pays','Country') ?></th><td><?= esc($row['country']) ?></td></tr>
      <tr><th><?= lab('البريد الإلكتروني','E-mail','E-mail') ?></th><td><?= esc($row['email']) ?></td></tr>
      <tr><th><?= lab('المهنة','Profession','Profession') ?></th><td><?= esc($row['profession']) ?></td></tr>
      <tr><th><?= lab('قادِم من','Provenance','Coming from') ?></th><td><?= esc($row['from_place']) ?></td></tr>
      <tr><th><?= lab('ذاهب إلى','Destination','Going to') ?></th><td><?= esc($row['to_place']) ?></td></tr>
      <tr><th><?= lab('تاريخ الوصول','Date d’arrivée','Date of entrance') ?></th><td><?= esc($row['date_in']) ?></td></tr>
      <tr><th><?= lab('تاريخ الذهاب','Date de départ','Date of departure') ?></th><td><?= esc($row['date_out']) ?></td></tr>

      <?php if($showResidenceRow): ?>
        <tr>
          <th><?= lab('بطاقة الإقامة','Carte de séjour','Residence card') ?></th>
          <td><?= esc($residenceNum ?: $idKindRaw) ?></td>
        </tr>
      <?php endif; ?>

      <?php if($showNationalIdRow): ?>
        <tr>
          <th><?= lab('البطاقة الوطنية','Carte nationale','National ID') ?></th>
          <td><?= esc($idKindRaw) ?></td>
        </tr>
      <?php endif; ?>

      <tr><th><?= lab('تاريخ ومكان التسليم','Date et lieu de délivrance','Date & place of issue') ?></th><td><?= esc($row['id_issue']) ?></td></tr>

      <?php if($showPassportRow): ?>
        <tr><th><?= lab('رقم جواز السفر','N° du passeport','Passport Nr') ?></th><td><?= esc($row['passport']) ?></td></tr>
      <?php endif; ?>

      <tr><th><?= lab('رقم الغرفة','N° de chambre','Room number') ?></th><td><?= esc($row['room']) ?></td></tr>
      <tr><th><?= lab('أكادير في','Agadir le / on','Agadir on') ?></th><td><?= esc($row['agadir']) ?></td></tr>
    </table>

    <div class="footer">
      <div class="sig">
        <div class="sigcol">
          <div class="siglabel"><?= ar_nbsp('الإمضاء') ?> • Signature</div>
          <div class="sigbox"></div>
        </div>
        <div class="sigcol">
          <div class="siglabel">Réceptionniste</div>
          <div class="sigbox"></div>
        </div>
      </div>

      <div class="note">
        <div class="nota-fr"><b>NOTA&nbsp;—</b> Tout voyageur est tenu de remplir et signer le présent bulletin
          <b>TRES LISIBLEMENT ET EN GROS CARACTERES</b> (Dahir du 29/05/40) et de justifier son identité
          par des pièces officielles énumérées au Dahir précité. Ces prescriptions sont applicables aux
          femmes même accompagnées de leur mari.
        </div>
        <div class="nota-ar">تنبيه — يجب على كل مسافر ملء ووضع خطه على هذه الورقة بصفة يمكن قراءته برسوم كبيرة
          (الظهير 29/05/40) وتبرير معرفته بأوراق التعريف الرسمية المذكورة في الظهير المشار إليه.
          هذه المقتضيات تنطبق أيضًا على النساء ولو كنّ مصحوبات بأزواجهنّ.
        </div>
      </div>
    </div>

    <?php if ($frontWeb || $backWeb): ?>
      <!-- الصفحة 2: الصور -->
      <div class="pb images-page">
        <h2 class="img-title">Pièce d’identité — Réservation: <?= esc($cleanRes ?: ($row['reservation_no']??'')) ?></h2>
        <div class="img-grid">
          <?php if($frontWeb): ?>
            <div class="img-cell">
              <h4>Recto</h4>
              <img src="<?= $frontWeb ?>" alt="CIN Recto">
            </div>
          <?php endif; ?>
          <?php if($backWeb): ?>
            <div class="img-cell">
              <h4>Verso</h4>
              <img src="<?= $backWeb ?>" alt="CIN Verso">
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

  <?php endforeach; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
document.getElementById('dlBtn')?.addEventListener('click', async ()=>{
  const el = document.getElementById('printArea');
  const opt = {
    margin: 0,
    filename: 'arrivals-batch.pdf',
    image: { type:'jpeg', quality:0.98 },
    html2canvas: { scale: 3, useCORS: true, scrollX: 0, scrollY: 0, backgroundColor:'#ffffff' },
    jsPDF: { unit:'mm', format:'a4', orientation:'portrait' },
    pagebreak: { mode: ['css','legacy'] }
  };
  await html2pdf().set(opt).from(el).save();
});
</script>
</body>
</html>

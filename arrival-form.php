<?php
ini_set('display_errors',1); error_reporting(E_ALL);

$SQLITE_PATH = __DIR__ . '/hotel_app.db';
$pdo = new PDO('sqlite:'.$SQLITE_PATH, null, null, [
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
]);

$pdo->exec("
CREATE TABLE IF NOT EXISTS arrivals (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  reservation_no TEXT,
  name TEXT, firstname TEXT, dob TEXT, nationality TEXT, address TEXT, city TEXT, zip TEXT, country TEXT,
  email TEXT, profession TEXT, from_place TEXT, to_place TEXT,
  date_in TEXT, date_out TEXT,
  id_kind TEXT NOT NULL,
  id_issue TEXT, passport TEXT, room TEXT, agadir TEXT,
  batch_idx INTEGER,
  created_at TEXT DEFAULT (datetime('now'))
);
");

$cols = $pdo->query("PRAGMA table_info(arrivals)")->fetchAll();
$hasRes = $hasBatch = false;
foreach($cols as $c){
  if($c['name']==='reservation_no') $hasRes=true;
  if($c['name']==='batch_idx')      $hasBatch=true;
}
if(!$hasRes){  $pdo->exec("ALTER TABLE arrivals ADD COLUMN reservation_no TEXT"); }
if(!$hasBatch){$pdo->exec("ALTER TABLE arrivals ADD COLUMN batch_idx INTEGER"); }

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$resFromGet = isset($_GET['res']) ? trim($_GET['res']) : '';
$lockRes    = ($resFromGet !== '');
$total      = isset($_GET['count']) ? max(1, min(5, (int)$_GET['count'])) : 1;
$idx        = isset($_GET['i']) ? max(1, (int)$_GET['i']) : 1;
$idsChain   = isset($_GET['ids']) ? trim($_GET['ids']) : '';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // نوع الوثيقة + رقمها (3 اختيارات دابا)
  $doc_type   = $_POST['doc_type']   ?? '';           // 'id' | 'passport' | 'residence'
  $doc_number = trim($_POST['doc_number'] ?? '');

  if (!in_array($doc_type, ['id','passport','residence'], true)) {
    $errors[] = "Choisissez le type de pièce d’identité.";
  }
  if ($doc_number === '') {
    $errors[] = ($doc_type === 'passport')
      ? "Le champ « N° du passeport » est obligatoire."
      : "Le champ du numéro est obligatoire.";
  }

  // نحضّر القيم حسب الاختيار
  $final_id_kind = null;
  $final_passport = null;
  if (!$errors) {
    if ($doc_type === 'passport') {
      $final_id_kind = 'Passeport';
      $final_passport = $doc_number;
    } elseif ($doc_type === 'residence') {
      // Carte de séjour: نخزّنها داخل id_kind بالنص + الرقم
      $final_id_kind = 'Carte de séjour ' . $doc_number;
      $final_passport = null;
    } else { // 'id' (Carte nationale)
      $final_id_kind = $doc_number;   // Nature & N° …
      $final_passport = null;
    }
  }

  if (!$errors) {
    $reservation_no = $lockRes ? $resFromGet : ($_POST['reservation_no'] ?? null);

    $stmt = $pdo->prepare("INSERT INTO arrivals
      (reservation_no, name, firstname, dob, nationality, address, city, zip, country, email,
       profession, from_place, to_place, date_in, date_out, id_kind, id_issue, passport, room, agadir, batch_idx)
      VALUES
      (:reservation_no, :name, :firstname, :dob, :nationality, :address, :city, :zip, :country, :email,
       :profession, :from_place, :to_place, :date_in, :date_out, :id_kind, :id_issue, :passport, :room, :agadir, :batch_idx)");
    $stmt->execute([
      ':reservation_no' => $reservation_no,
      ':name'        => $_POST['name']        ?? null,
      ':firstname'   => $_POST['firstname']   ?? null,
      ':dob'         => $_POST['dob']         ?? null,
      ':nationality' => $_POST['nationality'] ?? null,
      ':address'     => $_POST['address']     ?? null,
      ':city'        => $_POST['city']        ?? null,
      ':zip'         => $_POST['zip']         ?? null,
      ':country'     => $_POST['country']     ?? null,
      ':email'       => $_POST['email']       ?? null,
      ':profession'  => $_POST['profession']  ?? null,
      ':from_place'  => $_POST['from_place']  ?? null,
      ':to_place'    => $_POST['to_place']    ?? null,
      ':date_in'     => $_POST['date_in']     ?? null,
      ':date_out'    => $_POST['date_out']    ?? null,
      ':id_kind'     => $final_id_kind,
      ':id_issue'    => $_POST['id_issue']    ?? null,
      ':passport'    => $final_passport,
      ':room'        => $_POST['room']        ?? null,
      ':agadir'      => $_POST['agadir']      ?? null,
      ':batch_idx'   => $idx,
    ]);
    $newId = (int)$pdo->lastInsertId();

    $accum = $idsChain !== '' ? ($idsChain . ',' . $newId) : (string)$newId;

    if ($idx < $total) {
      $next = $idx + 1;
      $q = http_build_query([
        'res'   => $reservation_no,
        'count' => $total,
        'i'     => $next,
        'ids'   => $accum
      ]);
      header("Location: scan.php?".$q);
      exit;
    }

    header("Location: arrival-print.php?ids=".$accum);
    exit;
  }
}

$val = fn($k)=> h($_POST[$k] ?? ($_GET[$k] ?? ''));
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Formulaire d'arrivée – Saisie</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root{ --w: 900px }
  body{ margin:0; font-family:Arial,"Segoe UI",Tahoma,sans-serif; background:#f6f7f9; color:#0f172a }
  .wrap{ max-width:var(--w); margin:24px auto; padding:16px }
  .card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 18px 8px }
  h1{ margin:0 0 6px; font-size:20px }
  .sub{ margin:0 0 14px; color:#475569; font-size:13px }
  .grid{ display:grid; grid-template-columns:1fr 1fr; gap:12px 16px }
  label{ display:block; font-size:12px; color:#334155; margin-bottom:6px; font-weight:700 }
  input,textarea,select{ width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; background:#fff; }
  .row-1{ grid-column:1 / -1 }
  .bar{ display:flex; gap:10px; margin-top:14px; justify-content:flex-end }
  .btn{ padding:10px 14px; border:none; border-radius:10px; cursor:pointer }
  .primary{ background:#1f4d7a; color:#fff }
  .ghost{ background:#e2e8f0; color:#111 }
  .note{ font-size:12px; color:#64748b; margin-top:6px }
  .error{ background:#fef2f2; color:#991b1b; border:1px solid #fecaca; padding:10px 12px; border-radius:8px; margin-bottom:12px }
  .readonly{ background:#f8fafc; color:#475569 }
  @media (max-width: 760px){ .grid{ grid-template-columns:1fr } }
</style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>Formulaire d'arrivée – Saisie des informations</h1>
      <p class="sub">الاستمارة <?= (int)$idx ?> من <?= (int)$total ?> — رقم الحجز: <strong><?= h($resFromGet ?: $val('reservation_no')) ?></strong></p>

      <?php if ($errors): ?>
        <div class="error">
          <?php foreach($errors as $e){ echo "• ".h($e)."<br>"; } ?>
        </div>
      <?php endif; ?>

      <form method="post" action="arrival-form.php?<?= http_build_query(['res'=>$resFromGet,'count'=>$total,'i'=>$idx,'ids'=>$idsChain]) ?>" novalidate>
        <div class="grid">
          <div class="row-1">
            <label>N° de réservation</label>
            <input name="reservation_no" value="<?= $lockRes ? h($resFromGet) : $val('reservation_no') ?>" <?= $lockRes ? 'readonly class="readonly"' : '' ?>>
          </div>

          <div><label>Nom</label><input name="name" value="<?=$val('name')?>"></div>
          <div><label>Prénom</label><input name="firstname" value="<?=$val('firstname')?>"></div>
          <div><label>Date & lieu de naissance</label><input name="dob" value="<?=$val('dob')?>"></div>
          <div><label>Nationalité</label><input name="nationality" value="<?=$val('nationality')?>"></div>
          <div class="row-1"><label>Adresse</label><input name="address" value="<?=$val('address')?>"></div>
          <div><label>Ville</label><input name="city" value="<?=$val('city')?>"></div>
          <div><label>Code Postal</label><input name="zip" value="<?=$val('zip')?>"></div>
          <div><label>Pays</label><input name="country" value="<?=$val('country')?>"></div>
          <div><label>E-mail</label><input type="email" name="email" value="<?=$val('email')?>"></div>
          <div><label>Profession</label><input name="profession" value="<?=$val('profession')?>"></div>
          <div><label>Provenance</label><input name="from_place" value="<?=$val('from_place')?>"></div>
          <div><label>Destination</label><input name="to_place" value="<?=$val('to_place')?>"></div>
          <div><label>Date d’arrivée</label><input type="date" name="date_in" value="<?=$val('date_in')?>"></div>
          <div><label>Date de départ</label><input type="date" name="date_out" value="<?=$val('date_out')?>"></div>

          <!-- نوع الوثيقة -->
          <div>
            <label>Type de pièce d’identité</label>
            <select name="doc_type" id="doc_type">
              <option value="id" <?= ($val('doc_type')==='id' ? 'selected' : '') ?>>Carte nationale</option>
              <option value="passport" <?= ($val('doc_type')==='passport' ? 'selected' : '') ?>>Passeport</option>
              <option value="residence" <?= ($val('doc_type')==='residence' ? 'selected' : '') ?>>Carte de séjour</option>
            </select>
          </div>
          <div>
            <label id="doc_number_label">
              <?php
                $dt = $_POST['doc_type'] ?? '';
                if ($dt==='passport')      echo 'N° du passeport';
                elseif ($dt==='residence') echo 'N° de la carte de séjour';
                else                       echo 'Nature & N° des pièces d’identité <span class="note">(*) obligatoire</span>';
              ?>
            </label>
            <input name="doc_number" id="doc_number" value="<?= h($_POST['doc_number'] ?? '') ?>" required>
          </div>

          <div><label>Date & lieu de délivrance</label><input name="id_issue" value="<?=$val('id_issue')?>"></div>
          <div><label>N° de chambre</label><input name="room" value="<?=$val('room')?>"></div>
          <div><label>Agadir le</label><input name="agadir" value="<?=$val('agadir')?>"></div>
        </div>

        <div class="bar">
          <a class="btn ghost" href="index.php">Retour</a>
          <button class="btn primary" type="submit">
            <?= ($idx < $total) ? "Enregistrer &amp; suivant" : "Enregistrer &amp; imprimer" ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
  // تحديث اللّيبل والبلايسهولدر حسب الاختيار
  (function(){
    const sel = document.getElementById('doc_type');
    const lab = document.getElementById('doc_number_label');
    const inp = document.getElementById('doc_number');
    function sync(){
      if (sel.value === 'passport'){
        lab.innerHTML = 'N° du passeport';
        inp.placeholder = 'Ex: U1234567';
      } else if (sel.value === 'residence'){
        lab.innerHTML = 'N° de la carte de séjour';
        inp.placeholder = 'Ex: SEJ 123456';
      } else {
        lab.innerHTML = 'Nature & N° des pièces d’identité <span class="note">(*) obligatoire</span>';
        inp.placeholder = 'Ex: CNIE X123456 (ou type + numéro)';
      }
    }
    sel.addEventListener('change', sync);
    sync();
  })();
  </script>
</body>
</html>

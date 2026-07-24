<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle  = 'Werken bij SoulFM';
$activePage = 'solliciteer';
$csrf       = generateCsrfToken();
$departments = getDepartments();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {

    $firstName    = sanitize($_POST['first_name']    ?? '');
    $lastName     = sanitize($_POST['last_name']     ?? '');
    $email        = trim($_POST['email']             ?? '');
    $phone        = sanitize($_POST['phone']         ?? '');
    $birthDate    = trim($_POST['birth_date']        ?? '');
    $city         = sanitize($_POST['city']          ?? '');
    $department   = trim($_POST['department']        ?? '');
    $motivation   = sanitize($_POST['motivation']    ?? '');
    $experience   = sanitize($_POST['experience']    ?? '');
    $portfolioUrl = trim($_POST['portfolio_url']     ?? '');
    $availability = sanitize($_POST['availability']  ?? '');
    $ip           = getUserIp();

    // Validatie
    if (!$firstName)                              $errors['first_name']  = 'Vul je voornaam in.';
    if (!$lastName)                               $errors['last_name']   = 'Vul je achternaam in.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email']     = 'Vul een geldig e-mailadres in.';
    if (!$department)                             $errors['department']  = 'Kies een afdeling.';
    elseif (!getDepartment($department))          $errors['department']  = 'Ongeldige afdeling.';
    if (strlen($motivation) < 50)                 $errors['motivation']  = 'Schrijf minimaal 50 tekens motivatie.';
    if ($portfolioUrl && !filter_var($portfolioUrl, FILTER_VALIDATE_URL))
                                                  $errors['portfolio_url'] = 'Vul een geldige URL in (inclusief https://).';

    if (!$errors) {
        try {
            $pdo  = getPDO();
            $stmt = $pdo->prepare('
                INSERT INTO applications
                  (first_name, last_name, email, phone, birth_date, city, department,
                   motivation, experience, portfolio_url, availability, ip_address)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ');
            $stmt->execute([
                $firstName, $lastName, $email,
                $phone ?: null,
                $birthDate ?: null,
                $city ?: null,
                $department,
                $motivation,
                $experience ?: null,
                $portfolioUrl ?: null,
                $availability ?: null,
                $ip
            ]);
            $success = true;
        } catch (PDOException $e) {
            error_log('Application insert error: ' . $e->getMessage());
            $errors['_general'] = 'Er is een technische fout opgetreden. Probeer het later opnieuw.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
<div class="page-header">
  <div class="container">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> <span>›</span> <span>Werken bij SoulFM</span></div>
    <h1>Werken bij SoulFM</h1>
    <p>Wil jij deel uitmaken van ons team? We zijn altijd op zoek naar gepassioneerde mensen. Solliciteer hieronder!</p>
  </div>
</div>

<!-- Afdelingen overzicht -->
<section class="section-sm" style="background:rgba(30,58,95,0.12);border-bottom:1px solid rgba(0,180,216,0.08)">
  <div class="container">
    <span class="section-subtitle">Onze afdelingen</span>
    <h2 class="section-title" style="font-size:1.8rem;margin-bottom:1.5rem">Kies jouw plek</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem">
      <?php foreach ($departments as $dept): ?>
      <div class="card" style="padding:1.5rem;cursor:pointer" onclick="document.getElementById('department').value='<?= htmlspecialchars($dept['slug']) ?>';document.getElementById('form-section').scrollIntoView({behavior:'smooth'})">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem">
          <div style="width:40px;height:40px;background:rgba(0,180,216,0.12);border:1px solid rgba(0,180,216,0.25);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <?php
            $icons = [
                'dj'            => '<svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--color-accent)"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>',
                'administratie' => '<svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--color-accent)"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>',
                'evenementen'   => '<svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--color-accent)"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm3 18H5V8h14v11z"/></svg>',
                'redactie'      => '<svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--color-accent)"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
                'content'       => '<svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--color-accent)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>',
                'marketing'     => '<svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--color-accent)"><path d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3zm0-5v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7zm7-3c3.866 0 7 1.343 7 3s-3.134 3-7 3-7-1.343-7-3 3.134-3 7-3z"/></svg>',
            ];
            echo $icons[$dept['slug']] ?? '';
            ?>
          </div>
          <div>
            <div style="font-weight:700;color:var(--color-white);font-size:1rem"><?= htmlspecialchars($dept['name']) ?></div>
          </div>
        </div>
        <p style="color:var(--color-text-dim);font-size:0.86rem;margin:0"><?= htmlspecialchars($dept['description']) ?></p>
        <div style="margin-top:1rem">
          <span style="font-size:0.78rem;color:var(--color-accent);font-weight:600">Solliciteer →</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Sollicitatieformulier -->
<section class="section" id="form-section">
  <div class="container">
    <?php if ($success): ?>
    <div style="max-width:600px;margin:0 auto;text-align:center;padding:3rem 2rem;background:rgba(16,185,129,0.07);border:1px solid rgba(16,185,129,0.25);border-radius:20px">
      <div style="width:64px;height:64px;background:rgba(16,185,129,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem">
        <svg viewBox="0 0 24 24" style="width:32px;height:32px;fill:#10b981"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14l-4-4 1.41-1.41L10 13.17l6.59-6.59L18 8l-8 8z"/></svg>
      </div>
      <h2 style="color:#10b981;margin-bottom:0.75rem">Sollicitatie verstuurd!</h2>
      <p style="color:var(--color-text-dim);margin-bottom:2rem">Bedankt voor je sollicitatie. We nemen zo snel mogelijk contact met je op.</p>
      <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline">Terug naar home</a>
    </div>

    <?php else: ?>
    <div style="display:grid;grid-template-columns:1fr 360px;gap:4rem;align-items:start">

      <!-- Formulier -->
      <div>
        <span class="section-subtitle">Sollicitatieformulier</span>
        <h2 class="section-title" style="font-size:1.9rem;margin-bottom:0.5rem">Jouw sollicitatie</h2>
        <p style="color:var(--color-text-dim);margin-bottom:2.5rem">Vul het formulier zo volledig mogelijk in. We lezen elke sollicitatie zorgvuldig.</p>

        <?php if (!empty($errors['_general'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errors['_general']) ?></div>
        <?php endif; ?>

        <form method="POST" action="#form-section" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

          <!-- Persoonlijke gegevens -->
          <div style="background:rgba(30,58,95,0.25);border:1px solid rgba(0,180,216,0.1);border-radius:16px;padding:2rem;margin-bottom:1.75rem">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1.5rem;color:var(--color-accent);text-transform:uppercase;letter-spacing:1px;font-family:var(--font-body)">Persoonlijke gegevens</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">
              <div class="form-group" style="margin:0">
                <label class="form-label" for="first_name">Voornaam <span class="required">*</span></label>
                <input type="text" id="first_name" name="first_name" class="form-control <?= isset($errors['first_name'])?'error':'' ?>"
                  value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required maxlength="100" autocomplete="given-name">
                <?php if (isset($errors['first_name'])): ?><span class="form-error"><?= $errors['first_name'] ?></span><?php endif; ?>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label" for="last_name">Achternaam <span class="required">*</span></label>
                <input type="text" id="last_name" name="last_name" class="form-control <?= isset($errors['last_name'])?'error':'' ?>"
                  value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required maxlength="100" autocomplete="family-name">
                <?php if (isset($errors['last_name'])): ?><span class="form-error"><?= $errors['last_name'] ?></span><?php endif; ?>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label" for="email">E-mailadres <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-control <?= isset($errors['email'])?'error':'' ?>"
                  value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required maxlength="150" autocomplete="email">
                <?php if (isset($errors['email'])): ?><span class="form-error"><?= $errors['email'] ?></span><?php endif; ?>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label" for="phone">Telefoonnummer</label>
                <input type="tel" id="phone" name="phone" class="form-control"
                  value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" maxlength="30" autocomplete="tel">
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label" for="birth_date">Geboortedatum</label>
                <input type="date" id="birth_date" name="birth_date" class="form-control"
                  value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>"
                  max="<?= date('Y-m-d', strtotime('-16 years')) ?>">
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label" for="city">Woonplaats</label>
                <input type="text" id="city" name="city" class="form-control"
                  value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" maxlength="100" autocomplete="address-level2">
              </div>
            </div>
          </div>

          <!-- Afdeling & beschikbaarheid -->
          <div style="background:rgba(30,58,95,0.25);border:1px solid rgba(0,180,216,0.1);border-radius:16px;padding:2rem;margin-bottom:1.75rem">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1.5rem;color:var(--color-accent);text-transform:uppercase;letter-spacing:1px;font-family:var(--font-body)">Afdeling & Beschikbaarheid</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">
              <div class="form-group" style="margin:0">
                <label class="form-label" for="department">Afdeling <span class="required">*</span></label>
                <select id="department" name="department" class="form-control <?= isset($errors['department'])?'error':'' ?>" required>
                  <option value="" disabled <?= empty($_POST['department'])?'selected':'' ?>>Kies een afdeling</option>
                  <?php foreach ($departments as $dept): ?>
                  <option value="<?= htmlspecialchars($dept['slug']) ?>" <?= ($_POST['department'] ?? '') === $dept['slug'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dept['name']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
                <?php if (isset($errors['department'])): ?><span class="form-error"><?= $errors['department'] ?></span><?php endif; ?>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label" for="availability">Beschikbaarheid</label>
                <select id="availability" name="availability" class="form-control">
                  <option value="">Kies...</option>
                  <option value="Fulltime" <?= ($_POST['availability']??'')==='Fulltime'?'selected':'' ?>>Fulltime (40 uur)</option>
                  <option value="Parttime" <?= ($_POST['availability']??'')==='Parttime'?'selected':'' ?>>Parttime (20 uur)</option>
                  <option value="Weekenden" <?= ($_POST['availability']??'')==='Weekenden'?'selected':'' ?>>Weekenden</option>
                  <option value="Avonden" <?= ($_POST['availability']??'')==='Avonden'?'selected':'' ?>>Avonden</option>
                  <option value="Vrijwilliger" <?= ($_POST['availability']??'')==='Vrijwilliger'?'selected':'' ?>>Vrijwilliger</option>
                  <option value="Flexibel" <?= ($_POST['availability']??'')==='Flexibel'?'selected':'' ?>>Flexibel</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Motivatie & Ervaring -->
          <div style="background:rgba(30,58,95,0.25);border:1px solid rgba(0,180,216,0.1);border-radius:16px;padding:2rem;margin-bottom:1.75rem">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1.5rem;color:var(--color-accent);text-transform:uppercase;letter-spacing:1px;font-family:var(--font-body)">Motivatie & Ervaring</h3>
            <div class="form-group" style="margin-bottom:1.25rem">
              <label class="form-label" for="motivation">Motivatiebrief <span class="required">*</span></label>
              <textarea id="motivation" name="motivation" class="form-control <?= isset($errors['motivation'])?'error':'' ?>"
                style="min-height:180px" placeholder="Waarom wil jij bij SoulFM werken? Wat maakt jou de perfecte kandidaat? (minimaal 50 tekens)" required maxlength="3000"><?= htmlspecialchars($_POST['motivation'] ?? '') ?></textarea>
              <?php if (isset($errors['motivation'])): ?><span class="form-error"><?= $errors['motivation'] ?></span><?php endif; ?>
            </div>
            <div class="form-group" style="margin-bottom:1.25rem">
              <label class="form-label" for="experience">Relevante ervaring</label>
              <textarea id="experience" name="experience" class="form-control"
                style="min-height:120px" placeholder="Opleiding, werkervaring, hobby's die relevant zijn..." maxlength="2000"><?= htmlspecialchars($_POST['experience'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label" for="portfolio_url">Portfolio / LinkedIn URL</label>
              <input type="url" id="portfolio_url" name="portfolio_url" class="form-control <?= isset($errors['portfolio_url'])?'error':'' ?>"
                value="<?= htmlspecialchars($_POST['portfolio_url'] ?? '') ?>"
                placeholder="https://www.linkedin.com/in/jouwprofiel" maxlength="255">
              <?php if (isset($errors['portfolio_url'])): ?><span class="form-error"><?= $errors['portfolio_url'] ?></span><?php endif; ?>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
            <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            Sollicitatie versturen
          </button>
          <p style="text-align:center;color:var(--color-text-dim);font-size:0.82rem;margin-top:1rem">
            Jouw gegevens worden vertrouwelijk behandeld en alleen gedeeld met de betreffende afdeling.
          </p>
        </form>
      </div>

      <!-- Info sidebar -->
      <div style="position:sticky;top:100px">
        <div class="card" style="padding:1.75rem;margin-bottom:1.25rem">
          <h3 style="font-size:1.1rem;margin-bottom:1.25rem">Wat kun je verwachten?</h3>
          <div style="display:flex;flex-direction:column;gap:1rem">
            <?php
            $steps = [
                ['nr'=>'1','title'=>'Ontvangstbevestiging','desc'=>'We bevestigen je sollicitatie binnen 1 werkdag.'],
                ['nr'=>'2','title'=>'Beoordeling','desc'=>'Het afdelingshoofd bekijkt jouw sollicitatie.'],
                ['nr'=>'3','title'=>'Gesprek','desc'=>'Bij interesse nodigen we je uit voor een kennismaking.'],
                ['nr'=>'4','title'=>'Terugkoppeling','desc'=>'We laten je altijd weten wat ons besluit is.'],
            ];
            foreach ($steps as $s): ?>
            <div style="display:flex;gap:1rem;align-items:flex-start">
              <div style="width:28px;height:28px;background:rgba(0,180,216,0.15);border:1px solid rgba(0,180,216,0.3);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;color:var(--color-accent);flex-shrink:0"><?= $s['nr'] ?></div>
              <div>
                <div style="font-weight:600;font-size:0.9rem;color:var(--color-white)"><?= $s['title'] ?></div>
                <div style="font-size:0.82rem;color:var(--color-text-dim)"><?= $s['desc'] ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card" style="padding:1.75rem">
          <h3 style="font-size:1rem;margin-bottom:1rem">Contact</h3>
          <p style="color:var(--color-text-dim);font-size:0.88rem;margin-bottom:1rem">Vragen over solliciteren? Stuur een e-mail naar:</p>
          <a href="mailto:<?= htmlspecialchars(getSetting('contact_email','info@soulfm.nl')) ?>" style="color:var(--color-accent);font-weight:600;font-size:0.9rem">
            <?= htmlspecialchars(getSetting('contact_email','info@soulfm.nl')) ?>
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

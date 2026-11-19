<?php
/**
 * E-mail d'invitation ou de réinitialisation (HTML de messagerie : tableaux et styles en ligne).
 * Variables : $subject, $is_invite, $first_name, $sender, $email, $url, $validity, $site_name, $login_url.
 * lint-ok : e-mail HTML. Couleurs, tailles et police en ligne obligatoires (les messageries ignorent les variables CSS) :
 * reprendre la charte du projet, contraste 4,5:1 au moins. La version texte part toujours avec.
 */
declare(strict_types=1);

$h = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$encre = '#1d2026';
$doux = '#545a64';
$fond = '#f7f6f3';
$trait = '#d9d6cf';
$accent = '#9a4a07'; // contraste 6:1 sur blanc, texte blanc sur l'accent lisible
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title><?= $h($subject) ?></title>
</head>
<body style="margin:0;padding:0;background:<?= $fond ?>;">
<div style="display:none;max-height:0;overflow:hidden;"><?= $is_invite ? 'Créez votre mot de passe pour accéder à l\'administration du site.' : 'Choisissez un nouveau mot de passe pour votre compte.' ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?= $fond ?>;">
<tr><td align="center" style="padding:32px 12px 40px;">
  <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:560px;background:#ffffff;font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;color:<?= $encre ?>;">
    <tr><td style="padding:28px 40px 20px;border-bottom:1px solid <?= $trait ?>;font-size:15px;font-weight:700;"><?= $h($site_name) ?></td></tr>
    <tr><td style="padding:32px 40px 8px;">
      <h1 style="margin:0 0 20px;font-size:26px;line-height:1.25;"><?= $is_invite ? 'Bienvenue ' . $h($first_name) : 'Nouveau mot de passe' ?></h1>
      <p style="margin:0 0 16px;font-size:16px;line-height:1.65;">
        <?php if ($is_invite): ?>
          <?= $sender !== '' ? $h($sender) . ' vous a créé' : 'Un administrateur vous a créé' ?> un compte pour gérer le site. Pour l'activer, choisissez votre mot de passe.
        <?php else: ?>
          Une réinitialisation du mot de passe de votre compte d'administration a été demandée. Choisissez votre nouveau mot de passe avec le bouton ci-dessous.
        <?php endif; ?>
      </p>
    </td></tr>
    <tr><td style="padding:12px 40px 28px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="<?= $accent ?>" style="background:<?= $accent ?>;border-radius:6px;">
        <a href="<?= $h($url) ?>" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;"><?= $is_invite ? 'Créer mon mot de passe' : 'Choisir un nouveau mot de passe' ?></a>
      </td></tr></table>
    </td></tr>
    <tr><td style="padding:24px 40px 0;border-top:1px solid <?= $trait ?>;font-size:14px;line-height:1.65;">
      <p style="margin:0 0 6px;font-weight:700;">Ensuite, pour vous connecter</p>
      <p style="margin:0 0 20px;">Rendez-vous sur <a href="<?= $h($login_url) ?>" style="color:<?= $accent ?>;"><?= $h($login_url) ?></a> avec votre e-mail <strong><?= $h($email) ?></strong> et ce mot de passe<?= $is_invite ? ", puis activez la double authentification avec une application de votre téléphone" : "" ?>.</p>
      <p style="margin:0 0 6px;font-weight:700;">Le bouton ne fonctionne pas ?</p>
      <p style="margin:0;word-break:break-all;">Copiez ce lien dans votre navigateur :<br><a href="<?= $h($url) ?>" style="color:<?= $accent ?>;"><?= $h($url) ?></a></p>
    </td></tr>
    <tr><td style="padding:28px 40px 32px;font-size:13px;line-height:1.6;color:<?= $doux ?>;">
      Ce lien est personnel, valable <?= $h($validity) ?> et ne fonctionne qu'une seule fois.
      <?= $is_invite ? 'Si vous n\'attendiez pas ce message, ignorez-le simplement.' : 'Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message : votre mot de passe actuel reste valable.' ?>
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>

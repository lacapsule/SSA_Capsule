<?php

/** @var array $user */
/** @var array $str */
/** @var string|null $flash */
/** @var array<string>|null $errors */
/** @var string|null $accountPasswordAction */

$e = static fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$username = $e($user['username'] ?? '');
$email    = $e($user['email'] ?? '');
$role     = $e($user['role'] ?? '');
$action   = $e($accountPasswordAction ?? '/dashboard/account/password');
?>

<section class="account">
    <h1><?= $e($str['account.title'] ?? 'Mon compte') ?></h1>

    <div class="wrapper">
        <table class="table account__identity">
            <thead>
                <tr>
                    <th class='username'><?= $e($str['account.username'] ?? 'Utilisateur') ?></th>
                    <th>Rôle</th>
                    <th class='email'><?= $e($str['account.email'] ?? 'Email') ?></th>
                    <th>Gérer</th>

                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="usernameValue" name='Value'><?= $username ?></td>
                    <td class='<?= htmlspecialchars($user['role']) ?>'>
                        <p><?= $role ?></p>
                    </td>
                    <td class="emailValue" name='emailValue'><?= $email ?></td>
                    <td><button class="editBtn" type="button" onclick='editLeUser()'>Gérer</button></td>
                </tr>
            </tbody>

        </table>
    </div>


    <?php if (!empty($flash)): ?>
        <p class="notice notice--success"><?= $e($flash) ?></p>
    <?php endif; ?>

    <?php if (!empty($errors) && is_array($errors)): ?>
        <ul class="notice notice--error">
            <?php foreach ($errors as $msg): ?>
                <li><?= $e($msg) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div id="update-password-form">
        <h4><?= $e($str['account.change_password'] ?? 'Changer de mot de passe') ?></h4>
        <form method="post" action="<?= $action ?>" autocomplete="off" novalidate>
            <?= \CapsuleLib\Security\CsrfTokenManager::insertInput(); ?>

            <label for="old_password">
                <span><?= $e($str['account.old_password'] ?? 'Ancien mot de passe') ?></span>
            </label>



            <input
                type="password"
                name="old_password"
                id="old_password"
                required
                autocomplete="current-password"
                minlength="8">

            <label for="new_password">
                <span><?= $e($str['account.new_password'] ?? 'Nouveau mot de passe') ?></span>
            </label>
            <input
                type="password"
                name="new_password"
                id="new_password"
                required
                autocomplete="new-password"
                minlength="8">

            <label for="confirm_new_password">
                <span><?= $e($str['account.confirm_new_password'] ?? 'Confirmer le nouveau mot de passe') ?></span>
            </label>

            <input
                type="password"
                name="confirm_new_password"
                id="confirm_new_password"
                required
                autocomplete="new-password"
                minlength="8">

            <button type="submit" id="submit-update-password">
                <?= $e($str['account.update_password_cta'] ?? 'Mettre à jour') ?>
            </button>
        </form>
    </div>
</section>

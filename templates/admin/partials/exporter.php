<?php
/**
 * Menu « Exporter » (modules/export-tableur, primitive .adm-menu de la coquille) : Excel ou CSV.
 * @var string $adresse adresse du fichier, sans paramètre @var string $quoi ce qui est exporté (« les 140 lieux »)
 */
?>
<details class="adm-menu exp-menu" data-adm-menu>
  <summary class="btn btn--neutre"><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>Exporter<svg class="adm-ico exp-menu__chevron" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg></summary>
  <div class="adm-menu__panneau">
    <p class="adm-menu__entete"><strong>Exporter <?= e($quoi) ?></strong></p>
    <hr class="adm-menu__separateur">
    <?php if (App\Admin\Exports::excelPossible()): // extension zip de PHP absente : CSV seulement ?>
    <a class="adm-menu__item exp-choix" href="<?= e($adresse . '?format=xlsx') ?>" download><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 4h16v16H4zM4 9.5h16M4 15h16M10 4v16"/></svg><span class="exp-choix__textes"><span class="exp-choix__nom">Excel (.xlsx)</span><span class="exp-choix__aide">Pour un tableur (Excel, LibreOffice)</span></span></a>
    <?php endif ?>
    <a class="adm-menu__item exp-choix" href="<?= e($adresse . '?format=csv') ?>" download><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14 3H6v18h12V7zM14 3v4h4M8.5 12h7M8.5 16h7"/></svg><span class="exp-choix__textes"><span class="exp-choix__nom">CSV (.csv)</span><span class="exp-choix__aide">Texte pour un autre logiciel</span></span></a>
  </div>
</details>

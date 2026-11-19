<?php
/**
 * Fiche d'un événement (App\Agenda\AgendaAdmin), dans le gabarit commun admin/edition/form.
 * @var string $title @var ?array $e @var array<string, array<string, mixed>> $champs @var array<string, mixed> $valeurs
 * @var array<string, string> $erreurs
 */
use App\Admin\Admin;

$retour = Admin::url('agenda/');
$data = [
    'retour' => $retour,
    'retourLibelle' => 'Agenda',
    'action' => $e ? Admin::url('agenda/' . (int) $e['id'] . '/') : Admin::url('agenda/nouveau/'),
    'groupes' => ['L\'événement' => ['title', 'category'], 'Quand' => ['date', 'debut', 'fin', 'date_fin'], 'Où et comment' => ['address', 'description', 'links']],
    'suppression' => $e ? Admin::url('agenda/' . (int) $e['id'] . '/supprimer/') : null,
    'voir' => $e && (int) $e['is_published'] === 1 && $e['slug'] ? '/agenda/' . $e['slug'] . '/' : null,
    'nomFiche' => $e ? (string) $e['title'] : '',
];
extract($data);
require ROOT . '/templates/admin/edition/form.php';

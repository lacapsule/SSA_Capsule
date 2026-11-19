<?php
declare(strict_types=1);

/*
 * Deux tests d'exemple : une fonction pure, puis une écriture en base. À copier pour écrire les vôtres :
 * un nom qui dit ce qui est vérifié, une fonction qui reçoit la base neuve du test.
 */

return [
    'e() échappe le HTML et les guillemets' => function (PDO $db): void {
        $echapper = function_exists('e') ? 'e' : static fn (?string $v): string => htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        affirmer_egal('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $echapper('<script>alert("x")</script>'));
        affirmer_egal('l&#039;été', $echapper("l'été"));
        affirmer_egal('', $echapper(null));
    },

    'chaque test reçoit une base neuve, sans les écritures du test précédent' => function (PDO $db): void {
        $existe = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'essai'")->fetchColumn();
        affirmer_egal(0, (int) $existe, 'table laissée par un autre test');
        $db->exec('CREATE TABLE essai (id INTEGER PRIMARY KEY, texte TEXT NOT NULL)');
        $insertion = $db->prepare('INSERT INTO essai (texte) VALUES (?)');
        foreach (['un', 'deux', 'trois'] as $texte) {
            $insertion->execute([$texte]);
        }
        affirmer_egal(3, (int) $db->query('SELECT COUNT(*) FROM essai')->fetchColumn());
        affirmer_exception(static fn () => $db->exec('INSERT INTO essai (texte) VALUES (NULL)'), PDOException::class);
    },
];

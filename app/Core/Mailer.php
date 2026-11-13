<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Envoi d'e-mails par SMTP, sans dépendance : SSL implicite (465) ou STARTTLS obligatoire (587), AUTH LOGIN.
 * Message texte seul, ou texte et HTML (multipart/alternative). Toute valeur d'en-tête contenant un retour à la ligne est refusée
 * (injection d'en-têtes : Bcc ajouté par un champ de formulaire).
 *
 * Réglages, par ordre de priorité :
 *   1. MAIL_CATCHER=127.0.0.1:1025 (.env) : capteur local (Mailpit ou équivalent), en clair, adresse de bouclage seulement ;
 *      il prime pour qu'une préproduction n'envoie jamais de vrai e-mail ;
 *   2. Mailer::$configProvider : réglages lus ailleurs (back-office, base), mot de passe chiffré par Mailer::seal() ;
 *   3. .env : SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, MAIL_FROM, MAIL_FROM_NAME.
 * En local sans réglage, le message est écrit dans data/mail.log au lieu d'être envoyé.
 */
final class Mailer
{
    /** Cause du dernier échec, en clair (journal, tableau de bord). */
    public static ?string $lastError = null;

    /** Identifiant (sans chevrons) du dernier message composé. */
    public static string $lastMessageId = '';

    /**
     * Réglages venus d'ailleurs que le .env. Renvoie null si rien n'est réglé.
     * @var (callable(): (array{host: string, port: int, user: string, password: string, from?: string, from_name?: string}|null))|null
     */
    public static $configProvider = null;

    /** En-têtes composés par Mailer, jamais remplacés par $headers. */
    private const RESERVED = ['date', 'from', 'to', 'cc', 'bcc', 'subject', 'message-id', 'mime-version', 'content-type', 'content-transfer-encoding', 'reply-to', 'sender', 'return-path'];

    /** @return array{host: string, port: int, user: string, password: string, from: string, from_name: string, plain?: true}|null */
    public static function config(): ?array
    {
        $catcher = self::catcher();
        if ($catcher !== null) {
            return $catcher;
        }
        $cfg = self::$configProvider !== null ? (self::$configProvider)() : null;
        if ($cfg === null && ($_ENV['SMTP_HOST'] ?? '') !== '' && ($_ENV['SMTP_USER'] ?? '') !== '') {
            $cfg = [
                'host' => (string) $_ENV['SMTP_HOST'],
                'port' => (int) (($_ENV['SMTP_PORT'] ?? '') ?: 465),
                'user' => (string) $_ENV['SMTP_USER'],
                'password' => (string) ($_ENV['SMTP_PASSWORD'] ?? ''),
            ];
        }
        if ($cfg === null || $cfg['host'] === '' || $cfg['user'] === '' || $cfg['password'] === '') {
            return null;
        }
        return $cfg + ['from' => self::defaultFrom($cfg['user']), 'from_name' => self::defaultName()];
    }

    /**
     * Capteur d'e-mails local : SMTP en clair sans authentification, accepté seulement sur 127.0.0.1, localhost ou [::1].
     * @return array{host: string, port: int, user: string, password: string, from: string, from_name: string, plain: true}|null
     */
    public static function catcher(): ?array
    {
        $value = trim((string) ($_ENV['MAIL_CATCHER'] ?? ''));
        if ($value === '' || preg_match('/^(127\.0\.0\.1|localhost|\[::1\]):(\d{2,5})$/', $value, $m) !== 1) {
            return null;
        }
        return ['host' => $m[1], 'port' => (int) $m[2], 'user' => '', 'password' => '', 'from' => self::defaultFrom('capteur@exemple.invalid'), 'from_name' => self::defaultName(), 'plain' => true];
    }

    /**
     * @param string|null $html version HTML facultative (la version texte reste obligatoire : lecteurs, filtres, accessibilité)
     * @param array<string, string> $headers en-têtes en plus (In-Reply-To, References, List-Unsubscribe...)
     * @param list<array{nom: string, type: string, contenu: string}> $attachments pièces jointes (invitation .ics, reçu PDF), contenu brut
     * @return bool vrai si le message est parti (ou écrit dans data/mail.log en local)
     */
    public static function send(string $to, string $subject, string $text, ?string $html = null, ?string $replyTo = null, array $headers = [], array $attachments = []): bool
    {
        self::$lastError = null;
        $config = self::config();
        try {
            $from = $config['from'] ?? self::defaultFrom('dev@exemple.invalid');
            $message = self::compose($to, $subject, $text, $html, $replyTo, $headers, $from, $config['from_name'] ?? self::defaultName(), $attachments);
        } catch (\InvalidArgumentException $e) {
            self::$lastError = 'message refusé : ' . $e->getMessage();
            error_log('E-mail : ' . self::$lastError);
            return false;
        }

        if ($config === null) {
            if (!is_local()) {
                self::$lastError = 'serveur d\'envoi non réglé';
                error_log('E-mail : ' . self::$lastError);
                return false;
            }
            $dir = ROOT . '/data';
            is_dir($dir) || mkdir($dir, 0775, true);
            $entry = '[' . now() . "] À : $to" . ($replyTo !== null ? " (répondre à $replyTo)" : '') . "\nObjet : $subject\n\n$text\n"
                . ($html !== null ? "\n(version HTML jointe, " . strlen($html) . " octets)\n" : '')
                . implode('', array_map(static fn (array $a): string => "(pièce jointe : {$a['nom']}, " . strlen($a['contenu']) . " octets)\n", $attachments)) . str_repeat('-', 60) . "\n";
            return file_put_contents($dir . '/mail.log', $entry, FILE_APPEND | LOCK_EX) !== false;
        }

        try {
            self::smtp($config, $from, $to, $message);
            return true;
        } catch (\RuntimeException $e) {
            self::$lastError = $e->getMessage();
            error_log('SMTP : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Message complet (en-têtes et corps), après validation de chaque valeur.
     * @param array<string, string> $headers
     * @param list<array{nom: string, type: string, contenu: string}> $attachments
     * @throws \InvalidArgumentException adresse invalide, retour à la ligne dans un en-tête, en-tête réservé, pièce jointe mal formée
     */
    public static function compose(string $to, string $subject, string $text, ?string $html, ?string $replyTo, array $headers, string $from, string $fromName, array $attachments = []): string
    {
        $to = self::address($to, 'destinataire');
        $from = self::address($from, 'expéditeur');
        $replyTo = $replyTo !== null && $replyTo !== '' ? self::address($replyTo, 'adresse de réponse') : null;
        self::singleLine($subject, 'objet');
        self::singleLine($fromName, 'nom de l\'expéditeur');

        $domain = substr((string) strrchr($from, '@'), 1);
        self::$lastMessageId = bin2hex(random_bytes(16)) . '@' . $domain;
        $head = [
            'Date' => date(DATE_RFC2822),
            'From' => self::displayName($fromName) . " <$from>",
            'To' => "<$to>",
            'Subject' => self::encodeHeader($subject),
            'Message-ID' => '<' . self::$lastMessageId . '>',
            'MIME-Version' => '1.0',
        ];
        if ($replyTo !== null) {
            $head['Reply-To'] = "<$replyTo>";
        }
        foreach ($headers as $name => $value) {
            $name = (string) $name;
            if (preg_match('/^[A-Za-z][A-Za-z0-9-]{0,62}$/', $name) !== 1 || in_array(strtolower($name), self::RESERVED, true)) {
                throw new \InvalidArgumentException("en-tête « $name » refusé");
            }
            self::singleLine((string) $value, "en-tête $name");
            $head[$name] = self::encodeHeader((string) $value);
        }

        if ($html === null) {
            $partType = 'text/plain; charset=UTF-8';
            $partEncoding = 'base64';
            $body = chunk_split(base64_encode(self::crlf($text)));
        } else {
            $boundary = '=_' . bin2hex(random_bytes(12));
            $partType = "multipart/alternative; boundary=\"$boundary\"";
            $partEncoding = null;
            $body = "Ce message est au format MIME.\r\n\r\n"
                . "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode(self::crlf($text)))
                . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode(self::crlf($html)))
                . "--$boundary--\r\n";
        }
        if ($attachments === []) {
            $head['Content-Type'] = $partType;
            if ($partEncoding !== null) {
                $head['Content-Transfer-Encoding'] = $partEncoding;
            }
        } else {
            // multipart/mixed : le corps (texte, ou texte et HTML) puis chaque pièce jointe en base64.
            $mixed = '=_' . bin2hex(random_bytes(12));
            $head['Content-Type'] = "multipart/mixed; boundary=\"$mixed\"";
            $mixedBody = "Ce message est au format MIME.\r\n\r\n--$mixed\r\nContent-Type: $partType\r\n"
                . ($partEncoding !== null ? "Content-Transfer-Encoding: $partEncoding\r\n" : '') . "\r\n" . $body;
            foreach ($attachments as $a) {
                $nom = (string) ($a['nom'] ?? '');
                $type = (string) ($a['type'] ?? '');
                if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,99}$/', $nom) !== 1 || preg_match('#^[a-z]+/[a-z0-9.+-]+(; ?[a-z-]+=[A-Za-z0-9-]+)*$#', $type) !== 1) {
                    throw new \InvalidArgumentException('pièce jointe refusée (nom en ASCII simple, type MIME valide)');
                }
                $mixedBody .= "--$mixed\r\nContent-Type: $type; name=\"$nom\"\r\nContent-Transfer-Encoding: base64\r\n"
                    . "Content-Disposition: attachment; filename=\"$nom\"\r\n\r\n" . chunk_split(base64_encode((string) ($a['contenu'] ?? '')));
            }
            $body = $mixedBody . "--$mixed--\r\n";
        }
        $message = '';
        foreach ($head as $k => $v) {
            $message .= "$k: $v\r\n";
        }
        return $message . "\r\n" . $body;
    }

    /**
     * Connexion et authentification sans rien envoyer (bouton « Tester » d'une page de réglages). Une seule tentative.
     * @param array{host: string, port: int, user: string, password: string} $config
     * @return string|null cause du refus en clair, null si le serveur accepte
     */
    public static function check(array $config): ?string
    {
        try {
            self::smtp($config);
            return null;
        } catch (\RuntimeException $e) {
            $error = $e->getMessage();
            return match (true) {
                str_contains($error, ' 535') || str_contains($error, ': 535') => 'Le serveur refuse cet identifiant ou ce mot de passe.',
                str_starts_with($error, 'connexion impossible') => 'Le serveur ne répond pas : vérifiez son adresse et le port.',
                str_starts_with($error, 'STARTTLS') => 'Le serveur ne propose pas de connexion chiffrée sur ce port : essayez le port 465.',
                default => 'Connexion refusée (' . $error . ').',
            };
        }
    }

    /** Dernier échec passager (serveur injoignable, délai, réponse 4xx, réglage absent) : à retenter plus tard. */
    public static function temporary(): bool
    {
        $error = (string) self::$lastError;
        return $error === 'serveur d\'envoi non réglé'
            || str_starts_with($error, 'connexion impossible')
            || str_starts_with($error, 'délai dépassé')
            || preg_match('/réponse inattendue : 4\d\d/', $error) === 1;
    }

    // ---------------------------------------------------------------- mot de passe chiffré au repos

    /** Mot de passe SMTP chiffré (AES-256-GCM, sous-clé de l'application) avant enregistrement en base, préfixe « o1: ». */
    public static function seal(string $password): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($password, 'aes-256-gcm', Key::derive('smtp'), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Chiffrement indisponible (extension openssl).');
        }
        return 'o1:' . base64_encode($iv . $tag . $cipher);
    }

    /** Mot de passe en clair, chaîne vide si illisible (autre clé, valeur altérée) : traité comme absent, à saisir de nouveau. */
    public static function reveal(string $sealed): string
    {
        if (!str_starts_with($sealed, 'o1:')) {
            return '';
        }
        $raw = base64_decode(substr($sealed, 3), true);
        if ($raw === false || strlen($raw) <= 28) {
            return '';
        }
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', Key::derive('smtp'), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return $plain === false ? '' : $plain;
    }

    // ---------------------------------------------------------------- SMTP

    /**
     * Sans $from : connexion et authentification seulement, puis QUIT.
     * @param array{host: string, port: int, user: string, password: string, plain?: true} $config
     */
    private static function smtp(array $config, ?string $from = null, string $to = '', string $message = ''): void
    {
        $plain = !empty($config['plain']);
        $ssl = !$plain && $config['port'] === 465;
        $socket = @stream_socket_client(($ssl ? 'ssl://' : 'tcp://') . $config['host'] . ':' . $config['port'], $errno, $errstr, 15);
        if ($socket === false) {
            throw new \RuntimeException("connexion impossible : $errstr");
        }
        // Délai court par réponse : un serveur muet ne doit pas faire couper la page par l'hébergeur.
        stream_set_timeout($socket, 10);
        $step = 'accueil';
        $expect = static function (string $code) use ($socket, &$step): string {
            $reply = '';
            while (($line = fgets($socket, 1024)) !== false) {
                $reply .= $line;
                if (($line[3] ?? '') !== '-') {
                    break;
                }
            }
            if ($reply === '' && stream_get_meta_data($socket)['timed_out']) {
                throw new \RuntimeException("délai dépassé : pas de réponse du serveur à l'étape $step");
            }
            if (!str_starts_with($reply, $code)) {
                throw new \RuntimeException("réponse inattendue : " . trim($reply) . " (étape $step)");
            }
            return $reply;
        };
        $cmd = static function (string $line, string $code, string $name) use ($socket, $expect, &$step): string {
            // Nom d'étape seulement dans les erreurs : jamais l'identifiant, le mot de passe ni le message.
            $step = $name;
            fwrite($socket, $line . "\r\n");
            return $expect($code);
        };

        try {
            $expect('220');
            $ehlo = 'EHLO ' . self::heloName();
            $caps = $cmd($ehlo, '250', 'EHLO');
            if (!$ssl && !$plain) {
                if (stripos($caps, 'STARTTLS') === false) {
                    throw new \RuntimeException('STARTTLS non proposé par le serveur : envoi en clair refusé');
                }
                $cmd('STARTTLS', '220', 'STARTTLS');
                $method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : 0);
                if (stream_socket_enable_crypto($socket, true, $method) !== true) {
                    throw new \RuntimeException('STARTTLS refusé (certificat ou version TLS)');
                }
                $cmd($ehlo, '250', 'EHLO');
            }
            if (!$plain) {
                $cmd('AUTH LOGIN', '334', 'AUTH');
                $cmd(base64_encode($config['user']), '334', 'identifiant');
                $cmd(base64_encode($config['password']), '235', 'mot de passe');
            }
            if ($from !== null) {
                $cmd("MAIL FROM:<$from>", '250', 'MAIL FROM');
                $cmd("RCPT TO:<$to>", '25', 'RCPT TO');
                $cmd('DATA', '354', 'DATA');
                // Point en début de ligne doublé (RFC 5321, 4.5.2).
                $cmd((string) preg_replace('/^\./m', '..', $message) . "\r\n.", '250', 'envoi du message');
            }
            fwrite($socket, "QUIT\r\n");
        } finally {
            fclose($socket);
        }
    }

    // ---------------------------------------------------------------- en-têtes

    /** Adresse e-mail seule, sans nom ni retour à la ligne. */
    private static function address(string $email, string $label): string
    {
        $email = trim($email);
        if (preg_match('/[\r\n<>,;"]/', $email) === 1 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException("$label invalide");
        }
        return $email;
    }

    private static function singleLine(string $value, string $label): void
    {
        if (preg_match('/[\r\n\0]/', $value) === 1) {
            throw new \InvalidArgumentException("retour à la ligne interdit dans : $label");
        }
    }

    /** Nom affiché : mots encodés si non ASCII, chaîne entre guillemets si caractères spéciaux. */
    private static function displayName(string $name): string
    {
        if (preg_match('/[^\x20-\x7e]/', $name) === 1) {
            return self::encodeHeader($name);
        }
        return preg_match('/[()<>\[\]:;@\\\\,."]/', $name) === 1 ? '"' . addcslashes($name, '"\\') . '"' : $name;
    }

    /** Valeur d'en-tête : telle quelle en ASCII imprimable, sinon mots encodés (RFC 2047) de 45 octets au plus, repliés. */
    private static function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7e]/', $value) !== 1) {
            return $value;
        }
        $words = [];
        $chunk = '';
        foreach (preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            if (strlen($chunk . $char) > 45) {
                $words[] = '=?UTF-8?B?' . base64_encode($chunk) . '?=';
                $chunk = '';
            }
            $chunk .= $char;
        }
        if ($chunk !== '') {
            $words[] = '=?UTF-8?B?' . base64_encode($chunk) . '?=';
        }
        return implode("\r\n ", $words);
    }

    /** Fins de ligne normalisées en CRLF. */
    private static function crlf(string $text): string
    {
        return (string) preg_replace('/\r\n|\r|\n/', "\r\n", $text);
    }

    private static function heloName(): string
    {
        $host = (string) gethostname();
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9.-]{0,252}$/', $host) === 1 ? $host : 'localhost';
    }

    private static function defaultFrom(string $fallback): string
    {
        return (string) (($_ENV['MAIL_FROM'] ?? '') ?: $fallback);
    }

    private static function defaultName(): string
    {
        return (string) (($_ENV['MAIL_FROM_NAME'] ?? '') ?: config('app.name', ''));
    }
}

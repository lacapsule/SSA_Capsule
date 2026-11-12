<?php

declare(strict_types=1);

namespace App\Modules\Home;

use App\Support\Mailer;

final class ContactService
{
    private const RATE_LIMIT = 5;
    private const RATE_WINDOW_SECONDS = 600;
    private const MAX_NAME_LENGTH = 150;
    private const MAX_SUBJECT_LENGTH = 150;
    private const MAX_MESSAGE_LENGTH = 2000;

    public function __construct(
        private ContactRepository $repository,
        private string $toEmail,
        private string $fromEmail,
        private string $siteName
    ) {
    }

    /**
     * @param array{name?:string,email?:string,subject?:string,message?:string,honeypot?:string,bot_check?:string} $input
     * @return array{errors?:array<string,string>,data?:array<string,string>}
     */
    public function handle(array $input, string $ip): array
    {
        // Honeypots -> on fait comme si tout était OK pour éviter l'abus
        if (!empty($input['honeypot']) || !empty($input['bot_check'])) {
            return [];
        }

        $data = [
            'name' => trim((string)($input['name'] ?? '')),
            'email' => trim((string)($input['email'] ?? '')),
            'subject' => trim((string)($input['subject'] ?? '')),
            'message' => trim((string)($input['message'] ?? '')),
        ];

        $errors = $this->validate($data);

        if ($errors !== []) {
            return ['errors' => $errors, 'data' => $data];
        }

        if (!$this->checkRateLimit($ip)) {
            return [
                'errors' => ['_global' => 'Merci de patienter avant d’envoyer un nouveau message.'],
                'data' => $data,
            ];
        }

        $this->repository->create([
            'nom' => $data['name'],
            'email' => $data['email'],
            'message' => $data['message'],
            'ip' => $ip,
        ]);

        $subject = $data['subject'] !== '' ? $data['subject'] : 'Nouveau message du site';
        $body = $this->buildBody($data, $ip);

        $sent = Mailer::send(
            $this->toEmail,
            $subject,
            $body,
            $this->fromEmail,
            $this->siteName,
            $data['email'],
            $data['name']
        );

        if (!$sent) {
            return [
                'errors' => ['_global' => 'Impossible d\'envoyer votre message pour le moment.'],
                'data' => $data,
            ];
        }

        return [];
    }

    /**
     * @param array{name:string,email:string,subject:string,message:string} $data
     * @return array<string,string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if ($data['name'] === '' || mb_strlen($data['name']) < 2) {
            $errors['name'] = 'Merci d’indiquer votre nom.';
        } elseif (mb_strlen($data['name']) > self::MAX_NAME_LENGTH) {
            $errors['name'] = 'Nom trop long.';
        }

        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide.';
        } elseif (mb_strlen($data['email']) > 190) {
            $errors['email'] = 'Adresse email trop longue.';
        }

        if ($data['message'] === '' || mb_strlen($data['message']) < 10) {
            $errors['message'] = 'Votre message est trop court.';
        } elseif (mb_strlen($data['message']) > self::MAX_MESSAGE_LENGTH) {
            $errors['message'] = 'Votre message est trop long.';
        }

        if ($data['subject'] !== '' && mb_strlen($data['subject']) > self::MAX_SUBJECT_LENGTH) {
            $errors['subject'] = 'Sujet trop long.';
        }

        return $errors;
    }

    /**
     * @param array{name:string,email:string,subject:string,message:string} $data
     */
    private function buildBody(array $data, string $ip): string
    {
        $bodyMessage = mb_substr($data['message'], 0, self::MAX_MESSAGE_LENGTH);

        $lines = [
            "Nouveau message via le site {$this->siteName}",
            "Nom      : {$data['name']}",
            "Email    : {$data['email']}",
            "Sujet    : " . ($data['subject'] !== '' ? $data['subject'] : '[Sans sujet]'),
            "IP       : {$ip}",
            str_repeat('-', 40),
            $bodyMessage,
        ];

        return implode("\n", $lines);
    }

    private function checkRateLimit(string $ip): bool
    {
        $threshold = gmdate('Y-m-d H:i:s', time() - self::RATE_WINDOW_SECONDS);
        $recent = $this->repository->countSinceIp($ip, $threshold);

        return $recent < self::RATE_LIMIT;
    }
}



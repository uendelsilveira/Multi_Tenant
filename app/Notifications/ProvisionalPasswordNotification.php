<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TenantUser;
use Carbon\CarbonInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

/**
 * Não é enfileirada de propósito: é enviada de dentro de um job, e assim a
 * senha em texto nunca fica gravada no payload de uma fila.
 */
final class ProvisionalPasswordNotification extends Notification
{
    public function __construct(
        public readonly string $loginUrl,
        #[SensitiveParameter] public readonly string $plainPassword,
        public readonly CarbonInterface $expiresAt,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = $notifiable instanceof TenantUser ? $notifiable->email : '';

        return (new MailMessage)
            ->subject('Seu acesso ao painel está pronto')
            ->greeting('Olá!')
            ->line('O ambiente da sua empresa foi criado. Use os dados abaixo para o primeiro acesso.')
            ->line("E-mail: {$email}")
            ->line("Senha provisória: {$this->plainPassword}")
            ->action('Acessar o painel', $this->loginUrl)
            ->line("A senha provisória vale até {$this->expiresAt->format('d/m/Y H:i')}. No primeiro acesso você define uma senha nova.")
            ->line('Se o prazo passar, peça o reenvio a quem cadastrou sua empresa.');
    }
}

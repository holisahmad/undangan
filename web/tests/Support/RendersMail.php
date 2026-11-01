<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Renders a notification body the way the mailer does. Asserting on the output
 * catches template mistakes (undefined variables, broken links) that asserting
 * on MailMessage fields such as introLines never would, now that the messages
 * are Blade views instead of markdown lines.
 */
trait RendersMail
{
    protected function renderMail(MailMessage $mail): string
    {
        return $this->renderMailPart($mail, 'html');
    }

    protected function renderMailText(MailMessage $mail): string
    {
        return $this->renderMailPart($mail, 'text');
    }

    private function renderMailPart(MailMessage $mail, string $part): string
    {
        $view = $mail->view;

        $name = is_array($view) ? (string) ($view[$part] ?? '') : (string) $view;

        return view($name, $mail->viewData)->render();
    }
}

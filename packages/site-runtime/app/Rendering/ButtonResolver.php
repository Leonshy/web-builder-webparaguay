<?php

namespace App\Rendering;

/**
 * Un solo contrato de botón (§3.1 del Anexo A), resuelto a un enlace real.
 *
 * Los destinos de whatsapp / email / phone se toman de site.settings cuando
 * el botón no los especifica, para que el agente no los repita en cada botón.
 */
final class ButtonResolver
{
    /** @param array<string,mixed> $settings */
    public function __construct(private array $settings, private UrlContext $url, private SiteConfig $site) {}

    /**
     * @param  array<string,mixed>|null  $button
     * @return array{label:string,href:string,target:string,rel:?string,style:string,icon:?string,external:bool}|null
     */
    public function resolve(?array $button): ?array
    {
        if (! $button || empty($button['label']) || empty($button['action'])) {
            return null;
        }

        $href = match ($button['action']) {
            'url' => $this->urlHref($button),
            'anchor' => $this->url->homeAnchorHref($this->site, $button['anchor'] ?? ''),
            'whatsapp' => $this->whatsappHref($button),
            'email' => $this->emailHref($button),
            'phone' => $this->phoneHref(),
            default => null,
        };

        if ($href === null || $href === '') {
            return null;
        }

        $target = $button['target'] ?? '_self';
        $external = $target === '_blank' || str_starts_with($href, 'http') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'https://wa.me');

        return [
            'label' => (string) $button['label'],
            'href' => $href,
            'target' => $target,
            'rel' => ($target === '_blank') ? 'noopener noreferrer' : null,
            'style' => $button['style'] ?? 'primary',
            'icon' => $button['icon'] ?? null,
            'external' => $external,
        ];
    }

    /** @param array<string,mixed> $button */
    private function urlHref(array $button): ?string
    {
        return $button['url'] ?? null;
    }

    /** @param array<string,mixed> $button */
    private function whatsappHref(array $button): ?string
    {
        $number = $this->digits($this->settings['whatsapp'] ?? $this->settings['phone'] ?? '');
        if ($number === '') {
            return null;
        }

        $message = $button['whatsapp_message'] ?? null;

        return 'https://wa.me/'.$number.($message ? '?text='.rawurlencode($message) : '');
    }

    /** @param array<string,mixed> $button */
    private function emailHref(array $button): ?string
    {
        $to = $button['email_to'] ?? $this->settings['email'] ?? '';
        if ($to === '') {
            return null;
        }

        $query = [];
        if (! empty($button['email_subject'])) {
            $query['subject'] = $button['email_subject'];
        }

        return 'mailto:'.$to.($query ? '?'.http_build_query($query) : '');
    }

    private function phoneHref(): ?string
    {
        $number = $this->digits($this->settings['phone'] ?? $this->settings['whatsapp'] ?? '');

        return $number === '' ? null : 'tel:+'.$number;
    }

    private function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}

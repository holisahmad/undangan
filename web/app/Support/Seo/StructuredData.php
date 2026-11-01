<?php

declare(strict_types=1);

namespace App\Support\Seo;

/**
 * Structured data is assembled in PHP, never inline in Blade: an "@" prefixed
 * key inside a Blade expression is compiled as a directive and would corrupt
 * the JSON payload.
 */
final class StructuredData
{
    /**
     * @param  array<string, string>  $address  A schema.org PostalAddress, built by BusinessAddress.
     * @param  list<array{question: string, answer: string}>  $faqs
     * @param  list<array{name: string, url: string}>  $templates
     * @return array<string, mixed>
     */
    public static function home(
        string $siteName,
        string $homeUrl,
        string $description,
        string $email,
        string $logoUrl,
        string $legalName,
        ?string $telephone,
        array $address,
        ?string $mapsUrl,
        array $faqs,
        array $templates,
    ): array {
        $organization = array_filter([
            '@type' => 'Organization',
            '@id' => $homeUrl.'#organization',
            'name' => $siteName,
            // The product is Wedly, the studio behind it is the legal entity a
            // customer contracts with, so both are published.
            'legalName' => $legalName,
            'url' => $homeUrl,
            // Serp and knowledge-panel consumers expect an absolute URL
            // to a square logo, not the favicon.
            'logo' => $logoUrl,
            'description' => $description,
            'email' => $email,
            'telephone' => $telephone,
            'address' => $address === [] ? null : $address,
            'hasMap' => $mapsUrl,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                $organization,
                [
                    '@type' => 'WebSite',
                    '@id' => $homeUrl.'#website',
                    'url' => $homeUrl,
                    'name' => $siteName,
                    'inLanguage' => 'id-ID',
                    'publisher' => ['@id' => $homeUrl.'#organization'],
                ],
                [
                    '@type' => 'ItemList',
                    'name' => 'Contoh template undangan pernikahan',
                    'itemListElement' => array_map(static fn (array $template, int $index): array => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $template['name'],
                        'url' => $template['url'],
                    ], $templates, array_keys($templates)),
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(static fn (array $faq): array => [
                        '@type' => 'Question',
                        'name' => $faq['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $faq['answer'],
                        ],
                    ], $faqs),
                ],
            ],
        ];
    }

    /**
     * The public theme catalogue. Only the page being requested is listed, while
     * numberOfItems states the whole filtered result so a reader knows how much
     * is behind the load-more control.
     *
     * @param  list<array{name: string, url: string}>  $templates
     * @return array<string, mixed>
     */
    public static function catalog(
        string $homeUrl,
        string $url,
        string $title,
        string $description,
        int $total,
        array $templates,
    ): array {
        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'CollectionPage',
                    '@id' => $url.'#collection',
                    'name' => $title,
                    'url' => $url,
                    'description' => $description,
                    'inLanguage' => 'id-ID',
                    'isPartOf' => ['@id' => $homeUrl.'#website'],
                    'publisher' => ['@id' => $homeUrl.'#organization'],
                ],
                [
                    '@type' => 'ItemList',
                    'name' => $title,
                    'numberOfItems' => $total,
                    'itemListElement' => array_map(static fn (array $template, int $index): array => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $template['name'],
                        'url' => $template['url'],
                    ], $templates, array_keys($templates)),
                ],
            ],
        ];
    }
}

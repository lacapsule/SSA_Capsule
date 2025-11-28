<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Service pour générer les données structurées JSON-LD conformes à schema.org
 */
final class StructuredDataService
{
    private const BASE_URL = 'https://ssapaysdemorlaix.fr';
    private const ORGANIZATION_NAME = 'SSA Pays de Morlaix';
    private const ORGANIZATION_LOGO = 'https://ssapaysdemorlaix.fr/assets/img/logo.svg';
    
    /**
     * Génère les données structurées pour une Organisation
     * @param array<string,string> $contactPoint
     * @return array<string,mixed>
     */
    public static function organization(array $contactPoint = []): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => self::ORGANIZATION_NAME,
            'url' => self::BASE_URL,
            'logo' => self::ORGANIZATION_LOGO,
            'sameAs' => [
                // Ajoutez ici les réseaux sociaux si disponibles
            ],
        ];
        
        if (!empty($contactPoint)) {
            $data['contactPoint'] = [
                '@type' => 'ContactPoint',
                'contactType' => $contactPoint['type'] ?? 'customer service',
                'email' => $contactPoint['email'] ?? '',
                'telephone' => $contactPoint['phone'] ?? '',
                'areaServed' => 'FR',
                'availableLanguage' => ['French', 'Breton'],
            ];
        }
        
        return $data;
    }
    
    /**
     * Génère les données structurées pour un Site Web
     * @param string $searchAction
     * @return array<string,mixed>
     */
    public static function website(string $searchAction = ''): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => self::ORGANIZATION_NAME,
            'url' => self::BASE_URL,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $searchAction ?: self::BASE_URL . '/search?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
        
        return $data;
    }
    
    /**
     * Génère les données structurées pour un Article
     * @param array<string,mixed> $article
     * @return array<string,mixed>
     */
    public static function article(array $article): array
    {
        $publishedDate = $article['date'] ?? date('Y-m-d');
        $publishedTime = $article['time'] ?? '00:00';
        $publishedDateTime = $publishedDate . 'T' . $publishedTime . ':00';
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article['title'] ?? '',
            'description' => $article['summary'] ?? $article['description'] ?? '',
            'image' => $article['image'] ?? self::ORGANIZATION_LOGO,
            'datePublished' => $publishedDateTime,
            'dateModified' => $article['dateModified'] ?? $publishedDateTime,
            'author' => [
                '@type' => 'Organization',
                'name' => self::ORGANIZATION_NAME,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => self::ORGANIZATION_NAME,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => self::ORGANIZATION_LOGO,
                ],
            ],
        ];
        
        if (isset($article['url'])) {
            $data['mainEntityOfPage'] = [
                '@type' => 'WebPage',
                '@id' => $article['url'],
            ];
        }
        
        if (isset($article['author_name']) && $article['author_name'] !== '') {
            $data['author'] = [
                '@type' => 'Person',
                'name' => $article['author_name'],
            ];
        }
        
        return $data;
    }
    
    /**
     * Génère les données structurées pour un Événement
     * @param array<string,mixed> $event
     * @return array<string,mixed>
     */
    public static function event(array $event): array
    {
        $startDate = $event['start'] ?? '';
        $endDate = $event['end'] ?? '';
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event['title'] ?? '',
            'description' => $event['description'] ?? '',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'organizer' => [
                '@type' => 'Organization',
                'name' => self::ORGANIZATION_NAME,
                'url' => self::BASE_URL,
            ],
        ];
        
        if (isset($event['location'])) {
            $data['location'] = [
                '@type' => 'Place',
                'name' => $event['location'],
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Morlaix',
                    'addressCountry' => 'FR',
                ],
            ];
        }
        
        if (isset($event['url'])) {
            $data['url'] = $event['url'];
        }
        
        if (isset($event['image'])) {
            $data['image'] = $event['image'];
        }
        
        return $data;
    }
    
    /**
     * Génère les données structurées pour une Collection d'Événements
     * @param array<array<string,mixed>> $events
     * @return array<string,mixed>
     */
    public static function eventCollection(array $events): array
    {
        $structuredEvents = [];
        foreach ($events as $event) {
            $structuredEvents[] = self::event($event);
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => array_map(function ($event, $index) {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'item' => $event,
                ];
            }, $structuredEvents, array_keys($structuredEvents)),
        ];
    }
    
    /**
     * Génère les données structurées pour un BreadcrumbList
     * @param array<array{name:string,url:string}> $items
     * @return array<string,mixed>
     */
    public static function breadcrumbList(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(function ($item, $index) {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ];
            }, $items, array_keys($items)),
        ];
    }
    
    /**
     * Convertit un tableau de données structurées en JSON-LD
     * @param array<string,mixed>|array<array<string,mixed>> $data
     * @return string
     */
    public static function toJsonLd(array $data): string
    {
        // Si c'est un tableau d'objets, on les combine
        if (isset($data[0]) && is_array($data[0])) {
            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}


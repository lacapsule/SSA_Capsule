<?php

declare(strict_types=1);

use CapsuleLib\Security\Html;

/**
 * Échappe une chaîne pour un affichage HTML classique.
 * 
 * Empêche les injections XSS en encodant les caractères spéciaux.
 *
 * @param string|null $str La chaîne brute à échapper.
 * @return string La chaîne sécurisée pour HTML (texte entre balises).
 */
function secure_html(?string $str): string
{
    return Html::escape($str);
}

/**
 * Échappe une chaîne destinée à un attribut HTML (ex: value, href, alt).
 * 
 * Préserve les guillemets et échappe les caractères problématiques pour un attribut.
 *
 * @param string|null $str La valeur brute.
 * @return string La valeur sécurisée à injecter dans un attribut HTML.
 */
function secure_attr(?string $str): string
{
    return Html::escapeAttr($str);
}

/**
 * Valide et échappe une URL pour un attribut href/src.
 *
 * Évite les schémas dangereux (ex: `javascript:`), encode les caractères spéciaux.
 *
 * @param string|null $url L’URL brute.
 * @return string L’URL sécurisée pour injection dans HTML (href, src, etc.).
 */
function secure_url(?string $url): string
{
    return Html::escapeUrl($url);
}

/**
 * Échappe une chaîne injectée dans du JavaScript inline.
 *
 * Prévient les échappements non-intentionnels dans `var`, `alert()`, etc.
 *
 * @param string|null $str Chaîne à injecter dans JS inline.
 * @return string Chaîne sécurisée, échappée pour usage JavaScript.
 */
function secure_js(?string $str): string
{
    return Html::escapeJs($str);
}

/**
 * Échappe récursivement un tableau associatif ou un objet (DTO).
 *
 * Chaque valeur (string) sera sécurisée via `Html::escape()`.
 * Utile pour sécuriser les données en bloc avant affichage.
 *
 * @param array<string,mixed>|object $data Données brutes (array assoc ou DTO).
 * @return array<string,mixed> Données échappées (tableau associatif propre).
 */
function secure_data(array|object $data): array
{
    return Html::escapeArray($data);
}

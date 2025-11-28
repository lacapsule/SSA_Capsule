<!DOCTYPE html>
<html lang="{{str.lang}}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="{{str.meta_description}}">
  <meta name="keywords" content="{{str.meta_keywords}}">
  <meta name="author" content="{{str.meta_author}}">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
  <title>{{str.page_title}}</title>

  <link rel="preload" href="/assets/fonts/outfit.ttf" as="font" type="font/ttf" crossorigin>
  <link rel="preload" href="/assets/css/global.min.css" as="style">
  <link rel="preload" href="/modules/constants.js" as="script" crossorigin>
  <link rel="preload" href="/modules/utils/dom.js" as="script" crossorigin>
  <link rel="stylesheet" href="/assets/css/global.min.css">
  <link rel="icon" type="image/png" href="/assets/img/logoSSA.png">
  
  {{#ogTitle}}<meta property="og:title" content="{{ogTitle}}">{{/ogTitle}}
  {{#ogDescription}}<meta property="og:description" content="{{ogDescription}}">{{/ogDescription}}
  {{#ogImage}}<meta property="og:image" content="{{ogImage}}">{{/ogImage}}
  {{#ogUrl}}<meta property="og:url" content="{{ogUrl}}">{{/ogUrl}}
  <meta property="og:type" content="{{ogType}}">
  <meta property="og:site_name" content="SSA Pays de Morlaix">
  <meta property="og:locale" content="{{str.lang}}_FR">
  
  {{#twitterCard}}<meta name="twitter:card" content="{{twitterCard}}">{{/twitterCard}}
  {{#twitterTitle}}<meta name="twitter:title" content="{{twitterTitle}}">{{/twitterTitle}}
  {{#twitterDescription}}<meta name="twitter:description" content="{{twitterDescription}}">{{/twitterDescription}}
  {{#twitterImage}}<meta name="twitter:image" content="{{twitterImage}}">{{/twitterImage}}
  
  {{> partial:structured-data }}
</head>

<body>
  <a href="#main-content" class="skip-link">Aller au contenu principal</a>
  
  {{#showHeader}}
  {{> partial:public/header }}
  {{/showHeader}}

  <main id="main-content" role="main">
    {{{ content }}}
  </main>

  {{#showFooter}}
  {{> partial:public/footer }}
  {{/showFooter}}

  <link rel="modulepreload" href="/modules/gallery/lightbox.js">
  <script type="module" src="/main-public.js" defer></script>

</body>

</html>
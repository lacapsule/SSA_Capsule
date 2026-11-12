<!DOCTYPE html>
<html lang="{{str.lang}}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="{{str.meta_description}}">
  <meta name="keywords" content="{{str.meta_keywords}}">
  <meta name="author" content="{{str.meta_author}}">
  <title>{{str.page_title}}</title>

  <link rel="stylesheet" href="/assets/css/global.css">
  <link rel="stylesheet" href="/assets/css/modal.css">
  <link rel="icon" type="image/png" href="/assets/img/logoSSA.png">
</head>

<body>
  <a class="skip-link" href="#contenu">Aller au contenu</a>
  {{#showHeader}}
  {{> partial:public/header }}
  {{/showHeader}}

  <main id="contenu">
    {{{ content }}}
  </main>

  {{#showFooter}}
  {{> partial:public/footer }}
  {{/showFooter}}

  <script type="module" src="/main.js" defer></script>

</body>

</html>
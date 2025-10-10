<!DOCTYPE html>
<html lang="{{str.lang}}">
    
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="{{str.meta_description}}">
  <meta name="keywords" content="{{str.meta_keywords}}">
  <meta name="author" content="{{str.meta_author}}">
  <title>{{str.page_title}}</title>

  <link rel="stylesheet" href="/assets/css/config.css">
  <link rel="stylesheet" href="/assets/css/styles.css">
  <link rel="icon" type="image/png" href="/assets/img/logoSSA.png">
    </head>

    <body>
  {{#showHeader}}
    {{> partial:header }}
  {{/showHeader}}

  <main>
    {{{content}}}
  </main>

  {{#showFooter}}
    {{> partial:footer }}
  {{/showFooter}}

  <script src="/assets/js/script.js" defer></script>
</body>
</html>

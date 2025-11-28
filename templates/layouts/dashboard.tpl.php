<!DOCTYPE html>
<html lang="{{str.lang}}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{str.meta_description}}">
    <meta name="keywords" content="{{str.meta_keywords}}">
    <meta name="author" content="{{str.meta_author}}">
    <title>{{str.page_title}}</title>

    <link rel="stylesheet" href="/assets/css/dashboard.min.css">

    <link rel="icon" type="image/png" href="/assets/img/logoSSA.png">
</head>

<header>
    <input type="checkbox" id="menu-toggle">
    <label for="menu-toggle" class="hamburger">
        <span class="line"></span>
        <span class="line"></span>
        <span class="line"></span>
    </label>

    <nav class="navbar">
        <div class="contain">
            <h1>Bienvenue</h1> 
            <h2>{{user.username}}</h2>

            <ul>
                {{#each links}}
                <li>
                    <a href="{{url}}" class="{{#active}}active{{/active}}">
                        {{title}}
                    </a>
                    <img class="icons" src="/assets/icons/{{icon}}.svg" alt="" />
                </li>
                {{/each}}
            </ul>
            <div class="copyright">
                <p>Site web éco-conçu par <a href="">La Capsule</a></p>
            </div>
    </nav>
</header>

<body>

    <!-- Contenu principal (injecté depuis les pages) -->

        {{{ content }}}

    <script type="module" src="/main-dashboard.js" defer></script>

</body>

</html>
<!DOCTYPE html>
<html lang="{{str.lang}}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{str.meta_description}}">
    <meta name="keywords" content="{{str.meta_keywords}}">
    <meta name="author" content="{{str.meta_author}}">
    <title>{{str.page_title}}</title>

    <link rel="stylesheet" href="/assets/css/dashboard-sidebar.css">
    <link rel="stylesheet" href="/assets/css/module/variables.css">
    <!-- <link rel="stylesheet" href="assets/css/global.css"> -->
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
        <a href="/" class="logo-link">
            <img src="/assets/img/logo.svg" alt="{{str.nav_title}}" class="logo">
        </a>
        <h2>Dashboard</h2>
        <ul>
            {{#each links}}
            <li>
                <a class="icons" href="{{url}}" class="{{#active}}active{{/active}}">
                    <img src="/assets/icons/{{icon}}.svg" alt="" />
                    {{title}}
                </a>
            </li>
            {{/each}}
        </ul>
    </nav>

</header>

<body>

    <!-- Contenu principal (injecté depuis les pages) -->
    <div class="dashboard-content">
        {{{ content }}}
    </div>

    <script type="module" src="/main.js" defer></script>

</body>

</html>
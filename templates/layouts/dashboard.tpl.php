<!DOCTYPE html>
<html lang="{{str.lang}}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{title}} - Dashboard SSA</title>
  <link rel="stylesheet" href="/assets/css/config.css">
  <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="icon" type="image/png" href="/assets/img/logoSSA.png">
</head>
<body class="dashboard-layout">
    <section class="admin-dashboard">
        <!-- Sidebar avec navigation -->
        <aside class="dashboard-sidebar">
            <h2>Dashboard</h2>
            <ul>
            {{#each links}}
                <li>
                    <a href="{{url}}" class="{{#active}}active{{/active}}">
                        <img src="/assets/icons/{{icon}}.svg" alt="" />
                        {{title}}
                    </a>
                </li>
            {{/each}}
            </ul>
        </aside>

        <!-- Contenu principal (injecté depuis les pages) -->
        <div class="dashboard-content">
            {{{ content }}}
        </div>
    </section>

    <script src="/assets/js/dashboard.js" defer></script>
</body>
</html>

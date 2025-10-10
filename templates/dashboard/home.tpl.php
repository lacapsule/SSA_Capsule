<section class="admin-dashboard">
  <aside>
    <h2>Dashboard</h2>
    <ul>
    {{#each links}}
        <li>
        <a href="{{url}}">
            <img src="/assets/icons/{{icon}}.svg" alt="" />
            {{title}}
        </a>
        </li>
    {{/each}}
    </ul>
  </aside>

  <div class="dashboard-content">
    {{#component}}
      {{> component:@component }}
    {{/component}}
    {{^component}}
      <p>Bienvenue dans votre tableau de bord.</p>
    {{/component}}
  </div>
</section>

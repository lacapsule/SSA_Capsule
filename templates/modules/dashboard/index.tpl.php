<div class="dashboard-section">
    {{#flash.success}}
        <div class="alert alert-success">
            {{#each flash.success}}
                <p>{{.}}</p>
            {{/each}}
        </div>
    {{/flash.success}}

    {{#flash.error}}
        <div class="alert alert-error">
            {{#each flash.error}}
                <p>{{.}}</p>
            {{/each}}
        </div>
    {{/flash.error}}

    {{#component}}
        {{> component:@component }}
    {{/component}}

    {{^component}}
        <div class="dashboard-welcome">
            <h1>Bienvenue {{user.username}}</h1>
            <p>Sélectionnez une section dans le menu pour commencer.</p>
        </div>
    {{/component}}
</div>

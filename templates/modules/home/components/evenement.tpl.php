<section class="evenement section" id="evenement">
    <div class="contain">
        <div class="title">
            <div class="section-title">
                <h2>évènements</h2>
                <p>{{str.agenda_intro}}</p>
            </div>
        </div>

        <script type="application/json" id="home-events-data">
            {{{events_json}}}
        </script>
        <script type="application/json" id="home-categories-data">
            {{{categories_json}}}
        </script>
        <div class="evenement row" id="home-events-list" data-events-count="{{events_count}}">
            {{#each events}}
            <button type="button" class="evenement-item event-card" data-event-id="{{id}}">
                <div class="evenement-item-inner shadow-dark">
                    <div class="evenement-info info" style="background-color: {{#category_color}}{{{category_color}}}{{/category_color}}{{^category_color}}var(--ssa-jaune){{/category_color}};">
                        <div class="evenement-date">
                            <p>{{date_label}}</p>
                        </div>
                        <div class="evenement-time">
                            <p>{{time}}</p>
                        </div>
                    </div>
                    <div class="evenement-info desc">
                        <div class="event-header">
                            <h4 class="evenement-title">{{title}}</h4>
                        </div>
                        <p class="evenement-description">{{summary}}</p>
                    </div>
                </div>
            </button>
            {{/each}}
            {{^events}}
            <p class="no-event">{{str.no_upcoming_articles}}</p>
            {{/events}}
        </div>

        <div class="evenement-actions">
            <button type="button" class="btn-style-two calendar-open-btn" data-modal-open="public-calendar-modal">
                {{str.agenda_calendar_button}}
            </button>
        </div>
    </div>
</section>

<dialog id="home-event-modal" class="universal-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="home-event-modal-title">Événement</h2>
            <button type="button" class="modal-close-btn" data-close="home-event-modal"><span>&times;</span></button>
        </div>
        <div class="modal-body" id="home-event-modal-body">
            <p>Chargement...</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-annuler" data-close="home-event-modal">Fermer</button>
        </div>
    </div>
</dialog>

<dialog id="public-calendar-modal" class="universal-modal calendar-modal" data-modal-id="public-calendar-modal">
    <div class="modal-content">
        <div class="modal-calendar-header">
            <h2>{{str.agenda_calendar_modal_title}}</h2>
            <button type="button" class="modal-close-btn" data-modal-close="public-calendar-modal"
                aria-label="{{str.agenda_calendar_close}}">
                <span>&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="categories-legend-banner">
                <h3>Légende des catégories</h3>
                <div class="categories-list-legend">
                    {{#each categories}}
                    <div class="category-badge-legend">
                        <span class="category-color-dot" style="background-color: {{{color}}}"></span>
                        <span class="category-label">{{{label}}}</span>
                    </div>
                    {{/each}}
                    {{^categories}}
                    <p class="calendar-details-empty">Aucune catégorie disponible</p>
                    {{/categories}}
                </div>
            </div>
            <div class="calendar-controls">
                <div class="calendar-category-filter">
                    <label for="public-calendar-category">Filtrer par catégorie</label>
                    <select id="public-calendar-category">
                        <option value="all">Toutes</option>
                    </select>
                </div>
                <div class="calendar-view-switch">
                    <button type="button" class="calendar-view-btn is-active"
                        data-calendar-view="week">{{str.agenda_calendar_view_week}}</button>
                    <button type="button" class="calendar-view-btn"
                        data-calendar-view="month">{{str.agenda_calendar_view_month}}</button>
                    <button type="button" class="calendar-view-btn"
                        data-calendar-view="year">{{str.agenda_calendar_view_year}}</button>
                </div>
                <div class="calendar-nav">
                    <a type="button" class="calendar-nav-btn" data-calendar-nav="-1"
                        aria-label="Précédent"><img src="/assets/icons/arrow-left.svg" alt=""></a>
                    <div id="public-calendar-label">—</div>
                    <a type="button" class="calendar-nav-btn" data-calendar-nav="1" aria-label="Suivant"><img src="/assets/icons/arrow-right.svg" alt=""></a>
                </div>
            </div>
            <div id="public-calendar-details" class="calendar-details" data-empty="{{str.agenda_calendar_no_event}}"
                data-title="{{str.agenda_calendar_details_title}}">
                <p class="calendar-details-empty">{{str.agenda_calendar_no_event}}</p>
            </div>

            <div id="public-calendar-loading" class="calendar-loading" hidden>Chargement...</div>
            <p id="public-calendar-error" class="calendar-error" hidden></p>

            <div id="public-calendar-grid" class="calendar-grid calendar-grid--month" aria-live="polite"
                aria-label="{{str.agenda_calendar_modal_title}}"></div>

        </div>
    </div>
</dialog>
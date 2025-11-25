<section class="evenement section" id="evenement">
    <div class="contain">
        <div class="title">
            <div class="section-title">
                <h2>évènements</h2>
                <p>{{str.agenda_intro}}</p>
            </div>
        </div>

        <div class="evenement row">
            {{^articles}}
            <p class="no-event">{{str.no_upcoming_articles}}</p>
            {{/articles}}
            {{#each articles}}
            <div class="evenement-item ">
                <div class="evenement-item-inner shadow-dark">
                    <div class="evenement-info info">
                        <div class="evenement-date">
                            <p>{{date_event}}</p>
                        </div>
                        <div class="evenement-time">
                            <p>{{time}}</p>
                        </div>
                    </div>
                    <div class="evenement-info desc">
                        <h4 class="evenement-title">{{title}}</h4>
                        <p class="evenement-description">{{summary}}</p>
                    </div>
                </div>
            </div>
            {{/each}}
        </div>

        <div class="evenement-actions">
            <button type="button" class="btn calendar-open-btn" data-modal-open="public-calendar-modal">
                {{str.agenda_calendar_button}}
            </button>
        </div>
    </div>
</section>

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
            <div class="calendar-controls">
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